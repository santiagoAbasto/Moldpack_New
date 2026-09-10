<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zona privada · Moldpack</title><meta name="robots" content="noindex,nofollow"><meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=M+PLUS+1:wght@300;400&family=Montserrat:wght@300&family=PT+Sans:wght@400;700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite('resources/css/public.css')
  @vite('resources/css/client-shell.css')
  @if($section==='productos') @vite('resources/css/client-products.css') @endif
  @if($section==='carrito') @vite('resources/css/client-cart.css') @endif
</head>
<body class="private-site {{ $section==='productos'?'private-products-page':'' }} {{ $section==='carrito'?'private-cart-page':'' }}">
@php
  $nav=['productos'=>'Productos','carrito'=>'Carrito','pedidos'=>'Mis pedidos','pagos'=>'Info de pagos','cuenta'=>'Estado de cuenta','facturas'=>'Facturas'];
  $cartSubtotal=$cartLines->sum(fn($line)=>$line['price']*$line['quantity']);
  $discount=$cartSubtotal*(float)$client->discount_percent/100; $net=$cartSubtotal-$discount; $tax=$net*.21; $total=$net+$tax;
  $status=['pending'=>'Pendiente','approved'=>'Aprobado','preparing'=>'En preparación','ready'=>'Listo','dispatched'=>'Despachado','delivered'=>'Entregado','cancelled'=>'Cancelado'];
@endphp
@include('partials.site-header', ['privateMode' => true])

@if(session('success')||$errors->any())<div class="private-toast {{ $errors->any()?'error':'' }}" data-private-toast><span>{{ $errors->any()?'!':'✓' }}</span><div><strong>{{ $errors->any()?'Revisá los datos':'Listo' }}</strong><p>{{ $errors->first() ?: session('success') }}</p></div><button type="button" aria-label="Cerrar">×</button></div>@endif

<div class="client-exit-layer" data-client-exit role="dialog" aria-modal="true" aria-labelledby="client-exit-title" hidden>
  <button class="client-exit-backdrop" type="button" data-client-stay aria-label="Permanecer en zona privada"></button>
  <section class="client-exit-card">
    <span class="client-exit-icon" aria-hidden="true"><img src="{{ asset('assets/figma/exact/user.svg') }}" alt=""></span>
    <h2 id="client-exit-title">Estás en tu zona privada</h2>
    <p>Para navegar por el sitio público primero debés cerrar tu sesión de cliente.</p>
    <div class="client-exit-actions"><button type="button" class="client-stay" data-client-stay>Permanecer en zona privada</button><form method="post" action="{{ route('client.logout') }}">@csrf<input type="hidden" name="redirect_to" value="{{ session('client_public_destination', '/') }}" data-client-destination><button type="submit" class="client-leave">Cerrar sesión y continuar</button></form></div>
  </section>
</div>

