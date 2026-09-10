<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Acceso administrativo | Moldpack</title>
    <meta name="theme-color" content="#691b3d">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon/favicon-96x96.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon.png') }}">
    @vite('resources/css/login.css')
</head>
<body>
<main class="login-layout">
    <section class="login-form-panel" aria-labelledby="login-title">
        <div class="login-form-wrap">
            <a class="login-brand" href="/" aria-label="Volver al sitio de Moldpack">
                <img src="{{ asset('assets/figma/exact/logo-header.png') }}" alt="Moldpack">
            </a>

            <div class="login-heading">
                <p class="login-context">Panel de administración</p>
                <h1 id="login-title">Bienvenido de nuevo</h1>
                <p>Ingresá con tu cuenta autorizada para gestionar el contenido del sitio.</p>
            </div>

            <form method="post" action="{{ route('admin.login.store') }}" class="login-form" novalidate>
                @csrf

                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        inputmode="email"
                        maxlength="254"
                        required
                        autofocus
                        aria-describedby="@error('email') email-error @enderror"
                        @error('email') aria-invalid="true" @enderror
                    >
                    @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        maxlength="1024"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Mantener mi sesión en este dispositivo</span>
                </label>

                <button type="submit">Ingresar al panel</button>
            </form>

            <div class="security-note">
                <strong>Acceso restringido</strong>
                <span>Los intentos de acceso están limitados y protegidos.</span>
            </div>

            <a class="back-link" href="/">Volver al sitio público</a>
        </div>
    </section>

    <aside class="login-visual" aria-hidden="true">
        <img src="{{ asset('assets/figma/exact/category-1.png') }}" alt="">
        <div class="login-visual-shade"></div>
        <div class="login-visual-copy">
            <span>Moldpack CMS</span>
            <p>Contenido, productos y novedades en un solo lugar.</p>
        </div>
    </aside>
</main>
</body>
</html>
