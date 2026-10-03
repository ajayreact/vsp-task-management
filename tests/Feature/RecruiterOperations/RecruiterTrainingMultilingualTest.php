<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingContentStructurer;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use App\Modules\RecruiterOperations\Services\TrainingSpeechService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function multilingualStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function englishSections(): array
{
    return [
        ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => 'Know when to call candidates in each U.S. time zone.'],
        ['kind' => 'content', 'heading' => 'What You Need to Know', 'body' => "Call between 9 AM and 6 PM local time.\n- Eastern: New York\n- Pacific: California"],
        ['kind' => 'reference', 'heading' => 'Quick Reference', 'body' => "| Zone | Example |\n| --- | --- |\n| ET | NY |\n| PT | CA |"],
        ['kind' => 'example', 'heading' => 'Recruiter Example', 'body' => '**Recruiter:** Is this a good time to talk?'],
        ['kind' => 'practice', 'heading' => 'Practice', 'body' => "1. What time is it in California at noon in New York?\n2. Which zone is Texas in?"],
        ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => 'Check the candidate\'s local time before you call.'],
    ];
}

/**
 * @return list<array{kind: string, heading: string, body: string}>
 */
function teluguSections(): array
{
    return [
        ['kind' => 'objective', 'heading' => 'నేర్చుకునే లక్ష్యం', 'body' => 'ప్రతి U.S. time zone లో candidates కి ఎప్పుడు call చేయాలో తెలుసుకోండి.'],
        ['kind' => 'takeaway', 'heading' => 'ముఖ్యమైన విషయం', 'body' => 'Call చేసే ముందు candidate local time చూసుకోండి.'],
    ];
}

/**
 * A published version with one lesson assigned to a recruiter, plus a lead
 * who manages training. Its English (and Telugu, when given) is approved.
 *
 * @return array{0: Employee, 1: Employee, 2: TrainingCourseVersion, 3: TrainingLesson}
 */
function multilingualCourse(?array $telugu = null): array
{
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $recruiter = multilingualStaff(SystemRole::Recruiter);
    $version = TrainingCourseVersion::factory()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['title' => 'Calling Hours', 'description' => null, 'body' => 'Old body.']);
    $contents = app(TrainingLessonContentService::class);
    $contents->save($lesson, TrainingLanguage::English, englishSections(), $lead->user);
    app(TrainingContentReviewService::class)->setStatus($lesson->refresh(), TrainingLanguage::English, TrainingContentReview::Approved, $lead->user);

    if ($telugu !== null) {
        $contents->save($lesson->refresh(), TrainingLanguage::Telugu, $telugu, $lead->user);
        app(TrainingContentReviewService::class)->setStatus($lesson->refresh(), TrainingLanguage::Telugu, TrainingContentReview::Approved, $lead->user);
    }

    app(TrainingContentService::class)->publishVersion($version->fresh(), $lead->user);
    TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    return [$lead, $recruiter, $version->fresh(), $lesson->fresh()];
}

function learnerLessonUrl(TrainingLesson $lesson): string
{
    return "/recruiter/training/courses/{$lesson->version->course_id}/lessons/{$lesson->id}";
}

// Structured content

test('plain lesson text becomes sections with headings, paragraphs, lists and tables, keeping every word', function () {
    $body = "Learning Objective\nKnow the state codes.\n\nState codes\n| State | Code |\n| --- | --- |\n| Texas | TX |\n\nCommon Mistakes\nMixing up MO and MS.\nMixing up AK and AL.\nMixing up MI and MN.\n\nKey Takeaway\nAlways double-check the code.";
    $sections = TrainingLessonStructure::fromPlainText($body);

    expect($sections[0])->toMatchArray(['kind' => 'objective', 'heading' => 'Learning Objective'])
        ->and(collect($sections)->pluck('kind')->all())->toContain('mistakes', 'takeaway')
        ->and(collect($sections)->firstWhere('kind', 'mistakes')['body'])->toBe("- Mixing up MO and MS.\n- Mixing up AK and AL.\n- Mixing up MI and MN.")
        ->and(collect($sections)->pluck('body')->implode("\n"))->toContain("| State | Code |\n| --- | --- |\n| Texas | TX |");

    $blocks = TrainingLessonStructure::blocks("Intro line.\n- one\n- two\n1. first\n2. second\n| A | B |\n| --- | --- |\n| x | y |");

    expect($blocks)->toBe([
        ['type' => 'paragraph', 'text' => 'Intro line.'],
        ['type' => 'bullets', 'items' => ['one', 'two']],
        ['type' => 'numbered', 'items' => ['first', 'second']],
        ['type' => 'table', 'header' => ['A', 'B'], 'rows' => [['x', 'y']]],
    ]);
});

