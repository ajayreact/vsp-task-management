<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentContentService;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function quizTaker(SystemRole $role = SystemRole::Recruiter): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A published quiz worth 4 points: single choice (1, answer A), multiple
 * choice (2, answers A and C), true/false (1, answer True). With a short
 * answer (2 points) it is worth 6.
 *
 * @param  array<string, mixed>  $settings
 */
function publishedQuiz(array $settings = [], bool $withShortAnswer = false): AssessmentVersion
{
    $version = AssessmentVersion::factory()->published()->create($settings);
    AssessmentQuestion::factory()->forVersion($version, 1)->singleChoice()->create(['points' => 1, 'explanation' => 'OPT is Optional Practical Training.']);
    AssessmentQuestion::factory()->forVersion($version, 2)->multipleChoice()->create(['points' => 2]);
    AssessmentQuestion::factory()->forVersion($version, 3)->trueFalse(true)->create(['points' => 1]);

    if ($withShortAnswer) {
        AssessmentQuestion::factory()->forVersion($version, 4)->shortAnswer()->create(['points' => 2]);
    }

    return $version->fresh();
}

function assignQuiz(AssessmentVersion $version, Employee $recruiter): AssessmentAssignment
{
    return AssessmentAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();
}

function startQuiz(Employee $recruiter, AssessmentAssignment $assignment): AssessmentAttempt
{
    test()->actingAs($recruiter->user)->post("/recruiter/assessments/{$assignment->id}/start")->assertSessionHasNoErrors()->assertRedirect();

    return AssessmentAttempt::query()->where('assignment_id', $assignment->id)->latest('id')->firstOrFail();
}

/**
 * Question ids in sort order with their correct and wrong option ids.
 *
 * @return list<array{id: int, correct: list<int>, wrong: list<int>}>
 */
function quizKey(AssessmentVersion $version): array
{
    return $version->questions()->with('options')->get()->map(fn (AssessmentQuestion $question) => [
        'id' => $question->id,
        'correct' => $question->correctOptionIds(),
        'wrong' => $question->options->pluck('id')->diff($question->correctOptionIds())->values()->all(),
    ])->all();
}

/**
 * @return array<int, array{option_ids?: list<int>, text?: string}>
 */
function perfectAnswers(AssessmentVersion $version): array
{
    $answers = [];

    foreach (quizKey($version) as $question) {
        $answers[$question['id']] = $question['correct'] === [] && $question['wrong'] === []
            ? ['text' => 'The cap-gap extension bridges status until the H-1B starts.']
            : ['option_ids' => $question['correct']];
    }

    return $answers;
}

function submitQuiz(Employee $recruiter, AssessmentAttempt $attempt, array $answers): AssessmentAttempt
{
    test()->actingAs($recruiter->user)
        ->post("/recruiter/assessments/attempts/{$attempt->id}/submit", ['answers' => $answers])
        ->assertSessionHasNoErrors();

    return $attempt->fresh();
}

// Assignment

test('a lead assigns a published quiz to one recruiter, pinned to its live version, and only the recruiter is notified', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $version = publishedQuiz();
    $due = today()->addWeek()->toDateString();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/assignments', [
            'assessment_id' => $version->assessment_id,
            'mode' => 'individual',
            'employee_ids' => [$recruiter->id],
            'due_at' => $due,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Assessment assigned to 1 recruiter.');

    $assignment = AssessmentAssignment::query()->sole();

    expect($assignment->assessment_version_id)->toBe($version->id)
        ->and($assignment->employee_id)->toBe($recruiter->id)
        ->and($assignment->assigned_by_user_id)->toBe($lead->user->id)
        ->and($assignment->status)->toBe(AssessmentAssignmentStatus::Assigned)
        ->and($assignment->due_at->toDateString())->toBe($due);

    $notification = $recruiter->user->notifications()->sole();

    expect($notification->data['event'])->toBe('recruiter.assessment.assigned')
        ->and($notification->data['url'])->toBe("/recruiter/assessments/{$assignment->id}")
        ->and($notification->data['recruiter_assessment_assignment_id'])->toBe($assignment->id)
        ->and($lead->user->notifications()->count())->toBe(0);

    expect(Activity::query()->where('log_name', 'recruiter-assessments')->where('subject_type', $assignment->getMorphClass())->where('event', 'created')->exists())->toBeTrue();
});

