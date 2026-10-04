<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use App\Modules\RecruiterOperations\Services\TrainingProgressService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function coursePageStaff(SystemRole $role = SystemRole::Recruiter): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A published course of five lessons in three modules: two in "Before OPT",
 * two in "OPT Application" and one without a module. The fifth is optional.
 *
 * @return array{0: TrainingCourse, 1: TrainingCourseVersion, 2: list<TrainingLesson>}
 */
function coursePageCourse(): array
{
    $version = TrainingCourseVersion::factory()->published()->create();
    $version->course->forceFill(['title' => 'OPT to STEM OPT: Complete Recruiter Process'])->save();
    $modules = ['Before OPT', 'Before OPT', 'OPT Application', 'OPT Application', null];
    $lessons = [];

    foreach ($modules as $index => $module) {
        $number = $index + 1;
        $lessons[] = TrainingLesson::factory()->forVersion($version, $number)->create([
            'module' => $module,
            'title' => "Step {$number}",
            'description' => null,
            'body' => "Learning objective\nUnderstand step {$number}.\n\nKey takeaway\nCheck step {$number} before every call.",
            'is_required' => $number <= 4,
        ]);
    }

    return [$version->course->fresh(), $version, $lessons];
}

function coursePageUrl(TrainingCourse $course): string
{
    return "/recruiter/training/courses/{$course->id}";
}

// 1-6. The whole course on one page, in order, grouped into modules

test('a recruiter opens an assigned course and gets every lesson, in order, with content, audio and module anchors', function () {
    $recruiter = coursePageStaff();
    [$course, $version, $lessons] = coursePageCourse();
    TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/course')
            ->has('lessons', 5)
            ->where('lessons.0.id', $lessons[0]->id)
            ->where('lessons.1.id', $lessons[1]->id)
            ->where('lessons.2.id', $lessons[2]->id)
            ->where('lessons.3.id', $lessons[3]->id)
            ->where('lessons.4.id', $lessons[4]->id)
            ->where('lessons.0.module', 'Before OPT')
            ->where('lessons.0.languages.0.sections.0.kind', 'objective')
            ->where('lessons.0.languages.0.sections.0.body', 'Understand step 1.')
            ->where('lessons.0.completed', false)
            ->where('lessons.0.counted', true)
            ->where('lessons.4.counted', false)
            ->where('lessons.0.urls.complete', route('recruiter.training.lessons.complete', [$course, $lessons[0]]))
            ->where('lessons.0.audio.speech_url', route('recruiter.training.lessons.speech', $lessons[0]))
            ->where('lessons.0.audio.has_text_by_language.en', true)
            ->has('modules', 3)
            ->where('modules.0', ['anchor' => 'module-before-opt', 'title' => 'Before OPT', 'number' => 1, 'lesson_ids' => [$lessons[0]->id, $lessons[1]->id]])
            ->where('modules.1', ['anchor' => 'module-opt-application', 'title' => 'OPT Application', 'number' => 2, 'lesson_ids' => [$lessons[2]->id, $lessons[3]->id]])
            ->where('modules.2.anchor', 'module-opt-to-stem-opt-complete-recruiter-process')
            ->where('modules.2.title', 'OPT to STEM OPT: Complete Recruiter Process')
            ->where('modules.2.lesson_ids', [$lessons[4]->id])
            ->where('progress', ['percent' => 0, 'completed' => 0, 'counted' => 4, 'total' => 5, 'remaining' => 4])
            ->where('audio.voices.0.locale', 'en-IN')
            ->where('audio.voices_by_language.te.0.locale', 'te-IN'));
});

