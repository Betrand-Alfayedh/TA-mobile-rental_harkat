{{-- resources/views/layouts/landing.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">

    <title>@yield('title', 'Harkat Rent Car Yogyakarta | Sewa Mobil Lepas Kunci & dengan Supir')</title>
    <meta name="description"
        content="Sewa mobil di Yogyakarta. Booking online, DP 50%, lepas kunci atau dengan supir. Ambil kunci di Sewon, Bantul.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Animasi masuk hanya aktif bila JS jalan; tanpa JS konten tetap terlihat. --}}
    <script>
        document.documentElement.classList.add('js')
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="site flex min-h-screen flex-col">
    <x-layouts.header />

    {{-- Modal login customer --}}
    <div id="customerLoginModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto p-4"
        role="dialog" aria-modal="true" aria-label="Masuk">
        <div id="modalOverlay" class="absolute inset-0 bg-forest-deep/70 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md">
            <button id="closeLoginModal" type="button" aria-label="Tutup"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full text-2xl leading-none text-ink/50 transition-colors duration-500 ease-spring hover:bg-mint hover:text-forest">&times;</button>
            <x-auth.login-form :roleName="'Customer'" :color="'green'" :loginFrom="'customer'" />
        </div>
    </div>

    {{-- Modal: lengkapi profil sebelum booking --}}
    <div id="profileModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog"
        aria-modal="true" aria-label="Lengkapi profil">
        <div id="profileOverlay" class="absolute inset-0 bg-forest-deep/70 backdrop-blur-sm"></div>
        <div class="bezel relative w-full max-w-md">
            <div class="bezel-core p-7">
                <h2 class="text-xl font-bold tracking-tight text-forest">Lengkapi profil dulu</h2>
                <p class="mt-2 text-[15px] leading-relaxed text-ink/65">
                    Nomor HP, alamat, dan asal kota dibutuhkan sebelum Anda bisa booking mobil.
                </p>
                <div class="mt-7 flex justify-end gap-2">
                    <button id="cancelProfileModal" type="button" class="btn btn-outline pr-6">Nanti</button>
                    <a href="{{ route('profile.index') }}" class="btn btn-primary">
                        Isi profil <span class="btn-icon"><x-line-icon name="arrow-right" class="h-4 w-4" /></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Hero lebar penuh (beranda) --}}
    @yield('hero')

    {{-- Pita judul halaman dalam (opsional) --}}
    @yield('pagehead')

    {{-- Halaman biasa: konten di dalam wadah --}}
    @hasSection('content')
        <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-24 pt-6">
            @yield('content')
        </main>
    @endif

    {{-- Bagian lebar penuh (beranda) --}}
    @yield('fullwidth')

    <x-layouts.footer />

    <script>
        // Modal login & profil
        const openModal = el => {
            el?.classList.remove('hidden');
            el?.classList.add('flex');
        };
        const closeModal = el => {
            el?.classList.add('hidden');
            el?.classList.remove('flex');
        };
        const loginModal = document.getElementById('customerLoginModal');
        const profileModal = document.getElementById('profileModal');

        document.querySelectorAll('[data-open-login]').forEach(btn =>
            btn.addEventListener('click', e => {
                e.preventDefault();
                openModal(loginModal);
            }));
        ['modalOverlay', 'closeLoginModal'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => closeModal(loginModal)));

        function openProfileModal() {
            openModal(profileModal);
        }
        ['profileOverlay', 'cancelProfileModal'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => closeModal(profileModal)));

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal(loginModal);
                closeModal(profileModal);
            }
        });

        // Satu momen masuk: konten naik pelan saat terlihat (IntersectionObserver, bukan scroll listener)
        const reveals = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(en => {
                    if (en.isIntersecting) {
                        en.target.classList.add('is-in');
                        io.unobserve(en.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -8% 0px'
            });
            reveals.forEach(el => io.observe(el));
        } else {
            reveals.forEach(el => el.classList.add('is-in'));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: @json(session('success')),
                showConfirmButton: false,
                timer: 3000,
                confirmButtonColor: '#16A34A'
            })
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Tidak berhasil',
                text: @json(session('error')),
                confirmButtonColor: '#16A34A'
            })
        </script>
    @endif

    {{-- Buka modal login otomatis bila ada galat login --}}
    @if ($errors->has('email') || $errors->has('password'))
        <script>
            openModal(loginModal);
        </script>
    @endif

    @stack('scripts')
</body>

</html>
