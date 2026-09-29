<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function quizAuthor(SystemRole $role = SystemRole::RecruiterLead): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

function draftQuiz(int $questions = 0): AssessmentVersion
{
    $version = AssessmentVersion::factory()->create();

    for ($i = 1; $i <= $questions; $i++) {
        AssessmentQuestion::factory()->forVersion($version, $i)->singleChoice()->create();
    }

    return $version->fresh();
}

/**
 * @return array<string, mixed>
 */
function singleChoicePayload(array $overrides = []): array
{
    return [
        'type' => 'single_choice',
        'prompt' => 'What does OPT stand for?',
        'points' => 2,
        'explanation' => 'OPT is Optional Practical Training.',
        'category' => 'Visa Basics',
        'options' => [
            ['text' => 'Optional Practical Training', 'is_correct' => true],
            ['text' => 'Occupational Practical Training', 'is_correct' => false],
            ['text' => 'Optional Professional Training', 'is_correct' => false],
        ],
        ...$overrides,
    ];
}

// Access

test('recruiters cannot open or use any authoring, assignment or results screen', function () {
    $recruiter = quizAuthor(SystemRole::Recruiter);
    $version = draftQuiz(1);
    $question = AssessmentQuestion::factory()->singleChoice()->create();

    $this->actingAs($recruiter->user);

    foreach ([
        '/recruiter/assessments/manage',
        '/recruiter/assessments/manage/create',
        "/recruiter/assessments/manage/{$version->assessment_id}",
        "/recruiter/assessments/manage/versions/{$version->id}/preview",
        '/recruiter/assessments/questions',
        "/recruiter/assessments/questions/{$question->id}",
        '/recruiter/assessments/questions/import',
        '/recruiter/assessments/questions/import/template',
        '/recruiter/assessments/assignments',
        '/recruiter/assessments/assignments/create',
        '/recruiter/assessments/results',
    ] as $url) {
        $this->get($url)->assertForbidden();
    }

    $this->post('/recruiter/assessments/manage', ['title' => 'Mine'])->assertForbidden();
    $this->post('/recruiter/assessments/questions', singleChoicePayload())->assertForbidden();
    $this->post("/recruiter/assessments/manage/versions/{$version->id}/publish")->assertForbidden();
    $this->post('/recruiter/assessments/assignments', [])->assertForbidden();

    expect(Assessment::query()->count())->toBe(1)
        ->and($version->fresh()->status)->toBe(AssessmentStatus::Draft);
});

test('people without recruiter access cannot reach assessments at all', function () {
    $outsider = Employee::factory()->create();

    $this->actingAs($outsider->user)->get('/recruiter/assessments')->assertForbidden();
});

test('the recruiter lead role holds the three assessment permissions and nothing unrelated', function () {
    $lead = quizAuthor();

    expect($lead->user->can('recruiter.assessments.manage'))->toBeTrue()
        ->and($lead->user->can('recruiter.assessments.invite'))->toBeTrue()
        ->and($lead->user->can('recruiter.assessments.review'))->toBeTrue()
        ->and($lead->user->can('tasks.access'))->toBeFalse();

    $recruiter = quizAuthor(SystemRole::Recruiter);

    expect($recruiter->user->can('recruiter.assessments.manage'))->toBeFalse()
        ->and($recruiter->user->can('recruiter.assessments.invite'))->toBeFalse()
        ->and($recruiter->user->can('recruiter.assessments.review'))->toBeFalse();
});

// Quizzes and versions

test('a lead creates a training quiz with a draft Version 1 and the default settings', function () {
    $lead = quizAuthor();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/manage', [
            'title' => 'Visa Basics Quiz',
            'description' => 'After the visa lessons.',
            'passing_percentage' => 70,
            'max_attempts' => 2,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $assessment = Assessment::query()->sole();
    $version = $assessment->versions()->sole();

    expect($assessment->type)->toBe(AssessmentType::TrainingQuiz)
        ->and($assessment->status)->toBe(AssessmentStatus::Draft)
        ->and($assessment->current_version_id)->toBeNull()
        ->and($version->version_number)->toBe(1)
        ->and($version->status)->toBe(AssessmentStatus::Draft)
        ->and($version->passing_percentage)->toBe(70)
        ->and($version->max_attempts)->toBe(2)
        ->and($version->time_limit_minutes)->toBeNull()
        ->and($version->show_result)->toBeTrue()
        ->and($version->allow_review)->toBeFalse();

    expect(Activity::query()->where('log_name', 'recruiter-assessments')->where('subject_type', $assessment->getMorphClass())->exists())->toBeTrue();
});

