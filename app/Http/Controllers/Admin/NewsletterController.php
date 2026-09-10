<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterDelivery;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'recipient_ids.*' => ['integer', 'exists:newsletter_subscribers,id'],
            'subject' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:240'],
            'body' => ['required', 'string', 'max:50000'],
            'action_label' => ['nullable', 'string', 'max:80'],
            'action_url' => ['nullable', 'url:http,https', 'max:500', 'required_with:action_label'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:8192'],
        ]);

        $subscribers = NewsletterSubscriber::query()->whereIn('id', $data['recipient_ids'])->where('status', 'active')->get();
        if ($subscribers->isEmpty()) return back()->with('error', 'Seleccioná al menos un suscriptor activo.');

        $campaign = NewsletterCampaign::create([
            'subject' => $data['subject'],
            'preheader' => $data['preheader'] ?? null,
            'body' => $this->sanitizeHtml($data['body']),
            'image_path' => $request->file('image')?->store('newsletter/'.date('Y/m'), 'public'),
            'action_label' => $data['action_label'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'status' => 'sending',
            'recipient_count' => $subscribers->count(),
            'created_by' => $request->user()->id,
        ]);

        foreach ($subscribers as $subscriber) {
            $delivery = NewsletterDelivery::create(['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id, 'status' => 'queued']);
            SendNewsletterDelivery::dispatch($delivery->id);
        }
        return back()->with('success', "Campaña preparada para {$subscribers->count()} contactos. Los envíos se procesan de forma segura en segundo plano.");
    }

    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><h2><h3><ul><ol><li><a>');
        $html = preg_replace('/<(?!a\b)([a-z0-9]+)\s+[^>]*>/i', '<$1>', $html) ?? '';
        return preg_replace_callback('/<a\b([^>]*)>/i', function ($match) {
            preg_match('/href=["\']([^"\']+)["\']/i', $match[1], $href);
            $url = isset($href[1]) ? filter_var($href[1], FILTER_VALIDATE_URL) : false;
            return $url && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? '<a href="'.e($url).'">' : '<a>';
        }, $html) ?? '';
    }
}