<main class="private-main private-shell">
@if($section==='productos')
  <div class="private-breadcrumb"><a href="/">Inicio</a><span> &gt; Productos</span></div>
  <section class="private-search-panel"><form class="private-product-search" data-product-search><label class="wide"><span>Buscar</span><div><input name="q" placeholder="Buscar por producto / categoría / código"><img src="{{ asset('assets/figma/private/search-pink.svg') }}" alt=""></div></label><div class="search-separator"></div><label><span>Categoría</span><select name="category"><option value="">Seleccionar categoría</option>@foreach($categories as $category)<option>{{ $category }}</option>@endforeach</select></label><label><span>Subcategoría</span><select name="subcategory"><option value="">Seleccionar subcategoría</option></select></label><label class="code"><span>Código</span><input name="code" placeholder="Código"></label><button class="wine-button" type="submit">Buscar</button></form></section>
  <div class="private-filterbar"><div class="active-filters"><strong>Filtros:</strong><span data-filter-chip hidden><em></em><button type="button" aria-label="Quitar filtros"><img src="{{ asset('assets/figma/private/x.svg') }}" alt=""></button><small data-search-count aria-live="polite"></small></div><div class="view-buttons"><button type="button" data-view="list" class="active" aria-label="Vista lista"><img src="{{ asset('assets/figma/private/list.svg') }}" alt=""></button><button type="button" data-view="grid" aria-label="Vista grilla"><img src="{{ asset('assets/figma/private/grid.svg') }}" alt=""></button></div></div>
  <div class="private-product-head"><span></span><b>Cód.</b><b>Producto</b><b>Presentación</b><b>Cantidad</b>@if($client->show_prices)<b>Precio lista<br>x unidad</b><b>Subtotal</b>@endif<span></span></div>
  <section class="private-products" data-products>@forelse($products as $product)@php $presentations=collect(data_get($product->settings,'presentations',[]))->filter(fn($p)=>(float)($p['price']??0)>0)->values(); if($presentations->isEmpty())$presentations=collect([['code'=>data_get($product->settings,'code'),'name'=>data_get($product->settings,'presentation',$product->label),'price'=>data_get($product->settings,'price',0)]]); $first=$presentations->first(); $price=(float)($first['price']??0); $category=trim((string)(data_get($product->settings,'category')?:explode('/',(string)$product->subtitle)[0])); $subcategory=trim((string)(data_get($product->settings,'subcategory')?:explode('/',(string)$product->subtitle)[1]??'')); $searchText=Str::lower(Str::ascii(collect([$product->title,$product->subtitle,$product->label,$category,$subcategory,data_get($product->settings,'brand'),$presentations->pluck('code')->implode(' '),$presentations->pluck('name')->implode(' ')])->filter()->implode(' '))); @endphp
    <article class="private-product" data-search="{{ $searchText }}" data-category="{{ Str::lower(Str::ascii($category)) }}" data-subcategory="{{ Str::lower(Str::ascii($subcategory)) }}"><button type="button" class="product-photo" aria-label="Ampliar {{ $product->title }}">@if($product->media->first()?->path)<img src="{{ asset($product->media->first()->path) }}" alt="{{ $product->title }}">@endif<img class="product-expand" src="{{ asset('assets/figma/private/expand.svg') }}" alt=""></button><div class="product-code"><strong data-product-code>{{ $first['code']??'—' }}</strong></div><div class="product-name"><small>{{ Str::upper(str_replace('/',' | ',$product->subtitle)) }}</small><strong>{{ $product->title }}</strong></div><form method="post" action="{{ route('client.cart.add') }}" class="product-add">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="stay" value="productos"><select name="presentation_index" aria-label="Presentación de {{ $product->title }}">@foreach($presentations as $index=>$presentation)<option value="{{ $index }}" data-code="{{ $presentation['code']??'' }}" data-price="{{ (float)($presentation['price']??0) }}">{{ $presentation['name']??$presentation['code']??'Presentación' }}</option>@endforeach</select><div class="product-quantity-control"><input class="product-quantity" aria-label="Cantidad de {{ $product->title }}" type="number" name="quantity" min="0" max="9999" value="0"><button type="button" class="product-quantity-step quantity-up" data-quantity-step="1" aria-label="Aumentar cantidad"><img src="{{ asset('assets/figma/private/quantity-up.svg') }}" alt=""></button><button type="button" class="product-quantity-step quantity-down" data-quantity-step="-1" aria-label="Disminuir cantidad"><img src="{{ asset('assets/figma/private/quantity-down.svg') }}" alt=""></button></div>@if($client->show_prices)<strong class="unit-price" data-unit-price>${{ number_format($price,2,',','.') }}</strong><strong class="subtotal" data-row-subtotal>${{ number_format(0,2,',','.') }}</strong>@endif<button type="submit" class="figma-cart" title="Agregar al carrito"><img src="{{ asset('assets/figma/private/cart-pink.svg') }}" alt=""></button></form></article>
  @empty<div class="private-empty">Todavía no hay productos con precio publicados.</div>@endforelse</section>
  @if($products->isNotEmpty())<div class="private-empty private-search-empty" data-search-empty hidden><strong>No encontramos productos</strong><span>Probá con otro nombre, categoría, presentación o código.</span></div>@endif

