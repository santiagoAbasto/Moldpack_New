<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->boolean('show_on_home')->default(false)->after('is_published');
        });

        $homeId = DB::table('pages')->where('slug', 'inicio')->value('id');
        $definitions = [
            'categories' => ['Categorías', 'categorias'],
            'products' => ['Productos destacados', 'productos'],
            'news' => ['Novedades', 'novedades'],
        ];

        foreach ($definitions as $type => [$name, $slug]) {
            $pageId = DB::table('pages')->where('slug', $slug)->value('id');
            if (! $pageId) {
                $pageId = DB::table('pages')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'seo_title' => $name.' · Moldpack',
                    'seo_description' => $name.' de Moldpack.',
                    'is_published' => true,
                    'show_on_home' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('sections')->where('page_id', $homeId)->where('type', $type)->update(['page_id' => $pageId, 'sort_order' => 0]);
        }
    }

    public function down(): void
    {
        $homeId = DB::table('pages')->where('slug', 'inicio')->value('id');
        foreach (['categorias', 'productos', 'novedades'] as $slug) {
            $pageId = DB::table('pages')->where('slug', $slug)->value('id');
            if ($pageId) DB::table('sections')->where('page_id', $pageId)->update(['page_id' => $homeId]);
            DB::table('pages')->where('id', $pageId)->delete();
        }
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('show_on_home'));
    }
};
