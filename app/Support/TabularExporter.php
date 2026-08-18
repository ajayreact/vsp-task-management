<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared bordered Excel / PDF exporter for DataTable list pages.
 */
class TabularExporter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function excel(string $title, array $headers, array $rows, ?string $filename = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_map(fn ($value) => $value ?? '', $row), null, "A{$rowIndex}");
            $rowIndex++;
        }

        $columnCount = max(1, count($headers));
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $lastRow = max(1, $rowIndex - 1);
        $range = "A1:{$lastColumn}{$lastRow}";

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '78736E'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        for ($i = 1; $i <= $columnCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $downloadName = ($filename ?? str($title)->slug().'-'.now()->format('Y-m-d-His')).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function pdf(string $title, array $headers, array $rows, ?string $filename = null)
    {
        $downloadName = ($filename ?? str($title)->slug().'-'.now()->format('Y-m-d-His')).'.pdf';

        return Pdf::loadView('exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape')->download($downloadName);
    }
}
