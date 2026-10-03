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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reorganises courses into modules and lessons from a written plan, inside
 * each course's draft version only:
 *
 * - a planned lesson is matched by its title, then by the title it replaces
 *   ("from"); matched lessons keep their content, translations and reviews;
 * - a new lesson gets the planned English, or English copied from a lesson of
 *   another course ("copy"), saved as Needs review; no translation is added;
 * - planned English replaces an existing lesson's English only while nobody
 *   has reviewed it;
 * - draft lessons the plan does not mention are removed from the draft. The
 *   published version, its lessons and its assignments are never touched;
 * - lessons the plan marks are flagged for compliance review, with the plan's
 *   review items as the note, unless a manager already decided on them.
 */
class TrainingCurriculumRestructurer
{
    public const KEPT = 'Kept';

    public const RENAMED = 'Renamed';

    public const COPIED = 'Copied';

    public const CREATED = 'New';

    public const REMOVED = 'Removed from draft';

    public const SKIPPED = 'Skipped: no draft version';

    public function __construct(
        protected TrainingContentService $content,
        protected TrainingLessonContentService $contents,
    ) {}

    /**
     * @param  list<array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}>  $plans
     * @return list<array{course: string, version: string, module: string, position: int|null, lesson: string, action: string, english: string, compliance: string}>
     */
    public function restructure(array $plans, ?User $actor, bool $dryRun): array
    {
        $report = [];

        foreach ($plans as $plan) {
            array_push($report, ...DB::transaction(fn () => $this->course($plan, $actor, $dryRun)));
        }

        return $report;
    }