@elseif($section==='carrito')
  <div class="cart-breadcrumb"><a href="{{ route('client.portal','productos') }}">Inicio</a><span> &gt; Carrito</span></div>
  @if($cartLines->isNotEmpty())
    <div class="cart-product-head"><span></span><b>Cód.</b><b>Producto</b><b>Presentación</b><b>Cantidad</b>@if($client->show_prices)<b>Precio lista<br>x unidad</b><b>Subtotal</b>@endif<span></span></div>
    <section class="cart-product-list">
      @foreach($cartLines as $line)
        @php
          $product=$line['product']; $qty=$line['quantity']; $price=$line['price']; $presentation=$line['presentation'];
          $presentations=collect(data_get($product->settings,'presentations',[]))->filter(fn($item)=>(float)($item['price']??0)>0)->values();
          if($presentations->isEmpty()) $presentations=collect([['code'=>data_get($product->settings,'code'),'name'=>data_get($product->settings,'presentation',$product->label),'price'=>data_get($product->settings,'price',0)]]);
        @endphp
        <article class="cart-product-row">
          <div class="cart-product-photo">@if($product->media->first()?->path)<img src="{{ asset($product->media->first()->path) }}" alt="{{ $product->title }}">@else<img src="{{ asset('assets/product-placeholder.svg') }}" alt="">@endif</div>
          <strong class="cart-product-code">{{ $presentation['code']??data_get($product->settings,'code','—') }}</strong>
          <div class="cart-product-name"><small>{{ Str::upper(str_replace('/',' | ',$product->subtitle)) }}</small><strong>{{ $product->title }}</strong></div>
          <form method="post" action="{{ route('client.cart.update',$line['key']) }}" class="cart-product-update">@csrf @method('PATCH')
            <select name="presentation_index" aria-label="Presentación de {{ $product->title }}" data-cart-presentation>@foreach($presentations as $index=>$option)<option value="{{ $index }}" {{ $line['presentation_index']===$index?'selected':'' }}>{{ $option['name']??$option['code']??'Presentación' }}</option>@endforeach</select>
            <div class="cart-quantity"><input name="quantity" value="{{ $qty }}" min="0" max="9999" inputmode="numeric" aria-label="Cantidad de {{ $product->title }}"><button type="button" class="up" data-step="1" aria-label="Aumentar cantidad"><img src="{{ asset('assets/figma/private/quantity-up.svg') }}" alt=""></button><button type="button" class="down" data-step="-1" aria-label="Disminuir cantidad"><img src="{{ asset('assets/figma/private/quantity-down.svg') }}" alt=""></button></div>
          </form>
          @if($client->show_prices)<span class="cart-unit-price">${{ number_format($price,2,',','.') }}</span><span class="cart-line-subtotal">${{ number_format($price*$qty,2,',','.') }}</span>@endif
          <form method="post" action="{{ route('client.cart.remove',$line['key']) }}" class="cart-remove">@csrf @method('DELETE')<button type="submit" aria-label="Eliminar {{ $product->title }}"><img src="{{ asset('assets/figma/private/cart-trash.svg') }}" alt=""></button></form>
        </article>
      @endforeach
    </section>
    <a class="cart-add-more" href="{{ route('client.portal','productos') }}">+Agregar más productos</a>

    <form class="cart-checkout" method="post" action="{{ route('client.checkout') }}" enctype="multipart/form-data">@csrf
      <div class="cart-checkout-left">
        <section class="cart-info-card"><h2>{{ $settings['important_title']??'Información importante' }}</h2><div class="cart-rule"></div><div class="cart-important-copy">Venta sujeta a disponibilidad en stock.<br>- Los precios se encuentran expresados en pesos argentinos.<br>- El plazo de entrega de la mercadería es de 7 a 15 días hábiles.<br>- Los precios se encuentran sin IVA.<br>- El pago deberá ser anticipado para el despacho del mismo.<br>- Los precios están sujetos al momento de facturación.<br><strong>- No se aceptan cambios o devoluciones por pedidos mal realizados.</strong></div></section>
        <section class="cart-message"><h2>Escribinos un mensaje</h2><textarea name="notes" placeholder="Días especiales de entrega, cambios de domicilio, expresos, requerimientos especiales en la mercadería, exenciones."></textarea></section>
        <section class="cart-attachment"><h2>Adjunta un archivo</h2><label><span data-cart-file-name>Seleccionar archivo</span><input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.xls,.xlsx,.doc,.docx"><i><img src="{{ asset('assets/figma/private/cart-upload.svg') }}" alt=""></i></label></section>
      </div>
      <div class="cart-checkout-right">
        <section class="cart-option-card"><h2>Entrega</h2><div class="cart-rule"></div>@foreach(['pickup'=>'Retiro cliente','moldpack_delivery'=>'Reparto Moldpack','freight'=>'Transporte al interior'] as $value=>$label)<label><input type="radio" name="delivery_method" value="{{ $value }}" {{ $loop->first?'checked':'' }}><span></span>{{ $label }}</label>@endforeach<input type="hidden" name="delivery_address" value="{{ $client->delivery_address }}"></section>
        <section class="cart-option-card"><h2>Forma de pago</h2><div class="cart-rule"></div>@foreach(['cash'=>'Efectivo','transfer'=>'Transferencia','check'=>'Cheque'] as $value=>$label)<label><input type="radio" name="payment_method" value="{{ $value }}" {{ $loop->first?'checked':'' }}><span></span>{{ $label }}</label>@endforeach</section>
        <section class="cart-order-summary"><h2>Tu pedido</h2><div class="cart-rule"></div><p><span>Subtotal</span><b>${{ number_format($cartSubtotal,2,',','.') }}</b></p>@if($discount>0)<p><span>Descuento ({{ number_format($client->discount_percent,0) }}%)</span><b>-${{ number_format($discount,2,',','.') }}</b></p>@endif<p class="cart-tax"><span>IVA (21%)</span><b>${{ number_format($tax,2,',','.') }}</b></p><p class="cart-total"><span>Total <small>(IVA INCLUIDO)</small></span><strong>${{ number_format($total,2,',','.') }}</strong></p></section>
        <div class="cart-checkout-actions"><a href="{{ route('client.portal','productos') }}">Cancelar pedido</a><button type="submit">Realizar pedido</button></div>
      </div>
    </form>
  @else
    <div class="cart-empty-state"><strong>Tu carrito está vacío</strong><p>Agregá productos para preparar tu pedido.</p><a href="{{ route('client.portal','productos') }}">+Agregar productos</a></div>
  @endif

