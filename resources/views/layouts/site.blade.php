<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @php
        $seoTitle = $page->seo_title ?: $page->name.' | Moldpack';
        $seoDescription = $page->seo_description ?: 'Packaging gastronómico Moldpack: productos, calidad, distribuidores y atención comercial.';
        $seoCanonical = $page->canonical_url ?: url()->current();
        $seoImage = $page->seo_image ?: 'assets/figma/exact/logo-header.png';
        $seoImageUrl = str_starts_with($seoImage, 'http') ? $seoImage : asset($seoImage);
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if($page->seo_keywords)<meta name="keywords" content="{{ $page->seo_keywords }}">@endif
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta name="robots" content="{{ $page->noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' }}">
    <meta property="og:type" content="website"><meta property="og:locale" content="es_AR"><meta property="og:site_name" content="Moldpack">
    <meta property="og:title" content="{{ $page->og_title ?: $seoTitle }}"><meta property="og:description" content="{{ $page->og_description ?: $seoDescription }}"><meta property="og:url" content="{{ $seoCanonical }}"><meta property="og:image" content="{{ $seoImageUrl }}">
    <meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $page->og_title ?: $seoTitle }}"><meta name="twitter:description" content="{{ $page->og_description ?: $seoDescription }}"><meta name="twitter:image" content="{{ $seoImageUrl }}">
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Moldpack', 'url' => url('/'), 'logo' => asset('assets/figma/exact/logo-header.png'), 'email' => $contact['email'] ?? 'ventas@moldpack.com.ar', 'telephone' => $contact['phone'] ?? '4727-2836/2837', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $contact['address'] ?? 'Dante Alighieri 1377', 'addressLocality' => 'Don Torcuato', 'addressRegion' => 'Buenos Aires', 'addressCountry' => 'AR']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <meta name="theme-color" content="#691b3d">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon/favicon-96x96.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+1:wght@300;400&family=Montserrat:wght@300&family=PT+Sans:wght@400;700&family=Public+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    @vite('resources/css/public.css')
</head>
<body class="@yield('body_class')">
@php
    $whatsapp = preg_replace('/\D+/', '', $contact['whatsapp'] ?? '5491147272836');
    $whatsappMessage = rawurlencode('Hola Moldpack, quisiera recibir más información.');
    $productsNavigationIsCurrent = request()->is('productos') || request()->is('productos/*');
    $catalogNavigationIsCurrent = request()->is('catalogo');
    $newsNavigationIsCurrent = request()->is('novedades') || request()->is('novedades/*');
    $contactNavigationIsCurrent = request()->is('contacto');
    $qualityNavigationIsCurrent = request()->is('calidad');
    $storesNavigationIsCurrent = request()->is('donde-comprar');
    $socialLinks = (App\Models\SiteSetting::query()->where('key', 'social')->value('value')['links'] ?? []);
    $newsletterSettings = App\Models\SiteSetting::query()->where('key', 'newsletter')->value('value') ?? [];
    $siteNotice = null;
    if (session('contact_success')) {
        $siteNotice = ['type' => 'success', 'title' => 'Mensaje enviado', 'message' => session('contact_success')];
    } elseif (session('newsletter_success')) {
        $siteNotice = ['type' => 'success', 'title' => 'Suscripción confirmada', 'message' => session('newsletter_success')];
    } elseif ($errors->getBag('newsletter')->any()) {
        $siteNotice = ['type' => 'error', 'title' => 'Revisá tu email', 'message' => $errors->getBag('newsletter')->first('newsletter_email')];
    } elseif (request()->is('contacto') && $errors->any()) {
        $siteNotice = ['type' => 'error', 'title' => 'Faltan algunos datos', 'message' => 'Revisá los campos marcados e intentá nuevamente.'];
    }
@endphp

@if($siteNotice)
    <div class="site-toast-region" aria-live="{{ $siteNotice['type'] === 'error' ? 'assertive' : 'polite' }}" aria-atomic="true">
        <aside class="site-toast is-{{ $siteNotice['type'] }}" data-site-toast role="{{ $siteNotice['type'] === 'error' ? 'alert' : 'status' }}">
            <span class="site-toast-icon" aria-hidden="true">
                @if($siteNotice['type'] === 'success')
                    <svg viewBox="0 0 24 24"><path d="m6.8 12.2 3.2 3.2 7.2-7.2"/></svg>
                @else
                    <svg viewBox="0 0 24 24"><path d="M12 7.5v5.2"/><path d="M12 16.5h.01"/></svg>
                @endif
            </span>
            <span class="site-toast-copy"><strong>{{ $siteNotice['title'] }}</strong><span>{{ $siteNotice['message'] }}</span></span>
            <button class="site-toast-close" type="button" aria-label="Cerrar notificación"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m6 6 8 8m0-8-8 8"/></svg></button>
            <span class="site-toast-progress" aria-hidden="true"></span>
        </aside>
    </div>
