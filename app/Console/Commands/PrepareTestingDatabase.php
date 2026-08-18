<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Support\TestingEnvironment;

class PrepareTestingDatabase extends Command
{
    protected $signature = 'testing:prepare-database';

    protected $description = 'Reset the isolated testing database before running the test suite';

    public function handle(): int
    {
        return TestingEnvironment::prepareDatabase();
    }
}
