<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Review of lesson content. Review states are an internal marker only:
 * saved content is live without them.
 *
 * - each language of a lesson has its own review state; English comes first,
 *   and a translation can only be approved once its English is approved;
 * - lessons flagged for compliance need a separate compliance approval;
 * - only a manager sets a state, and only on lessons that can still be
 *   edited. Nothing here publishes, assigns or approves on its own.
 */
class TrainingContentReviewService
{
    public const OUTDATED_LABEL = 'English changed — review required';

    public const OVER_TARGET_WORDS = TrainingContentStructurer::TARGET_MAX_WORDS;

    public function __construct(protected TrainingLessonContentService $contents) {}

    /**
     * Every lesson managers are working on: each course's unpublished draft
     * while it has one, else its live version. In course level order, then
     * the order recruiters read them.
     *
     * @return Collection<int, TrainingLesson>
     */
    public function editableLessons(): Collection
    {
        $versions = TrainingCourseVersion::query()
            ->where(fn ($query) => $query
                ->where('status', TrainingContentStatus::Draft->value)
                ->orWhereIn('id', TrainingCourse::query()->whereNotNull('current_version_id')->select('current_version_id')))
            ->with(['course.category', 'lessons' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'lessons.contents.reviewer:id,name', 'lessons.complianceReviewer:id,name'])
            ->get()
            ->sortBy(fn (TrainingCourseVersion $version) => [$version->course->category->level_number ?? PHP_INT_MAX, $version->course_id, $version->isDraft() ? 0 : 1]);

        $lessons = new Collection;
        $seenCourses = [];

        foreach ($versions as $version) {
            if (isset($seenCourses[$version->course_id])) {
                continue;
            }
            $seenCourses[$version->course_id] = true;

            foreach ($version->lessons as $lesson) {
                $lesson->setRelation('version', $version);
                $lessons->push($lesson);
            }
        }

        return $lessons;
    }

    /**
     * The lessons a reviewer steps through for a language: all of them for
     * English, only those with a translation for any other language.
     *
     * @param  Collection<int, TrainingLesson>  $lessons
     * @return Collection<int, TrainingLesson>
     */
    public function queue(Collection $lessons, TrainingLanguage $language): Collection
    {
        if ($language->isCanonical()) {
            return $lessons->values();
        }

        return $lessons->filter(fn (TrainingLesson $lesson) => $lesson->contentIn($language) !== null)->values();
    }

    /**
     * @return array{status: string, label: string, outdated: bool, words: int, over_target: bool, reviewer: string|null, reviewed_at: string|null, note: string|null, updated_at: string|null, sections: list<array{kind: string, heading: string, body: string}>|null}|null
     */
    public function languageState(TrainingLesson $lesson, TrainingLanguage $language): ?array
    {
        $stored = $lesson->contentIn($language);
        $sections = $this->contents->sectionsFor($lesson, $language);

        if ($stored === null && $sections === null) {
            return null;
        }

        $status = $stored->review_status ?? TrainingContentReview::NeedsReview;
        $outdated = $stored !== null && $this->contents->isOutdated($lesson, $stored);
        $words = $sections === null ? 0 : TrainingLessonStructure::wordCount($sections);

        return [
            'status' => $outdated ? 'outdated' : $status->value,
            'label' => $outdated ? self::OUTDATED_LABEL : $status->labelFor($language),
            'outdated' => $outdated,
            'words' => $words,
            'over_target' => $language->isCanonical() && $words > self::OVER_TARGET_WORDS,
            'reviewer' => $stored?->reviewer?->name,
            'reviewed_at' => $stored?->reviewed_at?->toIso8601String(),
            'note' => $stored?->review_note,
            'updated_at' => ($stored->updated_at ?? $lesson->updated_at)->toIso8601String(),
            'sections' => $sections,
        ];
    }

