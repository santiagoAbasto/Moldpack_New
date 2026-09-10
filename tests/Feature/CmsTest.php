<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SiteSetting;
use App\Models\ContactInquiry;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactInquiryReceived;
use App\Mail\ContactInquiryConfirmation;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterSubscriber;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_published_content(): void
    {
        $this->seed();
        $this->get('/')
            ->assertOk()
            ->assertSee('El packaging que hace')
            ->assertSeeInOrder(['Categorías', 'Productos', 'Nosotros', 'Novedades', 'Catálogo de productos']);
    }

    public function test_home_placeholder_links_are_wired_to_public_destinations(): void
    {
        $this->seed();

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('href="#', $html);
        $this->assertStringContainsString('href="'.route('public.page', ['slug' => 'productos']).'"', $html);
        $this->assertStringContainsString(route('public.page', ['slug' => 'productos']).'?categoria=', $html);
        $this->assertStringContainsString('href="'.route('public.page', ['slug' => 'nosotros']).'"', $html);
        $this->assertStringContainsString('href="'.route('public.page', ['slug' => 'novedades']).'"', $html);
        $this->assertStringContainsString('href="'.route('public.page', ['slug' => 'catalogo']).'"', $html);
    }

    public function test_admin_is_protected_by_admin_login_route(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Bienvenido de nuevo');
    }

    public function test_admin_login_has_security_headers(): void
    {
        $response = $this->get('/admin/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Security-Policy');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
    }

    public function test_only_an_administrator_can_authenticate(): void
    {
        $user = User::create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => false,
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_log_in_and_open_cms(): void
    {
        $this->seed();
        $user = User::where('is_admin', true)->firstOrFail();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Cambiar123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Admin/Cms', false)->has('pages', 9),
        );

        $this->get('/dashboard')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Admin/Cms', false)
                ->where('initialModule', 'intelligence')
                ->has('intelligence.metrics'),
        );
    }

    public function test_contact_page_matches_the_public_contact_route(): void
    {
        $this->get('/contacto')
            ->assertOk()
            ->assertSee('Nombre y apellido')
            ->assertSee('Enviar mensaje')
            ->assertSee('https://maps.app.goo.gl/gVUD5k7wC3zZhwbX', false)
            ->assertSee('www.google.com/maps/embed', false);
    }

    public function test_newsletter_subscriptions_and_branded_campaigns_are_ready(): void
    {
        Mail::fake();
        Storage::fake('public');
        $this->post('/newsletter', ['newsletter_email' => 'cliente@example.com'])->assertRedirect()->assertSessionHas('newsletter_success');
        $subscriber = NewsletterSubscriber::firstOrFail();
        $admin = User::create(['name' => 'Admin', 'email' => 'marketing@example.com', 'password' => Hash::make('Password123!'), 'is_admin' => true]);
        $this->actingAs($admin)->post('/admin/newsletter/campaigns', [
            'recipient_ids' => [$subscriber->id], 'subject' => 'Nuevos productos Moldpack', 'preheader' => 'Conocé las novedades',
            'body' => '<p>Presentamos nuestra nueva colección.</p>', 'action_label' => 'Ver productos', 'action_url' => 'https://moldpack.com.ar/productos',
        ])->assertRedirect()->assertSessionHas('success');
        Mail::assertSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo('cliente@example.com'));
        $this->assertDatabaseHas('newsletter_campaigns', ['subject' => 'Nuevos productos Moldpack', 'recipient_count' => 1, 'status' => 'sent']);
        $this->get('/newsletter/baja/'.$subscriber->unsubscribe_token)->assertRedirect('/');
        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    public function test_quality_page_matches_figma_content_and_is_admin_managed(): void
    {
        $this->get('/calidad')->assertOk()->assertSee('Políticas de calidad')->assertSee('Papeles aptos para alimentos')->assertSee('certificados R.N.E. y R.N.P.A.')->assertSee('Descargas')->assertSee('assets/figma/exact/calidad/raw-1.png', false);
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('calidad', false);
    }

    public function test_stores_page_is_public_and_locations_are_managed_by_admin(): void
    {
        $this->seed();
        $this->get('/donde-comprar')
            ->assertOk()
            ->assertSee('CEM PROVISIONES INDUSTRIALES')
            ->assertSee('assets/figma/exact/stores/pin.svg', false)
            ->assertSee('Usar mi ubicación')
            ->assertSee('id="stores-map"', false);

        $admin = User::where('is_admin', true)->firstOrFail();
        $value = [
            'country' => 'Argentina',
            'load_more_label' => 'Cargar más resultados',
            'locations' => [[
                'name' => 'Distribuidor de prueba',
                'address' => 'Dante Alighieri 1377, Don Torcuato, Argentina',
                'phone' => '11 4727 2836',
                'email' => 'ventas@example.com',
                'latitude' => -34.4836,
                'longitude' => -58.6134,
            ]],
        ];

        $this->actingAs($admin)->put('/admin/settings/stores', ['value' => $value])->assertRedirect();
        $this->get('/donde-comprar')->assertOk()->assertSee('Distribuidor de prueba');
    }

    public function test_admin_can_geocode_a_store_address(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([[
            'lat' => '-34.4836', 'lon' => '-58.6134', 'display_name' => 'Don Torcuato, Argentina',
        ]])]);
        $admin = User::create(['name' => 'Admin', 'email' => 'geo@example.com', 'password' => Hash::make('Password123!'), 'is_admin' => true]);

        $this->actingAs($admin)->getJson('/admin/stores/geocode?address=Dante%20Alighieri%201377')
            ->assertOk()
            ->assertJson(['latitude' => -34.4836, 'longitude' => -58.6134]);
    }

    public function test_contact_inquiry_is_stored_and_visible_to_admin(): void
    {
        Mail::fake();
        $this->post('/contacto/consultas', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '11 4444 5555',
            'company' => 'Empresa Demo',
            'message' => 'Quisiera recibir información comercial.',
            'website' => '',
        ])->assertRedirect()->assertSessionHas('contact_success');

        $inquiry = ContactInquiry::firstOrFail();
        $this->assertSame('Juan Pérez', $inquiry->name);
        Mail::assertQueued(ContactInquiryReceived::class);
        Mail::assertQueued(ContactInquiryConfirmation::class, fn ($mail) => $mail->hasTo('juan@example.com'));

        $admin = User::create([
            'name' => 'Administradora',
            'email' => 'admin-contact@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get('/admin')->assertInertia(
            fn (Assert $page) => $page->has('contactInquiries.data', 1)
                ->where('contactInquiries.data.0.email', 'juan@example.com'),
        );

        $this->actingAs($admin)->put('/admin/consultas/'.$inquiry->id.'/read')->assertRedirect();
        $this->assertNotNull($inquiry->fresh()->read_at);
    }

    public function test_catalog_page_and_secure_pdf_management_are_connected(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $section = \App\Models\Section::whereHas('page', fn ($query) => $query->where('slug', 'catalogo'))
            ->where('type', 'catalog_page')->firstOrFail();

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Catálogo completo')
            ->assertSee('¿Buscás solo una categoría?')
            ->assertSee('aria-current="page"', false);

        $this->actingAs($admin)->post('/admin/documents', [
            'owner_type' => 'section',
            'owner_id' => $section->id,
            'file' => UploadedFile::fake()->create('catalogo-moldpack.pdf', 2048, 'application/pdf'),
        ])->assertRedirect();

        $settings = $section->fresh()->settings;
        Storage::disk('public')->assertExists(substr($settings['document_path'], 8));
        $this->get('/catalogo')->assertOk()->assertSee('Descargar catálogo');
    }

    public function test_admin_can_update_a_site_module(): void
    {
        $admin = User::create([
            'name' => 'Administradora',
            'email' => 'admin-module@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->put('/admin/settings/newsletter', [
            'value' => ['title' => 'Recibí nuestras novedades'],
        ])->assertRedirect();

        $this->assertSame(
            'Recibí nuestras novedades',
            SiteSetting::where('key', 'newsletter')->firstOrFail()->value['title'],
        );
    }

    public function test_social_footer_icons_and_page_seo_are_admin_managed(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $home = \App\Models\Page::where('slug', 'inicio')->firstOrFail();

        $this->actingAs($admin)->post('/admin/social-links', [
            'id' => 'instagram', 'name' => 'Instagram', 'url' => 'https://instagram.com/moldpack',
            'icon' => UploadedFile::fake()->image('instagram.png', 64, 64),
        ])->assertRedirect()->assertSessionHas('success');
        $social = SiteSetting::where('key', 'social')->value('value');
        $this->assertSame('https://instagram.com/moldpack', collect($social['links'])->firstWhere('id', 'instagram')['url']);

        $this->actingAs($admin)->delete('/admin/social-links/instagram')
            ->assertRedirect()
            ->assertSessionHas('success', 'Red social eliminada del footer.');
        $social = SiteSetting::where('key', 'social')->value('value');
        $this->assertNull(collect($social['links'])->firstWhere('id', 'instagram'));

        $this->actingAs($admin)->put('/admin/pages/'.$home->id, [
            'name' => $home->name, 'slug' => $home->slug, 'seo_title' => 'Packaging gastronómico profesional | Moldpack',
            'seo_description' => 'Fabricamos packaging gastronómico, moldes y pirotines de calidad para presentar y hornear tus productos en toda Argentina.',
            'seo_keywords' => 'packaging gastronómico, moldes, pirotines', 'canonical_url' => 'https://moldpack.com.ar/',
            'og_title' => 'Packaging gastronómico Moldpack', 'og_description' => 'Conocé las soluciones de Moldpack.',
            'noindex' => false, 'is_published' => true, 'show_on_home' => false,
        ])->assertRedirect();
        $this->get('/')->assertOk()->assertSee('property="og:title" content="Packaging gastronómico Moldpack"', false)->assertSee('application/ld+json', false);
    }

    public function test_user_management_requires_a_strong_confirmed_password(): void
    {
        $admin = User::create([
            'name' => 'Administradora',
            'email' => 'admin-users@example.com',
            'password' => Hash::make('Password123!'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Nueva editora',
            'email' => 'editora@example.com',
            'password' => 'corta',
            'password_confirmation' => 'corta',
            'is_admin' => true,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'editora@example.com']);
    }

    public function test_admin_can_replace_a_slider_image_with_a_video(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $slide = \App\Models\ContentItem::whereHas('section', fn ($query) => $query->where('type', 'hero'))->firstOrFail();

        $this->actingAs($admin)->post('/admin/media', [
            'owner_type' => 'item',
            'owner_id' => $slide->id,
            'kind' => 'video',
            'file' => UploadedFile::fake()->create('portada.mp4', 1024, 'video/mp4'),
            'alt' => 'Video principal de Moldpack',
        ])->assertRedirect();

        $this->assertSame('video', $slide->fresh()->media()->sole()->kind);
        $this->get('/')->assertOk()->assertSee('<video', false)->assertSee('video/mp4');
    }

    public function test_nosotros_page_is_independent_and_connected_to_its_admin_section(): void
    {
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $section = \App\Models\Section::whereHas('page', fn ($query) => $query->where('slug', 'nosotros'))
            ->where('type', 'about_page')
            ->firstOrFail();

        $this->get('/nosotros')
            ->assertOk()
            ->assertSee('Fábrica de Moldes &amp; Packs Gastronómicos', false)
            ->assertSee('Fabricación propia')
            ->assertSee('¿Porque elegirnos?');

        $settings = $section->settings;
        $settings['second_title'] = 'Un equipo que fabrica soluciones';

        $this->actingAs($admin)->put('/admin/sections/'.$section->id, [
            'type' => 'about_page',
            'title' => $section->title,
            'body' => $section->body,
            'settings' => $settings,
            'sort_order' => 0,
            'is_visible' => true,
        ])->assertRedirect();

        $this->get('/nosotros')->assertOk()->assertSee('Un equipo que fabrica soluciones');
        $this->get('/')->assertOk()->assertDontSee('Un equipo que fabrica soluciones');
    }

    public function test_products_index_filter_data_and_detail_are_public(): void
    {
        $this->get('/productos')->assertOk()->assertSee('Inicio')->assertSee('&gt; Productos', false)->assertSee('Pirotines para cupcakes')->assertSee('Psicodélico Multi-color')->assertSee('data-product-catalog', false);
        $this->get('/productos/psicodelico-multi-color')->assertOk()->assertSee('Productos relacionados')->assertSee('Envase apto para alimentos')->assertSee('Consultar');
    }

    public function test_product_featured_switch_controls_home_without_hiding_catalog_item(): void
    {
        $this->seed();
        $page = \App\Models\Page::with('sections.items')->where('slug', 'productos')->firstOrFail();
        $item = $page->sections->firstWhere('type', 'products')->items->first();
        $settings = $item->settings;
        $settings['featured_home'] = false;
        $item->update(['settings' => $settings]);

        $this->get('/')->assertDontSee($item->title);
        $this->get('/productos')->assertSee($item->title);

        $settings['featured_home'] = true;
        $item->update(['settings' => $settings]);
        $this->get('/')->assertSee($item->title);
    }

    public function test_product_related_items_follow_the_manual_admin_selection(): void
    {
        $page = \App\Models\Page::with('sections.items')->where('slug', 'productos')->firstOrFail();
        $items = $page->sections->firstWhere('type', 'products')->items->values();
        $product = $items[0];
        $settings = $product->settings;
        $settings['related_ids'] = [$items[2]->id, $items[1]->id];
        $product->update(['settings' => $settings]);

        $this->get('/productos/'.$settings['slug'])
            ->assertOk()
            ->assertSeeInOrder([$items[2]->title, $items[1]->title]);
    }

    public function test_products_receive_intelligent_relations_until_editor_overrides_them(): void
    {
        $page = \App\Models\Page::with('sections.items')->where('slug', 'productos')->firstOrFail();
        $items = $page->sections->firstWhere('type', 'products')->items->values();
        $product = $items->first();
        $settings = $product->settings;
        $settings['related_ids'] = [];
        $settings['related_manual'] = false;
        $product->update(['settings' => $settings]);

        $related = app(\App\Services\ProductRelationService::class)->resolve($product->fresh(), $items, 4);

        $this->assertCount(4, $related);
        $this->assertFalse($related->contains('id', $product->id));
        $this->assertTrue($related->every(fn ($candidate) => $candidate->is_visible));
    }

    public function test_product_without_available_photo_uses_placeholder(): void
    {
        $page = \App\Models\Page::with('sections.items.media')->where('slug', 'productos')->firstOrFail();
        $product = $page->sections->firstWhere('type', 'products')->items->first();
        $product->media()->delete();

        $this->get('/productos')
            ->assertOk()
            ->assertSee('assets/product-placeholder.svg', false);

        $this->get('/productos/'.$product->settings['slug'])
            ->assertOk()
            ->assertSee('Imagen próximamente', false);
    }

    public function test_news_cards_open_a_public_detail_page(): void
    {
        $this->seed();
        $page = \App\Models\Page::with('sections.items')->where('slug', 'novedades')->firstOrFail();
        $article = $page->sections->firstWhere('type', 'news')->items->firstOrFail();
        $slug = ($article->settings['slug'] ?? null) ?: \Illuminate\Support\Str::slug($article->title).'-'.$article->id;

        $this->get('/novedades')
            ->assertOk()
            ->assertSee('/novedades/'.$slug, false)
            ->assertSee('Leer más');

        $this->get('/novedades/'.$slug)
            ->assertOk()
            ->assertSee('Inicio')
            ->assertSee('Novedades')
            ->assertSee($article->title)
            ->assertSee('Volver a novedades');
    }

    public function test_admin_can_edit_the_complete_client_profile_and_manage_access(): void
    {
        $this->seed();
        $admin = User::where('is_admin', true)->firstOrFail();
        $client = Cliente::create([
            'username' => 'pasteleria-sur', 'name' => 'Pastelería Sur', 'email' => 'sur@example.com',
            'password' => 'ClaveAnterior123!', 'is_active' => false,
        ]);

        $this->actingAs($admin)->put('/admin/clientes/'.$client->id, [
            'username' => 'pasteleria-sur', 'name' => 'María Sur', 'first_name' => 'María', 'last_name' => 'Sur',
            'business_name' => 'Pastelería Sur SRL', 'email' => 'sur@example.com', 'alternate_email' => 'compras@example.com',
            'phone' => '11 4444 5555', 'tax_id' => '30711222333', 'document_id' => '30111222',
            'billing_address' => 'Av. Central 123', 'delivery_address' => 'Depósito 4', 'started_on' => '04/09/2026',
            'discount_percent' => 12.5, 'show_prices' => true, 'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', ['id'=>$client->id, 'first_name'=>'María', 'delivery_address'=>'Depósito 4', 'show_prices'=>1, 'is_active'=>1]);

        $this->actingAs($admin)->post('/admin/clientes/'.$client->id.'/password', [
            'admin_password' => 'Cambiar123!', 'password' => 'NuevaClave123!', 'password_confirmation' => 'NuevaClave123!',
        ])->assertRedirect()->assertSessionHas('success');

        $client->refresh();
        $this->assertTrue(Hash::check('NuevaClave123!', $client->password));
        $this->actingAs($admin)->postJson('/admin/clientes/'.$client->id.'/password/view', [
            'admin_password' => 'Cambiar123!',
        ])->assertOk()->assertJson(['password'=>'NuevaClave123!']);
    }
}
