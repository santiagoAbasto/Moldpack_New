@extends('layouts.site')

@section('title', $page->meta_title ?: 'Novedades | MoldPack')
@section('body_class', 'news-route')

@section('content')
@php
    $section = $page->sections->firstWhere('type', 'news') ?? $page->sections->first();
    $items = $section?->items ?? collect();
    $categories = $items
        ->groupBy(fn ($item) => \Illuminate\Support\Str::lower(trim((string) ($item->label ?: 'Productos'))))
        ->map(fn ($group, $name) => ['name' => \Illuminate\Support\Str::ucfirst($name), 'count' => $group->count()])
        ->values();
    $months = $items
        ->groupBy(fn ($item) => optional($item->updated_at)->locale('es')->translatedFormat('F') ?: 'Recientes')
        ->map(fn ($group, $name) => ['name' => ucfirst($name), 'count' => $group->count()])
        ->values();
@endphp

<main class="news-page">
    <nav class="shell product-breadcrumb news-breadcrumb" aria-label="Migas">
        <a href="/">Inicio</a><span> &gt; Novedades</span>
    </nav>

    <div class="shell news-layout" data-news-page>
        <section class="news-list" aria-label="Listado de novedades">
            @forelse($items as $item)
                @php
                    $media = $item->media->firstWhere('kind', 'image') ?? $item->media->first();
                    $image = ($media?->kind === 'image' && $media->path) ? asset($media->path) : asset('assets/figma/news-'.(($loop->index % 3) + 1).'.jpg');
                    $month = optional($item->updated_at)->locale('es')->translatedFormat('F') ?: 'Recientes';
                    $slug = $item->settings['slug'] ?? \Illuminate\Support\Str::slug($item->title ?: 'novedad').'-'.$item->id;
                    $href = route('news.show', $slug);
                    $plainBody = trim(strip_tags((string) $item->body));
                @endphp
                <article class="news-page-card" data-category="{{ \Illuminate\Support\Str::lower($item->label ?: 'Productos') }}" data-month="{{ \Illuminate\Support\Str::lower($month) }}">
                    <a class="news-page-media" href="{{ $href }}" aria-label="Leer {{ $item->title }}">
                        <img src="{{ $image }}" alt="{{ $media?->alt ?: $item->title }}" width="392" height="350" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    </a>
                    <div class="news-page-copy">
                        <p class="news-page-label">{{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($item->label ?: 'Productos')) }}</p>
                        <h1>{{ $item->title }}</h1>
                        <p class="news-page-excerpt">{{ $plainBody }}</p>
                        <a href="{{ $href }}">Leer más</a>
                    </div>
                </article>
            @empty
                <article class="news-empty">
                    <h1>Novedades en preparación</h1>
                    <p>Cuando cargues artículos desde el panel, aparecerán automáticamente acá.</p>
                </article>
            @endforelse
        </section>

        <aside class="news-sidebar" aria-label="Filtros de novedades">
            <label class="news-search">
                <input type="search" aria-label="Buscar novedades" placeholder="Buscar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="#EC458B" stroke-width="1.7" stroke-linecap="round"/></svg>
            </label>

            <section class="news-filter-box open">
                <button type="button" aria-expanded="true"><strong>Categorías</strong><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <div>
                    @foreach($categories as $category)
                        <button type="button" data-news-filter="category" data-value="{{ \Illuminate\Support\Str::lower($category['name']) }}"><span>{{ $category['name'] }}</span><b>{{ $category['count'] }}</b></button>
                    @endforeach
                </div>
            </section>

            <section class="news-filter-box open">
                <button type="button" aria-expanded="true"><strong>Archivo</strong><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <div>
                    @foreach($months as $month)
                        <button type="button" data-news-filter="month" data-value="{{ \Illuminate\Support\Str::lower($month['name']) }}"><span>{{ $month['name'] }}</span><b>{{ $month['count'] }}</b></button>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
  const root = document.querySelector('[data-news-page]');
  if (!root) return;
  const cards = [...root.querySelectorAll('.news-page-card')];
  const search = root.querySelector('.news-search input');
  let filters = { category: '', month: '' };

  const apply = () => {
    const q = (search?.value || '').trim().toLocaleLowerCase('es');
    cards.forEach(card => {
      const text = card.textContent.toLocaleLowerCase('es');
      const okSearch = !q || text.includes(q);
      const okCategory = !filters.category || card.dataset.category === filters.category;
      const okMonth = !filters.month || card.dataset.month === filters.month;
      card.hidden = !(okSearch && okCategory && okMonth);
    });
  };

  // En tablet y móvil los filtros arrancan plegados para que las notas se vean primero.
  if (window.matchMedia('(max-width:1024px)').matches) {
    root.querySelectorAll('.news-filter-box.open').forEach(box => {
      box.classList.remove('open');
      box.querySelector(':scope > button')?.setAttribute('aria-expanded', 'false');
    });
  }

  root.querySelectorAll('.news-filter-box > button').forEach(button => {
    button.addEventListener('click', () => {
      const box = button.closest('.news-filter-box');
      box.classList.toggle('open');
      button.setAttribute('aria-expanded', box.classList.contains('open') ? 'true' : 'false');
    });
  });

  root.querySelectorAll('[data-news-filter]').forEach(button => {
    button.addEventListener('click', () => {
      const kind = button.dataset.newsFilter;
      const already = button.classList.contains('active');
      root.querySelectorAll(`[data-news-filter="${kind}"]`).forEach(item => item.classList.remove('active'));
      filters[kind] = already ? '' : button.dataset.value;
      if (!already) button.classList.add('active');
      apply();
    });
  });

  search?.addEventListener('input', apply);
})();
</script>
@endpush
