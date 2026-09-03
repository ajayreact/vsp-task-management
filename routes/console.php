<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('files:cleanup')->daily();
Schedule::command('tasks:send-due-reminders')->everyMinute();
Schedule::command('tasks:process-recurring')->everyMinute();
