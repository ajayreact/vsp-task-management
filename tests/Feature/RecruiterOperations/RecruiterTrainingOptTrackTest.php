<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingCurriculumRestructurer;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function optTrackLead(): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles(SystemRole::RecruiterLead->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function optTrackEnglish(string $topic): array
{
    return [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => "Understand {$topic}."],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => "- Details about {$topic}."],
    ];
}

/**
 * A course with a published Version 1 and a Version 2 draft whose lessons
 * have English content.
 *
 * @param  list<string>  $titles
 * @return array{0: TrainingCourseVersion, 1: TrainingCourseVersion}
 */
function optTrackCourse(string $title, array $titles, Employee $lead, bool $withDraft = true): array
{
    $course = TrainingCourse::factory()->create(['title' => $title, 'slug' => Str::slug($title)]);
    $v1 = TrainingCourseVersion::factory()->forCourse($course)->published()->create();

    foreach ($titles as $i => $lessonTitle) {
        TrainingLesson::factory()->forVersion($v1, $i + 1)->create(['title' => $lessonTitle, 'slug' => Str::slug($lessonTitle)]);
    }

    if (! $withDraft) {
        return [$v1->fresh(), $v1->fresh()];
    }

    $v2 = app(TrainingContentService::class)->createVersion($course->fresh(), $lead->user);

    foreach ($v2->lessons as $lesson) {
        app(TrainingLessonContentService::class)->save($lesson, TrainingLanguage::English, optTrackEnglish($lesson->title), $lead->user);
    }

    return [$v1->fresh(), $v2->fresh()];
}

/**
 * @return list<array<string, mixed>>
 */
function optTrackPlan(): array
{
    return [[
        'course' => 'Plan Course',
        'title' => 'Plan Course Renamed',
        'modules' => [
            'First Module' => [
                ['title' => 'Intro'],
                ['title' => 'New Name', 'from' => 'Old Name', 'compliance' => true, 'review' => 'Confirm the old name content.'],
            ],
            'Second Module' => [
                ['title' => 'Brand New', 'en' => optTrackEnglish('a brand new topic'), 'compliance' => true, 'review' => 'Confirm the company details.'],
                ['title' => 'Copied Lesson', 'copy' => ['Plan Source', 'Source Lesson']],
                ['title' => 'Translated'],
            ],
        ],
    ]];
}

/**
 * @return array{0: Employee, 1: TrainingCourseVersion, 2: TrainingCourseVersion}
 */
function optTrackSetup(): array
{
    $lead = optTrackLead();
    [$v1, $v2] = optTrackCourse('Plan Course', ['Intro', 'Old Name', 'Dropped', 'Translated'], $lead);
    optTrackCourse('Plan Source', ['Source Lesson'], $lead);

    $translated = $v2->lessons()->where('title', 'Translated')->sole();
    app(TrainingLessonContentService::class)->save($translated, TrainingLanguage::Telugu, optTrackEnglish('Telugu draft'), $lead->user);

    return [$lead, $v1, $v2];
}

function optTrackSnapshot(TrainingCourseVersion $version): array
{
    return $version->lessons()->with('contents')->orderBy('id')->get()
        ->map(fn (TrainingLesson $lesson) => [$lesson->id, $lesson->title, $lesson->module, $lesson->sort_order, $lesson->compliance_status?->value, $lesson->body, $lesson->contents->map(fn ($c) => [$c->locale, $c->review_status->value, $c->sections])->all()])
        ->all();
}

