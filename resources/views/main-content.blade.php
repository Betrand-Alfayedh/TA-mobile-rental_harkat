{{-- resources/views/landing.blade.php --}}
@extends('layouts.landing')

@section('hero')
    {{-- HERO: full width background image + fade bottom --}}
    <section class="relative w-full h-[100vh] bg-cover bg-center"
        style="background-image: url('{{ asset('images/hero.jpg') }}');">

        {{-- overlay putih semi transparan biar teks kebaca --}}
        <div class="absolute inset-0 bg-white/40"></div>

        {{-- fade transparan di bawah --}}
        <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-black to-transparent"></div>

        {{-- text content --}}
        <div class="relative z-10 flex items-center justify-center h-full text-center px-4">
            <div class="max-w-3xl">
                <span class="hidden md:block text-6xl font-bold text-green-700 tracking-wide">
                    Harkat <span class="text-gray-800">Rent Car</span>
                </span>
                <p class="text-lg md:text-xl text-gray-700 mb-8 mt-6">
                    Nikmati perjalanan yang aman, nyaman, dan terjangkau bersama Harkat Yogyakarta.
                    Booking online kapan pun kamu butuh mobil!
                </p>

                @if (Auth::check() && Auth::user()->isRole('Customer'))
                    <a href="" id="btnBooking"
                        class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition">
                        Booking Sekarang
                    </a>
                @else
                    <a href="#" id="openCustomerLoginModalMobile"
                        class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition">
                        Booking Sekarang
                    </a>
                @endif
            </div>
        </div>
    </section>
@endsection

@section('content')
    {{-- PILIHAN ARMADA --}}
    <section class="py-20">
        <div class="container mx-auto text-center mb-8" data-aos="fade-down">
            <h2 class="text-3xl md:text-4xl font-bold">Pilihan Armada Tersedia</h2>
        </div>
        <x-layouts.showcase :mobils="$mobils" />
    </section>
    <!-- Section Info + Jam Operasional -->
    <section class="py-10">
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-10">

            <!-- Kiri: Deskripsi + Button -->
            <div class="flex-1" data-aos="fade-right">
                <p class="uppercase tracking-wide text-sm text-gray-500 font-semibold">
                    Harkat Rental – Solusi Transportasi Andal!
                </p>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2">
                    Cari kendaraan nyaman untuk perjalanan Anda?
                </h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    <span class="font-bold">Harkat Rental</span> siap memenuhi kebutuhan transportasi Anda!
                    Kami menyediakan berbagai pilihan mobil dengan kondisi prima, harga terjangkau,
                    dan layanan profesional, baik untuk penggunaan harian, perjalanan bisnis,
                    maupun liburan keluarga.
                </p>
                <a href="#kontak"
                    class="inline-block mt-6 px-5 py-2 border border-green-700 text-green-500 rounded-full hover:bg-green-700 hover:text-white transition">
                    Hubungi Kami!
                </a>
            </div>

            <!-- Kanan: Jam Operasional -->
            <div class="flex-1 max-w-sm w-full">
                <div class="flex-1" data-aos="fade-left"> <img src="{{ asset('images/carFinance.gif') }}"
                        alt="Ilustrasi Mobil" class="w-full max-w-md mx-auto drop-shadow-lg" loading="lazy"> </div>
            </div>

        </div>
    </section>
    <!-- Features -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Why Choose Harkat RentCar?</h2>
                <p class="text-xl text-gray-600">Experience the difference with our premium service</p>
            </div>

            <!-- Grid untuk 4 fitur -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="p-6 bg-white rounded-2xl shadow hover:shadow-lg transition text-center" data-aos="fade-right">
                    <div
                        class="w-16 h-16 bg-green-100 text-green-600 flex items-center justify-center rounded-full mx-auto mb-4 text-3xl">
                        🚗
                    </div>
                    <h3 class="font-bold text-lg">Premium Fleet</h3>
                    <p class="text-gray-500 mt-2">Latest model vehicles maintained to the highest standards</p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 bg-white rounded-2xl shadow hover:shadow-lg transition text-center" data-aos="fade-right">
                    <div
                        class="w-16 h-16 bg-green-100 text-green-600 flex items-center justify-center rounded-full mx-auto mb-4 text-3xl">
                        📱
                    </div>
                    <h3 class="font-bold text-lg">Easy Booking</h3>
                    <p class="text-gray-500 mt-2">Simple online booking process in just a few clicks</p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 bg-white rounded-2xl shadow hover:shadow-lg transition text-center" data-aos="fade-left">
                    <div
                        class="w-16 h-16 bg-green-100 text-green-600 flex items-center justify-center rounded-full mx-auto mb-4 text-3xl">
                        💰
                    </div>
                    <h3 class="font-bold text-lg">Best Prices</h3>
                    <p class="text-gray-500 mt-2">Competitive rates with no hidden fees or surprises</p>
                </div>

                <!-- Feature 4 -->
                <div class="p-6 bg-white rounded-2xl shadow hover:shadow-lg transition text-center" data-aos="fade-left">
                    <div
                        class="w-16 h-16 bg-green-100 text-green-600 flex items-center justify-center rounded-full mx-auto mb-4 text-3xl">
                        🕒
                    </div>
                    <h3 class="font-bold text-lg">24/7 Support</h3>
                    <p class="text-gray-500 mt-2">Round-the-clock customer service for your peace of mind</p>
                </div>

            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        document.getElementById("btnBooking")?.addEventListener("click", async (e) => {
            e.preventDefault(); // stop reload / default action

            try {
                const res = await fetch("{{ route('check.profile') }}");
                const data = await res.json();

                if (data.status === "unauthenticated") {
                    window.location.href = "{{ route('google.login') }}";
                } else if (data.status === "incomplete") {
                    openProfileModal(); // fungsi ini dari layouts.landing
                } else if (data.status === "complete") {
                    window.location.href = data.redirect; // => booking.create
                }
            } catch (err) {
                console.error("Gagal cek profile:", err);
                alert("Terjadi kesalahan. Coba lagi nanti ya.");
            }
        });
    </script>
@endpush
