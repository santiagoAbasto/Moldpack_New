@extends('layouts.site')
@section('body_class', 'contact-route')

@section('content')
<section class="contact-page">
    <div class="shell contact-breadcrumb"><a href="/">Inicio</a><span> &gt; Contacto</span></div>

    <div class="shell contact-layout">
        <aside class="contact-intro">
            <p class="contact-lead">{{ $contact['intro'] ?? 'Para mayor información, no dude en contactarse mediante el siguiente formulario, o a través de nuestras vías de comunicación.' }}</p>
            <address class="contact-details">
                <p><img src="{{ asset('assets/figma/exact/map-pin.svg') }}" alt=""><a href="{{ $contact['maps_url'] ?? 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX' }}" target="_blank" rel="noopener noreferrer">{{ $contact['address'] ?? 'Dante Alighieri 1377, Don Torcuato.' }}<br>{{ $contact['city'] ?? 'Buenos Aires, Argentina.' }}</a></p>
                <p><img src="{{ asset('assets/figma/exact/phone.svg') }}" alt=""><a href="tel:{{ preg_replace('/[^0-9+]/', '', explode('/', $contact['phone'] ?? '4727-2836')[0]) }}">{{ $contact['phone'] ?? '4727-2836/2837' }}</a></p>
                <p><img src="{{ asset('assets/figma/exact/mail.svg') }}" alt=""><a href="mailto:{{ $contact['email'] ?? 'ventas@moldpack.com.ar' }}">{{ $contact['email'] ?? 'ventas@moldpack.com.ar' }}</a></p>
            </address>
        </aside>

        <form class="contact-form" action="{{ route('contact.inquiries.store') }}" method="post">
            @csrf
            <input class="contact-honeypot" name="website" type="text" tabindex="-1" autocomplete="off" aria-hidden="true">
            <input type="hidden" name="form_started" value="{{ Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">
            <label><span>Nombre y apellido *</span><input name="name" type="text" value="{{ old('name') }}" placeholder="Juan Perez" maxlength="120" required></label>
            <label><span>Email*</span><input name="email" type="email" value="{{ old('email') }}" placeholder="juanperez@mail.com" maxlength="254" required></label>
            <label><span>Teléfono*</span><input name="phone" type="tel" value="{{ old('phone') }}" placeholder="Código de área + 1234 5678" maxlength="60" required></label>
            <label><span>Empresa</span><input name="company" type="text" value="{{ old('company') }}" placeholder="Nombre de la empresa / Razón social" maxlength="160"></label>
            <label class="contact-message"><span>Mensaje *</span><textarea name="message" placeholder="Dejanos tu consulta" maxlength="3000" required>{{ old('message') }}</textarea></label>
            <div class="contact-actions"><span>*Campos obligatorios</span><button type="submit"><span class="button-label">Enviar mensaje</span><span class="submit-spinner" aria-hidden="true"></span></button></div>
        </form>
    </div>

    <div class="shell contact-map">
        <iframe src="{{ $contact['map_embed_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.7656349128115!2d-58.613389219219584!3d-34.48364573183678!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bca4cd16c9fcf7%3A0x46425f36c28d01be!2sMOLDPACK!5e0!3m2!1ses!2sbo!4v1788402231251!5m2!1ses!2sbo' }}" width="1224" height="484" style="border:0" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin" title="Ubicación de Moldpack"></iframe>
        <div class="map-brand-marker" aria-hidden="true"><span><img src="{{ asset('assets/figma/exact/map-pin.svg') }}" alt=""></span><b>Nuestra fábrica</b></div>
        <div class="map-location-card">
            <span class="map-location-symbol"><img src="{{ asset('assets/figma/exact/map-pin.svg') }}" alt=""></span>
            <span><small>Casa central · Don Torcuato</small><strong>{{ $contact['address'] ?? 'Dante Alighieri 1377, Don Torcuato.' }}</strong></span>
            <a href="{{ $contact['maps_url'] ?? 'https://maps.app.goo.gl/gVUD5k7wC3zZhwbX' }}" target="_blank" rel="noopener noreferrer">Cómo llegar <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>
@endsection
