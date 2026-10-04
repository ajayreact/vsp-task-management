<?php

use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Services\TrainingLessonStructure;
use Database\Seeders\RecruiterOperations\TrainingContent\RecruiterTrainingContent;

/*
| U.S. Fundamentals, Immigration & Work Authorization and the OPT Recruiter
| course are made of combined lessons: each former lesson is a topic (a tab)
| whose sections follow it, and a lesson map links to every topic.
*/

/**
 * @return array<string, array<string, mixed>>
 */
function combinedLessons(int $course): array
{
    $plan = RecruiterTrainingContent::optTrack()[$course];

    return collect($plan['modules'])->flatten(1)->keyBy('title')->all();
}

/**
 * @param  array<string, mixed>  $entry
 * @return list<string>
 */
function topicHeadings(array $entry): array
{
    return array_values(array_map(fn (array $section) => $section['heading'], array_filter($entry['en'], fn (array $section) => $section['kind'] === TrainingSectionKind::Topic->value)));
}

test('the three courses become compact combined lessons', function () {
    expect(array_keys(combinedLessons(0)))->toBe(['U.S. States, Regions & Major Cities', 'U.S. Time Zones & India Conversion', 'U.S. Calendar, Seasons & Business Hours'])
        ->and(array_keys(combinedLessons(1)))->toBe(['U.S. Visa & Immigration Statuses', 'Immigration Documents & Systems', 'Employment & Work Authorization', 'Recruiter Compliance', 'When to Escalate', 'Practical Immigration Screening'])
        ->and(array_keys(combinedLessons(2)))->toBe([
            'OPT Recruiter Role, Workflow & Candidate Sourcing',
            'Initial OPT Process — Step-by-Step',
            'OPT Employment, Offer Letter, Projects, Timesheets & Payroll',
            'STEM OPT — Eligibility, Transition, I-983, Application, Employment & Reporting',
            'Services, Compliance & Practical Assessment',
        ]);
});

test('every combined lesson fits the section limits and its lesson map links to its topics', function (int $course) {
    foreach (combinedLessons($course) as $title => $entry) {
        $sections = TrainingLessonStructure::normalize($entry['en']);
        $topics = topicHeadings($entry);
        $playbook = collect($sections)->where('kind', TrainingSectionKind::Script->value)->count() >= 3;

        expect($sections)->toHaveCount(count($entry['en']))
            ->and(count($sections))->toBeLessThanOrEqual(TrainingLessonStructure::MAX_SECTIONS)
            ->and(max(array_map(fn (array $section) => mb_strlen($section['body']), $entry['en'])))->toBeLessThanOrEqual(TrainingLessonStructure::MAX_BODY)
            ->and($sections[0]['kind'])->toBe('objective', "{$title} starts with its objective")
            ->and(end($sections)['kind'])->toBe('takeaway', "{$title} ends with its takeaway");

        if ($playbook) {
            continue;
        }

        $mapLinks = function (string $heading) use ($entry, $title): array {
            $map = collect($entry['en'])->firstWhere('heading', $heading)['body'] ?? '';
            preg_match_all('/^\d+\. (.+?) \[\[([^\]]+)\]\]$/m', $map, $links);

            foreach ($links[1] as $label) {
                expect($label)->not->toMatch('/[.!?]\s/', "{$title} map label \"{$label}\" would split as a flow step");
            }

            return $links[2];
        };

        expect($topics)->not->toBeEmpty("{$title} has topics");
        $parts = collect($entry['en'])->where('kind', TrainingSectionKind::Part->value)->pluck('heading')->values()->all();

        if ($parts === []) {
            expect($mapLinks('Lesson Map'))->toBe($topics, "{$title} lesson map links to every topic in order");

            continue;
        }

        $closing = array_slice($topics, -1);
        expect($mapLinks('Lesson Map'))->toBe([...$parts, ...$closing], "{$title} lesson map links to every part, then the closing tab");

        $inPart = [];
        $part = null;
        foreach ($entry['en'] as $section) {
            if ($section['kind'] === TrainingSectionKind::Part->value) {
                $part = $section['heading'];
            } elseif ($section['kind'] === TrainingSectionKind::Topic->value && $part !== null) {
                $inPart[$part][] = $section['heading'];
            }
        }

        foreach ($parts as $number => $heading) {
            $own = $number === count($parts) - 1 ? array_slice($inPart[$heading], 0, -1) : $inPart[$heading];
            expect($mapLinks('Part '.($number + 1).' Map'))->toBe($own, "{$heading} map links to every topic of the part in order");
        }
    }
})->with([0, 1, 2]);

