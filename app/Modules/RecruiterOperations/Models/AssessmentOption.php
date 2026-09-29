<?php

namespace App\Modules\RecruiterOperations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An answer choice. `is_correct` is the answer key: it is never sent to a
 * recruiter before they submit.
 *
 * @property int $id
 * @property int $question_id
 * @property string $option_key
 * @property string $text
 * @property bool $is_correct
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AssessmentQuestion $question
 */
class AssessmentOption extends Model
{
    protected $table = 'ro_assessment_options';

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @var list<string>
     */
    protected $hidden = ['is_correct'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AssessmentQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id')->withTrashed();
    }
}
