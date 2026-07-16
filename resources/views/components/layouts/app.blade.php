@props([
    'title' => null,
    'description' => 'MK Scholars is a premium e-learning platform for academies, courses, student support, and progress tracking.',
    'image' => null,
])

@php
    $shareTitle = ($title ? $title.' | ' : '').config('app.name', 'MK Scholars');
    $shareImage = $image ?: asset('images/mk-scholars-logo.webp');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description }}">

        <title>{{ $shareTitle }}</title>
        <meta property="og:title" content="{{ $shareTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $shareTitle }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
        <link rel="icon" type="image/webp" href="{{ asset('favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/mk-scholars-logo.webp') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-mk-cloud text-slate-800 antialiased">
        <div class="min-h-screen bg-mk-cloud">
            <x-navbar />

            <main>
                {{ $slot }}
            </main>

            <x-footer />
        </div>

        @livewireScripts
    </body>
</html>

