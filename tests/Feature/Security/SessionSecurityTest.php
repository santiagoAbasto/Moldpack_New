<?php

namespace Tests\Feature\Security;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Sessions, cookies and credential handling. */
class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::create(['name' => 'Sec Admin', 'email' => 'sec-admin@example.com', 'password' => Hash::make('Password-Segura-1'), 'is_admin' => true]);
    }

    public function test_session_cookie_configuration_is_hardened(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('strict', config('session.same_site'));
        $this->assertTrue((bool) config('session.encrypt'));
        $this->assertNotSame('cookie', config('session.driver'));
        $this->assertSame([], (fn () => static::$neverEncrypt)->call(new \Illuminate\Cookie\Middleware\EncryptCookies(app('encrypter'))));
    }

    public function test_admin_login_regenerates_session_id(): void
    {
        $this->admin();
        $this->get('/admin/login');
        $before = session()->getId();

        $this->post('/admin/login', ['email' => 'sec-admin@example.com', 'password' => 'Password-Segura-1'])->assertRedirect('/admin');
        $this->assertNotSame($before, session()->getId());
    }

    public function test_client_login_regenerates_session_id(): void
    {
        $this->get('/');
        $before = session()->getId();
        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234'])->assertRedirect(route('client.portal', 'productos'));
        $this->assertNotSame($before, session()->getId());
        $this->assertTrue(Auth::guard('cliente')->check());
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $this->admin();
        $this->post('/admin/login', ['email' => 'sec-admin@example.com', 'password' => 'Password-Segura-1']);
        $id = session()->getId();
        $token = session()->token();

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertNotSame($id, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_client_logout_invalidates_session_and_rejects_open_redirects(): void
    {
        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234']);
        $this->post('/area-clientes/salir', ['redirect_to' => '//evil.example'])->assertRedirect('/');
        $this->assertFalse(Auth::guard('cliente')->check());
        $this->get('/area-clientes/pedidos')->assertRedirect('/');

        $this->post('/area-clientes/login', ['username' => 'demo', 'password' => 'demo1234']);
        $response = $this->post('/area-clientes/salir', ['redirect_to' => '/\\evil.example']);
        $this->assertSame(parse_url(config('app.url'), PHP_URL_HOST), parse_url($response->headers->get('Location'), PHP_URL_HOST));
    }

    public function test_tampered_session_and_remember_cookies_are_rejected(): void
    {
        $recaller = Auth::guard('cliente')->getRecallerName();
        $this->withCookie(config('session.cookie'), 'tampered-value')->get('/area-clientes/pedidos')->assertRedirect('/');
        $this->withCookie($recaller, '1|forged-token|$2y$04$forged')->get('/area-clientes/pedidos')->assertRedirect('/');
        $this->withCookie(Auth::guard('web')->getRecallerName(), '1|x|y')->get('/admin')->assertRedirect('/admin/login');
        $this->withCookie('role', 'admin')->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_remember_token_is_random_and_rotates_on_password_change(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => 'sec-admin@example.com', 'password' => 'Password-Segura-1', 'remember' => '1']);
        $first = $admin->fresh()->remember_token;
        $this->assertSame(60, strlen((string) $first));

        $this->put("/admin/users/{$admin->id}", ['name' => 'Sec Admin', 'email' => 'sec-admin@example.com', 'password' => 'Otra-Clave-Segura-2', 'password_confirmation' => 'Otra-Clave-Segura-2', 'is_admin' => true])->assertRedirect();
        $this->assertNotSame($first, $admin->fresh()->remember_token);
        $this->assertTrue(Hash::check('Otra-Clave-Segura-2', $admin->fresh()->password));
    }

    public function test_admin_session_is_invalidated_after_password_change_elsewhere(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['email' => 'sec-admin@example.com', 'password' => 'Password-Segura-1']);
        $this->get('/admin')->assertOk();

        $admin->forceFill(['password' => Hash::make('Cambiada-En-Otro-Lado-3')])->save();
        $this->app['auth']->forgetGuards();

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_client_password_change_by_admin_rotates_remember_token(): void
    {
        $admin = $this->admin();
        $client = Cliente::where('username', 'demo')->firstOrFail();
        $client->forceFill(['remember_token' => 'old-token'])->save();

        $this->actingAs($admin)->post("/admin/clientes/{$client->id}/password", ['admin_password' => 'Password-Segura-1', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])->assertRedirect();
        $this->assertNotSame('old-token', $client->fresh()->remember_token);
    }

    public function test_passwords_and_hashes_are_never_sent_to_the_browser(): void
    {
        $admin = $this->admin();
        $inertia = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true'])->get('/admin')->getContent();
        $portal = $this->actingAs(Cliente::where('username', 'demo')->first(), 'cliente')->get('/area-clientes/cuenta')->getContent();

        foreach ([$inertia, $portal] as $body) {
            $this->assertStringNotContainsString('$2y$', $body);
            $this->assertStringNotContainsString('password_encrypted', $body);
            $this->assertStringNotContainsString('remember_token', $body);
            $this->assertStringNotContainsString('unsubscribe_token', $body);
            $this->assertStringNotContainsString('demo1234', $body);
        }
    }

    public function test_frontend_code_never_persists_credentials_client_side(): void
    {
        $sources = collect(glob(resource_path('{js,views}/**/*.{js,jsx,php}'), GLOB_BRACE))
            ->merge(glob(resource_path('views/*.php')))->merge(glob(resource_path('js/*.{js,jsx}'), GLOB_BRACE));

        foreach ($sources as $file) {
            $code = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/(localStorage|sessionStorage|document\.cookie)/', $code, basename($file).' touches client-side storage');
        }
        $this->assertStringNotContainsString('APP_KEY', implode('', array_map('file_get_contents', glob(public_path('build/assets/*.js')) ?: [])));
    }
}
