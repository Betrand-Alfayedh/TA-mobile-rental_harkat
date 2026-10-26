{{-- resources/views/main-content.blade.php : beranda --}}
@extends('layouts.landing')

@php
    $isCustomer = auth('web')->check() && auth('web')->user()->isRole('Customer');
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $wa = 'https://wa.me/6285222521995';
    $maps = 'https://www.google.com/maps/search/?api=1&query=' . urlencode('Jl. KH Ali Maksum Panggungharjo Sewon Bantul');
    // Tombol booking utama: pelanggan lanjut ke form (dicek kelengkapan profilnya), tamu diminta masuk dulu.
    $bookAttrs = $isCustomer ? 'href="' . route('booking.create') . '" data-book' : 'href="#" data-open-login';
@endphp

{{-- ───────────────────────── HERO ───────────────────────── --}}
@section('hero')
    <section class="relative isolate flex min-h-[100dvh] flex-col overflow-hidden bg-forest-deep">
        <img src="{{ asset('images/hero.jpg') }}" alt="Serah terima kunci mobil rental"
            class="absolute inset-0 -z-20 h-full w-full object-cover object-[72%_center]">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-forest-deep via-forest-deep/85 to-forest/25"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-1/3 bg-gradient-to-t from-forest-deep to-transparent"></div>

        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-6 pb-10 pt-32">
            <h1 class="reveal max-w-3xl text-[clamp(2.4rem,min(6vw,8.6vh),4.75rem)] font-extrabold leading-[1.03] tracking-[-0.028em] text-white">
                Sewa mobil di Yogyakarta, dari booking sampai kunci di tangan.
            </h1>
            <p class="reveal mt-6 max-w-xl text-lg leading-relaxed text-white/75" style="--d: 120ms">
                Pilih mobil dan tanggal dari ponsel, bayar DP 50%, lalu ambil kuncinya.
                Tersedia lepas kunci maupun dengan supir.
            </p>
            <div class="reveal mt-10 flex flex-wrap gap-3" style="--d: 220ms">
                <a {!! $bookAttrs !!} class="btn btn-primary">
                    Booking sekarang
                    <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                </a>
                <a href="#armada" class="btn btn-ghost">
                    Lihat armada
                    <span class="btn-icon"><x-line-icon name="arrow-right" class="h-4 w-4" /></span>
                </a>
            </div>
        </div>

        {{-- Ringkasan: angka asli dari data armada --}}
        <div class="px-4 pb-6 sm:pb-8">
            <div class="bezel-dark mx-auto max-w-5xl">
                <dl class="grid grid-cols-2 overflow-hidden rounded-[calc(2rem-0.375rem)] bg-forest-deep/90 lg:grid-cols-4">
                    @foreach ([[$stats['unit'] . ' unit', 'Armada siap disewa'], [$stats['mulai'] ? $rp($stats['mulai']) : '-', 'Tarif mulai per hari'], ['50%', 'DP untuk mengunci booking'], ['2 pilihan', 'Lepas kunci atau dengan supir']] as $i => [$angka, $label])
                        <div class="px-6 py-5 {{ $i % 2 === 1 ? 'border-l border-white/10' : '' }} {{ $i > 1 ? 'border-t border-white/10 lg:border-t-0' : '' }} {{ $i === 2 ? 'lg:border-l' : '' }}">
                            <dd class="text-2xl font-bold tracking-tight text-white sm:text-[1.7rem]">{{ $angka }}</dd>
                            <dt class="mt-1 text-sm text-white/55">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>
@endsection

