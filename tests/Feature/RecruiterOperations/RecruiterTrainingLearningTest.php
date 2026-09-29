<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Exceptions\TrainingSpeechException;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingProgressService;
use App\Modules\RecruiterOperations\Speech\Providers\DisabledSpeechProvider;
use App\Modules\RecruiterOperations\Speech\TrainingAudio;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\VoiceProfile;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function trainingLearner(SystemRole $role = SystemRole::Recruiter): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A published course. Returns the course, its live version and its lessons.
 *
 * @return array{0: TrainingCourse, 1: TrainingCourseVersion, 2: list<TrainingLesson>}
 */
function liveTrainingCourse(int $required = 2, int $optional = 0): array
{
    $version = TrainingCourseVersion::factory()->published()->create();
    $lessons = [];

    for ($i = 1; $i <= $required + $optional; $i++) {
        $lessons[] = TrainingLesson::factory()->forVersion($version, $i)->create([
            'title' => "Lesson {$i}",
            'body' => "Lesson {$i} explains U.S. calling hours.",
            'is_required' => $i <= $required,
        ]);
    }

    return [$version->course->fresh(), $version, $lessons];
}

function assignTraining(TrainingCourseVersion $version, Employee $recruiter): TrainingAssignment
{
    return TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();
}

/**
 * A stand-in for a paid server-side speech provider that records every call.
 */
function fakeSpeechProvider(?Throwable $failure = null): TrainingSpeechProvider
{
    return new class($failure) implements TrainingSpeechProvider
    {
        /** @var list<array{text: string, voice: string, locale: string}> */
        public array $calls = [];

        public function __construct(private ?Throwable $failure) {}

        public function name(): string
        {
            return 'fake-cloud';
        }

        public function delivery(): TrainingSpeechDelivery
        {
            return TrainingSpeechDelivery::Audio;
        }

        public function supportedVoices(): array
        {
            return ['indian_english', 'us_english'];
        }

        public function synthesize(string $text, VoiceProfile $voice): TrainingAudio
        {
            $this->calls[] = ['text' => $text, 'voice' => $voice->key, 'locale' => $voice->locale];

            if ($this->failure !== null) {
                throw $this->failure;
            }

            return new TrainingAudio('ID3-fake-audio', 'audio/mpeg', 'mp3');
        }
    };
}

// E. Assignments

test('a lead assigns a course to one recruiter, pinned to the live version, and only the recruiter is notified', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    [$course, $version] = liveTrainingCourse();
    $due = today()->addWeek()->toDateString();

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', [
            'course_id' => $course->id,
            'mode' => 'individual',
            'employee_ids' => [$recruiter->id],
            'due_at' => $due,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Training assigned to 1 recruiter.')
        ->assertRedirect("/recruiter/training/assignments?course={$course->id}");

    $assignment = TrainingAssignment::query()->sole();

    expect($assignment->employee_id)->toBe($recruiter->id)
        ->and($assignment->course_version_id)->toBe($version->id)
        ->and($assignment->status)->toBe(TrainingAssignmentStatus::Assigned)
        ->and($assignment->assigned_by_user_id)->toBe($lead->user->id)
        ->and($assignment->due_at->toDateString())->toBe($due)
        ->and($assignment->due_at->format('H:i'))->toBe('23:59');

    $notification = $recruiter->user->notifications()->sole();

    expect($notification->data['event'])->toBe('recruiter.training.assigned')
        ->and($notification->data['url'])->toBe("/recruiter/training/courses/{$course->id}")
        ->and($notification->data['recruiter_training_assignment_id'])->toBe($assignment->id)
        ->and($lead->user->notifications()->count())->toBe(0);
});

