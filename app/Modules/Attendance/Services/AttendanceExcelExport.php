<?php

namespace App\Modules\Attendance\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExcelExport
{
    public function __construct(
        protected AttendanceReportService $reports,
    ) {}

    public function monthly(
        int $month,
        int $year,
        ?int $employeeId = null,
        ?int $departmentId = null,
        ?int $officeId = null,
    ): StreamedResponse {
        $report = $this->reports->monthlyReport($month, $year, $employeeId, $departmentId, $officeId);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('VSP CRM')
            ->setTitle('Attendance Report '.$report['label']);

        $this->buildSummarySheet($spreadsheet, $report);
        $this->buildRegisterSheet($spreadsheet, $report, $employeeId, $departmentId, $officeId);
        $this->buildMatrixSheet($spreadsheet, $report);

        $spreadsheet->setActiveSheetIndex(0);
        $filename = sprintf(
            'VSP_Attendance_%s_%d.xlsx',
            \Illuminate\Support\Carbon::create($year, $month, 1)->format('F'),
            $year,
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function buildSummarySheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly Summary');

        $this->writeReportHeader($sheet, 'Monthly Attendance Summary', $report['label']);

        $summary = $report['summary'];
        $metaRow = 5;
        $sheet->setCellValue("A{$metaRow}", 'Total Employees');
        $sheet->setCellValue("B{$metaRow}", $summary['total_employees']);
        $sheet->setCellValue("D{$metaRow}", 'Working Days');
        $sheet->setCellValue("E{$metaRow}", $summary['working_days']);
        $metaRow++;
        $sheet->setCellValue("A{$metaRow}", 'Present');
        $sheet->setCellValue("B{$metaRow}", $summary['present']);
        $sheet->setCellValue("D{$metaRow}", 'Absent');
        $sheet->setCellValue("E{$metaRow}", $summary['absent']);
        $metaRow++;
        $sheet->setCellValue("A{$metaRow}", 'Late');
        $sheet->setCellValue("B{$metaRow}", $summary['late']);
        $sheet->setCellValue("D{$metaRow}", 'Week Off');
        $sheet->setCellValue("E{$metaRow}", $summary['week_off']);
        $metaRow++;
        $sheet->setCellValue("A{$metaRow}", 'Average Working Hours');
        $sheet->setCellValue("B{$metaRow}", $summary['average_working_hours']);

        $headers = [
            'Employee ID',
            'Employee Name',
            'Department',
            'Office',
            'Present',
            'Absent',
            'Late',
            'Week Off',
            'Total Working Days',
            'Total Net Hours',
            'Average Hours/Day',
        ];
        $headerRow = 10;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $rowIndex = $headerRow + 1;
        foreach ($report['rows'] as $row) {
            $presentDays = $row['totals']['present'] + $row['totals']['late'];
            $sheet->fromArray([
                $row['employee_code'],
                $row['employee'],
                $row['department'],
                $row['office'],
                $row['totals']['present'],
                $row['totals']['absent'],
                $row['totals']['late'],
                $row['totals']['week_off'],
                $presentDays,
                round($row['totals']['net_seconds'] / 3600, 2),
                $row['totals']['average_hours'],
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        $lastRow = max($headerRow, $rowIndex - 1);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $this->styleTable($sheet, "A{$headerRow}:{$lastColumn}{$lastRow}", true);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");

        for ($column = 1; $column <= count($headers); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function buildRegisterSheet(
        Spreadsheet $spreadsheet,
        array $report,
        ?int $employeeId,
        ?int $departmentId,
        ?int $officeId,
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Attendance Register');

        $this->writeReportHeader($sheet, 'Attendance Register', $report['label']);

        $headers = [
            'Employee ID',
            'Employee Name',
            'Date',
            'Day',
            'Check In',
            'Check Out',
            'Break Time',
            'Net Working Hours',
            'Status',
        ];
        $headerRow = 5;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $rowIndex = $headerRow + 1;
        foreach ($report['rows'] as $row) {
            $detail = $this->reports->employeeMonthlyDetail(
                $row['employee_id'],
                $report['month'],
                $report['year'],
            );

            foreach ($detail['records'] as $record) {
                $sheet->fromArray([
                    $record['employee_code'],
                    $record['employee'],
                    $record['date'],
                    $record['day'],
                    $record['check_in_at'] ? \Illuminate\Support\Carbon::parse($record['check_in_at'])->timezone(config('app.timezone'))->format('H:i') : '',
                    $record['check_out_at'] ? \Illuminate\Support\Carbon::parse($record['check_out_at'])->timezone(config('app.timezone'))->format('H:i') : '',
                    $this->formatHours($record['total_break_seconds']),
                    $this->formatHours($record['net_working_seconds']),
                    $record['report_label'] !== '' ? $record['report_label'] : $record['status_label'],
                ], null, "A{$rowIndex}");
                $rowIndex++;
            }
        }

        $lastRow = max($headerRow, $rowIndex - 1);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $this->styleTable($sheet, "A{$headerRow}:{$lastColumn}{$lastRow}", true);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");

        for ($column = 1; $column <= count($headers); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function buildMatrixSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Monthly Matrix');

        $this->writeReportHeader($sheet, 'Monthly Attendance Matrix', $report['label']);

        $headers = ['Employee ID', 'Employee Name'];
        foreach ($report['days'] as $day) {
            $headers[] = sprintf('%02d-%s', $day['day'], \Illuminate\Support\Carbon::parse($day['date'])->format('M'));
        }
        $headers = array_merge($headers, ['Present', 'Absent', 'Late', 'Off']);

        $headerRow = 5;
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $rowIndex = $headerRow + 1;
        foreach ($report['rows'] as $row) {
            $line = [$row['employee_code'], $row['employee']];

            foreach ($row['days'] as $day) {
                $line[] = $day['code'];
            }

            $line[] = $row['totals']['present'];
            $line[] = $row['totals']['absent'];
            $line[] = $row['totals']['late'];
            $line[] = $row['totals']['week_off'];
            $sheet->fromArray($line, null, "A{$rowIndex}");

            $columnIndex = 3;
            foreach ($row['days'] as $day) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex).$rowIndex;
                $this->applyStatusStyle($sheet, $cell, $day['code'], $day['is_weekend']);
                $columnIndex++;
            }

            $rowIndex++;
        }

        $lastRow = max($headerRow, $rowIndex - 1);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $this->styleTable($sheet, "A{$headerRow}:{$lastColumn}{$lastRow}", true);

        foreach ($report['days'] as $index => $day) {
            $column = Coordinate::stringFromColumnIndex($index + 3);
            if ($day['is_weekend']) {
                $sheet->getStyle("{$column}{$headerRow}:{$column}{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'],
                    ],
                ]);
            }
            $sheet->getColumnDimension($column)->setWidth(8);
        }

        $sheet->freezePane('C'.($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    protected function writeReportHeader(Worksheet $sheet, string $title, string $periodLabel): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'VSP CRM');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4F46E5']],
        ]);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $title);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
        ]);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'Period: '.$periodLabel);

        $sheet->mergeCells('A4:F4');
        $sheet->setCellValue(
            'A4',
            'Generated: '.now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        );
    }

    protected function styleTable(Worksheet $sheet, string $range, bool $freezeHeader = false): void
    {
        [$start, $end] = explode(':', $range);
        $headerEnd = preg_replace('/\d+$/', '1', $start);
        if (preg_match('/(\d+)$/', $start, $matches)) {
            $headerEnd = preg_replace('/\d+$/', $matches[0], $start);
        }

        preg_match('/(\d+)$/', $start, $startMatch);
        $headerRow = $startMatch[1] ?? '1';
        $headerRange = preg_replace('/\d+$/', $headerRow, $start).':'.preg_replace('/\d+$/', $headerRow, $end);

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

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    protected function applyStatusStyle(Worksheet $sheet, string $cell, string $code, bool $isWeekend): void
    {
        $styles = match ($code) {
            'P' => ['fill' => 'DCFCE7', 'font' => '166534'],
            'A' => ['fill' => 'FEE2E2', 'font' => '991B1B'],
            'L' => ['fill' => 'FFEDD5', 'font' => '9A3412'],
            'OFF' => ['fill' => 'FEF3C7', 'font' => '92400E'],
            default => $isWeekend ? ['fill' => 'FEF3C7', 'font' => '92400E'] : null,
        };

        if ($styles === null) {
            return;
        }

        $sheet->getStyle($cell)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $styles['fill']],
            ],
            'font' => ['bold' => true, 'color' => ['rgb' => $styles['font']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    protected function formatHours(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '';
        }

        return number_format($seconds / 3600, 2);
    }
}
