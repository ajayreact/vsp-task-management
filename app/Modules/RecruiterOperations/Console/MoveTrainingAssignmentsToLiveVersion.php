<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\TrainingAssignmentMover;
use Illuminate\Console\Command;

/**
 * Moves a course's unfinished assignments onto its live published version,
 * carrying completed lessons across. Completed assignments are not touched.
 */
class MoveTrainingAssignmentsToLiveVersion extends Command
{
    protected $signature = 'recruiter:training-move-assignments
        {course : Course ID or exact course title}
        {--as= : Email of the training manager making the change}
        {--dry-run : Report what would change without saving}';

    protected $description = 'Move unfinished recruiter training assignments to the course\'s live published version';

    public function handle(TrainingAssignmentMover $mover): int
    {
        $key = trim((string) $this->argument('course'));
        $course = ctype_digit($key)
            ? TrainingCourse::query()->find((int) $key)
            : TrainingCourse::query()->where('title', $key)->first();

        if ($course === null) {
            $this->error('No course with that ID or title.');

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

        if (! $dryRun && ($actor === null || ! $actor->can('assign', TrainingCourse::class))) {
            $this->error('Pass --as=<email> of someone who can assign recruiter training.');

            return self::FAILURE;
        }

        $report = $mover->move(collect([$course]), $actor, $dryRun);

        if ($report === []) {
            $this->info("No unfinished assignments on an older version of {$course->title}. Nothing to move.");

            return self::SUCCESS;
        }

        $this->table(
            ['Course', 'Recruiter', 'From', 'To', 'Lessons carried over', 'Progress dropped', 'Result'],
            array_map(fn (array $row) => [$row['course'], $row['recruiter'], $row['from'], $row['to'], $row['kept'], $row['dropped'], $row['status']], $report),
        );

        $moved = count(array_filter($report, fn (array $row) => $row['status'] === TrainingAssignmentMover::MOVED));
        $this->newLine();
        $this->line(($dryRun ? 'Dry run, nothing saved. Would move: ' : 'Moved: ').$moved);
        $this->line('Skipped: '.(count($report) - $moved));

        return self::SUCCESS;
    }
}
