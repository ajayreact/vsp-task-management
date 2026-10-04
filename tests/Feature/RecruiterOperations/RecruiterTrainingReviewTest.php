<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function reviewStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function reviewEnglish(string $topic, int $extraWords = 0): array
{
    return [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => "Understand {$topic} well enough to explain it to a candidate."],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => "Key facts about {$topic}.".($extraWords > 0 ? ' '.trim(str_repeat('detail ', $extraWords)) : '')],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => "Check the facts about {$topic} before every call."],
    ];
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function reviewTelugu(): array
{
    return [
        ['kind' => 'objective', 'heading' => 'నేర్చుకునే లక్ష్యం', 'body' => 'OPT గురించి candidate కి వివరించేంతగా అర్థం చేసుకోండి.'],
        ['kind' => 'content', 'heading' => 'మీరు తెలుసుకోవాల్సినది', 'body' => 'OPT గురించి ముఖ్యమైన విషయాలు.'],
        ['kind' => 'takeaway', 'heading' => 'ముఖ్యమైన విషయం', 'body' => 'ప్రతి call కి ముందు OPT వివరాలు check చేయండి.'],
    ];
}

/**
 * A level 2 course: a published Version 1 assigned to a recruiter, and a
 * Version 2 draft whose two lessons have structured English, the first with a
 * Telugu translation. Nothing is reviewed yet.
 *
 * @return array{lead: Employee, recruiter: Employee, course: TrainingCourse, v1: TrainingCourseVersion, v2: TrainingCourseVersion, first: TrainingLesson, second: TrainingLesson}
 */
function reviewFixture(int $extraWords = 0): array
{
    $lead = reviewStaff(SystemRole::RecruiterLead);
    $recruiter = reviewStaff(SystemRole::Recruiter);
    $category = TrainingCategory::factory()->create(['level_number' => 2]);
    $course = TrainingCourse::factory()->create(['category_id' => $category->id]);
    $v1 = TrainingCourseVersion::factory()->forCourse($course)->published()->create();
    TrainingLesson::factory()->forVersion($v1, 1)->create(['title' => 'OPT', 'description' => null, 'body' => "Learning Objective\nKnow OPT.\n\nKey Takeaway\nCheck the EAD dates."]);
    TrainingLesson::factory()->forVersion($v1, 2)->create(['title' => 'STEM OPT', 'description' => null, 'body' => "Learning Objective\nKnow STEM OPT.\n\nKey Takeaway\nCheck the extension."]);
    TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();

    $v2 = app(TrainingContentService::class)->createVersion($course->fresh(), $lead->user);
    [$first, $second] = $v2->lessons()->orderBy('sort_order')->get()->all();
    $contents = app(TrainingLessonContentService::class);
    $contents->save($first, TrainingLanguage::English, reviewEnglish('OPT', $extraWords), $lead->user);
    $contents->save($second, TrainingLanguage::English, reviewEnglish('STEM OPT'), $lead->user);
    $contents->save($first->refresh(), TrainingLanguage::Telugu, reviewTelugu(), $lead->user);

    return ['lead' => $lead, 'recruiter' => $recruiter, 'course' => $course->fresh(), 'v1' => $v1->fresh(), 'v2' => $v2->fresh(), 'first' => $first->fresh(), 'second' => $second->fresh()];
}

function reviewUrl(TrainingLesson $lesson, string $language = 'en'): string
{
    return "/recruiter/training/manage/review/lessons/{$lesson->id}/{$language}";
}

function approveEnglish(TrainingLesson ...$lessons): void
{
    $lead = reviewStaff(SystemRole::RecruiterLead);

    foreach ($lessons as $lesson) {
        app(TrainingContentReviewService::class)->setStatus($lesson->fresh(), TrainingLanguage::English, TrainingContentReview::Approved, $lead->user);
    }
}

// 1. English review status

