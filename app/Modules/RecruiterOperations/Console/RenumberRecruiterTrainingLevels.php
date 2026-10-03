<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use Database\Seeders\RecruiterOperations\RecruiterTrainingCurriculumSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Brings each curriculum level's number, order, name and slug in line with
 * the curriculum outline, finding the level through its course. Only the
 * level record changes: courses, versions, lessons, assignments and progress
 * stay as they are. Levels already in place are skipped, so it is safe to
 * run again.
 */
class RenumberRecruiterTrainingLevels extends Command
{
    protected $signature = 'recruiter:training-levels
        {--dry-run : List the level changes without saving anything}';

    protected $description = 'Renumber recruiter training levels to match the curriculum order';

    public function handle(): int
    {
        if (! Schema::hasTable((new TrainingCategory)->getTable())) {
            $this->error('The recruiter training tables do not exist yet. Run the migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $changes = [];

        foreach (RecruiterTrainingCurriculumSeeder::curriculum() as $level => $definition) {
            $course = TrainingCourse::query()->with('category')->where('slug', Str::slug($definition['course']))->first();
            $category = $course?->category;

            if ($category === null) {
                $rows[] = [$level, $definition['course'], '-', 'Course not found, skipped'];

                continue;
            }

            $target = [
                'name' => "Level {$level} - {$definition['name']}",
                'slug' => Str::slug("level-{$level}-{$definition['name']}"),
                'level_number' => $level,
                'sort_order' => $level,
            ];

            $current = $category->only(array_keys($target));

            if ($current == $target) {
                $rows[] = [$level, $definition['course'], $category->name, 'Already in place'];

                continue;
            }

            $taken = TrainingCategory::query()->where('slug', $target['slug'])->whereKeyNot($category->id)->exists();

            if ($taken) {
                $rows[] = [$level, $definition['course'], $category->name, "Another level already uses {$target['slug']}, skipped"];

                continue;
            }

            $rows[] = [$level, $definition['course'], $category->name, 'Becomes '.$target['name']];
            $changes[] = [$category, $target];
        }

        if (! $dryRun) {
            DB::transaction(function () use ($changes) {
                foreach ($changes as [$category, $target]) {
                    $category->forceFill($target)->save();
                }
            });
        }

        $this->table(['Level', 'Course', 'Current level', 'Result'], $rows);
        $this->info(($dryRun ? 'Dry run, nothing saved. Would renumber: ' : 'Renumbered: ').count($changes));

        return self::SUCCESS;
    }
}
