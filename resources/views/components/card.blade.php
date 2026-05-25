<div {{ $attributes->merge(['class' => 'bg-white shadow rounded-xl p-6']) }}>
    @isset($title)
        <h2 class="text-lg font-semibold mb-4">{{ $title }}</h2>
    @endisset
    {{ $slot }}
</div>
