<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Services\TrainingQuizInstaller;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Console\Command;

/**
 * Creates the OPT & STEM OPT Recruiter Process quizzes and links them to the
 * course's draft version. The course version is not published and no quiz is
 * assigned to anyone.
 */
class InstallOptTrainingQuizzes extends Command
{
    protected $signature = 'recruiter:training-opt-quizzes
        {--as= : Email of a training manager, who the quizzes are recorded against}
        {--dry-run : Show what would be created and linked without saving anything}';

    protected $description = 'Create the OPT Recruiter course quizzes and link them to the course draft';

    public function handle(TrainingQuizInstaller $installer): int
    {
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
            $this->error('Pass --as=<training manager email> to save changes, or --dry-run to only show the plan.');

            return self::FAILURE;
        }

        $report = $installer->install(RecruiterTrainingContent::optQuizzes(), $actor, $dryRun);

        $this->table(
            ['Quiz', 'Questions', 'Quiz', 'Course draft'],
            array_map(fn (array $row) => [$row['quiz'], $row['questions'], $row['quiz_action'], $row['link']], $report),
        );

        $this->info(($dryRun ? 'Dry run, nothing saved. ' : '').'The course version stays a draft; publish it from Manage when it has been reviewed.');

        return self::SUCCESS;
    }
}
