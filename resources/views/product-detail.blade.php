@extends('layouts.site')
@section('title', $product->title.' | MoldPack')
@section('body-class', 'product-detail-page')
@section('content')
@php($main = $product->media->first())
<main class="product-detail shell">
 <nav class="product-breadcrumb"><a href="/">Inicio</a><span> &gt; </span><a href="/productos">Productos</a><span> &gt; {{ $product->title }}</span></nav>
 <section class="product-hero-detail">
  <div class="product-gallery">
   <div class="product-thumbs">
    @foreach($product->media->take(3) as $media)
     @php($youtubeId = $media->kind === 'youtube' ? preg_replace('~^.*(?:youtu.be/|v=|embed/)([^?&/]+).*$~', '$1', $media->url) : null)
     <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-gallery-index="{{ $loop->index }}" aria-label="Mostrar {{ $media->alt ?: 'medio '.$loop->iteration }}">
      @if($media->kind === 'image')<img src="{{ asset($media->path) }}" alt="" onerror="this.onerror=null;this.src='{{ asset('assets/product-placeholder.svg') }}'">
      @elseif($media->kind === 'video')<video src="{{ asset($media->path) }}" muted preload="metadata"></video><span class="media-play" aria-hidden="true">▶</span>
      @else<img src="https://i.ytimg.com/vi/{{ $youtubeId }}/hqdefault.jpg" alt=""><span class="media-play" aria-hidden="true">▶</span>@endif
     </button>
    @endforeach
   </div>
   <div class="product-main-image">
    @forelse($product->media->take(3) as $media)
     @php($youtubeId = $media->kind === 'youtube' ? preg_replace('~^.*(?:youtu.be/|v=|embed/)([^?&/]+).*$~', '$1', $media->url) : null)
     <div class="product-main-media {{ $loop->first ? 'active' : '' }}" data-gallery-panel="{{ $loop->index }}">
      @if($media->kind === 'image')<img src="{{ asset($media->path) }}" alt="{{ $media->alt ?: $product->title }}" onerror="this.onerror=null;this.src='{{ asset('assets/product-placeholder.svg') }}'">
      @elseif($media->kind === 'video')<video src="{{ asset($media->path) }}" controls playsinline preload="metadata"></video>
      @else<iframe src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0&amp;modestbranding=1" title="{{ $media->alt ?: $product->title }}" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>@endif
     </div>
    @empty<div class="product-main-media active"><img src="{{ asset('assets/product-placeholder.svg') }}" alt="Imagen próximamente para {{ $product->title }}"></div>@endforelse
   </div>
  </div>
  <div class="product-info"><h1>{{ $product->title }}</h1><div class="product-rule"></div>
   @foreach([['swatch','Diseño',$product->settings['design']??''],['utensils','Envase apto para alimentos',$product->settings['food_safe']??''],['shield','Calidad',$product->settings['quality']??''],['package','Presentación',$product->label]] as [$icon,$title,$copy])<div class="product-spec"><span class="spec-icon">@include('partials.product-icon',['icon'=>$icon])</span><p><strong>{{ $title }}:</strong><br>{{ $copy }}</p></div>@endforeach
   <a class="product-consult" href="https://wa.me/5491147272836?text={{ urlencode('Hola, quiero consultar por '.$product->title) }}" target="_blank" rel="noopener">Consultar</a>
  </div>
 </section>
 <section class="related-products"><h2>Productos relacionados</h2><div class="related-grid">@foreach($related as $item)<a class="catalog-product" href="{{ $item->url }}"><span class="catalog-product-media"><img src="{{ $item->media->first()?->path ? asset($item->media->first()->path) : asset('assets/product-placeholder.svg') }}" alt="{{ $item->title }}" onerror="this.onerror=null;this.src='{{ asset('assets/product-placeholder.svg') }}'"><i></i></span><span class="catalog-product-category">{{ $item->subtitle }}</span><strong>{{ $item->title }}</strong><span class="catalog-product-presentation">PRESENTACIONES: {{ $item->label }}</span></a>@endforeach</div></section>
</main>
@endsection
@push('scripts')<script>document.querySelectorAll('[data-gallery-index]').forEach(function(button){button.addEventListener('click',function(){var index=button.dataset.galleryIndex;document.querySelectorAll('[data-gallery-index]').forEach(function(item){item.classList.toggle('active',item===button)});document.querySelectorAll('[data-gallery-panel]').forEach(function(panel){var active=panel.dataset.galleryPanel===index;panel.classList.toggle('active',active);if(!active)panel.querySelectorAll('video').forEach(function(video){video.pause()})})})});</script>@endpush
