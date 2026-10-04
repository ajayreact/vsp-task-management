<?php

/*
 * Sourcing & Resume Screening in four combined lessons. "Requirement →
 * Search Keywords" moved here from Job Requirement Analysis; first calls are
 * taught in Calling & Communication and referenced from here.
 */

return [
    'course' => 'Sourcing & Resume Screening',
    'title' => 'Sourcing & Resume Screening',
    'modules' => [
        'Sourcing' => [
            require __DIR__.'/sourcing/channels.php',
            require __DIR__.'/sourcing/search-keywords-boolean.php',
        ],
        'Resume Screening' => [
            require __DIR__.'/sourcing/resume-screening.php',
            require __DIR__.'/sourcing/profile-verification.php',
        ],
    ],
];