test('level 3 is five lessons that end with a checklist and common mistakes tab', function () {
    $opt = combinedLessons(2);

    foreach ($opt as $title => $entry) {
        $sections = $entry['en'];

        expect(collect($sections)->firstWhere('heading', 'Lesson Map')['kind'])->toBe('flow', "{$title} opens with its lesson map")
            ->and(collect($sections)->where('kind', 'topic')->last()['heading'])->toBe('Checklist & Common Mistakes', "{$title} ends with the checklist tab")
            ->and(collect($sections)->where('kind', TrainingSectionKind::Script->value))->toBeEmpty("{$title} does not copy the calling scripts");

        $afterLastTopic = array_slice($sections, collect($sections)->keys()->filter(fn ($i) => $sections[$i]['kind'] === 'topic')->last() + 1);
        expect(array_column($afterLastTopic, 'kind'))->toBeIn([['reference', 'mistakes', 'takeaway'], ['mistakes', 'takeaway'], ['reference', 'takeaway']]);
    }

    $topicCounts = array_map(fn (array $entry) => count(topicHeadings($entry)), $opt);
    expect(array_values($topicCounts))->toBe([15, 13, 15, 44, 18]);
});

test('the stem opt lesson is in three parts and combines its repeated topics', function () {
    $entry = combinedLessons(2)['STEM OPT — Eligibility, Transition, I-983, Application, Employment & Reporting'];
    $sections = collect($entry['en']);
    $topics = topicHeadings($entry);

    expect($sections->where('kind', TrainingSectionKind::Part->value)->pluck('heading')->values()->all())->toBe([
        'Part 1 — Eligibility & OPT → STEM Transition',
        'Part 2 — Form I-983 & STEM OPT Application',
        'Part 3 — STEM OPT Employment, Evaluations & Reporting',
    ]);

    foreach (['Employer Resources and Supervision', 'Employer Supervision', 'Evaluations on the I-983', 'Evaluations During STEM OPT', 'The 6-Month Validation', 'The 12-Month Evaluation', 'The 18-Month Validation', 'The 24-Month Final Evaluation', 'STEM OPT Employment Begins or Continues'] as $merged) {
        expect($topics)->not->toContain($merged);
    }

    $group = function (string $heading) use ($entry): string {
        $start = collect($entry['en'])->search(fn ($s) => $s['kind'] === 'topic' && $s['heading'] === $heading);
        $body = [];
        foreach (array_slice($entry['en'], $start) as $offset => $section) {
            if ($offset > 0 && in_array($section['kind'], ['topic', 'part', 'takeaway'], true)) {
                break;
            }
            $body[] = $section['heading']."\n".$section['body'];
        }

        return implode("\n", $body);
    };

    expect($group('Supervision and Oversight'))
        ->toContain('resources and experienced personnel')
        ->toContain('The plan names how the employer will oversee and supervise the training.')
        ->toContain('Weak or absent supervision puts the training plan at risk.')
        ->toContain('has not spoken to his supervisor in a month')
        ->toContain('The recruiter never names a supervisor.');

    expect($group('Validations & Evaluations: 6, 12, 18 and 24 Months'))
        ->toContain('| 12 | Self-evaluation on the I-983 | Candidate, signed by the employer |')
        ->toContain('The first validation is due at six months.')
        ->toContain('section 7 of the I-983, Evaluation of Student Progress')
        ->toContain('Missed validations can cause problems')
        ->toContain('or when the training ends early')
        ->toContain('The final evaluation closes the training plan.');

    expect($group('STEM OPT Employment at a Glance'))
        ->toContain('STEM OPT employment follows the training plan from day one.')
        ->toContain('STEM OPT is a continuous cycle')
        ->toContain('Any material change to the role is reported so the I-983 can be updated.')
        ->toContain('**Candidate:** Completes the 24-month final evaluation.');

    expect(substr_count($group('Checklist & Common Mistakes'), 'Naming a supervisor who never works with the student.'))->toBe(1);
});

