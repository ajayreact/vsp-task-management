<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Services\TrainingAssignmentMover;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Illuminate\Console\Command;

/**
 * One-off switch to live editing: makes every course's unpublished draft the
 * live version, then moves each course's unfinished assignments onto it,
 * carrying lesson progress across. Completed assignments are not touched.
 */
class MakeTrainingDraftsLive extends Command
{
    protected $signature = 'recruiter:training-go-live
        {--as= : Email of the training manager making the change}
        {--dry-run : Report what would change without saving}';

    protected $description = 'Make every recruiter training draft live and move unfinished assignments onto it';

    public function handle(TrainingContentService $content, TrainingAssignmentMover $mover): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $actor = filled($this->option('as')) ? User::query()->where('email', (string) $this->option('as'))->first() : null;

        if (filled($this->option('as')) && $actor === null) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        if (! $dryRun && ($actor === null || ! $actor->can('assign', TrainingCourse::class))) {
            $this->error('Pass --as=<email> of a training manager who can assign recruiter training, or --dry-run.');

            return self::FAILURE;
        }

        $drafts = TrainingCourseVersion::query()
            ->with('course')
            ->where('status', TrainingContentStatus::Draft->value)
            ->withCount('lessons')
            ->orderBy('course_id')
            ->get()
            ->reject(fn (TrainingCourseVersion $draft) => $draft->course->isArchived());

        if ($drafts->isEmpty()) {
            $this->info('No drafts to make live.');
        }

        $rows = [];

        foreach ($drafts as $draft) {
            $open = TrainingAssignment::query()
                ->forCourse($draft->course)
                ->where('status', '!=', TrainingAssignmentStatus::Completed->value)
                ->count();

            $rows[] = [$draft->course->title, $draft->label(), (int) $draft->lessons_count, $open];

            if (! $dryRun) {
                $content->publishVersion($draft, $actor);
            }
        }

        $this->table(['Course', 'Draft made live', 'Lessons', 'Unfinished assignments'], $rows);

        if ($dryRun) {
            $this->line('Dry run, nothing saved. Would make live: '.count($rows));

            return self::SUCCESS;
        }

        $report = $mover->move(TrainingCourse::query()->whereNotNull('current_version_id')->orderBy('id')->get(), $actor, false);

        if ($report !== []) {
            $this->table(
                ['Course', 'Recruiter', 'From', 'To', 'Lessons carried over', 'To finish again', 'Progress dropped', 'Result'],
                array_map(fn (array $row) => [$row['course'], $row['recruiter'], $row['from'], $row['to'], $row['kept'], $row['reset'], $row['dropped'], $row['status']], $report),
            );
        }

        $moved = count(array_filter($report, fn (array $row) => $row['status'] === TrainingAssignmentMover::MOVED));
        $this->line('Made live: '.count($rows).'. Assignments moved: '.$moved.'.');

        return self::SUCCESS;
    }
}
