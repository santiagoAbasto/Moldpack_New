<?php

namespace Tests\Feature\Security;

use App\Models\ContactInquiry;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laravel skips CSRF checks while running unit tests; this suite re-enables the
 * real middleware to prove legitimate forms pass and forged posts are rejected.
 */
class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    private function token(string $page): string
    {
        $html = $this->get($page)->assertOk()->getContent();
        preg_match('/name="_token"[^>]*value="([^"]+)"/', $html, $match);
        $this->assertNotEmpty($match[1] ?? null, 'Form must render a CSRF token.');

        return $match[1];
    }

    public function test_contact_with_token_passes_and_without_token_is_rejected(): void
    {
        $data = ['name' => 'Ana', 'email' => 'ana@example.com', 'phone' => '1144445555', 'message' => 'Consulta'];

        $this->post('/contacto/consultas', $data)->assertStatus(419);
        $this->post('/contacto/consultas', $data + ['_token' => 'forged'])->assertStatus(419);
        $this->withHeader('Origin', 'https://evil.example')->post('/contacto/consultas', $data)->assertStatus(419);
        $this->assertSame(0, ContactInquiry::count());

        $this->post('/contacto/consultas', $data + ['_token' => $this->token('/contacto')])->assertSessionHas('contact_success');
        $this->assertSame(1, ContactInquiry::count());
    }

    public function test_newsletter_with_token_passes_and_without_token_is_rejected(): void
    {
        $this->post('/newsletter', ['newsletter_email' => 'a@example.com'])->assertStatus(419);
        $this->assertSame(0, NewsletterSubscriber::count());

        $this->post('/newsletter', ['newsletter_email' => 'a@example.com', '_token' => $this->token('/')])->assertSessionHas('newsletter_success');
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_client_login_and_admin_actions_require_token(): void
    {
        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234'])->assertStatus(419);
        $this->post('/admin/login', ['email' => 'admin@moldpack.com.ar', 'password' => 'x'])->assertStatus(419);
    }
}