test('sections are read aloud without table pipes or bold markers', function () {
    $text = TrainingLessonStructure::speakable(TrainingLessonStructure::normalize(englishSections()));

    expect($text)->not->toContain('|')
        ->and($text)->not->toContain('**')
        ->and($text)->toContain('ET, NY.')
        ->and($text)->toContain('Recruiter: Is this a good time to talk?');
});

// Learner rendering

test('the lesson page sends English sections by default and Telugu when it exists', function () {
    [, $recruiter, , $lesson] = multilingualCourse(teluguSections());

    $this->actingAs($recruiter->user)
        ->get(learnerLessonUrl($lesson))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lesson.languages.0.code', 'en')
            ->where('lesson.languages.0.canonical', true)
            ->where('lesson.languages.0.available', true)
            ->where('lesson.languages.0.sections.0.kind', 'objective')
            ->where('lesson.languages.0.sections.0.heading', 'Learning Objective')
            ->where('lesson.languages.0.sections.2.body', "| Zone | Example |\n| --- | --- |\n| ET | NY |\n| PT | CA |")
            ->where('lesson.languages.0.sections.3.kind', 'example')
            ->where('lesson.languages.0.sections.4.kind', 'practice')
            ->where('lesson.languages.1.code', 'te')
            ->where('lesson.languages.1.native_label', 'తెలుగు')
            ->where('lesson.languages.1.available', true)
            ->where('lesson.languages.1.review_status', 'approved')
            ->where('lesson.languages.1.outdated', false)
            ->where('lesson.languages.1.sections.0.heading', 'నేర్చుకునే లక్ష్యం'));
});

test('a lesson without a Telugu translation reports Telugu as unavailable so the page falls back to English', function () {
    [, $recruiter, , $lesson] = multilingualCourse();

    $this->actingAs($recruiter->user)
        ->get(learnerLessonUrl($lesson))
        ->assertInertia(fn ($page) => $page
            ->where('lesson.languages.1.code', 'te')
            ->where('lesson.languages.1.available', false)
            ->where('lesson.languages.1.sections', null)
            ->where('audio.has_text_by_language.te', false)
            ->where('audio.has_text_by_language.en', true));
});

test('a lesson with only legacy text is still shown as English sections, without saving anything', function () {
    $recruiter = multilingualStaff(SystemRole::Recruiter);
    $version = TrainingCourseVersion::factory()->published()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['body' => "Learning Objective\nKnow the codes.\n\nKey Takeaway\nCheck the code."]);
    TrainingAssignment::factory()->forVersion($version)->forEmployee($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->get(learnerLessonUrl($lesson))
        ->assertInertia(fn ($page) => $page
            ->where('lesson.languages.0.available', true)
            ->where('lesson.languages.0.structured', false)
            ->where('lesson.languages.0.sections.0.kind', 'objective'));

    expect(TrainingLessonContent::query()->count())->toBe(0);
});

// Audio

test('Indian English stays the default voice and Telugu has its own te-IN voice', function () {
    [, $recruiter, , $lesson] = multilingualCourse(teluguSections());

    $this->actingAs($recruiter->user)
        ->get(learnerLessonUrl($lesson))
        ->assertInertia(fn ($page) => $page
            ->where('audio.voices.0.key', 'indian_english')
            ->where('audio.voices_by_language.en.0.locale', 'en-IN')
            ->where('audio.voices_by_language.te.0.key', 'telugu')
            ->where('audio.voices_by_language.te.0.locale', 'te-IN'));

    expect(collect(app(TrainingSpeechService::class)->voiceOptions())->pluck('locale')->all())->not->toContain('te-IN');
});

test('the speech endpoint reads the selected language with a voice for that language', function () {
    [, $recruiter, , $lesson] = multilingualCourse(teluguSections());
    $url = "/recruiter/training/lessons/{$lesson->id}/speech";

    $this->actingAs($recruiter->user)
        ->getJson($url)
        ->assertOk()
        ->assertJsonPath('language', 'en')
        ->assertJsonPath('voice.key', 'indian_english')
        ->assertJsonPath('segments.0', 'Calling Hours.');

    $telugu = $this->actingAs($recruiter->user)->getJson("{$url}?language=te")->assertOk();
    $telugu->assertJsonPath('language', 'te')
        ->assertJsonPath('voice.locale', 'te-IN')
        ->assertJsonPath('has_text', true);
    expect(implode(' ', $telugu->json('segments')))->toContain('candidate local time');

    $this->actingAs($recruiter->user)->getJson("{$url}?language=te&voice=indian_english")->assertUnprocessable()->assertJsonValidationErrors('voice');
    $this->actingAs($recruiter->user)->getJson("{$url}?language=xx")->assertUnprocessable()->assertJsonValidationErrors('language');
});

