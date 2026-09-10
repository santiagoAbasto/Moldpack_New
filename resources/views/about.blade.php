@extends('layouts.site')

@section('body_class', 'about-route')

@section('content')
@php
    $section = $page->sections->firstWhere('type', 'about_page') ?? $page->sections->first();
    $settings = $section?->settings ?? [];
    $photo = $section?->media?->first();
    $iconPaths = [
        'factory' => 'assets/figma/exact/nosotros/factory.svg',
        'oven' => 'assets/figma/exact/nosotros/oven.svg',
        'truck' => 'assets/figma/exact/nosotros/truck.svg',
    ];
@endphp

<div class="about-page">
    <nav class="about-breadcrumb shell" aria-label="Migas de pan">
        <a href="/">Inicio</a><span aria-hidden="true"> &gt; </span><span>Nosotros</span>
    </nav>

    @if($section)
        <section class="about-page-story shell" aria-labelledby="about-page-title">
            <figure class="about-page-photo">
                <img src="{{ asset($photo?->path ?: 'assets/figma/exact/nosotros/raw-4.png') }}" alt="{{ $photo?->alt ?: 'Cupcakes elaborados con pirotines Moldpack' }}" width="600" height="600">
            </figure>
            <div class="about-page-copy">
                <h1 id="about-page-title">{{ $section->title }}</h1>
                <div class="about-page-rich">{!! $section->body !!}</div>
                <h2>{{ $settings['second_title'] ?? 'Fabricantes de accesorios para cotillón.' }}</h2>
                <div class="about-page-rich second">{!! $settings['second_body'] ?? '' !!}</div>
            </div>
        </section>

        <section class="about-benefits" aria-labelledby="about-benefits-title">
            <div class="shell">
                <h2 id="about-benefits-title">{{ $settings['benefits_title'] ?? '¿Porque elegirnos?' }}</h2>
                <div class="about-benefit-grid">
                    @foreach($section->items as $item)
                        @php($icon = $item->settings['icon'] ?? ['factory', 'oven', 'truck'][$loop->index % 3])
                        <article class="about-benefit-card">
                            <img src="{{ asset($iconPaths[$icon] ?? $iconPaths['factory']) }}" alt="" width="40" height="40">
                            <h3>{{ $item->title }}</h3>
                            <div>{!! $item->body !!}</div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
