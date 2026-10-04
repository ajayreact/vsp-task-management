<?php

/*
 * OPT Recruiter course 3: the OPT recruiter workflow and business process,
 * in five lessons. Immigration foundations are taught in Immigration & Work
 * Authorization and calling skills in Calling & Communication; this course
 * points to them instead of repeating them. Each combined lesson keeps its
 * former lessons' topics as tabs with the wording unchanged, and ends with a
 * Checklist & Common Mistakes tab. The STEM OPT lesson is in three parts.
 * Sourcing, service delivery and the practical assessment mark their
 * company-specific steps for management review.
 *
 * Every lesson needs compliance review: the immigration steps must be
 * checked against current official sources, and the internal process
 * against the company's approved process.
 */

return [
    'course' => 'OPT Recruiter Process & Sourcing Strategy',
    'title' => 'OPT & STEM OPT Recruiter Process',
    'modules' => [
        'Role & Sourcing' => [
            require __DIR__.'/course-3/role-workflow-sourcing.php',
        ],
        'OPT Process & Employment' => [
            require __DIR__.'/course-3/initial-opt.php',
            require __DIR__.'/course-3/employment-projects-payroll.php',
        ],
        'STEM OPT' => [
            require __DIR__.'/course-3/stem-opt.php',
        ],
        'Delivery, Compliance & Assessment' => [
            require __DIR__.'/course-3/services-compliance-assessment.php',
        ],
    ],
];
