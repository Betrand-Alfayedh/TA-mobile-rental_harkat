@props(['crumb' => []])

<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-[#E1E9E4] bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = true"
            class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Buka menu">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Lokasi halaman">
            @forelse ($crumb as $c)
                @if (!$loop->first)
                    <span class="text-gray-300">/</span>
                @endif
                <span class="{{ $loop->last ? 'font-semibold text-gray-800' : '' }}">{{ $c }}</span>
            @empty
                <span class="font-semibold text-gray-800">Panel Admin</span>
            @endforelse
        </nav>
    </div>

    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button type="button" @click="open = !open"
            class="flex items-center gap-3 rounded-xl px-2 py-1.5 transition hover:bg-gray-100">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-leaf text-sm font-bold text-white">
                {{ strtoupper(mb_substr(Auth::user()->name ?? 'A', 0, 1)) }}
            </span>
            <span class="hidden text-left leading-tight sm:block">
                <span class="block text-sm font-semibold text-gray-800">{{ Auth::user()->name ?? 'Admin' }}</span>
                <span class="block text-[11px] text-gray-500">Administrator</span>
            </span>
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" x-cloak x-transition
            class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-[#E1E9E4] bg-white py-1 shadow-lg">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil Saya</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Keluar</button>
            </form>
        </div>
    </div>
</header>
