<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Rules\AssignableRecruiter;
use App\Modules\RecruiterOperations\Services\TrainingTrackCatalog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Assign a course to one recruiter, several, or the whole recruiter team.
 * The training track is chosen first and the course must belong to it. The
 * version is always the course's current published one, chosen on the
 * server.
 */
class TrainingAssignmentRequest extends FormRequest
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
        $mode = $this->input('mode');

        return [
            'track' => ['required', 'string', 'max:150'],
            'course_id' => ['required', 'integer', 'exists:ro_training_courses,id'],
            'mode' => ['required', 'in:individual,multiple,team'],
            'employee_ids' => $mode === 'team'
                ? ['prohibited']
                : ['required', 'array', 'min:1', 'max:500', ...($mode === 'individual' ? ['size:1'] : [])],
            'employee_ids.*' => ['integer', 'distinct', new AssignableRecruiter],
            'due_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['track', 'course_id'])) {
                    return;
                }

                try {
                    $track = app(TrainingTrackCatalog::class)->resolve((string) $this->input('track'));
                } catch (ModelNotFoundException) {
                    $validator->errors()->add('track', 'Choose a training track.');

                    return;
                }

                $course = TrainingCourse::query()->find((int) $this->input('course_id'));

                if ($course !== null && $course->training_track_id !== $track?->id) {
                    $validator->errors()->add('course_id', 'This course is not in the '.($track->name ?? 'selected').' training track.');
                }
            },
        ];
    }

    public function track(): ?TrainingTrack
    {
        return app(TrainingTrackCatalog::class)->resolve((string) $this->validated('track'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'track' => 'training track',
            'course_id' => 'course',
            'employee_ids' => 'recruiters',
            'employee_ids.*' => 'recruiter',
            'due_at' => 'due date',
        ];
    }
}
