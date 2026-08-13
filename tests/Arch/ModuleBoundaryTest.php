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
|     Crm  -> Core
|     Work -> Core
|
| Never Crm -> TaskManagement, never TaskManagement -> Crm, and never
| Core -> either module.
|
*/

arch('crm does not depend on task management')
    ->expect('App\Modules\Crm')
    ->not->toUse('App\Modules\TaskManagement');

arch('task management does not depend on crm')
    ->expect('App\Modules\TaskManagement')
    ->not->toUse('App\Modules\Crm');

arch('the shared kernel does not depend on either module')
    ->expect('App\Modules\Core')
    ->not->toUse(['App\Modules\Crm', 'App\Modules\TaskManagement']);

arch('nothing outside core reaches for the old app models namespace')
    ->expect('App\Models')
    ->toBeUsedInNothing();
