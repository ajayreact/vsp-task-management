<?php

use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentScorer;
use App\Modules\RecruiterOperations\Services\Assessments\CorrectAnswerParser;
use App\Modules\RecruiterOperations\Services\Assessments\Import\ImportDuplicateDetector;
use App\Modules\RecruiterOperations\Services\Assessments\Import\QuestionImportRowValidator;
use App\Modules\RecruiterOperations\Services\Assessments\QuestionRules;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @param  array<string, string>  $values
 * @return array<string, mixed>
 */
function importedRow(array $values, array $letters = ['A', 'B', 'C', 'D'], int $row = 2): array
{
    return QuestionImportRowValidator::validate($row, array_merge([
        'question' => 'What does OPT stand for?',
        'type' => 'single_choice',
        'option a' => 'Optional Practical Training',
        'option b' => 'Occupational Practical Training',
        'option c' => '',
        'option d' => '',
        'correct answer' => 'A',
        'points' => '1',
        'explanation' => '',
        'category' => '',
    ], $values), $letters);
}

/**
 * @param  list<array{0: string, 1: bool}>  $options
 * @return list<array{text: string, correct: bool}>
 */
function ruleOptions(array $options): array
{
    return array_map(fn (array $option) => ['text' => $option[0], 'correct' => $option[1]], $options);
}

// Scoring

test('selections are normalized to sorted unique positive ids', function () {
    expect(AssessmentScorer::normalizeSelection([5, '3', 3, 0, -1, 'x', 9]))->toBe([3, 5, 9])
        ->and(AssessmentScorer::normalizeSelection([]))->toBe([])
        ->and(AssessmentScorer::sameSelection([1, 3], ['3', 1]))->toBeTrue()
        ->and(AssessmentScorer::sameSelection([1, 3], [1]))->toBeFalse();
});

test('single choice and true/false need exactly the one correct option', function (QuestionType $type) {
    expect(AssessmentScorer::isCorrect($type, [10], [10]))->toBeTrue()
        ->and(AssessmentScorer::isCorrect($type, [10], [11]))->toBeFalse()
        ->and(AssessmentScorer::isCorrect($type, [10], [10, 11]))->toBeFalse()
        ->and(AssessmentScorer::isCorrect($type, [10], []))->toBeFalse()
        ->and(AssessmentScorer::pointsFor($type, 3, [10], [10]))->toBe(3)
        ->and(AssessmentScorer::pointsFor($type, 3, [10], [11]))->toBe(0);
})->with([QuestionType::SingleChoice, QuestionType::TrueFalse]);

test('multiple choice scores full points only for the exact correct set', function () {
    $correct = [1, 3];

    expect(AssessmentScorer::pointsFor(QuestionType::MultipleChoice, 2, $correct, [3, 1]))->toBe(2)
        ->and(AssessmentScorer::pointsFor(QuestionType::MultipleChoice, 2, $correct, [1]))->toBe(0)
        ->and(AssessmentScorer::pointsFor(QuestionType::MultipleChoice, 2, $correct, [1, 2, 3]))->toBe(0)
        ->and(AssessmentScorer::pointsFor(QuestionType::MultipleChoice, 2, $correct, [2, 4]))->toBe(0)
        ->and(AssessmentScorer::pointsFor(QuestionType::MultipleChoice, 2, $correct, []))->toBe(0);
});

test('short answers are never scored automatically', function () {
    expect(AssessmentScorer::isCorrect(QuestionType::ShortAnswer, [], []))->toBeFalse()
        ->and(AssessmentScorer::pointsFor(QuestionType::ShortAnswer, 5, [1], [1]))->toBe(0);
});

test('percentages are rounded to two decimals and capped', function () {
    expect(AssessmentScorer::percentage(1, 3))->toBe(33.33)
        ->and(AssessmentScorer::percentage(2, 3))->toBe(66.67)
        ->and(AssessmentScorer::percentage(5, 6))->toBe(83.33)
        ->and(AssessmentScorer::percentage(4, 4))->toBe(100.0)
        ->and(AssessmentScorer::percentage(9, 4))->toBe(100.0)
        ->and(AssessmentScorer::percentage(0, 0))->toBe(0.0);
});

