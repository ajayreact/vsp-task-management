<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use App\Modules\RecruiterOperations\Services\TrainingProgressService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function withdrawStaff(SystemRole $role = SystemRole::Recruiter): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A published Version 1 of three lessons with English content, and a Version 2
 * draft with an extra lesson.
 *
 * @return array{0: TrainingCourse, 1: TrainingCourseVersion, 2: TrainingCourseVersion}
 */
function withdrawCourse(Employee $lead): array
{
    $v1 = TrainingCourseVersion::factory()->published()->create();

    foreach (['F-1', 'OPT', 'STEM OPT'] as $i => $title) {
        $lesson = TrainingLesson::factory()->forVersion($v1, $i + 1)->create(['title' => $title, 'module' => 'Visa / Status']);
        (new TrainingLessonContent)->forceFill([
            'lesson_id' => $lesson->id,
            'locale' => TrainingLanguage::English,
            'sections' => [['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => "Understand {$title}."]],
            'review_status' => TrainingContentReview::Approved,
        ])->save();
    }

    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($v1->course->fresh(), $lead->user);
    $lesson = $content->createLesson($v2, ['module' => 'Visa / Status', 'title' => 'Green Card', 'content_type' => 'text'], null, $lead->user);
    app(TrainingLessonContentService::class)->save($lesson, TrainingLanguage::English, [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Recognise a green card.'],
    ], $lead->user);
    app(TrainingContentReviewService::class)->setStatus($lesson->refresh(), TrainingLanguage::English, TrainingContentReview::Approved, $lead->user);

    return [$v1->course->fresh(), $v1->fresh(), $v2->fresh()];
}

/**
 * Every row of the training tables except assignments and completions.
 */
function withdrawTrainingSnapshot(): array
{
    return collect(['ro_training_categories', 'ro_training_courses', 'ro_training_course_versions', 'ro_training_lessons', 'ro_training_lesson_contents'])
        ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all()])
        ->all();
}

test('withdrawing an assignment removes only that assignment; the course, versions, lessons, content and other recruiters stay', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    $first = withdrawStaff();
    $second = withdrawStaff();
    [$course, $v1, $v2] = withdrawCourse($lead);
    $withdrawn = TrainingAssignment::factory()->forVersion($v1)->forEmployee($first)->create();
    $kept = TrainingAssignment::factory()->forVersion($v1)->forEmployee($second)->create();
    app(TrainingProgressService::class)->completeLesson($kept, $v1->lessons()->orderBy('sort_order')->first(), $second->user);
    $before = withdrawTrainingSnapshot();
    $keptBefore = [$kept->fresh()->toArray(), TrainingLessonCompletion::query()->where('assignment_id', $kept->id)->get()->toArray()];

    $this->actingAs($lead->user)
        ->delete("/recruiter/training/assignments/{$withdrawn->id}")
        ->assertRedirect()
        ->assertSessionHas('success', 'Training assignment withdrawn.');

    expect(TrainingAssignment::query()->find($withdrawn->id))->toBeNull()
        ->and(withdrawTrainingSnapshot())->toBe($before)
        ->and(TrainingCourse::query()->find($course->id))->not->toBeNull()
        ->and(TrainingCategory::query()->find($course->category_id))->not->toBeNull()
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($v1->lessons()->count())->toBe(3)
        ->and($v2->fresh()->status)->toBe(TrainingContentStatus::Draft)
        ->and($v2->lessons()->count())->toBe(4)
        ->and(TrainingLessonContent::query()->whereIn('lesson_id', $v1->lessons()->pluck('id'))->count())->toBe(3)
        ->and([$kept->fresh()->toArray(), TrainingLessonCompletion::query()->where('assignment_id', $kept->id)->get()->toArray()])->toBe($keptBefore)
        ->and($course->fresh()->current_version_id)->toBe($v1->id);

    $this->actingAs($second->user)->get("/recruiter/training/courses/{$course->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->has('lessons', 3)->where('progress.completed', 1));
    $this->actingAs($first->user)->get("/recruiter/training/courses/{$course->id}")->assertNotFound();
});

test('the success message never says the course was deleted', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    [, $v1] = withdrawCourse($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee(withdrawStaff())->create();

    $response = $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$assignment->id}");

    expect(session('success'))->toBe('Training assignment withdrawn.')
        ->and(strtolower((string) session('success')))->not->toContain('course deleted');
    $response->assertRedirect();
});

