<?php

namespace App\Jobs;

use App\Models\SecurityEvent;
use App\Models\WebAnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordWebAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly array $event, public readonly ?array $securityEvent = null) {}

    public function handle(): void
    {
        WebAnalyticsEvent::query()->create($this->event);
        if ($this->securityEvent) SecurityEvent::query()->create($this->securityEvent);
    }
}
