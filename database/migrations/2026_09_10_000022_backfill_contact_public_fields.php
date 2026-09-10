<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('site_settings')->where('key', 'contact')->first();
        $value = $setting ? json_decode($setting->value, true) : [];
        $value = array_merge([
            'intro' => 'Para mayor información, no dude en contactarse mediante el siguiente formulario, o a través de nuestras vías de comunicación.',
            'address' => 'Dante Alighieri 1377, Don Torcuato.',
            'city' => 'Buenos Aires, Argentina.',
            'phone' => '4727-2836/2837',
            'email' => 'ventas@moldpack.com.ar',
            'whatsapp' => '5491147272836',
            'maps_url' => 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX',
        ], $value ?: []);
        $value['maps_url'] = 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX';
        DB::table('site_settings')->updateOrInsert(['key' => 'contact'], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void {}
};
