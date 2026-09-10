<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $page = Page::query()->firstOrCreate(
            ['slug' => 'catalogo'],
            ['name' => 'Catálogo', 'seo_title' => 'Catálogo de productos | Moldpack', 'seo_description' => 'Descargá el catálogo completo o elegí una categoría de productos Moldpack.', 'is_published' => true]
        );
        $section = $page->sections()->firstOrCreate(
            ['type' => 'catalog_page'],
            ['title' => 'Catálogo completo', 'body' => '<p>Descargá nuestro catálogo actualizado con todos nuestros artículos en venta</p>', 'settings' => ['pages' => '124', 'display_size' => '18 MB'], 'sort_order' => 0, 'is_visible' => true]
        );
        if (! $section->media()->exists()) {
            $section->media()->create(['kind' => 'image', 'path' => 'storage/descargas/Q6pBaKpZFKCKx2ZLy1hYtP13rK4pbUnGuZ7MUv6n.png', 'alt' => 'Portada del catálogo de productos Moldpack', 'sort_order' => 0]);
        }
        foreach (['Pirotines para cupcakes', 'Cajas', 'Soportes', 'Tulipas para muffins'] as $order => $title) {
            $section->items()->firstOrCreate(['title' => $title], ['label' => 'Descargar', 'sort_order' => $order, 'is_visible' => true]);
        }
        $homeCatalog = Page::query()->where('slug', 'inicio')->first()?->sections()->where('type', 'cta')->first();
        if ($homeCatalog) {
            $settings = $homeCatalog->settings ?? [];
            if (empty($settings['button_url']) || $settings['button_url'] === '#') $settings['button_url'] = '/catalogo';
            $homeCatalog->update(['settings' => $settings]);
        }
    }

    public function down(): void
    {
        Page::query()->where('slug', 'catalogo')->delete();
    }
};
