<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/*
| Every Calling & Communication module except Practical Calling is one
| playbook lesson: every stage on one page, with flows whose steps link to
| the stages. They replace the modules' short lessons in the draft only.
*/

/** @return list<string> */
function playbookStages(): array
{
    return ['Prepare', 'Greeting', 'Screening', 'Our Services', 'Offer Letter Service', 'Training & Support', 'C2C Marketing', 'Payroll Service', 'Questions / Objections', 'Resume & Referral', 'Follow-up', 'Closing', 'Voicemail'];
}

/**
 * @return array<string, mixed>
 */
function playbookEntry(string $module = 'Calling Fundamentals'): array
{
    $plan = RecruiterTrainingContent::optTrack()[3];

    expect($plan['course'])->toBe('Calling & Communication')
        ->and($plan['modules'][$module])->toHaveCount(1);

    return $plan['modules'][$module][0];
}

test('calling fundamentals is a single playbook lesson with every stage of the call', function () {
    $entry = playbookEntry();
    $sections = TrainingLessonStructure::normalize($entry['en']);
    $stages = array_values(array_filter($sections, fn (array $section) => $section['kind'] === TrainingSectionKind::Script->value));

    expect($entry['title'])->toBe('How to Speak with a Consultant')
        ->and($entry['from'])->toBe('Purpose of the Recruiter Call')
        ->and($entry['compliance'])->toBeTrue()
        ->and($entry['review'])->toContain('9515708888')
        ->and(count($sections))->toBe(count($entry['en']))
        ->and(array_column($stages, 'heading'))->toBe(playbookStages());

    foreach ($stages as $stage) {
        expect($stage['body'])->toContain('**Record in CRM:**');

        if (! in_array($stage['heading'], ['Prepare', 'Questions / Objections'], true)) {
            expect($stage['body'])->toContain('**Say:**');
        }
    }

    foreach (['Greeting', 'Screening', 'Our Services', 'Offer Letter Service', 'Training & Support', 'C2C Marketing', 'Payroll Service', 'Questions / Objections', 'Resume & Referral', 'Follow-up'] as $heading) {
        $body = collect($stages)->firstWhere('heading', $heading)['body'];
        expect(preg_match_all('/^- \*\*They say:\*\* .+ \*\*You say:\*\* .+$/m', $body))->toBeGreaterThan(0);
    }
});

/**
 * Step names of a flow section, and the part of the lesson each one links to.
 *
 * @return array{0: list<string>, 1: list<string>}
 */
function playbookFlow(string $heading, string $module = 'Calling Fundamentals'): array
{
    $flow = collect(playbookEntry($module)['en'])->firstWhere('heading', $heading)['body'];
    preg_match_all('/^\d+\. (.+?) \[\[([^\]]+)\]\]$/m', $flow, $matches);

    return [
        array_map(fn (string $step) => trim((string) strtok((string) preg_replace('/^\*\*[^*]+:\*\*\s*/', '', $step), '.?')), $matches[1]),
        $matches[2],
    ];
}

test('every flow step links to a stage or a step of the same lesson', function () {
    $sections = collect(playbookEntry()['en']);
    $targets = $sections->pluck('heading')
        ->merge($sections->where('kind', 'script')->flatMap(fn (array $section) => preg_match_all('/^\*\*([^*:]+)\*\*$/m', $section['body'], $steps) ? $steps[1] : []))
        ->all();

    [$callSteps, $callTargets] = playbookFlow('Call Flow');
    [$serviceSteps, $serviceTargets] = playbookFlow('Service Flow');

    expect($callSteps)->toBe([
        'Start Call', 'Greeting', 'Good Time', 'Screening', 'Visa / Status', 'Education', 'Location', 'Employment Situation', 'Technology',
        'Understand Requirement', 'Explain Services', 'Offer Letter — $500', 'Payroll — $2,260', 'Interested', 'Resume', 'Sales Manager', 'Follow-up', 'Close',
    ])
        ->and($serviceSteps)->toBe([
            'Screening', 'Understand Requirement', 'Explain Services', 'Offer Letter — $500', 'Training', 'Resume Building',
            'Job / Interview Support', 'C2C Marketing', 'Payroll — $2,260', 'Next Step',
        ]);

    foreach ([...$callTargets, ...$serviceTargets] as $target) {
        expect($targets)->toContain($target);
    }

    expect(preg_match_all('/^\d+\. \*\*Core service:\*\* /m', $sections->firstWhere('heading', 'Service Flow')['body']))->toBe(2);
});