test('team mode assigns every active recruiter and skips anyone who already has the quiz open', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $first = quizTaker();
    $second = quizTaker();
    $inactive = quizTaker();
    $inactive->user->forceFill(['is_active' => false])->save();
    $notRecruiter = Employee::factory()->create();
    $version = publishedQuiz();
    assignQuiz($version, $first);

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/assignments', ['assessment_id' => $version->assessment_id, 'mode' => 'team'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Skipped:'));

    $assigned = AssessmentAssignment::query()->pluck('employee_id');

    expect($assigned->filter(fn (int $id) => $id === $first->id))->toHaveCount(1)
        ->and($assigned)->toContain($second->id)
        ->and($assigned)->not->toContain($inactive->id)
        ->and($assigned)->not->toContain($notRecruiter->id);
});

test('only published quizzes can be assigned, and only to recruiters', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $draft = AssessmentVersion::factory()->create();
    $archived = publishedQuiz();
    app(AssessmentContentService::class)->archiveAssessment($archived->assessment, $lead->user);

    foreach ([$draft, $archived] as $version) {
        $this->actingAs($lead->user)
            ->post('/recruiter/assessments/assignments', ['assessment_id' => $version->assessment_id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]])
            ->assertSessionHasErrors();
    }

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/assignments', ['assessment_id' => publishedQuiz()->assessment_id, 'mode' => 'individual', 'employee_ids' => [Employee::factory()->create()->id]])
        ->assertSessionHasErrors('employee_ids.0');

    expect(AssessmentAssignment::query()->count())->toBe(0);
});

test('recruiters cannot assign quizzes', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();

    $this->actingAs($recruiter->user)
        ->post('/recruiter/assessments/assignments', ['assessment_id' => $version->assessment_id, 'mode' => 'team'])
        ->assertForbidden();

    expect(AssessmentAssignment::query()->count())->toBe(0);
});

test('an assignment without attempts can be withdrawn; one with attempts cannot', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $version = publishedQuiz();
    $unstarted = assignQuiz($version, quizTaker());
    $recruiter = quizTaker();
    $started = assignQuiz($version, $recruiter);
    startQuiz($recruiter, $started);

    $this->actingAs($lead->user)->delete("/recruiter/assessments/assignments/{$unstarted->id}")->assertSessionHasNoErrors();
    $this->actingAs($lead->user)->delete("/recruiter/assessments/assignments/{$started->id}")->assertForbidden();

    expect(AssessmentAssignment::query()->find($unstarted->id))->toBeNull()
        ->and($started->fresh())->not->toBeNull();
});

// Recruiter experience and isolation

test('recruiters see only their own quizzes', function () {
    $recruiter = quizTaker();
    $other = quizTaker();
    $version = publishedQuiz();
    $mine = assignQuiz($version, $recruiter);
    $theirs = assignQuiz($version, $other);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/assessments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/assessments/index')
            ->has('assignments', 1)
            ->where('assignments.0.id', $mine->id)
            ->where('counts.assigned', 1));

    $this->actingAs($recruiter->user)->get("/recruiter/assessments/{$theirs->id}")->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/assessments/{$theirs->id}/start")->assertForbidden();

    expect(AssessmentAttempt::query()->count())->toBe(0);
});

