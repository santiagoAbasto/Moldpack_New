@extends('layouts.site')

@section('content')
@foreach($page->sections as $section)
    @php($settings = $section->settings ?? [])

    @if($section->type === 'hero')
        @php($slides = $section->items->where('is_visible', true)->values())
        <section class="hero" data-slider>
            @if($slides->isNotEmpty())
                @foreach($slides as $slideIndex => $slide)
                    @php($media = $slide->media->first())
                    <article class="hero-slide {{ $loop->first ? 'is-active' : '' }}" data-slide>
                        @if($media?->kind === 'youtube')
                            <iframe src="https://www.youtube.com/embed/{{ preg_replace('~^.*(?:youtu.be/|v=|embed/)([^?&/]+).*$~', '$1', $media->url) }}?autoplay=1&mute=1&loop=1&controls=0" allow="autoplay; fullscreen" title="{{ $media->alt }}"></iframe>
                        @elseif($media?->kind === 'video')
                            <video autoplay muted loop playsinline preload="metadata" aria-label="{{ $media->alt ?: $slide->title }}"><source src="{{ asset($media->path) }}" type="{{ $media->mime_type ?: 'video/mp4' }}"></video>
                        @else
                            <img src="{{ asset($media?->path ?: 'assets/figma/hero.jpg') }}" alt="{{ $media?->alt ?: $slide->title }}">
                        @endif
                        <div class="hero-shade"></div>
                        <div class="shell hero-copy">
                            <h1>{!! nl2br(e($slide->title)) !!}</h1>
                            <div class="rich">{!! $slide->body !!}</div>
                            <a class="button" href="{{ $slide->url ?: '#productos' }}">{{ $slide->label ?: 'Ver productos' }}</a>
                            @if($slides->count() > 1)
                                <div class="slide-dots" role="tablist" aria-label="Slides principales">
                                    @foreach($slides as $dotIndex => $dotSlide)
                                        <button class="{{ $dotIndex === $slideIndex ? 'is-active' : '' }}" type="button" data-slide-dot="{{ $dotIndex }}" aria-label="Mostrar slide {{ $loop->iteration }}" aria-selected="{{ $dotIndex === $slideIndex ? 'true' : 'false' }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            @else
                @php($media = $section->media->first())
                @if($media)<img src="{{ asset($media->path) }}" alt="{{ $media->alt }}">@endif
                <div class="hero-shade"></div>
                <div class="shell hero-copy"><h1>{!! nl2br(e($section->title)) !!}</h1><div class="rich">{!! $section->body !!}</div><a class="button" href="{{ $settings['button_url'] ?? '#productos' }}">{{ $settings['button_label'] ?? 'Ver productos' }}</a></div>
            @endif
        </section>
    @elseif($section->type === 'categories')
        <section class="section shell" id="productos"><h2>{{ $section->title }}</h2><div class="category-grid">@foreach($section->items as $item)<a class="category-card" href="{{ $item->url ?: '#' }}"><img src="{{ $item->media->first() ? asset($item->media->first()->path) : asset('assets/figma/category-'.(($loop->index % 3) + 1).'.jpg') }}" alt="{{ $item->media->first()->alt ?? $item->title }}"><span>{{ $item->title }}</span></a>@endforeach</div></section>
    @elseif($section->type === 'products')
        <section class="section products shell"><h2>{{ $section->title }}</h2><div class="product-grid">@foreach($section->items as $item)<article class="product"><a class="product-media product-media-{{ $loop->index }}" href="{{ $item->url ?: '#' }}" aria-label="Ver {{ $item->title }}"><img src="{{ $item->media->first()?->path ? asset($item->media->first()->path) : asset('assets/product-placeholder.svg') }}" alt="{{ $item->title }}" width="288" height="288" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('assets/product-placeholder.svg') }}'"></a><p>{{ $item->subtitle }}</p><h3>{{ $item->title }}</h3><div class="product-presentations"><span>PRESENTACIONES: {{ $item->label ?: 'Consultar disponibilidad' }}</span></div></article>@endforeach</div></section>
    @elseif($section->type === 'about')
        <section class="about shell" id="nosotros"><div class="about-image"><img src="{{ $section->media->first() ? asset($section->media->first()->path) : asset('assets/figma/about.jpg') }}" alt="{{ $section->media->first()->alt ?? 'Nosotros' }}"></div><div><h2>{{ $section->title }}</h2><div class="rich">{!! $section->body !!}</div><a class="button outline" href="{{ $settings['button_url'] ?? '#' }}">{{ $settings['button_label'] ?? 'Más información' }}</a></div></section>
    @elseif($section->type === 'news')
        <section class="section shell" id="novedades"><div class="section-head"><h2>{{ $section->title }}</h2><a class="button outline" href="{{ $settings['button_url'] ?? '#' }}">{{ $settings['button_label'] ?? 'Ver todas' }}</a></div><div class="news-grid">@foreach($section->items as $item)<article class="news-card"><a class="news-media" href="{{ $item->url ?: '#' }}" aria-label="Leer {{ $item->title }}"><img src="{{ $item->media->first() ? asset($item->media->first()->path) : asset('assets/figma/news-'.(($loop->index % 3) + 1).'.jpg') }}" alt="{{ $item->title }}"></a><div><small>{{ $item->label ?: 'PRODUCTOS' }}</small><h3>{{ $item->title }}</h3><div class="excerpt">{!! $item->body !!}</div><a href="{{ $item->url ?: '#' }}">Leer más</a></div></article>@endforeach</div></section>
    @elseif($section->type === 'rich_text')
        <section class="section shell prose"><h2>{{ $section->title }}</h2><div class="rich">{!! $section->body !!}</div></section>
    @elseif($section->type === 'media')
        <section class="section shell prose"><h2>{{ $section->title }}</h2>@foreach($section->media as $media)@if($media->kind === 'youtube')<div class="video"><iframe src="https://www.youtube.com/embed/{{ preg_replace('~^.*(?:youtu.be/|v=|embed/)([^?&/]+).*$~', '$1', $media->url) }}" allowfullscreen></iframe></div>@elseif($media->kind === 'video')<video controls class="content-video"><source src="{{ asset($media->path) }}"></video>@else<img class="content-image" src="{{ asset($media->path) }}" alt="{{ $media->alt }}">@endif @endforeach</section>
    @elseif($section->type === 'cta')
        <section class="cta" id="catalogo" style="--cta-bg:url('{{ $section->media->first() ? asset($section->media->first()->path) : asset('assets/figma/exact/catalog-bg.png') }}')"><div class="shell"><h2>{{ $section->title }}</h2><div class="rich">{!! $section->body !!}</div><a class="button" href="{{ $settings['button_url'] ?? '#contacto' }}">{{ $settings['button_label'] ?? 'Contactanos' }}</a></div></section>
    @endif
