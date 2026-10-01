<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingContentPopulator;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\RecruiterTrainingCurriculumSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RecruiterTrainingCurriculumSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function contentPopulationEmployee(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * @return array<int, array{level: int, course: string, lessons: list<string>}>
 */
function seededCurriculumOutline(): array
{
    $seeder = new RecruiterTrainingCurriculumSeeder;
    $method = new ReflectionMethod($seeder, 'curriculum');

    return $method->invoke($seeder);
}

function courseForLevel(int $level): TrainingCourse
{
    $title = seededCurriculumOutline()[$level]['course'];

    return TrainingCourse::query()->where('title', $title)->sole();
}

test('every curriculum lesson across all ten levels has written content that matches its title', function () {
    $content = collect(RecruiterTrainingContent::all())->keyBy('level');

    expect($content->keys()->sort()->values()->all())->toBe(range(1, 10));

    foreach (seededCurriculumOutline() as $level => $definition) {
        expect($content[$level]['course'])->toBe($definition['course'])
            ->and(array_keys($content[$level]['lessons']))->toEqualCanonicalizing($definition['lessons']);
    }
});

test('the written content is plain, speakable text without answer keys', function () {
    $maxCharacters = (int) config('recruiter-training.speech.max_characters', 20000);

    foreach (RecruiterTrainingContent::all() as $entry) {
        foreach ($entry['lessons'] as $title => $body) {
            $words = TrainingContentPopulator::wordCount($body);

            expect($words)->toBeGreaterThanOrEqual(300, "{$title} is too short")
                ->and($words)->toBeLessThanOrEqual(1200, "{$title} is too long")
                ->and(mb_strlen($title.$body))->toBeLessThan($maxCharacters)
                ->and(preg_match('/[•→↔*#|]/u', $body))->toBe(0, "{$title} has symbols that would be read aloud")
                ->and(stripos($body, 'answer key'))->toBeFalse()
                ->and($body)->toContain('Learning objective')
                ->and($body)->toContain('Key takeaway')
                ->and(TrainingContentPopulator::isPlaceholder($body))->toBeFalse();
        }
    }
});

test('immigration and payroll lessons carry the awareness note, and calling lessons flag company-specific statements', function () {
    $content = collect(RecruiterTrainingContent::all())->keyBy('level');

    foreach ($content[2]['lessons'] as $title => $body) {
        expect($body)->toStartWith('Important note', "{$title} is missing the awareness note");
    }

    $calling = implode("\n", $content[7]['lessons']);

    expect($calling)->toContain('Company-specific process')
        ->and($calling)->toContain('Verify with HR or authorized personnel')
        ->and($calling)->not->toContain('we guarantee');
});

test('the command fills every empty lesson without creating, removing or renaming anything', function () {
    $before = TrainingLesson::query()->orderBy('id')->get(['id', 'course_version_id', 'title', 'slug', 'sort_order'])->toArray();
    $counts = [TrainingCategory::query()->count(), TrainingCourse::query()->count(), TrainingCourseVersion::query()->count()];

    expect($before)->toHaveCount(183)
        ->and(TrainingLesson::query()->whereNull('body')->count())->toBe(183);

    $this->artisan('recruiter:training-content')
        ->expectsOutputToContain('Populated: 183')
        ->expectsOutputToContain('Still empty: 0')
        ->assertSuccessful();

    expect(TrainingLesson::query()->orderBy('id')->get(['id', 'course_version_id', 'title', 'slug', 'sort_order'])->toArray())->toBe($before)
        ->and([TrainingCategory::query()->count(), TrainingCourse::query()->count(), TrainingCourseVersion::query()->count()])->toBe($counts)
        ->and(TrainingLesson::query()->get()->filter(fn (TrainingLesson $lesson) => TrainingContentPopulator::isPlaceholder($lesson->body)))->toBeEmpty()
        ->and(TrainingCourseVersion::query()->where('status', '!=', TrainingContentStatus::Draft->value)->count())->toBe(0);

    foreach (seededCurriculumOutline() as $level => $definition) {
        $course = courseForLevel($level);

        expect($course->category->level_number)->toBe($level)
            ->and($course->versions()->sole()->lessons()->count())->toBe(count($definition['lessons']));
    }
});

test('a dry run reports the changes but saves nothing', function () {
    $this->artisan('recruiter:training-content', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run, nothing saved.')
        ->expectsOutputToContain('Populated: 183')
        ->assertSuccessful();

    expect(TrainingLesson::query()->whereNull('body')->count())->toBe(183);
});

test('running it again changes nothing and keeps edited text', function () {
    $populator = app(TrainingContentPopulator::class);
    $populator->populate(RecruiterTrainingContent::all());

    $edited = courseForLevel(7)->versions()->sole()->lessons()->where('title', 'Mock Calling')->sole();
    $edited->forceFill(['body' => 'Our own mock calling guide, written by the training team.'])->save();
    $snapshot = TrainingLesson::query()->orderBy('id')->pluck('body', 'id')->all();

    $report = collect($populator->populate(RecruiterTrainingContent::all()));

    expect($report)->toHaveCount(183)
        ->and($report->pluck('status')->unique()->values()->all())->toBe([TrainingContentPopulator::KEPT])
        ->and(TrainingLesson::query()->orderBy('id')->pluck('body', 'id')->all())->toBe($snapshot)
        ->and($edited->fresh()->body)->toBe('Our own mock calling guide, written by the training team.');
});

test('meaningful content is kept and placeholder text is replaced', function () {
    $lessons = courseForLevel(1)->versions()->sole()->lessons()->orderBy('sort_order')->get();
    $lessons[0]->forceFill(['body' => 'A manager wrote this lesson about the United States.'])->save();
    $lessons[1]->forceFill(['body' => 'The lesson text recruiters read and listen to.'])->save();
    $lessons[2]->forceFill(['body' => "   \n  "])->save();
    $lessons[3]->forceFill(['body' => 'TBD'])->save();

    $report = collect(app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all()))->keyBy('lesson');

    expect($lessons[0]->fresh()->body)->toBe('A manager wrote this lesson about the United States.')
        ->and($report[$lessons[0]->title]['status'])->toBe(TrainingContentPopulator::KEPT)
        ->and($report[$lessons[1]->title]['status'])->toBe(TrainingContentPopulator::REPLACED_PLACEHOLDER)
        ->and($report[$lessons[2]->title]['status'])->toBe(TrainingContentPopulator::POPULATED)
        ->and($report[$lessons[3]->title]['status'])->toBe(TrainingContentPopulator::REPLACED_PLACEHOLDER);

    foreach ([1, 2, 3] as $index) {
        expect($lessons[$index]->fresh()->body)->toContain('Learning objective');
    }
});

test('a published version is never edited; with a manager a new draft is created and filled', function () {
    $lead = contentPopulationEmployee(SystemRole::RecruiterLead);
    $recruiter = contentPopulationEmployee(SystemRole::Recruiter);
    $service = app(TrainingContentService::class);
    $course = courseForLevel(10);
    $v1 = $course->versions()->sole();
    $service->publishVersion($v1, $lead->user);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();
    $firstLesson = $v1->lessons()->orderBy('sort_order')->first();
    $completion = new TrainingLessonCompletion;
    $completion->forceFill([
        'assignment_id' => $assignment->id,
        'lesson_id' => $firstLesson->id,
        'started_at' => now(),
    ])->save();

    $this->artisan('recruiter:training-content')
        ->expectsOutputToContain('--as=')
        ->assertSuccessful();

    expect($v1->lessons()->whereNotNull('body')->count())->toBe(0)
        ->and($course->versions()->count())->toBe(1);

    $this->artisan('recruiter:training-content', ['--as' => $lead->user->email])->assertSuccessful();

    $v2 = $course->versions()->where('version_number', 2)->sole();

    expect($v2->status)->toBe(TrainingContentStatus::Draft)
        ->and($v2->lessons()->count())->toBe(6)
        ->and($v2->lessons()->whereNull('body')->count())->toBe(0)
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($v1->lessons()->whereNotNull('body')->count())->toBe(0)
        ->and($assignment->fresh()->course_version_id)->toBe($v1->id)
        ->and($completion->fresh()->lesson_id)->toBe($firstLesson->id);
});

/**
 * Puts an "earlier release" body on the first $count lessons of a level and
 * returns those lessons with the fingerprint list that marks them as generated.
 *
 * @return array{0: Collection<int, TrainingLesson>, 1: array<int, array<string, list<string>>>}
 */
function earlierGeneratedLessons(TrainingCourseVersion $version, int $level, int $count): array
{
    $lessons = $version->lessons()->orderBy('sort_order')->take($count)->get();
    $previous = [];

    foreach ($lessons as $lesson) {
        $old = "Learning objective\nAn earlier generated version of {$lesson->title}.\n\nKey takeaway\nOld text.";
        $lesson->forceFill(['body' => str_replace("\n", "\r\n", $old)])->save();
        $previous[$level][$lesson->title] = [TrainingContentPopulator::fingerprint($old)];
    }

    return [$lessons, $previous];
}

test('the shipped fingerprints cover every curriculum lesson', function () {
    $previous = RecruiterTrainingContent::previous();

    foreach (RecruiterTrainingContent::all() as $entry) {
        foreach (array_keys($entry['lessons']) as $title) {
            expect($previous[$entry['level']][$title] ?? [])->not->toBeEmpty("{$title} has no earlier fingerprint");

            foreach ($previous[$entry['level']][$title] as $fingerprint) {
                expect($fingerprint)->toMatch('/^[0-9a-f]{64}$/');
            }
        }
    }
});

test('the normal run never replaces earlier generated content', function () {
    $version = courseForLevel(1)->versions()->sole();
    [$lessons] = earlierGeneratedLessons($version, 1, 2);
    $before = $lessons->pluck('body', 'id')->all();

    $report = collect(app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all()))->keyBy('lesson');

    expect(TrainingLesson::query()->whereIn('id', $lessons->pluck('id'))->pluck('body', 'id')->all())->toBe($before)
        ->and($report[$lessons[0]->title]['status'])->toBe(TrainingContentPopulator::KEPT);
});

