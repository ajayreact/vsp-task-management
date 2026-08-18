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
|
| Core must never import Task Management. The CRM module is gone.
|
*/

arch('task management does not depend on a removed crm module')
    ->expect('App\Modules\TaskManagement')
    ->not->toUse('App\Modules\Crm');

arch('the shared kernel does not depend on task management')
    ->expect('App\Modules\Core')
    ->not->toUse(['App\Modules\Crm', 'App\Modules\TaskManagement']);

arch('nothing outside core reaches for the old app models namespace')
    ->expect('App\Models')
    ->toBeUsedInNothing();