test('pass/fail uses whole-number comparison so rounding cannot tip it', function () {
    expect(AssessmentScorer::passed(7, 10, 70))->toBeTrue()
        ->and(AssessmentScorer::passed(69, 100, 70))->toBeFalse()
        ->and(AssessmentScorer::passed(2, 3, 67))->toBeFalse()
        ->and(AssessmentScorer::passed(2, 3, 66))->toBeTrue()
        ->and(AssessmentScorer::passed(0, 0, 1))->toBeFalse()
        ->and(AssessmentScorer::result(10, 10, 70, true))->toBe(AssessmentResult::PendingReview)
        ->and(AssessmentScorer::result(7, 10, 70, false))->toBe(AssessmentResult::Passed)
        ->and(AssessmentScorer::result(6, 10, 70, false))->toBe(AssessmentResult::Failed);
});

// Question rules

test('question rules follow the type', function (QuestionType $type, array $options, ?string $problem) {
    $problems = QuestionRules::problems($type, ruleOptions($options));

    $problem === null
        ? expect($problems)->toBe([])
        : expect(implode(' ', $problems))->toContain($problem);
})->with([
    'single choice ok' => [QuestionType::SingleChoice, [['A', true], ['B', false]], null],
    'single choice two correct' => [QuestionType::SingleChoice, [['A', true], ['B', true]], 'exactly one correct'],
    'single choice one option' => [QuestionType::SingleChoice, [['A', true]], 'at least 2 options'],
    'multiple choice two correct' => [QuestionType::MultipleChoice, [['A', true], ['B', false], ['C', true]], null],
    'multiple choice none correct' => [QuestionType::MultipleChoice, [['A', false], ['B', false]], 'at least one correct'],
    'true/false ok' => [QuestionType::TrueFalse, [['True', false], ['False', true]], null],
    'true/false wrong labels' => [QuestionType::TrueFalse, [['Yes', true], ['No', false]], 'True and False'],
    'short answer ok' => [QuestionType::ShortAnswer, [], null],
    'short answer with options' => [QuestionType::ShortAnswer, [['A', true]], 'do not have options'],
    'duplicates ignoring case and spaces' => [QuestionType::SingleChoice, [['  H-1B ', true], ['h-1b', false]], 'same text'],
]);

test('there is no fixed limit of four options', function () {
    $options = array_map(fn (int $i) => ["Option {$i}", $i === 7], range(1, 10));

    expect(QuestionRules::problems(QuestionType::SingleChoice, ruleOptions($options)))->toBe([])
        ->and(QuestionRules::optionKey(9))->toBe('J');
});

test('categories are trimmed with inner spaces collapsed', function () {
    expect(QuestionRules::normalizeCategory('  Visa   Basics '))->toBe('Visa Basics')
        ->and(QuestionRules::normalizeCategory('   '))->toBeNull()
        ->and(QuestionRules::normalizeCategory(null))->toBeNull();
});

// Correct-answer parsing

test('correct answers are parsed per question type', function (string $value, QuestionType $type, array $keys, ?string $error) {
    $parsed = CorrectAnswerParser::parse($value, $type, ['A', 'B', 'C', 'D']);

    expect($parsed['keys'])->toBe($keys);
    $error === null ? expect($parsed['error'])->toBeNull() : expect((string) $parsed['error'])->toContain($error);
})->with([
    'single letter' => ['A', QuestionType::SingleChoice, ['A'], null],
    'lower case' => ['c', QuestionType::SingleChoice, ['C'], null],
    'multiple letters' => ['A,C', QuestionType::MultipleChoice, ['A', 'C'], null],
    'multiple with spaces' => [' b , d ', QuestionType::MultipleChoice, ['B', 'D'], null],
    'true word' => ['True', QuestionType::TrueFalse, ['A'], null],
    'false word' => ['FALSE', QuestionType::TrueFalse, ['B'], null],
    'true/false letter' => ['B', QuestionType::TrueFalse, ['B'], null],
    'short answer blank' => ['', QuestionType::ShortAnswer, [], null],
    'missing' => ['', QuestionType::SingleChoice, [], 'missing'],
    'semicolons' => ['A;C', QuestionType::MultipleChoice, [], 'option letters'],
    'trailing comma' => ['A,', QuestionType::MultipleChoice, [], 'option letters'],
    'words' => ['Optional', QuestionType::SingleChoice, [], 'option letters'],
    'repeated letter' => ['A,A', QuestionType::MultipleChoice, [], 'same option twice'],
    'missing option' => ['E', QuestionType::SingleChoice, [], 'which is empty'],
    'two for single' => ['A,B', QuestionType::SingleChoice, [], 'exactly one'],
    'answer on short answer' => ['A', QuestionType::ShortAnswer, [], 'Leave Correct Answer empty'],
]);

