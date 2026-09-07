<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceRecurringExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewMyFinance') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'category' => ['required', Rule::enum(FinanceExpenseCategory::class)],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'active' => ['sometimes', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && is_string($this->input('title'))) {
            $this->merge(['title' => trim($this->string('title')->value())]);
        }

        if ($this->has('notes') && is_string($this->input('notes'))) {
            $notes = trim($this->string('notes')->value());
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }

        if ($this->has('end_date') && is_string($this->input('end_date')) && trim($this->input('end_date')) === '') {
            $this->merge(['end_date' => null]);
        }

        if ($this->has('active')) {
            $this->merge(['active' => $this->boolean('active')]);
        } else {
            $this->merge(['active' => true]);
        }
    }
}
