<?php

namespace App\Services;

use App\Models\ContentItem;
use Illuminate\Support\Collection;

class ProductTaxonomyService
{
    /** @param Collection<int, ContentItem> $products */
    public function families(Collection $products, array $preferredOrder = []): array
    {
        $order = collect($preferredOrder)
            ->pluck('name')
            ->mapWithKeys(fn ($name, $index) => [mb_strtolower((string) $name) => $index]);

        return $products
            ->filter(fn (ContentItem $product) => $product->is_visible)
            ->groupBy(fn (ContentItem $product) => trim((string) ($product->settings['category'] ?? $product->settings['family'] ?? '')) ?: 'Otros')
            ->map(function (Collection $categoryProducts, string $category): array {
                $children = $categoryProducts
                    ->map(function (ContentItem $product): string {
                        $settings = $product->settings ?? [];
                        $hasExplicitCategory = trim((string) ($settings['category'] ?? '')) !== '';

                        return trim((string) ($settings['subcategory'] ?? ($hasExplicitCategory ? $settings['family'] ?? '' : '')));
                    })
                    ->filter()
                    ->unique(fn (string $name) => mb_strtolower($name))
                    ->sort(fn (string $left, string $right) => strnatcasecmp($left, $right))
                    ->values()
                    ->all();

                return ['name' => $category, 'children' => $children, 'count' => $categoryProducts->count()];
            })
            ->sort(function (array $left, array $right) use ($order): int {
                $leftOrder = $order->get(mb_strtolower($left['name']), 999);
                $rightOrder = $order->get(mb_strtolower($right['name']), 999);

                return $leftOrder === $rightOrder
                    ? strnatcasecmp($left['name'], $right['name'])
                    : $leftOrder <=> $rightOrder;
            })
            ->values()
            ->all();
    }
}
