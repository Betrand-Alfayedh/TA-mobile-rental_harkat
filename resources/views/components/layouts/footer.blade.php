@php
    $home = url('/');
    $wa = 'https://wa.me/6285222521995';
@endphp

<footer class="bg-forest-deep text-white/70">
    <div class="mx-auto grid max-w-6xl gap-12 px-6 py-20 md:grid-cols-12">

        <div class="md:col-span-5">
            <a href="{{ $home }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                <span class="text-lg font-extrabold tracking-tight text-white">Harkat Rent Car</span>
            </a>
            <p class="mt-5 max-w-sm text-[15px] leading-relaxed">
                Rental mobil di Yogyakarta untuk liburan, urusan keluarga, dan perjalanan kerja. Pilih mobil dari
                ponsel, bayar DP, lalu ambil kuncinya di Sewon, Bantul.
            </p>
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary mt-8">
                Chat WhatsApp
                <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
            </a>
        </div>

        <div class="md:col-span-3">
            <h3 class="text-sm font-semibold text-white">Jelajahi</h3>
            <ul class="mt-5 space-y-3 text-[15px]">
                <li><a href="{{ $home }}" class="transition-colors duration-500 ease-spring hover:text-white">Beranda</a></li>
                <li><a href="{{ route('mobil.index') }}" class="transition-colors duration-500 ease-spring hover:text-white">Armada</a></li>
                <li><a href="{{ $home }}#cara-sewa" class="transition-colors duration-500 ease-spring hover:text-white">Cara sewa</a></li>
                <li><a href="{{ $home }}#lokasi" class="transition-colors duration-500 ease-spring hover:text-white">Lokasi</a></li>
                @auth('web')
                    <li><a href="{{ route('booking.index') }}" class="transition-colors duration-500 ease-spring hover:text-white">Pesanan saya</a></li>
                @endauth
                <li><a href="{{ route('login.form', ['role' => 'supir']) }}" class="transition-colors duration-500 ease-spring hover:text-white">Portal supir</a></li>
            </ul>
        </div>

        <div class="md:col-span-4">
            <h3 class="text-sm font-semibold text-white">Hubungi kami</h3>
            <ul class="mt-5 space-y-4 text-[15px]">
                <li class="flex gap-3">
                    <x-line-icon name="pin" class="mt-0.5 h-5 w-5 shrink-0 text-leaf" />
                    <span>Jl. KH Ali Maksum No. 04, Palemsewu, Panggungharjo, Sewon, Bantul, DIY 55188</span>
                </li>
                <li class="flex gap-3">
                    <x-line-icon name="whatsapp" class="mt-0.5 h-5 w-5 shrink-0 text-leaf" />
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="transition-colors duration-500 ease-spring hover:text-white">+62 852-2252-1995</a>
                </li>
                <li class="flex gap-3">
                    <x-line-icon name="tiktok" class="mt-0.5 h-5 w-5 shrink-0 text-leaf" />
                    <span>@harkat_tourtravel</span>
                </li>
                <li class="flex gap-3">
                    <x-line-icon name="instagram" class="mt-0.5 h-5 w-5 shrink-0 text-leaf" />
                    <span>Segera hadir</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-6 py-6 text-sm text-white/45 sm:flex-row sm:justify-between">
            <span>&copy; {{ date('Y') }} Harkat Rent Car. Semua hak dilindungi.</span>
            <span>Yogyakarta</span>
        </div>
    </div>
</footer>