@elseif($section==='pedidos')
  <h1 class="private-title">Mis pedidos</h1><section class="order-list">@forelse($orders as $order)<details class="order-row"><summary><span class="order-icon"><svg><use href="#i-box"/></svg></span><div><small>Pedido</small><strong>{{ $order->number }}</strong></div><div><small>Fecha</small><span>{{ $order->created_at->format('d/m/Y') }}</span></div><div><small>Items</small><span>{{ $order->items->sum('quantity') }}</span></div><div><small>Estado</small><b class="status status-{{ $order->status }}">{{ $status[$order->status]??$order->status }}</b></div><div><small>Importe</small><strong>${{ number_format($order->total,2,',','.') }}</strong></div><svg class="chev"><use href="#i-chevron"/></svg></summary><div class="order-detail">@foreach($order->items as $item)<p><span>{{ $item->quantity }} × {{ $item->name }} <small>{{ $item->sku }}</small></span><b>${{ number_format($item->line_total,2,',','.') }}</b></p>@endforeach</div></details>@empty<div class="private-empty">Todavía no realizaste pedidos.</div>@endforelse</section>

@elseif($section==='pagos')
  <h1 class="private-title">Info de pagos</h1><div class="payment-grid"><section class="bank-card"><span>TRANSFERENCIAS</span><h2>Datos bancarios</h2>@foreach([['Titular',$settings['bank_holder']??'Moldpack'],['Banco',$settings['bank_name']??'Banco Nación'],['CBU',$settings['bank_cbu']??''],['Alias',$settings['bank_alias']??''],['CUIT',$settings['bank_tax_id']??'']] as [$label,$value])<div><small>{{ $label }}</small><strong>{{ $value }}</strong><button type="button" data-copy="{{ $value }}" aria-label="Copiar"><svg><use href="#i-copy"/></svg></button></div>@endforeach</section><form class="payment-form private-card" method="post" action="{{ route('client.payments.store') }}" enctype="multipart/form-data">@csrf<h2>Informar pago</h2><div class="form-grid"><label>Fecha<input type="date" name="paid_at" max="{{ now()->format('Y-m-d') }}" required></label><label>Importe<input type="number" step=".01" min=".01" name="amount" placeholder="$ 0,00" required></label><label>Banco emisor<input name="bank" required></label><label>Sucursal<input name="branch"></label><label class="wide">Facturas pendientes<select name="invoice_ids[]" multiple>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->type }} {{ $invoice->number }} · ${{ number_format($invoice->total,2,',','.') }}</option>@endforeach</select></label><label class="wide">Observaciones<textarea name="observations"></textarea></label><label class="file-drop wide"><img src="{{ asset('assets/figma/private/upload.svg') }}" alt=""><span><strong>Adjuntar comprobante</strong><small>PDF, JPG o PNG · Máx. 10 MB</small></span><input type="file" name="receipt" required></label></div><button class="wine-button">Informar pago</button></form></div>

