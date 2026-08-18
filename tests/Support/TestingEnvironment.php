<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class TestingEnvironment
{
    public const EXPECTED_DATABASE = 'vsp_crm_testing';

    /** @var resource|null */
    private static $lockHandle = null;

    public static function boot(): void
    {
        self::applyTestingEnvironmentVariables();
        self::acquireProcessLock();
    }

    public static function prepareDatabase(): int
    {
        self::applyTestingEnvironmentVariables();

        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database !== self::EXPECTED_DATABASE) {
            fwrite(
                STDERR,
                "Refusing to prepare `{$database}` on connection `{$connection}`. Expected `"
                .self::EXPECTED_DATABASE."`.\n",
            );

            return SymfonyCommand::FAILURE;
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        RefreshDatabaseState::$migrated = true;

        return SymfonyCommand::SUCCESS;
    }

    public static function applyTestingEnvironmentVariables(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        $_ENV['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        putenv('DB_CONNECTION=mysql');

        $_ENV['DB_DATABASE'] = self::EXPECTED_DATABASE;
        $_SERVER['DB_DATABASE'] = self::EXPECTED_DATABASE;
        putenv('DB_DATABASE='.self::EXPECTED_DATABASE);
    }

    private static function acquireProcessLock(): void
    {
        $lockPath = dirname(__DIR__, 2).'/storage/framework/testing-suite.lock';

        if (! is_dir(dirname($lockPath))) {
            mkdir(dirname($lockPath), 0777, true);
        }

        self::$lockHandle = fopen($lockPath, 'c+');

        if (self::$lockHandle === false || ! flock(self::$lockHandle, LOCK_EX | LOCK_NB)) {
            fwrite(STDERR, "Another VSP CRM test suite is already running. Wait for it to finish.\n");
            exit(2);
        }

        register_shutdown_function(static function (): void {
            if (self::$lockHandle !== null) {
                flock(self::$lockHandle, LOCK_UN);
                fclose(self::$lockHandle);
            }
        });
    }
}