test('lessons follow their order, not their creation, and a module name used twice apart gets its own anchor', function () {
    [$course, , $lessons] = coursePageCourse();
    $lessons[4]->forceFill(['sort_order' => 0, 'module' => 'Before OPT'])->save();
    $lessons[0]->forceFill(['module' => 'OPT Application'])->save();
    $ordered = $course->versions()->sole()->lessons()->get();

    $modules = app(TrainingPresenter::class)->modules($ordered, $course->title);

    expect($ordered->pluck('id')->first())->toBe($lessons[4]->id)
        ->and(array_column($modules, 'anchor'))->toBe(['module-before-opt', 'module-opt-application', 'module-before-opt-2', 'module-opt-application-2'])
        ->and(array_column($modules, 'number'))->toBe([1, 2, 3, 4]);
});

test('a course with no modules is one module named after the course', function () {
    [$course, , $lessons] = coursePageCourse();
    TrainingLesson::query()->update(['module' => null]);

    $modules = app(TrainingPresenter::class)->modules($course->versions()->sole()->lessons()->get(), $course->title);

    expect($modules)->toHaveCount(1)
        ->and($modules[0]['lesson_ids'])->toBe(array_map(fn (TrainingLesson $lesson) => $lesson->id, $lessons));
});

// 8-10. Completing a lesson stays on the page and updates progress

test('marking a lesson complete from the course page saves it and returns progress without navigating away', function () {
    $recruiter = coursePageStaff();
    [$course, $version, $lessons] = coursePageCourse();
    $assignment = TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $lessons[0]]))
        ->assertOk()
        ->assertJson([
            'lesson_id' => $lessons[0]->id,
            'completed' => true,
            'progress' => ['percent' => 25, 'completed' => 1, 'counted' => 4, 'total' => 5, 'remaining' => 3],
            'assignment' => ['status' => 'in_progress'],
            'course_completed' => false,
            'message' => 'Lesson marked complete.',
        ]);

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))
        ->assertInertia(fn ($page) => $page->where('lessons.0.completed', true)
            ->where('lessons.1.completed', false)
            ->where('progress.completed', 1)
            ->where('progress.remaining', 3)
            ->has('lessons', 5));

    foreach ([1, 2] as $index) {
        $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $lessons[$index]]))->assertOk();
    }

    $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $lessons[3]]))
        ->assertOk()
        ->assertJson(['progress' => ['percent' => 100, 'remaining' => 0], 'course_completed' => true, 'message' => 'Course completed. Well done!']);

    $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $lessons[3]]))->assertOk();

    expect($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::Completed)
        ->and(TrainingLessonCompletion::query()->whereNotNull('completed_at')->count())->toBe(4);
});

test('the single lesson page still moves on to the next lesson after a plain completion', function () {
    $recruiter = coursePageStaff();
    [$course, $version, $lessons] = coursePageCourse();
    TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)->post(route('recruiter.training.lessons.complete', [$course, $lessons[0]]))
        ->assertRedirect("/recruiter/training/courses/{$course->id}/lessons/{$lessons[1]->id}");
});

test('a recruiter cannot complete lessons of a course that is not assigned to them', function () {
    $recruiter = coursePageStaff();
    [$course, , $lessons] = coursePageCourse();

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))->assertNotFound();
    $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $lessons[0]]))->assertNotFound();

    expect(TrainingLessonCompletion::query()->count())->toBe(0);
});

// 15. Audio never completes a lesson

test('reporting audio and reading time from the course page never completes a lesson', function () {
    $recruiter = coursePageStaff();
    [$course, $version, $lessons] = coursePageCourse();
    $assignment = TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->postJson(route('recruiter.training.lessons.progress', [$course, $lessons[0]]), ['audio_seconds' => 900, 'spent_seconds' => 120])
        ->assertOk()
        ->assertJson(['audio_progress_seconds' => 900, 'completed' => false]);

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))
        ->assertInertia(fn ($page) => $page->where('lessons.0.started', true)
            ->where('lessons.0.completed', false)
            ->where('lessons.0.audio_progress_seconds', 900)
            ->where('progress.completed', 0));

    expect($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::InProgress);
});

// 11-12. Only the assigned published version

