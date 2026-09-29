<?php

namespace App\Modules\RecruiterOperations\Services\Assessments\Import;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads and writes the question import workbook with PhpSpreadsheet.
 *
 * Layout: a "Questions" sheet with a header row. Required columns are
 * Question, Type and Correct Answer; Option A, Option B, ... columns are
 * detected dynamically (any number); Points, Explanation and Category are
 * optional. Header matching ignores case and extra spaces.
 */
final class QuestionSpreadsheet
{
    public const SHEET = 'Questions';

    public const MAX_ROWS = 1000;

    public const REQUIRED_HEADERS = ['question', 'type', 'correct answer'];

    public const OPTIONAL_HEADERS = ['points', 'explanation', 'category'];

    public const TEMPLATE_OPTIONS = ['A', 'B', 'C', 'D', 'E', 'F'];

    /**
     * @return array{option_letters: list<string>, rows: list<array{row: int, values: array<string, string>}>}
     */
    public static function read(string $path): array
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'The file could not be read. Upload an .xlsx file made from the template.']);
        }

        $sheet = $spreadsheet->getSheetByName(self::SHEET) ?? $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow - 1 > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'The file has more than '.self::MAX_ROWS.' question rows. Split it into smaller files.']);
        }

        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $columns = [];
        $letters = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $header = self::normalizeHeader(self::cellText($sheet, $column, 1));

            if ($header === '' || isset(array_flip($columns)[$header])) {
                continue;
            }

            if (preg_match('/^option ([a-z])$/', $header, $match) === 1) {
                $letters[] = strtoupper($match[1]);
            } elseif (! in_array($header, [...self::REQUIRED_HEADERS, ...self::OPTIONAL_HEADERS], true)) {
                continue;
            }

            $columns[$column] = $header;
        }

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $columns));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'The sheet is missing the column(s): '.implode(', ', array_map('ucwords', $missing)).'. Download the template for the expected layout.',
            ]);
        }

        sort($letters);
        $expected = array_slice(range('A', 'Z'), 0, count($letters));

        if ($letters !== $expected) {
            throw ValidationException::withMessages(['file' => 'Option columns must run in order from Option A (for example Option A, Option B, Option C).']);
        }

        $rows = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $values = [];

            foreach ($columns as $column => $header) {
                $values[$header] = self::cellText($sheet, $column, $row);
            }

            $rows[] = ['row' => $row, 'values' => $values];
        }

        return ['option_letters' => $letters, 'rows' => $rows];
    }

    /**
     * The blank template with an Instructions sheet and example rows (which
     * the importer refuses, so they cannot be imported by mistake).
     */
    public static function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET);

        $headers = self::templateHeaders();
        $sheet->fromArray($headers, null, 'A1');
        self::styleHeader($sheet, count($headers));

        $examples = [
            ['EXAMPLE: What does OPT stand for?', 'single_choice', 'Optional Practical Training', 'Occupational Practical Training', 'Official Placement Training', '', '', '', 'A', '1', 'OPT is work authorization related to an F-1 student\'s field of study.', 'Immigration'],
            ['EXAMPLE: Which of these are U.S. payroll taxes?', 'multiple_choice', 'Social Security', 'Medicare', 'GST', 'FUTA', '', '', 'A,B,D', '2', 'GST is not a U.S. payroll tax.', 'Payroll'],
            ['EXAMPLE: C2C means Corp-to-Corp.', 'true_false', 'True', 'False', '', '', '', '', 'A', '1', '', 'Staffing'],
            ['EXAMPLE: Describe how you would confirm a consultant\'s availability.', 'short_answer', '', '', '', '', '', '', '', '3', 'Reviewed manually.', 'Communication'],
        ];
        $sheet->fromArray($examples, null, 'A2');
        $sheet->getStyle('A2:'.Coordinate::stringFromColumnIndex(count($headers)).'5')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setWidth($column === 1 ? 60 : ($column === 11 ? 45 : 22));
        }

        $sheet->freezePane('A2');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $lines = [
            ['Recruiter quiz question import'],
            [''],
            ['1. Fill in the Questions sheet, one question per row. Delete the grey EXAMPLE rows first; rows starting with "EXAMPLE:" are refused.'],
            ['2. Type: single_choice, multiple_choice, true_false or short_answer.'],
            ['3. Options: fill Option A, Option B, ... in order without gaps. Add more columns (Option G, Option H, ...) if you need them.'],
            ['4. Correct Answer: the option letter(s), for example A, or A,C for multiple choice. True/false may also use True or False.'],
            ['5. True/false: leave the options empty (True and False are filled in) or enter True and False as Option A and Option B.'],
            ['6. Short answer: leave options and Correct Answer empty. These answers are reviewed manually by a reviewer.'],
            ['7. Points: a whole number from 1 to 100. Empty means 1 point.'],
            ['8. Explanation and Category are optional. Categories are matched to existing ones ignoring case.'],
            ['9. At most '.self::MAX_ROWS.' question rows per file, .xlsx only.'],
            ['10. After uploading you will see a preview. Nothing is saved until you confirm, and rows with errors are never imported.'],
            ['11. Questions that already exist in the Question Bank (same wording, ignoring case and spaces) are flagged and skipped unless you choose to include them.'],
        ];
        $instructions->fromArray($lines, null, 'A1');
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructions->getColumnDimension('A')->setWidth(130);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * The rows that were not imported, with the reason, laid out like the
     * template so they can be fixed and uploaded again.
     *
     * @param  list<string>  $optionLetters
     * @param  list<array{row: int, errors: list<string>, duplicate: string|null, values: array<string, string>}>  $rows
     */
    public static function errorReport(array $optionLetters, array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET);

        $keys = ['question', 'type', ...array_map(fn (string $letter) => 'option '.strtolower($letter), $optionLetters), 'correct answer', 'points', 'explanation', 'category'];
        $headers = [...array_map(fn (string $key) => ucwords($key), $keys), 'Source Row', 'Problems'];
        $sheet->fromArray($headers, null, 'A1');
        self::styleHeader($sheet, count($headers));

        $line = 2;

        foreach ($rows as $row) {
            $problems = $row['errors'];

            if ($row['duplicate'] !== null) {
                $problems[] = 'Duplicate: '.$row['duplicate'];
            }

            $cells = array_map(fn (string $key) => $row['values'][$key] ?? '', $keys);
            $cells[] = (string) $row['row'];
            $cells[] = implode(' ', $problems);

            foreach ($cells as $index => $value) {
                $sheet->setCellValueExplicit([$index + 1, $line], $value, DataType::TYPE_STRING);
            }

            $line++;
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setWidth($column === 1 || $column === count($headers) ? 60 : 20);
        }

        return $spreadsheet;
    }

    /**
     * @return list<string>
     */
    public static function templateHeaders(): array
    {
        return ['Question', 'Type', ...array_map(fn (string $letter) => "Option {$letter}", self::TEMPLATE_OPTIONS), 'Correct Answer', 'Points', 'Explanation', 'Category'];
    }

    public static function normalizeHeader(string $header): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $header)));
    }

    protected static function cellText(Worksheet $sheet, int $column, int $row): string
    {
        $value = $sheet->getCell([$column, $row])->getValue();

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'True' : 'False';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        return trim(is_scalar($value) ? (string) $value : '');
    }

    protected static function styleHeader(Worksheet $sheet, int $columns): void
    {
        $range = 'A1:'.Coordinate::stringFromColumnIndex($columns).'1';
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    }
}
