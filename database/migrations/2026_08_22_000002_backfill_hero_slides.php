<?php

use App\Models\Section;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Section::query()
            ->where('type', 'hero')
            ->with(['items', 'media'])
            ->each(function (Section $section): void {
                if ($section->items->isNotEmpty()) {
                    return;
                }

                $item = $section->items()->create([
                    'title' => $section->title,
                    'body' => $section->body,
                    'label' => data_get($section->settings, 'button_label'),
                    'url' => data_get($section->settings, 'button_url'),
                    'settings' => ['migrated_from_section' => true],
                    'sort_order' => 0,
                    'is_visible' => $section->is_visible,
                ]);

                foreach ($section->media as $media) {
                    $copy = $media->replicate(['mediable_id', 'mediable_type']);
                    $item->media()->save($copy);
                }
            });
    }

    public function down(): void
    {
        Section::query()
            ->where('type', 'hero')
            ->with('items')
            ->each(fn (Section $section) => $section->items
                ->filter(fn ($item) => data_get($item->settings, 'migrated_from_section') === true)
                ->each->delete());
    }
};