    /**
     * @param  Collection<int, TrainingLesson>  $lessons
     * @return array{english: array{total: int, needs_review: int, in_review: int, approved: int, changes_requested: int}, telugu: array{available: int, pending: int, in_review: int, approved: int, changes_requested: int, outdated: int, not_started: int}, compliance: array{required: int, pending: int, approved: int, changes_requested: int}, over_target: int}
     */
    public function summary(Collection $lessons): array
    {
        $english = ['total' => $lessons->count(), 'needs_review' => 0, 'in_review' => 0, 'approved' => 0, 'changes_requested' => 0];
        $telugu = ['available' => 0, 'pending' => 0, 'in_review' => 0, 'approved' => 0, 'changes_requested' => 0, 'outdated' => 0, 'not_started' => 0];
        $compliance = ['required' => 0, 'pending' => 0, 'approved' => 0, 'changes_requested' => 0];
        $overTarget = 0;

        foreach ($lessons as $lesson) {
            $state = $this->languageState($lesson, TrainingLanguage::English);
            $english[$state === null ? 'needs_review' : $state['status']]++;
            $overTarget += $state !== null && $state['over_target'] ? 1 : 0;

            $translation = $this->languageState($lesson, TrainingLanguage::Telugu);

            if ($translation === null) {
                $telugu['not_started']++;
            } else {
                $telugu['available']++;
                $telugu[$translation['status'] === TrainingContentReview::NeedsReview->value ? 'pending' : $translation['status']]++;
            }

            $status = $lesson->compliance();

            if ($status->isRequired()) {
                $compliance['required']++;
                $compliance[$status->value]++;
            }
        }

        return ['english' => $english, 'telugu' => $telugu, 'compliance' => $compliance, 'over_target' => $overTarget];
    }

    public function setStatus(TrainingLesson $lesson, TrainingLanguage $language, TrainingContentReview $status, User $actor, ?string $note = null): TrainingLessonContent
    {
        $this->contents->ensureManager($actor);
        $this->contents->ensureEditable($lesson);

        $note = $this->cleanNote($note);

        if ($status === TrainingContentReview::ChangesRequested && $note === null) {
            throw ValidationException::withMessages(['note' => 'Say what needs to change.']);
        }

        return DB::transaction(function () use ($lesson, $language, $status, $actor, $note) {
            TrainingLesson::query()->whereKey($lesson->id)->lockForUpdate()->first();
            $lesson->load('contents');

            $content = $lesson->contentIn($language);

            if ($content === null) {
                throw ValidationException::withMessages([
                    'status' => $language->isCanonical()
                        ? 'This lesson has no structured English content to review yet.'
                        : 'This lesson has no '.$language->label().' translation to review.',
                ]);
            }

            $attributes = [
                'review_status' => $status,
                'reviewed_by_user_id' => $status === TrainingContentReview::NeedsReview ? null : $actor->id,
                'reviewed_at' => $status === TrainingContentReview::NeedsReview ? null : now(),
                'review_note' => $note,
            ];

            if ($status === TrainingContentReview::Approved && ! $language->isCanonical()) {
                $english = $lesson->contentIn(TrainingLanguage::English);

                if ($english === null || $english->review_status !== TrainingContentReview::Approved) {
                    throw ValidationException::withMessages([
                        'status' => 'Approve the English lesson first. A translation is approved against approved English.',
                    ]);
                }

                // The reviewer compared it with the current English, so it is no longer outdated.
                $attributes['source_fingerprint'] = TrainingLessonStructure::fingerprint(TrainingLessonStructure::normalize($english->sections));
            }

            $content->forceFill($attributes)->save();
            $lesson->unsetRelation('contents');

            return $content;
        });
    }

    public function setCompliance(TrainingLesson $lesson, TrainingComplianceStatus $status, User $actor, ?string $note = null): TrainingLesson
    {
        $this->contents->ensureManager($actor);
        $this->contents->ensureEditable($lesson);

        $note = $this->cleanNote($note);

        if ($status === TrainingComplianceStatus::ChangesRequested && $note === null) {
            throw ValidationException::withMessages(['note' => 'Say what needs to change.']);
        }

        $decided = $status === TrainingComplianceStatus::Approved || $status === TrainingComplianceStatus::ChangesRequested;

        $lesson->forceFill([
            'compliance_status' => $status,
            'compliance_reviewed_by_user_id' => $decided ? $actor->id : null,
            'compliance_reviewed_at' => $decided ? now() : null,
            'compliance_note' => $note,
            'updated_by_user_id' => $actor->id,
        ])->save();

        return $lesson;
    }

