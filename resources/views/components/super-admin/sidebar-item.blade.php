@props(['active' => false, 'href' => '#'])

@php
$classes = $active
            ? 'block px-4 py-2 rounded-lg bg-blue-600 text-white font-medium'
            : 'block px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