test('every draft lesson is listed for English review with its status, and starting a review records the reviewer', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture();

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review')->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/manage/review/index')
            ->where('language', 'en')
            ->has('rows', 2)
            ->where('rows.0.title', 'OPT')
            ->where('rows.0.level', 2)
            ->where('rows.0.status', 'needs_review')
            ->where('rows.0.status_label', 'Needs review')
            ->where('summary.english.total', 2)
            ->where('summary.english.needs_review', 2)
            ->where('summary.english.approved', 0));

    $this->actingAs($lead->user)->post(reviewUrl($first), ['status' => 'in_review'])->assertRedirect(reviewUrl($first));

    $content = $first->fresh()->contentIn(TrainingLanguage::English);
    expect($content->review_status)->toBe(TrainingContentReview::InReview)
        ->and($content->reviewed_by_user_id)->toBe($lead->user->id);

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review')
        ->assertInertia(fn ($page) => $page->where('summary.english.in_review', 1)->where('rows.0.reviewer', $lead->user->name));
});

// 2. Telugu review status

test('only lessons with a Telugu translation are in the Telugu queue, shown as pending native review', function () {
    ['lead' => $lead] = reviewFixture();

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review?language=te')->assertOk()
        ->assertInertia(fn ($page) => $page->where('language', 'te')
            ->has('rows', 1)
            ->where('rows.0.title', 'OPT')
            ->where('rows.0.status', 'needs_review')
            ->where('rows.0.status_label', 'Pending native review')
            ->where('summary.telugu.available', 1)
            ->where('summary.telugu.pending', 1)
            ->where('summary.telugu.approved', 0)
            ->where('summary.telugu.not_started', 1));
});

// 3. Compliance review status

test('compliance flags are set by level, never approved automatically, and decided only by a manager', function () {
    ['lead' => $lead, 'v1' => $v1, 'first' => $first, 'second' => $second] = reviewFixture();

    $this->artisan('recruiter:training-review-flags', ['--dry-run' => true])->expectsOutputToContain('Would flag: 2')->assertSuccessful();
    expect($first->fresh()->compliance_status)->toBeNull();

    $this->artisan('recruiter:training-review-flags')->expectsOutputToContain('Flagged for compliance review: 2')->assertSuccessful();
    $this->artisan('recruiter:training-review-flags')->expectsOutputToContain('Flagged for compliance review: 0')->assertSuccessful();

    expect($first->fresh()->compliance_status)->toBe(TrainingComplianceStatus::Pending)
        ->and($second->fresh()->compliance_status)->toBe(TrainingComplianceStatus::Pending)
        ->and($v1->lessons()->whereNotNull('compliance_status')->count())->toBe(0);

    $url = "/recruiter/training/manage/review/lessons/{$first->id}/compliance";
    $this->actingAs($lead->user)->post($url, ['status' => 'changes_requested'])->assertSessionHasErrors('note');
    $this->actingAs($lead->user)->post($url, ['status' => 'changes_requested', 'note' => 'Add the legal advice disclaimer.'])->assertRedirect();
    expect($first->fresh()->compliance_status)->toBe(TrainingComplianceStatus::ChangesRequested)
        ->and($first->fresh()->compliance_note)->toBe('Add the legal advice disclaimer.');

    $this->actingAs($lead->user)->post($url, ['status' => 'approved'])->assertRedirect();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/review/lessons/{$second->id}/compliance", ['status' => 'not_required'])->assertRedirect();

    expect($first->fresh()->compliance_status)->toBe(TrainingComplianceStatus::Approved)
        ->and($first->fresh()->compliance_reviewed_by_user_id)->toBe($lead->user->id)
        ->and($second->fresh()->compliance()->isRequired())->toBeFalse();

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review')
        ->assertInertia(fn ($page) => $page->where('summary.compliance.required', 1)->where('summary.compliance.approved', 1)->where('summary.compliance.pending', 0));
});

// 4. Word count flag

test('lessons over the 700-word target are flagged but never shortened', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture(720);
    $before = $first->contentIn(TrainingLanguage::English)->sections;

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review')
        ->assertInertia(fn ($page) => $page->where('summary.over_target', 1)
            ->where('rows.0.over_target', true)
            ->where('rows.1.over_target', false)
            ->where('overTargetWords', 700));

    expect($first->fresh()->contentIn(TrainingLanguage::English)->sections)->toEqual($before);
});

