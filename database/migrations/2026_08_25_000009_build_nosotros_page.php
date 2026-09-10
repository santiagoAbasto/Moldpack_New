<?php

use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pageId = DB::table('pages')->where('slug', 'nosotros')->value('id');
        if (! $pageId) {
            return;
        }

        $section = DB::table('sections')->where('page_id', $pageId)->orderBy('sort_order')->first();
        $firstBody = '<p>MOLDPACK nace con la idea de resolver complementos gastronómicos, impresos en papeles especiales aptos para alimentos, moldes - pirotines - mini budines - tulipas para cupcakes - papel parafinado y packs gastronómicos en general.</p><p>“Nuestro objetivo es acompañar a los cocineros de Argentina con sus recetas más ricas, facilitando accesorios gastronómicos impresos en papel de gran calidad, color y sensibilidad. La idea es resolver de una forma práctica, personal y divertida moldes, contenedores y complementos para esos alimentos” nos cuenta uno de sus fundadores.</p>';
        $secondBody = '<p>Inicialmente MOLDPACK abrió su fábrica con el objetivo de abastecer al mercado de pirotines. Diseños divertidos, originales, materia prima de primer nivel, recursos humanos altamente capacitados, maquinaria de última tecnología son los condimentos que hacen a Moldpack un fabricante consolidado en el mercado nacional, y un líder del sector moldes, pirotines y Tulipas para cupcakes.</p><p>Innovamos permanentemente nuestros moldes para sorprender. Para 2018 desarrollamos nuevos formatos, como Tulipas para cupcakes y Moldes de pirotines nº 5 y 9.</p>';
        $settings = json_encode([
            'second_title' => 'Fabricantes de accesorios para cotillón.',
            'second_body' => $secondBody,
            'benefits_title' => '¿Porque elegirnos?',
        ], JSON_UNESCAPED_UNICODE);

        if ($section) {
            DB::table('sections')->where('id', $section->id)->update([
                'type' => 'about_page',
                'title' => 'Fábrica de Moldes & Packs Gastronómicos',
                'body' => $firstBody,
                'settings' => $settings,
                'sort_order' => 0,
                'is_visible' => true,
                'updated_at' => now(),
            ]);
            $sectionId = $section->id;
        } else {
            $sectionId = DB::table('sections')->insertGetId([
                'page_id' => $pageId,
                'type' => 'about_page',
                'title' => 'Fábrica de Moldes & Packs Gastronómicos',
                'body' => $firstBody,
                'settings' => $settings,
                'sort_order' => 0,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('media')->where('mediable_type', Section::class)->where('mediable_id', $sectionId)->exists()) {
            DB::table('media')->insert([
                'mediable_type' => Section::class,
                'mediable_id' => $sectionId,
                'kind' => 'image',
                'disk' => 'public',
                'path' => 'assets/figma/exact/nosotros/raw-4.png',
                'alt' => 'Cupcakes elaborados con pirotines Moldpack',
                'mime_type' => 'image/png',
                'width' => 1194,
                'height' => 840,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('content_items')->where('section_id', $sectionId)->exists()) {
            DB::table('content_items')->insert([
                [
                    'section_id' => $sectionId,
                    'title' => 'Fabricación propia',
                    'body' => '<p>No revendemos: producimos cada molde en nuestra planta de Don Torcuato, de punta a punta.</p>',
                    'settings' => json_encode(['icon' => 'factory']),
                    'sort_order' => 0,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'section_id' => $sectionId,
                    'title' => 'Aptos para horno',
                    'body' => '<p>Todos nuestros pirotines y tulipas resisten temperatura de horneado sin perder forma.</p>',
                    'settings' => json_encode(['icon' => 'oven']),
                    'sort_order' => 1,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'section_id' => $sectionId,
                    'title' => 'Distribución nacional',
                    'body' => '<p>Llegamos a pastelerías y mayoristas en todo el país a través de nuestra red de puntos de venta.</p>',
                    'settings' => json_encode(['icon' => 'truck']),
                    'sort_order' => 2,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        $pageId = DB::table('pages')->where('slug', 'nosotros')->value('id');
        if ($pageId) {
            DB::table('sections')->where('page_id', $pageId)->where('type', 'about_page')->update(['type' => 'rich_text']);
        }
    }
};
