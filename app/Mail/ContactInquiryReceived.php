<?php

namespace App\Mail;

use App\Models\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryReceived extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public ContactInquiry $inquiry) {}
    public function envelope(): Envelope { return new Envelope(subject: 'Nueva consulta web de '.$this->inquiry->name, replyTo: [$this->inquiry->email]); }
    public function content(): Content { return new Content(view: 'emails.contact-inquiry-received'); }
}
