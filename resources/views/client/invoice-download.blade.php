<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura {{ $invoice->number }}</title>
  <style>
    @page { margin: 34px 40px 48px; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #27272a; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    .header { width: 100%; padding-bottom: 20px; border-bottom: 3px solid #5d1c38; }
    .header td { width: 50%; vertical-align: top; }
    .logo { width: 132px; }
    .issuer { padding-top: 9px; color: #52525c; line-height: 1.55; }
    .document { text-align: right; }
    .document-label { margin: 0 0 4px; color: #5d1c38; font-size: 25px; font-weight: 700; }
    .document-number { margin: 0; font-size: 14px; font-weight: 700; }
    .document-date { margin-top: 7px; color: #71717a; }
    .meta { width: 100%; margin-top: 22px; border-spacing: 0; border: 1px solid #dedfe0; border-radius: 8px; background: #fcfafb; }
    .meta td { width: 50%; padding: 14px 16px; vertical-align: top; line-height: 1.55; }
    .meta td + td { border-left: 1px solid #dedfe0; }
    .eyebrow { margin-bottom: 4px; color: #71717a; font-size: 8px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
    .meta strong { font-size: 13px; }
    .items { width: 100%; margin-top: 24px; border-collapse: collapse; table-layout: fixed; }
    .items th { padding: 9px 8px; background: #5d1c38; color: #fff; font-size: 9px; text-align: left; text-transform: uppercase; }
    .items th:first-child { border-radius: 7px 0 0 0; }
    .items th:last-child { border-radius: 0 7px 0 0; text-align: right; }
    .items td { padding: 11px 8px; border-bottom: 1px solid #e4e4e7; vertical-align: top; word-wrap: break-word; }
    .items .number { text-align: right; white-space: nowrap; }
    .items small { display: block; margin-top: 3px; color: #71717a; }
    .summary { width: 270px; margin: 22px 0 0 auto; border-collapse: collapse; }
    .summary td { padding: 5px 0; }
    .summary td:last-child { text-align: right; font-weight: 700; }
    .summary .total td { padding-top: 11px; border-top: 2px solid #5d1c38; color: #5d1c38; font-size: 16px; }
    .note { margin-top: 34px; padding: 13px 15px; border-radius: 7px; background: #fff3f8; color: #5d1c38; line-height: 1.55; }
    .footer { position: fixed; right: 0; bottom: -28px; left: 0; color: #71717a; font-size: 8px; text-align: center; }
    .page-number:after { content: counter(page); }
  </style>
</head>
<body>
  <table class="header">
    <tr>
      <td>
        <img class="logo" src="{{ public_path('assets/figma/exact/logo-header.png') }}" alt="Moldpack">
        <div class="issuer">Dante Alighieri 1377, Don Torcuato<br>Buenos Aires, Argentina<br>ventas@moldpack.com.ar</div>
      </td>
      <td class="document">
        <h1 class="document-label">Factura {{ $invoice->type }}</h1>
        <p class="document-number">N.º {{ $invoice->number }}</p>
        <p class="document-date">Fecha: {{ optional($invoice->issued_at)->format('d/m/Y') ?: '—' }}</p>
      </td>
    </tr>
  </table>

  <table class="meta">
    <tr>
      <td>
        <div class="eyebrow">Cliente</div>
        <strong>{{ $invoice->order->cliente->business_name ?: $invoice->order->cliente->name }}</strong><br>
        CUIT: {{ $invoice->order->cliente->tax_id ?: '—' }}<br>
        {{ $invoice->order->cliente->billing_address ?: 'Domicilio no informado' }}
      </td>
      <td>
        <div class="eyebrow">Pedido asociado</div>
        <strong>{{ $invoice->order->number }}</strong><br>
        Forma de pago: {{ ucfirst($invoice->order->payment_method ?: 'no informada') }}<br>
        Estado: {{ ucfirst($invoice->status) }}
      </td>
    </tr>
  </table>

  <table class="items">
    <colgroup><col style="width:11%"><col style="width:41%"><col style="width:10%"><col style="width:19%"><col style="width:19%"></colgroup>
    <thead><tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
    <tbody>
      @foreach($invoice->order->items as $item)
        <tr>
          <td>{{ $item->sku ?: '—' }}</td>
          <td><strong>{{ $item->name }}</strong>@if($item->presentation)<small>{{ $item->presentation }}</small>@endif</td>
          <td class="number">{{ $item->quantity }}</td>
          <td class="number">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
          <td class="number">${{ number_format($item->line_total, 2, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="summary">
    <tr><td>Subtotal</td><td>${{ number_format($invoice->subtotal, 2, ',', '.') }}</td></tr>
    @if((float) $invoice->order->discount_total > 0)<tr><td>Descuento</td><td>-${{ number_format($invoice->order->discount_total, 2, ',', '.') }}</td></tr>@endif
    <tr><td>IVA</td><td>${{ number_format($invoice->tax_total, 2, ',', '.') }}</td></tr>
    <tr class="total"><td>Total</td><td>${{ number_format($invoice->total, 2, ',', '.') }}</td></tr>
  </table>

  <div class="note">Este documento fue generado desde la zona privada de Moldpack y corresponde al comprobante registrado para el pedido indicado.</div>
  <div class="footer">Moldpack · Comprobante {{ $invoice->number }} · Página <span class="page-number"></span></div>
</body>
</html>
