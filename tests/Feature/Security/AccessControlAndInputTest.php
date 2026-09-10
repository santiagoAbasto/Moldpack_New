<?php

namespace Tests\Feature\Security;

use App\Models\Cliente;
use App\Models\ClientInvoice;
use App\Models\ClientOrder;
use App\Models\ContentItem;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Authorization, IDOR, mass assignment, uploads, exports and headers. */
class AccessControlAndInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    private function invoiceFor(Cliente $client, string $number): ClientInvoice
    {
        $order = ClientOrder::create(['cliente_id' => $client->id, 'number' => 'T-'.$number, 'subtotal' => 100, 'tax_total' => 21, 'total' => 121]);

        return $order->invoices()->create(['type' => 'A', 'number' => $number, 'status' => 'issued', 'subtotal' => 100, 'tax_total' => 21, 'total' => 121, 'issued_at' => now()]);
    }

    public function test_client_cannot_download_another_clients_invoice(): void
    {
        $owner = Cliente::where('username', 'demo')->firstOrFail();
        $other = Cliente::create(['username' => 'otro', 'name' => 'Otro', 'email' => 'otro@example.com', 'password' => 'Password123', 'is_active' => true]);
        $invoice = $this->invoiceFor($owner, 'A-0001-99999999');

        $this->actingAs($other, 'cliente')->get(route('client.invoices.download', $invoice))->assertForbidden();
        $this->actingAs($owner, 'cliente')->get(route('client.invoices.download', $invoice))->assertOk();
    }

    public function test_clients_and_non_admin_users_cannot_reach_admin(): void
    {
        $this->assertContains($this->actingAs(Cliente::where('username', 'demo')->first(), 'cliente')->get('/admin')->status(), [302, 403]);
        $this->assertContains($this->actingAs(Cliente::where('username', 'demo')->first(), 'cliente')->put('/admin/settings/contact', ['value' => ['intro' => 'x']])->status(), [302, 403]);
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $user = User::create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => Hash::make('Password-Segura-1'), 'is_admin' => false]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->post('/admin/users', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'Password-Segura-1', 'password_confirmation' => 'Password-Segura-1', 'is_admin' => true])->assertForbidden();
        $this->assertFalse(User::where('email', 'x@example.com')->exists());
    }

    public function test_non_admin_cannot_log_into_admin(): void
    {
        User::create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => Hash::make('Password-Segura-1'), 'is_admin' => false]);
        $this->post('/admin/login', ['email' => 'editor@example.com', 'password' => 'Password-Segura-1', 'is_admin' => 1])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_public_registration_cannot_self_approve_or_set_privileged_fields(): void
    {
        $this->post('/area-clientes/registro', [
            'username' => 'nuevo', 'name' => 'Nuevo', 'email' => 'nuevo@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123',
            'is_active' => 1, 'discount_percent' => 90, 'approved_at' => now()->toDateTimeString(), 'show_prices' => 1, 'password_encrypted' => 'x',
        ])->assertRedirect('/');

        $client = Cliente::where('username', 'nuevo')->firstOrFail();
        $this->assertFalse($client->is_active);
        $this->assertEquals(0, (float) $client->discount_percent);
        $this->assertNull($client->approved_at);
        $this->assertNull($client->password_encrypted);
        $this->assertTrue(Hash::check('Password123', $client->password));
    }

    public function test_client_login_is_rate_limited_per_account_and_recovers(): void
    {
        foreach (range(1, 5) as $i) $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'incorrecta'])->assertSessionHasErrors('username');
        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234'])->assertSessionHasErrors('username');
        $this->assertFalse(auth('cliente')->check());

        $this->travel(61)->seconds();
        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234'])->assertRedirect(route('client.portal', 'productos'));
    }

    public function test_client_login_does_not_reveal_whether_user_exists(): void
    {
        $unknown = $this->post('/area-clientes/login', ['username' => 'no-existe', 'password' => 'x'])->getSession()->get('errors')->first('username');
        $wrong = $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'x'])->getSession()->get('errors')->first('username');
        $this->assertSame($unknown, $wrong);
    }

    public function test_search_treats_injection_as_text(): void
    {
        foreach (["' OR '1'='1", '1 OR 1=1', '"; DROP TABLE pages;--', '<script>alert(1)</script>'] as $q) {
            $this->getJson('/buscar/ia-moldpack?q='.urlencode($q))->assertOk()->assertJsonStructure(['answer', 'results']);
        }
        $this->getJson('/buscar/ia-moldpack?q='.str_repeat('a', 181))->assertStatus(422);
        $this->assertTrue(\App\Models\Page::query()->exists());
    }

    public function test_malicious_svg_is_rejected_and_clean_svg_is_accepted(): void
    {
        Storage::fake('public');
        $section = Section::firstOrFail();
        $upload = fn (string $name, string $content) => $this->actingAs($this->admin())->post('/admin/media', ['owner_type' => 'section', 'owner_id' => $section->id, 'kind' => 'image', 'file' => UploadedFile::fake()->createWithContent($name, $content)]);

        $upload('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')->assertSessionHasErrors('file');
        $upload('y.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="1" height="1"/></svg>')->assertSessionHasErrors('file');
        $upload('z.svg', '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>')->assertSessionHasErrors('file');
        $this->assertSame([], Storage::disk('public')->allFiles());

        $upload('ok.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#691b3d"/></svg>')->assertSessionHas('success');
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_executable_and_spoofed_uploads_are_rejected_or_neutralized(): void
    {
        Storage::fake('public');
        $section = Section::firstOrFail();
        $post = fn (UploadedFile $file) => $this->actingAs($this->admin())->post('/admin/media', ['owner_type' => 'section', 'owner_id' => $section->id, 'kind' => 'image', 'file' => $file]);

        // Real temp files so the MIME type comes from content (finfo), exactly as in production.
        $real = function (string $name, string $content): UploadedFile {
            $path = tempnam(sys_get_temp_dir(), 'upl');
            file_put_contents($path, $content);
            return new UploadedFile($path, $name, null, null, true);
        };
        $this->assertSame(422, $post($real('shell.php', '<?php system($_GET["c"]); ?>'))->status());
        $this->assertSame(422, $post($real('photo.jpg.php', '<?php echo 1; ?>'))->status());
        $this->assertSame(422, $post($real('fake.png', '<?php echo 1; ?>'))->status());
        $this->assertSame(422, $post($real('polyglot.gif.phtml', 'GIF89a<?php echo 1; ?>'))->status());
        $this->assertSame([], Storage::disk('public')->allFiles());

        $post(UploadedFile::fake()->image('../../evil.png', 20, 20))->assertSessionHas('success');
        $stored = Storage::disk('public')->allFiles();
        $this->assertCount(1, $stored);
        $this->assertMatchesRegularExpression('#^cms/\d{4}/\d{2}/[A-Za-z0-9]{40}\.png$#', $stored[0]);

        $this->actingAs($this->admin())->post('/admin/documents', ['owner_type' => 'section', 'owner_id' => $section->id, 'file' => UploadedFile::fake()->createWithContent('catalogo.pdf.php', '<?php echo 1; ?>')])->assertSessionHasErrors('file');
    }

    public function test_client_payment_receipt_rejects_executables(): void
    {
        Storage::fake('public');
        $this->actingAs(Cliente::where('username', 'demo')->first(), 'cliente')->post(route('client.payments.store'), [
            'paid_at' => now()->toDateString(), 'amount' => 100, 'bank' => 'Banco', 'receipt' => UploadedFile::fake()->createWithContent('recibo.php', '<?php echo 1; ?>'),
        ])->assertSessionHasErrors('receipt');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_social_icon_svg_with_script_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post('/admin/social-links', ['name' => 'X', 'url' => 'https://x.com/moldpack', 'icon' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])
            ->assertSessionHasErrors('icon');
    }

    public function test_csv_export_neutralizes_formulas(): void
    {
        $this->post('/area-clientes/registro', ['username' => 'formula', 'name' => '=HYPERLINK("http://evil.example","clic")', 'business_name' => '+cmd|x', 'email' => 'formula@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123']);
        $csv = $this->actingAs($this->admin())->get('/admin/zona-privada/exportar/clientes')->streamedContent();

        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+cmd|x", $csv);
    }

    public function test_content_links_reject_script_schemes(): void
    {
        $item = ContentItem::firstOrFail();
        foreach (['javascript:alert(1)', ' JavaScript:alert(1)', 'data:text/html,<script>alert(1)</script>', 'vbscript:x'] as $url) {
            $this->actingAs($this->admin())->put("/admin/items/{$item->id}", ['title' => 'x', 'url' => $url])->assertSessionHasErrors('url');
        }
        $this->actingAs($this->admin())->put("/admin/items/{$item->id}", ['title' => 'x', 'url' => '/contacto'])->assertSessionHasNoErrors();
    }

    public function test_public_and_admin_responses_send_security_headers(): void
    {
        foreach (['/', '/contacto', '/productos'] as $path) {
            $this->get($path)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $this->get('/admin/login')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Content-Security-Policy');
        $this->actingAs(Cliente::where('username', 'demo')->first(), 'cliente')->get('/area-clientes/pedidos')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_errors_do_not_leak_debug_information_in_production_mode(): void
    {
        config(['app.debug' => false]);
        $this->get('/productos/'.urlencode("' OR 1=1--"))->assertNotFound()->assertDontSee('SQLSTATE')->assertDontSee('vendor/laravel');
        $this->get('/..%2F..%2F.env')->assertNotFound();
        $this->get('/area-clientes/'.urlencode('../../.env'))->assertNotFound();
    }
}
