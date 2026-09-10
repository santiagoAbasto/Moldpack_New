@extends('layouts.site')

@section('body_class', 'stores-route')

@section('content')
<div class="stores-page">
    <nav class="stores-breadcrumb shell" aria-label="Migas de pan"><a href="/">Inicio</a><span>&gt;</span><span>Donde comprar</span></nav>
    <section class="stores-content shell" aria-label="Puntos de venta Moldpack">
        <aside class="stores-directory">
            <div class="stores-country"><span>{{ $stores['country'] ?? 'Argentina' }}</span><img src="{{ asset('assets/figma/exact/stores/search.svg') }}" alt=""></div>
            <div class="stores-list" id="stores-list">
                @foreach(($stores['locations'] ?? []) as $index => $location)
                    <button class="store-entry {{ $loop->first ? 'is-active' : '' }} {{ $index >= 4 ? 'is-hidden' : '' }}" type="button" data-store-index="{{ $index }}" @if($index >= 4) hidden @endif>
                        <strong>{{ $location['name'] ?? '' }}</strong>
                        <span>{{ $location['address'] ?? '' }}</span>
                        @foreach(preg_split('/\s*\/\s*/', $location['phone'] ?? '', -1, PREG_SPLIT_NO_EMPTY) as $phone)<span>{{ $phone }}</span>@endforeach
                        @if(!empty($location['email']))<span>{{ $location['email'] }}</span>@endif
                    </button>
                @endforeach
            </div>
            <button class="stores-more" type="button">{{ $stores['load_more_label'] ?? 'Cargar más resultados' }}</button>
        </aside>
        <div class="stores-map-wrap">
            <div class="stores-map" id="stores-map" data-pin="{{ asset('assets/figma/exact/stores/pin.svg') }}" aria-label="Mapa de puntos de venta"></div>
            <button class="stores-location-button" id="stores-use-location" type="button" aria-describedby="stores-location-status">
                <span class="stores-location-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M10 2V4M10 16V18M2 10H4M16 10H18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.7"/></svg>
                </span>
                <span class="stores-location-label">Usar mi ubicación</span>
            </button>
            <div class="stores-location-result" id="stores-location-result" role="status" aria-live="polite" aria-hidden="true">
                <div class="stores-result-mark" aria-hidden="true"><img src="{{ asset('assets/figma/exact/stores/pin.svg') }}" alt=""></div>
                <div><span>Tu punto de venta más cercano</span><strong id="stores-nearest-name"></strong><small id="stores-nearest-detail"></small></div>
                <a id="stores-nearest-route" href="#" target="_blank" rel="noopener noreferrer">Cómo llegar<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.333 8H12.667M9.333 4.667L12.667 8L9.333 11.333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            <p class="stores-location-status" id="stores-location-status" aria-live="polite"></p>
        </div>
    </section>
</div>
<script id="stores-data" type="application/json">{!! Illuminate\Support\Js::encode($stores['locations'] ?? []) !!}</script>
@vite('resources/js/stores-map.js')
@endsection