test('assigning again skips recruiters who already have the course open', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $first = trainingLearner();
    $second = trainingLearner();
    [$course, $version] = liveTrainingCourse();
    assignTraining($version, $first);

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['course_id' => $course->id, 'mode' => 'multiple', 'employee_ids' => [$first->id, $second->id]])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Training assigned to 1 recruiter.')
            && str_contains($message, 'Already has this course open.'));

    expect(TrainingAssignment::query()->where('employee_id', $first->id)->count())->toBe(1)
        ->and(TrainingAssignment::query()->where('employee_id', $second->id)->count())->toBe(1)
        ->and($first->user->notifications()->count())->toBe(0);
});

test('a recruiter who completed the live version is not given it again, but gets a newer version', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    [$course, $v1] = liveTrainingCourse(1);
    TrainingAssignment::factory()->completed()->forVersion($v1)->forEmployee($recruiter)->create();
    $payload = ['course_id' => $course->id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]];

    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload)
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Already completed this version.'));
    expect(TrainingAssignment::query()->count())->toBe(1);

    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($course, $lead->user);
    $content->publishVersion($v2, $lead->user);

    $this->actingAs($lead->user)->post('/recruiter/training/assignments', $payload)->assertSessionHasNoErrors();

    expect(TrainingAssignment::query()->where('course_version_id', $v2->id)->where('employee_id', $recruiter->id)->exists())->toBeTrue();
});

test('team mode assigns every active recruiter from the recruiter directory', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiters = collect([trainingLearner(), trainingLearner()]);
    $inactive = trainingLearner();
    $inactive->user->forceFill(['is_active' => false])->save();
    $notRecruiter = Employee::factory()->create();
    [$course] = liveTrainingCourse();

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['course_id' => $course->id, 'mode' => 'team'])
        ->assertSessionHasNoErrors();

    $assigned = TrainingAssignment::query()->pluck('employee_id');

    expect($assigned)->toContain($recruiters[0]->id, $recruiters[1]->id)
        ->and($assigned)->not->toContain($inactive->id)
        ->and($assigned)->not->toContain($notRecruiter->id)
        ->and($lead->user->notifications()->count())->toBe(0);
});

test('only published courses can be assigned, and only to recruiters', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    $notRecruiter = Employee::factory()->create();
    $draft = TrainingCourse::factory()->create();
    [$live] = liveTrainingCourse();
    $archived = liveTrainingCourse()[0];
    $archived->forceFill(['status' => 'archived'])->save();

    foreach ([$draft, $archived] as $course) {
        $this->actingAs($lead->user)
            ->post('/recruiter/training/assignments', ['course_id' => $course->id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]])
            ->assertSessionHasErrors('course_id');
    }

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['course_id' => $live->id, 'mode' => 'individual', 'employee_ids' => [$notRecruiter->id]])
        ->assertSessionHasErrors('employee_ids.0');

    expect(TrainingAssignment::query()->count())->toBe(0);
});

test('the due date cannot be in the past', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    [$course] = liveTrainingCourse();

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', [
            'course_id' => $course->id,
            'mode' => 'individual',
            'employee_ids' => [$recruiter->id],
            'due_at' => today()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors('due_at');
});

test('recruiters cannot assign training; the permission is checked before validation', function () {
    $recruiter = trainingLearner();

    $this->actingAs($recruiter->user)->get('/recruiter/training/assignments')->assertForbidden();
    $this->actingAs($recruiter->user)->get('/recruiter/training/assignments/create')->assertForbidden();
    $this->actingAs($recruiter->user)->post('/recruiter/training/assignments', [])->assertForbidden();
});

test('an unstarted assignment can be withdrawn, a started one is kept', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    [, $version] = liveTrainingCourse();
    $unstarted = assignTraining($version, trainingLearner());
    $started = TrainingAssignment::factory()->inProgress()->forVersion($version)->forEmployee(trainingLearner())->create();

    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$unstarted->id}")->assertSessionHas('success');
    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$started->id}")->assertForbidden();

    expect(TrainingAssignment::query()->find($unstarted->id))->toBeNull()
        ->and($started->fresh())->not->toBeNull();
});

