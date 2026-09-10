<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cards = [
            0 => ['Triángulo metalizado dorado', 'SOPORTES / TRIÁNGULOS', 5],
            1 => ['Caja para torta 30x30x14', 'CAJAS', 6],
            2 => ['Surtido colores rocosos', 'PIROTINES / SURTIDOS', 4],
            3 => ['Porta bombones rosa', 'CAJAS / PORTABOMBONES', 7],
            4 => ['Bandeja rojo lunares blanco', 'DESCARTABLES / CANDY BAR', 1],
            5 => ['Surtido Love', 'PIROTINES / LOVE', 2],
            6 => ['Tulipa impresa personalizada 1 color', 'TULIPAS', 3],
            7 => ['Estrella dorada/plateada', 'PIROTINES / MOTIVOS', 8],
        ];

        $items = DB::table('content_items')
            ->join('sections', 'sections.id', '=', 'content_items.section_id')
            ->where('sections.type', 'products')
            ->select('content_items.id', 'content_items.sort_order')
            ->get();

        foreach ($items as $item) {
            if (! isset($cards[$item->sort_order])) {
                continue;
            }

            [$title, $category, $imageNumber] = $cards[$item->sort_order];
            DB::table('content_items')->where('id', $item->id)->update([
                'title' => $title,
                'subtitle' => $category,
                'updated_at' => now(),
            ]);

            $media = DB::table('media')
                ->where('mediable_type', 'App\\Models\\ContentItem')
                ->where('mediable_id', $item->id)
                ->orderBy('sort_order')
                ->first();

            if ($media && preg_match('~^assets/figma/exact/product-[1-8]\\.png$~', $media->path)) {
                DB::table('media')->where('id', $media->id)->update([
                    'path' => 'assets/figma/exact/product-'.$imageNumber.'.png',
                    'alt' => $title,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Se preservan los medios y textos para no sobrescribir ediciones del CMS.
    }
};
