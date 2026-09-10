<?php

namespace Tests\Feature\Security;

use App\Mail\ContactInquiryConfirmation;
use App\Mail\ContactInquiryReceived;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Regression contract for the public contact form (must keep working). */
class ContactFormSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '11 4444-5555',
            'company' => 'Pastelería Ñandú S.R.L.',
            'message' => "Hola, quisiera una cotización de pirotines N° 10.\n¡Gracias!",
            'website' => '',
            'form_started' => Crypt::encryptString((string) (now()->timestamp - 30)),
        ], $overrides);
    }

    public function test_contact_page_renders_form_with_csrf_honeypot_and_time_token(): void
    {
        $this->get('/contacto')->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="website"', false)
            ->assertSee('name="form_started"', false)
            ->assertSee('name="message"', false);
    }

    public function test_legitimate_contact_is_stored_notified_and_confirmed(): void
    {
        $this->from('/contacto')->post('/contacto/consultas', $this->payload())
            ->assertRedirect('/contacto')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contact_success');

        $this->assertSame('Pastelería Ñandú S.R.L.', ContactInquiry::sole()->company);
        Mail::assertQueued(ContactInquiryReceived::class);
        Mail::assertQueued(ContactInquiryConfirmation::class, fn ($mail) => $mail->hasTo('juan@example.com'));
        $this->followRedirects($this->from('/contacto')->post('/contacto/consultas', $this->payload(['email' => 'otra@example.com'])))
            ->assertOk()->assertSee('Mensaje enviado');
    }

    public function test_contact_without_time_token_is_still_accepted_for_backward_compatibility(): void
    {
        $payload = $this->payload();
        unset($payload['form_started'], $payload['website'], $payload['company']);
        $this->post('/contacto/consultas', $payload)->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_validation_rejects_missing_invalid_and_oversized_fields(): void
    {
        $this->post('/contacto/consultas', $this->payload(['name' => '', 'email' => 'no-es-email']))
            ->assertSessionHasErrors(['name', 'email']);
        $this->post('/contacto/consultas', $this->payload(['message' => str_repeat('a', 3001)]))
            ->assertSessionHasErrors('message');
        $this->post('/contacto/consultas', $this->payload(['name' => str_repeat('b', 121)]))
            ->assertSessionHasErrors('name');
        $this->post('/contacto/consultas', $this->payload(['message' => ['array' => 'payload']]))
            ->assertSessionHasErrors('message');
        $this->assertSame(0, ContactInquiry::count());
    }

    public function test_header_injection_in_name_is_rejected(): void
    {
        $this->post('/contacto/consultas', $this->payload(['name' => "Ana\r\nBcc: victim@example.com"]))
            ->assertSessionHasErrors('name');
        $this->assertSame(0, ContactInquiry::count());
        Mail::assertNothingQueued();
    }

    public function test_honeypot_blocks_bot(): void
    {
        $this->post('/contacto/consultas', $this->payload(['website' => 'http://spam.example']))->assertSessionHasErrors('website');
        $this->assertSame(0, ContactInquiry::count());
    }

    public function test_time_trap_silently_discards_instant_submissions(): void
    {
        $this->post('/contacto/consultas', $this->payload(['form_started' => Crypt::encryptString((string) now()->timestamp)]))
            ->assertSessionHas('contact_success');
        $this->assertSame(0, ContactInquiry::count());
        Mail::assertNothingQueued();
    }

    public function test_forged_time_token_does_not_block_humans(): void
    {
        $this->post('/contacto/consultas', $this->payload(['form_started' => 'not-a-valid-token']))->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_sql_injection_strings_are_stored_as_plain_text(): void
    {
        foreach (["O'Brien", '" OR "1"="1', "' OR '1'='1", '1 OR 1=1; DROP TABLE contact_inquiries;--'] as $i => $value) {
            $this->post('/contacto/consultas', $this->payload(['name' => $value, 'message' => $value, 'email' => "sqli{$i}@example.com"]))->assertSessionHas('contact_success');
        }
        $this->assertSame(4, ContactInquiry::count());
        $this->assertSame("O'Brien", ContactInquiry::orderBy('id')->first()->name);
    }

    public function test_xss_payload_is_escaped_in_emails(): void
    {
        $xss = '<script>alert(1)</script><img src=x onerror=alert(1)>';
        $this->post('/contacto/consultas', $this->payload(['message' => $xss, 'name' => 'Ana <b>x</b>']))->assertSessionHas('contact_success');

        Mail::assertQueued(ContactInquiryReceived::class, function ($mail) {
            $html = $mail->render();
            return ! str_contains($html, '<script>alert(1)</script>') && ! str_contains($html, '<img src=x')
                && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;');
        });
    }

    public function test_confirmation_mail_cannot_be_used_to_flood_one_address(): void
    {
        foreach (range(1, 5) as $i) {
            $this->travel(10)->minutes();
            $this->post('/contacto/consultas', $this->payload(['form_started' => Crypt::encryptString((string) (now()->timestamp - 30))]))->assertSessionHas('contact_success');
        }
        $this->assertSame(5, ContactInquiry::count());
        Mail::assertQueued(ContactInquiryReceived::class, 5);
        Mail::assertQueued(ContactInquiryConfirmation::class, 3);
    }

    public function test_rate_limit_stops_bot_and_recovers_for_legitimate_user(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/contacto/consultas', $this->payload(['email' => "user{$i}@example.com"]))->assertSessionHas('contact_success');
        }

        $this->from('/contacto')->post('/contacto/consultas', $this->payload(['email' => 'bot@example.com']))
            ->assertRedirect('/contacto')->assertSessionHasErrors('message');
        $this->assertSame(5, ContactInquiry::count());

        $this->travel(61)->seconds();
        $this->post('/contacto/consultas', $this->payload(['email' => 'human@example.com', 'form_started' => Crypt::encryptString((string) (now()->timestamp - 30))]))
            ->assertSessionHas('contact_success');
        $this->assertSame(6, ContactInquiry::count());
    }

    public function test_other_endpoints_do_not_consume_the_contact_quota(): void
    {
        foreach (range(1, 20) as $i) $this->getJson('/buscar/ia-moldpack?q=pirotines')->assertOk();
        foreach (range(1, 5) as $i) $this->post('/newsletter', ['newsletter_email' => "n{$i}@example.com"]);
        $this->post('/area-clientes/login', ['username' => 'nadie', 'password' => 'x']);

        $this->post('/contacto/consultas', $this->payload())->assertSessionHasNoErrors()->assertSessionHas('contact_success');
    }

    public function test_json_client_receives_429_when_limited(): void
    {
        foreach (range(1, 5) as $i) $this->post('/contacto/consultas', $this->payload(['email' => "j{$i}@example.com"]));
        $this->postJson('/contacto/consultas', $this->payload())->assertStatus(429);
    }
}
