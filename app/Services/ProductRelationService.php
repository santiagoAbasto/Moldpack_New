<?php

namespace App\Services;

use App\Models\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductRelationService
{
    /** @param Collection<int, ContentItem> $catalog */
    public function resolve(ContentItem $product, Collection $catalog, int $limit = 4): Collection
    {
        $settings = $product->settings ?? [];

        $manual = array_key_exists('related_manual', $settings)
            ? (bool) $settings['related_manual']
            : ! empty($settings['related_ids']);

        if ($manual) {
            $ids = collect($settings['related_ids'] ?? [])->map(fn ($id) => (int) $id)->take($limit)->values();

            return $catalog
                ->whereIn('id', $ids)
                ->sortBy(fn (ContentItem $candidate) => $ids->search($candidate->id))
                ->values();
        }

        return $this->suggest($product, $catalog, $limit);
    }

    /** @param Collection<int, ContentItem> $catalog */
    public function suggest(ContentItem $product, Collection $catalog, int $limit = 4): Collection
    {
        return $catalog
            ->filter(fn (ContentItem $candidate) => $candidate->id !== $product->id && $candidate->is_visible)
            ->map(fn (ContentItem $candidate) => [
                'product' => $candidate,
                'score' => $this->score($product, $candidate),
            ])
            ->sort(function (array $left, array $right): int {
                $scoreOrder = $right['score'] <=> $left['score'];

                return $scoreOrder !== 0 ? $scoreOrder : $left['product']->id <=> $right['product']->id;
            })
            ->take($limit)
            ->pluck('product')
            ->values();
    }

    private function score(ContentItem $product, ContentItem $candidate): float
    {
        $source = $product->settings ?? [];
        $target = $candidate->settings ?? [];
        $score = 0;

        $score += $this->same($source['family'] ?? null, $target['family'] ?? null) ? 55 : 0;
        $score += $this->same($source['subcategory'] ?? null, $target['subcategory'] ?? null) ? 45 : 0;
        $score += $this->same($source['category'] ?? null, $target['category'] ?? null) ? 35 : 0;

        $sourceCode = $this->codePrefix($source['code'] ?? null);
        $targetCode = $this->codePrefix($target['code'] ?? null);
        $score += $sourceCode !== '' && $sourceCode === $targetCode ? 24 : 0;

        $sourceWords = $this->words($product->title);
        $targetWords = $this->words($candidate->title);
        $intersection = count(array_intersect($sourceWords, $targetWords));
        $union = count(array_unique([...$sourceWords, ...$targetWords]));
        $score += $union > 0 ? ($intersection / $union) * 30 : 0;

        if ($product->label && $candidate->label && Str::contains(Str::lower($candidate->label), Str::lower(Str::before($product->label, '|')))) {
            $score += 6;
        }

        return $score;
    }

    private function same(mixed $left, mixed $right): bool
    {
        $left = Str::lower(trim((string) $left));
        $right = Str::lower(trim((string) $right));

        return $left !== '' && $left === $right;
    }

    private function codePrefix(mixed $code): string
    {
        preg_match('/^[a-z]+/i', trim((string) $code), $match);

        return Str::upper($match[0] ?? '');
    }

    /** @return array<int, string> */
    private function words(?string $value): array
    {
        $normalized = Str::of((string) $value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim();

        return collect(explode(' ', (string) $normalized))
            ->filter(fn (string $word) => mb_strlen($word) >= 3)
            ->reject(fn (string $word) => in_array($word, ['para', 'con', 'por', 'una', 'uno', 'del', 'los', 'las'], true))
            ->unique()
            ->values()
            ->all();
    }
}