@endforeach

<style>
    /* Figma node 1010:1345: 75% vertical gradient + 20% uniform black, no video filters. */
    .hero-shade{background:linear-gradient(180deg,rgba(0,0,0,.75) 0%,rgba(0,0,0,0) 100%),rgba(0,0,0,.2);pointer-events:none}
    .hero-slide>video,.hero>video{filter:none;opacity:1}
    .hero-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity .45s ease}
    .hero-slide.is-active{opacity:1;visibility:visible}
    .hero-slide>img,.hero-slide>video,.hero-slide>iframe{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border:0}
    .slide-dots{display:flex;gap:4px;width:max-content;height:6px;margin-top:100px}
    .slide-dots button{display:block;width:28px;height:6px;padding:0;background:#fff;border:0;border-radius:0;opacity:.5;cursor:pointer;transition:opacity .2s ease,transform .14s cubic-bezier(.22,1,.36,1)}
    .slide-dots button.is-active{opacity:1}
    .slide-dots button:focus-visible{outline:2px solid #fff;outline-offset:4px}
    .product{display:flex;flex-direction:column;width:288px;height:459px;border:1px solid #d9d9d9;border-radius:8px;background:#fff;overflow:hidden;text-align:center}
    .product .product-media{position:relative;inset:auto;flex:0 0 288px;width:288px;height:288px;margin:-1px -1px 0;border-radius:8px 8px 0 0;background:#fff;display:grid;place-items:center;overflow:hidden;color:inherit;text-decoration:none;text-transform:none}
    .product-media::after{content:"";position:absolute;inset:0;background-color:rgba(0,0,0,.03);pointer-events:none;transition:background-color .22s ease}
    .product-media::before{content:"";position:absolute;z-index:3;top:50%;left:50%;width:18px;height:18px;background:linear-gradient(#fff,#fff) center/18px 1.5px no-repeat,linear-gradient(#fff,#fff) center/1.5px 18px no-repeat;opacity:0;transform:translate(-50%,-50%) scale(.72);transition:opacity .18s ease,transform .24s cubic-bezier(.22,1,.36,1);pointer-events:none}
    .product-media img{display:block;width:100%;height:100%;object-fit:cover;background:transparent}
    .product-media-0 img{width:89.583%;height:89.583%}.product-media-2 img{width:92.36%;height:88.47%}.product-media-3 img{width:84.72%;height:84.72%}.product-media-7 img{width:87.5%;height:87.5%}
    .product p{position:static;margin:16px 24px 0;color:#ec458b;text-align:center;font:700 14px/1.5 'Public Sans';text-transform:none}
    .product h3{flex:0 0 60px;width:240px;height:60px;margin:5px auto 0;color:#000;text-align:center;font:400 20px/1.5 'Public Sans';overflow:hidden}
    .product-presentations{display:flex;flex:0 0 46px;align-items:flex-start;justify-content:center;width:286px;height:46px;margin-top:22px;padding:16px 12px 12px;border-radius:0 0 8px 8px;background:#fcfafb;color:rgba(0,0,0,.8);text-align:center;font:400 12px/1.5 'Public Sans';white-space:nowrap;overflow:hidden}
    @media(max-width:1100px){.product{width:100%;max-width:288px;margin-inline:auto}.product-media{width:calc(100% + 2px)}}
    .product-media:focus-visible::before{opacity:1;transform:translate(-50%,-50%) scale(1)}.product-media:focus-visible::after{background-color:rgba(0,0,0,.2)}
    @media(hover:hover) and (pointer:fine){.slide-dots button:hover{opacity:.8;transform:scaleY(1.34)}.product-media:hover::before{opacity:1;transform:translate(-50%,-50%) scale(1)}.product-media:hover::after{background-color:rgba(0,0,0,.2)}}
    @media(prefers-reduced-motion:reduce){.hero-slide,.slide-dots button,.product-media::before,.product-media::after{transition:none}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-slider]').forEach(function(slider){var slides=slider.querySelectorAll('[data-slide]');var dots=slider.querySelectorAll('[data-slide-dot]');if(slides.length<2)return;var current=0;function show(index){slides[current].classList.remove('is-active');current=index;slides[current].classList.add('is-active');dots.forEach(function(dot){var active=Number(dot.dataset.slideDot)===current;dot.classList.toggle('is-active',active);dot.setAttribute('aria-selected',active?'true':'false')})}dots.forEach(function(dot){dot.addEventListener('click',function(){show(Number(dot.dataset.slideDot))})});if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)setInterval(function(){show((current+1)%slides.length)},6500)})});
</script>
@endsection
