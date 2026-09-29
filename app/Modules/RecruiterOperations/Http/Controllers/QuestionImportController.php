<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Http\Requests\QuestionImportRequest;
use App\Modules\RecruiterOperations\Services\Assessments\Import\QuestionImportService;
use App\Modules\RecruiterOperations\Services\Assessments\Import\QuestionSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel import into the Question Bank: template download, upload, preview,
 * confirm or cancel, and the problem report. Nothing is saved before confirm.
 */
class QuestionImportController extends Controller
{
    public function __construct(protected QuestionImportService $imports) {}

    public function create(): Response
    {
        return Inertia::render('RecruiterOperations/assessments/bank/import', [
            'limits' => [
                'max_rows' => QuestionSpreadsheet::MAX_ROWS,
                'max_kb' => QuestionImportRequest::MAX_KB,
            ],
            'headers' => QuestionSpreadsheet::templateHeaders(),
        ]);
    }

    public function template(): StreamedResponse
    {
        return $this->imports->template();
    }

    public function store(QuestionImportRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        $batchId = $this->imports->upload($file, $request->user());

        return to_route('recruiter.assessments.questions.import.show', $batchId);
    }

    public function show(Request $request, string $batch): Response|RedirectResponse
    {
        $data = $this->imports->batch($batch, $request->user());

        return Inertia::render('RecruiterOperations/assessments/bank/import-preview', [
            'batch' => [
                'id' => $data['id'],
                'file_name' => $data['file_name'],
                'uploaded_at' => $data['uploaded_at'],
                'option_letters' => $data['option_letters'],
            ],
            'counts' => $this->imports->counts($data),
            'rows' => array_map(fn (array $row) => [
                'row' => $row['row'],
                'status' => $row['status'],
                'errors' => $row['errors'],
                'duplicate' => $row['duplicate'],
                'question' => $row['question'] !== null ? [
                    'type' => $row['question']['type'],
                    'prompt' => $row['question']['prompt'],
                    'points' => $row['question']['points'],
                    'category' => $row['question']['category'],
                    'options' => $row['question']['options'],
                ] : null,
                'values' => $row['values'],
            ], $data['rows']),
        ]);
    }

    public function confirm(Request $request, string $batch): RedirectResponse
    {
        $result = $this->imports->confirm($batch, $request->boolean('include_duplicates'), $request->user());

        $message = $result['imported'] === 1 ? '1 question imported.' : "{$result['imported']} questions imported.";

        if ($result['skipped_errors'] > 0) {
            $message .= " {$result['skipped_errors']} row(s) with errors were not imported.";
        }

        if ($result['skipped_duplicates'] > 0) {
            $message .= " {$result['skipped_duplicates']} duplicate(s) skipped.";
        }

        return to_route('recruiter.assessments.questions.index', ['batch' => $result['batch']])->with('success', $message);
    }

    public function cancel(Request $request, string $batch): RedirectResponse
    {
        $this->imports->cancel($batch, $request->user());

        return to_route('recruiter.assessments.questions.import.create')->with('success', 'Import cancelled. Nothing was saved.');
    }

    public function errors(Request $request, string $batch): StreamedResponse
    {
        return $this->imports->errorReport($batch, $request->user());
    }
}
