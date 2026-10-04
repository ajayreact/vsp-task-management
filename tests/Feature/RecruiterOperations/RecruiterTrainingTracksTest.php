<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/*
| Training tracks: OPT Recruiter and Bench Sales Recruiter are separate. A
| track's courses are never listed, opened or assigned under another track,
| and placing courses in a track never touches versions or assignments.
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function trackPerson(SystemRole ...$roles): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles(array_map(fn (SystemRole $role) => $role->value, $roles));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

function optRecruiterTrack(): TrainingTrack
{
    return TrainingTrack::query()->where('slug', TrainingTrack::OPT_RECRUITER)->sole();
}

function benchSalesTrack(): TrainingTrack
{
    return TrainingTrack::query()->where('slug', TrainingTrack::BENCH_SALES_RECRUITER)->sole();
}

/**
 * A published course with lessons, optionally in a track and with a draft
 * Version 2.
 *
 * @return array{0: TrainingCourse, 1: TrainingCourseVersion}
 */
function trackCourse(?TrainingTrack $track, int $lessons = 2, bool $withDraft = false, ?string $title = null): array
{
    $title ??= 'Track course '.Str::random(6);
    $course = TrainingCourse::factory()->create([
        'title' => $title,
        'slug' => Str::slug($title),
        'training_track_id' => $track?->id,
    ]);
    $v1 = TrainingCourseVersion::factory()->forCourse($course, 1)->published()->create();

    for ($i = 1; $i <= $lessons; $i++) {
        TrainingLesson::factory()->forVersion($v1, $i)->create(['module' => 'Module A', 'body' => "Lesson {$i} body."]);
    }

    if ($withDraft) {
        $v2 = TrainingCourseVersion::factory()->forCourse($course, 2)->create();
        TrainingLesson::factory()->forVersion($v2, 1)->create(['module' => 'Module A']);
        TrainingLesson::factory()->forVersion($v2, 2)->create(['module' => 'Module B']);
        TrainingLesson::factory()->forVersion($v2, 3)->create(['module' => 'Module B']);
    }

    return [$course->fresh(), $v1];
}

/**
 * Everything placing courses in a track must leave alone.
 */
function trackFingerprint(): string
{
    return hash('sha256', json_encode([
        DB::table('ro_training_course_versions')->orderBy('id')->get(),
        DB::table('ro_training_lessons')->orderBy('id')->get(),
        DB::table('ro_training_assignments')->orderBy('id')->get(),
        DB::table('ro_training_lesson_completions')->orderBy('id')->get(),
        DB::table('ro_training_courses')->orderBy('id')->get(['id', 'category_id', 'title', 'slug', 'status', 'current_version_id', 'updated_at']),
    ]));
}

// Tracks

test('the OPT Recruiter and Bench Sales Recruiter tracks exist and are active', function () {
    expect(optRecruiterTrack()->name)->toBe('OPT Recruiter')
        ->and(optRecruiterTrack()->isActive())->toBeTrue()
        ->and(benchSalesTrack()->name)->toBe('Bench Sales Recruiter')
        ->and(benchSalesTrack()->isActive())->toBeTrue()
        ->and(benchSalesTrack()->courses()->count())->toBe(0);
});

test('the track command places the four OPT courses in OPT Recruiter without touching versions or assignments', function () {
    $recruiter = trackPerson(SystemRole::Recruiter);
    $plans = RecruiterTrainingContent::optTrack();
    expect($plans)->toHaveCount(4);

    $opt = [];
    foreach ($plans as $plan) {
        [$course, $v1] = trackCourse(null, 2, true, $plan['course']);
        TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
        $opt[] = $course;
    }
    [$other] = trackCourse(null);
    $before = trackFingerprint();

    $this->artisan('recruiter:training-tracks', ['--dry-run' => true])->assertSuccessful();
    expect(TrainingCourse::query()->whereNotNull('training_track_id')->count())->toBe(0);

    $this->artisan('recruiter:training-tracks')->assertSuccessful();
    $this->artisan('recruiter:training-tracks')->assertSuccessful();

    foreach ($opt as $course) {
        expect($course->fresh()->training_track_id)->toBe(optRecruiterTrack()->id);
    }

    expect($other->fresh()->training_track_id)->toBeNull()
        ->and(benchSalesTrack()->courses()->count())->toBe(0)
        ->and(TrainingCourse::query()->count())->toBe(5)
        ->and(trackFingerprint())->toBe($before);
});

