@props([
    'name' => 'dashboard',
    'class' => 'h-5 w-5',
])

@php
    $paths = [
        'dashboard' => '<path d="M4 13h7V4H4v9Zm9 7h7V4h-7v16ZM4 20h7v-5H4v5Z" stroke-linecap="round" stroke-linejoin="round"/>',
        'courses' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/><path d="M8 7h8M8 11h6" stroke-linecap="round"/>',
        'payments' => '<path d="M4 7h16v10H4z"/><path d="M4 10h16"/><path d="M7 14h4" stroke-linecap="round"/>',
        'assignments' => '<path d="M8 3h8l2 2v16H6V5l2-2Z"/><path d="M9 11h6M9 15h6M9 7h3" stroke-linecap="round"/>',
        'certificates' => '<path d="M12 15a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/><path d="m8.5 14.5-1 6 4.5-2 4.5 2-1-6" stroke-linecap="round" stroke-linejoin="round"/>',
        'documents' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/><path d="M8 13h8M8 17h6" stroke-linecap="round"/>',
        'live' => '<path d="M4 6h12v12H4z"/><path d="m16 10 4-2v8l-4-2v-4Z" stroke-linecap="round" stroke-linejoin="round"/>',
        'notifications' => '<path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M10 21h4" stroke-linecap="round"/>',
        'settings' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1 .6V20a2 2 0 0 1-4 0v-.1a1.8 1.8 0 0 0-1-.6 1.8 1.8 0 0 0-1.98.36l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-.6-1H4a2 2 0 0 1 0-4h.1a1.8 1.8 0 0 0 .6-1 1.8 1.8 0 0 0-.36-1.98l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1-.6V4a2 2 0 0 1 4 0v.1a1.8 1.8 0 0 0 1 .6 1.8 1.8 0 0 0 1.98-.36l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.8 1.8 0 0 0 19.4 9c.22.38.43.7.6 1H20a2 2 0 0 1 0 4h-.1c-.17.3-.38.62-.5 1Z" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    {!! $paths[$name] ?? $paths['dashboard'] !!}
</svg>
