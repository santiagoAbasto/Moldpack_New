<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MoldpackAiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:180']])['q']);
        $terms = $this->terms($query);
        $items = ContentItem::query()->where('is_visible', true)->with(['section.page', 'media'])->get()
            ->map(function (ContentItem $item) use ($terms) {
                $settings = $item->settings ?? [];
                $settingsText = json_encode([
                    $settings['code'] ?? null, $settings['codes'] ?? [], $settings['category'] ?? null,
                    $settings['subcategory'] ?? null, $settings['family'] ?? null, $settings['brand'] ?? null,
                    $settings['presentation'] ?? null, $settings['presentations'] ?? [],
                    $settings['design'] ?? null, $settings['food_safe'] ?? null, $settings['quality'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $haystack = $this->normalize(implode(' ', [$item->title, $item->subtitle, strip_tags((string) $item->body), $item->label, $settingsText]));
                $title = $this->normalize((string) $item->title);
                $score = collect($terms)->sum(fn ($term) => str_contains($haystack, $term) ? (str_contains($title, $term) ? 8 : 3) : $this->near($term, $haystack));
                $slug = $settings['slug'] ?? null;
                $type = $item->section?->type;
                $fallback = '/'.($item->section?->page?->slug ?? '');
                $storedUrl = (string) ($item->url ?: $fallback);
                $safeUrl = str_starts_with($storedUrl, '/') || Str::startsWith($storedUrl, ['https://', 'http://']) ? $storedUrl : $fallback;
                $url = $type === 'products' && $slug ? route('products.show', $slug) : ($type === 'news' && $slug ? route('news.show', $slug) : $safeUrl);
                return ['score' => $score, 'title' => $item->title ?: 'Contenido Moldpack', 'subtitle' => trim((string) ($item->subtitle ?: Str::limit(strip_tags((string) $item->body), 95))), 'type' => $type === 'products' ? 'Producto' : ($type === 'news' ? 'Novedad' : 'Contenido'), 'url' => $url, 'image' => $item->media->first()?->src];
            })->filter(fn ($result) => $result['score'] > 0);

        $pages = Page::query()->where('is_published', true)->get()->map(function (Page $page) use ($terms) {
            $haystack = $this->normalize($page->name.' '.$page->seo_title.' '.$page->seo_description.' '.$page->seo_keywords);
            $score = collect($terms)->sum(fn ($term) => str_contains($haystack, $term) ? 4 : 0);
            return ['score' => $score, 'title' => $page->name, 'subtitle' => $page->seo_description, 'type' => 'Sección', 'url' => $page->slug === 'inicio' ? '/' : '/'.$page->slug, 'image' => $page->seo_image ? asset($page->seo_image) : asset('assets/figma/exact/logo-header.png')];
        })->filter(fn ($result) => $result['score'] > 0);

        $results = $items->concat($pages)->sortByDesc('score')->unique('url')->take(10)->values();
        $contact = SiteSetting::query()->where('key', 'contact')->value('value') ?? [];
        $answer = $this->answer($query, $results->count(), $contact);

        if (auth('cliente')->check() && preg_match('/pedido|factura|pago|cuenta/i', $query)) {
            $private = [['score' => 100, 'title' => 'Abrir mi zona privada', 'subtitle' => 'Consultá tus pedidos, facturas, pagos y estado de cuenta.', 'type' => 'Tu cuenta', 'url' => route('client.portal', preg_match('/factura/i', $query) ? 'facturas' : (preg_match('/pago/i', $query) ? 'pagos' : (preg_match('/cuenta/i', $query) ? 'cuenta' : 'pedidos'))), 'image' => asset('assets/figma/exact/logo-header.png')]];
            $results = collect($private)->concat($results)->take(10)->values();
        }
        return response()->json(['answer' => $answer, 'results' => $results]);
    }

    private function terms(string $query): array
    {
        $normalized = Str::lower(Str::ascii($query));
        $synonyms = ['comprar' => 'producto', 'precio' => 'producto', 'muffin' => 'tulipa', 'cupcake' => 'pirotin', 'ubicacion' => 'donde comprar contacto', 'direccion' => 'contacto', 'certificado' => 'calidad', 'catalogo' => 'productos'];
        foreach ($synonyms as $word => $expansion) if (str_contains($normalized, $word)) $normalized .= ' '.$expansion;
        $terms = collect(preg_split('/[^a-z0-9]+/', $normalized))->filter(fn ($word) => strlen($word) >= 2 && ! in_array($word, ['para','como','con','una','uno','los','las','del','que','por','donde'], true))->unique()->values()->all();
        return $terms ?: [$normalized];
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(strip_tags($value)));
    }

    private function near(string $term, string $haystack): int
    {
        foreach (preg_split('/\s+/', Str::ascii($haystack)) as $word) if (strlen($word) > 3 && levenshtein($term, trim($word, ',.;:')) <= 2) return 1;
        return 0;
    }

    private function answer(string $query, int $count, array $contact): string
    {
        if (preg_match('/telefono|mail|correo|contact/i', $query)) return 'Podés comunicarte al '.($contact['phone'] ?? '4727-2836/2837').' o escribir a '.($contact['email'] ?? 'ventas@moldpack.com.ar').'.';
        if (preg_match('/direccion|ubicacion|como llegar/i', $query)) return 'Moldpack está en '.($contact['address'] ?? 'Dante Alighieri 1377, Don Torcuato').', '.($contact['city'] ?? 'Buenos Aires, Argentina').'.';
        if ($count) return "Encontré {$count} resultados relevantes en el catálogo y contenido de Moldpack.";
        return 'No encontré una coincidencia exacta. Probá con el nombre, código, categoría o uso del producto.';
    }
}