    /**
     * @param  array{course: string, title: string, modules: array<string, list<array<string, mixed>>>}  $plan
     * @return list<array{course: string, version: string, module: string, position: int|null, lesson: string, action: string, english: string, compliance: string}>
     */
    protected function course(array $plan, ?User $actor, bool $dryRun): array
    {
        $course = TrainingCourse::query()->where('slug', Str::slug($plan['course']))->first();
        $version = $course?->versions()->where('status', TrainingContentStatus::Draft->value)->first();

        if ($course === null || $version === null) {
            return [[
                'course' => $plan['course'],
                'version' => '-',
                'module' => '-',
                'position' => null,
                'lesson' => '-',
                'action' => self::SKIPPED,
                'english' => '-',
                'compliance' => '-',
            ]];
        }

        /** @var Collection<int, TrainingLesson> $lessons */
        $lessons = $version->lessons()->with('contents')->get();
        $lessons->each(fn (TrainingLesson $lesson) => $lesson->setRelation('version', $version));
        $byTitle = $lessons->keyBy(fn (TrainingLesson $lesson) => $this->key($lesson->title));
        $label = 'Version '.$version->version_number.' (Draft)';

        $steps = [];
        $used = [];
        $position = 0;

        foreach ($plan['modules'] as $module => $entries) {
            foreach ($entries as $entry) {
                $title = (string) $entry['title'];
                $lesson = $byTitle->get($this->key($title));
                $action = self::KEPT;

                if ($lesson === null && isset($entry['from'])) {
                    $lesson = $byTitle->get($this->key((string) $entry['from']));
                    $action = self::RENAMED.' from "'.$entry['from'].'"';
                }

                if ($lesson !== null && isset($used[$lesson->id])) {
                    $lesson = null;
                }

                if ($lesson === null) {
                    $action = isset($entry['copy']) ? self::COPIED.' from '.$entry['copy'][0].' / "'.$entry['copy'][1].'"' : self::CREATED;
                } else {
                    $used[$lesson->id] = true;
                }

                $steps[] = ['entry' => $entry, 'module' => (string) $module, 'position' => ++$position, 'lesson' => $lesson, 'action' => $action];
            }
        }

        $removed = $lessons->reject(fn (TrainingLesson $lesson) => isset($used[$lesson->id]))->values();
        $report = [];

        if (! $dryRun && $actor !== null) {
            foreach ($removed as $lesson) {
                $this->content->deleteLesson($lesson, $actor);
            }
        }

        foreach ($removed as $lesson) {
            $report[] = [
                'course' => $course->title,
                'version' => $label,
                'module' => $lesson->module ?? '-',
                'position' => null,
                'lesson' => $lesson->title,
                'action' => self::REMOVED,
                'english' => '-',
                'compliance' => $lesson->compliance()->label(),
            ];
        }

        foreach ($steps as $step) {
            $lesson = $step['lesson'];
            $english = $this->plannedEnglish($step['entry']);

            if (! $dryRun && $actor !== null) {
                $lesson = $this->apply($version, $lesson, $step['entry'], $step['module'], $step['position'], $english, $actor);
            }

            $report[] = [
                'course' => $course->title,
                'version' => $label,
                'module' => $step['module'],
                'position' => $step['position'],
                'lesson' => (string) $step['entry']['title'],
                'action' => $step['action'],
                'english' => $this->englishState($lesson, $english),
                'compliance' => $this->complianceState($lesson, $step['entry']),
            ];
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  list<array{kind: string, heading: string, body: string}>|null  $english
     */
    protected function apply(TrainingCourseVersion $version, ?TrainingLesson $lesson, array $entry, string $module, int $position, ?array $english, User $actor): TrainingLesson
    {
        $title = (string) $entry['title'];

        if ($lesson === null) {
            $lesson = $this->content->createLesson($version, [
                'module' => $module,
                'title' => $title,
                'content_type' => 'text',
                'is_required' => true,
            ], null, $actor);
            $lesson->setRelation('version', $version);
        } elseif ($lesson->title !== $title || $lesson->module !== $module) {
            $lesson = $this->content->updateLesson($lesson, [
                'module' => $module,
                'title' => $title,
                'description' => $lesson->description,
                'content_type' => $lesson->content_type->value,
                'body' => $lesson->body,
                'duration_minutes' => $lesson->duration_minutes,
                'is_required' => $lesson->is_required,
                'external_url' => $lesson->external_url,
            ], null, $actor);
        }

        if ($lesson->sort_order !== $position) {
            $lesson->forceFill(['sort_order' => $position])->saveQuietly();
        }

        $lesson->load('contents');

        if ($english !== null && $this->mayWriteEnglish($lesson, $english)) {
            $this->contents->save($lesson, TrainingLanguage::English, $english, $actor);
            $lesson->load('contents');
        }

        if (($entry['compliance'] ?? false) === true && $lesson->compliance_status === null) {
            $lesson->forceFill([
                'compliance_status' => TrainingComplianceStatus::Pending,
                'compliance_note' => isset($entry['review']) ? (string) $entry['review'] : null,
            ])->save();
        }

        return $lesson;
    }

    /**
     * English to write: the planned sections, or a copy of another course's
     * lesson for a lesson that does not exist yet.
     *
     * @param  array<string, mixed>  $entry
     * @return list<array{kind: string, heading: string, body: string}>|null
     */
    protected function plannedEnglish(array $entry): ?array
    {
        if (isset($entry['en'])) {
            return TrainingLessonStructure::normalize((array) $entry['en']);
        }

        if (! isset($entry['copy'])) {
            return null;
        }

        [$courseTitle, $lessonTitle] = $entry['copy'];
        $course = TrainingCourse::query()->where('slug', Str::slug((string) $courseTitle))->first();
        $source = $course?->versions()->orderByDesc('version_number')->get()
            ->sortByDesc(fn (TrainingCourseVersion $version) => $version->isDraft())
            ->flatMap(fn (TrainingCourseVersion $version) => $version->lessons()->with(['contents', 'version'])->get())
            ->first(fn (TrainingLesson $lesson) => $this->key($lesson->title) === $this->key((string) $lessonTitle));

        return $source === null ? null : $this->contents->sectionsFor($source, TrainingLanguage::English);
    }

    /**
     * Planned English goes into a lesson with no English yet, or replaces
     * English nobody has reviewed. Reviewed work is never overwritten.
     *
     * @param  list<array{kind: string, heading: string, body: string}>  $english
     */
    protected function mayWriteEnglish(TrainingLesson $lesson, array $english): bool
    {
        $stored = $lesson->contentIn(TrainingLanguage::English);

        if ($stored === null) {
            return true;
        }

        if (TrainingLessonStructure::fingerprint(TrainingLessonStructure::normalize($stored->sections)) === TrainingLessonStructure::fingerprint($english)) {
            return false;
        }

        return $stored->review_status === TrainingContentReview::NeedsReview && $stored->reviewed_by_user_id === null;
    }

    /**
     * @param  list<array{kind: string, heading: string, body: string}>|null  $english
     */
    protected function englishState(?TrainingLesson $lesson, ?array $english): string
    {
        $stored = $lesson?->contentIn(TrainingLanguage::English);

        if ($stored === null) {
            return $english !== null ? TrainingContentReview::NeedsReview->labelFor(TrainingLanguage::English) : 'No written content';
        }

        if ($english !== null && $this->mayWriteEnglish($lesson, $english)) {
            return TrainingContentReview::NeedsReview->labelFor(TrainingLanguage::English);
        }

        return $stored->review_status->labelFor(TrainingLanguage::English);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function complianceState(?TrainingLesson $lesson, array $entry): string
    {
        $status = $lesson?->compliance_status;

        if ($status === null && ($entry['compliance'] ?? false) === true) {
            $status = TrainingComplianceStatus::Pending;
        }

        return $status === null ? TrainingComplianceStatus::NotRequired->label() : $status->label();
    }

    protected function key(string $title): string
    {
        return TrainingContentPopulator::normalizeTitle($title);
    }
}
