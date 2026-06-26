@php
    $links = [
        ['label' => 'Dashboard', 'route' => 'instructor.dashboard', 'active' => 'instructor.dashboard'],
        ['label' => 'My Courses', 'route' => 'instructor.courses.index', 'active' => 'instructor.courses.*'],
        ['label' => 'Live Classes', 'route' => 'instructor.live-classes.index', 'active' => 'instructor.live-classes.*'],
        ['label' => 'Notifications', 'route' => 'instructor.notifications', 'active' => 'instructor.notifications*'],
    ];
@endphp

<div class="mb-8 flex flex-wrap gap-3">
    @foreach ($links as $link)
        <x-button
            :href="route($link['route'])"
            size="sm"
            :variant="request()->routeIs($link['active']) ? 'primary' : 'secondary'"
        >
            {{ $link['label'] }}
        </x-button>
    @endforeach
</div>
