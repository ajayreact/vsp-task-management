<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\TrainingContentPopulator;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Fills empty or placeholder written content in the existing OPT recruiter
 * curriculum. Safe to run again: meaningful text is never overwritten and no
 * course or lesson is ever created.
 */
class PopulateRecruiterTrainingContent extends Command
{
    protected $signature = 'recruiter:training-content
        {--as= : Email of a training manager, used to create a new draft when a course only has a published version}
        {--dry-run : Report what would change without saving}';

    protected $description = 'Populate empty written content in the existing OPT recruiter training lessons';

    public function handle(TrainingContentPopulator $populator): int
    {
        if (! Schema::hasTable((new TrainingCourse)->getTable())) {
            $this->error('The recruiter training tables do not exist yet. Run the migrations and the curriculum seeder first.');

            return self::FAILURE;
        }

        $actor = null;

        if (filled($this->option('as'))) {
            $actor = User::query()->where('email', (string) $this->option('as'))->first();

            if ($actor === null) {
                $this->error('No user with that email.');

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $report = $populator->populate(RecruiterTrainingContent::all(), $actor, $dryRun);

        $this->table(
            ['Level', 'Course', 'Version', 'Lesson', 'Written content', 'Words'],
            array_map(fn (array $row) => [$row['level'], $row['course'], $row['version'] ?? '-', $row['lesson'], $row['status'], $row['words']], $report),
        );

        $count = fn (string ...$statuses) => count(array_filter($report, fn (array $row) => in_array($row['status'], $statuses, true)));

        $this->newLine();
        $this->line(($dryRun ? 'Dry run, nothing saved. ' : '').'Lessons: '.count(array_filter($report, fn (array $row) => $row['status'] !== TrainingContentPopulator::LESSON_MISSING && $row['status'] !== TrainingContentPopulator::COURSE_MISSING)));
        $this->line('Populated: '.$count(TrainingContentPopulator::POPULATED, TrainingContentPopulator::REPLACED_PLACEHOLDER));
        $this->line('Already had meaningful content: '.$count(TrainingContentPopulator::KEPT));
        $this->line('Still empty: '.$count(TrainingContentPopulator::NO_CONTENT, TrainingContentPopulator::NEEDS_DRAFT));
        $this->line('Content with no matching lesson or course: '.$count(TrainingContentPopulator::LESSON_MISSING, TrainingContentPopulator::COURSE_MISSING));

        if ($count(TrainingContentPopulator::NEEDS_DRAFT) > 0) {
            $this->warn('Some courses only have a published version. Re-run with --as=<training manager email> to create a new draft and fill it.');
        }

        return self::SUCCESS;
    }
}
