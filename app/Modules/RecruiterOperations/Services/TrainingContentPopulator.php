<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills the written content of existing curriculum lessons. It never creates
 * courses or lessons and never overwrites meaningful text: only empty bodies
 * and known placeholder text are replaced.
 *
 * In refresh mode it also replaces a body that is exactly a previously shipped
 * generated body (matched by fingerprint), so an earlier release of the
 * generated content can be upgraded. Text edited in the app never matches a
 * fingerprint and is always kept.
 *
 * Published and archived versions are never edited. When a course has no
 * draft, a new draft is created through the normal versioning service (which
 * needs a manager to act as), and that draft is filled instead.
 */
class TrainingContentPopulator
{
    public const POPULATED = 'Populated';

    public const REPLACED_PLACEHOLDER = 'Populated (placeholder replaced)';

    public const REFRESHED = 'Refreshed (previous generated content replaced)';

    public const CURRENT = 'Already has the current content';

    public const KEPT = 'Kept existing content';

    public const KEPT_EDITED = 'Kept: edited in the app';

    public const NO_CONTENT = 'No written content available';

    public const COURSE_MISSING = 'Course not found';

    public const LESSON_MISSING = 'Lesson not found';

    public const NEEDS_DRAFT = 'Skipped: published, needs a draft';

    /**
     * Compared after lower-casing, trimming and dropping trailing punctuation.
     */
    public const PLACEHOLDERS = [
        'the lesson text recruiters read and listen to',
        'add content here',
        'add lesson content here',
        'lesson content',
        'lesson content here',
        'content',
        'content here',
        'content goes here',
        'placeholder',
        'tbd',
        'to be added',
        'todo',
        'coming soon',
        'write the lesson here',
        'written content',
    ];

    public function __construct(private readonly TrainingContentService $content) {}

    /**
     * @param  list<array{level: int, course: string, lessons: array<string, string>}>  $curriculum
     * @param  array<int, array<string, list<string>>>|null  $previous  fingerprints of earlier generated bodies by level and lesson title; null keeps the fill-only mode
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    public function populate(array $curriculum, ?User $actor = null, bool $dryRun = false, ?array $previous = null): array
    {
        $report = [];

        foreach ($curriculum as $entry) {
            array_push($report, ...$this->populateCourse($entry, $actor, $dryRun, $previous));
        }

        return $report;
    }

    public static function isPlaceholder(?string $body): bool
    {
        $text = Str::lower(trim((string) preg_replace('/\s+/u', ' ', (string) $body)));
        $text = rtrim($text, " .!:-\u{2026}");

        return $text === ''
            || in_array($text, self::PLACEHOLDERS, true)
            || str_starts_with($text, 'lorem ipsum');
    }

    public static function wordCount(?string $text): int
    {
        return count(preg_split('/\s+/u', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    public static function normalizeTitle(string $title): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $title)));
    }

    /**
     * Insensitive to line endings and surrounding whitespace, so a body saved
     * through a form with CRLF line breaks still matches.
     */
    public static function fingerprint(?string $body): string
    {
        return hash('sha256', trim(str_replace(["\r\n", "\r"], "\n", (string) $body)));
    }

