<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A recruiter's answer to one question in one attempt. Chosen options are
 * in ro_assessment_answer_options. Scoring columns are server-written.
 *
 * @property int $id
 * @property int $attempt_id
 * @property int $question_id
 * @property string|null $text_answer
 * @property bool|null $is_correct
 * @property int|null $awarded_points
 * @property bool $needs_review
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $reviewer_feedback
 * @property Carbon|null $answered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AssessmentAttempt $attempt
 * @property-read AssessmentQuestion $question
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentOption> $selectedOptions
 * @property-read User|null $reviewer
 */
class AssessmentAnswer extends Model
{
    protected $table = 'ro_assessment_answers';

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
            'is_correct' => 'boolean',
            'awarded_points' => 'integer',
            'needs_review' => 'boolean',
            'reviewed_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AssessmentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    /**
     * @return BelongsTo<AssessmentQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id')->withTrashed();
    }

    /**
     * @return BelongsToMany<AssessmentOption, $this>
     */
    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(AssessmentOption::class, 'ro_assessment_answer_options', 'answer_id', 'option_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isAwaitingReview(): bool
    {
        return $this->needs_review && $this->reviewed_at === null;
    }

    /**
     * @return list<int>
     */
    public function selectedOptionIds(): array
    {
        return $this->selectedOptions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }
}
