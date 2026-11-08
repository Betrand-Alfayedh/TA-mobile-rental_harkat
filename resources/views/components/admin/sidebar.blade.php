@props(['nav'])

{{-- Overlay (layar kecil) --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-hk-ink/30 lg:hidden"
    @click="sidebarOpen = false"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-hk-line bg-hk-panel transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    {{-- Merek --}}
    <div class="p-3">
        <div class="flex items-center gap-3 rounded-xl border border-hk-line bg-hk-deep/60 px-3 py-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-hk-ink text-white">
                <i data-lucide="car-front" class="h-[18px] w-[18px]"></i>
            </span>
            <div class="leading-tight">
                <div class="text-[11px] text-hk-mute">Rental mobil</div>
                <div class="text-sm font-semibold text-hk-ink">Harkat Yogyakarta</div>
            </div>
        </div>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4 pt-2">
        @foreach ($nav as $grup)
            <div>
                <div class="mb-1.5 px-3 text-[11px] font-medium text-hk-mute">{{ $grup['label'] }}</div>
                <div class="space-y-0.5">
                    @foreach ($grup['items'] as $i)
                        @php $on = $i['match'] && request()->routeIs(...$i['match']); @endphp
                        <a href="{{ route($i['route']) }}" @if ($on) aria-current="page" @endif
                            class="flex items-center gap-3 rounded-[10px] border px-3 py-2 text-[13.5px] transition {{ $on ? 'border-hk-line bg-white font-semibold text-hk-ink shadow-[0_1px_2px_rgba(23,23,26,.05)]' : 'border-transparent text-hk-ink/65 hover:bg-white/70 hover:text-hk-ink' }}">
                            <i data-lucide="{{ $i['icon'] }}"
                                class="h-4 w-4 shrink-0 {{ $on ? 'text-hk-ink' : 'text-hk-mute' }}"></i>
                            <span class="flex-1">{{ $i['label'] }}</span>
                            @if (!empty($i['badge']))
                                <span class="mono rounded-full bg-hk-ink px-1.5 py-0.5 text-[10px] font-medium leading-none text-white"
                                    title="Menunggu tindakan">{{ $i['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Akun --}}
    <div class="relative border-t border-hk-line p-3" x-data="{ open: false }" @click.outside="open = false">
        <button type="button" @click="open = !open"
            class="flex w-full items-center gap-3 rounded-xl border border-hk-line bg-white px-3 py-2.5 text-left transition hover:border-hk-ink/30">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-hk-ink text-sm font-semibold text-white">
                {{ strtoupper(mb_substr(Auth::user()->name ?? 'A', 0, 1)) }}
            </span>
            <span class="min-w-0 flex-1 leading-tight">
                <span class="block truncate text-sm font-semibold text-hk-ink">{{ Auth::user()->name ?? 'Admin' }}</span>
                <span class="block text-[11px] text-hk-mute">Administrator</span>
            </span>
            <i data-lucide="chevrons-up-down" class="h-4 w-4 shrink-0 text-hk-mute"></i>
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms
            class="absolute inset-x-3 bottom-[calc(100%-4px)] overflow-hidden rounded-xl border border-hk-line bg-white py-1 shadow-lg">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Keluar</button>
            </form>
        </div>
    </div>
</aside>