test('the assignment list filters by recruiter, course and status', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $alice = trainingLearner();
    $bob = trainingLearner();
    [$courseA, $versionA] = liveTrainingCourse();
    [, $versionB] = liveTrainingCourse();
    $aliceA = assignTraining($versionA, $alice);
    $bobB = TrainingAssignment::factory()->overdue()->forVersion($versionB)->forEmployee($bob)->create();

    $ids = fn (string $query) => collect($this->actingAs($lead->user)
        ->get("/recruiter/training/assignments?{$query}")
        ->assertOk()
        ->viewData('page')['props']['assignments']['data'])->pluck('id')->all();

    expect($ids("recruiter={$alice->id}"))->toBe([$aliceA->id])
        ->and($ids("course={$courseA->id}"))->toBe([$aliceA->id])
        ->and($ids('status=overdue'))->toBe([$bobB->id])
        ->and($ids('status=assigned'))->toBe([$aliceA->id]);
});

// F. Recruiter learning

test('a recruiter sees only their own training on the dashboard and in My Training', function () {
    $recruiter = trainingLearner();
    $colleague = trainingLearner();
    [, $mine] = liveTrainingCourse();
    [, $theirs] = liveTrainingCourse();
    $own = assignTraining($mine, $recruiter);
    assignTraining($theirs, $colleague);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/dashboard')
            ->where('counts.assigned', 1)
            ->where('counts.not_started', 1)
            ->where('counts.in_progress', 0)
            ->where('counts.completed', 0)
            ->where('counts.overdue', 0)
            ->where('counts.overall_percent', 0)
            ->has('continueLearning', 1)
            ->where('can.manage', false));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/my-training')
            ->has('assignments', 1)
            ->where('assignments.0.id', $own->id));
});

test('the course page needs an assignment', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(2);
    [$other] = liveTrainingCourse();
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)->get("/recruiter/training/courses/{$other->id}")->assertNotFound();

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/course')
            ->has('lessons', 2)
            ->where('progress.percent', 0)
            ->where('resumeLessonId', $lessons[0]->id)
            ->where('assignment.status', 'assigned'));
});

test('opening a lesson starts the assignment and the lesson, without completing either', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(2);
    $assignment = assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/lesson')
            ->where('lesson.id', $lessons[0]->id)
            ->where('position.index', 1)
            ->where('nextLesson.id', $lessons[1]->id)
            ->where('previousLesson', null)
            ->where('completion.completed', false));

    $completion = TrainingLessonCompletion::query()->sole();

    expect($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::InProgress)
        ->and($assignment->fresh()->started_at)->not->toBeNull()
        ->and($completion->started_at)->not->toBeNull()
        ->and($completion->completed_at)->toBeNull();
});

test('marking every required lesson complete completes the course; optional lessons are not needed', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(2, 1);
    $assignment = assignTraining($version, $recruiter);
    $base = "/recruiter/training/courses/{$course->id}/lessons";

    $this->actingAs($recruiter->user)
        ->post("{$base}/{$lessons[0]->id}/complete")
        ->assertRedirect("{$base}/{$lessons[1]->id}")
        ->assertSessionHas('success', 'Lesson marked complete.');

    expect(app(TrainingProgressService::class)->progressFor($assignment->fresh()))
        ->toMatchArray(['total' => 3, 'counted' => 2, 'completed' => 1, 'percent' => 50])
        ->and($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::InProgress);

    $this->actingAs($recruiter->user)
        ->post("{$base}/{$lessons[1]->id}/complete")
        ->assertRedirect("/recruiter/training/courses/{$course->id}")
        ->assertSessionHas('success', 'Course completed. Well done!');

    $assignment->refresh();

    expect($assignment->status)->toBe(TrainingAssignmentStatus::Completed)
        ->and($assignment->completed_at)->not->toBeNull()
        ->and(app(TrainingProgressService::class)->progressFor($assignment)['percent'])->toBe(100);

    $this->actingAs($recruiter->user)->post("{$base}/{$lessons[1]->id}/complete");

    expect(TrainingLessonCompletion::query()->whereNotNull('completed_at')->count())->toBe(2);
});