    /**
     * @param  array{level: int, course: string, lessons: array<string, string>}  $entry
     * @param  array<int, array<string, list<string>>>|null  $previous
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    protected function populateCourse(array $entry, ?User $actor, bool $dryRun, ?array $previous): array
    {
        $row = fn (string $lesson, string $status, int $words = 0, ?TrainingCourseVersion $version = null) => [
            'level' => $entry['level'],
            'course' => $entry['course'],
            'version' => $version === null ? null : 'Version '.$version->version_number.' ('.$version->status->label().')',
            'lesson' => $lesson,
            'status' => $status,
            'words' => $words,
        ];

        $course = TrainingCourse::query()
            ->where('slug', Str::slug($entry['course']))
            ->whereHas('category', fn ($query) => $query->where('level_number', $entry['level']))
            ->first();

        if ($course === null) {
            return array_map(fn (string $title) => $row($title, self::COURSE_MISSING), array_keys($entry['lessons']));
        }

        $content = [];
        $earlier = [];

        foreach ($entry['lessons'] as $title => $body) {
            $content[self::normalizeTitle($title)] = ['title' => $title, 'body' => trim($body)];
        }

        foreach ($previous[$entry['level']] ?? [] as $title => $fingerprints) {
            $earlier[self::normalizeTitle($title)] = $fingerprints;
        }

        $refresh = $previous !== null;

        return DB::transaction(function () use ($course, $content, $earlier, $refresh, $actor, $dryRun, $row) {
            $version = $course->versions()->where('status', TrainingContentStatus::Draft->value)->first()
                ?? $course->versions()->orderByDesc('version_number')->first();

            if ($version === null) {
                return array_map(fn (array $item) => $row($item['title'], self::LESSON_MISSING), array_values($content));
            }

            $replaceable = function (TrainingLesson $lesson) use ($content, $earlier, $refresh): bool {
                $key = $this->lessonKey($lesson, $content);

                if ($key === null) {
                    return false;
                }

                if (self::isPlaceholder($lesson->body)) {
                    return true;
                }

                $current = self::fingerprint($lesson->body);

                return $refresh
                    && $current !== self::fingerprint($content[$key]['body'])
                    && in_array($current, $earlier[$key] ?? [], true);
            };

            $lessons = $version->lessons()->get();

            if (! $version->isDraft() && $lessons->filter($replaceable)->isNotEmpty()) {
                if ($actor === null || $dryRun) {
                    return $this->describe($version, $row, $replaceable);
                }

                $version = $this->content->createVersion($course, $actor, $version);
                $lessons = $version->lessons()->get();
            }

            $report = [];
            $matched = [];

            foreach ($lessons as $lesson) {
                $key = $this->lessonKey($lesson, $content);
                $available = $key === null ? null : $content[$key];

                if ($key !== null) {
                    $matched[$key] = true;
                }

                if ($available === null || ! $version->isDraft()) {
                    $report[] = self::isPlaceholder($lesson->body)
                        ? $row($lesson->title, self::NO_CONTENT, 0, $version)
                        : $row($lesson->title, self::KEPT, self::wordCount($lesson->body), $version);

                    continue;
                }

                if (! $replaceable($lesson)) {
                    $unchanged = self::fingerprint($lesson->body) === self::fingerprint($available['body']);
                    $status = match (true) {
                        ! $refresh => self::KEPT,
                        $unchanged => self::CURRENT,
                        default => self::KEPT_EDITED,
                    };
                    $report[] = $row($lesson->title, $status, self::wordCount($lesson->body), $version);

                    continue;
                }

                $status = match (true) {
                    trim((string) $lesson->body) === '' => self::POPULATED,
                    self::isPlaceholder($lesson->body) => self::REPLACED_PLACEHOLDER,
                    default => self::REFRESHED,
                };

                if (! $dryRun) {
                    $this->fill($lesson, $available['body'], $replaceable);
                }

                $report[] = $row($lesson->title, $status, self::wordCount($available['body']), $version);
            }

            foreach ($content as $key => $item) {
                if (! isset($matched[$key])) {
                    $report[] = $row($item['title'], self::LESSON_MISSING, 0, $version);
                }
            }

            return $report;
        });
    }

    /**
     * Matches on the lesson's stored slug first, then on its normalised title.
     *
     * @param  array<string, array{title: string, body: string}>  $content
     */
    protected function lessonKey(TrainingLesson $lesson, array $content): ?string
    {
        foreach ($content as $key => $item) {
            if ($lesson->slug === Str::slug($item['title'])) {
                return $key;
            }
        }

        $key = self::normalizeTitle($lesson->title);

        return isset($content[$key]) ? $key : null;
    }

    /**
     * @param  callable(string, string, int, TrainingCourseVersion): array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}  $row
     * @param  callable(TrainingLesson): bool  $replaceable
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    protected function describe(TrainingCourseVersion $version, callable $row, callable $replaceable): array
    {
        $report = [];

        foreach ($version->lessons()->get() as $lesson) {
            $report[] = match (true) {
                $replaceable($lesson) => $row($lesson->title, self::NEEDS_DRAFT, 0, $version),
                self::isPlaceholder($lesson->body) => $row($lesson->title, self::NO_CONTENT, 0, $version),
                default => $row($lesson->title, self::KEPT, self::wordCount($lesson->body), $version),
            };
        }

        return $report;
    }

    /**
     * @param  callable(TrainingLesson): bool  $replaceable
     */
    protected function fill(TrainingLesson $lesson, string $body, callable $replaceable): void
    {
        TrainingLesson::query()->whereKey($lesson->id)->lockForUpdate()->first();
        $lesson->refresh();

        if (! $replaceable($lesson)) {
            return;
        }

        $lesson->forceFill(['body' => $body])->save();
    }
}
