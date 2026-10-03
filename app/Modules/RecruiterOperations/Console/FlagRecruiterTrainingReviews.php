<?php

namespace App\Modules\RecruiterOperations\Console;

use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Marks draft lessons that need a compliance review (see
 * recruiter-training.compliance_review) as pending. It never approves
 * anything and never touches published versions or lessons a manager has
 * already decided on, so it is safe to run again.
 */
class FlagRecruiterTrainingReviews extends Command
{
    protected $signature = 'recruiter:training-review-flags
        {--dry-run : List the lessons that would be flagged without saving anything}';

    protected $description = 'Flag draft recruiter training lessons that need a compliance review';

    public function handle(TrainingContentReviewService $reviews): int
    {
        if (! Schema::hasColumn((new TrainingLesson)->getTable(), 'compliance_status')) {
            $this->error('The training review columns do not exist yet. Run the recruiter migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $flagged = $reviews->flagCompliance($dryRun);

        $this->table(
            ['Level', 'Course', 'Lesson'],
            $flagged->map(fn (TrainingLesson $lesson) => [
                $lesson->version->course->category->level_number ?? '-',
                $lesson->version->course->title,
                $lesson->title,
            ])->all(),
        );

        $this->info(($dryRun ? 'Dry run, nothing saved. Would flag: ' : 'Flagged for compliance review: ').$flagged->count());

        return self::SUCCESS;
    }
}
