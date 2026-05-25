{{-- resources/views/layouts/landing.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">

    <title>Harkat Rental Yogyakarta</title>
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-[#ffffff] flex flex-col min-h-screen bg-gradient-to-b from-white via-gray-100 to-gray-200 text-gray-800 font-sans">
    <!-- Loading Screen -->
    <div id="loading-screen"
        class="fixed inset-0 flex flex-col items-center justify-center z-50 bg-gradient-to-b from-green-900 to-green-500 text-white opacity-100 transition-opacity duration-700">
        <img src="/images/logo.png" alt="Harkat Rent Car" class="w-24 h-24 animate-bounce" />
        <p class="mt-4 text-lg font-semibold animate-pulse">Memuat...</p>
    </div>
    <!-- Header -->
    <x-layouts.header />

    {{-- Customer Login Modal --}}
    <div id="customerLoginModal" class="fixed inset-0 hidden items-center justify-center z-50">
        <!-- Overlay -->
        <div id="modalOverlay" class="absolute inset-0 bg-black bg-opacity-50"></div>

        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg overflow-auto">
            <button id="closeLoginModal"
                class="absolute top-2 right-2 text-gray-700 text-3xl font-bold hover:text-gray-500">&times;</button>

            {{-- Form login customer --}}
            <x-auth.login-form :roleName="'Customer'" :color="'green'" :loginFrom="'customer'" />
        </div>
    </div>

    {{-- Lengkapi Profile Modal --}}
    <div id="profileModal" class="fixed inset-0 hidden items-center justify-center z-50">
        <!-- Overlay -->
        <div id="profileOverlay" class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg p-6">
            <button id="closeProfileModal"
                class="absolute top-2 right-2 text-gray-600 text-2xl font-bold hover:text-gray-800">&times;</button>

            <h2 class="text-lg font-bold mb-3">Lengkapi Profil Dulu 🚨</h2>
            <p class="mb-4 text-gray-600">Sebelum booking mobil, kamu perlu melengkapi data profil.</p>

            <div class="flex justify-end gap-2">
                <button id="cancelProfileModal" class="px-4 py-2 bg-gray-300 rounded">Nanti</button>
                <a href="{{ route('profile.index') }}" class="px-4 py-2 bg-green-600 text-white rounded">Isi Profile</a>
            </div>
        </div>
    </div>
    <!-- Hero (full width) -->
    @yield('hero')
    <!-- Main Content -->
    <main class="flex-1 pt-18 pb-20 px-4 max-w-6xl mx-auto w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <x-layouts.footer />
    {{-- lucide icons --}}
    <script type="module">
        import "lucide/dist/umd/lucide.js";
        lucide.createIcons();
    </script>
    <script type="module" src="{{ asset('resources/js/showcase.js') }}"></script>

    <script>
        const modal = document.getElementById('customerLoginModal');
        const overlay = document.getElementById('modalOverlay');
        const closeBtn = document.getElementById('closeLoginModal');

        // Semua tombol login yang bisa buka modal
        const loginDesktop = document.getElementById('openCustomerLoginModalDesktop');
        const loginMobile = document.getElementById('openCustomerLoginModalMobile');
        const loginBtn = document.getElementById('openCustomerLoginModal'); // fallback

        // Semua tombol yang bisa buka modal
        const loginTriggers = document.querySelectorAll(
            '#openCustomerLoginModal, #openCustomerLoginModalDesktop, #openCustomerLoginModalMobile');

        loginTriggers.forEach(btn => {
            btn?.addEventListener('click', e => {
                e.preventDefault();
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            });
        });

        // Tutup modal
        [overlay, closeBtn].forEach(el => {
            el?.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        });
    </script>
    <script>
        const profileModal = document.getElementById('profileModal');
        const profileOverlay = document.getElementById('profileOverlay');
        const closeProfileModal = document.getElementById('closeProfileModal');
        const cancelProfileModal = document.getElementById('cancelProfileModal');

        // Function buat munculin modal
        function openProfileModal() {
            profileModal.classList.remove('hidden');
            profileModal.classList.add('flex');
        }

        // Tutup modal
        [profileOverlay, closeProfileModal, cancelProfileModal].forEach(el => {
            el?.addEventListener('click', () => {
                profileModal.classList.add('hidden');
                profileModal.classList.remove('flex');
            });
        });
    </script>

    <!-- JS Dropdown -->
    <script>
        document.getElementById('loginDropdownBtn')?.addEventListener('click', function() {
            const dropdown = document.getElementById('loginDropdown');
            dropdown.classList.toggle('hidden');
        });

        window.addEventListener('click', function(e) {
            const btn = document.getElementById('loginDropdownBtn');
            const dropdown = document.getElementById('loginDropdown');
            if (!btn?.contains(e.target) && !dropdown?.contains(e.target)) {
                dropdown?.classList.add('hidden');
            }
        });
    </script>

    <!-- Loading Screen Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                let loader = document.getElementById('loading-screen');
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                    document.getElementById('content').classList.remove('hidden');
                }, 700);
            }, 1500); // Delay 1.5 detik biar animasinya kebaca
        });
    </script>

    <!-- AOS -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000
            })
        </script>
    @endif
    <!-- Script auto-open modal kalau ada error -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            @if ($errors->has('email') || $errors->has('password'))
                const modal = document.getElementById("customerLoginModal");
                modal?.classList.remove("hidden");
                modal?.classList.add("flex");
            @endif
        });
    </script>
    @stack('scripts') {{-- ini yang penting buat munculin script dari @push --}}
</body>

</html>
