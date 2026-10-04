<?php

/*
 * U.S. IT Staffing & Payroll Fundamentals in four combined lessons. Each old
 * lesson is a topic (tab); their checklists, common mistakes and source notes
 * are gathered in each lesson's last topic. Requirement analysis and work
 * authorization rules are taught in Job Requirement Analysis and referenced
 * from here.
 */

return [
    'course' => 'U.S. IT Staffing & Payroll Fundamentals',
    'title' => 'U.S. IT Staffing & Payroll Fundamentals',
    'modules' => [
        'Staffing Basics' => [
            require __DIR__.'/staffing/model-key-players.php',
            require __DIR__.'/staffing/requirement-to-placement.php',
        ],
        'Employment & Payroll' => [
            require __DIR__.'/staffing/employment-types-rates.php',
            require __DIR__.'/staffing/payroll-taxes-forms.php',
        ],
    ],
];
