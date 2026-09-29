<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

const IMPORT_HEADERS = ['Question', 'Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer', 'Points', 'Explanation', 'Category'];

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function importManager(SystemRole $role = SystemRole::RecruiterLead): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * One template row: question, type, options A-D, correct answer, points, explanation, category.
 *
 * @param  list<string>  $options
 * @return list<string>
 */
function importRow(string $question, string $type, array $options, string $correct, string $points = '1', string $explanation = '', string $category = 'Immigration'): array
{
    return [$question, $type, ...array_pad($options, 4, ''), $correct, $points, $explanation, $category];
}

/**
 * @param  list<list<string>>  $rows
 * @param  list<string>  $headers
 */
function questionWorkbook(array $rows, array $headers = IMPORT_HEADERS): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Questions');
    $sheet->fromArray([$headers, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), 'ro-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'questions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function uploadWorkbook(Employee $manager, UploadedFile $file): TestResponse
{
    return test()->actingAs($manager->user)->post('/recruiter/assessments/questions/import', ['file' => $file]);
}

function importBatchId(TestResponse $response): string
{
    $response->assertSessionHasNoErrors()->assertRedirect();
    preg_match('#/import/([0-9a-f-]{36})$#', (string) $response->headers->get('Location'), $match);

    expect($match)->toHaveKey(1);

    return $match[1];
}

/**
 * @return array<string, mixed>
 */
function importPreview(Employee $manager, string $batch): array
{
    $response = test()->actingAs($manager->user)->get("/recruiter/assessments/questions/import/{$batch}")->assertOk();

    return $response->viewData('page')['props'];
}

function validImportRows(): array
{
    return [
        importRow('What does OPT stand for?', 'single_choice', ['Optional Practical Training', 'Occupational Practical Training', 'Optional Professional Training', 'Operational Practical Training'], 'A', '1', 'OPT stands for Optional Practical Training.'),
        importRow('Which of these are work visas?', 'multiple_choice', ['H-1B', 'B-2', 'L-1', 'F-2'], 'A,C', '2'),
        importRow('STEM OPT can extend OPT.', 'true_false', [], 'True'),
        importRow('Explain the cap-gap extension.', 'short_answer', [], '', '3'),
    ];
}

// Template

test('the template downloads as xlsx with the Questions and Instructions sheets', function () {
    $response = $this->actingAs(importManager()->user)->get('/recruiter/assessments/questions/import/template')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('spreadsheetml');

    $path = tempnam(sys_get_temp_dir(), 'ro-template-').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $book = IOFactory::load($path);
    $headers = $book->getSheetByName('Questions')->rangeToArray('A1:L1')[0];

    expect($book->getSheetByName('Instructions'))->not->toBeNull()
        ->and($headers)->toBe(['Question', 'Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Option F', 'Correct Answer', 'Points', 'Explanation', 'Category']);
});

// Upload, preview, confirm

test('uploading a valid workbook saves nothing until the manager confirms', function () {
    $manager = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook(validImportRows())));

    expect(AssessmentQuestion::query()->count())->toBe(0);

    $props = importPreview($manager, $batch);

    expect($props['counts'])->toMatchArray(['total' => 4, 'valid' => 4, 'errors' => 0, 'duplicates' => 0, 'importable' => 4])
        ->and(collect($props['rows'])->pluck('status')->unique()->all())->toBe(['valid'])
        ->and(AssessmentQuestion::query()->count())->toBe(0);

    $this->actingAs($manager->user)
        ->post("/recruiter/assessments/questions/import/{$batch}/confirm")
        ->assertSessionHasNoErrors()
        ->assertRedirect("/recruiter/assessments/questions?batch={$batch}");

    $questions = AssessmentQuestion::query()->with('options')->orderBy('import_row')->get();

    expect($questions)->toHaveCount(4)
        ->and($questions->pluck('source')->unique()->all())->toBe([QuestionSource::Excel])
        ->and($questions->pluck('import_batch')->unique()->all())->toBe([$batch])
        ->and($questions->pluck('import_row')->all())->toBe([2, 3, 4, 5])
        ->and($questions->pluck('assessment_version_id')->filter()->all())->toBe([]);

    [$single, $multiple, $trueFalse, $short] = $questions->all();

    expect($single->type)->toBe(QuestionType::SingleChoice)
        ->and($single->options)->toHaveCount(4)
        ->and($single->correctOptionIds())->toBe([$single->options[0]->id])
        ->and($single->explanation)->toBe('OPT stands for Optional Practical Training.')
        ->and($single->category)->toBe('Immigration')
        ->and($multiple->points)->toBe(2)
        ->and($multiple->correctOptionIds())->toBe([$multiple->options[0]->id, $multiple->options[2]->id])
        ->and($trueFalse->options->pluck('text')->all())->toBe(['True', 'False'])
        ->and($trueFalse->correctOptionIds())->toBe([$trueFalse->options[0]->id])
        ->and($short->type)->toBe(QuestionType::ShortAnswer)
        ->and($short->options)->toHaveCount(0);

    expect(Activity::query()->where('log_name', 'recruiter-assessments')->where('event', 'imported')->count())->toBe(1);

    $this->actingAs($manager->user)
        ->post("/recruiter/assessments/questions/import/{$batch}/confirm")
        ->assertSessionHasErrors('batch');

    expect(AssessmentQuestion::query()->count())->toBe(4);
});