test('the draft is reorganised into modules and the published version is untouched', function () {
    [$lead, $v1, $v2] = optTrackSetup();
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee(Employee::factory()->create())->create();
    $before = optTrackSnapshot($v1);

    app(TrainingCurriculumRestructurer::class)->restructure(optTrackPlan(), $lead->user, false);

    $lessons = $v2->lessons()->with('contents')->orderBy('sort_order')->get();

    expect($lessons->pluck('title')->all())->toBe(['Intro', 'New Name', 'Brand New', 'Copied Lesson', 'Translated'])
        ->and($lessons->pluck('module')->all())->toBe(['First Module', 'First Module', 'Second Module', 'Second Module', 'Second Module'])
        ->and($lessons->pluck('sort_order')->all())->toBe([1, 2, 3, 4, 5])
        ->and(optTrackSnapshot($v1->fresh()))->toBe($before)
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($v2->fresh()->status)->toBe(TrainingContentStatus::Draft)
        ->and($v1->course->fresh()->title)->toBe('Plan Course')
        ->and($assignment->fresh()->course_version_id)->toBe($v1->id);

    $byTitle = $lessons->keyBy('title');
    $english = fn (string $title) => $byTitle[$title]->contentIn(TrainingLanguage::English);

    expect($lessons->every(fn (TrainingLesson $lesson) => $lesson->contentIn(TrainingLanguage::English)?->review_status === TrainingContentReview::NeedsReview))->toBeTrue()
        ->and($english('New Name')->sections[0]['body'])->toBe('Understand Old Name.')
        ->and($english('Brand New')->sections[0]['body'])->toBe('Understand a brand new topic.')
        ->and($english('Copied Lesson')->sections[0]['body'])->toBe('Understand Source Lesson.')
        ->and($byTitle['Brand New']->contentIn(TrainingLanguage::Telugu))->toBeNull()
        ->and($byTitle['Copied Lesson']->contentIn(TrainingLanguage::Telugu))->toBeNull()
        ->and($byTitle['Translated']->contentIn(TrainingLanguage::Telugu)?->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($byTitle['New Name']->compliance_status)->toBe(TrainingComplianceStatus::Pending)
        ->and($byTitle['New Name']->compliance_note)->toBe('Confirm the old name content.')
        ->and($byTitle['Brand New']->compliance_status)->toBe(TrainingComplianceStatus::Pending)
        ->and($byTitle['Intro']->compliance_status)->toBeNull()
        ->and($byTitle['Copied Lesson']->compliance_status)->toBeNull();
});

test('a dry run reports the mapping and saves nothing', function () {
    [$lead, , $v2] = optTrackSetup();
    $before = optTrackSnapshot($v2);

    $report = app(TrainingCurriculumRestructurer::class)->restructure(optTrackPlan(), null, true);
    $actions = collect($report)->pluck('action', 'lesson');

    expect(optTrackSnapshot($v2->fresh()))->toBe($before)
        ->and($actions['Intro'])->toBe(TrainingCurriculumRestructurer::KEPT)
        ->and($actions['New Name'])->toBe('Renamed from "Old Name"')
        ->and($actions['Brand New'])->toBe(TrainingCurriculumRestructurer::CREATED)
        ->and($actions['Copied Lesson'])->toBe('Copied from Plan Source / "Source Lesson"')
        ->and($actions['Dropped'])->toBe(TrainingCurriculumRestructurer::REMOVED);
});

test('running the plan again changes nothing', function () {
    [$lead, , $v2] = optTrackSetup();
    $restructurer = app(TrainingCurriculumRestructurer::class);

    $restructurer->restructure(optTrackPlan(), $lead->user, false);
    $after = optTrackSnapshot($v2->fresh());
    $report = $restructurer->restructure(optTrackPlan(), $lead->user, false);

    expect(optTrackSnapshot($v2->fresh()))->toBe($after)
        ->and(collect($report)->pluck('action')->unique()->values()->all())->toBe([TrainingCurriculumRestructurer::KEPT]);
});

test('reviewed english and compliance decisions are never overwritten', function () {
    [$lead, , $v2] = optTrackSetup();
    $intro = $v2->lessons()->where('title', 'Intro')->sole();
    $intro->contentIn(TrainingLanguage::English)->forceFill([
        'review_status' => TrainingContentReview::Approved,
        'reviewed_by_user_id' => $lead->user->id,
        'reviewed_at' => now(),
    ])->save();
    $intro->forceFill(['compliance_status' => TrainingComplianceStatus::Approved, 'compliance_note' => 'Checked.'])->save();

    $plan = optTrackPlan();
    $plan[0]['modules']['First Module'][0] = ['title' => 'Intro', 'en' => optTrackEnglish('something else'), 'compliance' => true, 'review' => 'Again.'];

    app(TrainingCurriculumRestructurer::class)->restructure($plan, $lead->user, false);

    $intro = $intro->fresh()->load('contents');

    expect($intro->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::Approved)
        ->and($intro->contentIn(TrainingLanguage::English)->sections[0]['body'])->toBe('Understand Intro.')
        ->and($intro->compliance_status)->toBe(TrainingComplianceStatus::Approved)
        ->and($intro->compliance_note)->toBe('Checked.')
        ->and($intro->module)->toBe('First Module');
});

test('a course without a draft version is skipped', function () {
    $lead = optTrackLead();
    [$v1] = optTrackCourse('Plan Course', ['Intro', 'Old Name'], $lead, withDraft: false);
    $before = optTrackSnapshot($v1);

    $report = app(TrainingCurriculumRestructurer::class)->restructure(optTrackPlan(), $lead->user, false);

    expect($report)->toHaveCount(1)
        ->and($report[0]['action'])->toBe(TrainingCurriculumRestructurer::SKIPPED)
        ->and(optTrackSnapshot($v1->fresh()))->toBe($before)
        ->and(TrainingCourseVersion::query()->count())->toBe(1);
});

test('the command needs a known user to save and allows a dry run', function () {
    $this->artisan('recruiter:training-opt-track')->assertFailed();
    $this->artisan('recruiter:training-opt-track', ['--as' => 'nobody@example.test'])->assertFailed();
    $this->artisan('recruiter:training-opt-track', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run, nothing saved.')
        ->assertSuccessful();
});

test('the opt track plan has four courses with the agreed modules', function () {
    $plans = RecruiterTrainingContent::optTrack();
    $modules = array_map(fn (array $plan) => count($plan['modules']), $plans);
    $lessons = array_map(fn (array $plan) => array_sum(array_map('count', $plan['modules'])), $plans);

    expect(array_column($plans, 'title'))->toBe([
        'U.S. Fundamentals',
        'Immigration & Work Authorization',
        'OPT & STEM OPT Recruiter Process',
        'OPT Recruiter Calling & Communication',
    ])
        ->and($modules)->toBe([3, 4, 4, 6])
        ->and($lessons)->toBe([3, 6, 5, 7])
        ->and(array_keys($plans[1]['modules']))->toBe(['Visa / Status', 'Documents & Systems', 'Employment & Work Authorization', 'Recruiter Compliance'])
        ->and(array_keys($plans[2]['modules']))->toBe(['Role & Sourcing', 'OPT Process & Employment', 'STEM OPT', 'Delivery, Compliance & Assessment'])
        ->and(array_keys($plans[3]['modules']))->toBe(['Calling Fundamentals', 'Initial Candidate Screening', 'Explaining the Opportunity', 'Candidate Questions & Objections', 'Follow-Up & Next Steps', 'Practical Calling']);

    foreach ($plans as $plan) {
        $titles = [];

        foreach ($plan['modules'] as $entries) {
            foreach ($entries as $entry) {
                $titles[] = mb_strtolower($entry['title']);

                if (isset($entry['en'])) {
                    $sections = TrainingLessonStructure::normalize($entry['en']);
                    expect(TrainingLessonStructure::has($sections, TrainingSectionKind::Objective))->toBeTrue("{$entry['title']} needs a learning objective");
                    expect(mb_strtolower(implode(' ', array_column($sections, 'body'))))->not->toContain('bench sales');
                }

                if (($entry['compliance'] ?? false) === true) {
                    expect($entry['review'] ?? '')->not->toBe('', "{$entry['title']} needs review items");
                }
            }
        }

        expect(array_unique($titles))->toHaveCount(count($titles));
    }

    foreach ($plans[1]['modules'] as $entries) {
        foreach ($entries as $entry) {
            expect($entry['compliance'] ?? false)->toBeTrue("{$entry['title']} needs compliance review");
        }
    }
});
