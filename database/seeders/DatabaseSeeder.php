<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@moldpack.com.ar'],
            ['name' => 'Administrador', 'password' => Hash::make('Cambiar123!'), 'is_admin' => true],
        );

        Cliente::updateOrCreate(
            ['username' => 'demo'],
            [
                'name' => 'Cliente Demo',
                'first_name' => 'Cliente',
                'last_name' => 'Demo',
                'business_name' => 'Moldpack Demo',
                'tax_id' => '30-00000000-0',
                'email' => 'demo@moldpack.com.ar',
                'phone' => '11 4727-2836',
                'billing_address' => 'Dante Alighieri 1377, Don Torcuato',
                'delivery_address' => 'Dante Alighieri 1377, Don Torcuato',
                'discount_percent' => 10,
                'show_prices' => true,
                'is_active' => true,
                'password' => 'demo1234',
                'password_encrypted' => encrypt('demo1234'),
                'approved_at' => now(),
            ],
        );

        SiteSetting::updateOrCreate(['key' => 'client_portal'], ['value' => [
            'important_title' => 'Información importante',
            'important_body' => 'Los pedidos están sujetos a confirmación de stock. Los precios se expresan sin IVA y se actualizan al confirmar la operación.',
            'pickup_address' => 'Dante Alighieri 1377, Don Torcuato, Buenos Aires',
            'bank_holder' => 'Moldpack',
            'bank_name' => 'Banco Nación',
            'bank_cbu' => '0000383745825737629562',
            'bank_alias' => 'MOLDPACK.PACKAGING.CUENTA',
            'bank_tax_id' => '27-8477203-8',
        ]]);

        SiteSetting::updateOrCreate(['key' => 'quality'], ['value' => ['title' => 'Calidad', 'body' => '', 'button_label' => 'Conocer más', 'button_url' => '#']]);
        SiteSetting::updateOrCreate(['key' => 'stores'], ['value' => [
            'country' => 'Argentina',
            'load_more_label' => 'Cargar más resultados',
            'locations' => [
                ['name' => 'CEM PROVISIONES INDUSTRIALES de Claudio Macoratti', 'address' => 'Av. San Martín 3148, San Lorenzo, Santa Fe, Argentina', 'phone' => '+54 3476-426900', 'email' => 'cem@cemprovin.com.ar', 'latitude' => -32.7411, 'longitude' => -60.7322],
                ['name' => 'CLAUDIO COLEDANI', 'address' => 'Radio Belgrano 3062, Barrio Intersindical, Salta, Argentina', 'phone' => '0387-4243058 / 0387-155-196032', 'email' => 'claudiocoledani@yahoo.com.ar', 'latitude' => -24.7821, 'longitude' => -65.4232],
                ['name' => 'INDAVE PATAGONICO S.A.', 'address' => 'Río Negro 1218, Neuquén, Argentina', 'phone' => '+54 299 422-2780', 'email' => 'info@indavepatagonico.com', 'latitude' => -38.9587, 'longitude' => -68.0592],
                ['name' => 'INDAVE PATAGONICO S.A.', 'address' => 'Avellaneda 612, Bahía Blanca, Argentina', 'phone' => '+54 291 4533466', 'email' => 'rpuente@saippargentina.com.ar', 'latitude' => -38.7183, 'longitude' => -62.2663],
            ],
        ]]);
        SiteSetting::updateOrCreate(['key' => 'contact'], ['value' => ['intro' => 'Para mayor información, no dude en contactarse mediante el siguiente formulario, o a través de nuestras vías de comunicación.', 'address' => 'Dante Alighieri 1377, Don Torcuato.', 'city' => 'Buenos Aires, Argentina.', 'phone' => '4727-2836/2837', 'email' => 'ventas@moldpack.com.ar', 'whatsapp' => '5491147272836', 'maps_url' => 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX', 'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.7656349128115!2d-58.613389219219584!3d-34.48364573183678!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bca4cd16c9fcf7%3A0x46425f36c28d01be!2sMOLDPACK!5e0!3m2!1ses!2sbo!4v1788402231251!5m2!1ses!2sbo']]);
        SiteSetting::updateOrCreate(['key' => 'newsletter'], ['value' => ['title' => 'Suscribite al Newsletter', 'placeholder' => 'Ingresá tu email', 'success_message' => 'Gracias por suscribirte.']]);
        SiteSetting::updateOrCreate(['key' => 'social'], ['value' => ['links' => [['id' => 'facebook', 'name' => 'Facebook', 'url' => '', 'icon' => 'assets/figma/exact/facebook.svg'], ['id' => 'instagram', 'name' => 'Instagram', 'url' => '', 'icon' => 'assets/figma/exact/instagram.svg']]]]);

        Page::updateOrCreate(['slug' => 'donde-comprar'], ['name' => 'Dónde comprar', 'seo_title' => 'Dónde comprar Moldpack | Distribuidores en Argentina', 'seo_description' => 'Encontrá el punto de venta Moldpack más cercano, consultá direcciones y obtené indicaciones para llegar.', 'is_published' => true, 'show_on_home' => false]);

        $page = Page::updateOrCreate(
            ['slug' => 'inicio'],
            ['name' => 'Inicio', 'seo_title' => 'Moldpack · Packaging gastronómico', 'seo_description' => 'Soluciones de packaging gastronómico con diseño, calidad y personalidad.', 'is_published' => true],
        );
        $page->sections()->delete();

        $hero = $page->sections()->create(['type' => 'hero', 'title' => "El packaging que hace\nespecial cada creación", 'body' => '<p>Soluciones para presentar tus productos con diseño,<br>calidad y personalidad</p>', 'settings' => ['button_label' => 'Ver productos', 'button_url' => '#productos'], 'sort_order' => 0]);
        $hero->media()->create(['kind' => 'image', 'path' => 'assets/figma/hero.jpg', 'alt' => 'Packaging gastronómico Moldpack']);
        $slide = $hero->items()->create(['title' => "El packaging que hace\nespecial cada creación", 'body' => '<p>Soluciones para presentar tus productos con diseño,<br>calidad y personalidad</p>', 'label' => 'Ver productos', 'url' => '#productos', 'sort_order' => 0, 'is_visible' => true]);
        $slide->media()->create(['kind' => 'image', 'path' => 'assets/figma/hero.jpg', 'alt' => 'Packaging gastronómico Moldpack']);

        $categories = $page->sections()->create(['type' => 'categories', 'title' => 'Categorías', 'sort_order' => 1]);
        foreach (['Pirotines para cupcakes', 'Cajas', 'Soportes', 'Tulipas para muffins', 'Descartables', 'Papel parafinado'] as $index => $name) {
            $item = $categories->items()->create(['title' => $name, 'url' => '#', 'sort_order' => $index, 'is_visible' => true]);
            $item->media()->create(['kind' => 'image', 'path' => 'assets/figma/exact/category-'.($index + 1).'.png', 'alt' => $name]);
        }

        $products = $page->sections()->create(['type' => 'products', 'title' => 'Productos destacados', 'sort_order' => 2]);
        $productNames = ['Triángulo metalizado dorado', 'Caja para torta 30x30x14', 'Surtido colores rocosos', 'Porta bombones rosa', 'Bandeja rojo lunares blanco', 'Surtido Love', 'Tulipa impresa personalizada 1 color', 'Estrella dorada/plateada'];
        $productLabels = ['SOPORTES / TRIÁNGULOS', 'CAJAS', 'PIROTINES / SURTIDOS', 'CAJAS / PORTABOMBONES', 'DESCARTABLES / CANDY BAR', 'PIROTINES / LOVE', 'TULIPAS', 'PIROTINES / MOTIVOS'];
        $productImages = [5, 6, 4, 7, 1, 2, 3, 8];
        $productPresentations = ['TRI0001 U/10', '30x30x14', 'Nº10 U/510', 'PBP01 U/25', 'Nº10 U/510 | Nº8 U/510', 'Nº8 U/500 | Nº10 U/510', 'Nº8 U/500', '10cm U/10'];
        foreach ($productNames as $index => $name) {
            $item = $products->items()->create(['title' => $name, 'subtitle' => $productLabels[$index], 'label' => $productPresentations[$index], 'url' => '#', 'sort_order' => $index, 'is_visible' => true]);
            $item->media()->create(['kind' => 'image', 'path' => 'assets/figma/exact/product-'.$productImages[$index].'.png', 'alt' => $name]);
        }

        $about = $page->sections()->create(['type' => 'about', 'title' => 'Nosotros', 'body' => '<p>MOLDPACK nace con la idea de resolver complementos gastronómicos, impresos en papeles especiales aptos para alimentos, moldes - pirotines - mini budines - tulipas para cupcakes - papel parafinado y packs gastronómicos en general.</p><p>“Nuestro objetivo es acompañar a los cocineros de Argentina con sus recetas más ricas, facilitando accesorios gastronómicos impresos en papel de gran calidad, color y sensibilidad. La idea es resolver de una forma práctica, personal y divertida moldes, contenedores y complementos para esos alimentos” nos cuenta uno de sus fundadores.</p>', 'settings' => ['button_label' => 'Más información', 'button_url' => '#'], 'sort_order' => 3]);
        $about->media()->create(['kind' => 'image', 'path' => 'assets/figma/exact/about.png', 'alt' => 'Nosotros Moldpack']);

        $news = $page->sections()->create(['type' => 'news', 'title' => 'Novedades', 'settings' => ['button_label' => 'Ver todas', 'button_url' => '#'], 'sort_order' => 4]);
        $newsItems = [['¡Mega muffins con las tulipas!', 'Las tulipas se crearon con el fin de realizar tus muffins con un toque de personalidad distinta.'], ['Cupcakes de campeones', 'Este año acompañamos la celeste y blanca con los pirotines MOLDPACK en sus diversos colores.'], ['Pirotines de calidad dorada', 'Únicos fabricantes de pirotines metalizados aptos para hornear.']];
        foreach ($newsItems as $index => $newsItem) {
            $item = $news->items()->create(['title' => $newsItem[0], 'body' => '<p>'.$newsItem[1].'</p>', 'label' => 'PRODUCTOS', 'url' => '#', 'sort_order' => $index, 'is_visible' => true]);
            $item->media()->create(['kind' => 'image', 'path' => 'assets/figma/news-'.($index + 1).'.jpg', 'alt' => $newsItem[0]]);
        }

        $catalog = $page->sections()->create(['type' => 'cta', 'title' => 'Catálogo de productos', 'body' => '<p>Descargá nuestro catálogo actualizado con todos nuestros artículos en venta</p>', 'settings' => ['button_label' => 'Descargar catálogo', 'button_url' => '#'], 'sort_order' => 5]);
        $catalog->media()->create(['kind' => 'image', 'path' => 'assets/figma/exact/catalog-bg.png', 'alt' => 'Catálogo de productos Moldpack']);

        foreach ([
            'categories' => ['Categorías', 'categorias'],
            'products' => ['Productos destacados', 'productos'],
            'news' => ['Novedades', 'novedades'],
        ] as $type => [$name, $slug]) {
            $collectionPage = Page::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'seo_title' => $name.' · Moldpack', 'seo_description' => $name.' de Moldpack.', 'is_published' => true, 'show_on_home' => true],
            );
            $page->sections()->where('type', $type)->update(['page_id' => $collectionPage->id, 'sort_order' => 0]);
        }
    }
}
