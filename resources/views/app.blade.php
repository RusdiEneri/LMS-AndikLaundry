<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Andik Laundry') }}</title>

        <!-- Favicon / Brand Icon -->
        <link rel="icon" type="image/webp" href="{{ asset('images/logo-a.webp') }}?v=1.2">
        <link rel="icon" type="image/png" href="{{ asset('images/logo-a.png') }}?v=1.2">
        <link rel="shortcut icon" href="{{ asset('images/logo-a.webp') }}?v=1.2">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-a.png') }}?v=1.2">

        <!-- Fonts: Lexend (Heading), Poppins (Body), JetBrains Mono — DESIGN.md Section 10.3 -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@600;700;800&family=Poppins:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

        <!-- Scripts and Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-body antialiased bg-bg text-text h-full overflow-x-hidden">
        @inertia
    </body>
</html>
