<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->updateOrInsert(
            ['slug' => 'contacto'],
            [
                'name' => 'Contacto',
                'seo_title' => 'Contacto · Moldpack',
                'seo_description' => 'Contactate con Moldpack para recibir información sobre nuestros productos y soluciones.',
                'is_published' => true,
                'show_on_home' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('pages')->where('slug', 'contacto')->delete();
    }
};
