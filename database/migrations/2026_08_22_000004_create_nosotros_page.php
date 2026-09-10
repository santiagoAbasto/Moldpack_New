<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pageId = DB::table('pages')->where('slug', 'nosotros')->value('id');

        if (! $pageId) {
            $pageId = DB::table('pages')->insertGetId([
                'name' => 'Nosotros',
                'slug' => 'nosotros',
                'seo_title' => 'Nosotros · Moldpack',
                'seo_description' => 'Conocé la historia, experiencia y valores de Moldpack.',
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('sections')->where('page_id', $pageId)->exists()) {
            DB::table('sections')->insert([
                'page_id' => $pageId,
                'type' => 'rich_text',
                'title' => 'Nosotros',
                'body' => '<p>Contá aquí la historia completa de Moldpack, su experiencia y sus valores.</p>',
                'settings' => json_encode([]),
                'sort_order' => 0,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $pageId = DB::table('pages')->where('slug', 'nosotros')->value('id');
        if ($pageId) {
            DB::table('sections')->where('page_id', $pageId)->delete();
            DB::table('pages')->where('id', $pageId)->delete();
        }
    }
};
