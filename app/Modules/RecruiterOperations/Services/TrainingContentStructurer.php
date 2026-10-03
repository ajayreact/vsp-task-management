<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use Illuminate\Support\Facades\DB;

/**
 * Moves lessons to the structured, multi-language format, safely:
 *
 * - only draft versions are changed; a course whose latest version is
 *   published gets a new draft through the normal versioning service;
 * - English already stored as sections is never overwritten;
 * - a lesson's English is either its redesigned version (from the content
 *   files) or its existing written content converted into sections, word for
 *   word; placeholder lessons are left for a person to write;
 * - a Telugu draft is only added next to the redesigned English it was
 *   translated from, and always as "needs review".
 *
 * Every lesson is then checked for quality, which is what the report shows.
 */
class TrainingContentStructurer
{
    public const CONVERTED = 'Converted to sections';

    public const REDESIGNED = 'Redesigned content applied';

    public const KEPT = 'Already structured';

    public const NO_CONTENT = 'No written content: needs writing';

    public const NEEDS_DRAFT = 'Skipped: published, needs a draft';

    public const TELUGU_ADDED = 'Telugu draft added (needs review)';

    public const TELUGU_KEPT = 'Telugu already present';

    public const TELUGU_REFRESHED = 'Unreviewed Telugu draft refreshed';

    public const TELUGU_NONE = 'Not translated';

    /**
     * Lessons longer than this are flagged for shortening.
     */
    public const TARGET_MAX_WORDS = 700;

    public const MIN_WORDS = 80;

    public function __construct(
        protected TrainingContentService $content,
        protected TrainingLessonContentService $contents,
    ) {}

