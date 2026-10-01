<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Http\Middleware\EnsureTrainingLearner;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

/*
| Who sees the learner side of Training. Recruiters and Recruiter Leads learn;
| Admin and Operations Head manage training without being learners, unless
| they also hold a recruiter role.
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function audienceEmployee(SystemRole ...$roles): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles(array_map(fn (SystemRole $role) => $role->value, $roles));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A published one-lesson course assigned to the employee.
 *
 * @return array{0: TrainingCourse, 1: TrainingLesson, 2: TrainingAssignment}
 */
function audienceAssignedCourse(Employee $employee): array
{
    $version = TrainingCourseVersion::factory()->published()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['body' => 'Calling hours across the U.S.']);
    $assignment = TrainingAssignment::factory()->forVersion($version)->forEmployee($employee)->create();

    return [$version->course->fresh(), $lesson, $assignment];
}

test('learners see My Training on the overview and can open it', function (SystemRole $role) {
    $learner = audienceEmployee($role);
    audienceAssignedCourse($learner);

    $this->actingAs($learner->user)
        ->get('/recruiter/training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/dashboard')
            ->where('isLearner', true)
            ->where('trainingLearner', true)
            ->where('counts.assigned', 1)
            ->has('continueLearning', 1));

    $this->actingAs($learner->user)
        ->get('/recruiter/training/my-training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/my-training')->has('assignments', 1));

    $this->actingAs($learner->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page->where('trainingLearner', true)->where('training.assigned', 1));
})->with([
    'recruiter' => SystemRole::Recruiter,
    'recruiter lead' => SystemRole::RecruiterLead,
]);

test('management-only users get a management overview without My Training', function (Employee $manager) {
    audienceAssignedCourse($manager);

    $this->actingAs($manager->user)
        ->get('/recruiter/training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/dashboard')
            ->where('isLearner', false)
            ->where('trainingLearner', false)
            ->where('counts', null)
            ->where('continueLearning', [])
            ->where('can.manage', true)
            ->where('can.assign', true)
            ->where('can.viewTeam', true));

    $this->actingAs($manager->user)
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('trainingLearner', false)->where('training', null));
})->with([
    'admin' => fn () => audienceEmployee(SystemRole::Admin),
    'operations head' => fn () => superAdminEmployee(),
]);

test('management-only users are refused every learner route on the server', function (Employee $manager) {
    [$course, $lesson, $assignment] = audienceAssignedCourse($manager);
    $base = "/recruiter/training/courses/{$course->id}";

    $this->actingAs($manager->user)->get('/recruiter/training/my-training')->assertForbidden();
    $this->actingAs($manager->user)->get($base)->assertForbidden();
    $this->actingAs($manager->user)->get("{$base}/lessons/{$lesson->id}")->assertForbidden();
    $this->actingAs($manager->user)->post("{$base}/lessons/{$lesson->id}/complete")->assertForbidden();
    $this->actingAs($manager->user)->postJson("{$base}/lessons/{$lesson->id}/progress", ['audio_seconds' => 'not a number'])->assertForbidden();

    expect(TrainingLessonCompletion::query()->count())->toBe(0)
        ->and($assignment->fresh()->started_at)->toBeNull();
})->with([
    'admin' => fn () => audienceEmployee(SystemRole::Admin),
    'operations head' => fn () => superAdminEmployee(),
]);

test('admin and operations head keep training administration', function (Employee $manager) {

    $this->actingAs($manager->user)->get('/recruiter/training/manage')->assertOk();
    $this->actingAs($manager->user)->get('/recruiter/training/manage/categories')->assertOk();
    $this->actingAs($manager->user)->get('/recruiter/training/team')->assertOk();
    $this->actingAs($manager->user)->get('/recruiter/training/assignments')->assertOk();
    $this->actingAs($manager->user)->get('/recruiter/training/assignments/create')->assertOk();
})->with([
    'admin' => fn () => audienceEmployee(SystemRole::Admin),
    'operations head' => fn () => superAdminEmployee(),
]);

test('authorized managers can create and open courses', function (Employee $manager) {
    $category = TrainingCategory::factory()->create();

    $this->actingAs($manager->user)
        ->post('/recruiter/training/manage/courses', ['category_id' => $category->id, 'title' => 'U.S. Time Zones'])
        ->assertSessionHasNoErrors();

    $course = TrainingCourse::query()->sole();

    $this->actingAs($manager->user)->get("/recruiter/training/manage/courses/{$course->id}")->assertOk();
})->with([
    'admin' => fn () => audienceEmployee(SystemRole::Admin),
    'operations head' => fn () => superAdminEmployee(),
    'recruiter lead' => fn () => audienceEmployee(SystemRole::RecruiterLead),
]);

test('an admin who also holds a recruiter role is a learner', function () {
    $both = audienceEmployee(SystemRole::Admin, SystemRole::Recruiter);
    [$course] = audienceAssignedCourse($both);

    $this->actingAs($both->user)
        ->get('/recruiter/training')
        ->assertInertia(fn ($page) => $page->where('isLearner', true)->where('counts.assigned', 1));
    $this->actingAs($both->user)->get('/recruiter/training/my-training')->assertOk();
    $this->actingAs($both->user)->get("/recruiter/training/courses/{$course->id}")->assertOk();
    $this->actingAs($both->user)->get('/recruiter/training/manage')->assertOk();
});

test('a custom role holding recruiter.access stays a learner', function () {
    $employee = employeeWith(Ability::RecruiterAccess);

    $this->actingAs($employee->user)
        ->get('/recruiter/training')
        ->assertInertia(fn ($page) => $page->where('isLearner', true)->where('trainingLearner', true));
    $this->actingAs($employee->user)->get('/recruiter/training/my-training')->assertOk();
});

test('recruiters cannot reach training administration', function () {
    $recruiter = audienceEmployee(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)->get('/recruiter/training/manage')->assertForbidden();
    $this->actingAs($recruiter->user)->post('/recruiter/training/manage/courses', [])->assertForbidden();
    $this->actingAs($recruiter->user)->get('/recruiter/training/team')->assertForbidden();
    $this->actingAs($recruiter->user)->get('/recruiter/training/assignments')->assertForbidden();
});

test('people without recruiter access reach no training route', function () {
    $outsider = employeeWith(Ability::AccessTasks);

    foreach (['/recruiter/training', '/recruiter/training/my-training', '/recruiter/training/manage', '/recruiter/training/team'] as $url) {
        $this->actingAs($outsider->user)->get($url)->assertForbidden();
    }

    auth()->logout();

    $this->get('/recruiter/training/my-training')->assertRedirect('/login');
});

test('every learner route carries the learner check and no management route does', function () {
    $learnerRoutes = [
        'recruiter.training.my',
        'recruiter.training.courses.show',
        'recruiter.training.lessons.show',
        'recruiter.training.lessons.complete',
        'recruiter.training.lessons.progress',
    ];

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'recruiter.training.'));

    foreach ($routes as $route) {
        $guarded = in_array(EnsureTrainingLearner::class, $route->gatherMiddleware(), true);

        expect($guarded)->toBe(in_array($route->getName(), $learnerRoutes, true), (string) $route->getName());
    }
});