test('offer letter and payroll are the core services, with the exact approved figures', function () {
    $sections = collect(playbookEntry()['en']);
    $offer = $sections->firstWhere('heading', 'Offer Letter Service')['body'];
    $payroll = $sections->firstWhere('heading', 'Payroll Service')['body'];
    $model = $sections->firstWhere('heading', 'Your Service Model')['body'];
    $everything = $sections->pluck('body')->implode("\n");

    expect($offer)->toStartWith('**Price:** $500 per eligible candidate')
        ->and($payroll)->toStartWith('**Price:** $2,260 total')
        ->toContain('- Payroll amount: $1,934.17')
        ->toContain('- Service charge: $325.83')
        ->toContain('**Calculation:** $2,260 − $1,934.17 = $325.83')
        ->and(round(2260 - 1934.17, 2))->toBe(325.83)
        ->and($model)->toContain('| **1. Offer Letter Service (core)** |')
        ->toContain('| **2. Payroll Service (core)** |');

    foreach (['C2C Marketing', 'Training', 'Job Support & Interview Preparation', 'Resume Building'] as $service) {
        expect($model)->toContain($service);
    }

    expect($sections->firstWhere('heading', 'Rules for Every Call')['body'])
        ->toContain('Explain services only after you understand the consultant\'s requirement, technology, work authorization and employment situation.')
        ->toContain('Never promise immigration approval, guaranteed employment, a guaranteed project or guaranteed placement.')
        ->and(mb_strtolower($everything))->not->toMatch('/we (will|can) (guarantee|place you)|guaranteed (approval|job|placement) for you/');

    $headings = $sections->pluck('heading')->all();
    expect(array_search('Our Services', $headings, true))->toBeGreaterThan(array_search('Screening', $headings, true))
        ->and(array_search('Offer Letter Service', $headings, true))->toBeLessThan(array_search('Payroll Service', $headings, true));
});

test('the playbook carries the complete conversation and the voicemail script', function () {
    $script = collect(playbookEntry()['en'])->firstWhere('heading', 'Complete Call Script')['body'];

    expect($script)->toStartWith('**Recruiter:** Greetings of the day! Hi, this is Kiran calling from VSP Group.')
        ->toContain('Is this the right time to speak with you?')
        ->toContain('Let me know your visa status.')
        ->toContain('Our Offer Letter Service is $500 per eligible candidate.')
        ->toContain('For our payroll service, the total payroll service amount is $2,260. The applicable payroll amount is $1,934.17, and the remaining $325.83 is the service charge.')
        ->toContain('What is your available time to talk with my sales manager?')
        ->toContain('Thank you! Have a nice day. Bye.')
        ->toContain('I would appreciate it if you call me back at 9515708888, extension 999. Thank you. Bye, have a nice day.');
});

