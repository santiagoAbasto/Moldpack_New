<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $presentations = [
            0 => 'TRI0001 U/10',
            1 => '30x30x14',
            2 => 'Nº10 U/510',
            3 => 'PBP01 U/25',
            4 => 'Nº10 U/510 | Nº8 U/510',
            5 => 'Nº8 U/500 | Nº10 U/510',
            6 => 'Nº8 U/500',
            7 => '10cm U/10',
        ];

        $items = DB::table('content_items')
            ->join('sections', 'sections.id', '=', 'content_items.section_id')
            ->where('sections.type', 'products')
            ->orderBy('content_items.sort_order')
            ->select('content_items.id', 'content_items.sort_order', 'content_items.label')
            ->get();

        foreach ($items as $item) {
            if (($item->label === null || $item->label === '') && isset($presentations[$item->sort_order])) {
                DB::table('content_items')->where('id', $item->id)->update([
                    'label' => $presentations[$item->sort_order],
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Se preservan posibles ediciones realizadas desde el CMS.
    }
};
