<?php

use App\Modules\Attendance\Providers\AttendanceServiceProvider;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\TaskManagement\Providers\TaskManagementServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    TaskManagementServiceProvider::class,
    AttendanceServiceProvider::class,
    FinanceServiceProvider::class,
];
