@extends('layouts.site')

@section('title', $article->title.' | MoldPack')
@section('body_class', 'news-detail-route')

@section('content')
@php
    $mainMedia = $article->media->first();
    $fallbackImage = asset('assets/figma/news-1.jpg');
    $category = \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($article->label ?: 'Productos'));
    $plainBody = trim(strip_tags((string) $article->body));
    $publishedDate = optional($article->updated_at)->locale('es')->translatedFormat('d \\d\\e F, Y');
    $youtubeId = null;
    if ($mainMedia?->kind === 'youtube' && $mainMedia->url) {
        preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([^?&/]+)~', $mainMedia->url, $matches);
        $youtubeId = $matches[1] ?? null;
    }
@endphp

<main class="news-detail-page">
    <nav class="shell product-breadcrumb news-detail-breadcrumb" aria-label="Migas">
        <a href="/">Inicio</a><span> &gt; </span><a href="/novedades">Novedades</a><span> &gt; {{ $article->title }}</span>
    </nav>

    <article class="shell news-detail-shell">
        <header class="news-detail-heading">
            <p>{{ $category }}</p>
            <h1>{{ $article->title }}</h1>
            @if($plainBody)
                <div>{{ \Illuminate\Support\Str::limit($plainBody, 180) }}</div>
            @endif
            <span>{{ $publishedDate }}</span>
        </header>

        <section class="news-detail-hero" aria-label="Contenido principal de la novedad">
            <div class="news-detail-media">
                @if($mainMedia?->kind === 'video' && $mainMedia->path)
                    <video src="{{ asset($mainMedia->path) }}" controls playsinline preload="metadata"></video>
                @elseif($mainMedia?->kind === 'youtube' && $youtubeId)
                    <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0&amp;modestbranding=1" title="{{ $mainMedia->alt ?: $article->title }}" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                @else
                    <img src="{{ $mainMedia?->path ? asset($mainMedia->path) : $fallbackImage }}" alt="{{ $mainMedia?->alt ?: $article->title }}" width="808" height="520" loading="eager">
                @endif
            </div>

            <div class="news-detail-body">
                {!! $article->body ?: '<p>Muy pronto vamos a compartir más detalles sobre esta novedad.</p>' !!}
                <a class="button news-detail-back" href="/novedades">Volver a novedades</a>
            </div>
        </section>

        @if($article->media->count() > 1)
            <section class="news-detail-gallery" aria-label="Galería de la novedad">
                @foreach($article->media->skip(1) as $media)
                    @php
                        $galleryYoutubeId = null;
                        if ($media->kind === 'youtube' && $media->url) {
                            preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([^?&/]+)~', $media->url, $galleryMatches);
                            $galleryYoutubeId = $galleryMatches[1] ?? null;
                        }
                    @endphp
                    <figure>
                        @if($media->kind === 'video' && $media->path)
                            <video src="{{ asset($media->path) }}" controls playsinline preload="metadata"></video>
                        @elseif($media->kind === 'youtube' && $galleryYoutubeId)
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $galleryYoutubeId }}?rel=0&amp;modestbranding=1" title="{{ $media->alt ?: $article->title }}" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                        @else
                            <img src="{{ $media->path ? asset($media->path) : $fallbackImage }}" alt="{{ $media->alt ?: $article->title }}" loading="lazy">
                        @endif
                    </figure>
                @endforeach
            </section>
        @endif

        @if($related->isNotEmpty())
            <section class="news-detail-related">
                <div class="news-detail-related-head">
                    <h2>Más novedades</h2>
                    <a href="/novedades">Ver todas</a>
                </div>
                <div class="news-detail-related-grid">
                    @foreach($related as $item)
                        @php
                            $media = $item->media->firstWhere('kind', 'image') ?? $item->media->first();
                            $image = ($media?->kind === 'image' && $media->path) ? asset($media->path) : asset('assets/figma/news-'.(($loop->index % 3) + 1).'.jpg');
                            $slug = $item->settings['slug'] ?? \Illuminate\Support\Str::slug($item->title ?: 'novedad').'-'.$item->id;
                            $href = route('news.show', $slug);
                            $copy = trim(strip_tags((string) $item->body));
                        @endphp
                        <article class="news-page-card">
                            <a class="news-page-media" href="{{ $href }}" aria-label="Leer {{ $item->title }}">
                                <img src="{{ $image }}" alt="{{ $media?->alt ?: $item->title }}" width="392" height="350" loading="lazy">
                            </a>
                            <div class="news-page-copy">
                                <p class="news-page-label">{{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($item->label ?: 'Productos')) }}</p>
                                <h1>{{ $item->title }}</h1>
                                <p class="news-page-excerpt">{{ $copy }}</p>
                                <a href="{{ $href }}">Leer más</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</main>
@endsection
