<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Moldpack CMS</title>
    <meta name="theme-color" content="#691b3d">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon/favicon-96x96.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon.png') }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
