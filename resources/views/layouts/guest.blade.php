<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Absensi') }} - Login</title>

        <link rel="preload" href="{{ Vite::asset('node_modules/@fontsource-variable/plus-jakarta-sans/files/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/guest.css', 'resources/js/guest.js'])
    </head>
    <body class="antialiased bg-pattern">
        <div class="min-h-screen flex flex-col justify-center items-center p-4 sm:p-6">
            <div class="w-full sm:max-w-[450px]">
                {{ $slot }}
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-400 font-medium">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Sistem Absensi QR Code') }}. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>