test('only training quizzes can be created; reserved assessment types are refused', function () {
    $lead = quizAuthor();

    foreach (['recruiter_assessment', 'practical_assessment'] as $type) {
        $this->actingAs($lead->user)
            ->post('/recruiter/assessments/manage', ['title' => 'Practical', 'type' => $type])
            ->assertSessionHasErrors('type');
    }

    expect(Assessment::query()->count())->toBe(0);
});

test('version settings are validated', function () {
    $lead = quizAuthor();
    $version = draftQuiz();

    $this->actingAs($lead->user)
        ->put("/recruiter/assessments/manage/versions/{$version->id}", [
            'passing_percentage' => 0,
            'max_attempts' => 11,
            'time_limit_minutes' => 481,
        ])
        ->assertSessionHasErrors(['passing_percentage', 'max_attempts', 'time_limit_minutes']);

    $this->actingAs($lead->user)
        ->put("/recruiter/assessments/manage/versions/{$version->id}", [
            'passing_percentage' => 80,
            'max_attempts' => 3,
            'time_limit_minutes' => 15,
            'randomize_questions' => true,
            'show_result' => false,
        ])
        ->assertSessionHasNoErrors();

    expect($version->fresh())
        ->passing_percentage->toBe(80)
        ->max_attempts->toBe(3)
        ->time_limit_minutes->toBe(15)
        ->randomize_questions->toBeTrue()
        ->show_result->toBeFalse();
});

test('a version needs at least one question to publish', function () {
    $lead = quizAuthor();
    $version = draftQuiz();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/versions/{$version->id}/publish")
        ->assertSessionHasErrors('version');

    expect($version->fresh()->status)->toBe(AssessmentStatus::Draft);
});

test('publishing freezes the version: its questions and settings can no longer change', function () {
    $lead = quizAuthor();
    $version = draftQuiz(2);
    $question = $version->questions()->first();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/versions/{$version->id}/publish")
        ->assertSessionHasNoErrors();

    $version->refresh();

    expect($version->status)->toBe(AssessmentStatus::Published)
        ->and($version->assessment->fresh()->current_version_id)->toBe($version->id)
        ->and($version->assessment->fresh()->status)->toBe(AssessmentStatus::Published);

    $this->actingAs($lead->user);
    $this->put("/recruiter/assessments/manage/versions/{$version->id}", ['passing_percentage' => 50])->assertForbidden();
    $this->put("/recruiter/assessments/questions/{$question->id}", singleChoicePayload())->assertForbidden();
    $this->delete("/recruiter/assessments/questions/{$question->id}")->assertForbidden();
    $this->post("/recruiter/assessments/manage/versions/{$version->id}/questions", singleChoicePayload())->assertForbidden();
    $this->post("/recruiter/assessments/manage/questions/{$question->id}/move", ['direction' => 'down'])->assertForbidden();

    expect($version->fresh()->passing_percentage)->toBe(70)
        ->and($version->questions()->count())->toBe(2)
        ->and($question->fresh()->prompt)->not->toBe('What does OPT stand for?');
});

test('a new version copies the questions and settings into a draft; publishing it archives the old one and existing assignments stay put', function () {
    $lead = quizAuthor();
    $v1 = draftQuiz(2);
    $content = app(AssessmentContentService::class);
    $v1->forceFill(['passing_percentage' => 80])->save();
    $content->publishVersion($v1, $lead->user);
    $assignment = AssessmentAssignment::factory()->forVersion($v1)->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/{$v1->assessment_id}/versions", ['source_version_id' => $v1->id])
        ->assertSessionHasNoErrors();

    $v2 = AssessmentVersion::query()->where('assessment_id', $v1->assessment_id)->where('version_number', 2)->sole();

    expect($v2->status)->toBe(AssessmentStatus::Draft)
        ->and($v2->passing_percentage)->toBe(80)
        ->and($v2->questions()->count())->toBe(2)
        ->and($v2->questions()->pluck('id')->intersect($v1->questions()->pluck('id')))->toBeEmpty();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/{$v1->assessment_id}/versions")
        ->assertSessionHasErrors('version');

    $content->publishVersion($v2, $lead->user);

    expect($v1->fresh()->status)->toBe(AssessmentStatus::Archived)
        ->and($v2->fresh()->status)->toBe(AssessmentStatus::Published)
        ->and($v1->assessment->fresh()->current_version_id)->toBe($v2->id)
        ->and($assignment->fresh()->assessment_version_id)->toBe($v1->id);
});