    /**
     * @return array{courses: int, versions: int, versions_by_status: array<string, int>, lessons: int, lessons_with_content: int, lessons_without_content: int, structured_english: int, telugu: int}
     */
    public function analyse(): array
    {
        $lessons = TrainingLesson::query()->with('contents')->get();
        $byStatus = [];

        foreach (TrainingContentStatus::cases() as $status) {
            $byStatus[$status->label()] = TrainingCourseVersion::query()->where('status', $status->value)->count();
        }

        $withContent = $lessons->filter(fn (TrainingLesson $lesson) => $this->contents->sectionsFor($lesson, TrainingLanguage::English) !== null)->count();

        return [
            'courses' => TrainingCourse::query()->count(),
            'versions' => TrainingCourseVersion::query()->count(),
            'versions_by_status' => $byStatus,
            'lessons' => $lessons->count(),
            'lessons_with_content' => $withContent,
            'lessons_without_content' => $lessons->count() - $withContent,
            'structured_english' => $lessons->filter(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English) !== null)->count(),
            'telugu' => $lessons->filter(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::Telugu) !== null)->count(),
        ];
    }

    /**
     * @param  array<int, array<string, array<string, list<array{kind: string, heading: string, body: string}>>>>  $redesigned  sections by level, lesson title and language code
     * @return list<array{level: int|null, course: string, version: string, lesson: string, english: string, telugu: string, words: int, english_status: string, telugu_status: string, issues: list<string>}>
     */
    public function structure(array $redesigned, ?User $actor, bool $dryRun): array
    {
        $courses = TrainingCourse::query()->with('category')->get()
            ->sortBy(fn (TrainingCourse $course) => [$course->category->level_number ?? PHP_INT_MAX, $course->id]);

        $report = [];

        foreach ($courses as $course) {
            array_push($report, ...$this->structureCourse($course, $this->forLevel($redesigned, $course), $actor, $dryRun));
        }

        return $report;
    }

    /**
     * @param  list<array{kind: string, heading: string, body: string}>|null  $english
     * @return list<string>
     */
    public function issues(string $title, ?array $english): array
    {
        $issues = [];

        if (trim($title) === '') {
            $issues[] = 'No title';
        }

        if ($english === null || $english === []) {
            return [...$issues, 'No written content'];
        }

        $text = TrainingLessonStructure::speakable($english);
        $words = TrainingLessonStructure::wordCount($english);

        if (TrainingContentPopulator::isPlaceholder(TrainingLessonStructure::toPlainText($english))) {
            $issues[] = 'Placeholder text';
        }

        if (str_contains(mb_strtolower($text), 'the lesson text recruiters read and listen to')) {
            $issues[] = 'Contains the form placeholder sentence';
        }

        if (! TrainingLessonStructure::has($english, TrainingSectionKind::Objective)) {
            $issues[] = 'No learning objective';
        }

        if (! TrainingLessonStructure::has($english, TrainingSectionKind::Takeaway)) {
            $issues[] = 'No key takeaway';
        }

        if ($words < self::MIN_WORDS) {
            $issues[] = 'Too little content';
        }

        if ($words > self::TARGET_MAX_WORDS) {
            $issues[] = 'Longer than the target length';
        }

        if (TrainingLessonStructure::longestParagraphWords($english) > TrainingLessonStructure::GIANT_PARAGRAPH_WORDS) {
            $issues[] = 'Very long paragraph';
        }

        foreach ($english as $section) {
            if (trim($section['heading']) === '' || in_array(mb_strtolower($section['heading']), ['section', 'heading', 'untitled'], true)) {
                $issues[] = 'Generic or empty heading';

                break;
            }
        }

        $paragraphs = [];

        foreach ($english as $section) {
            foreach (TrainingLessonStructure::blocks($section['body']) as $block) {
                if ($block['type'] === 'paragraph' && mb_strlen($block['text']) > 40) {
                    $paragraphs[] = mb_strtolower($block['text']);
                }
            }
        }

        if (count($paragraphs) !== count(array_unique($paragraphs))) {
            $issues[] = 'Repeated paragraph';
        }

        return $issues;
    }

    /**
     * @param  array<string, array<string, list<array{kind: string, heading: string, body: string}>>>  $redesigned
     * @return list<array{level: int|null, course: string, version: string, lesson: string, english: string, telugu: string, words: int, english_status: string, telugu_status: string, issues: list<string>}>
     */
    protected function structureCourse(TrainingCourse $course, array $redesigned, ?User $actor, bool $dryRun): array
    {
        return DB::transaction(function () use ($course, $redesigned, $actor, $dryRun) {
            $version = $course->versions()->where('status', TrainingContentStatus::Draft->value)->first()
                ?? $course->versions()->orderByDesc('version_number')->first();

            if ($version === null) {
                return [];
            }

            $lessons = $version->lessons()->with('contents')->get();
            $plans = $lessons->map(fn (TrainingLesson $lesson) => $this->plan($lesson, $redesigned[TrainingContentPopulator::normalizeTitle($lesson->title)] ?? []));
            $needsWork = $plans->contains(fn (array $plan) => $plan['english_action'] !== null || $plan['telugu_action'] !== null);

            $blocked = false;

            if (! $version->isDraft() && $needsWork) {
                if ($dryRun || $actor === null) {
                    $blocked = true;
                } else {
                    $version = $this->content->createVersion($course, $actor, $version);
                    $lessons = $version->lessons()->with('contents')->get();
                    $plans = $lessons->map(fn (TrainingLesson $lesson) => $this->plan($lesson, $redesigned[TrainingContentPopulator::normalizeTitle($lesson->title)] ?? []));
                }
            }

            $report = [];

            foreach ($lessons->values() as $index => $lesson) {
                $plan = $plans[$index];

                if (! $dryRun && ! $blocked && $actor !== null) {
                    $this->apply($lesson, $plan, $actor);
                }

                $report[] = $this->row($course, $version, $lesson, $plan, $blocked);
            }

            return $report;
        });
    }

    /**
     * What would change for one lesson, and the English that results.
     *
     * @param  array<string, list<array{kind: string, heading: string, body: string}>>  $redesigned
     * @return array{english_action: string|null, telugu_action: string|null, english: list<array{kind: string, heading: string, body: string}>|null, english_new: list<array{kind: string, heading: string, body: string}>|null, telugu_new: list<array{kind: string, heading: string, body: string}>|null, english_label: string, telugu_label: string}
     */
    protected function plan(TrainingLesson $lesson, array $redesigned): array
    {
        $storedEnglish = $lesson->contentIn(TrainingLanguage::English);
        $storedTelugu = $lesson->contentIn(TrainingLanguage::Telugu);
        $redesignedEnglish = isset($redesigned['en']) ? TrainingLessonStructure::normalize($redesigned['en']) : null;
        $redesignedTelugu = isset($redesigned['te']) ? TrainingLessonStructure::normalize($redesigned['te']) : null;

        $englishAction = null;
        $englishNew = null;

        if ($storedEnglish !== null) {
            $english = TrainingLessonStructure::normalize($storedEnglish->sections);
            $englishLabel = self::KEPT;
        } elseif ($redesignedEnglish !== null && $redesignedEnglish !== []) {
            $english = $englishNew = $redesignedEnglish;
            $englishAction = $englishLabel = self::REDESIGNED;
        } elseif (TrainingContentPopulator::isPlaceholder($lesson->body)) {
            $english = null;
            $englishLabel = self::NO_CONTENT;
        } else {
            $english = $englishNew = TrainingLessonStructure::fromPlainText($lesson->body);
            $englishAction = $englishLabel = self::CONVERTED;
        }

        $teluguAction = null;
        $teluguNew = null;

        $matchesRedesign = $redesignedTelugu !== null && $redesignedTelugu !== [] && $english !== null && $redesignedEnglish !== null
            && TrainingLessonStructure::fingerprint($english) === TrainingLessonStructure::fingerprint($redesignedEnglish);

        if ($storedTelugu !== null) {
            $teluguLabel = self::TELUGU_KEPT;

            if ($matchesRedesign && $this->untouched($storedTelugu)
                && TrainingLessonStructure::fingerprint(TrainingLessonStructure::normalize($storedTelugu->sections)) !== TrainingLessonStructure::fingerprint($redesignedTelugu)) {
                $teluguNew = $redesignedTelugu;
                $teluguAction = $teluguLabel = self::TELUGU_REFRESHED;
            }
        } elseif ($matchesRedesign) {
            $teluguNew = $redesignedTelugu;
            $teluguAction = $teluguLabel = self::TELUGU_ADDED;
        } else {
            $teluguLabel = self::TELUGU_NONE;
        }

        return [
            'english_action' => $englishAction,
            'telugu_action' => $teluguAction,
            'english' => $english,
            'english_new' => $englishNew,
            'telugu_new' => $teluguNew,
            'english_label' => $englishLabel,
            'telugu_label' => $teluguLabel,
        ];
    }

    /**
     * A stored translation nobody has reviewed or edited since it was
     * generated, so an improved draft from the content files may replace it.
     */
    protected function untouched(TrainingLessonContent $translation): bool
    {
        return $translation->review_status === TrainingContentReview::NeedsReview
            && $translation->reviewed_by_user_id === null
            && $translation->review_note === null
            && $translation->created_at->equalTo($translation->updated_at);
    }

    /**
     * @param  array{english_new: list<array{kind: string, heading: string, body: string}>|null, telugu_new: list<array{kind: string, heading: string, body: string}>|null}  $plan
     */
    protected function apply(TrainingLesson $lesson, array $plan, User $actor): void
    {
        if ($plan['english_new'] !== null) {
            $this->contents->save($lesson, TrainingLanguage::English, $plan['english_new'], $actor);
        }

        if ($plan['telugu_new'] !== null) {
            $this->contents->save($lesson->refresh(), TrainingLanguage::Telugu, $plan['telugu_new'], $actor);
        }
    }

    /**
     * @param  array{english: list<array{kind: string, heading: string, body: string}>|null, english_label: string, telugu_label: string, telugu_new: list<array{kind: string, heading: string, body: string}>|null}  $plan
     * @return array{level: int|null, course: string, version: string, lesson: string, english: string, telugu: string, words: int, english_status: string, telugu_status: string, issues: list<string>}
     */
    protected function row(TrainingCourse $course, TrainingCourseVersion $version, TrainingLesson $lesson, array $plan, bool $blocked): array
    {
        $lesson->load('contents');
        $issues = $this->issues($lesson->title, $plan['english']);
        $storedEnglish = $lesson->contentIn(TrainingLanguage::English);
        $storedTelugu = $lesson->contentIn(TrainingLanguage::Telugu);

        $englishStatus = match (true) {
            $plan['english'] === null => 'Placeholder',
            $issues !== [] => 'Incomplete',
            default => 'Complete',
        };

        $teluguStatus = match (true) {
            $storedTelugu !== null && $this->contents->isOutdated($lesson, $storedTelugu) => 'Review required (English changed)',
            $storedTelugu !== null && $storedTelugu->review_status === TrainingContentReview::Approved => 'Complete',
            $storedTelugu !== null || $plan['telugu_new'] !== null => 'Pending review',
            default => 'Not started',
        };

        return [
            'level' => $course->category->level_number ?? null,
            'course' => $course->title,
            'version' => 'Version '.$version->version_number.' ('.$version->status->label().')',
            'lesson' => $lesson->title,
            'english' => $blocked && $plan['english_label'] !== self::KEPT && $plan['english_label'] !== self::NO_CONTENT ? self::NEEDS_DRAFT : $plan['english_label'],
            'telugu' => $blocked && in_array($plan['telugu_label'], [self::TELUGU_ADDED, self::TELUGU_REFRESHED], true) ? self::NEEDS_DRAFT : $plan['telugu_label'],
            'words' => $plan['english'] === null ? 0 : TrainingLessonStructure::wordCount($plan['english']),
            'english_status' => $englishStatus.($storedEnglish?->review_status === TrainingContentReview::Approved ? ', approved' : ''),
            'telugu_status' => $teluguStatus,
            'issues' => $issues,
        ];
    }

    /**
     * @param  array<int, array<string, array<string, list<array{kind: string, heading: string, body: string}>>>>  $redesigned
     * @return array<string, array<string, list<array{kind: string, heading: string, body: string}>>>
     */
    protected function forLevel(array $redesigned, TrainingCourse $course): array
    {
        $level = $course->category->level_number ?? null;
        $lessons = [];

        foreach ($level === null ? [] : ($redesigned[$level] ?? []) as $title => $languages) {
            $lessons[TrainingContentPopulator::normalizeTitle($title)] = $languages;
        }

        return $lessons;
    }
}
