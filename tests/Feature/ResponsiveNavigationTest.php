<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Database\Seeders\DemoClientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Markup contract for the responsive header. Layout behaviour per breakpoint is
 * verified in the browser (no JS test runner in this project).
 */
class ResponsiveNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_header_has_accessible_hamburger_controlling_the_main_menu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="viewport" content="width=device-width,initial-scale=1">', $html);
        $this->assertSame(1, substr_count($html, 'name="viewport"'));
        $this->assertMatchesRegularExpression('/<button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="site-menu" data-menu-toggle>/', $html);
        $this->assertStringContainsString('<nav id="site-menu" aria-label="Navegación principal">', $html);
        $this->assertSame(2, preg_match_all('/<button[^>]*\sdata-ai-open[\s>]/', $html), 'Desktop search and mobile menu search both open IA Moldpack.');

        foreach (['/nosotros', '/productos', '/catalogo', '/novedades', '/calidad', '/donde-comprar', '/contacto'] as $link) {
            $this->assertStringContainsString('href="'.$link.'"', $html);
        }
    }

    public function test_current_page_is_marked_in_the_mobile_menu(): void
    {
        $html = $this->get('/contacto')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<a class="is-current" href="/contacto"\s+aria-current="page"\s*>Contacto</a>#', $html);
    }

    public function test_private_header_has_its_own_accessible_hamburger_navigation(): void
    {
        $this->seed(DemoClientSeeder::class);
        $this->actingAs(Cliente::where('username', 'demo')->firstOrFail(), 'cliente');

        $this->get(route('client.portal', 'productos'))->assertOk()
            ->assertSee('class="site-header"', false)
            ->assertSee('data-private-header', false)
            ->assertSee('data-private-menu-toggle', false)
            ->assertSee('aria-controls="private-zone-menu"', false)
            ->assertSee('id="private-zone-menu"', false)
            ->assertDontSee('id="site-menu"', false);
    }

    public function test_public_forms_keep_their_contract(): void
    {
        $this->get('/contacto')->assertOk()
            ->assertSee('action="'.route('contact.inquiries.store').'" method="post"', false)
            ->assertSee('name="newsletter_email"', false)
            ->assertSee('action="'.route('newsletter.subscribe').'"', false);
    }

    public function test_all_private_sections_render_with_accessible_responsive_shell(): void
    {
        $this->seed(DemoClientSeeder::class);
        $this->actingAs(Cliente::where('username', 'demo')->firstOrFail(), 'cliente');

        $sections = [
            'productos' => 'client-products.css',
            'carrito' => 'client-cart.css',
            'pedidos' => 'client-orders.css',
            'pagos' => 'client-payments.css',
            'cuenta' => 'client-account.css',
            'facturas' => 'client-invoices.css',
        ];

        foreach ($sections as $section => $cssFile) {
            $response = $this->get(route('client.portal', $section));
            $response->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('client-shell', $html);
            $this->assertStringContainsString(str_replace('.css', '', $cssFile), $html);
            $this->assertStringContainsString('data-private-menu-toggle', $html);
            $this->assertStringContainsString('id="private-zone-menu"', $html);
            $this->assertStringContainsString('data-private-profile', $html);
        }
    }
}