test('starting creates one attempt; starting again returns the same open attempt', function () {
    $recruiter = quizTaker();
    $assignment = assignQuiz(publishedQuiz(['time_limit_minutes' => 20]), $recruiter);

    $attempt = startQuiz($recruiter, $assignment);
    $again = startQuiz($recruiter, $assignment);

    expect($again->id)->toBe($attempt->id)
        ->and(AssessmentAttempt::query()->count())->toBe(1)
        ->and($attempt->status)->toBe(AttemptStatus::InProgress)
        ->and($attempt->attempt_number)->toBe(1)
        ->and($attempt->total_points)->toBe(4)
        ->and($attempt->expires_at->equalTo($attempt->started_at->copy()->addMinutes(20)))->toBeTrue()
        ->and($assignment->fresh()->status)->toBe(AssessmentAssignmentStatus::InProgress);
});

test('the attempt page never contains the answer key or explanations', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['allow_review' => true], true);
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    $response = $this->actingAs($recruiter->user)
        ->get("/recruiter/assessments/attempts/{$attempt->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/assessments/attempt')->has('questions', 4));

    $json = json_encode($response->viewData('page')['props']);

    expect($json)->not->toContain('is_correct')
        ->and($json)->not->toContain('correct_option')
        ->and($json)->not->toContain('explanation')
        ->and($json)->not->toContain('OPT is Optional Practical Training.');
});

test('another recruiter, and even a lead, cannot open, save or submit a recruiter\'s attempt', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    foreach ([quizTaker(), quizTaker(SystemRole::RecruiterLead)] as $intruder) {
        $this->actingAs($intruder->user)->get("/recruiter/assessments/attempts/{$attempt->id}")->assertForbidden();
        $this->actingAs($intruder->user)->putJson("/recruiter/assessments/attempts/{$attempt->id}/answers", ['answers' => perfectAnswers($version)])->assertForbidden();
        $this->actingAs($intruder->user)->post("/recruiter/assessments/attempts/{$attempt->id}/submit", ['answers' => perfectAnswers($version)])->assertForbidden();
    }

    expect($attempt->fresh()->status)->toBe(AttemptStatus::InProgress)
        ->and(AssessmentAnswer::query()->count())->toBe(0);
});

// Scoring

test('a fully correct submission is scored on the server as passed', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    $assignment = assignQuiz($version, $recruiter);
    $attempt = startQuiz($recruiter, $assignment);

    $attempt = submitQuiz($recruiter, $attempt, perfectAnswers($version));

    expect($attempt->status)->toBe(AttemptStatus::Submitted)
        ->and($attempt->awarded_points)->toBe(4)
        ->and($attempt->total_points)->toBe(4)
        ->and((float) $attempt->percentage)->toBe(100.0)
        ->and($attempt->result)->toBe(AssessmentResult::Passed)
        ->and($attempt->active_assignment_id)->toBeNull()
        ->and($assignment->fresh()->status)->toBe(AssessmentAssignmentStatus::Completed)
        ->and($assignment->fresh()->result)->toBe(AssessmentResult::Passed);
});

test('each question type is scored all-or-nothing, and a browser-sent score is ignored', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    [$single, $multiple, $trueFalse] = quizKey($version);
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    $this->actingAs($recruiter->user)
        ->post("/recruiter/assessments/attempts/{$attempt->id}/submit", [
            'answers' => [
                $single['id'] => ['option_ids' => [$single['correct'][0]]],
                $multiple['id'] => ['option_ids' => [$multiple['correct'][0]]],
                $trueFalse['id'] => ['option_ids' => [$trueFalse['wrong'][0]]],
            ],
            'score' => 100,
            'percentage' => 100,
            'result' => 'passed',
        ])
        ->assertSessionHasNoErrors();

    $attempt->refresh();
    $points = AssessmentAnswer::query()->where('attempt_id', $attempt->id)->pluck('awarded_points', 'question_id');

    expect($points[$single['id']])->toBe(1)
        ->and($points[$multiple['id']])->toBe(0)
        ->and($points[$trueFalse['id']])->toBe(0)
        ->and($attempt->awarded_points)->toBe(1)
        ->and((float) $attempt->percentage)->toBe(25.0)
        ->and($attempt->result)->toBe(AssessmentResult::Failed);
});

