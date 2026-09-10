<?php

namespace Tests\Feature\Security;

use App\Models\ContactInquiry;
use App\Models\NewsletterSubscriber;
use App\Support\MailFallback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/** Contact and newsletter must keep working without (or with broken) SMTP. */
class MailResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['queue.default' => 'sync']);
    }

    private function contact(): \Illuminate\Testing\TestResponse
    {
        return $this->from('/contacto')->post('/contacto/consultas', [
            'name' => 'Ana', 'email' => 'ana@example.com', 'phone' => '1144445555', 'message' => 'Consulta',
            'form_started' => Crypt::encryptString((string) (now()->timestamp - 30)),
        ]);
    }

    public function test_missing_smtp_host_falls_back_to_log_mailer(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '']);
        MailFallback::apply();
        $this->assertSame('log', config('mail.default'));

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'mail.moldpack.com.ar']);
        MailFallback::apply();
        $this->assertSame('smtp', config('mail.default'));
    }

    public function test_contact_works_without_smtp_credentials(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '', 'mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null]);
        MailFallback::apply();

        $this->contact()->assertRedirect('/contacto')->assertSessionHasNoErrors()->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_contact_works_when_smtp_server_is_unreachable(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);

        $this->contact()->assertRedirect('/contacto')->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_contact_works_with_empty_recipient_addresses(): void
    {
        config(['mail.default' => 'log', 'mail.contact_to' => '']);

        $this->contact()->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_newsletter_works_without_smtp_credentials(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->from('/')->post('/newsletter', ['newsletter_email' => 'lector@example.com'])
            ->assertRedirect('/')->assertSessionHas('newsletter_success');
        $this->assertSame('active', NewsletterSubscriber::sole()->status);
    }
}
