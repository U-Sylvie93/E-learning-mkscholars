@props([
    'status',
    'label' => null,
])

@php
    $key = \Illuminate\Support\Str::of((string) $status)->lower()->replace(['-', ' '], '_')->toString();

    $toneMap = [
        // success (green)
        'approved' => 'success', 'published' => 'success', 'completed' => 'success',
        'passed' => 'success', 'active' => 'success', 'graded' => 'success',
        'issued' => 'success', 'eligible' => 'success', 'attended' => 'success', 'live' => 'success',
        // warning (amber/gold)
        'pending' => 'warning', 'submitted' => 'warning', 'under_review' => 'warning',
        'scheduled' => 'warning', 'draft' => 'warning', 'in_progress' => 'warning',
        // danger (red)
        'rejected' => 'danger', 'failed' => 'danger', 'suspended' => 'danger',
        'revoked' => 'danger', 'cancelled' => 'danger', 'archived' => 'danger',
        'missed' => 'danger', 'expired' => 'danger', 'not_started' => 'gray',
    ];

    $tone = $toneMap[$key] ?? 'gray';
    $text = $label ?? \Illuminate\Support\Str::of($key)->replace('_', ' ')->headline()->toString();
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $text }}</x-badge>
