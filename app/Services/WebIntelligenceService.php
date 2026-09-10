<?php

namespace App\Services;

use App\Models\ClientOrder;
use App\Models\ContactInquiry;
use App\Models\NewsletterSubscriber;
use App\Models\SecurityEvent;
use App\Models\WebAnalyticsEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebIntelligenceService
{
    public function dashboard(string $range = '7d'): array
    {
        $days = ['today' => 1, '24h' => 1, '7d' => 7, '30d' => 30, '90d' => 90][$range] ?? 7;
        $from = $range === 'today' ? CarbonImmutable::today() : CarbonImmutable::now()->subDays($days)->startOfHour();
        $available = Schema::hasTable('web_analytics_events');

        if (! $available) return $this->emptyDashboard($range, $from);

        $events = WebAnalyticsEvent::query()->where('occurred_at', '>=', $from);
        $security = SecurityEvent::query()->where('occurred_at', '>=', $from)->whereNull('false_positive_at');
        $pageViews = (clone $events)->where('method', 'GET');
        $humans = (clone $events)->where('visitor_class', 'human');
        $metrics = [
            'visitors' => (clone $humans)->distinct()->count('ip_hash'),
            'sessions' => (clone $humans)->whereNotNull('session_hash')->distinct()->count('session_hash'),
            'pageviews' => (clone $pageViews)->count(),
            'requests' => (clone $events)->count(),
            'bots' => (clone $events)->where('visitor_class', 'likely_bot')->count(),
            'security' => (clone $security)->count(),
            'errors' => (clone $events)->where('status_code', '>=', 400)->count(),
            'avg_response_ms' => (int) round((float) ((clone $events)->avg('duration_ms') ?? 0)),
            'contacts' => ContactInquiry::query()->where('created_at', '>=', $from)->count(),
            'subscribers' => NewsletterSubscriber::query()->where('subscribed_at', '>=', $from)->where('status', 'active')->count(),
            'orders' => ClientOrder::query()->where('created_at', '>=', $from)->count(),
        ];

        return [
            'available' => true, 'range' => $range, 'from' => $from->toIso8601String(), 'metrics' => $metrics,
            'timeline' => (clone $events)->selectRaw('DATE(occurred_at) as day, COUNT(*) as requests, COUNT(DISTINCT ip_hash) as visitors')->groupBy('day')->orderBy('day')->get()->map(fn ($row) => ['day' => $row->day, 'requests' => (int) $row->requests, 'visitors' => (int) $row->visitors]),
            'top_pages' => (clone $pageViews)->select('path')->selectRaw('COUNT(*) as total')->groupBy('path')->orderByDesc('total')->limit(8)->get()->map(fn ($row) => ['label' => $row->path, 'value' => (int) $row->total]),
            'sources' => (clone $events)->select('source')->selectRaw('COUNT(*) as total')->groupBy('source')->orderByDesc('total')->get()->map(fn ($row) => ['label' => $row->source, 'value' => (int) $row->total]),
            'devices' => (clone $events)->select('device')->selectRaw('COUNT(*) as total')->groupBy('device')->orderByDesc('total')->get()->map(fn ($row) => ['label' => $row->device ?: 'unknown', 'value' => (int) $row->total]),
            'statuses' => (clone $events)->select('status_code')->selectRaw('COUNT(*) as total')->groupBy('status_code')->orderBy('status_code')->get()->map(fn ($row) => ['label' => (string) $row->status_code, 'value' => (int) $row->total]),
            'recent' => (clone $events)->latest('occurred_at')->limit(12)->get(['occurred_at','ip_masked','method','path','status_code','duration_ms','device','visitor_class'])->map(fn ($row) => ['time' => $row->occurred_at?->toIso8601String(), 'ip' => $row->ip_masked, 'method' => $row->method, 'path' => $row->path, 'status' => $row->status_code, 'duration' => $row->duration_ms, 'device' => $row->device, 'class' => $row->visitor_class]),
            'security_events' => (clone $security)->latest('occurred_at')->limit(10)->get(['occurred_at','ip_masked','event_type','path','action','risk','status_code'])->map(fn ($row) => ['time' => $row->occurred_at?->toIso8601String(), 'ip' => $row->ip_masked, 'type' => $row->event_type, 'path' => $row->path, 'action' => $row->action, 'risk' => $row->risk, 'status' => $row->status_code]),
            'geography' => ['available' => (clone $events)->whereNotNull('country_code')->exists(), 'provider' => config('web-intelligence.trust_cloudflare_country') ? 'Cloudflare trusted header' : null],
            'health' => ['failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null, 'queue_connection' => config('queue.default'), 'database' => DB::connection()->getDriverName(), 'captured_at' => now()->toIso8601String()],
        ];
    }

    private function emptyDashboard(string $range, CarbonImmutable $from): array
    {
        return ['available' => false, 'range' => $range, 'from' => $from->toIso8601String(), 'metrics' => array_fill_keys(['visitors','sessions','pageviews','requests','bots','security','errors','avg_response_ms','contacts','subscribers','orders'], 0), 'timeline' => [], 'top_pages' => [], 'sources' => [], 'devices' => [], 'statuses' => [], 'recent' => [], 'security_events' => [], 'geography' => ['available' => false, 'provider' => null], 'health' => ['failed_jobs' => null, 'queue_connection' => config('queue.default'), 'database' => DB::connection()->getDriverName(), 'captured_at' => now()->toIso8601String()]];
    }
}
