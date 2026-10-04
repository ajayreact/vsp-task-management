<?php

/*
 * Job Requirement Analysis in three combined lessons. Turning a requirement
 * into search keywords is taught in Sourcing & Resume Screening; this course
 * keeps a short pointer to it.
 */

return [
    'course' => 'Job Requirement Analysis',
    'title' => 'Job Requirement Analysis',
    'modules' => [
        'Reading the Requirement' => [
            require __DIR__.'/requirements/reading-analysing.php',
        ],
        'Candidate Fit & Job Conditions' => [
            require __DIR__.'/requirements/candidate-fit.php',
            require __DIR__.'/requirements/job-conditions.php',
        ],
    ],
];
