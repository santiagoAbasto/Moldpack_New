<?php

namespace Tests\Feature\Security;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regression contract for the footer newsletter form (must keep working). */
class NewsletterSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_footer_form_renders_expected_fields(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('name="newsletter_email"', false)
            ->assertSee('name="newsletter_website"', false);
    }

    public function test_valid_email_subscribes_and_shows_confirmation(): void
    {
        $this->from('/')->post('/newsletter', ['newsletter_email' => 'Cliente@Example.com'])
            ->assertRedirect('/')->assertSessionHas('newsletter_success');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('cliente@example.com', $subscriber->email);
        $this->assertSame('active', $subscriber->status);
        $this->assertNotEmpty($subscriber->unsubscribe_token);

        $this->followRedirects($this->from('/')->post('/newsletter', ['newsletter_email' => 'otro@example.com']))
            ->assertOk()->assertSee('Suscripción confirmada');
    }

    public function test_existing_email_is_handled_without_duplicates_and_keeps_token(): void
    {
        $this->post('/newsletter', ['newsletter_email' => 'repetido@example.com']);
        $token = NewsletterSubscriber::sole()->unsubscribe_token;
        $this->post('/newsletter', ['newsletter_email' => 'REPETIDO@example.com'])->assertSessionHas('newsletter_success');

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame($token, NewsletterSubscriber::sole()->unsubscribe_token);
    }

    public function test_unsubscribe_link_works(): void
    {
        $this->post('/newsletter', ['newsletter_email' => 'baja@example.com']);
        $this->get('/newsletter/baja/'.NewsletterSubscriber::sole()->unsubscribe_token)->assertRedirect('/');
        $this->assertSame('unsubscribed', NewsletterSubscriber::sole()->status);
        $this->get('/newsletter/baja/'.str_repeat('0', 36))->assertNotFound();
    }

    public function test_invalid_and_malicious_input_is_rejected_in_newsletter_bag(): void
    {
        foreach (['', 'no-email', "' OR '1'='1", '<script>alert(1)</script>@x.com', str_repeat('a', 250).'@example.com'] as $value) {
            $this->post('/newsletter', ['newsletter_email' => $value])->assertSessionHasErrorsIn('newsletter', 'newsletter_email');
        }
        $this->post('/newsletter', ['newsletter_email' => ['a@example.com']])->assertSessionHasErrorsIn('newsletter', 'newsletter_email');
        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_honeypot_silently_discards_bot(): void
    {
        $this->post('/newsletter', ['newsletter_email' => 'bot@example.com', 'newsletter_website' => 'spam'])
            ->assertSessionHas('newsletter_success');
        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_rate_limit_stops_bot_and_recovers(): void
    {
        foreach (range(1, 6) as $i) {
            $this->post('/newsletter', ['newsletter_email' => "n{$i}@example.com"])->assertSessionHas('newsletter_success');
        }
        $this->from('/')->post('/newsletter', ['newsletter_email' => 'bot@example.com'])
            ->assertRedirect('/')->assertSessionHasErrorsIn('newsletter', 'newsletter_email');
        $this->assertSame(6, NewsletterSubscriber::count());

        $this->travel(61)->seconds();
        $this->post('/newsletter', ['newsletter_email' => 'humano@example.com'])->assertSessionHas('newsletter_success');
        $this->assertSame(7, NewsletterSubscriber::count());
    }

    public function test_search_usage_does_not_block_newsletter(): void
    {
        foreach (range(1, 20) as $i) $this->getJson('/buscar/ia-moldpack?q=bandejas')->assertOk();
        $this->post('/newsletter', ['newsletter_email' => 'lector@example.com'])->assertSessionHas('newsletter_success');
    }
}
