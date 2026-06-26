@props([
    'name' => 'book-open',
])

@php
    $icon = in_array($name, \App\Models\Academy::ICONS, true) ? $name : \App\Models\Academy::ICON_BOOK_OPEN;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-full w-full" aria-hidden="true">
        @switch($icon)
            @case('code')
                <path d="m16 18 6-6-6-6" />
                <path d="m8 6-6 6 6 6" />
                <path d="m14.5 4-5 16" />
                @break
            @case('language')
                <path d="m5 8 6 6" />
                <path d="m4 14 6-6 2-3" />
                <path d="M2 5h12" />
                <path d="M7 2h1" />
                <path d="m22 22-5-10-5 10" />
                <path d="M14 18h6" />
                @break
            @case('graduation-cap')
                <path d="M22 10 12 5 2 10l10 5 10-5Z" />
                <path d="M6 12v5c3 2 9 2 12 0v-5" />
                <path d="M22 10v6" />
                @break
            @case('globe')
                <circle cx="12" cy="12" r="10" />
                <path d="M2 12h20" />
                <path d="M12 2a15 15 0 0 1 0 20" />
                <path d="M12 2a15 15 0 0 0 0 20" />
                @break
            @case('briefcase')
                <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1" />
                <rect x="3" y="6" width="18" height="14" rx="2" />
                <path d="M3 12h18" />
                <path d="M12 12v2" />
                @break
            @case('users')
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                @break
            @case('award')
                <circle cx="12" cy="8" r="5" />
                <path d="m8.5 12-1.5 9 5-3 5 3-1.5-9" />
                @break
            @case('laptop')
                <rect x="4" y="5" width="16" height="11" rx="2" />
                <path d="M2 20h20" />
                @break
            @case('target')
                <circle cx="12" cy="12" r="10" />
                <circle cx="12" cy="12" r="6" />
                <circle cx="12" cy="12" r="2" />
                @break
            @default
                <path d="M2 4h7a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z" />
                <path d="M22 4h-7a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h8z" />
        @endswitch
    </svg>
    <span class="sr-only">{{ \App\Models\Academy::iconOptions()[$icon] ?? 'Academy' }}</span>
</span>
