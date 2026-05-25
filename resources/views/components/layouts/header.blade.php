<header class="px-6 py-4 bg-white shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        {{-- Logo --}}
        <a href="{{ auth('web')->check() ? url('/home') : url('/') }}" class="flex items-center gap-2">
            {{-- Desktop & Mobile logo --}}
            <img src="{{ asset('images/logo.png') }}" alt="Harkat Rent Car" class="h-8 w-auto hidden md:block">

            {{-- Desktop Logo (text) --}}
            <span class="hidden md:block text-2xl font-bold text-green-700 tracking-wide">
                Harkat <span class="text-gray-800">Rent Car</span>
            </span>

            {{-- Mobile Logo (image) --}}
            <img src="{{ asset('images/logo-text.png') }}" alt="Harkat Rent Car" class="h-10 w-auto block md:hidden">
        </a>

        {{-- Desktop Nav --}}
        <nav class="hidden md:flex items-center space-x-6">
            <a href="{{ route('mobil.index') }}" class="text-gray-700 font-medium hover:text-green-700">Armada Kami</a>

            @auth('web')
                <a href="{{ route('booking.index') }}" class="text-gray-700 font-medium hover:text-green-700">Pesanan</a>
                <a href="{{ route('riwayat.index') }}" class="text-gray-700 font-medium hover:text-green-700">Riwayat</a>

                <a href="{{ route('profile.index') }}"
                    class="text-gray-700 font-semibold hover:text-green-700 hover:underline transition">
                    👋 Hai, {{ auth('web')->user()->name }}
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white font-semibold text-sm px-4 py-2 rounded-md transition duration-200 shadow">
                        Logout
                    </button>
                </form>
            @else
                <a href="#" id="openCustomerLoginModal"
                    class="text-gray-700 font-medium hover:text-green-700">Pesanan</a>
                <a href="#" id="openCustomerLoginModal"
                    class="text-gray-700 font-medium hover:text-green-700">Riwayat</a>

                <div class="relative inline-block text-left">
                    <button id="loginDropdownBtn"
                        class="text-green-700 font-semibold hover:text-green-900 transition duration-150 text-sm">
                        Login ▼
                    </button>
                    <div id="loginDropdown"
                        class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden z-50 text-sm">
                        <a href="#" id="openCustomerLoginModalDesktop"
                            class="block px-4 py-3 text-gray-700 hover:bg-green-50 transition">🔑 Login</a>
                        <a href="{{ route('login.form', ['role' => 'supir']) }}"
                            class="block px-4 py-3 text-gray-700 hover:bg-green-50 transition">🚗 Login Supir</a>
                    </div>
                </div>
            @endauth
        </nav>

        {{-- Mobile Greeting + Menu Button --}}
        <div class="flex items-center gap-3 md:hidden">
            @auth('web')
                <a href="{{ route('profile.index') }}"
                    class="text-gray-700 font-semibold text-sm hover:text-green-700 transition">
                    👋 Hai, {{ auth('web')->user()->name }}
                </a>
            @endauth
            <button id="mobileMenuBtn" class="text-green-700 focus:outline-none text-2xl">☰</button>
        </div>
    </div>

    {{-- Mobile Nav --}}
    <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-200 mt-4">
        <a href="{{ route('mobil.index') }}" class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">
            Armada Kami
        </a>

        @auth('web')
            <a href="{{ route('booking.index') }}"
                class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">Pesanan</a>
            <a href="{{ route('riwayat.index') }}"
                class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">Riwayat</a>

            {{-- Logout Mobile --}}
            <form method="POST" action="{{ route('logout') }}" class="px-6 pb-4">
                @csrf
                <button type="submit"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold text-sm px-4 py-2 rounded-md transition duration-200 shadow">
                    Logout
                </button>
            </form>
        @else
            <a href="#" id="openCustomerLoginModalMobile"
                class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">Pesanan</a>
            <a href="#" id="openCustomerLoginModalMobile"
                class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">Riwayat</a>
            <a href="#" id="openCustomerLoginModalMobile"
                class="block px-4 py-3 text-gray-700 hover:bg-green-50 transition">🔑 Login</a>
            <a href="{{ route('login.form', ['role' => 'supir']) }}"
                class="block px-6 py-3 text-gray-700 hover:bg-green-50 transition">🚗 Login Supir</a>
        @endauth
    </div>

    {{-- Mobile Menu Toggle Script --}}
    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        window.addEventListener('click', function(e) {
            if (!document.getElementById('loginDropdownBtn')?.contains(e.target)) {
                document.getElementById('loginDropdown')?.classList.add('hidden');
            }
        });
    </script>
</header>
