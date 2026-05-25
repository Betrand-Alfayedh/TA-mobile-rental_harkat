<footer class="bg-gray-900 text-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 md:grid-cols-4 gap-8">

        <!-- Brand -->
        <div>
            <h2 class="text-xl font-bold mb-3"><span class="text-green-600">Harkat</span> RentCar</h2>
            <p class="mt-2 text-sm">
                Penyedia layanan rental mobil terpercaya di Yogyakarta. Memberikan pengalaman berkendara nyaman dan aman
                untuk semua pelanggan.
            </p>
        </div>

        {{-- Quick Links --}}
        <div>
            <h3 class="text-lg font-semibold text-white mb-3">Navigasi</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ url('/') }}" class="hover:text-green-400">Beranda</a></li>
                <li><a href="{{ route('mobil.index') }}" class="hover:text-green-400">Daftar Mobil</a></li>
                <li><a href="{{ route('booking.index') }}" class="hover:text-green-400">Booking</a></li>
                <li><a href="{{ url('/tentang') }}" class="hover:text-green-400">Tentang Kami</a></li>
                <li><a href="{{ url('/kontak') }}" class="hover:text-green-400">Kontak</a></li>
            </ul>
        </div>

        {{-- Informasi --}}
        <div>
            <h3 class="text-lg font-semibold text-white mb-3">Informasi</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ url('/syarat') }}" class="hover:text-green-400">Syarat & Ketentuan</a></li>
                <li><a href="{{ url('/privasi') }}" class="hover:text-green-400">Kebijakan Privasi</a></li>
                <li><a href="{{ url('/faq') }}" class="hover:text-green-400">FAQ</a></li>
            </ul>
        </div>

        <!-- We're here -->
        <div>
            <h3 class="text-base font-semibold mb-3">Hit us On</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-lg text-emerald-500"></i>
                    <span>Jl. KH Ali maksum, No.04, RT.001/RW.000, Ds. Palemsewu, Kel. Panggungharjo, Kec. Sewon, Kab.
                        Bantul, DIY 55188</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-brands fa-whatsapp text-xl text-emerald-500"></i>
                    <span>+62 852-2252-1995</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-brands fa-instagram text-xl text-emerald-500"></i>
                    <span>coming soon</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-brands fa-tiktok text-xl text-emerald-500"></i>
                    <span>@harkat_tourtravel</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Bottom Footer -->
    <div class="border-t border-gray-300 py-4 text-center text-xs text-gray-500">
        <a href="{{ auth('web')->check() ? url('/home') : url('/') }}" class="text-blue-600 hover:underline">about Harkat Rental</a> <br>
        &copy; {{ date('Y') }} Harkat Rent Car. All rights reserved.
    </div>
</footer>
