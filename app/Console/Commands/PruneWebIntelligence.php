<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\WebAnalyticsEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneWebIntelligence extends Command
{
    protected $signature = 'web-intelligence:prune {--dry-run : Informa qué se borraría sin borrar}';
    protected $description = 'Elimina telemetría vencida únicamente cuando existe una política de retención configurada.';

    public function handle(): int
    {
        $sets = [
            ['web_analytics_events', WebAnalyticsEvent::query(), config('web-intelligence.raw_retention_days')],
            ['security_events', SecurityEvent::query(), config('web-intelligence.security_retention_days')],
            ['admin_audit_logs', DB::table('admin_audit_logs'), config('web-intelligence.audit_retention_days')],
        ];

        foreach ($sets as [$table, $query, $days]) {
            if (! Schema::hasTable($table) || ! is_numeric($days) || (int) $days < 1) {
                $this->line("{$table}: sin política activa.");
                continue;
            }
            $expired = $query->where('occurred_at', '<', now()->subDays((int) $days));
            $count = (clone $expired)->count();
            if (! $this->option('dry-run')) $expired->delete();
            $this->info("{$table}: ".($this->option('dry-run') ? 'se eliminarían' : 'eliminados')." {$count} registros.");
        }
        return self::SUCCESS;
    }
}