// 5. Approve lesson

test('approving a lesson records who approved it and when, without changing content or publishing', function () {
    ['lead' => $lead, 'v2' => $v2, 'first' => $first] = reviewFixture();
    $sections = $first->contentIn(TrainingLanguage::English)->sections;

    $this->actingAs($lead->user)->post(reviewUrl($first), ['status' => 'approved'])->assertRedirect()->assertSessionHas('success');

    $content = $first->fresh()->contentIn(TrainingLanguage::English);
    expect($content->review_status)->toBe(TrainingContentReview::Approved)
        ->and($content->reviewed_by_user_id)->toBe($lead->user->id)
        ->and($content->reviewed_at)->not->toBeNull()
        ->and($content->sections)->toEqual($sections)
        ->and($v2->fresh()->status)->toBe(TrainingContentStatus::Draft);
});

// 6. Request changes

test('requesting changes needs a note and keeps it for the author', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture();

    $this->actingAs($lead->user)->post(reviewUrl($first), ['status' => 'changes_requested'])->assertSessionHasErrors('note');
    expect($first->fresh()->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::NeedsReview);

    $this->actingAs($lead->user)->post(reviewUrl($first), ['status' => 'changes_requested', 'note' => 'Add an example call.'])->assertRedirect();

    $content = $first->fresh()->contentIn(TrainingLanguage::English);
    expect($content->review_status)->toBe(TrainingContentReview::ChangesRequested)
        ->and($content->review_note)->toBe('Add an example call.');

    $this->actingAs($lead->user)->get(reviewUrl($first))
        ->assertInertia(fn ($page) => $page->where('lesson.english.status', 'changes_requested')->where('lesson.english.note', 'Add an example call.'));
});

// 7. Next / previous review

test('the review page steps through lessons in course order, and approve and next moves on', function () {
    ['lead' => $lead, 'first' => $first, 'second' => $second] = reviewFixture();

    $this->actingAs($lead->user)->get(reviewUrl($first))->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/manage/review/show')
            ->where('navigation.position', 1)
            ->where('navigation.total', 2)
            ->where('navigation.previous', null)
            ->where('navigation.next.id', $second->id));

    $this->actingAs($lead->user)->get(reviewUrl($second))
        ->assertInertia(fn ($page) => $page->where('navigation.previous.id', $first->id)->where('navigation.next', null));

    $this->actingAs($lead->user)->post(reviewUrl($first), ['status' => 'approved', 'next' => true])->assertRedirect(reviewUrl($second));
});

// 8. Telugu side-by-side review

test('Telugu is reviewed next to the English, section by section, and is approved only after the English', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture();

    $this->actingAs($lead->user)->get(reviewUrl($first, 'te'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('language', 'te')
            ->where('lesson.translation_label', 'Telugu')
            ->has('lesson.english.sections', 3)
            ->has('lesson.translation.sections', 3)
            ->where('lesson.english.sections.0.kind', 'objective')
            ->where('lesson.translation.sections.0.kind', 'objective')
            ->where('lesson.translation.sections.0.heading', 'నేర్చుకునే లక్ష్యం')
            ->where('lesson.translation.label', 'Pending native review'));

    $this->actingAs($lead->user)->post(reviewUrl($first, 'te'), ['status' => 'approved'])->assertSessionHasErrors('status');
    expect($first->fresh()->contentIn(TrainingLanguage::Telugu)->review_status)->toBe(TrainingContentReview::NeedsReview);

    approveEnglish($first);
    $this->actingAs($lead->user)->post(reviewUrl($first, 'te'), ['status' => 'approved'])->assertRedirect();

    expect($first->fresh()->contentIn(TrainingLanguage::Telugu)->review_status)->toBe(TrainingContentReview::Approved);
});

// 9. English changed -> Telugu review required