test('a lesson from another version cannot be opened or completed through a course', function () {
    $recruiter = trainingLearner();
    [$course, $version] = liveTrainingCourse(1);
    [, , $foreign] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)->get("/recruiter/training/courses/{$course->id}/lessons/{$foreign[0]->id}")->assertNotFound();
    $this->actingAs($recruiter->user)->post("/recruiter/training/courses/{$course->id}/lessons/{$foreign[0]->id}/complete")->assertNotFound();

    expect(TrainingLessonCompletion::query()->count())->toBe(0);
});

test('nobody can record progress on someone else\'s assignment, not even Super Admin', function () {
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    $assignment = assignTraining($version, $recruiter);
    $admin = superAdminEmployee();

    expect(fn () => app(TrainingProgressService::class)->completeLesson($assignment, $lessons[0], $admin->user))
        ->toThrow(AuthorizationException::class);
    expect(TrainingLessonCompletion::query()->count())->toBe(0);
});

test('the progress endpoint never completes a lesson, whatever the client sends', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(1);
    $assignment = assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->postJson("/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}/progress", [
            'audio_seconds' => 5000,
            'spent_seconds' => 3600,
            'completed' => true,
            'status' => 'completed',
        ])
        ->assertOk()
        ->assertJson(['audio_progress_seconds' => 5000, 'time_spent_seconds' => 300, 'completed' => false]);

    expect(TrainingLessonCompletion::query()->sole()->completed_at)->toBeNull()
        ->and($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::InProgress);
});

test('recruiters on an older version keep learning it after a new version is published', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    [$course, $v1, $v1Lessons] = liveTrainingCourse(1);
    $assignment = assignTraining($v1, $recruiter);
    $content = app(TrainingContentService::class);
    $v2 = $content->createVersion($course, $lead->user);
    $content->publishVersion($v2, $lead->user);
    $v2Lesson = $v2->lessons()->sole();
    $base = "/recruiter/training/courses/{$course->id}/lessons";

    $this->actingAs($recruiter->user)->get("{$base}/{$v1Lessons[0]->id}")->assertOk();
    $this->actingAs($recruiter->user)->get("{$base}/{$v2Lesson->id}")->assertNotFound();
    $this->actingAs($recruiter->user)->post("{$base}/{$v1Lessons[0]->id}/complete")->assertSessionHas('success', 'Course completed. Well done!');

    expect($assignment->fresh()->course_version_id)->toBe($v1->id)
        ->and($assignment->fresh()->status)->toBe(TrainingAssignmentStatus::Completed);
});

// G. Due dates

test('an open assignment past its due date shows as overdue; a completed one never does', function () {
    $recruiter = trainingLearner();
    [, $versionA] = liveTrainingCourse();
    [, $versionB] = liveTrainingCourse();
    $overdue = TrainingAssignment::factory()->inProgress()->overdue()->forVersion($versionA)->forEmployee($recruiter)->create();
    $done = TrainingAssignment::factory()->completed()->overdue()->forVersion($versionB)->forEmployee($recruiter)->create();

    expect($overdue->effectiveStatus())->toBe(TrainingAssignmentStatus::Overdue)
        ->and($overdue->fresh()->status)->toBe(TrainingAssignmentStatus::InProgress)
        ->and($done->effectiveStatus())->toBe(TrainingAssignmentStatus::Completed);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training')
        ->assertInertia(fn ($page) => $page->where('counts.overdue', 1)->where('counts.in_progress', 0)->where('counts.completed', 1));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/training/my-training?status=overdue')
        ->assertInertia(fn ($page) => $page->has('assignments', 1)->where('assignments.0.id', $overdue->id));
});