test('multiple choice scores only an exact match of the correct set', function (string $pick, int $expected) {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    $multiple = quizKey($version)[1];
    $selection = match ($pick) {
        'exact' => $multiple['correct'],
        'exact, reordered' => array_reverse($multiple['correct']),
        'subset' => [$multiple['correct'][0]],
        'superset' => [...$multiple['correct'], $multiple['wrong'][0]],
        'wrong' => $multiple['wrong'],
    };
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    submitQuiz($recruiter, $attempt, [$multiple['id'] => ['option_ids' => $selection]]);

    expect(AssessmentAnswer::query()->where('question_id', $multiple['id'])->sole()->awarded_points)->toBe($expected);
})->with([
    'exact' => ['exact', 2],
    'exact, reordered' => ['exact, reordered', 2],
    'subset' => ['subset', 0],
    'superset' => ['superset', 0],
    'wrong' => ['wrong', 0],
]);

test('the pass mark is applied to the server-calculated percentage', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['passing_percentage' => 75, 'max_attempts' => 3]);
    [$single, $multiple, $trueFalse] = quizKey($version);
    $assignment = assignQuiz($version, $recruiter);

    $attempt = submitQuiz($recruiter, startQuiz($recruiter, $assignment), [
        $single['id'] => ['option_ids' => $single['correct']],
        $multiple['id'] => ['option_ids' => $multiple['correct']],
        $trueFalse['id'] => ['option_ids' => $trueFalse['wrong']],
    ]);

    expect((float) $attempt->percentage)->toBe(75.0)
        ->and($attempt->result)->toBe(AssessmentResult::Passed);
});

test('answers must belong to the attempt\'s questions, and single-choice questions take one option', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    [$single, $multiple] = quizKey($version);
    $foreign = AssessmentQuestion::factory()->singleChoice()->create();
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));
    $url = "/recruiter/assessments/attempts/{$attempt->id}/answers";

    $this->actingAs($recruiter->user);
    $this->putJson($url, ['answers' => [$foreign->id => ['option_ids' => $foreign->options()->pluck('id')->take(1)->all()]]])->assertStatus(422);
    $this->putJson($url, ['answers' => [$single['id'] => ['option_ids' => $multiple['correct']]]])->assertStatus(422);
    $this->putJson($url, ['answers' => [$single['id'] => ['option_ids' => [...$single['correct'], ...$single['wrong']]]]])->assertStatus(422);
    $this->putJson($url, ['answers' => [$single['id'] => ['option_ids' => $single['correct']]]])->assertOk()->assertJsonStructure(['saved_at', 'seconds_remaining']);

    expect(AssessmentAnswer::query()->where('attempt_id', $attempt->id)->count())->toBe(1);
});

test('a submitted attempt cannot be changed or submitted again', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    [$single] = quizKey($version);
    $wrong = [$single['wrong'][0]];
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), [$single['id'] => ['option_ids' => $wrong]]);

    $this->actingAs($recruiter->user)
        ->putJson("/recruiter/assessments/attempts/{$attempt->id}/answers", ['answers' => perfectAnswers($version)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('attempt');

    $this->actingAs($recruiter->user)
        ->post("/recruiter/assessments/attempts/{$attempt->id}/submit", ['answers' => perfectAnswers($version)])
        ->assertSessionHasErrors('attempt');

    expect($attempt->fresh()->awarded_points)->toBe(0)
        ->and(AssessmentAnswer::query()->where('attempt_id', $attempt->id)->sole()->selectedOptionIds())->toBe($wrong);
});

// Attempt and time limits

test('the maximum number of attempts is enforced', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['max_attempts' => 2]);
    $assignment = assignQuiz($version, $recruiter);

    submitQuiz($recruiter, startQuiz($recruiter, $assignment), []);
    $second = submitQuiz($recruiter, startQuiz($recruiter, $assignment), []);

    expect($second->attempt_number)->toBe(2)
        ->and($assignment->fresh()->status)->toBe(AssessmentAssignmentStatus::Completed)
        ->and($assignment->fresh()->result)->toBe(AssessmentResult::Failed);

    $this->actingAs($recruiter->user)
        ->post("/recruiter/assessments/{$assignment->id}/start")
        ->assertSessionHasErrors(['attempt' => 'You have used all 2 attempts.']);

    expect(AssessmentAttempt::query()->count())->toBe(2);
});

