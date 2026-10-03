<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Structured, multi-language lesson content.
 *
 * - English is the source. Saving it also refreshes the lesson's plain body,
 *   so everything that reads the body keeps working.
 * - A translation can only be written once the lesson has real English
 *   content, and remembers which English it was written from: when English
 *   changes, the translation shows as outdated until it is reviewed again.
 * - Like every lesson change, this only happens inside a draft version.
 */
class TrainingLessonContentService
{
    /**
     * Sections for a lesson in one language. English falls back to the older
     * single-block body, converted on the fly (nothing is stored). Other
     * languages have no fallback: null means "not translated", which is also
     * what learners get for a translation that is not yet approved.
     *
     * @return list<array{kind: string, heading: string, body: string}>|null
     */
    public function sectionsFor(TrainingLesson $lesson, TrainingLanguage $language): ?array
    {
        if (! $language->isCanonical()) {
            $content = $this->visibleTranslation($lesson, $language);

            return $content === null ? null : TrainingLessonStructure::normalize($content->sections);
        }

        $content = $lesson->contentIn($language);

        if ($content !== null) {
            return TrainingLessonStructure::normalize($content->sections);
        }

        if (TrainingContentPopulator::isPlaceholder($lesson->body)) {
            return null;
        }

        return TrainingLessonStructure::fromPlainText($lesson->body);
    }

    /**
     * The translation to show for a lesson. In a draft every stored
     * translation shows, so managers can review it. Once the version is
     * published only an approved, up-to-date translation reaches learners;
     * otherwise they get the English lesson. Translations are never published
     * just because the English was.
     */
    public function visibleTranslation(TrainingLesson $lesson, TrainingLanguage $language): ?TrainingLessonContent
    {
        $content = $lesson->contentIn($language);

        if ($content === null || $language->isCanonical() || $lesson->version->isDraft()) {
            return $content;
        }

        return $content->review_status === TrainingContentReview::Approved && ! $this->isOutdated($lesson, $content) ? $content : null;
    }

    /**
     * A translation counts as outdated when the English it was written from
     * has changed since.
     */
    public function isOutdated(TrainingLesson $lesson, TrainingLessonContent $translation): bool
    {
        if ($translation->locale->isCanonical()) {
            return false;
        }

        $english = $this->sectionsFor($lesson, TrainingLanguage::English);

        return $english === null || $translation->source_fingerprint !== TrainingLessonStructure::fingerprint($english);
    }

    /**
     * Saving never approves. Changed content goes back to Needs review and
     * loses its reviewer; saving the same content keeps its review. A change
     * to English also sends every translation of the lesson back to review,
     * so an outdated translation is never left approved, and an approved
     * compliance review back to pending.
     *
     * @param  list<array{kind?: mixed, heading?: mixed, body?: mixed}>  $sections
     */
    public function save(TrainingLesson $lesson, TrainingLanguage $language, array $sections, User $actor): TrainingLessonContent
    {
        $this->ensureManager($actor);
        $this->ensureDraft($lesson);

        $clean = TrainingLessonStructure::normalize($sections);

        if ($clean === []) {
            throw ValidationException::withMessages(['sections' => 'Add at least one section with content.']);
        }

        if (! TrainingLessonStructure::has($clean, TrainingSectionKind::Objective) && $language->isCanonical()) {
            throw ValidationException::withMessages(['sections' => 'Add a learning objective section.']);
        }

        return DB::transaction(function () use ($lesson, $language, $clean, $actor) {
            TrainingLesson::query()->whereKey($lesson->id)->lockForUpdate()->first();
            $lesson->load('contents');

            $previousEnglish = $this->sectionsFor($lesson, TrainingLanguage::English);
            $sourceFingerprint = null;

            if (! $language->isCanonical()) {
                $english = $this->sectionsFor($lesson, TrainingLanguage::English);

                if ($english === null) {
                    throw ValidationException::withMessages([
                        'sections' => 'Write and review the English lesson first. Translations are made from the approved English content.',
                    ]);
                }

                $sourceFingerprint = TrainingLessonStructure::fingerprint($english);
            }

            $existing = $lesson->contentIn($language);
            $changed = $existing === null
                || TrainingLessonStructure::fingerprint(TrainingLessonStructure::normalize($existing->sections)) !== TrainingLessonStructure::fingerprint($clean)
                || $existing->source_fingerprint !== $sourceFingerprint;

            /** @var TrainingLessonContent $content */
            $content = $existing ?? (new TrainingLessonContent)->forceFill([
                'lesson_id' => $lesson->id,
                'locale' => $language,
            ]);

            $content->forceFill([
                'sections' => $clean,
                'source_fingerprint' => $sourceFingerprint,
                'updated_by_user_id' => $actor->id,
            ]);

            if ($changed) {
                $content->forceFill([
                    'review_status' => TrainingContentReview::NeedsReview,
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                ]);
            }

            $content->save();

            if ($language->isCanonical()) {
                $lesson->forceFill([
                    'body' => TrainingLessonStructure::toPlainText($clean),
                    'updated_by_user_id' => $actor->id,
                ])->save();

                if ($previousEnglish === null || TrainingLessonStructure::fingerprint($previousEnglish) !== TrainingLessonStructure::fingerprint($clean)) {
                    if ($lesson->compliance_status === TrainingComplianceStatus::Approved) {
                        $lesson->forceFill([
                            'compliance_status' => TrainingComplianceStatus::Pending,
                            'compliance_reviewed_by_user_id' => null,
                            'compliance_reviewed_at' => null,
                        ])->save();
                    }

                    $lesson->contents()
                        ->where('locale', '!=', TrainingLanguage::English->value)
                        ->each(fn (TrainingLessonContent $translation) => $translation->forceFill([
                            'review_status' => TrainingContentReview::NeedsReview,
                            'reviewed_by_user_id' => null,
                            'reviewed_at' => null,
                        ])->save());
                }
            }

            $lesson->unsetRelation('contents');

            return $content;
        });
    }

    public function remove(TrainingLesson $lesson, TrainingLanguage $language, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($lesson);

        if ($language->isCanonical()) {
            throw ValidationException::withMessages(['language' => 'English is the source content and cannot be removed.']);
        }

        $lesson->contents()->where('locale', $language->value)->delete();
        $lesson->unsetRelation('contents');
    }

    public function ensureManager(User $actor): void
    {
        if (! $actor->can(Ability::RecruiterAccess->value) || ! $actor->can(Ability::ManageRecruiterTraining->value)) {
            throw new AuthorizationException('You cannot manage recruiter training.');
        }
    }

    public function ensureDraft(TrainingLesson $lesson): void
    {
        if (! $lesson->version->isDraft()) {
            throw ValidationException::withMessages([
                'version' => 'Published and archived versions cannot be changed. Create a new version instead.',
            ]);
        }
    }
}
