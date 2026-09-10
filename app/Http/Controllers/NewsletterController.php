<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $data = validator($request->all(), ['newsletter_email' => ['required', 'string', 'email:rfc', 'max:254']])->validateWithBag('newsletter');

        // Hidden field that people never see; bots filling every input get the
        // normal success message but nothing is stored.
        if (filled($request->input('newsletter_website'))) {
            Log::notice('Newsletter subscription discarded by honeypot.', ['ip' => $request->ip()]);

            return back()->with('newsletter_success', '¡Gracias! Ya estás en la lista de novedades de Moldpack.');
        }

        NewsletterSubscriber::updateOrCreate(['email' => Str::lower($data['newsletter_email'])], [
            'status' => 'active',
            'unsubscribe_token' => NewsletterSubscriber::where('email', Str::lower($data['newsletter_email']))->value('unsubscribe_token') ?: (string) Str::uuid(),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        return back()->with('newsletter_success', '¡Gracias! Ya estás en la lista de novedades de Moldpack.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();
        $subscriber->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);

        return redirect('/')->with('newsletter_success', 'Tu suscripción fue cancelada correctamente.');
    }
}