test('assignments stay on their version: Version 2 reaches a recruiter only through a new assignment', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    $staying = withdrawStaff();
    $reassigned = withdrawStaff();
    [$course, $v1, $v2] = withdrawCourse($lead);
    $stays = TrainingAssignment::factory()->forVersion($v1)->forEmployee($staying)->create();
    $old = TrainingAssignment::factory()->forVersion($v1)->forEmployee($reassigned)->create();

    app(TrainingContentService::class)->publishVersion($v2, $lead->user);

    expect($stays->fresh()->course_version_id)->toBe($v1->id);
    $this->actingAs($staying->user)->get("/recruiter/training/courses/{$course->id}")
        ->assertInertia(fn ($page) => $page->has('lessons', 3)->where('version.label', $v1->label()));

    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$old->id}")->assertSessionHas('success', 'Training assignment withdrawn.');
    $this->actingAs($lead->user)->post('/recruiter/training/assignments', [
        'track' => 'unassigned',
        'course_id' => $course->id,
        'mode' => 'individual',
        'employee_ids' => [$reassigned->id],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->actingAs($reassigned->user)->get("/recruiter/training/courses/{$course->id}")
        ->assertInertia(fn ($page) => $page->has('lessons', 4)->where('version.label', $v2->fresh()->label()));
    $this->actingAs($staying->user)->get("/recruiter/training/courses/{$course->id}")
        ->assertInertia(fn ($page) => $page->has('lessons', 3)->where('version.label', $v1->label()));
});

test('a started assignment can be withdrawn: its progress goes, the course and other recruiters stay', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    $recruiter = withdrawStaff();
    $other = withdrawStaff();
    [, $v1] = withdrawCourse($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $kept = TrainingAssignment::factory()->forVersion($v1)->forEmployee($other)->create();
    app(TrainingProgressService::class)->completeLesson($assignment, $v1->lessons()->first(), $recruiter->user);
    app(TrainingProgressService::class)->completeLesson($kept, $v1->lessons()->first(), $other->user);
    $before = withdrawTrainingSnapshot();

    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$assignment->id}")
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Training assignment withdrawn.');

    expect($assignment->fresh())->toBeNull()
        ->and(TrainingLessonCompletion::query()->where('assignment_id', $assignment->id)->exists())->toBeFalse()
        ->and($kept->fresh())->not->toBeNull()
        ->and(TrainingLessonCompletion::query()->where('assignment_id', $kept->id)->count())->toBe(1)
        ->and(withdrawTrainingSnapshot())->toBe($before);
});

test('a completed assignment is kept as learning history and cannot be withdrawn', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    $recruiter = withdrawStaff();
    [, $v1] = withdrawCourse($lead);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();

    foreach ($v1->lessons as $lesson) {
        app(TrainingProgressService::class)->completeLesson($assignment, $lesson, $recruiter->user);
    }

    expect($assignment->fresh()->isCompleted())->toBeTrue();
    $before = withdrawTrainingSnapshot();

    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$assignment->id}")->assertForbidden();

    expect($assignment->fresh())->not->toBeNull()
        ->and(TrainingLessonCompletion::query()->where('assignment_id', $assignment->id)->count())->toBe(3)
        ->and(withdrawTrainingSnapshot())->toBe($before);
});

test('the assignment list offers withdrawal only where it is allowed', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    [, $v1] = withdrawCourse($lead);
    TrainingAssignment::factory()->forVersion($v1)->forEmployee(withdrawStaff())->create();

    $this->actingAs($lead->user)->get('/recruiter/training/assignments')->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/assignments/index')
            ->where('assignments.data.0.can.delete', true));
});

test('the course page offers to publish its draft, and publishing makes it live', function () {
    $lead = withdrawStaff(SystemRole::RecruiterLead);
    [$course, $v1, $v2] = withdrawCourse($lead);

    $this->actingAs($lead->user)->get("/recruiter/training/manage/courses/{$course->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->where('draft.id', $v2->id)
            ->where('draft.lessons_count', 4)
            ->where('draft.is_shown', false)
            ->where('draft.can_publish', true));

    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$v2->id}/publish")->assertSessionHasNoErrors();

    expect($course->fresh()->current_version_id)->toBe($v2->id)
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Archived);

    $this->actingAs($lead->user)->get("/recruiter/training/manage/courses/{$course->id}")
        ->assertInertia(fn ($page) => $page->where('draft', null));
});