test('changing approved English sends English, Telugu and compliance back to review', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture();
    $reviews = app(TrainingContentReviewService::class);
    approveEnglish($first);
    $reviews->setStatus($first->fresh(), TrainingLanguage::Telugu, TrainingContentReview::Approved, $lead->user);
    $reviews->setCompliance($first->fresh(), TrainingComplianceStatus::Approved, $lead->user);

    $changed = reviewEnglish('OPT');
    $changed[1]['body'] = 'Updated facts about OPT and the 90-day unemployment limit.';
    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$first->id}/content/en", ['sections' => $changed])->assertRedirect();

    $lesson = $first->fresh()->load('contents');
    $telugu = $lesson->contentIn(TrainingLanguage::Telugu);

    expect($lesson->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($telugu->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($telugu->reviewed_by_user_id)->toBeNull()
        ->and(app(TrainingLessonContentService::class)->isOutdated($lesson, $telugu))->toBeTrue()
        ->and($lesson->compliance_status)->toBe(TrainingComplianceStatus::Pending);

    $this->actingAs($lead->user)->get('/recruiter/training/manage/review?language=te')
        ->assertInertia(fn ($page) => $page->where('rows.0.status', 'outdated')
            ->where('rows.0.status_label', 'English changed — review required')
            ->where('summary.telugu.approved', 0)
            ->where('summary.telugu.outdated', 1));

    approveEnglish($first);
    $reviews->setStatus($first->fresh(), TrainingLanguage::Telugu, TrainingContentReview::Approved, $lead->user);
    $lesson = $first->fresh()->load('contents');

    expect(app(TrainingLessonContentService::class)->isOutdated($lesson, $lesson->contentIn(TrainingLanguage::Telugu)))->toBeFalse();
});

// 10. Older versions are history

test('once Version 2 is live, Version 1 is history and cannot be reviewed or changed', function () {
    ['lead' => $lead, 'v1' => $v1, 'v2' => $v2] = reviewFixture();
    app(TrainingContentService::class)->publishVersion($v2, $lead->user);
    $published = $v1->lessons()->orderBy('sort_order')->first();
    $before = $published->only(['title', 'body', 'compliance_status', 'updated_at']);

    $this->actingAs($lead->user)->post(reviewUrl($published), ['status' => 'approved'])->assertForbidden();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/review/lessons/{$published->id}/compliance", ['status' => 'approved'])->assertForbidden();
    $this->actingAs($lead->user)->get(reviewUrl($published))->assertNotFound();

    expect(fn () => app(TrainingContentReviewService::class)->setCompliance($published, TrainingComplianceStatus::Approved, $lead->user))->toThrow(ValidationException::class)
        ->and(fn () => app(TrainingContentReviewService::class)->setStatus($published, TrainingLanguage::English, TrainingContentReview::Approved, $lead->user))->toThrow(ValidationException::class);

    expect($published->fresh()->only(['title', 'body', 'compliance_status', 'updated_at']))->toEqual($before)
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Archived)
        ->and($v1->lessons()->withCount('contents')->get()->sum('contents_count'))->toBe(0);
});

test('a long lesson can be saved in parts, with more than 120 sections', function () {
    ['lead' => $lead, 'first' => $first] = reviewFixture();
    $sections = [['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Know STEM OPT from start to finish.']];

    foreach ([1, 2, 3] as $part) {
        $sections[] = ['kind' => 'part', 'heading' => "Part {$part}", 'body' => "Introduction to part {$part}."];

        foreach (range(1, 45) as $topic) {
            $sections[] = ['kind' => 'topic', 'heading' => "Topic {$part}.{$topic}", 'body' => 'Key point.'];
        }
    }

    $sections[] = ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Follow the plan.'];

    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$first->id}/content/en", ['sections' => $sections])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $stored = $first->fresh()->contentIn(TrainingLanguage::English)->sections;
    expect($stored)->toHaveCount(140)
        ->and(collect($stored)->where('kind', 'part')->pluck('heading')->values()->all())->toBe(['Part 1', 'Part 2', 'Part 3'])
        ->and(end($stored)['kind'])->toBe('takeaway');
});

// 11. Draft Version 2 editable