test('after passing, no further attempt can be started', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['max_attempts' => 3]);
    $assignment = assignQuiz($version, $recruiter);
    submitQuiz($recruiter, startQuiz($recruiter, $assignment), perfectAnswers($version));

    $this->actingAs($recruiter->user)
        ->post("/recruiter/assessments/{$assignment->id}/start")
        ->assertSessionHasErrors(['attempt' => 'You have already passed this assessment.']);
});

test('the time limit is enforced on the server: late saves are refused and late submissions keep only the answers saved in time', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['time_limit_minutes' => 10]);
    [$single, $multiple, $trueFalse] = quizKey($version);
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    $this->actingAs($recruiter->user)
        ->putJson("/recruiter/assessments/attempts/{$attempt->id}/answers", ['answers' => [$single['id'] => ['option_ids' => $single['correct']]]])
        ->assertOk();

    $this->travel(11)->minutes();

    $this->actingAs($recruiter->user)
        ->post("/recruiter/assessments/attempts/{$attempt->id}/submit", ['answers' => perfectAnswers($version)])
        ->assertSessionHasNoErrors();

    $attempt->refresh();

    expect($attempt->status)->toBe(AttemptStatus::Submitted)
        ->and($attempt->auto_submitted)->toBeTrue()
        ->and($attempt->awarded_points)->toBe(1)
        ->and(AssessmentAnswer::query()->where('attempt_id', $attempt->id)->whereIn('question_id', [$multiple['id'], $trueFalse['id']])->count())->toBe(0);
});

test('saving after the deadline is refused and submits the attempt', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['time_limit_minutes' => 5]);
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));

    $this->travel(6)->minutes();

    $this->actingAs($recruiter->user)
        ->putJson("/recruiter/assessments/attempts/{$attempt->id}/answers", ['answers' => perfectAnswers($version)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('attempt');

    expect($attempt->fresh()->isInProgress())->toBeFalse()
        ->and(AssessmentAnswer::query()->count())->toBe(0);
});

test('an expired attempt is finalized when the recruiter next opens it', function () {
    $recruiter = quizTaker();
    $attempt = startQuiz($recruiter, assignQuiz(publishedQuiz(['time_limit_minutes' => 5]), $recruiter));

    $this->travel(10)->minutes();

    $this->actingAs($recruiter->user)
        ->get("/recruiter/assessments/attempts/{$attempt->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/assessments/result'));

    expect($attempt->fresh()->isInProgress())->toBeFalse();
});

// Results visibility

test('with results hidden, recruiters see neither score nor pass/fail', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz(['show_result' => false]);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), perfectAnswers($version));

    $this->actingAs($recruiter->user)
        ->get("/recruiter/assessments/attempts/{$attempt->id}")
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/assessments/result')
            ->where('result.show_score', false)
            ->where('result.percentage', null)
            ->where('result.result', null)
            ->where('result.questions', []));
});

test('answer review shows the key only after submission and only when allowed', function (bool $allowReview) {
    $recruiter = quizTaker();
    $version = publishedQuiz(['allow_review' => $allowReview]);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), perfectAnswers($version));

    $response = $this->actingAs($recruiter->user)->get("/recruiter/assessments/attempts/{$attempt->id}")->assertOk();
    $result = $response->viewData('page')['props']['result'];

    expect($result['show_review'])->toBe($allowReview)
        ->and(count($result['questions']))->toBe($allowReview ? 3 : 0);

    if ($allowReview) {
        expect($result['questions'][0]['options'][0]['is_correct'])->toBeTrue()
            ->and($result['questions'][0]['explanation'])->toBe('OPT is Optional Practical Training.');
    }
})->with(['review allowed' => true, 'review not allowed' => false]);

// Short answers and review