    /**
     * Marks draft lessons that need a compliance review, by the levels and
     * phrases in config. Lessons a manager already decided on are left alone.
     *
     * @return Collection<int, TrainingLesson> the lessons that are (or would be) flagged
     */
    public function flagCompliance(bool $dryRun): Collection
    {
        /** @var list<int> $levels */
        $levels = array_map('intval', (array) config('recruiter-training.compliance_review.levels', []));
        /** @var list<string> $phrases */
        $phrases = array_map(fn ($phrase) => mb_strtolower((string) $phrase), (array) config('recruiter-training.compliance_review.phrases', []));
        /** @var list<string> $titles */
        $titles = array_map(fn ($title) => mb_strtolower((string) $title), (array) config('recruiter-training.compliance_review.lessons', []));

        $flagged = $this->editableLessons()->filter(function (TrainingLesson $lesson) use ($levels, $phrases, $titles) {
            if ($lesson->compliance_status !== null) {
                return false;
            }

            if (in_array($lesson->version->course->category->level_number ?? null, $levels, true)) {
                return true;
            }

            if (in_array(mb_strtolower($lesson->title), $titles, true)) {
                return true;
            }

            $english = $this->contents->sectionsFor($lesson, TrainingLanguage::English);
            $text = mb_strtolower($english === null ? (string) $lesson->body : TrainingLessonStructure::toPlainText($english));

            foreach ($phrases as $phrase) {
                if ($phrase !== '' && str_contains($text, $phrase)) {
                    return true;
                }
            }

            return false;
        })->values();

        if (! $dryRun) {
            foreach ($flagged as $lesson) {
                $lesson->forceFill(['compliance_status' => TrainingComplianceStatus::Pending])->save();
            }
        }

        return $flagged;
    }

    /**
     * Whether a version's reviews allow publishing it. English approval is
     * required once a version has structured English content; lessons still
     * on the older single text predate the review workflow. Translations are
     * never required: learners fall back to English.
     *
     * @return array{english_required: bool, english_total: int, english_approved: int, compliance_required: int, compliance_approved: int, compliance_blocking: int, ready: bool}
     */
    public function readiness(TrainingCourseVersion $version): array
    {
        $lessons = $version->lessons()->with('contents')->get();

        $englishRequired = $lessons->contains(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English) !== null);
        $englishApproved = $lessons->filter(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English)?->review_status === TrainingContentReview::Approved)->count();
        $required = $lessons->filter(fn (TrainingLesson $lesson) => $lesson->compliance()->isRequired());
        $blocking = $required->filter(fn (TrainingLesson $lesson) => $lesson->compliance()->blocksPublishing())->count();

        return [
            'english_required' => $englishRequired,
            'english_total' => $lessons->count(),
            'english_approved' => $englishApproved,
            'compliance_required' => $required->count(),
            'compliance_approved' => $required->filter(fn (TrainingLesson $lesson) => $lesson->compliance() === TrainingComplianceStatus::Approved)->count(),
            'compliance_blocking' => $blocking,
            'ready' => (! $englishRequired || $englishApproved === $lessons->count()) && $blocking === 0,
        ];
    }

    /**
     * Refuses to publish a version whose reviews are not finished.
     */
    public function ensureReadyToPublish(TrainingCourseVersion $version): void
    {
        $readiness = $this->readiness($version);

        if ($readiness['ready']) {
            return;
        }

        $reasons = [];

        if ($readiness['english_required'] && $readiness['english_approved'] < $readiness['english_total']) {
            $reasons[] = ($readiness['english_total'] - $readiness['english_approved']).' of '.$readiness['english_total'].' lessons still need English approval';
        }

        if ($readiness['compliance_blocking'] > 0) {
            $reasons[] = $readiness['compliance_blocking'].' lessons still need compliance approval';
        }

        throw ValidationException::withMessages([
            'version' => 'This version cannot be published yet: '.implode(', and ', $reasons).'.',
        ]);
    }

    protected function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 2000);
    }
}