test('the track command never moves a course that is already in another track', function () {
    $plan = RecruiterTrainingContent::optTrack()[0];
    [$course] = trackCourse(benchSalesTrack(), 1, false, $plan['course']);

    $this->artisan('recruiter:training-tracks')->assertFailed();

    expect($course->fresh()->training_track_id)->toBe(benchSalesTrack()->id);
});

// Track pages

test('the landing page shows track cards with counts, and Bench Sales is empty', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    trackCourse(optRecruiterTrack(), 2, true);
    trackCourse(optRecruiterTrack(), 4);

    $this->actingAs($lead->user)
        ->get('/recruiter/training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/dashboard')
            ->where('trackView', 'catalog')
            ->where('tracks.0.slug', 'opt-recruiter')
            ->where('tracks.0.courses', 2)
            ->where('tracks.0.lessons', 6)
            ->where('tracks.0.modules', 2)
            ->where('tracks.1.slug', 'bench-sales-recruiter')
            ->where('tracks.1.courses', 0)
            ->where('tracks.1.lessons', 0));
});

test('the OPT track page lists only OPT courses and the Bench Sales page only Bench Sales courses', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    [$optCourse] = trackCourse(optRecruiterTrack());
    [$benchCourse] = trackCourse(benchSalesTrack());
    [$loose] = trackCourse(null);

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/track')
            ->where('track.slug', 'opt-recruiter')
            ->has('courses', 1)
            ->where('courses.0.id', $optCourse->id)
            ->where('courses.0.lessons', 2)
            ->where('courses.0', fn ($course) => array_keys(collect($course)->all()) === ['id', 'title', 'description', 'status', 'status_label', 'duration_minutes', 'modules', 'lessons']));

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/bench-sales-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->where('courses.0.id', $benchCourse->id));

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/unassigned')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('courses', 1)->where('courses.0.id', $loose->id));
});

test('the empty Bench Sales track has no courses and is never filled with OPT content', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    trackCourse(optRecruiterTrack());

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/bench-sales-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('track.name', 'Bench Sales Recruiter')->where('courses', [])->where('assignments', []));
});

test('a recruiter sees their courses with the same Course numbers and order as the track page', function () {
    $recruiter = trackPerson(SystemRole::Recruiter);
    $levels = [7, 1, 2];
    $courses = [];
    foreach ($levels as $level) {
        [$course, $v1] = trackCourse(optRecruiterTrack());
        $category = TrainingCategory::factory()->create(['name' => "Level {$level} - Topic", 'level_number' => $level, 'sort_order' => $level]);
        $course->update(['category_id' => $category->id]);
        TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
        $courses[$level] = $course;
    }

    $expected = [$courses[1]->id, $courses[2]->id, $courses[7]->id];

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignments', fn ($cards) => collect($cards)->pluck('course.id')->all() === $expected
                && collect($cards)->pluck('course_number')->all() === [1, 2, 3]));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training?track=opt-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('assignments', fn ($cards) => collect($cards)->pluck('course.id')->all() === $expected
                && collect($cards)->pluck('course_number')->all() === [1, 2, 3]));

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$courses[7]->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('course.course_number', 3)->where('course.track', 'OPT Recruiter'));

    $lead = trackPerson(SystemRole::RecruiterLead);
    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertInertia(fn ($page) => $page->where('courses', fn ($list) => collect($list)->pluck('id')->all() === $expected));
});

test('an unknown track is a 404', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);

    $this->actingAs($lead->user)->get('/recruiter/training/tracks/finance')->assertNotFound();
});

