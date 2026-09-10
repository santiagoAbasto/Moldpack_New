@php
    $privateMode = $privateMode ?? false;
    $productsNavigationIsCurrent = request()->is('productos') || request()->is('productos/*');
    $catalogNavigationIsCurrent = request()->is('catalogo');
    $newsNavigationIsCurrent = request()->is('novedades') || request()->is('novedades/*');
    $contactNavigationIsCurrent = request()->is('contacto');
    $qualityNavigationIsCurrent = request()->is('calidad');
    $storesNavigationIsCurrent = request()->is('donde-comprar');
@endphp
<header class="site-header" @if($privateMode) data-private-header @endif>
    <div class="shell header-inner">
        <a href="{{ $privateMode ? route('client.portal', 'productos') : '/' }}" class="brand"><img src="{{ asset('assets/figma/exact/logo-header.png') }}" alt="Moldpack"></a>
        <button class="site-search" type="button" @if($privateMode) data-public-destination="/" @else data-ai-open aria-haspopup="dialog" aria-controls="moldpack-ai" @endif><img src="{{ asset('assets/figma/exact/search.svg') }}" alt=""><span>Buscar ...</span></button>
        @if($privateMode)
            <nav id="private-zone-menu" class="private-header-navigation" aria-label="Zona privada" data-private-menu>
                <div class="private-menu-heading"><span>Zona privada</span><strong>{{ $client->first_name ?: explode(' ', $client->name)[0] }}</strong></div>
                @foreach($nav as $key => $label)
                    <a href="{{ route('client.portal', $key) }}" class="{{ $section === $key ? 'is-current' : '' }}" @if($section === $key) aria-current="page" @endif>{{ $label }}@if($key === 'carrito' && $cart->sum('quantity'))<b>{{ $cart->sum('quantity') }}</b>@endif</a>
                @endforeach
            </nav>
            <button class="private-menu-backdrop" type="button" aria-label="Cerrar menú" data-private-menu-close hidden></button>
        @else
            <nav id="site-menu" aria-label="Navegación principal">
                <button class="site-menu-search" type="button" data-ai-open aria-haspopup="dialog" aria-controls="moldpack-ai"><img src="{{ asset('assets/figma/exact/search.svg') }}" alt=""><span>Buscar ...</span></button>
                <a class="{{ request()->is('nosotros') ? 'is-current' : '' }}" href="/nosotros" @if(request()->is('nosotros')) aria-current="page" @endif>Nosotros</a>
                <a class="{{ $productsNavigationIsCurrent ? 'is-current' : '' }}" href="/productos" @if($productsNavigationIsCurrent) aria-current="page" @endif>Productos</a>
                <a class="{{ $catalogNavigationIsCurrent ? 'is-current' : '' }}" href="/catalogo" @if($catalogNavigationIsCurrent) aria-current="page" @endif>Catálogo</a>
                <a class="{{ $newsNavigationIsCurrent ? 'is-current' : '' }}" href="/novedades" @if($newsNavigationIsCurrent) aria-current="page" @endif>Novedades</a>
                <a class="{{ $qualityNavigationIsCurrent ? 'is-current' : '' }}" href="/calidad" @if($qualityNavigationIsCurrent) aria-current="page" @endif>Calidad</a>
                <a class="{{ $storesNavigationIsCurrent ? 'is-current' : '' }}" href="/donde-comprar" @if($storesNavigationIsCurrent) aria-current="page" @endif>Donde comprar</a>
                <a class="{{ $contactNavigationIsCurrent ? 'is-current' : '' }}" href="/contacto" @if($contactNavigationIsCurrent) aria-current="page" @endif>Contacto</a>
            </nav>
        @endif
        <span class="header-divider">|</span>
        @if($privateMode)
            <div class="private-profile" data-private-profile>
                <button class="client-area private-client-area" type="button" aria-haspopup="menu" aria-expanded="false" data-private-profile-toggle><img src="{{ asset('assets/figma/exact/user.svg') }}" alt=""><span>{{ $client->first_name ?: explode(' ', $client->name)[0] }}</span><svg class="private-profile-chevron" viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg></button>
                <div class="private-profile-menu" role="menu" data-private-profile-menu hidden>
                    <div><strong>{{ $client->name }}</strong><small>{{ $client->email }}</small></div>
                    <form method="post" action="{{ route('client.logout') }}">@csrf<input type="hidden" name="redirect_to" value="/"><button type="submit" role="menuitem"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg><span>Cerrar sesión</span></button></form>
                </div>
            </div>
            <button class="private-menu-toggle" type="button" aria-label="Abrir menú de zona privada" aria-expanded="false" aria-controls="private-zone-menu" data-private-menu-toggle><span aria-hidden="true"></span></button>
        @else
            <button class="client-area client-area-trigger" type="button" aria-haspopup="dialog" aria-controls="client-login"><img src="{{ asset('assets/figma/exact/user.svg') }}" alt=""><span>Area clientes</span></button>
            <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="site-menu" data-menu-toggle><span class="menu-toggle-bars" aria-hidden="true"></span></button>
        @endif
    </div>
</header>
