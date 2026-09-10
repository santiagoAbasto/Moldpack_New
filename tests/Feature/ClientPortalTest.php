<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ContentItem;
use App\Models\Page;
use App\Models\Section;
use Database\Seeders\DemoClientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoClientSeeder::class);
    }

    public function test_demo_client_can_login_and_open_every_figma_screen(): void
    {
        $response = $this->post(route('client.login'), ['username' => 'demo', 'password' => 'demo1234']);
        $response->assertRedirect(route('client.portal', 'productos'));

        foreach (['productos', 'carrito', 'pedidos', 'pagos', 'cuenta', 'facturas'] as $section) {
            $this->get(route('client.portal', $section))->assertOk()->assertSee('Zona privada');
        }
    }

    public function test_private_area_reuses_public_shell_and_requires_confirmation_to_leave(): void
    {
        $client = Cliente::where('username', 'demo')->firstOrFail();
        $this->actingAs($client, 'cliente');

        $this->get(route('client.portal', 'productos'))
            ->assertOk()
            ->assertSee('class="site-header"', false)
            ->assertSee('class="site-footer"', false)
            ->assertSee('class="whatsapp-float"', false)
            ->assertSee('data-private-profile-menu', false)
            ->assertSee('Cerrar sesión')
            ->assertSee('data-client-exit', false);

        $this->get('/contacto')
            ->assertRedirect(route('client.portal', 'productos'))
            ->assertSessionHas('client_public_destination', '/contacto');

        $this->post(route('client.logout'), ['redirect_to' => '/contacto'])
            ->assertRedirect('/contacto');
        $this->assertGuest('cliente');
    }

    public function test_cart_payment_reporting_and_invoice_download_are_wired(): void
    {
        Storage::fake('public');
        $client = Cliente::where('username', 'demo')->firstOrFail();
        $this->actingAs($client, 'cliente');
        $invoice = $client->orders()->firstWhere('number', 'MP-DEMO-1001')->invoices()->firstOrFail();

        $this->post(route('client.payments.store'), [
            'paid_at' => now()->toDateString(), 'amount' => 1500, 'bank' => 'Banco Nación',
            'invoice_ids' => [$invoice->id], 'receipt' => UploadedFile::fake()->image('comprobante.jpg'),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('client_payment_reports', ['cliente_id' => $client->id, 'amount' => 1500]);
        $this->get(route('client.invoices.download', $invoice))->assertOk()->assertHeader('content-disposition');
    }

    public function test_cart_matches_the_figma_flow_and_keeps_purchasable_presentations_consistent(): void
    {
        $client = Cliente::where('username', 'demo')->firstOrFail();
        $this->actingAs($client, 'cliente');
        $page = Page::query()->where('slug', 'productos')->firstOrFail();
        $section = Section::query()->firstOrCreate(
            ['page_id' => $page->id, 'type' => 'products'],
            ['title' => 'Productos', 'is_visible' => true],
        );
        $product = ContentItem::create([
            'section_id' => $section->id,
            'title' => 'Pirotines de prueba',
            'subtitle' => 'Pirotines / Flúor',
            'settings' => ['presentations' => [
                ['code' => 'NO-VENDIBLE', 'name' => 'Sin precio', 'price' => 0],
                ['code' => 'PIR-A', 'name' => 'Nº 8 U/100', 'price' => 100],
                ['code' => 'PIR-B', 'name' => 'Nº 10 U/210', 'price' => 200],
            ]],
            'is_visible' => true,
        ]);

        $this->post(route('client.cart.add'), [
            'product_id' => $product->id,
            'presentation_index' => 1,
            'quantity' => 2,
        ])->assertRedirect(route('client.portal', 'carrito'));

        $this->get(route('client.portal', 'carrito'))
            ->assertOk()
            ->assertSee('PIR-B')
            ->assertSee('+Agregar más productos')
            ->assertSee('Información importante')
            ->assertSee('No se aceptan cambios o devoluciones por pedidos mal realizados.')
            ->assertSee('assets/figma/private/cart-trash.svg', false)
            ->assertSee('assets/figma/private/cart-upload.svg', false)
            ->assertSee('data-cart-presentation', false)
            ->assertDontSee('onchange="this.form.submit()"', false);

        $this->patch(route('client.cart.update', $product->id.':1'), [
            'presentation_index' => 0,
            'quantity' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, session('client_cart')[$product->id.':0']['quantity']);
        $this->get(route('client.portal', 'carrito'))->assertOk()->assertSee('PIR-A');
    }

    public function test_ia_moldpack_searches_cms_and_understands_questions(): void
    {
        $page = Page::query()->where('slug', 'productos')->firstOrFail();
        $section = Section::query()->firstOrCreate(
            ['page_id' => $page->id, 'type' => 'products'],
            ['title' => 'Productos', 'is_visible' => true],
        );
        ContentItem::create([
            'section_id' => $section->id,
            'title' => 'Pirotín para cupcakes',
            'subtitle' => 'Pirotines / Colores flúor',
            'settings' => ['slug' => 'pirotin-cupcakes-test', 'presentations' => [['code' => 'ZZSEARCH99', 'name' => 'Nº10 U/210', 'price' => 15733]]],
            'is_visible' => true,
        ]);

        $this->getJson(route('moldpack-ai.search', ['q' => 'pirotin cupcakes']))
            ->assertOk()->assertJsonFragment(['title' => 'Pirotín para cupcakes']);
        $this->getJson(route('moldpack-ai.search', ['q' => 'ZZSEARCH99']))
            ->assertOk()->assertJsonPath('results.0.title', 'Pirotín para cupcakes');
    }
}
