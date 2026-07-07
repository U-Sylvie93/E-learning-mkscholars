@props([
    'title',
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'MK Scholars quiz mode.' }}">
        <title>{{ $title }} | {{ config('app.name', 'MK Scholars') }}</title>
        <link rel="icon" type="image/webp" href="{{ asset('favicon.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-800 antialiased">
        <main class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.16),transparent_34%),linear-gradient(135deg,#073653_0%,#102a3a_52%,#071926_100%)] px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
        @livewireScripts
    </body>
</html>