test('every former lesson is kept as a topic, except duplicates which become recaps or pointers', function () {
    expect(topicHeadings(combinedLessons(0)['U.S. States, Regions & Major Cities']))->toBe(['Introduction to the United States', '50 U.S. States & State Codes', 'U.S. Regions', 'Major U.S. Cities'])
        ->and(topicHeadings(combinedLessons(1)['U.S. Visa & Immigration Statuses']))->toBe(['F-1', 'F-2', 'CPT', 'OPT', 'STEM OPT', 'H-1B', 'H-4', 'H-4 EAD', 'EAD', 'Green Card', 'Other Visa & Status Overview'])
        ->and(topicHeadings(combinedLessons(1)['Immigration Documents & Systems']))->toBe(['I-20', 'I-765', 'I-983', 'I-94', 'I-9', 'Passport', 'SEVIS', 'DSO', 'E-Verify'])
        ->and(topicHeadings(combinedLessons(1)['When to Escalate']))->toBe(['When to Escalate to HR, Compliance or DSO', 'Escalation Rules for Recruiters']);

    $topicCounts = array_map(fn (array $entry) => count(topicHeadings($entry)), [...combinedLessons(0), ...combinedLessons(1)]);
    expect(array_sum($topicCounts))->toBe((4 + 3 + 3) + (11 + 9 + 6 + 3 + 2));

    $opt = combinedLessons(2);
    $recap = collect($opt['OPT Recruiter Role, Workflow & Candidate Sourcing']['en'])->firstWhere('heading', 'Quick Recap: F-1, OPT, DSO and USCIS')['body'];

    foreach (['What Is F-1 Status?', 'What Is OPT?', 'What Is Post-Completion OPT?', 'Who Is the DSO?', 'What Does the DSO Do?', 'What Does USCIS Do?'] as $title) {
        expect($recap)->toContain("**{$title}**");
    }

    expect($recap)->toContain('**Full lesson:** Immigration & Work Authorization →');

    foreach (['Employment Eligibility Verification', 'What Is STEM OPT?', 'What Is Form I-983?', 'Escalation Rules for Recruiters'] as $pointer) {
        $lesson = collect($opt)->first(fn (array $entry) => in_array($pointer, topicHeadings($entry), true));
        $sections = $lesson['en'];
        $index = collect($sections)->search(fn (array $section) => $section['heading'] === $pointer && $section['kind'] === 'topic');

        expect($sections[$index]['body'])->toContain('**Full lesson:** Immigration & Work Authorization →')
            ->and($sections[$index + 1]['kind'] ?? 'topic')->toBeIn(['topic', 'takeaway']);
    }

    $optTopics = collect($opt)->flatMap(fn (array $entry) => topicHeadings($entry));
    expect($optTopics)->toHaveCount((7 + 7 + 12 + 8 + 6 + 14 + 21 + 16 + 6 + 8 + 3) - 8 + 5)
        ->and($optTopics->contains('OPT vs STEM OPT: The Key Differences'))->toBeFalse()
        ->and($optTopics->contains('OPT vs STEM OPT Comparison Table'))->toBeTrue();
});

test('the opt recruiter course does not repeat the calling lessons', function () {
    $calling = array_keys(collect(RecruiterTrainingContent::optTrack()[3]['modules'])->flatten(1)->keyBy('title')->all());
    $opt = combinedLessons(2);

    foreach ($calling as $title) {
        expect($opt)->not->toHaveKey($title);
    }

    expect(collect($opt['OPT Recruiter Role, Workflow & Candidate Sourcing']['en'])->firstWhere('heading', 'Where the Calling Lessons Are')['body'])
        ->toContain('How to Speak with a Consultant')
        ->toContain('Complete Mock OPT Recruiter Call');

    expect(collect($opt['Services, Compliance & Practical Assessment']['en'])->firstWhere('heading', 'Where the Calling Scripts Are')['body'])
        ->toContain('Explaining the Opportunity — Complete Services Guide')
        ->toContain('Candidate Questions & Objections — Complete Response Guide');
});

test('the 50 states are listed two per row with their codes', function () {
    $sections = collect(combinedLessons(0)['U.S. States, Regions & Major Cities']['en']);
    $table = $sections->firstWhere('heading', 'State Code Table')['body'];
    $rows = array_slice(explode("\n", $table), 2);
    $cells = collect($rows)->flatMap(fn (string $row) => array_chunk(array_map('trim', explode('|', trim($row, '| '))), 2))->filter(fn (array $pair) => $pair[0] !== '');

    expect(explode("\n", $table)[0])->toBe('| State | Code | State | Code |')
        ->and($rows)->toHaveCount(26)
        ->and($cells)->toHaveCount(51)
        ->and($cells->pluck(1)->all())->toContain('AL', 'WY', 'DC', 'MT', 'MO')
        ->and($sections->pluck('heading'))->not->toContain('The 50 states in alphabetical order');
});

test('new lessons mark undefined company process for management review', function () {
    $opt = combinedLessons(2);

    foreach (['OPT Recruiter Role, Workflow & Candidate Sourcing', 'Services, Compliance & Practical Assessment'] as $title) {
        expect($opt[$title]['compliance'])->toBeTrue()
            ->and($opt[$title]['review'])->toContain('not yet defined')
            ->and(collect($opt[$title]['en'])->where('heading', 'Management to Define')->count())->toBeGreaterThan(2);
    }

    expect($opt['Services, Compliance & Practical Assessment']['review'])->toContain('the pass mark, who observes and signs off the practical')
        ->and(combinedLessons(1)['Practical Immigration Screening']['review'])->toContain('not yet defined');
});
