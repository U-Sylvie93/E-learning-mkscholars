@props([
    'name',
    'class' => 'h-4 w-4',
])

@php
    $paths = [
        'bold' => '<path d="M7 5h6a4 4 0 0 1 0 8H7z"/><path d="M7 13h7a4 4 0 0 1 0 8H7z"/><path d="M7 5v16"/>',
        'italic' => '<path d="M10 5h8"/><path d="M6 21h8"/><path d="m14 5-4 16"/>',
        'strike' => '<path d="M4 12h16"/><path d="M8 8a4 4 0 0 1 7.5-2"/><path d="M16 16a4 4 0 0 1-7.5 2"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/>',
        'heading' => '<path d="M5 5v14"/><path d="M19 5v14"/><path d="M5 12h14"/><path d="M14 19h5"/>',
        'quote' => '<path d="M8 10h.01"/><path d="M7 14h4V8H6v6a4 4 0 0 0 4 4"/><path d="M17 10h.01"/><path d="M16 14h4V8h-5v6a4 4 0 0 0 4 4"/>',
        'code' => '<path d="m8 9-4 3 4 3"/><path d="m16 9 4 3-4 3"/><path d="m14 5-4 14"/>',
        'list' => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/>',
        'numbered-list' => '<path d="M10 6h10"/><path d="M10 12h10"/><path d="M10 18h10"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-1.5 2-3 0-.6-.4-1-1-1H4"/>',
        'table' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 11h18"/><path d="M9 5v14"/><path d="M15 5v14"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="m21 15-5-5L5 19"/>',
        'undo' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-1"/>',
        'redo' => '<path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h1"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['code'] !!}
</svg>
