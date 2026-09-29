<?php

namespace App\Modules\RecruiterOperations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recruiter's progress through one lesson of their assignment. Opening the
 * lesson sets started_at; completed_at is set only by the explicit "Mark
 * Lesson Complete" action. Listening to the audio updates
 * audio_progress_seconds and nothing else.
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $lesson_id
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $time_spent_seconds
 * @property int|null $audio_progress_seconds
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingAssignment $assignment
 * @property-read TrainingLesson $lesson
 */
class TrainingLessonCompletion extends Model
{
    protected $table = 'ro_training_lesson_completions';

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'time_spent_seconds' => 'integer',
            'audio_progress_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TrainingAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'assignment_id');
    }

    /**
     * @return BelongsTo<TrainingLesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
