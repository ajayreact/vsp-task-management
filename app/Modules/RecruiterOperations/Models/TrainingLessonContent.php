<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The structured content of one lesson in one language. English is the
 * source; other languages are translations of it. Like the lesson itself it
 * changes only while the lesson's version is a draft.
 *
 * Sections are plain data, never HTML:
 * list<array{kind: string, heading: string, body: string}>, where the body
 * uses the light text format of TrainingLessonStructure.
 *
 * @property int $id
 * @property int $lesson_id
 * @property TrainingLanguage $locale
 * @property list<array{kind: string, heading: string, body: string}> $sections
 * @property TrainingContentReview $review_status
 * @property string|null $source_fingerprint
 * @property int|null $updated_by_user_id
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingLesson $lesson
 * @property-read User|null $reviewer
 */
class TrainingLessonContent extends Model
{
    use LogsActivity;

    protected $table = 'ro_training_lesson_contents';

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
            'locale' => TrainingLanguage::class,
            'sections' => 'array',
            'review_status' => TrainingContentReview::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrainingLesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['lesson_id', 'locale', 'review_status', 'reviewed_by_user_id', 'review_note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