test('refresh replaces earlier generated content in place and keeps every identifier and record', function () {
    $recruiter = contentPopulationEmployee(SystemRole::Recruiter);
    $course = courseForLevel(4);
    $version = $course->versions()->sole();
    [$lessons, $previous] = earlierGeneratedLessons($version, 4, 3);
    $assignment = TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();
    $completion = new TrainingLessonCompletion;
    $completion->forceFill(['assignment_id' => $assignment->id, 'lesson_id' => $lessons[0]->id, 'started_at' => now()])->save();

    $structure = fn () => TrainingLesson::query()->orderBy('id')->get(['id', 'course_version_id', 'title', 'slug', 'sort_order', 'content_type', 'duration_minutes', 'is_required'])->toArray();
    $before = $structure();
    $counts = fn () => [TrainingCategory::query()->count(), TrainingCourse::query()->count(), TrainingCourseVersion::query()->count(), TrainingLesson::query()->count()];
    $countsBefore = $counts();

    $report = collect(app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all(), previous: $previous))->keyBy('lesson');
    $expected = collect(RecruiterTrainingContent::all())->keyBy('level')[4]['lessons'];

    foreach ($lessons as $lesson) {
        expect($report[$lesson->title]['status'])->toBe(TrainingContentPopulator::REFRESHED)
            ->and($lesson->fresh()->body)->toBe(trim($expected[$lesson->title]));
    }

    expect($structure())->toBe($before)
        ->and($counts())->toBe($countsBefore)
        ->and($assignment->fresh()->course_version_id)->toBe($version->id)
        ->and($completion->fresh()->lesson_id)->toBe($lessons[0]->id)
        ->and($course->fresh()->id)->toBe($course->id)
        ->and(TrainingLesson::query()->whereNull('body')->count())->toBe(0);

    $again = collect(app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all(), previous: $previous));

    expect($again->pluck('status')->unique()->values()->all())->toBe([TrainingContentPopulator::CURRENT]);
});

