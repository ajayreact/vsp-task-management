<?php

use App\Http\Controllers\RecruiterOperations\RecruiterDashboardController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentAssignmentController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentLearnerController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentQuestionController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentResultController;
use App\Modules\RecruiterOperations\Http\Controllers\AssessmentVersionController;
use App\Modules\RecruiterOperations\Http\Controllers\QuestionImportController;
use App\Modules\RecruiterOperations\Http\Controllers\RecruiterDailyActivityController;
use App\Modules\RecruiterOperations\Http\Controllers\RecruiterTaskController;
use App\Modules\RecruiterOperations\Http\Controllers\RecruiterTaskWorkflowController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingAssignmentController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingCategoryController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingContentReviewController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingCourseController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingLearnerController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingLessonContentController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingLessonController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingLessonMediaController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingTeamController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingVersionAssessmentController;
use App\Modules\RecruiterOperations\Http\Controllers\TrainingVersionController;
use App\Modules\RecruiterOperations\Http\Middleware\EnsureTrainingLearner;
use App\Modules\RecruiterOperations\Http\Middleware\ShareTrainingLearner;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Recruiter Operations routes
|--------------------------------------------------------------------------
|
| Registered by RecruiterOperationsServiceProvider under the "recruiter"
| prefix and "recruiter." route name prefix, behind the internal-staff check
| and the recruiter.access permission.
|
| Route parameters must not reuse names bound globally by other modules:
| {task}, {activity}, {media}, {document}, {contract}, {calendarItem},
| {personalTodo}, {wfhRequest}. Hence {trainingCourse}, {trainingLesson} and
| so on. Nothing here may reference Digital Marketing Task Management.
|
| Writes authorize on the route so a refused user gets 403 before any form
| validation runs.
|
*/

Route::get('/', RecruiterDashboardController::class)->name('dashboard');

