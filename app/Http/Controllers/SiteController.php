<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\ContentItem;
use App\Services\ProductRelationService;
use App\Services\ProductTaxonomyService;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function __construct(
        private readonly ProductRelationService $relations,
        private readonly ProductTaxonomyService $taxonomy,
    ) {}

    public function home(): View
    {
        $page = Page::with($this->publishedContent())->where('slug', 'inicio')->where('is_published', true)->firstOrFail();
        $featured = Page::with($this->publishedContent())
            ->where('show_on_home', true)
            ->where('is_published', true)
            ->whereIn('slug', ['categorias', 'productos', 'novedades'])
            ->get()
            ->sortBy(fn (Page $item) => array_search($item->slug, ['categorias', 'productos', 'novedades'], true));
        $homeOrder = ['hero' => 0, 'categories' => 10, 'products' => 20, 'about' => 30, 'news' => 40, 'cta' => 50];
        $collectionSections = collect(['categories', 'products', 'news'])->map(function (string $type) use ($featured, $page) {
            return $featured->flatMap->sections->first(fn ($section) => $section->type === $type && $section->items->isNotEmpty())
                ?? $page->sections->firstWhere('type', $type);
        })->filter();
        $sections = $page->sections
            ->reject(fn ($section) => in_array($section->type, ['categories', 'products', 'news'], true))
            ->concat($collectionSections)
            ->sortBy(fn ($section) => $homeOrder[$section->type] ?? 100)
            ->values();
        $sections->where('type', 'products')->each(function ($section): void {
            $section->setRelation('items', $section->items
                ->filter(fn (ContentItem $item) => (bool) ($item->settings['featured_home'] ?? false))
                ->values());
        });
        $page->setRelation('sections', $sections);

        return view('home', ['page' => $page, 'contact' => $this->contact()]);
    }

    public function page(string $slug): View
    {
        $page = Page::with($this->publishedContent())->where('slug', $slug)->where('is_published', true)->firstOrFail();

        if ($slug === 'productos' && ($section = $page->sections->firstWhere('type', 'products'))) {
            $settings = $section->settings ?? [];
            $settings['families'] = $this->taxonomy->families($section->items, $settings['families'] ?? []);
            $section->setAttribute('settings', $settings);
        }

        $view = match ($slug) { 'nosotros' => 'about', 'productos' => 'products', 'catalogo' => 'catalog', 'novedades' => 'news', 'calidad' => 'quality', 'contacto' => 'contact', default => 'home' };

        return view($view, ['page' => $page, 'contact' => $this->contact()]);
    }

    public function product(string $product): View
    {
        $page = Page::with($this->publishedContent())->where('slug', 'productos')->where('is_published', true)->firstOrFail();
        $items = $page->sections->flatMap->items;
        $item = $items->first(fn (ContentItem $item) => ($item->settings['slug'] ?? null) === $product) ?? abort(404);
        $related = $this->relations->resolve($item, $items, 4);
        return view('product-detail', ['page' => $page, 'product' => $item, 'related' => $related, 'contact' => $this->contact()]);
    }

    public function news(string $news): View
    {
        $page = Page::with($this->publishedContent())->where('slug', 'novedades')->where('is_published', true)->firstOrFail();
        $section = $page->sections->firstWhere('type', 'news') ?? $page->sections->first();
        $items = $section?->items ?? collect();
        $article = $items->first(fn (ContentItem $item) => ($item->settings['slug'] ?? $this->newsSlug($item)) === $news) ?? abort(404);
        $articleCategory = Str::lower(trim((string) ($article->label ?: 'Productos')));
        $related = $items
            ->reject(fn (ContentItem $item) => $item->id === $article->id)
            ->sortByDesc(fn (ContentItem $item) => Str::lower(trim((string) ($item->label ?: 'Productos'))) === $articleCategory)
            ->take(3)
            ->values();

        return view('news-show', ['page' => $page, 'article' => $article, 'related' => $related, 'contact' => $this->contact()]);
    }

    public function stores(): View
    {
        $page = Page::where('slug', 'donde-comprar')->where('is_published', true)->firstOrFail();

        return view('stores', [
            'page' => $page,
            'stores' => SiteSetting::query()->where('key', 'stores')->value('value') ?? [],
            'contact' => $this->contact(),
        ]);
    }

    private function newsSlug(ContentItem $item): string
    {
        return Str::slug($item->title ?: 'novedad').'-'.$item->id;
    }

    private function publishedContent(): array
    {
        return [
            'sections' => fn ($query) => $query->where('is_visible', true)->orderBy('sort_order'),
            'sections.items' => fn ($query) => $query->where('is_visible', true)->orderBy('sort_order'),
            'sections.items.media',
            'sections.media',
        ];
    }

    private function contact(): array
    {
        return SiteSetting::query()->where('key', 'contact')->value('value') ?? [];
    }
}