test('refresh keeps content edited in the app', function () {
    $version = courseForLevel(7)->versions()->sole();
    [$lessons, $previous] = earlierGeneratedLessons($version, 7, 2);
    $lessons[1]->forceFill(['body' => $lessons[1]->body."\r\nA paragraph the training manager added."])->save();

    $report = collect(app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all(), previous: $previous))->keyBy('lesson');

    expect($report[$lessons[0]->title]['status'])->toBe(TrainingContentPopulator::REFRESHED)
        ->and($report[$lessons[1]->title]['status'])->toBe(TrainingContentPopulator::KEPT_EDITED)
        ->and($lessons[1]->fresh()->body)->toContain('A paragraph the training manager added.');
});

test('refresh never edits a published version and needs a manager to draft the update', function () {
    $lead = contentPopulationEmployee(SystemRole::RecruiterLead);
    $course = courseForLevel(10);
    $v1 = $course->versions()->sole();
    [$lessons, $previous] = earlierGeneratedLessons($v1, 10, 2);
    app(TrainingContentService::class)->publishVersion($v1, $lead->user);
    $published = $v1->lessons()->orderBy('id')->pluck('body', 'id')->all();
    $populator = app(TrainingContentPopulator::class);

    $report = collect($populator->populate(RecruiterTrainingContent::all(), previous: $previous))->keyBy('lesson');

    expect($report[$lessons[0]->title]['status'])->toBe(TrainingContentPopulator::NEEDS_DRAFT)
        ->and($course->versions()->count())->toBe(1);

    $populator->populate(RecruiterTrainingContent::all(), $lead->user, previous: $previous);
    $v2 = $course->versions()->where('version_number', 2)->sole();

    expect($v2->status)->toBe(TrainingContentStatus::Draft)
        ->and($v2->lessons()->count())->toBe($v1->lessons()->count())
        ->and($v2->lessons()->where('title', $lessons[0]->title)->sole()->body)->toContain('Scenario two, from the company training')
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($v1->lessons()->orderBy('id')->pluck('body', 'id')->all())->toBe($published);
});