test('short answers leave the result pending until a reviewer scores them, then the result is final and the recruiter is notified', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $version = publishedQuiz([], true);
    $assignment = assignQuiz($version, $recruiter);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, $assignment), perfectAnswers($version));

    expect($attempt->result)->toBe(AssessmentResult::PendingReview)
        ->and($attempt->percentage)->toBeNull()
        ->and($assignment->fresh()->result)->toBe(AssessmentResult::PendingReview);

    $short = AssessmentAnswer::query()->where('attempt_id', $attempt->id)->where('needs_review', true)->sole();

    expect($short->awarded_points)->toBeNull()
        ->and($short->is_correct)->toBeNull();

    $this->actingAs($lead->user)
        ->get('/recruiter/assessments/results')
        ->assertInertia(fn ($page) => $page->where('awaitingReview', 1));

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/results/attempts/{$attempt->id}/answers/{$short->id}/review", ['points' => 1, 'feedback' => 'Mention the October 1 start date.'])
        ->assertSessionHasNoErrors();

    $attempt->refresh();
    $short->refresh();

    expect($short->awarded_points)->toBe(1)
        ->and($short->reviewed_by_user_id)->toBe($lead->user->id)
        ->and($short->reviewed_at)->not->toBeNull()
        ->and($short->reviewer_feedback)->toBe('Mention the October 1 start date.')
        ->and($attempt->awarded_points)->toBe(5)
        ->and((float) $attempt->percentage)->toBe(83.33)
        ->and($attempt->result)->toBe(AssessmentResult::Passed)
        ->and($assignment->fresh()->result)->toBe(AssessmentResult::Passed);

    $notification = $recruiter->user->notifications()->where('data->event', 'recruiter.assessment.result')->sole();

    expect($notification->data['recruiter_assessment_attempt_id'])->toBe($attempt->id)
        ->and($lead->user->notifications()->count())->toBe(0)
        ->and(Activity::query()->where('log_name', 'recruiter-assessments')->where('event', 'reviewed')->count())->toBe(1);
});

test('a short answer can be re-scored, and the final score is recalculated', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $version = publishedQuiz(['passing_percentage' => 90], true);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), perfectAnswers($version));
    $short = AssessmentAnswer::query()->where('attempt_id', $attempt->id)->where('needs_review', true)->sole();
    $url = "/recruiter/assessments/results/attempts/{$attempt->id}/answers/{$short->id}/review";

    $this->actingAs($lead->user)->post($url, ['points' => 0])->assertSessionHasNoErrors();
    expect($attempt->fresh()->result)->toBe(AssessmentResult::Failed);

    $this->actingAs($lead->user)->post($url, ['points' => 2])->assertSessionHasNoErrors();
    expect($attempt->fresh()->awarded_points)->toBe(6)
        ->and($attempt->fresh()->result)->toBe(AssessmentResult::Passed);
});

test('review is limited to reviewers, within the question\'s points, on submitted attempts that are not their own', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $version = publishedQuiz([], true);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), perfectAnswers($version));
    $short = AssessmentAnswer::query()->where('attempt_id', $attempt->id)->where('needs_review', true)->sole();
    $url = "/recruiter/assessments/results/attempts/{$attempt->id}/answers/{$short->id}/review";

    $this->actingAs($recruiter->user)->get('/recruiter/assessments/results')->assertForbidden();
    $this->actingAs($recruiter->user)->get("/recruiter/assessments/results/attempts/{$attempt->id}")->assertForbidden();
    $this->actingAs($recruiter->user)->post($url, ['points' => 2])->assertForbidden();

    $this->actingAs($lead->user)->post($url, ['points' => 3])->assertSessionHasErrors('points');

    $otherAttempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz(publishedQuiz([], true), $recruiter)), []);
    $this->actingAs($lead->user)->post("/recruiter/assessments/results/attempts/{$otherAttempt->id}/answers/{$short->id}/review", ['points' => 1])->assertNotFound();

    $ownAssignment = assignQuiz($version, $lead);
    $own = submitQuiz($lead, startQuiz($lead, $ownAssignment), perfectAnswers($version));
    $ownShort = AssessmentAnswer::query()->where('attempt_id', $own->id)->where('needs_review', true)->sole();
    $this->actingAs($lead->user)->post("/recruiter/assessments/results/attempts/{$own->id}/answers/{$ownShort->id}/review", ['points' => 2])->assertForbidden();

    $inProgress = startQuiz($recruiter, assignQuiz(publishedQuiz([], true), $recruiter));
    $this->actingAs($lead->user)->get("/recruiter/assessments/results/attempts/{$inProgress->id}")->assertOk();

    expect($short->fresh()->reviewed_at)->toBeNull()
        ->and($ownShort->fresh()->reviewed_at)->toBeNull();
});

