<?php

namespace App\Modules\RecruiterOperations\Services\Assessments\Import;

/**
 * Flags valid rows whose question matches one already in the Question Bank or
 * an earlier row of the same file. Matching ignores case and extra spaces
 * (AssessmentQuestion::hashPrompt). Pure: the bank hashes are passed in.
 *
 * @phpstan-import-type RowResult from QuestionImportRowValidator
 */
final class ImportDuplicateDetector
{
    /**
     * @param  list<RowResult>  $rows
     * @param  list<string>  $bankHashes
     * @return list<RowResult>
     */
    public static function flag(array $rows, array $bankHashes): array
    {
        $bank = array_flip($bankHashes);
        $seen = [];

        foreach ($rows as $index => $row) {
            $rows[$index]['duplicate'] = null;

            if ($row['status'] !== 'valid' || $row['hash'] === null) {
                continue;
            }

            if (isset($bank[$row['hash']])) {
                $rows[$index]['duplicate'] = 'Already in the Question Bank.';
            } elseif (isset($seen[$row['hash']])) {
                $rows[$index]['duplicate'] = 'Same question as row '.$seen[$row['hash']].'.';
            }

            $seen[$row['hash']] ??= $row['row'];
        }

        return $rows;
    }
}
