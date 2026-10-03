<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingSpeechService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('a table in the lesson is read row by row, without the header or the pipes', function () {
    $lesson = TrainingLesson::factory()->forVersion(TrainingCourseVersion::factory()->published()->create(), 1)->create([
        'title' => 'State Abbreviations & Codes',
        'description' => null,
        'body' => "State codes\n| State | Code |\n| --- | --- |\n| Alabama | AL |\n| Alaska | AK |\n\nCodes that are easy to confuse",
    ]);

    $speech = app(TrainingSpeechService::class);
    $text = $speech->lessonText($lesson);

    expect($text)->toBe("State Abbreviations & Codes.\n\nState codes\nAlabama, AL. Alaska, AK.\n\nCodes that are easy to confuse")
        ->and($text)->not->toContain('|')
        ->and($speech->segments($text))->toContain('Alabama, AL. Alaska, AK.');
});

test('the recruiter lesson page receives the table text unchanged', function () {
    $employee = Employee::factory()->create();
    $employee->user->syncRoles(SystemRole::Recruiter->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $version = TrainingCourseVersion::factory()->published()->create();
    $body = "| State | Code |\n| --- | --- |\n| Texas | TX |";
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['body' => $body]);
    TrainingAssignment::factory()->forVersion($version)->forEmployee($employee)->create();

    $this->actingAs($employee->user)
        ->get("/recruiter/training/courses/{$version->course_id}/lessons/{$lesson->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.body', $body));
});

test('the state codes lesson lists every state and D.C. as a table', function () {
    $body = collect(RecruiterTrainingContent::all())->where('level', 1)->pluck('lessons')->collapse()->get('State Abbreviations & Codes');
    $rows = array_values(array_filter(explode("\n", $body), fn (string $line) => str_starts_with(trim($line), '|')));

    expect($rows[0])->toBe('| State | Code |')
        ->and($rows[1])->toBe('| --- | --- |')
        ->and(count($rows) - 2)->toBe(51)
        ->and($body)->toContain('| District of Columbia | DC |');
});
