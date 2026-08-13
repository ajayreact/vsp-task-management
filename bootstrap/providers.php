<?php

use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Crm\Providers\CrmServiceProvider;
use App\Modules\TaskManagement\Providers\TaskManagementServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    CrmServiceProvider::class,
    TaskManagementServiceProvider::class,
];
