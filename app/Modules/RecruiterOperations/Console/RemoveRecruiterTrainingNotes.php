<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the "Important note" block from every lesson of every draft
 * version: the note section of stored English and translations, or the note
 * paragraph of a lesson still on a single text. Published and archived
 * versions are never touched; they change when a draft replaces them. Saving
 * goes through the normal content rules, so edited content returns to review.
 */
class RemoveRecruiterTrainingNotes extends Command
{
    /** @var list<string> */
    public const NOTE_HEADINGS = ['important note', 'ముఖ్య గమనిక'];

    public const BODY_PATTERN = '/^[ \t]*Important note[ \t]*\R(?:[ \t]*\S.*(?:\R|\z))+(?:[ \t]*\R)?/mi';

    protected $signature = 'recruiter:training-remove-notes
        {--as= : Email of a training manager, who the changes are recorded against}
        {--dry-run : List the lessons that would change without saving anything}';

    protected $description = 'Remove the Important note from draft recruiter training lessons';

    public function handle(TrainingContentReviewService $reviews, TrainingLessonContentService $contents): int
    {
        if (! Schema::hasTable((new TrainingLessonContent)->getTable())) {
            $this->error('The recruiter training tables do not exist yet. Run the migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $actor = filled($this->option('as')) ? User::query()->where('email', (string) $this->option('as'))->first() : null;

        if (filled($this->option('as')) && $actor === null) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        if (! $dryRun && $actor === null) {
            $this->error('Pass --as=<training manager email> to save changes, or --dry-run to only report.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($reviews->editableLessons() as $lesson) {
            $changed = $this->strip($lesson, $contents, $actor, $dryRun);

            if ($changed !== []) {
                $rows[] = [
                    $lesson->version->course->category->level_number ?? '-',
                    $lesson->version->course->title,
                    $lesson->title,
                    implode(', ', $changed),
                ];
            }
        }

        $this->table(['Level', 'Course', 'Lesson', 'Removed from'], $rows);
        $this->info(($dryRun ? 'Dry run, nothing saved. Would update: ' : 'Lessons updated: ').count($rows));

        return self::SUCCESS;
    }

    /**
     * @return list<string> what the note was removed from
     */
    protected function strip(TrainingLesson $lesson, TrainingLessonContentService $contents, ?User $actor, bool $dryRun): array
    {
        $changed = [];
        $stored = $lesson->contents->keyBy(fn (TrainingLessonContent $content) => $content->locale->value);

        if ($stored->isEmpty()) {
            $body = (string) $lesson->body;
            $stripped = preg_replace(self::BODY_PATTERN, '', $body) ?? $body;

            if ($stripped !== $body && trim($stripped) !== '') {
                $changed[] = 'Lesson text';

                if (! $dryRun && $actor !== null) {
                    $contents->ensureManager($actor);
                    $contents->ensureEditable($lesson);
                    $lesson->forceFill(['body' => $stripped, 'updated_by_user_id' => $actor->id])->save();
                }
            }

            return $changed;
        }

        // English first, so translations saved afterwards record the new English.
        $languages = collect(TrainingLanguage::cases())->sortBy(fn (TrainingLanguage $language) => $language->isCanonical() ? 0 : 1);

        foreach ($languages as $language) {
            $content = $stored->get($language->value);

            if ($content === null) {
                continue;
            }

            $kept = array_values(array_filter($content->sections, fn (array $section) => ! $this->isNote($section)));

            if (count($kept) === count($content->sections) || $kept === []) {
                continue;
            }

            $changed[] = $language->label();

            if (! $dryRun && $actor !== null) {
                $contents->save($lesson, $language, $kept, $actor);
                $lesson->load('contents');
            }
        }

        return $changed;
    }

    /**
     * @param  array{kind?: mixed, heading?: mixed}  $section
     */
    protected function isNote(array $section): bool
    {
        return ($section['kind'] ?? null) === TrainingSectionKind::Note->value
            && in_array(mb_strtolower(trim((string) ($section['heading'] ?? ''))), self::NOTE_HEADINGS, true);
    }
}
