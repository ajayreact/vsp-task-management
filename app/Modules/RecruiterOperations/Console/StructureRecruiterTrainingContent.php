<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Services\TrainingContentStructurer;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Analyses the training content, moves lessons to structured sections
 * (English, plus Telugu where a reviewed translation ships), and reports the
 * quality of every lesson. Only draft versions change: a course with only a
 * published version gets a new draft, which needs --as.
 */
class StructureRecruiterTrainingContent extends Command
{
    protected $signature = 'recruiter:training-structure
        {--as= : Email of a training manager, who the changes and any new draft versions are recorded against}
        {--dry-run : Analyse and report without saving anything}';

    protected $description = 'Convert recruiter training lessons to structured, multi-language content and report on quality';

    public function handle(TrainingContentStructurer $structurer): int
    {
        if (! Schema::hasTable((new TrainingCourse)->getTable()) || ! Schema::hasTable((new TrainingLessonContent)->getTable())) {
            $this->error('The recruiter training tables do not exist yet. Run the migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $actor = null;

        if (filled($this->option('as'))) {
            $actor = User::query()->where('email', (string) $this->option('as'))->first();

            if ($actor === null) {
                $this->error('No user with that email.');

                return self::FAILURE;
            }
        }

        if (! $dryRun && $actor === null) {
            $this->error('Pass --as=<training manager email> to save changes, or --dry-run to only report.');

            return self::FAILURE;
        }

        $before = $structurer->analyse();
        $this->info('Before');
        $this->analysis($before);

        $report = $structurer->structure(RecruiterTrainingContent::redesigned(), $actor, $dryRun);

        $this->newLine();
        $this->table(
            ['Level', 'Version', 'Lesson', 'English', 'Telugu', 'Words', 'Issues'],
            array_map(fn (array $row) => [
                $row['level'] ?? '-',
                $row['version'],
                $row['lesson'],
                $row['english'],
                $row['telugu'],
                $row['words'],
                implode('; ', $row['issues']) ?: '-',
            ], $report),
        );

        $count = fn (callable $filter) => count(array_filter($report, $filter));

        $this->newLine();
        $this->info(($dryRun ? 'Dry run, nothing saved. ' : '').'Changes');
        $this->line('Converted to sections: '.$count(fn (array $row) => $row['english'] === TrainingContentStructurer::CONVERTED));
        $this->line('Redesigned content applied: '.$count(fn (array $row) => $row['english'] === TrainingContentStructurer::REDESIGNED));
        $this->line('Already structured, kept: '.$count(fn (array $row) => $row['english'] === TrainingContentStructurer::KEPT));
        $this->line('Telugu drafts added: '.$count(fn (array $row) => $row['telugu'] === TrainingContentStructurer::TELUGU_ADDED));
        $this->line('Unreviewed Telugu drafts refreshed: '.$count(fn (array $row) => $row['telugu'] === TrainingContentStructurer::TELUGU_REFRESHED));

        $this->newLine();
        $this->info('Quality');
        $this->line('Total lessons: '.count($report));
        $this->line('English complete: '.$count(fn (array $row) => str_starts_with($row['english_status'], 'Complete')));
        $this->line('English incomplete: '.$count(fn (array $row) => str_starts_with($row['english_status'], 'Incomplete')));
        $this->line('English approved by a reviewer: '.$count(fn (array $row) => str_ends_with($row['english_status'], 'approved')));
        $this->line('Placeholder lessons: '.$count(fn (array $row) => $row['english_status'] === 'Placeholder'));
        $this->line('Telugu complete: '.$count(fn (array $row) => $row['telugu_status'] === 'Complete'));
        $this->line('Telugu pending review: '.$count(fn (array $row) => $row['telugu_status'] === 'Pending review'));
        $this->line('Telugu review required (English changed): '.$count(fn (array $row) => str_starts_with($row['telugu_status'], 'Review required')));
        $this->line('Telugu not started: '.$count(fn (array $row) => $row['telugu_status'] === 'Not started'));
        $this->line('Lessons requiring manual review: '.$count(fn (array $row) => $row['issues'] !== []));

        if (! $dryRun) {
            $this->newLine();
            $this->info('After');
            $this->analysis($structurer->analyse());
        }

        if ($count(fn (array $row) => $row['english'] === TrainingContentStructurer::NEEDS_DRAFT) > 0) {
            $this->warn('Some courses only have a published version. Re-run with --as=<training manager email> to create a new draft and convert it.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{courses: int, versions: int, versions_by_status: array<string, int>, lessons: int, lessons_with_content: int, lessons_without_content: int, structured_english: int, telugu: int}  $analysis
     */
    protected function analysis(array $analysis): void
    {
        $statuses = implode(', ', array_map(fn (string $status, int $count) => "{$status} {$count}", array_keys($analysis['versions_by_status']), $analysis['versions_by_status']));

        $this->line("Total courses: {$analysis['courses']}");
        $this->line("Total versions: {$analysis['versions']} ({$statuses})");
        $this->line("Total lessons: {$analysis['lessons']}");
        $this->line("Lessons with content: {$analysis['lessons_with_content']}");
        $this->line("Lessons without content: {$analysis['lessons_without_content']}");
        $this->line("Lessons stored as structured English: {$analysis['structured_english']}");
        $this->line("Lessons with a Telugu translation: {$analysis['telugu']}");
    }
}