Route::prefix('tasks')->name('tasks.')->where(['recruiterTask' => '[0-9]+'])->group(function () {
    Route::get('/', [RecruiterTaskController::class, 'index'])->name('index');
    Route::get('/export/excel', [RecruiterTaskController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [RecruiterTaskController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/create', [RecruiterTaskController::class, 'create'])->name('create');
    Route::post('/', [RecruiterTaskController::class, 'store'])->name('store')->can('create', RecruiterTask::class);
    Route::get('/{recruiterTask}', [RecruiterTaskController::class, 'show'])->name('show');
    Route::get('/{recruiterTask}/edit', [RecruiterTaskController::class, 'edit'])->name('edit');
    Route::put('/{recruiterTask}', [RecruiterTaskController::class, 'update'])->name('update')->can('update', 'recruiterTask');
    Route::delete('/{recruiterTask}', [RecruiterTaskController::class, 'destroy'])->name('destroy')->can('delete', 'recruiterTask');

    foreach (['accept', 'decline', 'hold', 'resume', 'complete', 'reopen', 'cancel', 'reassign'] as $action) {
        Route::post("/{recruiterTask}/{$action}", [RecruiterTaskWorkflowController::class, $action])
            ->name($action)
            ->can($action, 'recruiterTask');
    }
});

Route::prefix('activities')->name('activities.')->where(['dailyActivity' => '[0-9]+'])->group(function () {
    Route::get('/', [RecruiterDailyActivityController::class, 'index'])->name('index');
    Route::get('/export/excel', [RecruiterDailyActivityController::class, 'exportExcel'])->name('export.excel');
    Route::get('/create', [RecruiterDailyActivityController::class, 'create'])->name('create');
    Route::post('/', [RecruiterDailyActivityController::class, 'store'])->name('store')->can('create', RecruiterDailyActivity::class);
    Route::get('/{dailyActivity}', [RecruiterDailyActivityController::class, 'show'])->name('show');
    Route::get('/{dailyActivity}/edit', [RecruiterDailyActivityController::class, 'edit'])->name('edit');
    Route::put('/{dailyActivity}', [RecruiterDailyActivityController::class, 'update'])->name('update')->can('update', 'dailyActivity');
    Route::delete('/{dailyActivity}', [RecruiterDailyActivityController::class, 'destroy'])->name('destroy')->can('delete', 'dailyActivity');
});

Route::prefix('training')->name('training.')->where([
    'trainingCategory' => '[0-9]+',
    'trainingCourse' => '[0-9]+',
    'trainingVersion' => '[0-9]+',
    'trainingLesson' => '[0-9]+',
    'trainingAssignment' => '[0-9]+',
])->middleware(ShareTrainingLearner::class)->group(function () {
    // The overview serves learners and managers; it shows each only their side.
    Route::get('/', [TrainingLearnerController::class, 'dashboard'])->name('dashboard')->can('viewAny', TrainingCourse::class);
    Route::get('/tracks/{track}', [TrainingLearnerController::class, 'track'])->name('tracks.show')->where('track', '[a-z0-9-]+')->can('viewAny', TrainingCourse::class);

    // A recruiter's own learning. The assignment is always resolved from the
    // signed-in person, never from the URL. Management-only users (Admin,
    // Operations Head without a recruiter role) are refused.
    Route::middleware(EnsureTrainingLearner::class)->group(function () {
        Route::get('/my-training', [TrainingLearnerController::class, 'myTraining'])->name('my')->can('viewAny', TrainingCourse::class);
        Route::get('/courses/{trainingCourse}', [TrainingLearnerController::class, 'course'])->name('courses.show')->can('viewAny', TrainingCourse::class);
        Route::get('/courses/{trainingCourse}/lessons/{trainingLesson}', [TrainingLearnerController::class, 'lesson'])->name('lessons.show')->can('viewAny', TrainingCourse::class);
        Route::post('/courses/{trainingCourse}/lessons/{trainingLesson}/complete', [TrainingLearnerController::class, 'complete'])->name('lessons.complete')->can('viewAny', TrainingCourse::class);
        Route::post('/courses/{trainingCourse}/lessons/{trainingLesson}/progress', [TrainingLearnerController::class, 'progress'])->name('lessons.progress')->can('viewAny', TrainingCourse::class);
    });

    Route::get('/lessons/{trainingLesson}/speech', [TrainingLessonMediaController::class, 'speech'])->name('lessons.speech')->can('listen', 'trainingLesson');
    Route::get('/lessons/{trainingLesson}/audio', [TrainingLessonMediaController::class, 'audio'])->name('lessons.audio')->can('listen', 'trainingLesson');
    Route::get('/lessons/{trainingLesson}/file', [TrainingLessonMediaController::class, 'file'])->name('lessons.file')->can('viewFile', 'trainingLesson');

    Route::get('/team', [TrainingTeamController::class, 'index'])->name('team')->can('viewTeam', TrainingCourse::class);

    Route::prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [TrainingAssignmentController::class, 'index'])->name('index')->can('viewAny', TrainingAssignment::class);
        Route::get('/create', [TrainingAssignmentController::class, 'create'])->name('create')->can('create', TrainingAssignment::class);
        Route::post('/', [TrainingAssignmentController::class, 'store'])->name('store')->can('create', TrainingAssignment::class);
        Route::delete('/{trainingAssignment}', [TrainingAssignmentController::class, 'destroy'])->name('destroy')->can('delete', 'trainingAssignment');
    });

    Route::prefix('manage')->name('manage.')->group(function () {
        Route::get('/', [TrainingCourseController::class, 'index'])->name('index')->can('manage', TrainingCourse::class);

        Route::get('/categories', [TrainingCategoryController::class, 'index'])->name('categories.index')->can('viewAny', TrainingCategory::class);
        Route::post('/categories', [TrainingCategoryController::class, 'store'])->name('categories.store')->can('create', TrainingCategory::class);
        Route::put('/categories/{trainingCategory}', [TrainingCategoryController::class, 'update'])->name('categories.update')->can('update', 'trainingCategory');
        Route::post('/categories/{trainingCategory}/toggle', [TrainingCategoryController::class, 'toggle'])->name('categories.toggle')->can('toggle', 'trainingCategory');

        Route::get('/courses/create', [TrainingCourseController::class, 'create'])->name('courses.create')->can('create', TrainingCourse::class);
        Route::post('/courses', [TrainingCourseController::class, 'store'])->name('courses.store')->can('create', TrainingCourse::class);
        Route::get('/courses/{trainingCourse}', [TrainingCourseController::class, 'show'])->name('courses.show')->can('view', 'trainingCourse');
        Route::get('/courses/{trainingCourse}/edit', [TrainingCourseController::class, 'edit'])->name('courses.edit')->can('update', 'trainingCourse');
        Route::put('/courses/{trainingCourse}', [TrainingCourseController::class, 'update'])->name('courses.update')->can('update', 'trainingCourse');
        Route::post('/courses/{trainingCourse}/archive', [TrainingCourseController::class, 'archive'])->name('courses.archive')->can('archive', 'trainingCourse');
        Route::post('/courses/{trainingCourse}/restore', [TrainingCourseController::class, 'restore'])->name('courses.restore')->can('restore', 'trainingCourse');
        Route::post('/courses/{trainingCourse}/versions', [TrainingCourseController::class, 'storeVersion'])->name('courses.versions.store')->can('createVersion', 'trainingCourse');

        Route::put('/versions/{trainingVersion}', [TrainingVersionController::class, 'update'])->name('versions.update')->can('update', 'trainingVersion');
        Route::post('/versions/{trainingVersion}/publish', [TrainingVersionController::class, 'publish'])->name('versions.publish')->can('publish', 'trainingVersion');
        Route::post('/versions/{trainingVersion}/archive', [TrainingVersionController::class, 'archive'])->name('versions.archive')->can('archive', 'trainingVersion');
        Route::delete('/versions/{trainingVersion}', [TrainingVersionController::class, 'destroy'])->name('versions.destroy')->can('delete', 'trainingVersion');

        Route::get('/versions/{trainingVersion}/lessons/create', [TrainingLessonController::class, 'create'])->name('lessons.create')->can('update', 'trainingVersion');
        Route::post('/versions/{trainingVersion}/lessons', [TrainingLessonController::class, 'store'])->name('lessons.store')->can('update', 'trainingVersion');
        Route::get('/lessons/{trainingLesson}', [TrainingLessonController::class, 'show'])->name('lessons.show')->can('view', 'trainingLesson');
        Route::get('/lessons/{trainingLesson}/edit', [TrainingLessonController::class, 'edit'])->name('lessons.edit')->can('update', 'trainingLesson');
        Route::put('/lessons/{trainingLesson}', [TrainingLessonController::class, 'update'])->name('lessons.update')->can('update', 'trainingLesson');
        Route::delete('/lessons/{trainingLesson}', [TrainingLessonController::class, 'destroy'])->name('lessons.destroy')->can('delete', 'trainingLesson');
        Route::post('/lessons/{trainingLesson}/move', [TrainingLessonController::class, 'move'])->name('lessons.move')->can('move', 'trainingLesson');
        Route::get('/lessons/{trainingLesson}/content', [TrainingLessonContentController::class, 'edit'])->name('lessons.content.edit')->can('view', 'trainingLesson');
        Route::put('/lessons/{trainingLesson}/content/{language}', [TrainingLessonContentController::class, 'update'])->name('lessons.content.update')->where('language', '[a-z]{2}')->can('update', 'trainingLesson');
        Route::delete('/lessons/{trainingLesson}/content/{language}', [TrainingLessonContentController::class, 'destroy'])->name('lessons.content.destroy')->where('language', '[a-z]{2}')->can('update', 'trainingLesson');

        Route::get('/review', [TrainingContentReviewController::class, 'index'])->name('review.index')->can('manage', TrainingCourse::class);
        Route::get('/review/lessons/{trainingLesson}/{language}', [TrainingContentReviewController::class, 'show'])->name('review.show')->where('language', '[a-z]{2}')->can('view', 'trainingLesson');
        Route::post('/review/lessons/{trainingLesson}/{language}', [TrainingContentReviewController::class, 'updateStatus'])->name('review.status')->where('language', '[a-z]{2}')->can('update', 'trainingLesson');
        Route::post('/review/lessons/{trainingLesson}/compliance', [TrainingContentReviewController::class, 'updateCompliance'])->name('review.compliance')->can('update', 'trainingLesson');

        Route::post('/versions/{trainingVersion}/quizzes', [TrainingVersionAssessmentController::class, 'store'])->name('versions.quizzes.store')->can('update', 'trainingVersion');
        Route::delete('/versions/{trainingVersion}/quizzes/{assessmentVersion}', [TrainingVersionAssessmentController::class, 'destroy'])->name('versions.quizzes.destroy')->where('assessmentVersion', '[0-9]+')->can('update', 'trainingVersion');
    });
});

