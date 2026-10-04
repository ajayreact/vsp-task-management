<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/*
| The OPT & STEM OPT Recruiter Process curriculum: ten modules and 98 lessons
| installed in the OPT Recruiter course's Version 2 draft, with flowcharts,
| scenarios and three linked quizzes. Version 1 and its assignments are never
| touched, and Version 2 stays a draft until it is reviewed and published.
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function curriculumPerson(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

function curriculumTrack(string $slug): TrainingTrack
{
    return TrainingTrack::query()->where('slug', $slug)->sole();
}

/**
 * The OPT Recruiter course as it is today: a published Version 1 assigned to
 * a recruiter, and a Version 2 draft copied from it. Then the curriculum and
 * quiz commands run, as they would on the server.
 *
 * @return array{lead: Employee, recruiter: Employee, course: TrainingCourse, v1: TrainingCourseVersion, v2: TrainingCourseVersion, assignment: TrainingAssignment, before: string}
 */
function installOptCurriculum(): array
{
    $lead = curriculumPerson(SystemRole::RecruiterLead);
    $recruiter = curriculumPerson(SystemRole::Recruiter);
    $plan = RecruiterTrainingContent::optTrack()[2];

    $course = TrainingCourse::factory()->inTrack(curriculumTrack(TrainingTrack::OPT_RECRUITER))->create([
        'title' => $plan['course'],
        'slug' => Str::slug($plan['course']),
    ]);
    $v1 = TrainingCourseVersion::factory()->forCourse($course, 1)->published()->create();
    $course->forceFill(['current_version_id' => $v1->id, 'status' => TrainingContentStatus::Published])->save();

    foreach (['Completing the Degree & Contacting the DSO', 'Offer Letter', 'Onboarding'] as $i => $title) {
        TrainingLesson::factory()->forVersion($v1, $i + 1)->create(['title' => $title, 'slug' => Str::slug($title), 'module' => 'Before OPT', 'body' => "{$title} body."]);
    }

    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $v2 = app(TrainingContentService::class)->createVersion($course->fresh(), $lead->user);
    $before = curriculumFingerprint($v1);

    test()->artisan('recruiter:training-opt-track', ['--as' => $lead->user->email])->assertSuccessful();
    test()->artisan('recruiter:training-opt-quizzes', ['--as' => $lead->user->email])->assertSuccessful();

    return ['lead' => $lead, 'recruiter' => $recruiter, 'course' => $course->fresh(), 'v1' => $v1->fresh(), 'v2' => $v2->fresh(), 'assignment' => $assignment->fresh(), 'before' => $before];
}

/**
 * Version 1, its lessons, assignments and completions.
 */
function curriculumFingerprint(TrainingCourseVersion $v1): string
{
    return hash('sha256', (string) json_encode([
        DB::table('ro_training_course_versions')->where('id', $v1->id)->get(),
        DB::table('ro_training_lessons')->where('course_version_id', $v1->id)->orderBy('id')->get(),
        DB::table('ro_training_assignments')->orderBy('id')->get(),
        DB::table('ro_training_lesson_completions')->orderBy('id')->get(),
    ]));
}

/**
 * What a manager does before publishing: approve the English and the
 * compliance review of every lesson.
 */
function approveCurriculum(TrainingCourseVersion $version, Employee $lead): void
{
    $ids = $version->lessons()->pluck('id');
    TrainingLessonContent::query()->whereIn('lesson_id', $ids)->update([
        'review_status' => TrainingContentReview::Approved->value,
        'reviewed_by_user_id' => $lead->user->id,
        'reviewed_at' => now(),
    ]);
    TrainingLesson::query()->whereIn('id', $ids)->update(['compliance_status' => TrainingComplianceStatus::Approved->value]);
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function curriculumSections(TrainingCourseVersion $version): array
{
    return $version->lessons()->with('contents')->get()
        ->flatMap(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English)?->sections ?? [])
        ->values()->all();
}

// 1-4. Content in the right place, Version 1 untouched