test('initial candidate screening is a single playbook lesson with every screening question', function () {
    $entry = playbookEntry('Initial Candidate Screening');
    $sections = collect(TrainingLessonStructure::normalize($entry['en']));
    $stages = $sections->where('kind', TrainingSectionKind::Script->value);

    expect($entry['title'])->toBe('Initial Candidate Screening — Complete Screening Script')
        ->and($entry['from'])->toBe('Introducing the Job Opportunity')
        ->and($entry['compliance'])->toBeTrue()
        ->and($sections)->toHaveCount(count($entry['en']))
        ->and($stages->pluck('heading')->values()->all())->toBe([
            'Job Opportunity', 'Job Search Status', 'Visa & EAD', 'Education', 'Location', 'Relocation',
            'Training & Placement', 'Technology', 'Experience', 'Availability / Notice Period', 'Screening Result',
        ])
        ->and($sections->pluck('heading'))->toContain('CRM Notes');

    foreach ($stages as $stage) {
        expect($stage['body'])->toContain('**Record in CRM:**')
            ->and(preg_match('/^\*\*(Ask|Say):\*\* /m', $stage['body']))->toBe(1)
            ->and(preg_match_all('/^- \*\*They say:\*\* .+ \*\*You say:\*\* .+$/m', $stage['body']))->toBeGreaterThan(0);
    }

    [$steps, $links] = playbookFlow('Screening Flow', 'Initial Candidate Screening');
    $targets = $sections->pluck('heading')
        ->merge($stages->flatMap(fn (array $section) => preg_match_all('/^\*\*([^*:]+)\*\*$/m', $section['body'], $found) ? $found[1] : []))
        ->all();

    expect($steps)->toBe([
        'Start Screening', 'Job Interest', 'Visa Status', 'EAD Expiry', 'Master\'s / Degree', 'Current Location', 'Relocation',
        'Training Interest', 'Technology', 'Experience', 'Availability', 'Qualified / Follow-up / Not Interested', 'Next Step',
    ]);

    foreach ($links as $target) {
        expect($targets)->toContain($target);
    }
});

test('each combined module is a single playbook lesson whose flow links to its stages', function (string $module, string $title, string $from, string $flowHeading, array $stages) {
    $entry = playbookEntry($module);
    $sections = collect(TrainingLessonStructure::normalize($entry['en']));
    $scripts = $sections->where('kind', TrainingSectionKind::Script->value);

    expect($entry['title'])->toBe($title)
        ->and($entry['from'])->toBe($from)
        ->and($entry['compliance'])->toBeTrue()
        ->and($sections)->toHaveCount(count($entry['en']))
        ->and($scripts->pluck('heading')->values()->all())->toBe($stages);

    foreach ($scripts as $stage) {
        expect($stage['body'])->toContain('**Record in CRM:**')
            ->and(preg_match_all('/^- \*\*They say:\*\* .+ \*\*You say:\*\* .+$/m', $stage['body']))->toBeGreaterThan(0);
    }

    [$steps, $links] = playbookFlow($flowHeading, $module);
    $targets = $sections->pluck('heading')
        ->merge($scripts->flatMap(fn (array $section) => preg_match_all('/^\*\*([^*:]+)\*\*$/m', $section['body'], $found) ? $found[1] : []))
        ->all();

    expect($steps)->not->toBeEmpty();

    foreach ($links as $target) {
        expect($targets)->toContain($target);
    }

    expect(mb_strtolower($sections->pluck('body')->implode("\n")))->not->toMatch('/we (will|can) (guarantee|place you)|guaranteed (approval|job|placement) for you/');
})->with([
    'explaining the opportunity' => ['Explaining the Opportunity', 'Explaining the Opportunity — Complete Services Guide', 'Introducing VSP Group', 'Explanation Flow', [
        'Company Introduction', 'Service Overview', 'Offer Letter Service', 'Training', 'Resume Building', 'Job & Interview Support', 'C2C Marketing', 'Payroll Service', 'Next Steps',
    ]],
    'questions and objections' => ['Candidate Questions & Objections', 'Candidate Questions & Objections — Complete Response Guide', 'Question: "Why VSP Group?"', 'Objection Finder', [
        'How to Answer Any Objection', 'Why VSP Group?', 'Is the Company Genuine?', 'Services & Fees', 'Training & Relocation', 'Placement & Projects', 'H-1B & Sponsorship', 'EAD & Documents', 'Speak with a Manager',
    ]],
    'follow-up and next steps' => ['Follow-Up & Next Steps', 'Follow-Up & Next Steps — Complete Follow-Up Playbook', 'Requesting the Updated Resume', 'Follow-Up Flow', [
        'The Five-Step Close', 'Company Mail', 'Resume Request', 'Referral', 'Start Date', 'Sales Manager Call', 'Follow-up Call', 'No Response', 'Voicemail',
    ]],
]);

