@props(['nav'])

{{-- Overlay (layar kecil) --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-forest-deep/60 lg:hidden"
    @click="sidebarOpen = false"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-forest-deep text-white transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    {{-- Merek --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-leaf">
            <i data-lucide="car-front" class="h-5 w-5"></i>
        </span>
        <div class="leading-tight">
            <div class="text-sm font-bold">Harkat Rent Car</div>
            <div class="text-[11px] text-white/55">Panel Admin</div>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($nav as $n)
            @if (isset($n['items']))
                @php
                    $aktif = collect($n['items'])->contains(fn($i) => $i['match'] && request()->routeIs(...$i['match']));
                @endphp
                <div x-data="{ open: {{ $aktif ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white {{ $aktif ? 'text-white' : '' }}">
                        <i data-lucide="{{ $n['icon'] }}" class="h-[18px] w-[18px] shrink-0"></i>
                        <span class="flex-1 text-left">{{ $n['label'] }}</span>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="mt-1 space-y-0.5 pl-4">
                        <div class="space-y-0.5 border-l border-white/10 pl-3">
                            @foreach ($n['items'] as $i)
                                @php $on = $i['match'] && request()->routeIs(...$i['match']); @endphp
                                <a href="{{ route($i['route']) }}"
                                    class="block rounded-lg px-3 py-2 text-[13px] transition {{ $on ? 'bg-leaf font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                                    {{ $i['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                @php $on = request()->routeIs(...$n['match']); @endphp
                <a href="{{ route($n['route']) }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $on ? 'bg-leaf text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i data-lucide="{{ $n['icon'] }}" class="h-[18px] w-[18px] shrink-0"></i>
                    <span>{{ $n['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-5 py-3 text-[11px] text-white/45">
        Rental Mobil Harkat Yogyakarta
    </div>
</aside>
