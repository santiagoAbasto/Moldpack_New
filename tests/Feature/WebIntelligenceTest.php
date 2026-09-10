<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\WebAnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_requests_are_recorded_without_query_secrets(): void
    {
        $this->get('/productos?token=super-secret&utm_source=campaign')->assertOk();

        $event = WebAnalyticsEvent::query()->latest()->firstOrFail();
        $this->assertSame('/productos', $event->path);
        $this->assertSame('campaign', $event->utm_source);
        $this->assertStringNotContainsString('super-secret', json_encode($event->getAttributes()));
        $this->assertNotEmpty($event->ip_hash);
        $this->assertNotEmpty($event->ip_masked);
    }

    public function test_security_probe_is_observed_but_not_claimed_as_blocked(): void
    {
        $this->withHeader('User-Agent', 'sqlmap security test')->get('/')->assertOk();

        $event = SecurityEvent::query()->latest()->firstOrFail();
        $this->assertSame('scanner_user_agent', $event->event_type);
        $this->assertSame('observed', $event->action);
        $this->assertSame('medium', $event->risk);
    }

    public function test_dashboard_is_admin_only_and_excluded_from_public_telemetry(): void
    {
        $this->get('/dashboard')->assertRedirect('/admin/login');
        $admin = User::query()->create(['name' => 'Admin Security', 'email' => 'admin-security@example.com', 'password' => bcrypt('Password-Segura-1'), 'is_admin' => true]);
        $before = WebAnalyticsEvent::count();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertHeader('X-Frame-Options', 'DENY');

        $this->assertSame($before, WebAnalyticsEvent::count());
    }
}