test('the curriculum is installed in the OPT Recruiter course draft with all modules and lessons', function () {
    ['course' => $course, 'v2' => $v2] = installOptCurriculum();
    $lessons = $v2->lessons()->with('contents')->orderBy('sort_order')->get();

    expect($course->track->slug)->toBe(TrainingTrack::OPT_RECRUITER)
        ->and(TrainingCourse::query()->count())->toBe(1)
        ->and(TrainingTrack::query()->count())->toBe(2)
        ->and($lessons)->toHaveCount(98)
        ->and($lessons->pluck('module')->unique()->values()->all())->toBe([
            'F-1 Student & OPT Basics',
            'Initial OPT Process',
            'OPT Employment & Onboarding',
            'STEM OPT Eligibility',
            'Form I-983',
            'STEM OPT Application',
            'STEM OPT Employment',
            'OPT vs STEM OPT',
            'Recruiter Responsibilities',
            'Recruiter Do\'s & Don\'ts',
        ])
        ->and($lessons->countBy('module')->values()->all())->toBe([7, 12, 15, 12, 10, 11, 16, 3, 4, 8])
        ->and($lessons->pluck('sort_order')->all())->toBe(range(1, 98));

    foreach ([
        'What Is F-1 Status?', 'Who Is the DSO?', 'The Initial OPT Process at a Glance', 'Offer Letter vs Immigration Authorization',
        'The E-Verify Requirement', 'The I-983 Workflow', 'The 6-Month Validation', 'The 12-Month Evaluation', 'The 18-Month Validation',
        'The 24-Month Final Evaluation', 'OPT vs STEM OPT Comparison Table', 'The Recruiter Internal Workflow', '"If I Pay, Can You Guarantee My OPT?"',
    ] as $title) {
        expect($lessons->pluck('title'))->toContain($title);
    }

    expect($lessons->every(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English)?->review_status === TrainingContentReview::NeedsReview))->toBeTrue()
        ->and($lessons->every(fn (TrainingLesson $lesson) => $lesson->compliance_status === TrainingComplianceStatus::Pending && filled($lesson->compliance_note)))->toBeTrue();
});

test('existing draft lessons are carried over by title, keeping their records', function () {
    ['v2' => $v2] = installOptCurriculum();
    $lessons = $v2->lessons()->get()->keyBy('title');

    expect($lessons->keys())->not->toContain('Completing the Degree & Contacting the DSO')
        ->and($lessons['Student Completes a Qualifying Degree']->module)->toBe('Initial OPT Process')
        ->and($lessons['The Employment Agreement']->module)->toBe('OPT Employment & Onboarding')
        ->and($lessons['Employer Onboarding']->module)->toBe('OPT Employment & Onboarding')
        ->and(TrainingLesson::query()->where('course_version_id', $v2->id)->whereIn('title', ['Offer Letter', 'Onboarding'])->count())->toBe(0);
});

test('the lessons include seven flowcharts, six scenarios and the full comparison table', function () {
    ['v2' => $v2] = installOptCurriculum();
    $sections = collect(curriculumSections($v2));
    $flows = $sections->where('kind', 'flow');
    $scenarios = $sections->where('kind', 'scenario');

    expect($flows)->toHaveCount(7)
        ->and($scenarios)->toHaveCount(6);

    foreach ($flows as $flow) {
        expect(preg_match_all('/^\d+\. \*\*[^*]+:\*\* .+$/m', $flow['body']))->toBeGreaterThanOrEqual(8);
    }

    foreach ($scenarios as $scenario) {
        foreach (['**Situation:**', '**Correct response:**', '**Incorrect response:**', '**Why:**', '**Escalation:**'] as $part) {
            expect($scenario['body'])->toContain($part);
        }
    }

    $internal = $flows->first(fn (array $flow) => $flow['heading'] === 'Recruiter Internal Workflow');
    foreach (['Recruiter', 'HR', 'Compliance', 'Candidate', 'DSO', 'USCIS', 'Project Manager', 'Finance / Payroll'] as $owner) {
        expect($internal['body'])->toContain("**{$owner}:**");
    }

    $table = $sections->first(fn (array $section) => $section['heading'] === 'OPT vs STEM OPT')['body'];
    foreach (['Duration', 'DSO involvement', 'USCIS application', 'I-20', 'I-765', 'EAD', 'Employer requirements', 'E-Verify', 'I-983', 'Training plan', 'Supervision', 'Reporting', 'Evaluations'] as $row) {
        expect($table)->toContain("| {$row} |");
    }

    expect($sections->pluck('body')->implode(' '))->toContain('An offer letter itself does NOT create immigration work authorization.')
        ->and(mb_strtolower($sections->pluck('body')->implode(' ')))->not->toContain('bench sales');
});

