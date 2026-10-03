<?php

namespace App\Modules\RecruiterOperations\Providers;

use App\Modules\Core\Enums\Ability;
use App\Modules\RecruiterOperations\Console\MoveTrainingAssignmentsToLiveVersion;
use App\Modules\RecruiterOperations\Console\PopulateRecruiterTrainingContent;
use App\Modules\RecruiterOperations\Console\FlagRecruiterTrainingReviews;
use App\Modules\RecruiterOperations\Console\RemoveRecruiterTrainingNotes;
use App\Modules\RecruiterOperations\Console\RenumberRecruiterTrainingLevels;
use App\Modules\RecruiterOperations\Console\StructureRecruiterTrainingContent;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Policies\AssessmentAssignmentPolicy;
use App\Modules\RecruiterOperations\Policies\AssessmentAttemptPolicy;
use App\Modules\RecruiterOperations\Policies\AssessmentPolicy;
use App\Modules\RecruiterOperations\Policies\AssessmentQuestionPolicy;
use App\Modules\RecruiterOperations\Policies\AssessmentVersionPolicy;
use App\Modules\RecruiterOperations\Policies\RecruiterDailyActivityPolicy;
use App\Modules\RecruiterOperations\Policies\RecruiterTaskPolicy;
use App\Modules\RecruiterOperations\Policies\TrainingAssignmentPolicy;
use App\Modules\RecruiterOperations\Policies\TrainingCategoryPolicy;
use App\Modules\RecruiterOperations\Policies\TrainingCoursePolicy;
use App\Modules\RecruiterOperations\Policies\TrainingCourseVersionPolicy;
use App\Modules\RecruiterOperations\Policies\TrainingLessonPolicy;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProviderResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Operations for the US IT Staffing / OPT recruiter team: recruiter tasks,
 * daily activities, training and assessments. Owns every `ro_*` table.
 *
 * Separate from Digital Marketing Task Management by design. Depends on Core
 * only; anything that needs another module (Attendance) is composed outside
 * `App\Modules`.
 *
 * Must be registered before TaskManagementServiceProvider: routes/share.php
 * ends with a `{companySlug}/{shortCode}` catch-all that would otherwise
 * capture two-segment URLs such as /recruiter/training.
 */
class RecruiterOperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TrainingSpeechProvider::class, fn ($app) => $app->make(TrainingSpeechProviderResolver::class)
            ->resolve(config('recruiter-training.speech.driver')));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/recruiter'));

        if ($this->app->runningInConsole()) {
            $this->commands([PopulateRecruiterTrainingContent::class, MoveTrainingAssignmentsToLiveVersion::class, StructureRecruiterTrainingContent::class, FlagRecruiterTrainingReviews::class, RenumberRecruiterTrainingLevels::class, RemoveRecruiterTrainingNotes::class]);
        }

        Gate::policy(RecruiterTask::class, RecruiterTaskPolicy::class);
        Gate::policy(RecruiterDailyActivity::class, RecruiterDailyActivityPolicy::class);
        Gate::policy(TrainingCategory::class, TrainingCategoryPolicy::class);
        Gate::policy(TrainingCourse::class, TrainingCoursePolicy::class);
        Gate::policy(TrainingCourseVersion::class, TrainingCourseVersionPolicy::class);
        Gate::policy(TrainingLesson::class, TrainingLessonPolicy::class);
        Gate::policy(TrainingAssignment::class, TrainingAssignmentPolicy::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentVersion::class, AssessmentVersionPolicy::class);
        Gate::policy(AssessmentQuestion::class, AssessmentQuestionPolicy::class);
        Gate::policy(AssessmentAssignment::class, AssessmentAssignmentPolicy::class);
        Gate::policy(AssessmentAttempt::class, AssessmentAttemptPolicy::class);

        Route::middleware(['web', 'auth', 'internal', 'permission:'.Ability::RecruiterAccess->value])
            ->prefix('recruiter')
            ->name('recruiter.')
            ->group(base_path('routes/recruiter-operations.php'));
    }
}
