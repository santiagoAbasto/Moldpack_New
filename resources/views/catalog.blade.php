@extends('layouts.site')

@section('content')
@php
    $catalog = $page->sections->firstWhere('type', 'catalog_page');
    $settings = $catalog?->settings ?? [];
    $cover = $catalog?->media->first();
    $coverLogo = $catalog?->media->skip(1)->first();
    $document = $settings['document_path'] ?? null;
    $documentSize = !empty($settings['document_size']) ? number_format($settings['document_size'] / 1048576, 1, ',', '').' MB' : '—';
    $documentPages = $settings['document_pages'] ?? '—';
@endphp
<main class="catalog-page">
    @if($catalog)
    <section class="catalog-hero">
        <nav class="shell product-breadcrumb catalog-breadcrumb" aria-label="Migas"><a href="/">Inicio</a><span> &gt; Catálogo</span></nav>
        <div class="shell catalog-hero-grid">
            <figure class="catalog-cover">
                <img src="{{ $cover?->path ? asset($cover->path) : asset('assets/product-placeholder.svg') }}" alt="{{ $cover?->alt ?: 'Catálogo de productos Moldpack' }}">
                <figcaption>Catálogo de<br>productos</figcaption>
                <span class="catalog-cover-logo"><img src="{{ $coverLogo?->path ? asset($coverLogo->path) : asset('assets/figma/exact/logo-header.png') }}" alt="{{ $coverLogo?->alt ?: 'Moldpack' }}"></span>
            </figure>
            <div class="catalog-summary">
                <h1>{{ $catalog->title }}</h1>
                <div class="catalog-description">{!! $catalog->body !!}</div>
                <div class="catalog-stats">
                    <div><strong>{{ $documentPages }}</strong><span>PÁGINAS</span></div>
                    <div><strong>{{ $documentSize }}</strong><span>PESO DEL PDF</span></div>
                </div>
                @if($document)
                    <a class="button catalog-download-primary" href="{{ asset($document) }}" download>Descargar catálogo</a>
                @else
                    <span class="button catalog-download-primary disabled" aria-disabled="true">Catálogo en actualización</span>
                @endif
            </div>
        </div>
    </section>

    <section class="catalog-categories shell">
        <h2>{{ $settings['categories_title'] ?? '¿Buscás solo una categoría?' }}</h2>
        <div class="catalog-download-grid">
            @foreach($catalog->items as $item)
                @php($itemDocument = $item->settings['document_path'] ?? null)
                <article class="catalog-download-card">
                    <h3>{{ $item->title }}</h3>
                    @if($itemDocument)
                        <a href="{{ asset($itemDocument) }}" download>{{ $item->label ?: 'Descargar' }}<img src="{{ asset('assets/figma/exact/arrow-right.svg') }}" alt=""></a>
                    @else
                        <span>{{ $item->label ?: 'Próximamente' }}<img src="{{ asset('assets/figma/exact/arrow-right.svg') }}" alt=""></span>
                    @endif
                </article>
            @endforeach
        </div>
        @if($catalog->items->count() > 4)<div class="catalog-pages" aria-label="Páginas de categorías"><i></i><i></i></div>@endif
    </section>
    @endif
</main>
@endsection
