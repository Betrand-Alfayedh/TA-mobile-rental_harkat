@props(['crumb' => []])

<header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-hk-line bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = true"
            class="rounded-lg border border-hk-line p-2 text-hk-ink hover:bg-hk-panel lg:hidden" aria-label="Buka menu">
            <i data-lucide="menu" class="h-4 w-4"></i>
        </button>
        <nav class="flex items-center gap-2 text-sm text-hk-mute" aria-label="Lokasi halaman">
            @forelse ($crumb as $c)
                @if (!$loop->first)
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                @endif
                <span class="{{ $loop->last ? 'font-medium text-hk-ink' : '' }}">{{ $c }}</span>
            @empty
                <span class="font-medium text-hk-ink">Panel Admin</span>
            @endforelse
        </nav>
    </div>

    <div class="mono hidden items-center gap-2 rounded-lg border border-hk-line bg-white px-3 py-1.5 text-xs text-hk-ink sm:flex">
        <i data-lucide="calendar" class="h-3.5 w-3.5 text-hk-mute"></i>
        {{ now()->locale('id')->translatedFormat('j M Y') }}
    </div>
</header>