test('version 1 and its assignments are untouched and version 2 stays a draft', function () {
    ['course' => $course, 'v1' => $v1, 'v2' => $v2, 'assignment' => $assignment, 'before' => $before] = installOptCurriculum();

    expect(curriculumFingerprint($v1))->toBe($before)
        ->and($v1->status)->toBe(TrainingContentStatus::Published)
        ->and($v2->status)->toBe(TrainingContentStatus::Draft)
        ->and($v2->id)->not->toBe($v1->id)
        ->and($course->current_version_id)->toBe($v1->id)
        ->and($v1->lessons()->count())->toBe(3)
        ->and($assignment->course_version_id)->toBe($v1->id);
});

test('version 2 is published without waiting for every lesson to be reviewed', function () {
    ['lead' => $lead, 'v2' => $v2] = installOptCurriculum();

    app(TrainingContentService::class)->publishVersion($v2, $lead->user);
    expect($v2->fresh()->status)->toBe(TrainingContentStatus::Published);
});

// 10. Quizzes

test('three published quizzes with explained questions are linked to the draft', function () {
    ['v2' => $v2, 'v1' => $v1] = installOptCurriculum();
    $quizzes = Assessment::query()->with('currentVersion.questions.options')->get();
    $questions = $quizzes->flatMap(fn (Assessment $quiz) => $quiz->currentVersion->questions);

    expect($quizzes)->toHaveCount(3)
        ->and($v2->assessmentVersions()->count())->toBe(3)
        ->and($v1->assessmentVersions()->count())->toBe(0)
        ->and($questions)->toHaveCount(45)
        ->and($questions->every(fn (AssessmentQuestion $question) => filled($question->explanation) && filled($question->category)))->toBeTrue()
        ->and($questions->pluck('type')->map->value->unique()->sort()->values()->all())->toBe(['multiple_choice', 'single_choice', 'true_false']);

    foreach (['DSO', 'SEVIS', 'I-20', 'I-765', 'EAD', 'OPT', 'STEM OPT', 'I-983', 'E-Verify', 'Employer responsibilities', 'Recruiter responsibilities', 'Project manager responsibilities', 'Reporting', 'Evaluations', 'Offer letter vs authorization', 'Scenario'] as $category) {
        expect($questions->pluck('category'))->toContain($category);
    }
});

test('the install commands are safe to run again and need a known user', function () {
    ['v2' => $v2, 'lead' => $lead] = installOptCurriculum();

    $this->artisan('recruiter:training-opt-quizzes')->assertFailed();
    $this->artisan('recruiter:training-opt-quizzes', ['--as' => 'nobody@example.test'])->assertFailed();
    $this->artisan('recruiter:training-opt-quizzes', ['--dry-run' => true])->expectsOutputToContain('Dry run, nothing saved.')->assertSuccessful();
    $this->artisan('recruiter:training-opt-track', ['--as' => $lead->user->email])->assertSuccessful();
    $this->artisan('recruiter:training-opt-quizzes', ['--as' => $lead->user->email])->assertSuccessful();

    expect($v2->lessons()->count())->toBe(98)
        ->and(Assessment::query()->count())->toBe(3)
        ->and($v2->assessmentVersions()->count())->toBe(3);
});

// 5-11. Learning, once a manager has reviewed and published Version 2