test('Telugu speech has no text when the lesson has no Telugu translation', function () {
    [, $recruiter, , $lesson] = multilingualCourse();

    $this->actingAs($recruiter->user)
        ->getJson("/recruiter/training/lessons/{$lesson->id}/speech?language=te")
        ->assertOk()
        ->assertJson(['has_text' => false, 'segments' => []]);
});

test('listening never completes a lesson, and marking it complete still works', function () {
    [, $recruiter, $version, $lesson] = multilingualCourse(teluguSections());
    $base = learnerLessonUrl($lesson);

    $this->actingAs($recruiter->user)->getJson("/recruiter/training/lessons/{$lesson->id}/speech?language=te")->assertOk();
    $this->actingAs($recruiter->user)->postJson("{$base}/progress", ['audio_seconds' => 600])->assertOk()->assertJson(['completed' => false]);

    expect(TrainingLessonCompletion::query()->sole()->completed_at)->toBeNull();

    $this->actingAs($recruiter->user)->post("{$base}/complete")->assertRedirect();

    expect(TrainingLessonCompletion::query()->sole()->completed_at)->not->toBeNull()
        ->and(TrainingAssignment::query()->where('course_version_id', $version->id)->sole()->isCompleted())->toBeTrue();
});

// Editing and versioning

test('a manager saves English and Telugu sections on a draft, and English also updates the lesson text', function () {
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $version = TrainingCourseVersion::factory()->create();
    $lesson = TrainingLesson::factory()->forVersion($version, 1)->create(['body' => 'Old body.']);
    $url = "/recruiter/training/manage/lessons/{$lesson->id}/content";

    $this->actingAs($lead->user)->get("{$url}?language=te")->assertOk()
        ->assertInertia(fn ($page) => $page->component('RecruiterOperations/training/manage/lessons/content')->where('language', 'te')->where('can.update', true));

    $this->actingAs($lead->user)->put("{$url}/en", ['sections' => englishSections(), 'review_status' => 'approved'])->assertRedirect()->assertSessionHas('success');
    $this->actingAs($lead->user)->put("{$url}/te", ['sections' => teluguSections()])->assertRedirect()->assertSessionHas('success');

    $lesson->refresh()->load('contents');

    expect($lesson->body)->toContain('Learning Objective')
        ->and($lesson->body)->toContain('Know when to call candidates')
        ->and($lesson->contentIn(TrainingLanguage::English)->review_status)->toBe(TrainingContentReview::NeedsReview)
        ->and($lesson->contentIn(TrainingLanguage::English)->reviewed_by_user_id)->toBeNull()
        ->and($lesson->contentIn(TrainingLanguage::Telugu)->sections[0]['heading'])->toBe('నేర్చుకునే లక్ష్యం')
        ->and($lesson->contentIn(TrainingLanguage::Telugu)->source_fingerprint)->toBe(TrainingLessonStructure::fingerprint(TrainingLessonStructure::normalize(englishSections())));
});

test('section fields are validated and English needs a learning objective', function () {
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $lesson = TrainingLesson::factory()->forVersion(TrainingCourseVersion::factory()->create(), 1)->create();
    $url = "/recruiter/training/manage/lessons/{$lesson->id}/content";

    $this->actingAs($lead->user)->put("{$url}/en", ['sections' => []])->assertSessionHasErrors('sections');
    $this->actingAs($lead->user)->put("{$url}/en", ['sections' => [['kind' => 'poem', 'heading' => 'x', 'body' => 'y']]])->assertSessionHasErrors('sections.0.kind');
    $this->actingAs($lead->user)->put("{$url}/en", ['sections' => [['kind' => 'content', 'heading' => 'x', 'body' => 'y']]])->assertSessionHasErrors('sections');
});