@elseif($section==='cuenta')
  <h1 class="private-title">Estado de cuenta</h1><div class="account-summary"><article class="overdue"><small>Saldo vencido</small><strong>${{ number_format($balance,2,',','.') }}</strong></article><article><small>Saldo actual</small><strong>${{ number_format($balance,2,',','.') }}</strong></article></div><div class="account-filters"><label>Desde<input type="date"></label><label>Hasta<input type="date"></label><label>Tipo<select><option>Todos</option><option>Facturas</option><option>Pagos</option></select></label><button class="wine-button">Filtrar</button></div><div class="account-table"><table><thead><tr><th>Emisión</th><th>Vencimiento</th><th>Tipo</th><th>Número</th><th>Haber PS</th><th>Saldo PS</th><th>Importe origen</th><th>Importe bruto moneda de origen</th><th>Forma de pago</th><th></th></tr></thead><tbody>@foreach($invoices as $invoice)<tr><td>{{ optional($invoice->issued_at)->format('d/m/Y') }}</td><td>{{ optional($invoice->due_at)->format('d/m/Y') }}</td><td>Factura {{ $invoice->type }}</td><td>{{ $invoice->number }}</td><td>—</td><td>${{ number_format($invoice->total,2,',','.') }}</td><td>${{ number_format($invoice->subtotal,2,',','.') }}</td><td>${{ number_format($invoice->total,2,',','.') }}</td><td>{{ $invoice->order->payment_method??'—' }}</td><td><a href="{{ route('client.invoices.download',$invoice) }}"><svg><use href="#i-download"/></svg></a></td></tr>@endforeach @foreach($paymentReports as $payment)<tr><td>{{ $payment->paid_at->format('d/m/Y') }}</td><td>—</td><td>Pago</td><td>#{{ $payment->id }}</td><td>${{ number_format($payment->amount,2,',','.') }}</td><td>—</td><td>${{ number_format($payment->amount,2,',','.') }}</td><td>${{ number_format($payment->amount,2,',','.') }}</td><td>{{ $payment->bank }}</td><td></td></tr>@endforeach</tbody></table></div>

@elseif($section==='facturas')
  <h1 class="private-title">Facturas</h1><section class="invoice-list">@forelse($invoices as $invoice)<article><span class="order-icon"><svg><use href="#i-file"/></svg></span><div><small>Fecha</small><span>{{ optional($invoice->issued_at)->format('d/m/Y') }}</span></div><div><small>N° de pedido</small><strong>{{ $invoice->order->number }}</strong></div><div><small>Importe</small><strong>${{ number_format($invoice->total,2,',','.') }}</strong></div><div><small>Factura</small><span>{{ $invoice->type }} {{ $invoice->number }}</span></div><div><small>Estado</small><b class="status status-delivered">{{ ucfirst($invoice->status) }}</b></div><a href="{{ route('client.invoices.download',$invoice) }}" aria-label="Descargar factura"><svg><use href="#i-download"/></svg></a></article>@empty<div class="private-empty">Tus facturas aparecerán aquí una vez emitidas.</div>@endforelse</section>