test('the page shows the assigned published version only, never a newer draft or a newer published version', function () {
    $lead = coursePageStaff(SystemRole::RecruiterLead);
    $recruiter = coursePageStaff();
    [$course, $v1, $lessons] = coursePageCourse();
    TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $content = app(TrainingContentService::class);

    $v2 = $content->createVersion($course, $lead->user);
    $content->createLesson($v2, ['module' => 'STEM OPT', 'title' => 'Draft only lesson', 'content_type' => 'text', 'body' => "Learning objective\nDraft."], null, $lead->user);
    $v2->lessons()->orderBy('sort_order')->first()->forceFill(['title' => 'Renamed in the draft'])->save();

    $assertVersion1 = fn () => $this->actingAs($recruiter->user)->get(coursePageUrl($course))
        ->assertInertia(fn ($page) => $page->has('lessons', 5)
            ->where('lessons.0.id', $lessons[0]->id)
            ->where('lessons.0.title', 'Step 1')
            ->has('modules', 3)
            ->where('version.label', $v1->label()));

    $assertVersion1();

    $content->publishVersion($v2->fresh(), $lead->user);

    $assertVersion1();
    $this->actingAs($recruiter->user)->postJson(route('recruiter.training.lessons.complete', [$course, $v2->lessons()->first()]))->assertNotFound();
});

test('the course header gets the title, description, assigned version and lesson counts, and resume points at the first incomplete lesson', function () {
    $lead = coursePageStaff(SystemRole::RecruiterLead);
    $recruiter = coursePageStaff();
    [$course, $v1, $lessons] = coursePageCourse();
    $course->forceFill(['description' => 'From F-1 graduation to the end of STEM OPT.'])->save();
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($course, $lead->user);
    $content->createLesson($v2, ['module' => 'STEM OPT', 'title' => 'Draft only lesson', 'content_type' => 'text', 'body' => "Learning objective\nDraft."], null, $lead->user);

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))->assertOk()
        ->assertInertia(fn ($page) => $page->where('course.title', 'OPT to STEM OPT: Complete Recruiter Process')
            ->where('course.description', 'From F-1 graduation to the end of STEM OPT.')
            ->where('version.label', $v1->label())
            ->has('lessons', 5)
            ->where('progress.completed', 0)
            ->where('progress.counted', 4)
            ->where('assignment.started_at', null));

    foreach ([0, 1] as $index) {
        app(TrainingProgressService::class)->completeLesson($assignment, $lessons[$index], $recruiter->user);
    }

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))
        ->assertInertia(fn ($page) => $page->where('progress.completed', 2)
            ->where('progress.remaining', 2)
            ->where('resumeLessonId', $lessons[2]->id)
            ->where('lessons.2.completed', false)
            ->whereNot('assignment.started_at', null));

    expect($v2->fresh()->lessons()->count())->toBe(6);
});

// 13-17. Languages

/**
 * A published single-lesson course with structured English and a Telugu
 * translation, assigned to a recruiter. Telugu is approved only when asked.
 *
 * @return array{0: Employee, 1: TrainingCourse, 2: TrainingLesson}
 */
function coursePageTranslatedCourse(bool $approveTelugu): array
{
    $lead = coursePageStaff(SystemRole::RecruiterLead);
    $recruiter = coursePageStaff();
    $version = TrainingCourseVersion::factory()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['title' => 'OPT', 'description' => null, 'body' => null]);
    $contents = app(TrainingLessonContentService::class);
    $reviews = app(TrainingContentReviewService::class);

    $contents->save($lesson, TrainingLanguage::English, [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Understand OPT.'],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Check the EAD dates.'],
    ], $lead->user);
    $contents->save($lesson->refresh(), TrainingLanguage::Telugu, [
        ['kind' => 'objective', 'heading' => 'నేర్చుకునే లక్ష్యం', 'body' => 'OPT ను అర్థం చేసుకోండి.'],
        ['kind' => 'takeaway', 'heading' => 'ముఖ్యమైన విషయం', 'body' => 'EAD dates check చేయండి.'],
    ], $lead->user);
    $reviews->setStatus($lesson->refresh(), TrainingLanguage::English, TrainingContentReview::Approved, $lead->user);

    if ($approveTelugu) {
        $reviews->setStatus($lesson->refresh(), TrainingLanguage::Telugu, TrainingContentReview::Approved, $lead->user);
    }

    app(TrainingContentService::class)->publishVersion($version->fresh(), $lead->user);
    TrainingAssignment::factory()->forVersion($version->fresh())->forEmployee($recruiter)->create();

    return [$recruiter, $version->course->fresh(), $lesson->fresh()];
}

