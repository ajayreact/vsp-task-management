<?php

/*
 * OPT Recruiter course 2. One combined lesson per subject: each former
 * lesson is a topic (a tab) of the combined lesson, with its sections
 * unchanged. This course teaches the immigration foundations once; the OPT
 * Recruiter course points here instead of repeating them. Escalation Rules
 * for Recruiters moves here from the OPT Recruiter course. Every lesson
 * needs compliance review, and recruiters never give immigration or legal
 * advice.
 */

return [
    'course' => 'Immigration & Work Authorization',
    'title' => 'Immigration & Work Authorization',
    'modules' => [
        'Visa / Status' => [require __DIR__.'/course-2/statuses.php'],
        'Documents & Systems' => [require __DIR__.'/course-2/documents-systems.php'],
        'Employment & Work Authorization' => [require __DIR__.'/course-2/employment-authorization.php'],
        'Recruiter Compliance' => [
            require __DIR__.'/course-2/recruiter-compliance.php',
            require __DIR__.'/course-2/escalation.php',
            require __DIR__.'/course-2/practical-screening.php',
        ],
    ],
];
