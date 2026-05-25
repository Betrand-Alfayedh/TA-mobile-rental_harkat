<button type="{{ $type ?? 'submit' }}"
    {{ $attributes->merge(['class' => 'w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-semibold']) }}>
    {{ $slot }}
</button>
