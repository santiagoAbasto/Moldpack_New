<?php

namespace Tests\Feature;

use Tests\TestCase;

class MySqlProductionCheckTest extends TestCase
{
    public function test_mysql_preflight_explains_when_mysql_is_not_configured(): void
    {
        config()->set('database.default', 'sqlite');

        $this->artisan('moldpack:mysql:check')
            ->expectsOutputToContain('DB_CONNECTION must be mysql')
            ->assertExitCode(1);
    }
}
