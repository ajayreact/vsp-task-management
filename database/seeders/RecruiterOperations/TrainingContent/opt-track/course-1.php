<?php

/*
 * OPT Recruiter course 1. One combined lesson per module: each former lesson
 * is a topic (a tab) of the combined lesson, with its sections unchanged.
 * The 50 states list is folded into the two-states-per-row code table.
 */

return [
    'course' => 'U.S. Fundamentals',
    'title' => 'U.S. Fundamentals',
    'modules' => [
        'States & Geography' => [require __DIR__.'/course-1/states-regions-cities.php'],
        'Time Zones' => [require __DIR__.'/course-1/time-zones.php'],
        'Seasons, Calendar & Calling Hours' => [require __DIR__.'/course-1/calendar-business-hours.php'],
    ],
];
