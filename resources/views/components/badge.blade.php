@props(['color' => 'gray'])
@php
    $colors = [
        'green' => 'bg-green-100 text-green-800',
        'red'   => 'bg-red-100 text-red-800',
        'blue'  => 'bg-blue-100 text-blue-800',
        'gray'  => 'bg-gray-100 text-gray-800',
    ];
@endphp

<span class="px-2 py-1 text-xs font-medium rounded-full {{ $colors[$color] ?? $colors['gray'] }}">
    {{ $slot }}
</span>