test('saved Telugu is shown to recruiters without waiting for review', function () {
    [$recruiter, $course] = coursePageTranslatedCourse(approveTelugu: false);

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))->assertOk()
        ->assertInertia(fn ($page) => $page->where('lessons.0.languages.0.code', 'en')
            ->where('lessons.0.languages.0.sections.0.body', 'Understand OPT.')
            ->where('lessons.0.languages.1.code', 'te')
            ->where('lessons.0.languages.1.available', true)
            ->where('lessons.0.audio.has_text_by_language.te', true));
});

test('approved Telugu is shown with its own sections and audio, alongside English', function () {
    [$recruiter, $course, $lesson] = coursePageTranslatedCourse(approveTelugu: true);

    $this->actingAs($recruiter->user)->get(coursePageUrl($course))->assertOk()
        ->assertInertia(fn ($page) => $page->where('lessons.0.languages.0.available', true)
            ->where('lessons.0.languages.1.available', true)
            ->where('lessons.0.languages.1.outdated', false)
            ->where('lessons.0.languages.1.sections.0.heading', 'నేర్చుకునే లక్ష్యం')
            ->where('lessons.0.audio.has_text_by_language.te', true));

    $this->actingAs($recruiter->user)->getJson(route('recruiter.training.lessons.speech', $lesson).'?language=te&voice=telugu')
        ->assertOk()->assertJson(['has_text' => true]);
});

// 20. Managers keep their tools

test('managers set a lesson module on drafts, see it in Manage, and new versions keep it', function () {
    $lead = coursePageStaff(SystemRole::RecruiterLead);
    $version = TrainingCourseVersion::factory()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['title' => 'Candidate Contact', 'module' => null]);

    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$lesson->id}", [
        'module' => '  Internal Company Recruiter Process ',
        'title' => 'Candidate Contact',
        'content_type' => 'text',
        'body' => "Learning objective\nMake first contact.",
        'is_required' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($lesson->fresh()->module)->toBe('Internal Company Recruiter Process');

    $this->actingAs($lead->user)->get("/recruiter/training/manage/courses/{$version->course_id}?version={$version->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedVersion.lessons.0.module', 'Internal Company Recruiter Process'));

    $this->actingAs($lead->user)->get("/recruiter/training/manage/lessons/{$lesson->id}/edit")->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.module', 'Internal Company Recruiter Process')
            ->where('version.modules', ['Internal Company Recruiter Process']));

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review')->assertOk();

    $content = app(TrainingContentService::class);
    $content->publishVersion($version->fresh(), $lead->user);
    $v2 = $content->createVersion($version->course->fresh(), $lead->user);
    $content->publishVersion($v2, $lead->user);

    expect($v2->lessons()->sole()->module)->toBe('Internal Company Recruiter Process')
        ->and($lesson->fresh()->module)->toBe('Internal Company Recruiter Process');

    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$lesson->id}", [
        'module' => 'Changed',
        'title' => 'Candidate Contact',
        'content_type' => 'text',
        'is_required' => 1,
    ])->assertForbidden();

    expect($lesson->fresh()->module)->toBe('Internal Company Recruiter Process');
});

test('a recruiter cannot open the manager pages', function () {
    $recruiter = coursePageStaff();
    $version = TrainingCourseVersion::factory()->create();

    $this->actingAs($recruiter->user)->get("/recruiter/training/manage/courses/{$version->course_id}")->assertForbidden();
    $this->actingAs($recruiter->user)->get('/recruiter/training/manage/review')->assertForbidden();
});