test('team progress is for recruiter.team.view and filters by status', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $recruiter = trainingLearner();
    [, $versionA] = liveTrainingCourse();
    [, $versionB] = liveTrainingCourse();
    $late = TrainingAssignment::factory()->overdue()->forVersion($versionA)->forEmployee($recruiter)->create();
    TrainingAssignment::factory()->dueIn(5)->forVersion($versionB)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)->get('/recruiter/training/team')->assertForbidden();

    $this->actingAs($lead->user)
        ->get('/recruiter/training/team?status=overdue')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/team')
            ->has('assignments.data', 1)
            ->where('assignments.data.0.id', $late->id)
            ->where('assignments.data.0.status', 'overdue'));
});

// H. Listen to Lesson

test('every lesson offers Listen to Lesson with Indian English first', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}")
        ->assertInertia(fn ($page) => $page
            ->where('audio.delivery', 'device')
            ->where('audio.has_text', true)
            ->where('audio.voices.0.key', 'indian_english')
            ->where('audio.voices.0.label', 'Indian English')
            ->where('audio.voices.0.locale', 'en-IN')
            ->where('audio.speech_url', route('recruiter.training.lessons.speech', $lessons[0])));
});

test('the speech endpoint returns the lesson text for the device voice, defaulting to Indian English', function () {
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);
    $url = "/recruiter/training/lessons/{$lessons[0]->id}/speech";

    $this->actingAs($recruiter->user)
        ->getJson($url)
        ->assertOk()
        ->assertJson([
            'delivery' => 'device',
            'provider' => 'browser',
            'voice' => ['key' => 'indian_english', 'locale' => 'en-IN'],
            'has_text' => true,
            'segments' => ['Lesson 1.', 'Lesson 1 explains U.S. calling hours.'],
            'audio_url' => null,
        ]);

    $this->actingAs($recruiter->user)->getJson("{$url}?voice=us_english")->assertOk()->assertJsonPath('voice.locale', 'en-US');
    $this->actingAs($recruiter->user)->getJson("{$url}?voice=klingon")->assertUnprocessable()->assertJsonValidationErrors('voice');
});

test('lesson speech is only for managers and recruiters assigned that version', function () {
    $lead = trainingLearner(SystemRole::RecruiterLead);
    $assigned = trainingLearner();
    $otherVersion = trainingLearner();
    $stranger = trainingLearner();
    [$course, $v1, $lessons] = liveTrainingCourse(1);
    assignTraining($v1, $assigned);
    $v2 = app(TrainingContentService::class)->createVersion($course, $lead->user);
    assignTraining($v2, $otherVersion);
    $url = "/recruiter/training/lessons/{$lessons[0]->id}/speech";

    $this->actingAs($lead->user)->getJson($url)->assertOk();
    $this->actingAs($assigned->user)->getJson($url)->assertOk();
    $this->actingAs($otherVersion->user)->getJson($url)->assertForbidden();
    $this->actingAs($stranger->user)->getJson($url)->assertForbidden();
    $this->actingAs($stranger->user)->get("/recruiter/training/lessons/{$lessons[0]->id}/audio")->assertForbidden();
});

test('audio progress is stored and clamped but never completes the lesson', function () {
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(1);
    $assignment = assignTraining($version, $recruiter);
    $url = "/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}/progress";

    $this->actingAs($recruiter->user)->postJson($url, ['audio_seconds' => 42])->assertOk()->assertJson(['audio_progress_seconds' => 42]);
    $this->actingAs($recruiter->user)->postJson($url, ['audio_seconds' => 999999])->assertUnprocessable();
    $this->actingAs($recruiter->user)->postJson($url, ['audio_seconds' => -5])->assertUnprocessable();

    expect(TrainingLessonCompletion::query()->sole())
        ->audio_progress_seconds->toBe(42)
        ->completed_at->toBeNull()
        ->and($assignment->fresh()->isCompleted())->toBeFalse();

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}")
        ->assertInertia(fn ($page) => $page->where('completion.audio_progress_seconds', 42));
});

