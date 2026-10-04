<?php

/*
 * OPT Recruiter course 4, from the company calling script "How to speak with
 * consultant?". Every module except Practical Calling is one playbook lesson,
 * kept open during a call; Practical Calling holds only the cold call
 * technique and the role-play practice. Company-specific claims are marked
 * for management confirmation, and the lessons carrying them need compliance
 * review before publishing. Values are kept exactly as the script gives them.
 */

$confirm = fn (string $items): string => 'Company-specific claims from the calling script; management must confirm the current values before publishing: '.$items.'.';
$immigration = 'Immigration content: confirm against current official sources and the company\'s approved wording before publishing.';

$lesson = fn (string $objective, array $sections, string $takeaway): array => [
    ['kind' => 'objective', 'heading' => 'Learning Objective', 'body' => $objective],
    ...array_map(fn (array $section) => ['kind' => $section[0], 'heading' => $section[1], 'body' => $section[2]], $sections),
    ['kind' => 'takeaway', 'heading' => 'Key Takeaway', 'body' => $takeaway],
];

return [
    'course' => 'Calling & Communication',
    'title' => 'OPT Recruiter Calling & Communication',
    'modules' => [
        'Calling Fundamentals' => [
            require __DIR__.'/course-4/calling-playbook.php',
        ],
        'Initial Candidate Screening' => [
            require __DIR__.'/course-4/screening-playbook.php',
        ],
        'Explaining the Opportunity' => [
            require __DIR__.'/course-4/explaining-playbook.php',
        ],
        'Candidate Questions & Objections' => [
            require __DIR__.'/course-4/objections-playbook.php',
        ],
        'Follow-Up & Next Steps' => [
            require __DIR__.'/course-4/follow-up-playbook.php',
        ],
        'Practical Calling' => [
            ['title' => '60-Second Cold Call'],
            require __DIR__.'/course-4/mock-call.php',
        ],
    ],
];
