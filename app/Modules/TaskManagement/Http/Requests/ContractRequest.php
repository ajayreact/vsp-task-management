<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:64'],
            'contract_type' => ['required', Rule::enum(ContractType::class)],
            'country' => ['required', Rule::enum(ContractCountry::class)],
            'currency' => ['required', 'string', 'max:8'],
            'effective_date' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'tm_company_id' => ['required', 'integer', Rule::exists('tm_companies', 'id')],

            'provider' => ['required', 'array'],
            'provider.name' => ['required', 'string', 'max:255'],
            'provider.authorized_person' => ['nullable', 'string', 'max:255'],
            'provider.phone' => ['nullable', 'string', 'max:64'],
            'provider.email' => ['nullable', 'email', 'max:255'],
            'provider.website' => ['nullable', 'string', 'max:255'],
            'provider.address' => ['nullable', 'string', 'max:2000'],

            'client' => ['required', 'array'],
            'client.name' => ['required', 'string', 'max:255'],
            'client.authorized_person' => ['required', 'string', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:64'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.website' => ['nullable', 'string', 'max:255'],
            'client.address' => ['nullable', 'string', 'max:2000'],

            'service_plan' => ['required', 'array'],
            'service_plan.monthly_fee' => ['required'],
            'service_plan.currency' => ['required', 'string', 'max:8'],
            'service_plan.billing_frequency' => ['required', 'string', 'max:32'],

            'deliverables' => ['nullable', 'array'],
            'deliverables.*.quantity' => ['nullable'],
            'deliverables.*.name' => ['nullable', 'string', 'max:255'],
            'deliverables.*.description' => ['nullable', 'string', 'max:2000'],

            'extra_work' => ['nullable', 'array'],
            'extra_work.*.description' => ['nullable', 'string', 'max:2000'],
            'extra_work.*.fee' => ['nullable'],
            'extra_work.*.currency' => ['nullable', 'string', 'max:8'],
            'extra_work.*.affects_monthly_fee' => ['nullable', 'boolean'],

            'requirements' => ['nullable', 'array'],
            'requirements.*.label' => ['nullable', 'string', 'max:255'],
            'requirements.*.value' => ['nullable', 'string', 'max:2000'],

            'responsibilities' => ['nullable', 'array'],
            'responsibilities.*.text' => ['nullable', 'string', 'max:2000'],

            'campaign_objective' => ['nullable', 'array'],
            'campaign_objective.type' => ['nullable', 'string', 'max:64'],
            'campaign_objective.custom' => ['nullable', 'string', 'max:2000'],

            'client_content' => ['nullable', 'array'],
            'client_content.items' => ['nullable', 'array'],
            'client_content.description' => ['nullable', 'string', 'max:5000'],

            'lead_generation' => ['nullable', 'array'],
            'lead_pricing' => ['nullable', 'array'],
            'lead_pricing.*.lead_type' => ['nullable', 'string', 'max:255'],
            'lead_pricing.*.cpl' => ['nullable'],
            'lead_pricing.*.description' => ['nullable', 'string', 'max:2000'],

            'lead_example' => ['nullable', 'array'],
            'payment_terms' => ['nullable', 'array'],
            'custom_terms' => ['nullable', 'string', 'max:20000'],
            'document_logo' => ['nullable', 'string', 'max:500000'],
            'provider_signature' => ['nullable', 'string', 'max:255'],
            'provider_signature_date' => ['nullable', 'date'],
        ];
    }
}