test('the reviewer result page shows the answers and the key', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $version = publishedQuiz(['show_result' => false, 'allow_review' => false], true);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, assignQuiz($version, $recruiter)), perfectAnswers($version));

    $this->actingAs($lead->user)
        ->get("/recruiter/assessments/results/attempts/{$attempt->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/assessments/results/show')
            ->where('canReview', true)
            ->where('result.show_score', true)
            ->has('result.questions', 4)
            ->where('result.questions.3.answer.awaiting_review', true));
});

// History

test('publishing a new version leaves historical attempts and assignments on the version they used', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $v1 = publishedQuiz(['allow_review' => true]);
    $assignment = assignQuiz($v1, $recruiter);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, $assignment), perfectAnswers($v1));
    $originalPrompt = $v1->questions()->first()->prompt;

    $content = app(AssessmentContentService::class);
    $v2 = $content->createVersion($v1->assessment, $lead->user);
    $v2->questions()->first()->forceFill(['prompt' => 'A rewritten question'])->save();
    $content->publishVersion($v2, $lead->user);

    expect($assignment->fresh()->assessment_version_id)->toBe($v1->id)
        ->and($attempt->fresh()->assessment_version_id)->toBe($v1->id)
        ->and($attempt->fresh()->awarded_points)->toBe(4);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/assessments/attempts/{$attempt->id}")
        ->assertInertia(fn ($page) => $page->where('result.questions.0.prompt', $originalPrompt));
});

// Training integration

test('a quiz linked to a training version is assigned with the training and withdrawn with it', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $quiz = publishedQuiz();
    $training = TrainingCourseVersion::factory()->create();
    TrainingLesson::factory()->forVersion($training, 1)->create(['body' => 'Lesson text.']);

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$training->id}/quizzes", ['assessment_version_id' => $quiz->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$training->id}/quizzes", ['assessment_version_id' => $quiz->id])
        ->assertSessionHasErrors('assessment_version_id');

    app(TrainingContentService::class)->publishVersion($training, $lead->user);

    $this->actingAs($lead->user)
        ->post('/recruiter/training/assignments', ['track' => 'unassigned', 'course_id' => $training->course_id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]])
        ->assertSessionHasNoErrors();

    $trainingAssignment = TrainingAssignment::query()->sole();
    $quizAssignment = AssessmentAssignment::query()->sole();

    expect($quizAssignment->assessment_version_id)->toBe($quiz->id)
        ->and($quizAssignment->employee_id)->toBe($recruiter->id)
        ->and($quizAssignment->training_assignment_id)->toBe($trainingAssignment->id);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$training->course_id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('quizzes', 1)->where('quizzes.0.id', $quizAssignment->id));

    $this->actingAs($lead->user)->delete("/recruiter/training/assignments/{$trainingAssignment->id}")->assertSessionHasNoErrors();

    expect(AssessmentAssignment::query()->count())->toBe(0);
});

