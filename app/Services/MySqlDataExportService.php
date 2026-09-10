<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MySqlDataExportService
{
    /**
     * Emits data-only SQL. The production database must be created with the
     * Laravel migrations first, which keeps schema ownership in source control.
     */
    public function stream(callable $write, bool $includeOperational = false): void
    {
        $connection = DB::connection();
        $write("-- Moldpack data export for MySQL 8\n");
        $write('-- Generated at '.now()->toIso8601String()."\n");
        $write("-- Run php artisan migrate --force on MySQL before importing this file.\n\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n\n");

        foreach ($this->tables($includeOperational) as $table) {
            $columns = Schema::getColumnListing($table);
            if (! $columns) continue;
            $write('-- '.$table."\n");
            $identifier = $this->identifier($table);
            $write("DELETE FROM {$identifier};\n");

            $query = DB::table($table);
            $order = in_array('id', $columns, true) ? 'id' : $columns[0];
            foreach ($query->orderBy($order)->cursor() as $record) {
                $values = array_map(fn (string $column) => $this->value($connection, data_get($record, $column)), $columns);
                $columnList = implode(', ', array_map($this->identifier(...), $columns));
                $write("INSERT INTO {$identifier} ({$columnList}) VALUES (".implode(', ', $values).");\n");
            }
            $write("\n");
        }

        $write("COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function tables(bool $includeOperational): array
    {
        $skip = ['migrations', 'cache', 'cache_locks'];
        if (! $includeOperational) $skip = [...$skip, 'jobs', 'failed_jobs'];

        return collect(Schema::getTables())
            ->map(fn (array $table) => $table['name'])
            ->reject(fn (string $table) => str_starts_with($table, 'sqlite_') || in_array($table, $skip, true))
            ->sort()
            ->values()
            ->all();
    }

    private function identifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function value(ConnectionInterface $connection, mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string) $value;

        return $connection->getPdo()->quote((string) $value);
    }
}
