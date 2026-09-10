<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $page = Page::with('sections.items')->where('slug', 'productos')->first();
        $items = $page?->sections->where('type', 'products')->flatMap->items ?? collect();

        foreach ($items as $item) {
            $settings = $item->settings ?? [];
            if (empty($settings['slug'])) {
                $base = str($item->title ?: 'producto')->ascii()->slug()->toString();
                $settings['slug'] = $base.'-'.$item->id;
            }
            $item->update([
                'settings' => $settings,
                'url' => '/productos/'.$settings['slug'],
            ]);
        }
    }

    public function down(): void {}
};
