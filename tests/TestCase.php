<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingEnvironment;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->app->environment('testing')) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database !== TestingEnvironment::EXPECTED_DATABASE) {
            $this->fail(
                'Tests must run against `'.TestingEnvironment::EXPECTED_DATABASE."`, not `{$database}`.",
            );
        }
    }
}
