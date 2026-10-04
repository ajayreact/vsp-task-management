<?php

use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;

/*
| U.S. IT Staffing & Payroll Fundamentals, Job Requirement Analysis and
| Sourcing & Resume Screening (Levels 4 to 6) are made of combined lessons:
| each former lesson is a topic (a tab), and every lesson ends with one
| "Checklist & Common Mistakes" topic gathering the former lessons'
| checklists, mistakes and source notes.
*/

/**
 * @return array<string, array<string, mixed>>
 */
function compactLessons(int $course): array
{
    $plan = RecruiterTrainingContent::compactCourses()[$course];

    return collect($plan['modules'])->flatten(1)->keyBy('title')->all();
}

/**
 * @param  array<string, mixed>  $entry
 * @return list<string>
 */
function compactTopics(array $entry): array
{
    return array_values(array_map(fn (array $section) => $section['heading'], array_filter($entry['en'], fn (array $section) => $section['kind'] === TrainingSectionKind::Topic->value)));
}

/**
 * @param  array<string, mixed>  $entry
 * @return list<array{kind: string, heading: string, body: string}>
 */
function compactTopicSections(array $entry, string $topic): array
{
    $sections = [];
    $inside = false;

    foreach ($entry['en'] as $section) {
        if ($section['kind'] === TrainingSectionKind::Topic->value) {
            $inside = $section['heading'] === $topic;
        }

        if ($section['kind'] === TrainingSectionKind::Takeaway->value) {
            break;
        }

        if ($inside) {
            $sections[] = $section;
        }
    }

    return $sections;
}

test('levels 4 to 6 become compact courses with the agreed modules and lessons', function () {
    $plans = RecruiterTrainingContent::compactCourses();

    expect(array_column($plans, 'course'))->toBe(['U.S. IT Staffing & Payroll Fundamentals', 'Job Requirement Analysis', 'Sourcing & Resume Screening'])
        ->and(array_keys($plans[0]['modules']))->toBe(['Staffing Basics', 'Employment & Payroll'])
        ->and(array_keys($plans[1]['modules']))->toBe(['Reading the Requirement', 'Candidate Fit & Job Conditions'])
        ->and(array_keys($plans[2]['modules']))->toBe(['Sourcing', 'Resume Screening'])
        ->and(array_keys(compactLessons(0)))->toBe(['U.S. IT Staffing Model & Key Players', 'Requirement to Placement Lifecycle', 'Employment Types, W2/C2C & Rates', 'U.S. Payroll, Taxes & Payroll Forms'])
        ->and(array_keys(compactLessons(1)))->toBe(['Reading & Analysing a Requirement', 'Candidate Fit: Qualifications, Skills & Experience', 'Location, Work Mode, Duration, Rate & Start Date'])
        ->and(array_keys(compactLessons(2)))->toBe(['Sourcing Channels', 'Search Strategy, Keywords & Boolean', 'Resume Screening: Skills, Experience & Education', 'Profile Verification & Resume vs JD']);
});

test('every compact lesson fits the limits, maps every topic and links its flows inside the lesson', function (int $course) {
    foreach (compactLessons($course) as $title => $entry) {
        $sections = TrainingLessonStructure::normalize($entry['en']);
        $topics = compactTopics($entry);
        $headings = array_column($sections, 'heading');

        expect($sections)->toHaveCount(count($entry['en']))
            ->and(count($sections))->toBeLessThanOrEqual(TrainingLessonStructure::MAX_SECTIONS)
            ->and(max(array_map(fn (array $section) => mb_strlen($section['body']), $sections)))->toBeLessThanOrEqual(TrainingLessonStructure::MAX_BODY)
            ->and($sections[0]['kind'])->toBe('objective', "{$title} starts with its objective")
            ->and(end($sections)['kind'])->toBe('takeaway', "{$title} ends with its takeaway")
            ->and(end($topics))->toBe('Checklist & Common Mistakes', "{$title} ends with its review topic")
            ->and(array_unique($topics))->toHaveCount(count($topics));

        $map = collect($sections)->firstWhere('heading', 'Lesson Map')['body'];
        preg_match_all('/^\d+\. (.+?) \[\[([^\]]+)\]\]$/m', $map, $links);

        expect($links[2])->toBe($topics, "{$title} lesson map links to every topic in order");

        foreach ($links[1] as $label) {
            expect($label)->not->toMatch('/[.!?]\s/', "{$title} map label \"{$label}\" would split as a flow step");
        }

        foreach (collect($sections)->where('kind', TrainingSectionKind::Flow->value) as $flow) {
            preg_match_all('/\[\[([^\]]+)\]\]/', $flow['body'], $targets);

            foreach ($targets[1] as $target) {
                expect($headings)->toContain($target);
            }
        }
    }
})->with([0, 1, 2]);

