<?php

namespace App\Jobs;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendNewsletterDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public function __construct(public int $deliveryId) { $this->onQueue('mail'); }
    public function handle(): void
    {
        $delivery = NewsletterDelivery::with(['campaign', 'subscriber'])->findOrFail($this->deliveryId);
        if ($delivery->subscriber->status !== 'active') { $delivery->update(['status' => 'skipped']); $this->refreshCampaign($delivery); return; }
        Mail::to($delivery->subscriber->email)->send(new NewsletterCampaignMail($delivery->campaign, $delivery->subscriber));
        $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        $this->refreshCampaign($delivery);
    }
    public function failed(\Throwable $exception): void
    {
        if ($delivery = NewsletterDelivery::with('campaign')->find($this->deliveryId)) {
            $delivery->update(['status' => 'failed', 'error' => Str::limit($exception->getMessage(), 1000)]);
            $this->refreshCampaign($delivery);
        }
    }
    private function refreshCampaign(NewsletterDelivery $delivery): void
    {
        $campaign = $delivery->campaign;
        $total = $campaign->deliveries()->count();
        $finished = $campaign->deliveries()->whereIn('status', ['sent', 'failed', 'skipped'])->count();
        if ($finished < $total) return;
        $failed = $campaign->deliveries()->where('status', 'failed')->count();
        $sent = $campaign->deliveries()->where('status', 'sent')->count();
        $campaign->update(['status' => $failed ? ($sent ? 'partial' : 'failed') : 'sent', 'sent_at' => now()]);
    }
}