test('a server-side provider receives the lesson text in the Indian English voice, and its audio is cached privately', function () {
    $provider = fakeSpeechProvider();
    $this->app->instance(TrainingSpeechProvider::class, $provider);
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);
    $lesson = $lessons[0];

    $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lesson->id}/speech")
        ->assertOk()
        ->assertJson([
            'delivery' => 'audio',
            'provider' => 'fake-cloud',
            'segments' => [],
            'audio_url' => route('recruiter.training.lessons.audio', [$lesson, 'voice' => 'indian_english']),
        ]);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/lessons/{$lesson->id}/audio?voice=indian_english")
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($provider->calls)->toBe([[
        'text' => "Lesson 1.\n\nLesson 1 explains U.S. calling hours.",
        'voice' => 'indian_english',
        'locale' => 'en-IN',
    ]]);
    expect(Storage::disk('local')->files("recruiter-training/audio/{$lesson->id}"))->toHaveCount(1);

    $this->actingAs($recruiter->user)->get("/recruiter/training/lessons/{$lesson->id}/audio?voice=indian_english")->assertOk();

    expect($provider->calls)->toHaveCount(1);
});

test('a provider voice that is not supported is refused rather than swapped', function () {
    $this->app->instance(TrainingSpeechProvider::class, fakeSpeechProvider());
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lessons[0]->id}/audio?voice=uk_english")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('voice');
});

test('a failing provider gives a safe 503 and nothing is cached', function () {
    $provider = fakeSpeechProvider(new RuntimeException('API key sk-secret rejected'));
    $this->app->instance(TrainingSpeechProvider::class, $provider);
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $response = $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lessons[0]->id}/audio")
        ->assertStatus(503)
        ->assertJson(['message' => 'The audio service is not available right now. Please try again later.']);

    expect($response->getContent())->not->toContain('sk-secret')
        ->and(Storage::disk('local')->files("recruiter-training/audio/{$lessons[0]->id}"))->toBe([]);
});

test('a provider error meant for users is passed through as a 503', function () {
    $this->app->instance(TrainingSpeechProvider::class, fakeSpeechProvider(new TrainingSpeechException('Daily audio limit reached.')));
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lessons[0]->id}/audio")
        ->assertStatus(503)
        ->assertJson(['message' => 'Daily audio limit reached.']);
});

test('the audio file route is not used with the browser provider', function () {
    $recruiter = trainingLearner();
    [, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)->get("/recruiter/training/lessons/{$lessons[0]->id}/audio")->assertNotFound();
});

test('with speech disabled no voices are offered and speech is refused', function () {
    $this->app->instance(TrainingSpeechProvider::class, new DisabledSpeechProvider);
    $recruiter = trainingLearner();
    [$course, $version, $lessons] = liveTrainingCourse(1);
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$lessons[0]->id}")
        ->assertInertia(fn ($page) => $page->where('audio.delivery', 'unavailable')->where('audio.voices', []));

    $this->actingAs($recruiter->user)->getJson("/recruiter/training/lessons/{$lessons[0]->id}/speech")->assertUnprocessable();
});

// I. Separation

test('training sits behind recruiter.access even for someone holding the training permissions', function () {
    $outsider = staffWith(Ability::ManageRecruiterTraining, Ability::AssignRecruiterTraining);

    $this->actingAs($outsider)->get('/recruiter/training')->assertForbidden();
    $this->actingAs($outsider)->get('/recruiter/training/manage')->assertForbidden();
    $this->actingAs($outsider)->post('/recruiter/training/assignments', [])->assertForbidden();
});

test('every training route is a recruiter route', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'recruiter.training.'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->uri())->toStartWith('recruiter/training')
            ->and($route->gatherMiddleware())->toContain('auth', 'internal', 'permission:recruiter.access');
    }
});

test('the recruiter dashboard adds a training panel and keeps tasks and activities', function () {
    $recruiter = trainingLearner();
    [, $version] = liveTrainingCourse();
    assignTraining($version, $recruiter);

    $this->actingAs($recruiter->user)
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('training.assigned', 1)
            ->where('training.not_started', 1)
            ->has('myTasks')
            ->has('todayActivities'));
});