test('a draft can be discarded unless it is the only version', function () {
    $lead = quizAuthor();
    $only = draftQuiz(1);

    $this->actingAs($lead->user)->delete("/recruiter/assessments/manage/versions/{$only->id}")->assertSessionHasErrors('version');
    expect(AssessmentVersion::query()->find($only->id))->not->toBeNull();

    $content = app(AssessmentContentService::class);
    $content->publishVersion($only, $lead->user);
    $draft = $content->createVersion($only->assessment, $lead->user);

    $this->actingAs($lead->user)->delete("/recruiter/assessments/manage/versions/{$draft->id}")->assertSessionHasNoErrors();

    expect(AssessmentVersion::query()->find($draft->id))->toBeNull()
        ->and(AssessmentQuestion::query()->where('assessment_version_id', $draft->id)->count())->toBe(0)
        ->and($only->fresh()->status)->toBe(AssessmentStatus::Published);
});

test('an archived quiz cannot be assigned but can be restored', function () {
    $lead = quizAuthor();
    $version = draftQuiz(1);
    app(AssessmentContentService::class)->publishVersion($version, $lead->user);
    $assessment = $version->assessment->fresh();

    $this->actingAs($lead->user)->post("/recruiter/assessments/manage/{$assessment->id}/archive")->assertSessionHasNoErrors();
    expect($assessment->fresh()->status)->toBe(AssessmentStatus::Archived)
        ->and($assessment->fresh()->isAssignable())->toBeFalse();

    $this->actingAs($lead->user)->post("/recruiter/assessments/manage/{$assessment->id}/restore")->assertSessionHasNoErrors();
    expect($assessment->fresh()->isAssignable())->toBeTrue();
});

test('the builder shows managers the answer key, and the manage list lists quizzes', function () {
    $lead = quizAuthor();
    $version = draftQuiz(1);

    $this->actingAs($lead->user)
        ->get("/recruiter/assessments/manage/{$version->assessment_id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/assessments/manage/show')
            ->where('version.id', $version->id)
            ->has('questions', 1)
            ->where('questions.0.options.0.is_correct', true)
            ->where('can.publish', true));

    $this->actingAs($lead->user)
        ->get('/recruiter/assessments/manage')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/assessments/manage/index')->has('assessments.data', 1));
});

// Questions

test('a manager adds a single-choice question to the Question Bank', function () {
    $lead = quizAuthor();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload())
        ->assertSessionHasNoErrors();

    $question = AssessmentQuestion::query()->with('options')->sole();

    expect($question->assessment_version_id)->toBeNull()
        ->and($question->type)->toBe(QuestionType::SingleChoice)
        ->and($question->points)->toBe(2)
        ->and($question->source)->toBe(QuestionSource::Manual)
        ->and($question->category)->toBe('Visa Basics')
        ->and($question->options->pluck('option_key')->all())->toBe(['A', 'B', 'C'])
        ->and($question->correctOptionIds())->toBe([$question->options[0]->id]);
});

test('each question type enforces its own option rules', function (array $payload, string $errorKey) {
    $lead = quizAuthor();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload($payload))
        ->assertSessionHasErrors($errorKey);

    expect(AssessmentQuestion::query()->count())->toBe(0);
})->with([
    'single choice with two correct' => [['options' => [['text' => 'A', 'is_correct' => true], ['text' => 'B', 'is_correct' => true]]], 'options'],
    'single choice with none correct' => [['options' => [['text' => 'A', 'is_correct' => false], ['text' => 'B', 'is_correct' => false]]], 'options'],
    'multiple choice with none correct' => [['type' => 'multiple_choice', 'options' => [['text' => 'A', 'is_correct' => false], ['text' => 'B', 'is_correct' => false]]], 'options'],
    'only one option' => [['options' => [['text' => 'A', 'is_correct' => true]]], 'options'],
    'duplicate option text' => [['options' => [['text' => 'Same', 'is_correct' => true], ['text' => 'same', 'is_correct' => false]]], 'options'],
    'duplicate option text after trimming' => [['options' => [['text' => '  H-1B Visa ', 'is_correct' => true], ['text' => 'h-1b visa', 'is_correct' => false]]], 'options'],
    'short answer with options' => [['type' => 'short_answer'], 'options'],
    'unknown type' => [['type' => 'essay'], 'type'],
    'points above the limit' => [['points' => 101], 'points'],
]);

test('multiple choice accepts several correct options and more than four options', function () {
    $lead = quizAuthor();
    $options = collect(range(1, 6))->map(fn (int $i) => ['text' => "Option {$i}", 'is_correct' => in_array($i, [2, 5], true)])->all();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload(['type' => 'multiple_choice', 'options' => $options]))
        ->assertSessionHasNoErrors();

    $question = AssessmentQuestion::query()->with('options')->sole();

    expect($question->options)->toHaveCount(6)
        ->and($question->options->pluck('option_key')->last())->toBe('F')
        ->and(count($question->correctOptionIds()))->toBe(2);
});

