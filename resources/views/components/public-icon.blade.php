@props([
    'name' => 'sparkles',
    'class' => 'h-5 w-5',
])

@php
    $paths = [
        'academy' => '<path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-7h6v7"/><path d="M7 10h.01"/><path d="M17 10h.01"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'headset' => '<path d="M3 13a9 9 0 0 1 18 0"/><path d="M5 13v4a2 2 0 0 0 2 2h1v-8H7a2 2 0 0 0-2 2Z"/><path d="M19 13v4a2 2 0 0 1-2 2h-1v-8h1a2 2 0 0 1 2 2Z"/><path d="M13 21h-2"/>',
        'certificate' => '<path d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4Z"/><path d="m9 12 2 2 4-5"/>',
        'chart' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-7"/><path d="M17 7h2v2"/>',
        'code' => '<path d="m8 9-4 3 4 3"/><path d="m16 9 4 3-4 3"/><path d="m14 5-4 14"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-5"/>',
        'compass' => '<circle cx="12" cy="12" r="10"/><path d="m16 8-2.5 6.5L7 17l2.5-6.5L16 8Z"/>',
        'trending' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>',
        'play' => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4V8Z"/>',
        'message' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.18 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.72c.13.96.35 1.9.67 2.8a2 2 0 0 1-.45 2.11L8.1 9.85a16 16 0 0 0 6.05 6.05l1.22-1.22a2 2 0 0 1 2.11-.45c.9.32 1.84.54 2.8.67A2 2 0 0 1 22 16.9Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/>',
        'sparkles' => '<path d="M12 3 9.8 8.8 4 11l5.8 2.2L12 19l2.2-5.8L20 11l-5.8-2.2L12 3Z"/><path d="M5 3v4"/><path d="M3 5h4"/><path d="M19 17v4"/><path d="M17 19h4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['sparkles'] !!}
</svg>
