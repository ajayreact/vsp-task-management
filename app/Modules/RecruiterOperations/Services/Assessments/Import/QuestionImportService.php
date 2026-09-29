<?php

namespace App\Modules\RecruiterOperations\Services\Assessments\Import;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentQuestionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel import into the Question Bank, in two steps:
 *
 * 1. upload: the file is read and every row checked; the outcome is kept for
 *    the uploader only (cache, 2 hours). Nothing is written to the bank.
 * 2. confirm: valid rows are imported in one transaction. Rows with errors are
 *    never imported. Duplicates (same wording as a bank question or an
 *    earlier row) are skipped unless the uploader chooses to include them.
 *
 * Imported questions record source=excel, the batch id and the source row.
 *
 * @phpstan-import-type RowResult from QuestionImportRowValidator
 *
 * @phpstan-type ImportBatch array{id: string, user_id: int, file_name: string, uploaded_at: string, option_letters: list<string>, blank_rows: int, rows: list<RowResult>}
 */
class QuestionImportService
{
    public const TTL_MINUTES = 120;

    public function __construct(protected AssessmentQuestionService $questions) {}

    public function template(): StreamedResponse
    {
        return $this->download(QuestionSpreadsheet::template(), 'recruiter-question-import-template.xlsx');
    }

    /**
     * Reads and checks the file, and returns the batch id for the preview.
     */
    public function upload(UploadedFile $file, User $actor): string
    {
        $this->questions->ensureManager($actor);

        $sheet = QuestionSpreadsheet::read((string) $file->getRealPath());
        $rows = [];
        $blank = 0;

        foreach ($sheet['rows'] as $row) {
            if (QuestionImportRowValidator::isBlank($row['values'])) {
                $blank++;

                continue;
            }

            $rows[] = QuestionImportRowValidator::validate($row['row'], $row['values'], $sheet['option_letters']);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no question rows.']);
        }

        $batch = [
            'id' => (string) Str::uuid(),
            'user_id' => $actor->id,
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 150),
            'uploaded_at' => now()->toIso8601String(),
            'option_letters' => $sheet['option_letters'],
            'blank_rows' => $blank,
            'rows' => ImportDuplicateDetector::flag($rows, $this->bankHashes($rows)),
        ];

        Cache::put($this->key($batch['id']), $batch, now()->addMinutes(self::TTL_MINUTES));

        return $batch['id'];
    }

    /**
     * @return ImportBatch
     */
    public function batch(string $batchId, User $actor): array
    {
        $this->questions->ensureManager($actor);

        /** @var ImportBatch|null $batch */
        $batch = Str::isUuid($batchId) ? Cache::get($this->key($batchId)) : null;

        if (! is_array($batch)) {
            throw ValidationException::withMessages(['batch' => 'This import has expired or was already finished. Upload the file again.']);
        }

        if ($batch['user_id'] !== $actor->id) {
            throw new AuthorizationException('This import belongs to someone else.');
        }

        return $batch;
    }

    /**
     * @param  ImportBatch  $batch
     * @return array{total: int, valid: int, errors: int, duplicates: int, blank: int, importable: int}
     */
    public function counts(array $batch, bool $includeDuplicates = false): array
    {
        $valid = array_filter($batch['rows'], fn (array $row) => $row['status'] === 'valid');
        $duplicates = array_filter($valid, fn (array $row) => $row['duplicate'] !== null);

        return [
            'total' => count($batch['rows']),
            'valid' => count($valid),
            'errors' => count($batch['rows']) - count($valid),
            'duplicates' => count($duplicates),
            'blank' => $batch['blank_rows'],
            'importable' => $includeDuplicates ? count($valid) : count($valid) - count($duplicates),
        ];
    }

    /**
     * Imports the valid rows. Duplicates are checked again against the bank as
     * it is now.
     *
     * @return array{batch: string, imported: int, skipped_errors: int, skipped_duplicates: int}
     */
    public function confirm(string $batchId, bool $includeDuplicates, User $actor): array
    {
        $batch = $this->batch($batchId, $actor);

        return Cache::lock($this->key($batchId).':confirm', 30)->block(10, function () use ($batchId, $batch, $includeDuplicates, $actor) {
            if (! Cache::has($this->key($batchId))) {
                throw ValidationException::withMessages(['batch' => 'This import was already finished.']);
            }

            $rows = ImportDuplicateDetector::flag($batch['rows'], $this->bankHashes($batch['rows']));
            $importable = array_values(array_filter($rows, fn (array $row) => $row['status'] === 'valid'
                && $row['question'] !== null
                && ($includeDuplicates || $row['duplicate'] === null)));

            if ($importable === []) {
                throw ValidationException::withMessages(['batch' => 'There are no rows to import. Fix the errors in the file and upload it again.']);
            }

            $imported = DB::transaction(function () use ($importable, $batch, $actor) {
                foreach ($importable as $row) {
                    /** @var array{type: string, prompt: string, points: int, explanation: string|null, category: string|null, options: list<array{text: string, correct: bool}>} $question */
                    $question = $row['question'];

                    $this->questions->store(
                        null,
                        QuestionType::from($question['type']),
                        $question['prompt'],
                        $question['points'],
                        $question['explanation'],
                        $question['category'],
                        $question['options'],
                        $actor,
                        ['source' => QuestionSource::Excel, 'import_batch' => $batch['id'], 'import_row' => $row['row']],
                    );
                }

                return count($importable);
            });

            $valid = count(array_filter($rows, fn (array $row) => $row['status'] === 'valid'));
            $result = [
                'batch' => $batch['id'],
                'imported' => $imported,
                'skipped_errors' => count($rows) - $valid,
                'skipped_duplicates' => $valid - $imported,
            ];

            activity('recruiter-assessments')
                ->causedBy($actor)
                ->withProperties([...$result, 'file_name' => $batch['file_name'], 'include_duplicates' => $includeDuplicates])
                ->event('imported')
                ->log('Imported questions into the Question Bank from Excel');

            Cache::forget($this->key($batchId));

            return $result;
        });
    }

    public function cancel(string $batchId, User $actor): void
    {
        $this->batch($batchId, $actor);

        Cache::forget($this->key($batchId));
    }

    public function errorReport(string $batchId, User $actor): StreamedResponse
    {
        $batch = $this->batch($batchId, $actor);
        $rows = array_values(array_filter($batch['rows'], fn (array $row) => $row['status'] !== 'valid' || $row['duplicate'] !== null));

        return $this->download(QuestionSpreadsheet::errorReport($batch['option_letters'], $rows), 'question-import-problems.xlsx');
    }

    // Internals

    /**
     * @param  list<RowResult>  $rows
     * @return list<string>
     */
    protected function bankHashes(array $rows): array
    {
        $hashes = array_values(array_unique(array_filter(array_map(fn (array $row) => $row['hash'], $rows))));

        if ($hashes === []) {
            return [];
        }

        return AssessmentQuestion::query()
            ->bank()
            ->whereIn('prompt_hash', $hashes)
            ->pluck('prompt_hash')
            ->map(fn ($hash) => (string) $hash)
            ->unique()
            ->values()
            ->all();
    }

    protected function key(string $batchId): string
    {
        return 'ro-assessment-import:'.$batchId;
    }

    protected function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
