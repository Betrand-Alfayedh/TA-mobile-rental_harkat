@php
    $user = auth('web')->user();
    $home = url('/');
    $wa = 'https://wa.me/6285222521995';

    // Menu berubah mengikuti status login. Tautan #bagian selalu mengarah ke beranda.
    $links = $user
        ? [
            ['Armada', route('mobil.index'), request()->routeIs('mobil.*')],
            ['Pesanan', route('booking.index'), request()->routeIs('booking.index', 'pembayaran.*')],
            ['Riwayat', route('riwayat.index'), request()->routeIs('riwayat.*')],
            ['Lokasi', $home . '#lokasi', false],
        ]
        : [
            ['Armada', route('mobil.index'), request()->routeIs('mobil.*')],
            ['Layanan', $home . '#layanan', false],
            ['Cara sewa', $home . '#cara-sewa', false],
            ['Lokasi', $home . '#lokasi', false],
        ];
@endphp

{{-- Navigasi melayang: pil kaca yang terpisah dari tepi atas --}}
<header class="pointer-events-none fixed inset-x-0 top-4 z-40 px-4">
    <div
        class="pointer-events-auto mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-full bg-white/85 py-2 pl-5 pr-2 shadow-soft ring-1 ring-forest/10 backdrop-blur-xl">

        <a href="{{ $user ? url('/home') : $home }}" class="flex shrink-0 items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 rounded-full object-cover">
            <span class="text-[15px] font-extrabold leading-none tracking-tight text-forest">
                Harkat <span class="font-semibold text-ink/70">Rent Car</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
            @foreach ($links as [$label, $href, $active])
                <a href="{{ $href }}"
                    class="rounded-full px-4 py-2 text-sm transition-colors duration-500 ease-spring {{ $active ? 'bg-mint font-semibold text-forest' : 'font-medium text-ink/70 hover:bg-mint hover:text-forest' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @if ($user)
                <a href="{{ route('profile.index') }}"
                    class="hidden items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm font-medium text-ink/80 transition-colors duration-500 ease-spring hover:bg-mint sm:flex">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-forest text-xs font-bold text-white">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <span class="max-w-[8rem] truncate">{{ $user->name }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" title="Keluar" aria-label="Keluar"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-ink/60 transition-colors duration-500 ease-spring hover:bg-mint hover:text-forest">
                        <x-line-icon name="logout" class="h-5 w-5" />
                    </button>
                </form>
            @else
                <a href="{{ route('login.form', ['role' => 'supir']) }}"
                    class="hidden rounded-full px-3 py-2 text-sm font-medium text-ink/55 transition-colors duration-500 ease-spring hover:bg-mint hover:text-forest lg:block">
                    Portal supir
                </a>
                <a href="#" data-open-login
                    class="hidden rounded-full px-4 py-2 text-sm font-semibold text-forest transition-colors duration-500 ease-spring hover:bg-mint sm:block">
                    Masuk
                </a>
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary hidden sm:inline-flex">
                    Hubungi kami
                    <span class="btn-icon"><x-line-icon name="whatsapp" class="h-4 w-4" /></span>
                </a>
            @endif

            {{-- Hamburger: tiga garis berubah menjadi X --}}
            <button id="menuToggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu"
                class="relative flex h-10 w-10 items-center justify-center rounded-full bg-forest text-white lg:hidden">
                <span class="menu-line absolute h-px w-4 -translate-y-[5px] bg-current transition-transform duration-500 ease-spring"></span>
                <span class="menu-line absolute h-px w-4 bg-current transition-opacity duration-300"></span>
                <span class="menu-line absolute h-px w-4 translate-y-[5px] bg-current transition-transform duration-500 ease-spring"></span>
            </button>
        </div>
    </div>
</header>

{{-- Menu layar penuh (mobile) --}}
<div id="mobileMenu" aria-hidden="true"
    class="pointer-events-none fixed inset-0 z-30 flex flex-col justify-center bg-forest-deep/95 px-8 opacity-0 backdrop-blur-3xl transition-opacity duration-500 ease-spring">
    <nav class="flex flex-col gap-2" aria-label="Menu seluler">
        @foreach ($links as $i => [$label, $href, $active])
            <a href="{{ $href }}"
                class="menu-item translate-y-12 text-4xl font-bold tracking-tight text-white/90 opacity-0 transition-all duration-700 ease-spring hover:text-leaf"
                style="transition-delay: {{ 100 + $i * 70 }}ms">{{ $label }}</a>
        @endforeach
        @if ($user)
            <a href="{{ route('profile.index') }}"
                class="menu-item translate-y-12 text-4xl font-bold tracking-tight text-white/90 opacity-0 transition-all duration-700 ease-spring hover:text-leaf"
                style="transition-delay: {{ 100 + count($links) * 70 }}ms">Profil</a>
        @endif
    </nav>

    <div class="menu-item mt-10 flex translate-y-12 flex-wrap items-center gap-3 opacity-0 transition-all duration-700 ease-spring"
        style="transition-delay: {{ 160 + count($links) * 70 }}ms">
        @if ($user)
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-light">
                    Keluar <span class="btn-icon"><x-line-icon name="logout" class="h-4 w-4" /></span>
                </button>
            </form>
        @else
            <a href="#" data-open-login class="btn btn-primary">
                Masuk <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
            </a>
            <a href="{{ route('login.form', ['role' => 'supir']) }}" class="btn btn-ghost">Portal supir</a>
        @endif
    </div>
</div>

<script>
    (function() {
        const btn = document.getElementById('menuToggle');
        const menu = document.getElementById('mobileMenu');
        if (!btn || !menu) return;
        const lines = btn.querySelectorAll('.menu-line');
        const items = menu.querySelectorAll('.menu-item');

        function setOpen(open) {
            btn.setAttribute('aria-expanded', open);
            menu.setAttribute('aria-hidden', !open);
            menu.classList.toggle('opacity-0', !open);
            menu.classList.toggle('pointer-events-none', !open);
            document.body.classList.toggle('overflow-hidden', open);
            // garis atas/bawah memutar menjadi X, garis tengah menghilang
            lines[0].style.transform = open ? 'rotate(45deg)' : '';
            lines[1].style.opacity = open ? '0' : '';
            lines[2].style.transform = open ? 'rotate(-45deg)' : '';
            items.forEach(el => {
                el.classList.toggle('translate-y-12', !open);
                el.classList.toggle('opacity-0', !open);
            });
        }

        btn.addEventListener('click', () => setOpen(btn.getAttribute('aria-expanded') !== 'true'));
        menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
        document.addEventListener('keydown', e => e.key === 'Escape' && setOpen(false));
    })();
</script>