test('a manager switches tracks on the manage page and sees only that track', function () {
    $admin = trackPerson(SystemRole::Admin);
    [$optCourse] = trackCourse(optRecruiterTrack());
    [$benchCourse] = trackCourse(benchSalesTrack());
    trackCourse(null);

    $this->actingAs($admin->user)
        ->get('/recruiter/training/manage')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.track', 'opt-recruiter')
            ->where('trackName', 'OPT Recruiter')
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $optCourse->id)
            ->where('tracks', fn ($tracks) => collect($tracks)->pluck('value')->all() === ['opt-recruiter', 'bench-sales-recruiter', 'unassigned']));

    $this->actingAs($admin->user)
        ->get('/recruiter/training/manage?track=bench-sales-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('courses.data', 1)->where('courses.data.0.id', $benchCourse->id));
});

// Assignments

test('the assignment form lists courses with their track so the course list follows the chosen track', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    [$optCourse] = trackCourse(optRecruiterTrack());
    [$benchCourse] = trackCourse(benchSalesTrack());

    $this->actingAs($lead->user)
        ->get("/recruiter/training/assignments/create?course={$benchCourse->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defaults.track', 'bench-sales-recruiter')
            ->where('defaults.course_id', (string) $benchCourse->id)
            ->where('courses', fn ($courses) => collect($courses)->firstWhere('id', $optCourse->id)['track'] === 'opt-recruiter'
                && collect($courses)->firstWhere('id', $benchCourse->id)['track'] === 'bench-sales-recruiter'));

    $this->actingAs($lead->user)
        ->get('/recruiter/training/assignments?track=opt-recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('courses', fn ($courses) => collect($courses)->pluck('id')->all() === [$optCourse->id]));
});

test('the server refuses to assign a course under another track', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    $recruiter = trackPerson(SystemRole::Recruiter);
    [$optCourse] = trackCourse(optRecruiterTrack());
    [$benchCourse] = trackCourse(benchSalesTrack());
    $payload = fn (string $track, TrainingCourse $course) => ['track' => $track, 'course_id' => $course->id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]];

    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload('bench-sales-recruiter', $optCourse))->assertSessionHasErrors('course_id');
    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload('opt-recruiter', $benchCourse))->assertSessionHasErrors('course_id');
    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload('unassigned', $optCourse))->assertSessionHasErrors('course_id');
    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload('finance', $optCourse))->assertSessionHasErrors('track');
    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['course_id' => $optCourse->id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]])
        ->assertSessionHasErrors('track');

    expect(TrainingAssignment::query()->count())->toBe(0);

    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload('opt-recruiter', $optCourse))->assertSessionHasNoErrors();

    expect(TrainingAssignment::query()->sole()->employee_id)->toBe($recruiter->id);
});

test('an assigned course cannot be moved to another track; an unassigned draft course can', function () {
    $admin = trackPerson(SystemRole::Admin);
    $recruiter = trackPerson(SystemRole::Recruiter);
    [$assigned, $v1] = trackCourse(optRecruiterTrack());
    TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    [$legacy, $legacyV1] = trackCourse(null);
    TrainingAssignment::factory()->forVersion($legacyV1)->forEmployee($recruiter)->create();
    $fresh = TrainingCourse::factory()->inTrack(optRecruiterTrack())->create();
    $form = fn (TrainingCourse $course, ?int $track) => ['category_id' => $course->category_id, 'training_track_id' => $track, 'title' => $course->title];

    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$assigned->id}", $form($assigned, benchSalesTrack()->id))
        ->assertSessionHasErrors('training_track_id');
    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$assigned->id}", $form($assigned, null))
        ->assertSessionHasErrors('training_track_id');
    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$assigned->id}", $form($assigned, optRecruiterTrack()->id))
        ->assertSessionHasNoErrors();

    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$fresh->id}", $form($fresh, benchSalesTrack()->id))
        ->assertSessionHasNoErrors();
    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$legacy->id}", $form($legacy, optRecruiterTrack()->id))
        ->assertSessionHasNoErrors();
    $this->actingAs($admin->user)
        ->put("/recruiter/training/manage/courses/{$legacy->id}", $form($legacy->fresh(), benchSalesTrack()->id))
        ->assertSessionHasErrors('training_track_id');

    expect($assigned->fresh()->training_track_id)->toBe(optRecruiterTrack()->id)
        ->and($fresh->fresh()->training_track_id)->toBe(benchSalesTrack()->id)
        ->and($legacy->fresh()->training_track_id)->toBe(optRecruiterTrack()->id)
        ->and($legacyV1->fresh()->status->value)->toBe('published');
});

