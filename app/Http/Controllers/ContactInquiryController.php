<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use App\Mail\ContactInquiryConfirmation;
use App\Mail\ContactInquiryReceived;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactInquiryController extends Controller
{
    /** Humans need more than this to fill five fields; scripts post instantly. */
    public const MIN_FILL_SECONDS = 2;

    /** Confirmation copies sent to one address per hour (prevents using the form as a mail relay). */
    private const CONFIRMATIONS_PER_ADDRESS = 3;

    private const SUCCESS = 'Gracias. Recibimos tu consulta y nos pondremos en contacto.';

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:60', 'not_regex:/[\r\n]/'],
            'company' => ['nullable', 'string', 'max:160', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:3000'],
            'website' => ['nullable', 'max:0'],
        ]);

        if ($this->submittedTooFast($request)) {
            Log::notice('Contact form discarded by time trap.', ['ip' => $request->ip()]);

            return back()->with('contact_success', self::SUCCESS);
        }

        unset($data['website']);
        $inquiry = ContactInquiry::create($data);
        try {
            Mail::to(config('mail.contact_to'))->queue(new ContactInquiryReceived($inquiry));
            $confirmationKey = 'contact-confirmation:'.hash('sha256', mb_strtolower($inquiry->email));
            if (! RateLimiter::tooManyAttempts($confirmationKey, self::CONFIRMATIONS_PER_ADDRESS)) {
                RateLimiter::hit($confirmationKey, 3600);
                Mail::to($inquiry->email)->queue(new ContactInquiryConfirmation($inquiry));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('contact_success', self::SUCCESS);
    }

    /**
     * The form carries an encrypted render timestamp. Requests without it (old
     * cached pages, integrations) are still accepted; only an authentic token
     * showing an impossible fill time is discarded.
     */
    private function submittedTooFast(Request $request): bool
    {
        $token = $request->input('form_started');
        if (! is_string($token) || $token === '') return false;

        try {
            $startedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        return now()->timestamp - $startedAt < self::MIN_FILL_SECONDS;
    }
}
