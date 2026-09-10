<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInboxAndWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['queue.default' => 'sync', 'mail.default' => 'log']);
    }

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    public function test_contact_inquiry_and_newsletter_subscription_always_reach_the_admin(): void
    {
        $this->post('/contacto/consultas', [
            'name' => 'Lucía Fernández', 'email' => 'lucia@example.com', 'phone' => '11 5555-0000', 'company' => 'Dulces Lu',
            'message' => 'Necesito cotización de cajas.', 'form_started' => Crypt::encryptString((string) (now()->timestamp - 30)),
        ])->assertSessionHas('contact_success');
        $this->post('/newsletter', ['newsletter_email' => 'suscriptora@example.com'])->assertSessionHas('newsletter_success');

        $page = $this->actingAs($this->admin())->get('/admin')->assertOk()->viewData('page')['props'];

        $inquiry = collect($page['contactInquiries']['data'])->firstWhere('email', 'lucia@example.com');
        $this->assertSame('Lucía Fernández', $inquiry['name']);
        $this->assertNull($inquiry['read_at']);
        $this->assertSame('active', collect($page['newsletter']['subscribers'])->firstWhere('email', 'suscriptora@example.com')['status']);
    }

    public function test_admin_saves_whatsapp_number_and_public_floating_button_uses_it(): void
    {
        $before = SiteSetting::where('key', 'contact')->value('value');

        $this->actingAs($this->admin())->put('/admin/settings/whatsapp', ['value' => ['number' => '+54 9 11 5555-1234']])
            ->assertRedirect()->assertSessionHas('success');

        $contact = SiteSetting::where('key', 'contact')->value('value');
        $this->assertSame('5491155551234', $contact['whatsapp']);
        $this->assertSame($before['email'], $contact['email']);
        $this->assertSame($before['address'], $contact['address']);

        $this->get('/')->assertOk()->assertSee('https://wa.me/5491155551234', false);
        $this->get('/contacto')->assertOk()->assertSee('https://wa.me/5491155551234', false);
    }

    public function test_saving_contact_data_keeps_the_whatsapp_number(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings/whatsapp', ['value' => ['number' => '5491155551234']]);
        $this->actingAs($this->admin())->put('/admin/settings/contact', ['value' => ['intro' => 'Hola', 'address' => 'Dante Alighieri 1377', 'city' => 'Don Torcuato', 'phone' => '4727-2836', 'email' => 'ventas@moldpack.com.ar']]);

        $this->assertSame('5491155551234', SiteSetting::where('key', 'contact')->value('value')['whatsapp']);
    }

    public function test_invalid_whatsapp_numbers_are_rejected(): void
    {
        foreach (['', 'abc', '1234', str_repeat('9', 16), 'javascript:alert(1)'] as $number) {
            $this->actingAs($this->admin())->put('/admin/settings/whatsapp', ['value' => ['number' => $number]])->assertSessionHasErrors('number');
        }
        $this->assertSame('5491147272836', SiteSetting::where('key', 'contact')->value('value')['whatsapp']);
    }

    public function test_only_admins_can_change_whatsapp(): void
    {
        $this->put('/admin/settings/whatsapp', ['value' => ['number' => '5491100000000']])->assertRedirect('/admin/login');
        $editor = User::create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => Hash::make('Password-Segura-1'), 'is_admin' => false]);
        $this->actingAs($editor)->put('/admin/settings/whatsapp', ['value' => ['number' => '5491100000000']])->assertForbidden();
        $this->assertSame('5491147272836', SiteSetting::where('key', 'contact')->value('value')['whatsapp']);
    }
}
