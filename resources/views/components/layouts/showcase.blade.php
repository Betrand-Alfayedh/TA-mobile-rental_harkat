@props(['mobils']) {{-- ini sekarang array MasterMobil --}}

<section class="py-16">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            @foreach ($mobils as $m)
                <div class="swiper-slide">
                    <div
                        class="backdrop-blur-xl bg-white/30 border border-white/20 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl hover:-translate-y-2 transform transition duration-500">
                        <!-- Gambar mobil -->
                        <div class="p-4">
                            @if ($m->mobils->count())
                                <img src="{{ asset('public/gambar_mobil/' . $m->mobils->first()->gambar) }}" alt="{{ $m->nama }}"
                                    class="w-full h-40 object-contain">
                            @else
                                <img src="{{ asset('images/no-car.png') }}" alt="{{ $m->nama }}"
                                    class="w-full h-40 object-contain">
                            @endif
                        </div>

                        <!-- Detail mobil -->
                        <div class="p-4 text-center">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                {{ $m->nama }}
                            </h3>

                            {{-- Harga ambil dari salah satu unit mobil --}}
                            @if ($m->mobils->count())
                                <p class="text-gray-600 text-sm mb-2">
                                    Rp {{ number_format($m->mobils->first()->harga_sewa, 0, ',', '.') }}/hari
                                </p>
                                <p class="text-gray-500 text-sm">
                                    Include Supir:
                                    <span class="font-medium text-green-600">
                                        Rp {{ number_format($m->mobils->first()->harga_all_in, 0, ',', '.') }}/hari
                                    </span>
                                </p>
                            @endif

                            {{-- Tombol booking --}}
                            @if (Auth::check() && Auth::user()->isRole('Customer'))
                                <a href="{{ route('booking.create', ['masterMobil' => $m->id]) }}"
                                    class="mt-4 inline-block bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-600 transition">
                                    Booking Sekarang
                                </a>
                            @else
                                <a href="#" id="openCustomerLoginModalMobile"
                                    class="mt-4 inline-block bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-600 transition">
                                    Booking Sekarang
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="swiper-pagination mt-8"></div>
    </div>
</section>

<script>
    const swiper = new Swiper(".mySwiper", {
        loop: true,
        centeredSlides: true,
        slidesPerView: "auto",
        spaceBetween: 20,
        initialSlide: 0,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        speed: 1200,
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        on: {
            slideChangeTransitionStart: function() {
                // reset semua slide
                this.slides.forEach(slide => {
                    slide.style.transform = "scale(0.8)";
                    slide.style.opacity = "0.4";
                });

                // ambil slide aktif
                let active = this.slides[this.activeIndex];
                if (active) {
                    active.style.transform = "scale(1)";
                    active.style.opacity = "1";
                }
            },
        },
        breakpoints: {
            640: {
                slidesPerView: 2
            },
            1024: {
                slidesPerView: 3
            },
        },
    });

    // trigger pertama kali biar langsung animasi aktif
    swiper.emit("slideChangeTransitionStart");
</script>

<style>
    .swiper-slide {
        transition: transform 0.6s ease, opacity 0.6s ease;
        transform: scale(0.8);
        opacity: 0.4;
    }

    .swiper-slide-active {
        transform: scale(1) !important;
        opacity: 1 !important;
    }

    .swiper-pagination {
        margin-top: 24px !important;
        /* kasih jarak atas */
        position: relative !important;
        /* biar tetep di bawah swiper */
    }
</style>
