<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Services\TrainingCurriculumRestructurer;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Reorganises the OPT Recruiter courses into their modules and lessons, in
 * draft versions only. Nothing is published, approved or assigned.
 */
class RestructureOptRecruiterTrack extends Command
{
    protected $signature = 'recruiter:training-opt-track
        {--as= : Email of a training manager, who the changes are recorded against}
        {--dry-run : Show the course, module and lesson mapping without saving anything}';

    protected $description = 'Reorganise the OPT Recruiter training drafts into the four-course module structure';

    public function handle(TrainingCurriculumRestructurer $restructurer): int
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
            $this->error('Pass --as=<training manager email> to save changes, or --dry-run to only show the mapping.');

            return self::FAILURE;
        }

        $plans = RecruiterTrainingContent::optTrack();
        $report = $restructurer->restructure($plans, $actor, $dryRun);

        $this->table(
            ['Course', 'Version', 'Module', '#', 'Lesson', 'Action', 'English', 'Compliance'],
            array_map(fn (array $row) => [$row['course'], $row['version'], $row['module'], $row['position'] ?? '-', $row['lesson'], $row['action'], $row['english'], $row['compliance']], $report),
        );

        $this->newLine();
        $this->info(($dryRun ? 'Dry run, nothing saved. ' : '').'Summary');

        foreach ($plans as $plan) {
            $lessons = count(array_filter($report, fn (array $row) => $row['position'] !== null && Str::slug($row['course']) === Str::slug($plan['course'])));
            $this->line(sprintf('%s: %d modules, %d lessons. Title to use when published: %s', $plan['course'], count($plan['modules']), $lessons, $plan['title']));
        }

        $count = fn (string $prefix) => count(array_filter($report, fn (array $row) => str_starts_with($row['action'], $prefix)));
        $this->line('Kept: '.$count(TrainingCurriculumRestructurer::KEPT).', renamed: '.$count(TrainingCurriculumRestructurer::RENAMED).', copied: '.$count(TrainingCurriculumRestructurer::COPIED).', new: '.$count(TrainingCurriculumRestructurer::CREATED).', removed from drafts: '.$count(TrainingCurriculumRestructurer::REMOVED));

        if ($count(TrainingCurriculumRestructurer::SKIPPED) > 0) {
            $this->warn('Some courses have no draft version. Create a draft for them first; published versions are never changed.');
        }

        return self::SUCCESS;
    }
}