test('practical calling keeps only the cold call and the role-play practice', function () {
    $plan = RecruiterTrainingContent::optTrack()[3];
    $practical = $plan['modules']['Practical Calling'];
    $mock = collect($practical)->firstWhere('title', 'Complete Mock OPT Recruiter Call');

    expect(array_column($practical, 'title'))->toBe(['60-Second Cold Call', 'Complete Mock OPT Recruiter Call'])
        ->and(array_sum(array_map('count', $plan['modules'])))->toBe(7)
        ->and(array_column($mock['en'], 'heading'))->toContain('Consultant Scenarios', 'Call Scoring Checklist')
        ->and(collect($mock['en'])->pluck('kind')->all())->not->toContain('example');

    $screening = collect(playbookEntry('Initial Candidate Screening')['en'])->pluck('body')->implode("\n");

    expect($screening)->toContain('**Rate and Other Interviews**')
        ->toContain('**Video Interview Readiness**')
        ->toContain('Current project and its end date');
});

test('installing the plan replaces the short lessons with the two playbooks in the draft only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $lead = Employee::factory()->create();
    $lead->user->syncRoles(SystemRole::RecruiterLead->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $course = TrainingCourse::factory()->inTrack(TrainingTrack::query()->where('slug', TrainingTrack::OPT_RECRUITER)->sole())->create([
        'title' => 'Calling & Communication',
        'slug' => Str::slug('Calling & Communication'),
    ]);
    $v1 = TrainingCourseVersion::factory()->forCourse($course, 1)->published()->create();
    $course->forceFill(['current_version_id' => $v1->id, 'status' => TrainingContentStatus::Published])->save();

    foreach (['Purpose of the Recruiter Call', 'Professional Greeting', 'Asking if It Is a Good Time'] as $i => $title) {
        TrainingLesson::factory()->forVersion($v1, $i + 1)->create(['title' => $title, 'slug' => Str::slug($title), 'module' => 'Calling Fundamentals', 'body' => "{$title} body."]);
    }

    foreach (['Introducing the Job Opportunity', 'Visa Status', 'Technology Interest'] as $i => $title) {
        TrainingLesson::factory()->forVersion($v1, $i + 4)->create(['title' => $title, 'slug' => Str::slug($title), 'module' => 'Initial Candidate Screening', 'body' => "{$title} body."]);
    }

    $v2 = app(TrainingContentService::class)->createVersion($course->fresh(), $lead->user);
    $purpose = $v2->lessons()->where('title', 'Purpose of the Recruiter Call')->sole();
    $introduction = $v2->lessons()->where('title', 'Introducing the Job Opportunity')->sole();
    $before = DB::table('ro_training_lessons')->where('course_version_id', $v1->id)->orderBy('id')->get()->toJson();

    $this->artisan('recruiter:training-opt-track', ['--as' => $lead->user->email])->assertSuccessful();

    $fundamentals = $v2->lessons()->where('module', 'Calling Fundamentals')->get();
    $playbook = $fundamentals->sole();
    $sections = $playbook->contentIn(TrainingLanguage::English)?->sections ?? [];

    expect($playbook->id)->toBe($purpose->id)
        ->and($playbook->title)->toBe('How to Speak with a Consultant')
        ->and($playbook->compliance_status)->toBe(TrainingComplianceStatus::Pending)
        ->and(collect($sections)->where('kind', 'script')->pluck('heading')->all())->toBe(playbookStages())
        ->and($v2->lessons()->whereIn('title', ['Professional Greeting', 'Asking if It Is a Good Time', 'Visa Status', 'Technology Interest'])->count())->toBe(0)
        ->and($v2->lessons()->where('module', 'Initial Candidate Screening')->sole()->only(['id', 'title']))->toBe(['id' => $introduction->id, 'title' => 'Initial Candidate Screening — Complete Screening Script'])
        ->and($v2->fresh()->status)->toBe(TrainingContentStatus::Draft)
        ->and($course->fresh()->current_version_id)->toBe($v1->id)
        ->and(DB::table('ro_training_lessons')->where('course_version_id', $v1->id)->orderBy('id')->get()->toJson())->toBe($before);

    $speech = TrainingLessonStructure::speakable($sections);

    expect($speech)->toContain('Greetings of the day! Hi, this is Kiran calling from VSP Group.')
        ->toContain('They say: "Yes, this is Priya." You say: "How are you doing, Priya?"')
        ->not->toContain('[[')
        ->not->toContain('**');
});
