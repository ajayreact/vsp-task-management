<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingProgressService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function moveStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A course whose published Version 1 has empty lessons, plus a filled Version 2.
 * Version 2 is published unless asked otherwise.
 *
 * @return array{0: TrainingCourse, 1: TrainingCourseVersion, 2: TrainingCourseVersion}
 */
function courseWithFilledVersionTwo(Employee $lead, bool $publish = true): array
{
    $v1 = TrainingCourseVersion::factory()->published()->create();

    foreach (['Introduction', 'State Abbreviations', 'Time Zones'] as $i => $title) {
        TrainingLesson::factory()->forVersion($v1, $i + 1)->create(['title' => $title, 'slug' => Str::slug($title), 'body' => null, 'is_required' => true]);
    }

    $course = $v1->course->fresh();
    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($course, $lead->user);
    $v2->lessons()->each(fn (TrainingLesson $lesson) => $lesson->forceFill(['body' => "Full content for {$lesson->title}."])->save());

    if ($publish) {
        $content->publishVersion($v2, $lead->user);
    }

    return [$course->fresh(), $v1, $v2->fresh()];
}

function moveCommand(TrainingCourse $course, ?string $as, bool $dryRun = false): array
{
    return array_filter([
        'course' => (string) $course->id,
        '--as' => $as,
        '--dry-run' => $dryRun ?: null,
    ], fn ($value) => $value !== null);
}

test('an unfinished assignment moves to the live version and keeps its completed lessons', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    $recruiter = moveStaff(SystemRole::Recruiter);
    [$course, $v1, $v2] = courseWithFilledVersionTwo($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $firstV1Lesson = $v1->lessons()->orderBy('sort_order')->first();
    app(TrainingProgressService::class)->completeLesson($assignment, $firstV1Lesson, $recruiter->user);

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, $lead->user->email))
        ->expectsOutputToContain('Moved: 1')
        ->assertSuccessful();

    $assignment->refresh();
    $completion = TrainingLessonCompletion::query()->where('assignment_id', $assignment->id)->sole();
    $firstV2Lesson = $v2->lessons()->orderBy('sort_order')->first();

    expect($assignment->course_version_id)->toBe($v2->id)
        ->and($assignment->status)->toBe(TrainingAssignmentStatus::InProgress)
        ->and($completion->lesson_id)->toBe($firstV2Lesson->id)
        ->and($completion->completed_at)->not->toBeNull()
        ->and($v1->lessons()->count())->toBe(3)
        ->and(app(TrainingProgressService::class)->progressFor($assignment)['completed'])->toBe(1);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$firstV2Lesson->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('version', $v2->label())
            ->where('completion.completed', true)
            ->where('lesson.body', 'Full content for Introduction.'));
});

test('a dry run changes nothing', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    [$course, $v1] = courseWithFilledVersionTwo($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee(moveStaff(SystemRole::Recruiter))->create();

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, null, true))
        ->expectsOutputToContain('Would move: 1')
        ->assertSuccessful();

    expect($assignment->fresh()->course_version_id)->toBe($v1->id);
});

test('completed assignments stay on their version', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    [$course, $v1] = courseWithFilledVersionTwo($lead);
    $done = TrainingAssignment::factory()->completed()->forVersion($v1)->forEmployee(moveStaff(SystemRole::Recruiter))->create();

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, $lead->user->email))
        ->expectsOutputToContain('Nothing to move')
        ->assertSuccessful();

    expect($done->fresh()->course_version_id)->toBe($v1->id);
});

test('nothing moves while the new version is still a draft', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    [$course, $v1] = courseWithFilledVersionTwo($lead, publish: false);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee(moveStaff(SystemRole::Recruiter))->create();

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, $lead->user->email))
        ->expectsOutputToContain('Nothing to move')
        ->assertSuccessful();

    expect($assignment->fresh()->course_version_id)->toBe($v1->id);
});

test('a recruiter who already has the live version is skipped', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    $recruiter = moveStaff(SystemRole::Recruiter);
    [$course, $v1, $v2] = courseWithFilledVersionTwo($lead);
    $old = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    TrainingAssignment::factory()->forVersion($v2)->forEmployee($recruiter)->create();

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, $lead->user->email))
        ->expectsOutputToContain('Skipped: 1')
        ->assertSuccessful();

    expect($old->fresh()->course_version_id)->toBe($v1->id);
});

test('progress on a lesson that is not in the live version is dropped and reported', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    $recruiter = moveStaff(SystemRole::Recruiter);
    [$course, $v1, $v2] = courseWithFilledVersionTwo($lead, publish: false);
    $content = app(TrainingContentService::class);
    $content->deleteLesson($v2->lessons()->where('title', 'Time Zones')->sole(), $lead->user);
    $content->publishVersion($v2, $lead->user);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $removed = $v1->lessons()->where('title', 'Time Zones')->sole();
    app(TrainingProgressService::class)->completeLesson($assignment, $removed, $recruiter->user);

    $this->artisan('recruiter:training-move-assignments', moveCommand($course->fresh(), $lead->user->email))
        ->expectsOutputToContain('Moved: 1')
        ->assertSuccessful();

    expect($assignment->fresh()->course_version_id)->toBe($v2->id)
        ->and(TrainingLessonCompletion::query()->where('assignment_id', $assignment->id)->count())->toBe(0)
        ->and($removed->fresh())->not->toBeNull();
});

test('a real run needs a user who can assign training', function () {
    $lead = moveStaff(SystemRole::RecruiterLead);
    $recruiter = moveStaff(SystemRole::Recruiter);
    [$course, $v1] = courseWithFilledVersionTwo($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();

    $this->artisan('recruiter:training-move-assignments', moveCommand($course, null))->assertFailed();
    $this->artisan('recruiter:training-move-assignments', moveCommand($course, $recruiter->user->email))->assertFailed();
    $this->artisan('recruiter:training-move-assignments', ['course' => '999999', '--dry-run' => true])->assertFailed();

    expect($assignment->fresh()->course_version_id)->toBe($v1->id);
});
