<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistem Absensi') }} - Login</title>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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