test('the refresh option is explicit and reports what it would change', function () {
    $this->artisan('recruiter:training-content')->assertSuccessful();

    $this->artisan('recruiter:training-content', ['--refresh' => true, '--dry-run' => true])
        ->expectsOutputToContain('Dry run, nothing saved.')
        ->expectsOutputToContain('Refreshed from an earlier release: 0')
        ->expectsOutputToContain('Already current: 183')
        ->expectsOutputToContain('Kept because it was edited in the app: 0')
        ->assertSuccessful();
});

test('the command refuses an unknown manager email', function () {
    $this->artisan('recruiter:training-content', ['--as' => 'nobody@example.test'])
        ->expectsOutputToContain('No user with that email.')
        ->assertFailed();
});

test('populated content reaches the lesson page and Listen to Lesson in Indian English', function () {
    app(TrainingContentPopulator::class)->populate(RecruiterTrainingContent::all());
    $lead = contentPopulationEmployee(SystemRole::RecruiterLead);
    $recruiter = contentPopulationEmployee(SystemRole::Recruiter);
    $course = courseForLevel(7);
    $version = $course->versions()->sole();
    app(TrainingContentService::class)->publishVersion($version, $lead->user);
    TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();
    $lesson = $version->lessons()->where('title', '60-Second Cold Call')->sole();

    expect($lesson->body)->toContain('A cold call is a call to a candidate who is not expecting you.');

    $this->actingAs($recruiter->user)
        ->get("/recruiter/training/courses/{$course->id}/lessons/{$lesson->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/lesson')
            ->where('lesson.body', $lesson->body)
            ->where('audio.has_text', true)
            ->where('audio.voices.0.key', 'indian_english')
            ->where('audio.voices.0.locale', 'en-IN'));

    $segments = $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lesson->id}/speech")
        ->assertOk()
        ->assertJsonPath('voice.key', 'indian_english')
        ->assertJsonPath('voice.locale', 'en-IN')
        ->json('segments');

    $spoken = implode(' ', $segments);

    expect($segments)->not->toBeEmpty()
        ->and($spoken)->toContain('Learning objective')
        ->and($spoken)->toContain('A cold call is a call to a candidate who is not expecting you.')
        ->and($spoken)->toContain('Key takeaway');
});