// Excel row validation

test('a valid row becomes a question with its correct options marked', function () {
    $row = importedRow(['option c' => 'Optional Professional Training', 'correct answer' => 'a', 'points' => '2', 'category' => ' Immigration ']);

    expect($row['status'])->toBe('valid')
        ->and($row['errors'])->toBe([])
        ->and($row['question']['type'])->toBe('single_choice')
        ->and($row['question']['points'])->toBe(2)
        ->and($row['question']['category'])->toBe('Immigration')
        ->and($row['question']['options'])->toBe([
            ['text' => 'Optional Practical Training', 'correct' => true],
            ['text' => 'Occupational Practical Training', 'correct' => false],
            ['text' => 'Optional Professional Training', 'correct' => false],
        ]);
});

test('types are read loosely', function (string $type, string $expected) {
    expect(importedRow(['type' => $type])['question']['type'] ?? null)->toBe($expected);
})->with([
    ['Single Choice', 'single_choice'],
    ['single-choice', 'single_choice'],
    [' SINGLE_CHOICE ', 'single_choice'],
]);

test('true/false rows may leave the options empty', function () {
    $row = importedRow(['type' => 'true/false', 'option a' => '', 'option b' => '', 'correct answer' => 'False']);

    expect($row['status'])->toBe('valid')
        ->and($row['question']['options'])->toBe([['text' => 'True', 'correct' => false], ['text' => 'False', 'correct' => true]]);
});

test('short-answer rows keep no options', function () {
    $row = importedRow(['type' => 'short_answer', 'option a' => '', 'option b' => '', 'correct answer' => '']);

    expect($row['status'])->toBe('valid')
        ->and($row['question']['options'])->toBe([]);
});

test('invalid rows collect every problem', function (array $values, string $problem) {
    $row = importedRow($values);

    expect($row['status'])->toBe('error')
        ->and($row['question'])->toBeNull()
        ->and(implode(' ', $row['errors']))->toContain($problem);
})->with([
    'missing question' => [['question' => '  '], 'Question is missing'],
    'example row' => [['question' => 'example: pick one'], 'example row'],
    'long question' => [['question' => str_repeat('x', 2001)], 'longer than 2000'],
    'missing type' => [['type' => ''], 'Type is missing'],
    'unknown type' => [['type' => 'essay'], 'not recognised'],
    'gap in options' => [['option b' => '', 'option c' => 'Third'], 'filled in order'],
    'text points' => [['points' => 'one'], 'whole number'],
    'fractional points' => [['points' => '1.5'], 'whole number'],
    'zero points' => [['points' => '0'], 'between 1 and 100'],
    'too many points' => [['points' => '101'], 'between 1 and 100'],
    'long category' => [['category' => str_repeat('c', 101)], 'Category is longer'],
]);

test('blank points mean one point and blank rows are detected', function () {
    expect(QuestionImportRowValidator::points(''))->toBe([1, null])
        ->and(QuestionImportRowValidator::points('7'))->toBe([7, null])
        ->and(QuestionImportRowValidator::isBlank(['question' => ' ', 'type' => '']))->toBeTrue()
        ->and(QuestionImportRowValidator::isBlank(['question' => '', 'type' => 'single_choice']))->toBeFalse();
});

// Duplicate detection

test('prompts are compared ignoring case and extra whitespace', function () {
    expect(AssessmentQuestion::normalizePrompt("  What does\tOPT   stand for? "))->toBe('what does opt stand for?')
        ->and(AssessmentQuestion::hashPrompt('What does OPT stand for?'))->toBe(AssessmentQuestion::hashPrompt(' WHAT DOES opt  STAND FOR? '))
        ->and(AssessmentQuestion::hashPrompt('What does OPT stand for?'))->not->toBe(AssessmentQuestion::hashPrompt('What does CPT stand for?'));
});

