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
  @if($section==='pedidos') @vite('resources/css/client-orders.css') @endif
  @if($section==='pagos') @vite('resources/css/client-payments.css') @endif
  @if($section==='cuenta') @vite('resources/css/client-account.css') @endif
  @if($section==='facturas') @vite('resources/css/client-invoices.css') @endif
</head>
<body class="private-site {{ $section==='productos'?'private-products-page':'' }} {{ $section==='carrito'?'private-cart-page':'' }} {{ $section==='pedidos'?'private-orders-page':'' }} {{ $section==='pagos'?'private-payments-page':'' }} {{ $section==='cuenta'?'private-account-page':'' }} {{ $section==='facturas'?'private-invoices-page':'' }}">
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
  <div class="orders-breadcrumb"><a href="{{ route('client.portal','productos') }}">Inicio</a><span> &gt; Carrito</span></div>
  <div class="orders-head" aria-hidden="true"><b></b><b>Pedido</b><b>Fecha</b><b>Items</b><b>Estado</b><b>Importe</b><b></b></div>
  <section class="orders-list">
    @forelse($orders as $order)
      @php $invoice=$order->invoices->firstWhere('status','issued'); @endphp
      <details class="figma-order" {{ $loop->first?'open':'' }}>
        <summary>
          <span class="figma-order-icon"><img src="{{ asset('assets/figma/private/orders-clipboard.svg') }}" alt=""></span>
          <span class="figma-order-number">{{ $order->number }}</span>
          <span class="figma-order-date">{{ $order->created_at->format('d/m/Y') }}</span>
          <span class="figma-order-items">{{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity')===1?'item':'items' }}</span>
          <span class="figma-order-status status-{{ $order->status }}">{{ $status[$order->status]??$order->status }}</span>
          <strong class="figma-order-amount">${{ number_format($order->total,2,',','.') }}</strong>
          <img class="figma-order-chevron" src="{{ asset('assets/figma/private/orders-chevron.svg') }}" alt="">
        </summary>
        <div class="figma-order-detail">
          <h2>Detalle del pedido</h2>
          <div class="figma-order-lines">
            @foreach($order->items as $item)
              <p><span><strong>{{ $item->sku ?: '—' }}</strong> {{ $item->name }} <small>x {{ $item->quantity }}</small></span><b>${{ number_format($item->line_total,2,',','.') }}</b></p>
            @endforeach
          </div>
          <p class="figma-order-total"><span>Total con IVA:</span><strong>${{ number_format($order->total,2,',','.') }}</strong></p>
          <div class="figma-order-actions">
            @if($invoice)
              <a class="figma-invoice-download" href="{{ route('client.invoices.download',$invoice) }}"><span>Descargar factura</span><i><img src="{{ asset('assets/figma/private/orders-download.svg') }}" alt=""></i></a>
            @else
              <span class="figma-invoice-pending">Factura pendiente</span>
            @endif
            <form method="post" action="{{ route('client.orders.reorder',$order) }}">@csrf<button type="submit">Recomprar</button></form>
          </div>
        </div>
      </details>
    @empty
      <div class="orders-empty"><strong>Todavía no realizaste pedidos.</strong><a href="{{ route('client.portal','productos') }}">Ver productos</a></div>
    @endforelse
  </section>

@elseif($section==='pagos')
  <div class="payments-breadcrumb"><a href="{{ route('client.portal','productos') }}">Inicio</a><span> &gt; Info de pagos</span></div>
  <div class="payments-layout">
    <section class="transfer-data" aria-labelledby="transfer-title">
      <h1 id="transfer-title">Datos de transferencia</h1>
      <div class="transfer-rule"></div>
      @foreach([['Titular',$settings['bank_holder']??'Moldpack'],['Banco',$settings['bank_name']??'Banco Nación'],['CBU',$settings['bank_cbu']??''],['Alias',$settings['bank_alias']??''],['CUIT',$settings['bank_tax_id']??'']] as [$label,$value])
        <div class="transfer-row">
          <p><span>{{ $label }}:</span> <strong>{{ $value ?: '—' }}</strong></p>
          <button type="button" data-copy="{{ $value }}" aria-label="Copiar {{ Str::lower($label) }}"><img src="{{ asset('assets/figma/private/payment-copy.svg') }}" alt=""></button>
        </div>
      @endforeach
    </section>

    <form class="figma-payment-form" method="post" action="{{ route('client.payments.store') }}" enctype="multipart/form-data" data-payment-form>@csrf
      <h1>Informar pago</h1>
      <div class="payment-title-rule"></div>
      <div class="payment-fields-row">
        <div class="payment-date-field">
          <label for="payment-paid-at">Fecha*</label>
          <div class="payment-date-picker" data-date-picker>
            <input id="payment-paid-at" type="text" name="paid_at" value="{{ old('paid_at') }}" placeholder="dd/mm/aaaa" autocomplete="off" pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/\d{4}" readonly required aria-haspopup="dialog" aria-expanded="false" data-payment-date>
            <button class="payment-calendar-trigger" type="button" aria-label="Abrir calendario" aria-controls="payment-calendar" aria-expanded="false" data-calendar-trigger><img src="{{ asset('assets/figma/private/payment-calendar.svg') }}" alt=""></button>
            <div id="payment-calendar" class="payment-calendar" role="dialog" aria-label="Seleccionar fecha de pago" data-payment-calendar hidden>
              <div class="payment-calendar-header">
                <button type="button" aria-label="Mes anterior" data-calendar-prev><span aria-hidden="true">‹</span></button>
                <strong data-calendar-title></strong>
                <button type="button" aria-label="Mes siguiente" data-calendar-next><span aria-hidden="true">›</span></button>
              </div>
              <div class="payment-calendar-weekdays" aria-hidden="true"><span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
              <div class="payment-calendar-grid" role="grid" data-calendar-grid></div>
              <div class="payment-calendar-footer"><button type="button" data-calendar-today>Hoy</button></div>
            </div>
          </div>
        </div>
        <label>Importe*<input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" placeholder="Sólo números" required data-payment-amount></label>
      </div>
      <div class="payment-fields-row second-row">
        <label>Banco emisor*<input name="bank" value="{{ old('bank') }}" placeholder="Nombre del Banco" required></label>
        <label>Sucursal*<input name="branch" value="{{ old('branch') }}" placeholder="Nº de sucursal" required></label>
      </div>

      <fieldset class="pending-invoices">
        <legend>Aplicar a facturas pendientes&nbsp;(desde cuenta corriente)*</legend>
        <div class="pending-invoices-box">
          @forelse($pendingInvoices as $invoice)
            <label class="pending-invoice">
              <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-outstanding="{{ number_format($invoiceOutstanding->get($invoice->id,0),2,'.','') }}" {{ in_array($invoice->id,old('invoice_ids',[]))?'checked':'' }}>
              <span class="payment-checkbox" aria-hidden="true"></span>
              <span class="pending-invoice-name"><strong>{{ $invoice->number }}</strong> - Vence el {{ optional($invoice->due_at)->format('d/m/Y') ?: 'sin fecha' }}</span>
              <strong class="pending-invoice-amount">${{ number_format($invoiceOutstanding->get($invoice->id,0),2,',','.') }}</strong>
            </label>
          @empty
            <p class="pending-invoices-empty">No tenés facturas pendientes para aplicar.</p>
          @endforelse
        </div>
      </fieldset>

      <label class="payment-observations">Observaciones / Aclaraciones<textarea name="observations" placeholder="Dejanos una observación">{{ old('observations') }}</textarea></label>

      <div class="payment-bottom-row">
        <div class="payment-receipt"><label for="payment-receipt">Adjuntar comprobante*</label><label class="payment-file-control" for="payment-receipt"><span data-payment-file-name>Seleccionar archivo</span><input id="payment-receipt" type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" required><i><img src="{{ asset('assets/figma/private/payment-upload.svg') }}" alt=""></i></label></div>
        <button class="payment-submit" type="submit" {{ $pendingInvoices->isEmpty()?'disabled':'' }}>Informar pago</button>
      </div>
      <p class="payment-required-note">*Campos obligatorios - El pago queda pendiente de validación hasta ser conciliado</p>
    </form>
  </div>

@elseif($section==='cuenta')
  @php
    $accountDates=$invoices->pluck('issued_at')->merge($paymentReports->pluck('paid_at'))->filter()->map(fn($date)=>$date->format('Y-m-d'))->unique()->sortDesc()->values();
    $paymentMethods=['cash'=>'Efectivo','transfer'=>'Transferencia','check'=>'Cheque','card'=>'Tarjeta'];
  @endphp
  <div class="account-breadcrumb"><a href="{{ route('client.portal','productos') }}">Inicio</a><span> &gt; Estado de cuenta</span></div>
  <div class="account-payment-action"><a href="{{ route('client.portal','pagos') }}">Informar un pago</a></div>
  <section class="account-summary" aria-label="Resumen de cuenta">
    <article class="overdue"><small>Saldo vencido</small><strong>${{ number_format($overdueBalance,2,',','.') }}</strong></article>
    <article><small>Saldo actual</small><strong>{{ number_format($balance,2,',','.') }}</strong></article>
  </section>
  <div class="account-divider"></div>
  <section class="account-search" data-account-filters>
    <h1>Buscar por</h1>
    <div class="account-filter-fields">
      <label><span class="sr-only">Fecha</span><select data-account-date><option value="">Fecha</option>@foreach($accountDates as $date)<option value="{{ $date }}">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</option>@endforeach</select></label>
      <label><span class="sr-only">Tipo</span><select data-account-type><option value="">Buscar por tipo</option><option value="factura">Factura</option><option value="pago">Pago</option></select></label>
      <label><span class="sr-only">Movimiento</span><select data-account-movement><option value="">Buscar por movimientos</option>@foreach($invoices as $invoice)<option value="invoice-{{ $invoice->id }}">{{ $invoice->number }}</option>@endforeach @foreach($paymentReports as $payment)<option value="payment-{{ $payment->id }}">Pago #{{ $payment->id }}</option>@endforeach</select></label>
    </div>
  </section>
  <section class="account-ledger" aria-label="Movimientos de la cuenta">
    <div class="account-ledger-head" aria-hidden="true"><b>Emisión</b><b>Vencimiento</b><b>Tipo</b><b>Número</b><b>Haber PS</b><b>Saldo PS</b><b>Importe origen</b><b>Importe Bruto<br>moneda de origen</b><b>Forma de<br>pago</b><b></b></div>
    <div class="account-movements" data-account-movements>
      @foreach($invoices as $invoice)
        @php $expired=$invoice->due_at && $invoice->due_at->isPast(); $compactInvoice='FC - '.Str::substr(Str::afterLast($invoice->number,'-'),-4); @endphp
        <article class="account-movement {{ $expired?'is-overdue':'' }}" data-account-row data-date="{{ optional($invoice->issued_at)->format('Y-m-d') }}" data-type="factura" data-movement="invoice-{{ $invoice->id }}">
          <span>{{ optional($invoice->issued_at)->format('d/m/Y') ?: '—' }}</span><span>{{ optional($invoice->due_at)->format('d/m/Y') ?: '—' }}</span><span>FC {{ $invoice->type }}</span><span title="{{ $invoice->number }}">{{ $compactInvoice }}</span><strong>${{ number_format($invoice->total,2,',','.') }}</strong><strong>${{ number_format($invoiceOutstanding->get($invoice->id,0),2,',','.') }}</strong><strong>${{ number_format($invoice->subtotal,2,',','.') }}</strong><strong>${{ number_format($invoice->total,2,',','.') }}</strong><span>{{ $paymentMethods[$invoice->order->payment_method??'']??($invoice->order->payment_method??'—') }}</span><a href="{{ route('client.invoices.download',$invoice) }}" aria-label="Descargar {{ $invoice->number }}"><img src="{{ asset('assets/figma/private/account-download.svg') }}" alt=""></a>
        </article>
      @endforeach
      @foreach($paymentReports as $payment)
        <article class="account-movement" data-account-row data-date="{{ $payment->paid_at->format('Y-m-d') }}" data-type="pago" data-movement="payment-{{ $payment->id }}">
          <span>{{ $payment->paid_at->format('d/m/Y') }}</span><span>—</span><span>RCC</span><span>PAG - {{ str_pad((string)$payment->id,4,'0',STR_PAD_LEFT) }}</span><strong>${{ number_format($payment->amount,2,',','.') }}</strong><strong>${{ number_format($balance,2,',','.') }}</strong><strong>${{ number_format($payment->amount,2,',','.') }}</strong><strong>${{ number_format($payment->amount,2,',','.') }}</strong><span>Transferencia</span><a href="{{ route('client.payments.receipt',$payment) }}" aria-label="Descargar comprobante de pago #{{ $payment->id }}"><img src="{{ asset('assets/figma/private/account-download.svg') }}" alt=""></a>
        </article>
      @endforeach
      <div class="account-empty" data-account-empty {{ $invoices->isNotEmpty()||$paymentReports->isNotEmpty()?'hidden':'' }}>No hay movimientos para mostrar.</div>
    </div>
  </section>

@elseif($section==='facturas')
  <div class="invoices-breadcrumb"><a href="{{ route('client.portal','productos') }}">Inicio</a><span> &gt; Info de pagos</span></div>
  <div class="invoices-head" aria-hidden="true"><b></b><b>Fecha</b><b>Nº de pedido</b><b>Importe</b><b>Factura</b><b>Estado</b><b></b></div>
  <section class="figma-invoices" aria-label="Facturas del cliente">
    @forelse($invoices as $invoice)
      @php
        $invoiceValidated=$invoice->status==='issued';
        $invoiceLabel='FC - '.Str::substr(Str::afterLast($invoice->number,'-'),-4);
      @endphp
      <article class="figma-invoice-row">
        <span class="figma-invoice-icon"><img src="{{ asset('assets/figma/private/invoices-receipt.svg') }}" alt=""></span>
        <span class="figma-invoice-date">{{ optional($invoice->issued_at)->format('d/m/Y') ?: '—' }}</span>
        <span class="figma-invoice-order">{{ $invoice->order->number }}</span>
        <strong class="figma-invoice-amount">${{ number_format($invoice->total,2,',','.') }}</strong>
        <span class="figma-invoice-number" title="{{ $invoice->number }}">{{ $invoiceLabel }}</span>
        <span class="figma-invoice-status {{ $invoiceValidated?'is-validated':'is-pending' }}">{{ $invoiceValidated?'Validado':'Pendiente de validación' }}</span>
        <a class="figma-invoice-download-button" href="{{ route('client.invoices.download',$invoice) }}" aria-label="Descargar factura {{ $invoice->number }}"><img src="{{ asset('assets/figma/private/invoices-download.svg') }}" alt=""></a>
      </article>
    @empty
      <div class="figma-invoices-empty"><strong>Todavía no tenés facturas.</strong><span>Aparecerán aquí cuando sean emitidas.</span></div>
    @endforelse
  </section>
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
  const privateMenu=document.querySelector('[data-private-menu]'),privateMenuToggle=document.querySelector('[data-private-menu-toggle]'),privateMenuBackdrop=document.querySelector('[data-private-menu-close]');
  const closePrivateMenu=()=>{if(!privateMenu||!privateMenuToggle||!privateMenuBackdrop)return;privateMenu.classList.remove('is-open');privateMenuBackdrop.classList.remove('is-open');privateMenuToggle.setAttribute('aria-expanded','false');privateMenuToggle.setAttribute('aria-label','Abrir menú de zona privada');document.body.classList.remove('private-menu-open');setTimeout(()=>{if(!privateMenu.classList.contains('is-open'))privateMenuBackdrop.hidden=true},220)};
  const openPrivateMenu=()=>{if(!privateMenu||!privateMenuToggle||!privateMenuBackdrop)return;closeProfile();privateMenuBackdrop.hidden=false;document.body.classList.add('private-menu-open');requestAnimationFrame(()=>{privateMenu.classList.add('is-open');privateMenuBackdrop.classList.add('is-open')});privateMenuToggle.setAttribute('aria-expanded','true');privateMenuToggle.setAttribute('aria-label','Cerrar menú de zona privada')};
  privateMenuToggle?.addEventListener('click',()=>privateMenu.classList.contains('is-open')?closePrivateMenu():openPrivateMenu());
  privateMenuBackdrop?.addEventListener('click',closePrivateMenu);
  privateMenu?.querySelectorAll('a').forEach(link=>link.addEventListener('click',closePrivateMenu));
  const exitLayer=document.querySelector('[data-client-exit]'),exitDestination=exitLayer?.querySelector('[data-client-destination]');
  const openExit=destination=>{if(!exitLayer)return;if(exitDestination)exitDestination.value=destination||'/';exitLayer.hidden=false;document.body.classList.add('modal-open');requestAnimationFrame(()=>exitLayer.classList.add('is-open'));setTimeout(()=>exitLayer.querySelector('[data-client-stay]:not(.client-exit-backdrop)')?.focus(),120)};
  const closeExit=()=>{if(!exitLayer)return;exitLayer.classList.remove('is-open');document.body.classList.remove('modal-open');setTimeout(()=>exitLayer.hidden=true,180)};
  document.addEventListener('click',event=>{const target=event.target.closest('[data-public-destination]');if(!target)return;event.preventDefault();openExit(target.dataset.publicDestination||target.getAttribute('href')||'/')});
  exitLayer?.querySelectorAll('[data-client-stay]').forEach(button=>button.addEventListener('click',closeExit));
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeProfile();closePrivateMenu();if(exitLayer&&!exitLayer.hidden)closeExit()}});
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
  document.querySelectorAll('input[type=file]').forEach(input=>input.addEventListener('change',()=>{const file=input.files[0];if(!file)return;const generic=input.closest('.file-drop')?.querySelector('strong'),cart=input.closest('.cart-attachment')?.querySelector('[data-cart-file-name]'),payment=input.closest('.payment-file-control')?.querySelector('[data-payment-file-name]');if(generic)generic.textContent=file.name;if(cart)cart.textContent=file.name;if(payment)payment.textContent=file.name}));
  const paymentForm=document.querySelector('[data-payment-form]');
  if(paymentForm){const amount=paymentForm.querySelector('[data-payment-amount]'),checks=[...paymentForm.querySelectorAll('[data-outstanding]')];checks.forEach(check=>check.addEventListener('change',()=>{const selected=checks.filter(item=>item.checked).reduce((total,item)=>total+(Number(item.dataset.outstanding)||0),0);amount.value=selected?selected.toFixed(2):''}))}
  const accountFilters=document.querySelector('[data-account-filters]');
  if(accountFilters){
    const date=accountFilters.querySelector('[data-account-date]'),type=accountFilters.querySelector('[data-account-type]'),movement=accountFilters.querySelector('[data-account-movement]'),rows=[...document.querySelectorAll('[data-account-row]')],empty=document.querySelector('[data-account-empty]');
    const filterAccount=()=>{let visible=0;rows.forEach(row=>{const matches=(!date.value||row.dataset.date===date.value)&&(!type.value||row.dataset.type===type.value)&&(!movement.value||row.dataset.movement===movement.value);row.hidden=!matches;if(matches)visible++});empty.hidden=visible!==0};
    [date,type,movement].forEach(select=>select.addEventListener('change',filterAccount));
  }
  const datePicker=document.querySelector('[data-date-picker]');
  if(datePicker){
    const input=datePicker.querySelector('[data-payment-date]'),trigger=datePicker.querySelector('[data-calendar-trigger]'),calendar=datePicker.querySelector('[data-payment-calendar]'),title=datePicker.querySelector('[data-calendar-title]'),grid=datePicker.querySelector('[data-calendar-grid]'),todayButton=datePicker.querySelector('[data-calendar-today]');
    const today=new Date();today.setHours(0,0,0,0);
    const parseDate=value=>{const match=String(value||'').trim().match(/^(?:(\d{2})\/(\d{2})\/(\d{4})|(\d{4})-(\d{2})-(\d{2}))$/);if(!match)return null;const date=match[1]?new Date(+match[3],+match[2]-1,+match[1]):new Date(+match[4],+match[5]-1,+match[6]);return Number.isNaN(date.getTime())?null:date};
    const sameDay=(a,b)=>a&&b&&a.getFullYear()===b.getFullYear()&&a.getMonth()===b.getMonth()&&a.getDate()===b.getDate();
    const formatDate=date=>`${String(date.getDate()).padStart(2,'0')}/${String(date.getMonth()+1).padStart(2,'0')}/${date.getFullYear()}`;
    let selected=parseDate(input.value),view=selected?new Date(selected):new Date(today.getFullYear(),today.getMonth(),1),closeTimer;
    if(selected)input.value=formatDate(selected);
    const renderCalendar=()=>{
      title.textContent=new Intl.DateTimeFormat('es-AR',{month:'long',year:'numeric'}).format(view).replace(/^./,letter=>letter.toUpperCase());
      grid.replaceChildren();
      const first=new Date(view.getFullYear(),view.getMonth(),1),offset=(first.getDay()+6)%7,start=new Date(view.getFullYear(),view.getMonth(),1-offset);
      for(let index=0;index<42;index++){
        const date=new Date(start);date.setDate(start.getDate()+index);
        const button=document.createElement('button');button.type='button';button.textContent=date.getDate();button.dataset.calendarDate=`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;button.setAttribute('role','gridcell');button.setAttribute('aria-label',new Intl.DateTimeFormat('es-AR',{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(date));
        if(date.getMonth()!==view.getMonth())button.classList.add('is-outside');
        if(sameDay(date,today)){button.classList.add('is-today');button.setAttribute('aria-current','date')}
        if(sameDay(date,selected)){button.classList.add('is-selected');button.setAttribute('aria-selected','true')}
        if(date>today){button.disabled=true;button.setAttribute('aria-disabled','true')}
        grid.append(button);
      }
    };
    const closeCalendar=(restoreFocus=false)=>{clearTimeout(closeTimer);calendar.classList.remove('is-open');input.setAttribute('aria-expanded','false');trigger.setAttribute('aria-expanded','false');closeTimer=setTimeout(()=>calendar.hidden=true,150);if(restoreFocus)trigger.focus()};
    const openCalendar=()=>{clearTimeout(closeTimer);selected=parseDate(input.value);view=selected?new Date(selected.getFullYear(),selected.getMonth(),1):new Date(today.getFullYear(),today.getMonth(),1);renderCalendar();calendar.hidden=false;input.setAttribute('aria-expanded','true');trigger.setAttribute('aria-expanded','true');requestAnimationFrame(()=>{calendar.classList.add('is-open');(grid.querySelector('.is-selected:not(:disabled)')||grid.querySelector('.is-today:not(:disabled)')||grid.querySelector('button:not(:disabled)'))?.focus()})};
    const choose=date=>{selected=date;input.value=formatDate(date);input.dispatchEvent(new Event('change',{bubbles:true}));closeCalendar(true)};
    input.addEventListener('click',openCalendar);input.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '||event.key==='ArrowDown'){event.preventDefault();openCalendar()}});
    trigger.addEventListener('click',()=>calendar.hidden?openCalendar():closeCalendar(true));
    datePicker.querySelector('[data-calendar-prev]').addEventListener('click',()=>{view=new Date(view.getFullYear(),view.getMonth()-1,1);renderCalendar()});
    datePicker.querySelector('[data-calendar-next]').addEventListener('click',()=>{const next=new Date(view.getFullYear(),view.getMonth()+1,1);if(next<=new Date(today.getFullYear(),today.getMonth(),1)){view=next;renderCalendar()}});
    todayButton.addEventListener('click',()=>choose(new Date(today)));
    grid.addEventListener('click',event=>{const button=event.target.closest('[data-calendar-date]');if(!button||button.disabled)return;const [year,month,day]=button.dataset.calendarDate.split('-').map(Number);choose(new Date(year,month-1,day))});
    grid.addEventListener('keydown',event=>{const current=event.target.closest('[data-calendar-date]');if(!current)return;const movement={ArrowLeft:-1,ArrowRight:1,ArrowUp:-7,ArrowDown:7}[event.key];if(!movement)return;event.preventDefault();const buttons=[...grid.querySelectorAll('button:not(:disabled)')],next=buttons[Math.max(0,Math.min(buttons.length-1,buttons.indexOf(current)+movement))];next?.focus()});
    document.addEventListener('click',event=>{if(!calendar.hidden&&!datePicker.contains(event.target))closeCalendar()});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!calendar.hidden)closeCalendar(true)});
  }
  paymentForm?.addEventListener('submit',event=>{const date=event.currentTarget.querySelector('[data-payment-date]');if(!date)return;const match=date.value.trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);if(match)date.value=`${match[3]}-${match[2]}-${match[1]}`});
})();
</script>
</body></html>