test('a quiz linked to the live course reaches recruiters assigned earlier, and unlinking withdraws it if unstarted', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $quiz = publishedQuiz();
    $training = TrainingCourseVersion::factory()->published()->create();
    TrainingLesson::factory()->forVersion($training, 1)->create(['body' => 'Lesson text.']);
    $trainingAssignment = TrainingAssignment::factory()->forVersion($training)->forEmployee($recruiter)->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$training->id}/quizzes", ['assessment_version_id' => $quiz->id])
        ->assertSessionHasNoErrors();

    $quizAssignment = AssessmentAssignment::query()->sole();
    expect($quizAssignment->employee_id)->toBe($recruiter->id)
        ->and($quizAssignment->training_assignment_id)->toBe($trainingAssignment->id);

    $this->actingAs($lead->user)
        ->delete("/recruiter/training/manage/versions/{$training->id}/quizzes/{$quiz->id}")
        ->assertSessionHasNoErrors();

    expect(AssessmentAssignment::query()->count())->toBe(0);
});

test('draft quizzes cannot be linked, and older training versions kept as history cannot change their quizzes', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $draftQuiz = AssessmentVersion::factory()->create();
    $training = TrainingCourseVersion::factory()->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$training->id}/quizzes", ['assessment_version_id' => $draftQuiz->id])
        ->assertSessionHasErrors('assessment_version_id');

    $published = TrainingCourseVersion::factory()->archived()->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$published->id}/quizzes", ['assessment_version_id' => publishedQuiz()->id])
        ->assertForbidden();

    expect($training->assessmentVersions()->count())->toBe(0)
        ->and($published->assessmentVersions()->count())->toBe(0);
});

test('quiz attempts survive a new training version', function () {
    $lead = quizTaker(SystemRole::RecruiterLead);
    $recruiter = quizTaker();
    $quiz = publishedQuiz();
    $training = TrainingCourseVersion::factory()->create();
    TrainingLesson::factory()->forVersion($training, 1)->create(['body' => 'Lesson text.']);
    $content = app(TrainingContentService::class);
    $content->attachAssessment($training, $quiz, $lead->user);
    $content->publishVersion($training, $lead->user);
    $this->actingAs($lead->user)->post('/recruiter/training/assignments', ['track' => 'unassigned', 'course_id' => $training->course_id, 'mode' => 'individual', 'employee_ids' => [$recruiter->id]]);
    $attempt = submitQuiz($recruiter, startQuiz($recruiter, AssessmentAssignment::query()->sole()), perfectAnswers($quiz));

    $next = $content->createVersion($training->course, $lead->user);
    $content->publishVersion($next, $lead->user);

    expect($next->assessmentVersions()->pluck('ro_assessment_versions.id')->all())->toBe([$quiz->id])
        ->and($attempt->fresh()->result)->toBe(AssessmentResult::Passed)
        ->and(AssessmentAttempt::query()->count())->toBe(1);
});

// Dashboard and audit

test('the recruiter dashboard shows quiz counts, and reviewers see the review queue', function () {
    $recruiter = quizTaker();
    assignQuiz(publishedQuiz(), $recruiter);
    AssessmentAssignment::factory()->forVersion(publishedQuiz())->forEmployee($recruiter)->overdue()->create();

    $this->actingAs($recruiter->user)
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('assessments.assigned', 2)->where('assessments.overdue', 1)->where('awaitingReview', null));

    $this->actingAs(quizTaker(SystemRole::RecruiterLead)->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page->where('awaitingReview', 0));
});

test('answer saves and submissions do not write activity log entries', function () {
    $recruiter = quizTaker();
    $version = publishedQuiz();
    $attempt = startQuiz($recruiter, assignQuiz($version, $recruiter));
    $before = Activity::query()->count();

    $this->actingAs($recruiter->user)->putJson("/recruiter/assessments/attempts/{$attempt->id}/answers", ['answers' => perfectAnswers($version)])->assertOk();
    submitQuiz($recruiter, $attempt, perfectAnswers($version));

    expect(Activity::query()->where('subject_type', (new AssessmentAnswer)->getMorphClass())->count())->toBe(0)
        ->and(Activity::query()->where('subject_type', (new AssessmentAttempt)->getMorphClass())->count())->toBe(0)
        ->and(Activity::query()->count())->toBe($before);
});