@endif
</main>

@include('partials.site-footer', ['privateMode' => true])

<svg class="svg-sprite" aria-hidden="true"><symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol><symbol id="i-list" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></symbol><symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></symbol><symbol id="i-cart" viewBox="0 0 24 24"><circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6"/></symbol><symbol id="i-box" viewBox="0 0 24 24"><path d="m21 8-9 5-9-5 9-5 9 5Z"/><path d="m3 8 9 5v9l9-5V8M12 13v9"/></symbol><symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></symbol><symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol><symbol id="i-copy" viewBox="0 0 24 24"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></symbol><symbol id="i-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></symbol></svg>
<script>
(()=>{
  const money=value=>new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS',minimumFractionDigits:2}).format(value).replace('ARS','$');
  const normalize=value=>String(value??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim().replace(/\s+/g,' ');
  const toast=document.querySelector('[data-private-toast]');
  if(toast){requestAnimationFrame(()=>toast.classList.add('show'));const close=()=>toast.remove();toast.querySelector('button').onclick=close;setTimeout(close,5200)}
  const profile=document.querySelector('[data-private-profile]'),profileToggle=profile?.querySelector('[data-private-profile-toggle]'),profileMenu=profile?.querySelector('[data-private-profile-menu]');
  const closeProfile=()=>{if(!profile)return;profile.classList.remove('is-open');profileToggle.setAttribute('aria-expanded','false');setTimeout(()=>{if(!profile.classList.contains('is-open'))profileMenu.hidden=true},160)};
  profileToggle?.addEventListener('click',event=>{event.stopPropagation();const opening=!profile.classList.contains('is-open');if(opening){profileMenu.hidden=false;requestAnimationFrame(()=>profile.classList.add('is-open'));profileToggle.setAttribute('aria-expanded','true')}else closeProfile()});
  document.addEventListener('click',event=>{if(profile&&!profile.contains(event.target))closeProfile()});
  const exitLayer=document.querySelector('[data-client-exit]'),exitDestination=exitLayer?.querySelector('[data-client-destination]');
  const openExit=destination=>{if(!exitLayer)return;if(exitDestination)exitDestination.value=destination||'/';exitLayer.hidden=false;document.body.classList.add('modal-open');requestAnimationFrame(()=>exitLayer.classList.add('is-open'));setTimeout(()=>exitLayer.querySelector('[data-client-stay]:not(.client-exit-backdrop)')?.focus(),120)};
  const closeExit=()=>{if(!exitLayer)return;exitLayer.classList.remove('is-open');document.body.classList.remove('modal-open');setTimeout(()=>exitLayer.hidden=true,180)};
  document.addEventListener('click',event=>{const target=event.target.closest('[data-public-destination]');if(!target)return;event.preventDefault();openExit(target.dataset.publicDestination||target.getAttribute('href')||'/')});
  exitLayer?.querySelectorAll('[data-client-stay]').forEach(button=>button.addEventListener('click',closeExit));
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeProfile();if(exitLayer&&!exitLayer.hidden)closeExit()}});
  @if(session()->has('client_public_destination')) openExit(@json(session('client_public_destination'))); @endif
  document.querySelectorAll('[data-step]').forEach(btn=>btn.onclick=()=>{const f=btn.closest('form'),i=f.querySelector('input[name=quantity]');i.value=Math.max(0,(+i.value||0)+(+btn.dataset.step));f.submit()});
  document.querySelectorAll('[data-cart-presentation]').forEach(select=>select.addEventListener('change',()=>select.form.requestSubmit()));
  document.querySelectorAll('[data-quantity-step]').forEach(btn=>btn.onclick=()=>{const input=btn.closest('.product-quantity-control').querySelector('input');input.value=Math.min(9999,Math.max(0,(+input.value||0)+(+btn.dataset.quantityStep)));input.dispatchEvent(new Event('input',{bubbles:true}))});
  document.querySelectorAll('[data-copy]').forEach(btn=>btn.onclick=async()=>{await navigator.clipboard.writeText(btn.dataset.copy);btn.classList.add('copied');setTimeout(()=>btn.classList.remove('copied'),1200)});
  document.querySelectorAll('.private-product').forEach(card=>{const form=card.querySelector('.product-add'),presentation=form?.querySelector('[name=presentation_index]'),quantity=form?.querySelector('[name=quantity]');if(!form)return;const update=()=>{const option=presentation?.selectedOptions[0],price=+(option?.dataset.price||0),qty=+(quantity?.value||0);card.querySelector('[data-product-code]').textContent=option?.dataset.code||'—';card.querySelector('[data-unit-price]')&&(card.querySelector('[data-unit-price]').textContent=money(price));card.querySelector('[data-row-subtotal]')&&(card.querySelector('[data-row-subtotal]').textContent=money(price*qty))};presentation?.addEventListener('change',update);quantity?.addEventListener('input',update);form.addEventListener('submit',()=>{if(+quantity.value<1)quantity.value=1})});
  const sf=document.querySelector('[data-product-search]');
  if(sf){
    const cards=[...document.querySelectorAll('.private-product')],chip=document.querySelector('[data-filter-chip]'),count=document.querySelector('[data-search-count]'),empty=document.querySelector('[data-search-empty]'),category=sf.elements.category,subcategory=sf.elements.subcategory;
    const categories=new Map(),subcategories=new Map();
    cards.forEach(card=>{if(card.dataset.category)categories.set(card.dataset.category,card.dataset.category);if(card.dataset.subcategory)subcategories.set(card.dataset.subcategory,card.dataset.subcategory)});
    [...subcategories.values()].sort((a,b)=>a.localeCompare(b,'es')).forEach(value=>subcategory.add(new Option(value.charAt(0).toLocaleUpperCase('es')+value.slice(1),value)));
    const filter=()=>{
      const q=normalize(sf.elements.q.value),cat=normalize(category.value),sub=normalize(subcategory.value),code=normalize(sf.elements.code.value);
      let visible=0;
      const includesTerms=(haystack,query)=>!query||query.split(' ').every(term=>haystack.includes(term));
      cards.forEach(card=>{const matches=includesTerms(card.dataset.search,q)&&(!cat||card.dataset.category===cat)&&(!sub||card.dataset.subcategory===sub)&&includesTerms(card.dataset.search,code);card.hidden=!matches;if(matches)visible++});
      const active=[sf.elements.q.value,category.selectedOptions[0]?.text&&category.value?category.selectedOptions[0].text:'',subcategory.selectedOptions[0]?.text&&subcategory.value?subcategory.selectedOptions[0].text:'',sf.elements.code.value].map(value=>String(value).trim()).filter(Boolean);
      chip.hidden=!active.length;chip.querySelector('em').textContent=active.join(' · ');
      count.textContent=active.length?`${visible} ${visible===1?'resultado':'resultados'}`:'';
      if(empty)empty.hidden=visible!==0;
    };
    let timer;
    const schedule=()=>{clearTimeout(timer);timer=setTimeout(filter,100)};
    sf.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);filter()});
    sf.elements.q.addEventListener('input',schedule);sf.elements.code.addEventListener('input',schedule);category.addEventListener('change',filter);subcategory.addEventListener('change',filter);
    chip.querySelector('button').onclick=()=>{sf.reset();filter();sf.elements.q.focus()};
    document.querySelectorAll('[data-view]').forEach(btn=>btn.onclick=()=>{document.querySelector('[data-products]').classList.toggle('grid',btn.dataset.view==='grid');document.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('active',b===btn))});
  }
  document.querySelectorAll('input[type=file]').forEach(input=>input.addEventListener('change',()=>{const file=input.files[0];if(!file)return;const generic=input.closest('.file-drop')?.querySelector('strong'),cart=input.closest('.cart-attachment')?.querySelector('[data-cart-file-name]');if(generic)generic.textContent=file.name;if(cart)cart.textContent=file.name}));
})();
</script>
</body></html>