@section('fullwidth')
    {{-- ───────────────────────── ARMADA ───────────────────────── --}}
    <section id="armada" class="scroll-mt-4 py-24 sm:py-32">
        <div class="mx-auto max-w-6xl px-6">
            <div class="reveal flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="max-w-xl text-[clamp(2rem,4vw,2.9rem)] font-bold leading-[1.08] tracking-tight text-forest">
                        Pilih mobil yang pas untuk perjalanan Anda
                    </h2>
                    <p class="mt-4 max-w-lg text-[17px] leading-relaxed text-ink/65">
                        Harga per hari, dibulatkan per 24 jam. Booking hanya bisa untuk tanggal yang masih kosong.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="fleetPrev" aria-label="Geser ke kiri"
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-forest/15 text-forest transition-colors duration-500 ease-spring hover:bg-forest hover:text-white">
                        <x-line-icon name="arrow-left" class="h-5 w-5" />
                    </button>
                    <button type="button" id="fleetNext" aria-label="Geser ke kanan"
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-forest/15 text-forest transition-colors duration-500 ease-spring hover:bg-forest hover:text-white">
                        <x-line-icon name="arrow-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            @if ($mobils->isEmpty())
                <div class="bezel mt-12">
                    <div class="bezel-core px-8 py-16 text-center">
                        <x-line-icon name="car" class="mx-auto h-12 w-12 text-forest/30" />
                        <p class="mt-4 font-semibold text-forest">Armada sedang diperbarui</p>
                        <p class="mt-1 text-ink/60">Hubungi kami lewat WhatsApp untuk menanyakan unit yang tersedia.</p>
                    </div>
                </div>
            @else
                {{-- Tab tipe mobil --}}
                <div id="fleetTabs" class="no-scrollbar reveal mt-10 flex gap-2 overflow-x-auto" role="tablist"
                    aria-label="Filter tipe mobil">
                    <button type="button" role="tab" aria-selected="true" data-filter="all"
                        class="fleet-tab shrink-0 rounded-full bg-forest px-5 py-2 text-sm font-semibold text-white transition-colors duration-500 ease-spring">
                        Semua
                    </button>
                    @foreach ($tipes as $t)
                        <button type="button" role="tab" aria-selected="false" data-filter="{{ $t->id }}"
                            class="fleet-tab shrink-0 rounded-full bg-white px-5 py-2 text-sm font-semibold text-ink/70 ring-1 ring-forest/10 transition-colors duration-500 ease-spring hover:text-forest">
                            {{ $t->nama_tipe }}
                        </button>
                    @endforeach
                </div>

                <div id="fleetTrack" class="no-scrollbar -mx-6 mt-8 flex snap-x snap-mandatory gap-5 overflow-x-auto px-6 pb-4">
                    @foreach ($mobils as $m)
                        @php
                            $units = $m->mobils;
                            $foto = $units->firstWhere('gambar')?->gambar;
                        @endphp
                        <article data-tipe="{{ $m->tipe_id }}"
                            class="fleet-card reveal w-[82%] shrink-0 snap-start sm:w-[calc(50%-0.625rem)] lg:w-[calc(33.333%-0.85rem)]">
                            <div class="bezel h-full">
                                <div class="bezel-core flex flex-col overflow-hidden">
                                    <x-car-image :file="$foto" :alt="$m->nama" class="h-52" />
                                    <div class="flex flex-1 flex-col p-6">
                                        <div class="flex items-start justify-between gap-3">
                                            <h3 class="text-lg font-bold tracking-tight text-forest">{{ $m->nama }}</h3>
                                            @if ($m->tipe)
                                                <span class="shrink-0 rounded-full bg-mint px-3 py-1 text-xs font-semibold text-forest">{{ $m->tipe->nama_tipe }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-ink/55">{{ $units->count() }} unit</p>

                                        <dl class="mt-5 space-y-2 text-sm">
                                            <div class="flex items-baseline justify-between gap-3">
                                                <dt class="text-ink/55">Lepas kunci</dt>
                                                <dd class="font-bold text-ink">{{ $rp($units->min('harga_sewa')) }}<span class="font-medium text-ink/50"> /hari</span></dd>
                                            </div>
                                            <div class="flex items-baseline justify-between gap-3">
                                                <dt class="text-ink/55">Dengan supir</dt>
                                                <dd class="font-bold text-ink">{{ $rp($units->min('harga_all_in')) }}<span class="font-medium text-ink/50"> /hari</span></dd>
                                            </div>
                                        </dl>

                                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-6">
                                            @if ($isCustomer)
                                                <a href="{{ route('booking.create', ['masterMobil' => $m->id]) }}" class="btn btn-primary">
                                                    Booking <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                                                </a>
                                            @else
                                                <a href="#" data-open-login class="btn btn-primary">
                                                    Booking <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                                                </a>
                                            @endif
                                            <a href="{{ route('mobil.index', ['search' => $m->nama]) }}"
                                                class="rounded-full px-4 py-3 text-sm font-semibold text-forest transition-colors duration-500 ease-spring hover:bg-mint">
                                                Lihat unit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="reveal mt-6">
                    <a href="{{ route('mobil.index') }}" class="btn btn-outline">
                        Lihat semua armada
                        <span class="btn-icon"><x-line-icon name="arrow-right" class="h-4 w-4" /></span>
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- ───────────────────────── LAYANAN ───────────────────────── --}}
    <section id="layanan" class="scroll-mt-4 bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="reveal max-w-2xl text-[clamp(2rem,4vw,2.9rem)] font-bold leading-[1.08] tracking-tight text-forest">
                Semua yang Anda butuhkan untuk menyewa mobil, tanpa ke kantor dulu
            </h2>

            <div class="mt-14 grid gap-5 lg:grid-cols-4 lg:grid-rows-2">
                {{-- Kartu utama --}}
                <div class="reveal lg:col-span-2 lg:row-span-2">
                    <div class="bezel-dark h-full">
                        <div class="bezel-core flex h-full min-h-[22rem] flex-col justify-between p-8 sm:p-10">
                            <div class="flex gap-3 text-leaf">
                                <x-line-icon name="key" class="h-9 w-9" />
                                <x-line-icon name="steering" class="h-9 w-9" />
                            </div>
                            <div>
                                <h3 class="text-3xl font-bold leading-tight tracking-tight text-white">
                                    Lepas kunci atau dengan supir
                                </h3>
                                <p class="mt-4 max-w-md text-[17px] leading-relaxed text-white/65">
                                    Bawa sendiri untuk kebebasan penuh, atau pilih tarif dengan supir bila ingin duduk
                                    tenang. Pilihannya ada di form booking, per mobil.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach ([['calendar', 'Booking online', 'Pesan lewat web atau aplikasi Android. Ketersediaan dicek sesuai tanggal sewa.'], ['wallet', 'DP 50%', 'Bayar separuh lewat transfer atau QRIS untuk mengunci mobil. Sisanya dilunasi saat sewa.'], ['receipt', 'Invoice ke email', 'Bukti bayar diperiksa admin, lalu invoice PDF dikirim ke email Anda.'], ['shield', 'Jaminan jelas', 'KTP atau paspor untuk dari luar kota. KTP dan motor untuk dari Yogyakarta.']] as $i => [$ikon, $judul, $isi])
                    <div class="reveal" style="--d: {{ ($i + 1) * 80 }}ms">
                        <div class="bezel h-full">
                            <div class="bezel-core p-7">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-forest">
                                    <x-line-icon name="{{ $ikon }}" class="h-6 w-6" />
                                </span>
                                <h3 class="mt-6 text-lg font-bold tracking-tight text-forest">{{ $judul }}</h3>
                                <p class="mt-2 text-[15px] leading-relaxed text-ink/60">{{ $isi }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────── CARA SEWA ───────────────────────── --}}
    <section id="cara-sewa" class="scroll-mt-4 py-24 sm:py-32">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="reveal max-w-2xl text-[clamp(2rem,4vw,2.9rem)] font-bold leading-[1.08] tracking-tight text-forest">
                Lima langkah dari memilih mobil sampai mengembalikannya
            </h2>

            <ol class="relative mt-16 grid gap-10 lg:grid-cols-5 lg:gap-6">
                {{-- garis penghubung: vertikal di mobile, horizontal di desktop --}}
                <span class="absolute bottom-6 left-6 top-6 w-px bg-line lg:hidden"></span>
                <span class="absolute left-6 right-[10%] top-6 hidden h-px bg-line lg:block"></span>
                @foreach ([['Pilih mobil dan tanggal', 'Lihat unit yang kosong pada tanggal Anda, lalu pilih lepas kunci atau dengan supir.'], ['Booking dan bayar DP', 'DP 50% lewat transfer atau QRIS sebelum jatuh tempo, lalu unggah bukti bayarnya.'], ['Pembayaran diverifikasi', 'Admin memeriksa bukti bayar dan mengirim invoice ke email Anda.'], ['Ambil mobil', 'Datang ke Sewon, Bantul pada hari sewa dan bawa jaminan sesuai pilihan saat booking.'], ['Kembalikan dan lunasi', 'Kembalikan sesuai jadwal dan lunasi sisanya. Keterlambatan dikenai denda per jam.']] as $i => [$judul, $isi])
                    <li class="reveal relative flex gap-5 lg:block" style="--d: {{ $i * 90 }}ms">
                        <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-base font-bold text-forest shadow-soft ring-1 ring-forest/10">{{ $i + 1 }}</span>
                        <div class="lg:mt-6">
                            <h3 class="text-lg font-bold tracking-tight text-forest">{{ $judul }}</h3>
                            <p class="mt-2 text-[15px] leading-relaxed text-ink/60">{{ $isi }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ───────────────────────── AJAKAN ───────────────────────── --}}
    <section class="relative isolate overflow-hidden bg-forest py-24 sm:py-32">
        <div class="absolute -right-24 top-1/2 -z-10 h-[34rem] w-[34rem] -translate-y-1/2 rounded-full bg-[radial-gradient(circle,rgba(22,163,74,0.35),transparent_65%)]"></div>
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="reveal max-w-3xl text-[clamp(2.2rem,5vw,3.8rem)] font-extrabold leading-[1.04] tracking-[-0.03em] text-white">
                Tanggal perjalanan sudah ada? Amankan mobilnya sekarang.
            </h2>
            <p class="reveal mt-5 max-w-xl text-lg leading-relaxed text-white/70" style="--d: 100ms">
                Mobil yang sudah di-booking untuk tanggal Anda tidak bisa diambil orang lain.
            </p>
            <div class="reveal mt-10 flex flex-wrap gap-3" style="--d: 200ms">
                <a {!! $bookAttrs !!} class="btn btn-primary">
                    Booking sekarang
                    <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                </a>
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-ghost">
                    Tanya lewat WhatsApp
                    <span class="btn-icon"><x-line-icon name="whatsapp" class="h-4 w-4" /></span>
                </a>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── LOKASI ───────────────────────── --}}
    <section id="lokasi" class="scroll-mt-4 bg-white py-24 sm:py-32">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <div class="reveal">
                <h2 class="text-[clamp(2rem,4vw,2.9rem)] font-bold leading-[1.08] tracking-tight text-forest">
                    Ambil kuncinya di Sewon, Bantul
                </h2>
                <p class="mt-5 max-w-md text-[17px] leading-relaxed text-ink/65">
                    Jl. KH Ali Maksum No. 04, Palemsewu, Panggungharjo, Sewon, Bantul, DIY 55188.
                </p>
                <ul class="mt-8 space-y-4 text-[15px] text-ink/75">
                    <li class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-mint text-forest"><x-line-icon name="whatsapp" class="h-5 w-5" /></span>
                        +62 852-2252-1995
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-mint text-forest"><x-line-icon name="tiktok" class="h-5 w-5" /></span>
                        @harkat_tourtravel
                    </li>
                </ul>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ $maps }}" target="_blank" rel="noopener" class="btn btn-primary">
                        Petunjuk arah
                        <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                    </a>
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline">
                        Chat WhatsApp
                        <span class="btn-icon"><x-line-icon name="whatsapp" class="h-4 w-4" /></span>
                    </a>
                </div>
            </div>

            <div class="reveal" style="--d: 120ms">
                <div class="bezel">
                    <div class="bezel-core overflow-hidden">
                        <iframe title="Lokasi Harkat Rent Car" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            class="block h-[22rem] w-full border-0 sm:h-[26rem]"
                            src="https://www.google.com/maps?q={{ urlencode('Jl. KH Ali Maksum Panggungharjo Sewon Bantul') }}&output=embed"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // Booking dari tombol utama: cek dulu kelengkapan profil
        document.querySelectorAll('[data-book]').forEach(btn => btn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const res = await fetch("{{ route('check.profile') }}");
                const data = await res.json();

                if (data.status === 'unauthenticated') {
                    window.location.href = "{{ route('google.login') }}";
                } else if (data.status === 'incomplete') {
                    openProfileModal();
                } else if (data.status === 'complete') {
                    window.location.href = data.redirect;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi kesalahan',
                    text: 'Coba lagi sebentar lagi.',
                    confirmButtonColor: '#16A34A'
                });
            }
        }));

        // Armada: tab tipe + tombol geser
        (() => {
            const track = document.getElementById('fleetTrack');
            if (!track) return;
            const tabs = document.querySelectorAll('.fleet-tab');
            const cards = track.querySelectorAll('.fleet-card');

            tabs.forEach(tab => tab.addEventListener('click', () => {
                const f = tab.dataset.filter;
                tabs.forEach(t => {
                    const on = t === tab;
                    t.setAttribute('aria-selected', on);
                    t.classList.toggle('bg-forest', on);
                    t.classList.toggle('text-white', on);
                    t.classList.toggle('bg-white', !on);
                    t.classList.toggle('text-ink/70', !on);
                    t.classList.toggle('ring-1', !on);
                });
                cards.forEach(c => c.classList.toggle('hidden', f !== 'all' && c.dataset.tipe !== f));
                track.scrollTo({
                    left: 0,
                    behavior: 'smooth'
                });
            }));

            const step = () => track.clientWidth * 0.8;
            document.getElementById('fleetPrev')?.addEventListener('click', () => track.scrollBy({
                left: -step(),
                behavior: 'smooth'
            }));
            document.getElementById('fleetNext')?.addEventListener('click', () => track.scrollBy({
                left: step(),
                behavior: 'smooth'
            }));
        })();
    </script>
@endpush