test('every former lesson is kept as a topic, and the keyword lesson moves to sourcing with a pointer left behind', function () {
    $topics = fn (int $course) => collect(compactLessons($course))->flatMap(fn (array $entry) => compactTopics($entry))->reject(fn (string $topic) => $topic === 'Checklist & Common Mistakes')->values();

    expect($topics(0))->toHaveCount(30)
        ->and($topics(0))->toContain('U.S. IT Staffing Basics', 'Client', 'Bench', 'W2', 'C2C', 'Rate Structures', 'Gross Pay', 'FICA', 'SUTA/SUI', 'EIN', 'Pay Period', 'Staffing Glossary')
        ->and($topics(1))->toHaveCount(20)
        ->and($topics(1))->toContain('Understanding Requirements', 'Client', 'Work Authorization Check', 'Requirement Prioritization', 'Must-Have vs Preferred', 'Start Date', 'Requirement → Search Keywords')
        ->and($topics(2))->toHaveCount(24)
        ->and($topics(2))->toContain('LinkedIn', 'Direct Communication', 'Boolean Search Basics', 'Requirement → Search Keywords', 'Domain Experience', 'Resume vs JD Comparison');

    $pointer = compactTopicSections(compactLessons(1)['Reading & Analysing a Requirement'], 'Requirement → Search Keywords');
    $moved = compactTopicSections(compactLessons(2)['Search Strategy, Keywords & Boolean'], 'Requirement → Search Keywords');

    expect($pointer)->toHaveCount(1)
        ->and($pointer[0]['body'])->toContain('**Full lesson:** Sourcing & Resume Screening → Search Strategy, Keywords & Boolean')
        ->and(array_column($moved, 'heading'))->toContain('Step one, list title keywords', 'Step six, build search strings', 'Recruiter Example');
});

test('content owned by another course is a short cross-reference instead of a repeat', function () {
    $requirement = array_column(compactTopicSections(compactLessons(0)['Requirement to Placement Lifecycle'], 'Requirement'), 'heading');
    $glossary = collect(compactTopicSections(compactLessons(0)['U.S. IT Staffing Model & Key Players'], 'Staffing Glossary'))->firstWhere('heading', 'Key terms')['body'];
    $client = collect(compactTopicSections(compactLessons(1)['Reading & Analysing a Requirement'], 'Client'));
    $rate = collect(compactTopicSections(compactLessons(1)['Location, Work Mode, Duration, Rate & Start Date'], 'Rate'))->firstWhere('heading', 'Discussing rate with candidates')['body'];
    $call = collect(compactTopicSections(compactLessons(2)['Sourcing Channels'], 'Direct Communication'))->firstWhere('heading', 'The first call')['body'];
    $location = array_column(compactTopicSections(compactLessons(2)['Profile Verification & Resume vs JD'], 'Location'), 'heading');

    expect($requirement)->not->toContain('Requirement priority')
        ->and($requirement)->not->toContain('Work authorization in requirements')
        ->and($glossary)->not->toMatch('/^- (W2|C2C|Bench|Placement|Submission|Bill rate)\./m')
        ->and($glossary)->toContain('- MSP.')
        ->and($client->firstWhere('heading', 'What You Need to Know')['body'])->toContain('U.S. IT Staffing Model & Key Players')
        ->and($client->firstWhere('heading', 'Information sharing')['body'])->toContain('Never contact the end client directly')
        ->and($rate)->toContain('Employment Types, W2/C2C & Rates')
        ->and($call)->toContain('Calling & Communication → How to Speak with a Consultant')
        ->and($location)->not->toContain('Location and payroll');
});

test('checklists, mistakes and source notes are gathered once, and no old level numbers remain', function () {
    $payroll = compactTopicSections(compactLessons(0)['U.S. Payroll, Taxes & Payroll Forms'], 'Checklist & Common Mistakes');
    $sources = collect($payroll)->firstWhere('heading', 'Sources & Review')['body'];

    expect(array_column($payroll, 'heading'))->toBe(['Checklist & Common Mistakes', 'Recruiter Checklist', 'Common Mistakes', 'Sources & Review'])
        ->and(substr_count($sources, 'The company training documents'))->toBe(1)
        ->and($sources)->toContain('gross pay, net pay, pay periods')
        ->and($sources)->toContain('need review and approval by payroll and the training manager');

    foreach ([0, 1, 2] as $course) {
        foreach (compactLessons($course) as $title => $entry) {
            $sections = collect($entry['en']);
            $body = $sections->pluck('body')->implode("\n");

            expect($body)->not->toMatch('/\bLevel \d|this level|later lessons?\b|next lesson/i', "{$title} has no stale level references")
                ->and($sections->where('heading', 'Recruiter Checklist')->count())->toBeLessThanOrEqual(1)
                ->and($sections->where('kind', TrainingSectionKind::Mistakes->value)->count())->toBeLessThanOrEqual(1);

            if (($entry['compliance'] ?? false) === true) {
                expect($entry['review'] ?? '')->not->toBe('');
            }
        }
    }
});

test('the compact set is applied through the restructure command, and an unknown set is refused', function () {
    $this->artisan('recruiter:training-opt-track', ['--dry-run' => true, '--set' => 'compact'])
        ->expectsOutputToContain('Job Requirement Analysis')
        ->assertSuccessful();

    $this->artisan('recruiter:training-opt-track', ['--dry-run' => true, '--set' => 'bench'])
        ->expectsOutputToContain('Unknown --set')
        ->assertFailed();
});