test('the Version 2 draft stays editable, and an edit to approved content needs a new review', function () {
    ['lead' => $lead, 'second' => $second] = reviewFixture();
    approveEnglish($second);

    $this->actingAs($lead->user)->get("/recruiter/training/manage/lessons/{$second->id}/content")
        ->assertInertia(fn ($page) => $page->where('can.update', true));

    $same = $second->fresh()->contentIn(TrainingLanguage::English)->sections;
    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$second->id}/content/en", ['sections' => $same])->assertRedirect();
    expect($second->fresh()->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::Approved);

    $changed = reviewEnglish('STEM OPT');
    $changed[2]['body'] = 'Confirm the STEM OPT extension dates on every call.';
    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$second->id}/content/en", ['sections' => $changed, 'review_status' => 'approved'])->assertRedirect();

    $content = $second->fresh()->contentIn(TrainingLanguage::English);
    expect($content->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($content->reviewed_by_user_id)->toBeNull()
        ->and($content->sections[2]['body'])->toBe('Confirm the STEM OPT extension dates on every call.');
});

// 12. Publishing does not wait for review

test('Version 2 is published straight away, with reviews still open as an internal marker', function () {
    ['lead' => $lead, 'course' => $course, 'v1' => $v1, 'v2' => $v2, 'first' => $first] = reviewFixture();
    $this->artisan('recruiter:training-review-flags')->assertSuccessful();

    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$v2->id}/publish")->assertSessionHasNoErrors();

    expect($v2->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($v1->fresh()->status)->toBe(TrainingContentStatus::Archived)
        ->and($course->fresh()->current_version_id)->toBe($v2->id)
        ->and($first->fresh()->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($first->fresh()->compliance_status)->toBe(TrainingComplianceStatus::Pending);
});

// 13. Saved translations reach learners without review

test('a saved Telugu translation reaches learners without review, and falls back to English once the English changes', function () {
    ['lead' => $lead, 'v2' => $v2, 'first' => $first] = reviewFixture();
    app(TrainingContentService::class)->publishVersion($v2->fresh(), $lead->user);

    $learner = reviewStaff(SystemRole::Recruiter);
    TrainingAssignment::factory()->forVersion($v2)->forEmployee($learner)->create();
    $url = "/recruiter/training/courses/{$v2->course_id}/lessons/{$first->id}";

    $this->actingAs($learner->user)->get($url)->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.languages.0.available', true)
            ->where('lesson.languages.1.code', 'te')
            ->where('lesson.languages.1.available', true)
            ->where('audio.has_text_by_language.te', true));

    $changed = reviewEnglish('OPT');
    $changed[1]['body'] = 'Updated facts about OPT and the 90-day unemployment limit.';
    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$first->id}/content/en", ['sections' => $changed])->assertRedirect();

    $this->actingAs($learner->user)->get($url)->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.languages.1.available', false)
            ->where('lesson.languages.1.sections', null)
            ->where('audio.has_text_by_language.te', false));

    expect($first->fresh()->contentIn(TrainingLanguage::Telugu)->review_status)->toBe(TrainingContentReview::NeedsReview);
});

// 14. Unauthorized users cannot approve

test('recruiters cannot open the review queue or approve anything', function () {
    ['recruiter' => $recruiter, 'first' => $first] = reviewFixture();

    $this->actingAs($recruiter->user)->get('/recruiter/training/manage/review')->assertForbidden();
    $this->actingAs($recruiter->user)->get(reviewUrl($first))->assertForbidden();
    $this->actingAs($recruiter->user)->post(reviewUrl($first), ['status' => 'approved'])->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/training/manage/review/lessons/{$first->id}/compliance", ['status' => 'approved'])->assertForbidden();

    expect(fn () => app(TrainingContentReviewService::class)->setStatus($first, TrainingLanguage::English, TrainingContentReview::Approved, $recruiter->user))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(TrainingContentReviewService::class)->setCompliance($first, TrainingComplianceStatus::Approved, $recruiter->user))
        ->toThrow(AuthorizationException::class);

    expect($first->fresh()->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($first->fresh()->compliance_status)->toBeNull();
});

// 15. Learner experience unchanged

