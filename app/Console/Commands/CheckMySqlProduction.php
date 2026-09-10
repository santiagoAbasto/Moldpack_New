<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckMySqlProduction extends Command
{
    protected $signature = 'moldpack:mysql:check';

    protected $description = 'Verifies the configured MySQL connection and the migrated Moldpack schema without changing data.';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->components->error('DB_CONNECTION must be mysql. The current connection is '.config('database.default').'.');

            return self::FAILURE;
        }

        try {
            $version = (string) DB::scalar('select version()');
            $tables = Schema::getTables();
            $required = ['users', 'pages', 'products', 'client_orders'];
            $missing = collect($required)->reject(fn (string $table) => Schema::hasTable($table));
        } catch (Throwable $exception) {
            $this->components->error('MySQL could not be reached: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('MySQL connection verified: '.$version);
        $this->line('Database: '.config('database.connections.mysql.database'));
        $this->line('Tables detected: '.count($tables));

        if ($missing->isNotEmpty()) {
            $this->components->error('The database has not been migrated completely. Missing: '.$missing->implode(', ').'.');

            return self::FAILURE;
        }

        $this->components->info('Moldpack schema is ready for the data import.');

        return self::SUCCESS;
    }
}