test('true/false accepts the option letter A or B as well as the words', function () {
    $manager = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([
        importRow('A B-2 visa allows work.', 'true_false', [], 'B'),
    ])));

    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertSessionHasNoErrors();

    $question = AssessmentQuestion::query()->with('options')->sole();

    expect($question->correctOptionIds())->toBe([$question->options[1]->id]);
});

test('each invalid row is reported with its reason and never imported', function (array $row, string $message) {
    $manager = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([validImportRows()[0], $row])));
    $props = importPreview($manager, $batch);
    $bad = collect($props['rows'])->firstWhere('row', 3);

    expect($bad['status'])->toBe('error')
        ->and(implode(' ', $bad['errors']))->toContain($message)
        ->and($props['counts'])->toMatchArray(['valid' => 1, 'errors' => 1, 'importable' => 1]);

    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertSessionHasNoErrors();

    expect(AssessmentQuestion::query()->count())->toBe(1)
        ->and(AssessmentQuestion::query()->sole()->import_row)->toBe(2);
})->with([
    'missing question' => [importRow('', 'single_choice', ['Yes', 'No'], 'A'), 'Question is missing'],
    'invalid type' => [importRow('Pick one.', 'essay', ['Yes', 'No'], 'A'), 'is not recognised'],
    'missing options' => [importRow('Pick one.', 'single_choice', ['Only one'], 'A'), 'at least 2 options'],
    'correct answer points at an empty option' => [importRow('Pick one.', 'single_choice', ['Yes', 'No'], 'D'), 'which is empty'],
    'missing correct answer' => [importRow('Pick one.', 'single_choice', ['Yes', 'No'], ''), 'Correct Answer is missing'],
    'single choice with two answers' => [importRow('Pick one.', 'single_choice', ['Yes', 'No', 'Maybe'], 'A,B'), 'exactly one correct answer'],
    'invalid multiple-choice syntax' => [importRow('Pick some.', 'multiple_choice', ['H-1B', 'L-1', 'B-2'], 'A;C'), 'option letters'],
    'multiple choice repeating a letter' => [importRow('Pick some.', 'multiple_choice', ['H-1B', 'L-1', 'B-2'], 'A,A'), 'same option twice'],
    'non-numeric points' => [importRow('Pick one.', 'single_choice', ['Yes', 'No'], 'A', 'two'), 'Points'],
    'zero points' => [importRow('Pick one.', 'single_choice', ['Yes', 'No'], 'A', '0'), 'Points'],
    'short answer with options' => [importRow('Explain.', 'short_answer', ['Yes', 'No'], ''), 'do not have options'],
    'short answer with a correct answer' => [importRow('Explain.', 'short_answer', [], 'A'), 'Leave Correct Answer empty'],
    'true/false with an invalid answer' => [importRow('H-1B is a visa.', 'true_false', [], 'Maybe'), 'option letters'],
    'true/false with custom options' => [importRow('H-1B is a visa.', 'true_false', ['Yes', 'No'], 'A'), 'True and False'],
    'duplicate options' => [importRow('Pick one.', 'single_choice', ['Yes', ' yes '], 'A'), 'same text'],
    'template example row' => [importRow('EXAMPLE: What does OPT stand for?', 'single_choice', ['Yes', 'No'], 'A'), 'example row'],
]);

test('blank rows are skipped and counted, not reported as errors', function () {
    $manager = importManager();
    [$first, $second] = validImportRows();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([$first, array_fill(0, 10, ''), $second])));
    $props = importPreview($manager, $batch);

    expect($props['counts'])->toMatchArray(['total' => 2, 'valid' => 2, 'errors' => 0, 'blank' => 1])
        ->and(collect($props['rows'])->pluck('row')->all())->toBe([2, 4]);
});

test('a mixed workbook imports only the valid rows', function () {
    $manager = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([
        ...validImportRows(),
        importRow('', 'single_choice', ['Yes', 'No'], 'A'),
        importRow('Pick one.', 'essay', ['Yes', 'No'], 'A'),
    ])));

    expect(importPreview($manager, $batch)['counts'])->toMatchArray(['total' => 6, 'valid' => 4, 'errors' => 2]);

    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '4 questions imported.') && str_contains($message, '2 row(s) with errors'));

    expect(AssessmentQuestion::query()->count())->toBe(4);
});