test('true/false questions always have exactly the options True and False', function () {
    $lead = quizAuthor();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload([
            'type' => 'true_false',
            'prompt' => 'H-1B is a work visa.',
            'options' => [['text' => 'True', 'is_correct' => true], ['text' => 'False', 'is_correct' => false]],
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload([
            'type' => 'true_false',
            'prompt' => 'Another statement.',
            'options' => [['text' => 'Yes', 'is_correct' => true], ['text' => 'No', 'is_correct' => false]],
        ]))
        ->assertSessionHasErrors('options');

    expect(AssessmentQuestion::query()->sole()->options()->pluck('text')->all())->toBe(['True', 'False']);
});

test('a short-answer question has no options and no answer key', function () {
    $lead = quizAuthor();

    $this->actingAs($lead->user)
        ->post('/recruiter/assessments/questions', singleChoicePayload(['type' => 'short_answer', 'options' => []]))
        ->assertSessionHasNoErrors();

    expect(AssessmentQuestion::query()->sole()->options()->count())->toBe(0);
});

test('adding bank questions to a draft copies them, skips ones already added, and later bank edits do not touch the copy', function () {
    $lead = quizAuthor();
    $version = draftQuiz();
    $bank = AssessmentQuestion::factory()->singleChoice()->create(['prompt' => 'Original prompt']);
    $other = AssessmentQuestion::factory()->trueFalse()->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/versions/{$version->id}/bank", ['question_ids' => [$bank->id, $other->id]])
        ->assertSessionHasNoErrors();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/versions/{$version->id}/bank", ['question_ids' => [$bank->id]])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'skipped'));

    $copies = $version->questions()->get();

    expect($copies)->toHaveCount(2)
        ->and($copies[0]->bank_question_id)->toBe($bank->id)
        ->and($copies[0]->id)->not->toBe($bank->id);

    $this->actingAs($lead->user)
        ->put("/recruiter/assessments/questions/{$bank->id}", singleChoicePayload(['prompt' => 'Edited prompt']))
        ->assertSessionHasNoErrors();

    expect($copies[0]->fresh()->prompt)->toBe('Original prompt');
});

test('archiving a bank question soft-deletes it and it can be restored', function () {
    $lead = quizAuthor();
    $question = AssessmentQuestion::factory()->singleChoice()->create();

    $this->actingAs($lead->user)->delete("/recruiter/assessments/questions/{$question->id}")->assertSessionHasNoErrors();
    expect($question->fresh()->trashed())->toBeTrue();

    $this->actingAs($lead->user)->post("/recruiter/assessments/questions/{$question->id}/restore")->assertSessionHasNoErrors();
    expect($question->fresh()->trashed())->toBeFalse();
});

test('questions in a draft can be reordered and removed', function () {
    $lead = quizAuthor();
    $version = draftQuiz(3);
    [$first, $second, $third] = $version->questions()->get()->all();

    $this->actingAs($lead->user)
        ->post("/recruiter/assessments/manage/questions/{$third->id}/move", ['direction' => 'up'])
        ->assertRedirect();

    expect($version->questions()->pluck('id')->all())->toBe([$first->id, $third->id, $second->id]);

    $this->actingAs($lead->user)->delete("/recruiter/assessments/questions/{$first->id}")->assertSessionHasNoErrors();

    expect(AssessmentQuestion::withTrashed()->find($first->id))->toBeNull()
        ->and($version->questions()->pluck('sort_order')->all())->toBe([1, 2]);
});

test('the bank lists questions and filters them by type', function () {
    $lead = quizAuthor();
    AssessmentQuestion::factory()->singleChoice()->create();
    AssessmentQuestion::factory()->trueFalse()->create();
    AssessmentQuestion::factory()->forVersion(draftQuiz())->singleChoice()->create();

    $this->actingAs($lead->user)
        ->get('/recruiter/assessments/questions?type=true_false')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/assessments/bank/index')->has('questions.data', 1));

    $this->actingAs($lead->user)
        ->get('/recruiter/assessments/questions')
        ->assertInertia(fn ($page) => $page->has('questions.data', 2));
});

test('configuration changes are logged under recruiter-assessments', function () {
    $lead = quizAuthor();
    $version = draftQuiz(1);

    $this->actingAs($lead->user)->post("/recruiter/assessments/manage/versions/{$version->id}/publish");

    expect(Activity::query()
        ->where('log_name', 'recruiter-assessments')
        ->where('subject_type', $version->getMorphClass())
        ->where('subject_id', $version->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});