test('Telugu cannot be added while the English is empty, and becomes outdated when English changes', function () {
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $lesson = TrainingLesson::factory()->forVersion(TrainingCourseVersion::factory()->create(), 1)->create(['body' => '']);
    $contents = app(TrainingLessonContentService::class);

    expect(fn () => $contents->save($lesson, TrainingLanguage::Telugu, teluguSections(), $lead->user))
        ->toThrow(ValidationException::class);

    $contents->save($lesson, TrainingLanguage::English, englishSections(), $lead->user);
    $telugu = $contents->save($lesson->refresh(), TrainingLanguage::Telugu, teluguSections(), $lead->user);

    expect($contents->isOutdated($lesson->refresh(), $telugu))->toBeFalse();

    $changed = englishSections();
    $changed[1]['body'] = 'Call between 10 AM and 5 PM local time.';
    $contents->save($lesson->refresh(), TrainingLanguage::English, $changed, $lead->user);

    expect($contents->isOutdated($lesson->refresh(), $telugu->refresh()))->toBeTrue();
});

test('published lesson content cannot be changed, through the page or the service', function () {
    [$lead, , , $lesson] = multilingualCourse(teluguSections());
    $url = "/recruiter/training/manage/lessons/{$lesson->id}/content";
    $before = $lesson->contents()->orderBy('id')->get(['locale', 'sections', 'updated_at'])->toArray();

    $this->actingAs($lead->user)->get($url)->assertOk()->assertInertia(fn ($page) => $page->where('can.update', false));
    $this->actingAs($lead->user)->put("{$url}/en", ['sections' => englishSections(), 'review_status' => 'approved'])->assertForbidden();
    $this->actingAs($lead->user)->delete("{$url}/te")->assertForbidden();

    expect(fn () => app(TrainingLessonContentService::class)->save($lesson, TrainingLanguage::English, englishSections(), $lead->user))
        ->toThrow(ValidationException::class);
    expect($lesson->contents()->orderBy('id')->get(['locale', 'sections', 'updated_at'])->toArray())->toBe($before);
});

test('a new draft version copies every language, and editing it leaves the published version alone', function () {
    [$lead, $recruiter, $published, $lesson] = multilingualCourse(teluguSections());

    $draft = app(TrainingContentService::class)->createVersion($published->course, $lead->user);
    $draftLesson = $draft->lessons()->with('contents')->sole();

    expect($draftLesson->contents)->toHaveCount(2)
        ->and($draftLesson->contentIn(TrainingLanguage::Telugu)->sections)->toEqual($lesson->contentIn(TrainingLanguage::Telugu)->sections);

    $changed = englishSections();
    $changed[5]['body'] = 'Always check local time first.';
    $this->actingAs($lead->user)
        ->put("/recruiter/training/manage/lessons/{$draftLesson->id}/content/en", ['sections' => $changed, 'review_status' => 'approved'])
        ->assertRedirect();

    expect($lesson->fresh()->contentIn(TrainingLanguage::English)->sections)->toEqual(TrainingLessonStructure::normalize(englishSections()))
        ->and(TrainingAssignment::query()->sole()->course_version_id)->toBe($published->id);

    $this->actingAs($recruiter->user)->get(learnerLessonUrl($lesson))->assertOk()
        ->assertInertia(fn ($page) => $page->where('lesson.languages.0.sections.5.body', "Check the candidate's local time before you call."));
});

test('a translation can be removed from a draft, but English cannot', function () {
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $lesson = TrainingLesson::factory()->forVersion(TrainingCourseVersion::factory()->create(), 1)->create();
    $contents = app(TrainingLessonContentService::class);
    $contents->save($lesson, TrainingLanguage::English, englishSections(), $lead->user);
    $contents->save($lesson->refresh(), TrainingLanguage::Telugu, teluguSections(), $lead->user);
    $url = "/recruiter/training/manage/lessons/{$lesson->id}/content";

    $this->actingAs($lead->user)->delete("{$url}/te")->assertRedirect();
    $this->actingAs($lead->user)->delete("{$url}/en")->assertSessionHasErrors();

    expect($lesson->contents()->pluck('locale')->map->value->all())->toBe(['en']);
});

test('recruiters cannot open or change lesson content', function () {
    $recruiter = multilingualStaff(SystemRole::Recruiter);
    $lesson = TrainingLesson::factory()->forVersion(TrainingCourseVersion::factory()->create(), 1)->create();
    $url = "/recruiter/training/manage/lessons/{$lesson->id}/content";

    $this->actingAs($recruiter->user)->get($url)->assertForbidden();
    $this->actingAs($recruiter->user)->put("{$url}/en", ['sections' => englishSections(), 'review_status' => 'approved'])->assertForbidden();
    $this->actingAs($recruiter->user)->put("{$url}/en", ['sections' => 'not-an-array'])->assertForbidden();
    $this->actingAs($recruiter->user)->delete("{$url}/te")->assertForbidden();

    expect(fn () => app(TrainingLessonContentService::class)->save($lesson, TrainingLanguage::English, englishSections(), $recruiter->user))
        ->toThrow(AuthorizationException::class);
    expect(TrainingLessonContent::query()->count())->toBe(0);
});