@endif

@include('partials.site-header')

<style>
    .hero+.section{padding-top:82px}.products{padding-top:80px}.news-grid{margin-top:24px}.cta{margin-top:80px;background-image:linear-gradient(rgba(105,27,61,.66),rgba(105,27,61,.66)),var(--cta-bg)}.site-footer{margin-top:-1px}
    .footer-lower{position:absolute;right:0;bottom:25px;left:0}.footer-rule{width:100vw;height:1px;margin-left:calc((100% - 100vw)/2);background:rgba(255,255,255,.08)}.footer-lower .footer-bottom{position:static;border-top:0;padding:24px 4px 0}
    @media(max-width:1100px){.footer-lower{position:static;grid-column:1/-1;width:auto;margin-top:20px;transform:none}.footer-lower .footer-bottom{margin-top:0}}
    .whatsapp-float{position:fixed;z-index:60;right:max(28px,env(safe-area-inset-right));bottom:max(66px,env(safe-area-inset-bottom));display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none;filter:drop-shadow(0 15px 25px rgba(10,74,34,.28))}
    .whatsapp-label{display:grid;gap:3px;padding:11px 15px;color:#164d29;background:rgba(255,255,255,.97);border:1px solid rgba(22,77,41,.12);border-radius:16px;box-shadow:0 8px 28px rgba(18,64,34,.12);white-space:nowrap;opacity:0;pointer-events:none;transform:translateX(9px) scale(.96);transform-origin:right center;transition:opacity .18s ease,transform .24s cubic-bezier(.22,1,.36,1)}
    .whatsapp-label strong{font:700 13px/1.1 'Public Sans'}.whatsapp-label small{color:#568064;font:400 11px/1.2 'Public Sans'}
    .whatsapp-button{position:relative;display:grid;place-items:center;width:64px;height:64px;background:linear-gradient(145deg,#32e477 0%,#20bd5a 55%,#159447 100%);border:1px solid rgba(255,255,255,.52);border-radius:50%;box-shadow:inset 0 2px 1px rgba(255,255,255,.32),0 10px 30px rgba(22,170,79,.35);transition:transform .18s cubic-bezier(.22,1,.36,1),box-shadow .2s ease}
    .whatsapp-button::before{content:"";position:absolute;inset:-8px;border:2px solid rgba(37,211,102,.42);border-radius:50%;animation:whatsapp-ring 3.6s cubic-bezier(.22,1,.36,1) infinite}
    .whatsapp-button::after{content:"";position:absolute;inset:5px;border-radius:50%;background:linear-gradient(125deg,rgba(255,255,255,.28),transparent 42%);pointer-events:none}
    .whatsapp-button img{position:relative;z-index:1;width:36px;height:36px;transform-box:fill-box;transform-origin:center;animation:whatsapp-heartbeat 3.6s ease-in-out infinite}.whatsapp-status{position:absolute;z-index:2;right:1px;bottom:3px;width:14px;height:14px;background:#fff;border:3px solid #1bae55;border-radius:50%;box-shadow:0 2px 7px rgba(0,0,0,.18)}
    .whatsapp-float:hover .whatsapp-label,.whatsapp-float:focus-visible .whatsapp-label{opacity:1;transform:none}.whatsapp-float:hover .whatsapp-button,.whatsapp-float:focus-visible .whatsapp-button{transform:translateY(-3px) scale(1.04);box-shadow:inset 0 2px 1px rgba(255,255,255,.32),0 16px 34px rgba(22,170,79,.42)}.whatsapp-float:hover .whatsapp-button::before,.whatsapp-float:focus-visible .whatsapp-button::before,.whatsapp-float:hover .whatsapp-button img,.whatsapp-float:focus-visible .whatsapp-button img{animation-play-state:paused}.whatsapp-float:active .whatsapp-button{transform:scale(.96)}.whatsapp-float:focus-visible{outline:3px solid rgba(37,211,102,.38);outline-offset:7px;border-radius:32px}
    @keyframes whatsapp-heartbeat{0%,14%,28%,100%{transform:scale(1)}7%{transform:scale(1.12)}21%{transform:scale(1.07)}}
    @keyframes whatsapp-ring{0%,3%{opacity:0;transform:scale(.88)}8%{opacity:.62}16%{opacity:0;transform:scale(1.28)}17%{opacity:0;transform:scale(.9)}22%{opacity:.42}30%,100%{opacity:0;transform:scale(1.2)}}
    @media(max-width:620px){.whatsapp-float{right:max(18px,env(safe-area-inset-right));bottom:max(66px,env(safe-area-inset-bottom))}.whatsapp-button{width:58px;height:58px}.whatsapp-button img{width:33px;height:33px}.whatsapp-label{display:none}}
    @media(prefers-reduced-motion:reduce){.whatsapp-label,.whatsapp-button{transition:none}.whatsapp-button::before,.whatsapp-button img{animation:none}.whatsapp-button::before{opacity:.35}}
</style>

<main>@yield('content')</main>

<div class="moldpack-ai" id="moldpack-ai" role="dialog" aria-modal="true" aria-labelledby="moldpack-ai-title" hidden>
    <button class="moldpack-ai-backdrop" type="button" data-ai-close aria-label="Cerrar"></button>
    <section class="moldpack-ai-panel"><header><span class="moldpack-ai-mark">M</span><div><small>ASISTENTE INTELIGENTE</small><h2 id="moldpack-ai-title">IA Moldpack</h2></div><button type="button" data-ai-close aria-label="Cerrar">×</button></header><form data-ai-form><img src="{{ asset('assets/figma/exact/search.svg') }}" alt=""><input name="q" autocomplete="off" placeholder="¿Qué producto o información necesitás?" minlength="2" required><button>Buscar</button></form><p class="moldpack-ai-answer" data-ai-answer role="status" aria-live="polite">Buscá por producto, código, categoría, uso o haceme una pregunta sobre Moldpack.</p><div class="moldpack-ai-results" data-ai-results></div><div class="moldpack-ai-suggestions"><button data-ai-query="pirotines para cupcakes">Pirotines</button><button data-ai-query="productos para muffins">Muffins</button><button data-ai-query="dónde comprar">Dónde comprar</button><button data-ai-query="calidad y certificados">Calidad</button></div></section>
</div>

<div class="client-login-layer" id="client-login" role="dialog" aria-modal="true" aria-labelledby="client-login-title" hidden>
    <button class="client-login-backdrop" type="button" aria-label="Cerrar acceso de clientes"></button>
    <section class="client-login-card">
        <button class="client-login-close" type="button" aria-label="Cerrar">×</button>
        <h2 id="client-login-title">Area para clientes</h2>
        <form method="post" action="{{ route('client.login') }}">@csrf
            <label>Usuario<input name="username" autocomplete="username" placeholder="marianor" required></label>
            <label>Contraseña<input name="password" type="password" autocomplete="current-password" placeholder="**********" required></label>
            @if($errors->has('username'))<p class="client-login-error">{{ $errors->first('username') }}</p>@endif
            <div class="client-login-rule"></div>
            <button class="client-login-submit" type="submit">Iniciar sesión</button>
        </form>
        <p>¿No tenes cuenta?</p><button class="client-register-link" type="button">Registrate</button>
        <form class="client-register-form" method="post" action="{{ route('client.register') }}" hidden>@csrf
            <div class="client-register-grid"><label>Usuario<input name="username" required></label><label>Nombre y apellido<input name="name" required></label><label>Empresa<input name="business_name"></label><label>CUIT<input name="tax_id"></label><label>Email<input name="email" type="email" required></label><label>Teléfono<input name="phone"></label><label>Contraseña<input name="password" type="password" minlength="8" required></label><label>Repetir contraseña<input name="password_confirmation" type="password" minlength="8" required></label></div>
            <button class="client-login-submit" type="submit">Solicitar acceso</button>
        </form>
    </section>
</div>

@include('partials.site-footer')
@stack('scripts')
<script>
(()=>{const layer=document.querySelector('#client-login');if(!layer)return;const card=layer.querySelector('.client-login-card');const open=()=>{layer.hidden=false;document.body.classList.add('modal-open');requestAnimationFrame(()=>layer.classList.add('is-open'));setTimeout(()=>card.querySelector('input')?.focus(),160)};const close=()=>{layer.classList.remove('is-open');document.body.classList.remove('modal-open');setTimeout(()=>layer.hidden=true,180)};document.querySelector('.client-area-trigger')?.addEventListener('click',open);layer.querySelector('.client-login-backdrop')?.addEventListener('click',close);layer.querySelector('.client-login-close')?.addEventListener('click',close);layer.querySelector('.client-register-link')?.addEventListener('click',()=>{const form=layer.querySelector('.client-register-form');form.hidden=!form.hidden;card.classList.toggle('registering',!form.hidden)});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!layer.hidden)close()});@if($errors->has('username')) open(); @endif})();
(()=>{const toast=document.querySelector('[data-site-toast]');if(!toast)return;let timer;const dismiss=()=>{window.clearTimeout(timer);toast.classList.remove('is-visible');toast.classList.add('is-leaving');window.setTimeout(()=>toast.closest('.site-toast-region')?.remove(),240)};requestAnimationFrame(()=>requestAnimationFrame(()=>toast.classList.add('is-visible')));timer=window.setTimeout(dismiss,5600);toast.querySelector('.site-toast-close')?.addEventListener('click',dismiss);toast.addEventListener('mouseenter',()=>window.clearTimeout(timer));toast.addEventListener('mouseleave',()=>{timer=window.setTimeout(dismiss,1800)});})();
(()=>{
  const header=document.querySelector('.site-header'),toggle=header?.querySelector('[data-menu-toggle]'),menu=document.getElementById('site-menu');if(!toggle||!menu)return;
  const isOpen=()=>header.classList.contains('is-menu-open');
  const set=open=>{header.classList.toggle('is-menu-open',open);toggle.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-label',open?'Cerrar menú':'Abrir menú')};
  toggle.addEventListener('click',()=>{const open=!isOpen();set(open);if(open)menu.querySelector('a,button')?.focus()});
  menu.addEventListener('click',event=>{if(isOpen()&&event.target.closest('a,button'))set(false)});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&isOpen()){set(false);toggle.focus()}});
  document.addEventListener('click',event=>{if(isOpen()&&!header.contains(event.target))set(false)});
  window.matchMedia('(max-width:1024px)').addEventListener('change',()=>set(false));
})();
(()=>{document.querySelectorAll('.contact-form,.newsletter-form').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"]');if(!button)return;button.disabled=true;button.setAttribute('aria-busy','true');form.classList.add('is-submitting');const label=button.querySelector('.button-label');if(label)label.textContent='Enviando…';}));})();
(()=>{
  const layer=document.querySelector('#moldpack-ai');if(!layer)return;
  const input=layer.querySelector('input'),form=layer.querySelector('[data-ai-form]'),answer=layer.querySelector('[data-ai-answer]'),results=layer.querySelector('[data-ai-results]'),esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  let timer,controller,sequence=0;
  const open=()=>{layer.hidden=false;requestAnimationFrame(()=>layer.classList.add('open'));setTimeout(()=>input.focus(),120)};
  const close=()=>{clearTimeout(timer);controller?.abort();layer.classList.remove('open');setTimeout(()=>layer.hidden=true,180)};
  const reset=()=>{controller?.abort();layer.classList.remove('loading');results.innerHTML='';answer.textContent='Escribí al menos 2 caracteres para buscar en todo Moldpack.'};
  const search=async rawQuery=>{
    const q=String(rawQuery??'').trim();input.value=q;
    if(q.length<2){reset();return}
    controller?.abort();controller=new AbortController();const request=++sequence;
    layer.classList.add('loading');answer.textContent='Analizando el contenido de Moldpack…';
    try{
      const response=await fetch('{{ route('moldpack-ai.search') }}?q='+encodeURIComponent(q),{headers:{Accept:'application/json'},signal:controller.signal});
      const data=await response.json();if(!response.ok)throw new Error(data.message||'search_failed');if(request!==sequence)return;
      answer.textContent=data.answer;
      results.innerHTML=(data.results||[]).map(r=>`<a href="${esc(r.url)}">${r.image?`<img src="${esc(r.image)}" alt="">`:''}<span><small>${esc(r.type)}</small><strong>${esc(r.title)}</strong><em>${esc(r.subtitle)}</em></span><b>→</b></a>`).join('');
    }catch(error){if(error.name!=='AbortError')answer.textContent='No pude completar la búsqueda. Intentá nuevamente.'}
    finally{if(request===sequence)layer.classList.remove('loading')}
  };
  const schedule=()=>{clearTimeout(timer);const q=input.value.trim();if(q.length<2){reset();return}timer=setTimeout(()=>search(q),280)};
  document.querySelectorAll('[data-ai-open]').forEach(button=>button.addEventListener('click',open));layer.querySelectorAll('[data-ai-close]').forEach(button=>button.addEventListener('click',close));
  form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);search(input.value)});input.addEventListener('input',schedule);
  layer.querySelectorAll('[data-ai-query]').forEach(button=>button.addEventListener('click',()=>search(button.dataset.aiQuery)));document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!layer.hidden)close()});
})();
</script>
</body>
</html>
