<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use Database\Factories\RecruiterOperations\AssessmentQuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A question, either in the Question Bank (no version) or inside one
 * assessment version. Adding a bank question to a quiz copies it, so bank
 * edits never reach a quiz, and a published version's questions are frozen.
 *
 * @property int $id
 * @property int|null $assessment_version_id
 * @property int|null $bank_question_id
 * @property QuestionType $type
 * @property string $prompt
 * @property string $prompt_hash
 * @property int $points
 * @property string|null $explanation
 * @property string|null $category
 * @property int $sort_order
 * @property bool $is_required
 * @property array<string, mixed>|null $metadata
 * @property QuestionSource $source
 * @property string|null $import_batch
 * @property int|null $import_row
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read AssessmentVersion|null $version
 * @property-read AssessmentQuestion|null $bankQuestion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentOption> $options
 * @property-read User|null $creator
 */
class AssessmentQuestion extends Model
{
    /** @use HasFactory<AssessmentQuestionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'ro_assessment_questions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'prompt',
        'points',
        'explanation',
        'category',
        'is_required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'source' => QuestionSource::class,
            'points' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
            'metadata' => 'array',
            'import_row' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AssessmentQuestion $question) {
            $question->prompt_hash = self::hashPrompt($question->prompt);
        });
    }

    /**
     * Duplicate key: trimmed, whitespace collapsed, case-insensitive.
     */
    public static function normalizePrompt(string $prompt): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $prompt)));
    }

    public static function hashPrompt(string $prompt): string
    {
        return sha1(self::normalizePrompt($prompt));
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'assessment_version_id');
    }

    /**
     * @return BelongsTo<AssessmentQuestion, $this>
     */
    public function bankQuestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bank_question_id')->withTrashed();
    }

    /**
     * @return HasMany<AssessmentOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(AssessmentOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isInBank(): bool
    {
        return $this->assessment_version_id === null;
    }

    /**
     * Bank questions can always be edited (quizzes hold copies); a version's
     * questions only while that version is a draft.
     */
    public function isEditable(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        return $this->isInBank() || ($this->version !== null && $this->version->isDraft());
    }

    /**
     * @return list<int>
     */
    public function correctOptionIds(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeBank(Builder $query): void
    {
        $query->whereNull('assessment_version_id');
    }
}
