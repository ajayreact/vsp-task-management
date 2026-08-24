<?php

use App\Modules\Core\Models\Employee;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutVite();
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));
    Employee::factory()->create(['employee_code' => 'EMP-URL-VERIFY']);
});

afterEach(function () {
    Carbon::setTestNow();
});

function assertCombinedDashboard($page)
{
    $page
        ->component('Attendance/dashboard')
        ->has('snapshot.overview', 6)
        ->has('dailyTable.records')
        ->has('monthlyReport.rows')
        ->has('monthlyReport.days')
        ->has('monthlyReport.summary')
        ->has('filterOptions.employees');

    return $page;
}

test('attendance dashboard url /admin/attendance loads all sections', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => assertCombinedDashboard($page)
            ->where('snapshot.is_today', true)
            ->where('monthlyReport.month', 8)
            ->where('monthlyReport.year', 2026));
});

test('attendance dashboard url with historical date loads all sections', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date=2026-08-20')
        ->assertOk()
        ->assertInertia(fn ($page) => assertCombinedDashboard($page)
            ->where('snapshot.is_today', false)
            ->where('dailyTable.filter.date', '2026-08-20')
            ->where('monthlyReport.month', 8));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date=2026-08-24')
        ->assertOk()
        ->assertInertia(fn ($page) => assertCombinedDashboard($page)
            ->where('snapshot.is_today', true)
            ->where('dailyTable.filter.date', '2026-08-24'));
});

test('attendance dashboard legacy monthly tab url loads all sections', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/attendance?tab=monthly&month=8&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => assertCombinedDashboard($page)
            ->where('monthlyReport.month', 8)
            ->where('monthlyReport.year', 2026)
            ->missing('tab'));
});

test('attendance dashboard legacy daily tab url loads all sections', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/attendance?tab=daily&date=2026-08-24')
        ->assertOk()
        ->assertInertia(fn ($page) => assertCombinedDashboard($page)
            ->where('snapshot.is_today', true)
            ->where('dailyTable.filter.date', '2026-08-24')
            ->where('monthlyReport.month', 8));
});

test('monthly report payload includes weekend off codes and summary totals', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026')
        ->assertOk()
        ->assertInertia(function ($page) {
            $report = $page->toArray()['props']['monthlyReport'];
            $days = collect($report['rows'][0]['days']);

            expect($days->firstWhere('date', '2026-08-01')['code'])->toBe('OFF')
                ->and($days->firstWhere('date', '2026-08-02')['code'])->toBe('OFF')
                ->and($report['summary'])->toHaveKeys([
                    'total_employees',
                    'working_days',
                    'present',
                    'absent',
                    'late',
                    'week_off',
                    'average_working_hours',
                ]);
        });
});

test('excel export for august 2026 returns three formatted sheets', function () {
    $export = app(\App\Modules\Attendance\Services\AttendanceExcelExport::class);
    $response = $export->monthly(8, 2026);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vsp-att-verify.xlsx';
    file_put_contents($path, $content);

    $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);

    expect($book->getSheetCount())->toBe(3)
        ->and($book->getSheet(0)->getTitle())->toBe('Monthly Summary')
        ->and($book->getSheet(1)->getTitle())->toBe('Attendance Register')
        ->and($book->getSheet(2)->getTitle())->toBe('Monthly Matrix');

    $matrix = $book->getSheetByName('Monthly Matrix');
    expect($matrix->getCell('C6')->getStyle()->getFill()->getStartColor()->getRGB())->not->toBe('FFFFFF');

    unlink($path);
});