Route::prefix('assessments')->name('assessments.')->where([
    'assessment' => '[0-9]+',
    'assessmentVersion' => '[0-9]+',
    'assessmentQuestion' => '[0-9]+',
    'assessmentAssignment' => '[0-9]+',
    'assessmentAttempt' => '[0-9]+',
    'assessmentAnswer' => '[0-9]+',
    'batch' => '[0-9a-fA-F-]{36}',
])->group(function () {
    // A recruiter's own quizzes. Ownership is checked on the route and again
    // in AssessmentAttemptService against the signed-in person's employee.
    Route::get('/', [AssessmentLearnerController::class, 'index'])->name('index');
    Route::get('/{assessmentAssignment}', [AssessmentLearnerController::class, 'show'])->name('show')->can('take', 'assessmentAssignment');
    Route::post('/{assessmentAssignment}/start', [AssessmentLearnerController::class, 'start'])->name('start')->can('take', 'assessmentAssignment');
    Route::get('/attempts/{assessmentAttempt}', [AssessmentLearnerController::class, 'attempt'])->name('attempts.show')->can('view', 'assessmentAttempt');
    Route::put('/attempts/{assessmentAttempt}/answers', [AssessmentLearnerController::class, 'save'])->name('attempts.save')->can('view', 'assessmentAttempt');
    Route::post('/attempts/{assessmentAttempt}/submit', [AssessmentLearnerController::class, 'submit'])->name('attempts.submit')->can('view', 'assessmentAttempt');

    Route::prefix('manage')->name('manage.')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index')->can('viewAny', Assessment::class);
        Route::get('/create', [AssessmentController::class, 'create'])->name('create')->can('create', Assessment::class);
        Route::post('/', [AssessmentController::class, 'store'])->name('store')->can('create', Assessment::class);
        Route::get('/{assessment}', [AssessmentController::class, 'show'])->name('show')->can('view', 'assessment');
        Route::get('/{assessment}/edit', [AssessmentController::class, 'edit'])->name('edit')->can('update', 'assessment');
        Route::put('/{assessment}', [AssessmentController::class, 'update'])->name('update')->can('update', 'assessment');
        Route::post('/{assessment}/archive', [AssessmentController::class, 'archive'])->name('archive')->can('archive', 'assessment');
        Route::post('/{assessment}/restore', [AssessmentController::class, 'restore'])->name('restore')->can('restore', 'assessment');
        Route::post('/{assessment}/versions', [AssessmentController::class, 'storeVersion'])->name('versions.store')->can('createVersion', 'assessment');

        Route::put('/versions/{assessmentVersion}', [AssessmentVersionController::class, 'update'])->name('versions.update')->can('update', 'assessmentVersion');
        Route::post('/versions/{assessmentVersion}/publish', [AssessmentVersionController::class, 'publish'])->name('versions.publish')->can('publish', 'assessmentVersion');
        Route::post('/versions/{assessmentVersion}/archive', [AssessmentVersionController::class, 'archive'])->name('versions.archive')->can('archive', 'assessmentVersion');
        Route::delete('/versions/{assessmentVersion}', [AssessmentVersionController::class, 'destroy'])->name('versions.destroy')->can('delete', 'assessmentVersion');
        Route::get('/versions/{assessmentVersion}/preview', [AssessmentVersionController::class, 'preview'])->name('versions.preview')->can('view', 'assessmentVersion');
        Route::get('/versions/{assessmentVersion}/bank', [AssessmentVersionController::class, 'bank'])->name('versions.bank')->can('update', 'assessmentVersion');
        Route::post('/versions/{assessmentVersion}/bank', [AssessmentVersionController::class, 'addFromBank'])->name('versions.bank.store')->can('update', 'assessmentVersion');

        Route::get('/versions/{assessmentVersion}/questions/create', [AssessmentQuestionController::class, 'createForVersion'])->name('questions.create')->can('update', 'assessmentVersion');
        Route::post('/versions/{assessmentVersion}/questions', [AssessmentQuestionController::class, 'storeForVersion'])->name('questions.store')->can('update', 'assessmentVersion');
        Route::post('/questions/{assessmentQuestion}/move', [AssessmentQuestionController::class, 'move'])->name('questions.move')->can('update', 'assessmentQuestion');
    });

    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [AssessmentQuestionController::class, 'index'])->name('index')->can('viewAny', AssessmentQuestion::class);
        Route::get('/create', [AssessmentQuestionController::class, 'create'])->name('create')->can('create', AssessmentQuestion::class);
        Route::post('/', [AssessmentQuestionController::class, 'store'])->name('store')->can('create', AssessmentQuestion::class);

        Route::get('/import', [QuestionImportController::class, 'create'])->name('import.create')->can('import', AssessmentQuestion::class);
        Route::get('/import/template', [QuestionImportController::class, 'template'])->name('import.template')->can('import', AssessmentQuestion::class);
        Route::post('/import', [QuestionImportController::class, 'store'])->name('import.store')->can('import', AssessmentQuestion::class);
        Route::get('/import/{batch}', [QuestionImportController::class, 'show'])->name('import.show')->can('import', AssessmentQuestion::class);
        Route::post('/import/{batch}/confirm', [QuestionImportController::class, 'confirm'])->name('import.confirm')->can('import', AssessmentQuestion::class);
        Route::delete('/import/{batch}', [QuestionImportController::class, 'cancel'])->name('import.cancel')->can('import', AssessmentQuestion::class);
        Route::get('/import/{batch}/errors', [QuestionImportController::class, 'errors'])->name('import.errors')->can('import', AssessmentQuestion::class);

        Route::get('/{assessmentQuestion}', [AssessmentQuestionController::class, 'show'])->name('show')->withTrashed()->can('view', 'assessmentQuestion');
        Route::get('/{assessmentQuestion}/edit', [AssessmentQuestionController::class, 'edit'])->name('edit')->can('update', 'assessmentQuestion');
        Route::put('/{assessmentQuestion}', [AssessmentQuestionController::class, 'update'])->name('update')->can('update', 'assessmentQuestion');
        Route::delete('/{assessmentQuestion}', [AssessmentQuestionController::class, 'destroy'])->name('destroy')->can('delete', 'assessmentQuestion');
        Route::post('/{assessmentQuestion}/duplicate', [AssessmentQuestionController::class, 'duplicate'])->name('duplicate')->can('duplicate', 'assessmentQuestion');
        Route::post('/{assessmentQuestion}/restore', [AssessmentQuestionController::class, 'restore'])->name('restore')->withTrashed()->can('restore', 'assessmentQuestion');
    });

    Route::prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [AssessmentAssignmentController::class, 'index'])->name('index')->can('viewAny', AssessmentAssignment::class);
        Route::get('/create', [AssessmentAssignmentController::class, 'create'])->name('create')->can('create', AssessmentAssignment::class);
        Route::post('/', [AssessmentAssignmentController::class, 'store'])->name('store')->can('create', AssessmentAssignment::class);
        Route::delete('/{assessmentAssignment}', [AssessmentAssignmentController::class, 'destroy'])->name('destroy')->can('delete', 'assessmentAssignment');
    });

    Route::prefix('results')->name('results.')->group(function () {
        Route::get('/', [AssessmentResultController::class, 'index'])->name('index')->can('viewResults', Assessment::class);
        Route::get('/attempts/{assessmentAttempt}', [AssessmentResultController::class, 'show'])->name('show')->can('viewAsReviewer', 'assessmentAttempt');
        Route::post('/attempts/{assessmentAttempt}/answers/{assessmentAnswer}/review', [AssessmentResultController::class, 'review'])->name('review')->can('review', 'assessmentAttempt');
    });
});
