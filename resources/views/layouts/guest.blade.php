<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Absensi') }} - Login</title>

        <!-- Fonts & Icons -->

        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .bg-pattern {
                background-color: #f8fafc;
                background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px), radial-gradient(#e2e8f0 0.5px, #f8fafc 0.5px);
                background-size: 20px 20px;
                background-position: 0 0, 10px 10px;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