// Bulk structuring

test('structuring never edits a published version: it works on a new draft and keeps assignments and quizzes', function () {
    $lead = multilingualStaff(SystemRole::RecruiterLead);
    $recruiter = multilingualStaff(SystemRole::Recruiter);
    $category = TrainingCategory::factory()->create(['level_number' => 7]);
    $course = TrainingCourse::factory()->create(['category_id' => $category->id]);
    $published = TrainingCourseVersion::factory()->forCourse($course)->published()->create();
    $legacy = TrainingLesson::factory()->forVersion($published, 1)->create(['title' => 'Legacy Lesson', 'body' => "Learning Objective\nKnow the basics.\n\nKey Takeaway\nKeep it simple."]);
    TrainingLesson::factory()->forVersion($published, 2)->create(['title' => 'Calling Hours', 'body' => 'Old calling text.']);
    $assessment = AssessmentVersion::factory()->forAssessment(Assessment::factory()->create())->published()->create();
    $published->assessmentVersions()->attach($assessment->id, ['sort_order' => 1]);
    $assignment = TrainingAssignment::factory()->forVersion($published)->forEmployee($recruiter)->create();
    $redesigned = [7 => ['Calling Hours' => ['en' => englishSections(), 'te' => teluguSections()]]];
    $structurer = app(TrainingContentStructurer::class);

    $dry = $structurer->structure($redesigned, null, true);

    expect(collect($dry)->pluck('english')->unique()->all())->toBe([TrainingContentStructurer::NEEDS_DRAFT])
        ->and(TrainingCourseVersion::query()->count())->toBe(1)
        ->and(TrainingLessonContent::query()->count())->toBe(0);

    $report = $structurer->structure($redesigned, $lead->user, false);
    $draft = $course->versions()->where('status', TrainingContentStatus::Draft->value)->sole();

    expect(collect($report)->pluck('english', 'lesson')->all())->toBe([
        'Legacy Lesson' => TrainingContentStructurer::CONVERTED,
        'Calling Hours' => TrainingContentStructurer::REDESIGNED,
    ])
        ->and(collect($report)->firstWhere('lesson', 'Calling Hours')['telugu'])->toBe(TrainingContentStructurer::TELUGU_ADDED)
        ->and($published->lessons()->withCount('contents')->get()->sum('contents_count'))->toBe(0)
        ->and($published->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($legacy->fresh()->body)->toBe("Learning Objective\nKnow the basics.\n\nKey Takeaway\nKeep it simple.")
        ->and($draft->lessons()->withCount('contents')->get()->pluck('contents_count', 'title')->all())->toBe(['Legacy Lesson' => 1, 'Calling Hours' => 2])
        ->and($draft->assessmentVersions()->pluck('ro_assessment_versions.id')->all())->toBe([$assessment->id])
        ->and($assignment->fresh()->course_version_id)->toBe($published->id);

    $again = $structurer->structure($redesigned, $lead->user, false);

    expect(collect($again)->pluck('english')->unique()->all())->toBe([TrainingContentStructurer::KEPT])
        ->and(TrainingCourseVersion::query()->count())->toBe(2);
});

test('the redesigned lessons have English and Telugu for every required sample, with an objective first and a takeaway', function () {
    $redesigned = RecruiterTrainingContent::redesigned();
    $samples = [1 => 'State Abbreviations & Codes', 2 => 'OPT', 5 => 'Reading Job Descriptions', 7 => '60-Second Cold Call', 10 => 'Scenario: Calling'];

    foreach ($samples as $level => $title) {
        expect($redesigned[$level][$title] ?? null)->not->toBeNull("Level {$level} {$title}");

        foreach (['en', 'te'] as $code) {
            $sections = TrainingLessonStructure::normalize($redesigned[$level][$title][$code]);

            expect($sections[0]['kind'])->toBe('objective')
                ->and(TrainingLessonStructure::has($sections, TrainingSectionKind::Takeaway))->toBeTrue()
                ->and(TrainingLessonStructure::longestParagraphWords($sections))->toBeLessThanOrEqual(TrainingLessonStructure::GIANT_PARAGRAPH_WORDS);
        }

        expect(count($redesigned[$level][$title]['te']))->toBe(count($redesigned[$level][$title]['en']));
    }
});