test('duplicates of bank questions and within the file are flagged, skipped by default and imported only when chosen', function () {
    $manager = importManager();
    AssessmentQuestion::factory()->singleChoice()->create(['prompt' => 'What does OPT stand for?']);
    $rows = [
        importRow('  what does OPT stand   for? ', 'single_choice', ['Optional Practical Training', 'Other'], 'A'),
        importRow('Which of these are work visas?', 'multiple_choice', ['H-1B', 'B-2', 'L-1'], 'A,C'),
        importRow('WHICH of these are work visas?', 'multiple_choice', ['H-1B', 'B-2', 'L-1'], 'A,C'),
    ];

    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook($rows)));
    $props = importPreview($manager, $batch);
    $flags = collect($props['rows'])->pluck('duplicate', 'row');

    expect($props['counts'])->toMatchArray(['valid' => 3, 'duplicates' => 2, 'importable' => 1])
        ->and($flags[2])->toBe('Already in the Question Bank.')
        ->and($flags[3])->toBeNull()
        ->and($flags[4])->toBe('Same question as row 3.');

    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertSessionHasNoErrors();

    expect(AssessmentQuestion::query()->count())->toBe(2);

    $again = importBatchId(uploadWorkbook($manager, questionWorkbook($rows)));
    $this->actingAs($manager->user)
        ->post("/recruiter/assessments/questions/import/{$again}/confirm", ['include_duplicates' => true])
        ->assertSessionHasNoErrors();

    expect(AssessmentQuestion::query()->count())->toBe(5)
        ->and(AssessmentQuestion::query()->where('prompt', 'What does OPT stand for?')->count())->toBe(1);
});

// Structure

test('a workbook without the required columns is refused', function () {
    $manager = importManager();
    $headers = ['Question', 'Type', 'Option A', 'Option B'];

    uploadWorkbook($manager, questionWorkbook([['Pick one.', 'single_choice', 'Yes', 'No']], $headers))
        ->assertSessionHasErrors(['file' => 'The sheet is missing the column(s): Correct Answer. Download the template for the expected layout.']);
});

test('option columns must run in order from Option A', function () {
    $manager = importManager();
    $headers = ['Question', 'Type', 'Option A', 'Option C', 'Correct Answer'];

    uploadWorkbook($manager, questionWorkbook([['Pick one.', 'single_choice', 'Yes', 'No', 'A']], $headers))
        ->assertSessionHasErrors('file');
});

test('extra option columns beyond D are read', function () {
    $manager = importManager();
    $headers = ['Question', 'Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Option F', 'Correct Answer'];
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([['Pick the sixth.', 'single_choice', '1', '2', '3', '4', '5', '6', 'F']], $headers)));

    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertSessionHasNoErrors();

    $question = AssessmentQuestion::query()->with('options')->sole();

    expect($question->options)->toHaveCount(6)
        ->and($question->correctOptionIds())->toBe([$question->options[5]->id]);
});

test('files that are not xlsx or have no question rows are refused', function () {
    $manager = importManager();

    uploadWorkbook($manager, UploadedFile::fake()->create('questions.csv', 1, 'text/csv'))->assertSessionHasErrors('file');
    uploadWorkbook($manager, questionWorkbook([]))->assertSessionHasErrors('file');

    expect(AssessmentQuestion::query()->count())->toBe(0);
});

// Batches

test('an import belongs to the manager who uploaded it and can be cancelled', function () {
    $manager = importManager();
    $other = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook(validImportRows())));

    $this->actingAs($other->user)->get("/recruiter/assessments/questions/import/{$batch}")->assertForbidden();
    $this->actingAs($other->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertForbidden();
    $this->actingAs(importManager(SystemRole::Recruiter)->user)->get("/recruiter/assessments/questions/import/{$batch}")->assertForbidden();

    $this->actingAs($manager->user)->delete("/recruiter/assessments/questions/import/{$batch}")->assertSessionHasNoErrors();
    $this->actingAs($manager->user)->post("/recruiter/assessments/questions/import/{$batch}/confirm")->assertSessionHasErrors('batch');

    expect(AssessmentQuestion::query()->count())->toBe(0);
});

test('the problem report downloads as xlsx', function () {
    $manager = importManager();
    $batch = importBatchId(uploadWorkbook($manager, questionWorkbook([importRow('Pick one.', 'essay', ['Yes', 'No'], 'A')])));

    $response = $this->actingAs($manager->user)->get("/recruiter/assessments/questions/import/{$batch}/errors")->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('spreadsheetml')
        ->and(strlen((string) $response->streamedContent()))->toBeGreaterThan(0);
});
