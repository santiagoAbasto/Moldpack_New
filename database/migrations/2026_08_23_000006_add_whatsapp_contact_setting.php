<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('site_settings')->where('key', 'contact')->first();
        if (! $setting) return;

        $value = json_decode($setting->value, true) ?: [];
        $value['whatsapp'] ??= '5491147272836';
        DB::table('site_settings')->where('key', 'contact')->update(['value' => json_encode($value), 'updated_at' => now()]);
    }

    public function down(): void
    {
        $setting = DB::table('site_settings')->where('key', 'contact')->first();
        if (! $setting) return;

        $value = json_decode($setting->value, true) ?: [];
        unset($value['whatsapp']);
        DB::table('site_settings')->where('key', 'contact')->update(['value' => json_encode($value), 'updated_at' => now()]);
    }
};