test('duplicates are flagged against the bank and earlier rows; error rows are left alone', function () {
    $rows = [
        importedRow(['question' => 'Known question'], row: 2),
        importedRow(['question' => 'New question'], row: 3),
        importedRow(['question' => '  new QUESTION '], row: 4),
        importedRow(['question' => 'New question', 'type' => 'essay'], row: 5),
    ];

    $flagged = ImportDuplicateDetector::flag($rows, [AssessmentQuestion::hashPrompt('known question')]);

    expect(array_column($flagged, 'duplicate'))->toBe(['Already in the Question Bank.', null, 'Same question as row 3.', null]);
});

// State transitions

test('only a published training quiz with a current version is assignable', function (AssessmentStatus $status, ?int $current, AssessmentType $type, bool $assignable) {
    $assessment = (new Assessment)->forceFill(['status' => $status, 'current_version_id' => $current, 'type' => $type]);

    expect($assessment->isAssignable())->toBe($assignable);
})->with([
    'published' => [AssessmentStatus::Published, 1, AssessmentType::TrainingQuiz, true],
    'draft' => [AssessmentStatus::Draft, null, AssessmentType::TrainingQuiz, false],
    'archived' => [AssessmentStatus::Archived, 1, AssessmentType::TrainingQuiz, false],
    'published without version' => [AssessmentStatus::Published, null, AssessmentType::TrainingQuiz, false],
    'reserved type' => [AssessmentStatus::Published, 1, AssessmentType::PracticalAssessment, false],
]);

test('only training quizzes are offered as assessment types', function () {
    expect(AssessmentType::options())->toBe([['value' => 'training_quiz', 'label' => 'Training quiz']])
        ->and(AssessmentType::RecruiterAssessment->isAvailable())->toBeFalse();
});

test('version states', function () {
    $version = (new AssessmentVersion)->forceFill(['status' => AssessmentStatus::Draft, 'version_number' => 3]);

    expect($version->isDraft())->toBeTrue()
        ->and($version->isPublished())->toBeFalse()
        ->and($version->label())->toBe('v3');

    $version->status = AssessmentStatus::Published;

    expect($version->isDraft())->toBeFalse()
        ->and($version->isPublished())->toBeTrue();
});

test('an unfinished assignment past its due date is overdue; a completed one is not', function () {
    $now = Carbon::parse('2026-09-29 12:00');
    $assignment = (new AssessmentAssignment)->forceFill(['status' => AssessmentAssignmentStatus::InProgress, 'due_at' => $now->copy()->subMinute()]);

    expect($assignment->effectiveStatus($now))->toBe(AssessmentAssignmentStatus::Overdue);

    $assignment->due_at = $now->copy()->addDay();
    expect($assignment->effectiveStatus($now))->toBe(AssessmentAssignmentStatus::InProgress);

    $assignment->forceFill(['status' => AssessmentAssignmentStatus::Completed, 'due_at' => $now->copy()->subDay()]);
    expect($assignment->effectiveStatus($now))->toBe(AssessmentAssignmentStatus::Completed);
});

test('an attempt expires after its deadline plus the grace period', function () {
    $deadline = Carbon::parse('2026-09-29 12:00:00');
    $attempt = (new AssessmentAttempt)->forceFill(['status' => AttemptStatus::InProgress, 'expires_at' => $deadline]);

    expect($attempt->isInProgress())->toBeTrue()
        ->and($attempt->hasExpired($deadline->copy()->addSeconds(AssessmentAttempt::GRACE_SECONDS)))->toBeFalse()
        ->and($attempt->hasExpired($deadline->copy()->addSeconds(AssessmentAttempt::GRACE_SECONDS + 1)))->toBeTrue()
        ->and($attempt->secondsRemaining($deadline->copy()->subSeconds(90)))->toBe(90)
        ->and($attempt->secondsRemaining($deadline->copy()->addMinute()))->toBe(0);

    $untimed = (new AssessmentAttempt)->forceFill(['status' => AttemptStatus::InProgress, 'expires_at' => null]);

    expect($untimed->hasExpired())->toBeFalse()
        ->and($untimed->secondsRemaining())->toBeNull();
});
