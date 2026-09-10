@extends('layouts.site')
@section('title', $page->meta_title ?: 'Productos | MoldPack')
@section('body-class', 'products-page')
@section('content')
@php($section = $page->sections->firstWhere('type','products'))
<main class="products-catalog shell">
  <nav class="product-breadcrumb" aria-label="Migas"><a href="/">Inicio</a><span> &gt; Productos</span></nav>
  <div class="catalog-layout" data-product-catalog>
    <aside class="product-filters" aria-label="Categorías de productos">
      @foreach(($section?->settings['families'] ?? []) as $family)
      <section class="filter-family {{ $loop->first ? 'open' : '' }}">
        <button type="button" aria-expanded="{{ $loop->first ? 'true':'false' }}"><span>{{ $family['name'] }}</span><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        <div class="filter-children"><button type="button" data-category-filter="{{ $family['name'] }}">Todos <small>({{ $family['count'] ?? 0 }})</small></button>@foreach($family['children'] ?? [] as $child)<button type="button" data-family-filter="{{ $child }}">{{ $child }}</button>@endforeach</div>
      </section>
      @endforeach
    </aside>
    <section class="catalog-grid" aria-live="polite">
      @foreach($section?->items ?? [] as $item)
      <a class="catalog-product" href="{{ $item->url }}" data-category="{{ $item->settings['category'] ?? $item->settings['family'] ?? '' }}" data-family="{{ $item->settings['subcategory'] ?? '' }}">
        <span class="catalog-product-media"><img src="{{ $item->media->first()?->path ? asset($item->media->first()->path) : asset('assets/product-placeholder.svg') }}" alt="{{ $item->media->first()?->alt ?: $item->title }}" width="288" height="288" loading="{{ $loop->first ? 'eager':'lazy' }}" onerror="this.onerror=null;this.src='{{ asset('assets/product-placeholder.svg') }}'"><i aria-hidden="true"></i></span>
        <span class="catalog-product-category">{{ $item->subtitle }}</span><strong>{{ $item->title }}</strong><span class="catalog-product-presentation">PRESENTACIONES: {{ $item->label }}</span>
      </a>
      @endforeach
    </section>
  </div>
</main>
@endsection
@push('scripts')<script>(()=>{const cards=[...document.querySelectorAll('.catalog-product')];const controls=[...document.querySelectorAll('[data-category-filter],[data-family-filter]')];document.querySelectorAll('.filter-family>button').forEach(button=>button.addEventListener('click',()=>{const group=button.parentElement;group.classList.toggle('open');button.setAttribute('aria-expanded',group.classList.contains('open'))}));controls.forEach(button=>button.addEventListener('click',()=>{controls.forEach(control=>control.classList.remove('active'));button.classList.add('active');const category=(button.dataset.categoryFilter||'').trim().toLocaleLowerCase('es');const family=(button.dataset.familyFilter||'').trim().toLocaleLowerCase('es');cards.forEach(card=>{const matchesCategory=!category||card.dataset.category.trim().toLocaleLowerCase('es')===category;const matchesFamily=!family||card.dataset.family.trim().toLocaleLowerCase('es')===family;card.hidden=!(matchesCategory&&matchesFamily)})}))})();</script>@endpush
