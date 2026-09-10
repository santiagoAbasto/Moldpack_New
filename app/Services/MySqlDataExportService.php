<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MySqlDataExportService
{
    /** Emits a self-contained MySQL 8 schema and data backup. */
    public function stream(callable $write, bool $includeOperational = true): void
    {
        $connection = DB::connection();
        $tables = $this->tables($includeOperational);
        $write("-- Moldpack complete export for MySQL 8\n");
        $write('-- Generated at '.now()->toIso8601String()."\n");
        $write("-- Import this file into the selected empty MySQL database with phpMyAdmin.\n\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach (array_reverse($tables) as $table) {
            $write('DROP TABLE IF EXISTS '.$this->identifier($table).";\n");
        }
        $write("\n");

        foreach ($tables as $table) {
            $this->writeCreateTable($write, $table);
        }
        $write("\nSTART TRANSACTION;\n\n");

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            if (! $columns) continue;
            $write('-- '.$table."\n");
            $identifier = $this->identifier($table);

            $query = DB::table($table);
            $order = in_array('id', $columns, true) ? 'id' : $columns[0];
            foreach ($query->orderBy($order)->cursor() as $record) {
                $values = array_map(fn (string $column) => $this->value($connection, data_get($record, $column)), $columns);
                $columnList = implode(', ', array_map($this->identifier(...), $columns));
                $write("INSERT INTO {$identifier} ({$columnList}) VALUES (".implode(', ', $values).");\n");
            }
            $write("\n");
        }

        $write("COMMIT;\n\n");
        foreach ($tables as $table) {
            $this->writeForeignKeys($write, $table);
        }
        $write("\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function tables(bool $includeOperational): array
    {
        $skip = ['sqlite_sequence'];
        if (! $includeOperational) $skip = [...$skip, 'jobs', 'failed_jobs', 'cache', 'cache_locks'];

        return collect(Schema::getTables())
            ->map(fn (array $table) => $table['name'])
            ->reject(fn (string $table) => str_starts_with($table, 'sqlite_') || in_array($table, $skip, true))
            ->sort()
            ->values()
            ->all();
    }

    private function writeCreateTable(callable $write, string $table): void
    {
        $columns = Schema::getColumns($table);
        $definitions = collect($columns)->map(function (array $column): string {
            $definition = $this->identifier($column['name']).' '.$this->mysqlType($column);
            $definition .= $column['nullable'] ? ' NULL' : ' NOT NULL';
            if ($column['default'] !== null) $definition .= ' DEFAULT '.$column['default'];
            if ($column['auto_increment']) $definition .= ' AUTO_INCREMENT';

            return $definition;
        })->all();

        foreach (Schema::getIndexes($table) as $index) {
            $columns = implode(', ', array_map($this->identifier(...), $index['columns']));
            if ($index['primary']) $definitions[] = 'PRIMARY KEY ('.$columns.')';
            elseif ($index['unique']) $definitions[] = 'UNIQUE KEY '.$this->identifier($index['name']).' ('.$columns.')';
            else $definitions[] = 'KEY '.$this->identifier($index['name']).' ('.$columns.')';
        }

        $write('CREATE TABLE '.$this->identifier($table)." (\n  ".implode(",\n  ", $definitions)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");
    }

    private function writeForeignKeys(callable $write, string $table): void
    {
        foreach (Schema::getForeignKeys($table) as $foreign) {
            $columns = implode(', ', array_map($this->identifier(...), $foreign['columns']));
            $foreignColumns = implode(', ', array_map($this->identifier(...), $foreign['foreign_columns']));
            $name = $foreign['name'] ?: $table.'_'.implode('_', $foreign['columns']).'_foreign';
            $delete = strtoupper($foreign['on_delete'] ?? 'NO ACTION');
            $update = strtoupper($foreign['on_update'] ?? 'NO ACTION');
            $write('ALTER TABLE '.$this->identifier($table).' ADD CONSTRAINT '.$this->identifier($name).' FOREIGN KEY ('.$columns.') REFERENCES '.$this->identifier($foreign['foreign_table']).' ('.$foreignColumns.") ON DELETE {$delete} ON UPDATE {$update};\n");
        }
    }

    private function mysqlType(array $column): string
    {
        $name = $column['name'];
        $type = strtolower($column['type_name'] ?? $column['type'] ?? 'text');

        return match ($type) {
            'integer', 'int', 'bigint' => $column['auto_increment'] || $name === 'id' || str_ends_with($name, '_id') || str_ends_with($name, '_by') ? 'BIGINT UNSIGNED' : 'INT',
            'tinyint', 'boolean', 'bool' => 'TINYINT(1)',
            'numeric', 'decimal', 'float', 'double' => 'DECIMAL(20,4)',
            'date' => 'DATE',
            'datetime', 'timestamp' => 'DATETIME',
            'varchar', 'character varying' => 'VARCHAR(255)',
            'char' => 'CHAR(255)',
            default => 'LONGTEXT',
        };
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