test('a new course is created in the chosen track', function () {
    $admin = trackPerson(SystemRole::Admin);
    $category = TrainingCategory::factory()->create();

    $this->actingAs($admin->user)
        ->post('/recruiter/training/manage/courses', ['category_id' => $category->id, 'training_track_id' => benchSalesTrack()->id, 'title' => 'Bench Sales Basics'])
        ->assertSessionHasNoErrors();

    expect(TrainingCourse::query()->sole()->training_track_id)->toBe(benchSalesTrack()->id);
});

// Recruiters

test('a recruiter sees only their own assigned training, grouped by track', function () {
    $recruiter = trackPerson(SystemRole::Recruiter);
    $colleague = trackPerson(SystemRole::Recruiter);
    [, $mine] = trackCourse(optRecruiterTrack());
    [, $notMine] = trackCourse(optRecruiterTrack());
    [, $bench] = trackCourse(benchSalesTrack());
    TrainingAssignment::factory()->forVersion($mine)->forEmployee($recruiter)->create();
    TrainingAssignment::factory()->forVersion($notMine)->forEmployee($colleague)->create();
    TrainingAssignment::factory()->forVersion($bench)->forEmployee($colleague)->create();

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training')
        ->assertInertia(fn ($page) => $page
            ->where('trackView', 'learner')
            ->has('tracks', 1)
            ->where('tracks.0.slug', 'opt-recruiter')
            ->where('tracks.0.courses', 1));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertInertia(fn ($page) => $page
            ->has('assignments', 1)
            ->where('assignments.0.course.id', $mine->course_id)
            ->where('courses', []));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/tracks/bench-sales-recruiter')
        ->assertInertia(fn ($page) => $page->where('assignments', [])->where('courses', []));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training')
        ->assertInertia(fn ($page) => $page
            ->where('filters.track', 'opt-recruiter')
            ->has('tracks', 1)
            ->has('assignments', 1)
            ->where('assignments.0.course.id', $mine->course_id));
});

test('My Training shows one track at a time', function () {
    $recruiter = trackPerson(SystemRole::Recruiter);
    [, $opt] = trackCourse(optRecruiterTrack());
    [, $bench] = trackCourse(benchSalesTrack());
    TrainingAssignment::factory()->forVersion($opt)->forEmployee($recruiter)->create();
    TrainingAssignment::factory()->forVersion($bench)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training')
        ->assertInertia(fn ($page) => $page->has('tracks', 2)->has('assignments', 1)->where('assignments.0.track', 'opt-recruiter'));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training?track=bench-sales-recruiter')
        ->assertInertia(fn ($page) => $page->has('assignments', 1)->where('assignments.0.track', 'bench-sales-recruiter'));
});

test('assignments stay pinned to their version inside a track', function () {
    $lead = trackPerson(SystemRole::RecruiterLead);
    $recruiter = trackPerson(SystemRole::Recruiter);
    [$course, $v1] = trackCourse(optRecruiterTrack(), 2);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();

    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($course, $lead->user);
    $content->publishVersion($v2, $lead->user);

    expect($assignment->fresh()->course_version_id)->toBe($v1->id);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertInertia(fn ($page) => $page->has('assignments', 1)->where('assignments.0.version', $v1->label()));
});