test('after review and publishing, an assigned recruiter learns the curriculum, with explicit completion and Indian English audio', function () {
    ['lead' => $lead, 'course' => $course, 'v2' => $v2] = installOptCurriculum();
    $learner = curriculumPerson(SystemRole::Recruiter);
    approveCurriculum($v2, $lead);
    app(TrainingContentService::class)->publishVersion($v2, $lead->user);

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['track' => TrainingTrack::OPT_RECRUITER, 'course_id' => $course->id, 'mode' => 'individual', 'employee_ids' => [$learner->id]])
        ->assertSessionHasNoErrors();

    $assignment = TrainingAssignment::query()->where('employee_id', $learner->id)->sole();
    expect($assignment->course_version_id)->toBe($v2->id)
        ->and(AssessmentAssignment::query()->where('employee_id', $learner->id)->count())->toBe(3);

    $lesson = $v2->lessons()->where('title', 'The Initial OPT Process at a Glance')->sole();
    $base = "/recruiter/training/courses/{$course->id}/lessons/{$lesson->id}";

    $this->actingAs($learner->user)
        ->get("/recruiter/training/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/course')
            ->has('lessons', 98)
            ->has('modules', 10)
            ->where('modules.0.title', 'F-1 Student & OPT Basics')
            ->where('lessons.7.title', 'The Initial OPT Process at a Glance')
            ->where('lessons.7.languages.0.sections.1.kind', 'flow')
            ->has('quizzes', 3)
            ->where('progress.completed', 0));

    $this->actingAs($learner->user)
        ->get($base)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('audio.voices.0.key', 'indian_english')
            ->where('audio.voices.0.locale', 'en-IN')
            ->where('audio.has_text', true));

    $this->actingAs($learner->user)
        ->getJson("/recruiter/training/lessons/{$lesson->id}/speech")
        ->assertOk()
        ->assertJsonPath('voice.key', 'indian_english')
        ->assertJsonPath('has_text', true);

    $this->actingAs($learner->user)->postJson("{$base}/progress", ['audio_seconds' => 120])->assertOk();

    expect(TrainingLessonCompletion::query()->where('lesson_id', $lesson->id)->sole()->completed_at)->toBeNull();

    $this->actingAs($learner->user)->post("{$base}/complete")->assertSessionHasNoErrors();

    expect(TrainingLessonCompletion::query()->where('lesson_id', $lesson->id)->sole()->completed_at)->not->toBeNull()
        ->and($assignment->fresh()->isCompleted())->toBeFalse();

    $this->actingAs($learner->user)
        ->get("/recruiter/training/courses/{$course->id}")
        ->assertInertia(fn ($page) => $page->where('progress.completed', 1));

    $quiz = AssessmentAssignment::query()->where('employee_id', $learner->id)->with('version.questions.options')->firstOrFail();
    $this->actingAs($learner->user)->post("/recruiter/assessments/{$quiz->id}/start")->assertSessionHasNoErrors();
    $attempt = AssessmentAttempt::query()->where('assignment_id', $quiz->id)->sole();
    $answers = $quiz->version->questions->mapWithKeys(fn (AssessmentQuestion $question) => [$question->id => ['option_ids' => $question->correctOptionIds()]])->all();

    $this->actingAs($learner->user)->post("/recruiter/assessments/attempts/{$attempt->id}/submit", ['answers' => $answers])->assertSessionHasNoErrors();

    expect($attempt->fresh()->result)->toBe(AssessmentResult::Passed)
        ->and((float) $attempt->fresh()->percentage)->toBe(100.0);
});

test('a recruiter on version 1 keeps learning version 1 while version 2 is a draft', function () {
    ['recruiter' => $recruiter, 'course' => $course, 'v1' => $v1, 'v2' => $v2] = installOptCurriculum();
    $v1Lesson = $v1->lessons()->orderBy('sort_order')->first();
    $draftLesson = $v2->lessons()->orderBy('sort_order')->first();

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('lessons', 3)->where('lessons.0.id', $v1Lesson->id));

    $this->actingAs($recruiter->user)->get("/recruiter/training/courses/{$course->id}/lessons/{$draftLesson->id}")->assertNotFound();
    $this->actingAs($recruiter->user)->getJson("/recruiter/training/lessons/{$draftLesson->id}/speech")->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/training/courses/{$course->id}/lessons/{$v1Lesson->id}/complete")->assertSessionHasNoErrors();

    expect(TrainingLessonCompletion::query()->where('lesson_id', $v1Lesson->id)->sole()->completed_at)->not->toBeNull();
});

// 12-13. Tracks stay separate

test('the curriculum stays in OPT Recruiter and never appears under Bench Sales', function () {
    ['lead' => $lead, 'course' => $course, 'recruiter' => $recruiter] = installOptCurriculum();

    expect(curriculumTrack(TrainingTrack::BENCH_SALES_RECRUITER)->courses()->count())->toBe(0);

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/opt-recruiter')
        ->assertInertia(fn ($page) => $page->has('courses', 1)->where('courses.0.id', $course->id)->missing('courses.0.draft_version'));

    $this->actingAs($lead->user)
        ->get('/recruiter/training/tracks/bench-sales-recruiter')
        ->assertInertia(fn ($page) => $page->where('courses', [])->where('assignments', []));

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['track' => TrainingTrack::BENCH_SALES_RECRUITER, 'course_id' => $course->id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]])
        ->assertSessionHasErrors('course_id');

    expect(TrainingAssignment::query()->count())->toBe(1);
});
