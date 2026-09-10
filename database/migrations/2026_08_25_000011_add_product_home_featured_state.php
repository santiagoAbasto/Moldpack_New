<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $page = Page::with('sections.items')->where('slug', 'productos')->first();
        $items = $page?->sections->firstWhere('type', 'products')?->items ?? collect();

        foreach ($items as $index => $item) {
            $settings = $item->settings ?? [];
            if (! array_key_exists('featured_home', $settings)) {
                $settings['featured_home'] = $index < 8;
                $item->update(['settings' => $settings]);
            }
        }
    }

    public function down(): void {}
};