test('reviewing the draft changes nothing for a recruiter on Version 1', function () {
    ['lead' => $lead, 'recruiter' => $recruiter, 'v1' => $v1, 'first' => $first] = reviewFixture();
    $published = $v1->lessons()->orderBy('sort_order')->first();
    $url = "/recruiter/training/courses/{$v1->course_id}/lessons/{$published->id}";

    $this->actingAs($recruiter->user)->post("{$url}/complete")->assertRedirect();
    $completion = TrainingLessonCompletion::query()->sole()->only(['lesson_id', 'completed_at']);

    approveEnglish($first);
    app(TrainingContentReviewService::class)->setStatus($first->fresh(), TrainingLanguage::Telugu, TrainingContentReview::Approved, $lead->user);
    app(TrainingContentReviewService::class)->setCompliance($first->fresh(), TrainingComplianceStatus::Approved, $lead->user);

    $this->actingAs($recruiter->user)->get($url)->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.id', $published->id)
            ->where('lesson.body', "Learning Objective\nKnow OPT.\n\nKey Takeaway\nCheck the EAD dates.")
            ->where('lesson.languages.0.structured', false)
            ->where('lesson.languages.1.available', false));

    expect(TrainingAssignment::query()->where('employee_id', $recruiter->id)->sole()->course_version_id)->toBe($v1->id)
        ->and(TrainingLessonCompletion::query()->sole()->only(['lesson_id', 'completed_at']))->toEqual($completion);
});

// 16. Removing the important note

test('the important note is removed from draft lessons only, and translations stay current', function () {
    ['lead' => $lead, 'v1' => $v1, 'v2' => $v2, 'first' => $first] = reviewFixture();
    $contents = app(TrainingLessonContentService::class);
    $note = ['kind' => 'note', 'heading' => 'Important Note', 'body' => 'This lesson is not legal advice.'];
    $teluguNote = ['kind' => 'note', 'heading' => 'ముఖ్య గమనిక', 'body' => 'ఇది legal advice కాదు.'];
    $contents->save($first, TrainingLanguage::English, [$note, ...reviewEnglish('OPT')], $lead->user);
    $contents->save($first->refresh(), TrainingLanguage::Telugu, [$teluguNote, ...reviewTelugu()], $lead->user);
    $plain = TrainingLesson::factory()->forVersion($v2, 3)->create(['title' => 'EAD', 'description' => null, 'body' => "Important note\nThis lesson is not legal advice.\n\nLearning objective\nKnow the EAD."]);
    $published = $v1->lessons()->orderBy('sort_order')->first();
    $published->forceFill(['body' => "Important note\nNot legal advice.\n\n".$published->body])->save();
    $publishedBefore = $published->fresh()->body;

    $this->artisan('recruiter:training-remove-notes')->expectsOutputToContain('--as=')->assertFailed();
    $this->artisan('recruiter:training-remove-notes', ['--dry-run' => true])->expectsOutputToContain('Would update: 2')->assertSuccessful();
    expect($first->fresh()->contentIn(TrainingLanguage::English)->sections)->toHaveCount(4);

    $this->artisan('recruiter:training-remove-notes', ['--as' => $lead->user->email])->expectsOutputToContain('Lessons updated: 2')->assertSuccessful();
    $this->artisan('recruiter:training-remove-notes', ['--as' => $lead->user->email])->expectsOutputToContain('Lessons updated: 0')->assertSuccessful();

    $first = $first->fresh();
    $telugu = $first->contentIn(TrainingLanguage::Telugu);

    expect(collect($first->contentIn(TrainingLanguage::English)->sections)->pluck('kind')->all())->toBe(['objective', 'content', 'takeaway'])
        ->and(collect($telugu->sections)->pluck('kind')->all())->toBe(['objective', 'content', 'takeaway'])
        ->and($contents->isOutdated($first, $telugu))->toBeFalse()
        ->and($first->body)->not->toContain('Important Note')
        ->and($plain->fresh()->body)->toBe("Learning objective\nKnow the EAD.")
        ->and($published->fresh()->body)->toBe($publishedBefore);
});
