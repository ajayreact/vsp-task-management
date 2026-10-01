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
 * Published and archived versions are never edited. When a course has no
 * draft, a new draft is created through the normal versioning service (which
 * needs a manager to act as), and that draft is filled instead.
 */
class TrainingContentPopulator
{
    public const POPULATED = 'Populated';

    public const REPLACED_PLACEHOLDER = 'Populated (placeholder replaced)';

    public const KEPT = 'Kept existing content';

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
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    public function populate(array $curriculum, ?User $actor = null, bool $dryRun = false): array
    {
        $report = [];

        foreach ($curriculum as $entry) {
            array_push($report, ...$this->populateCourse($entry, $actor, $dryRun));
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
     * @param  array{level: int, course: string, lessons: array<string, string>}  $entry
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    protected function populateCourse(array $entry, ?User $actor, bool $dryRun): array
    {
        $row = fn (string $lesson, string $status, int $words = 0, ?TrainingCourseVersion $version = null) => [
            'level' => $entry['level'],
            'course' => $entry['course'],
            'version' => $version === null ? null : 'Version '.$version->version_number.' ('.$version->status->label().')',
            'lesson' => $lesson,
            'status' => $status,
            'words' => $words,
        ];

        $course = TrainingCourse::query()->where('slug', Str::slug($entry['course']))->first();

        if ($course === null) {
            return array_map(fn (string $title) => $row($title, self::COURSE_MISSING), array_keys($entry['lessons']));
        }

        $content = [];

        foreach ($entry['lessons'] as $title => $body) {
            $content[self::normalizeTitle($title)] = ['title' => $title, 'body' => trim($body)];
        }

        return DB::transaction(function () use ($course, $content, $actor, $dryRun, $row) {
            $version = $course->versions()->where('status', TrainingContentStatus::Draft->value)->first()
                ?? $course->versions()->orderByDesc('version_number')->first();

            if ($version === null) {
                return array_map(fn (array $item) => $row($item['title'], self::LESSON_MISSING), array_values($content));
            }

            if (! $version->isDraft() && $this->needsContent($version, $content)) {
                if ($actor === null || $dryRun) {
                    return $this->describe($version, $content, $row, self::NEEDS_DRAFT);
                }

                $version = $this->content->createVersion($course, $actor, $version);
            }

            $report = [];
            $matched = [];

            foreach ($version->lessons()->get() as $lesson) {
                $key = self::normalizeTitle($lesson->title);
                $available = $content[$key] ?? null;

                if ($available !== null) {
                    $matched[$key] = true;
                }

                if (! self::isPlaceholder($lesson->body)) {
                    $report[] = $row($lesson->title, self::KEPT, self::wordCount($lesson->body), $version);

                    continue;
                }

                if ($available === null || ! $version->isDraft()) {
                    $report[] = $row($lesson->title, self::NO_CONTENT, 0, $version);

                    continue;
                }

                $status = trim((string) $lesson->body) === '' ? self::POPULATED : self::REPLACED_PLACEHOLDER;

                if (! $dryRun) {
                    $this->fill($lesson, $available['body']);
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
     * @param  array<string, array{title: string, body: string}>  $content
     */
    protected function needsContent(TrainingCourseVersion $version, array $content): bool
    {
        return $version->lessons()->get()->contains(
            fn (TrainingLesson $lesson) => isset($content[self::normalizeTitle($lesson->title)]) && self::isPlaceholder($lesson->body),
        );
    }

    /**
     * @param  array<string, array{title: string, body: string}>  $content
     * @param  callable(string, string, int, TrainingCourseVersion): array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}  $row
     * @return list<array{level: int, course: string, version: string|null, lesson: string, status: string, words: int}>
     */
    protected function describe(TrainingCourseVersion $version, array $content, callable $row, string $emptyStatus): array
    {
        $report = [];

        foreach ($version->lessons()->get() as $lesson) {
            $report[] = self::isPlaceholder($lesson->body)
                ? $row($lesson->title, isset($content[self::normalizeTitle($lesson->title)]) ? $emptyStatus : self::NO_CONTENT, 0, $version)
                : $row($lesson->title, self::KEPT, self::wordCount($lesson->body), $version);
        }

        return $report;
    }

    protected function fill(TrainingLesson $lesson, string $body): void
    {
        TrainingLesson::query()->whereKey($lesson->id)->lockForUpdate()->first();
        $lesson->refresh();

        if (! self::isPlaceholder($lesson->body)) {
            return;
        }

        $lesson->forceFill(['body' => $body])->save();
    }
}
