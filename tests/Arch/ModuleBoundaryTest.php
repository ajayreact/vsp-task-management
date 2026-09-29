<?php

/*
|--------------------------------------------------------------------------
| Module boundary
|--------------------------------------------------------------------------
|
| Conventions decay. These tests turn the architecture into a build failure.
|
| Dependencies point one way only:
|
|     TaskManagement -> Core
|     RecruiterOperations -> Core
|
| Core must never import Task Management or Recruiter Operations. Recruiter
| Operations must never import another business module; cross-module
| composition lives outside App\Modules. The CRM module is gone.
|
*/

arch('task management does not depend on a removed crm module')
    ->expect('App\Modules\TaskManagement')
    ->not->toUse('App\Modules\Crm');

arch('the shared kernel does not depend on task management')
    ->expect('App\Modules\Core')
    ->not->toUse(['App\Modules\Crm', 'App\Modules\TaskManagement']);

arch('recruiter operations does not depend on other business modules')
    ->expect('App\Modules\RecruiterOperations')
    ->not->toUse([
        'App\Modules\TaskManagement',
        'App\Modules\Attendance',
        'App\Modules\Finance',
        'App\Modules\Crm',
    ]);

arch('the shared kernel does not depend on recruiter operations')
    ->expect('App\Modules\Core')
    ->not->toUse('App\Modules\RecruiterOperations');

arch('recruiter operations controllers never reach digital marketing task management or finance')
    ->expect('App\Http\Controllers\RecruiterOperations')
    ->not->toUse([
        'App\Modules\TaskManagement',
        'App\Modules\Finance',
        'App\Modules\Crm',
    ]);

arch('recruiter operations controllers do not depend on attendance')
    ->expect('App\Http\Controllers\RecruiterOperations')
    ->not->toUse('App\Modules\Attendance');

arch('digital marketing task management does not depend on recruiter operations')
    ->expect('App\Modules\TaskManagement')
    ->not->toUse('App\Modules\RecruiterOperations');

arch('attendance does not depend on recruiter operations')
    ->expect('App\Modules\Attendance')
    ->not->toUse('App\Modules\RecruiterOperations');

arch('recruiter operations factories never build digital marketing tasks')
    ->expect('Database\Factories\RecruiterOperations')
    ->not->toUse('App\Modules\TaskManagement');

arch('nothing outside core reaches for the old app models namespace')
    ->expect('App\Models')
    ->toBeUsedInNothing();
