{{--
    Kepala halaman dalam (booking, pesanan, pembayaran, dst.): pita hijau gelap, judul besar.
    Konten halaman ditarik ke atas (-mt-16) agar menimpa pita ini.
    <x-page-head title="..." subtitle="..." :back="['Semua armada', route('mobil.index')]" />
--}}
@props(['title', 'subtitle' => null, 'back' => null])

<section class="relative isolate overflow-hidden bg-forest-deep pb-28 pt-32 sm:pt-36">
    <div class="absolute -right-32 -top-28 -z-10 h-[30rem] w-[30rem] rounded-full bg-[radial-gradient(circle,rgba(22,163,74,0.30),transparent_65%)]"></div>
    <div class="absolute -bottom-40 left-1/4 -z-10 h-[22rem] w-[22rem] rounded-full bg-[radial-gradient(circle,rgba(20,90,65,0.55),transparent_65%)]"></div>

    <div class="mx-auto max-w-6xl px-6">
        @if ($back)
            <a href="{{ $back[1] }}"
                class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-white/60 transition-colors duration-500 ease-spring hover:text-white">
                <x-line-icon name="arrow-left" class="h-4 w-4" /> {{ $back[0] }}
            </a>
        @endif
        <h1 class="reveal max-w-3xl text-[clamp(2rem,4.6vw,3.2rem)] font-extrabold leading-[1.05] tracking-[-0.03em] text-white">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="reveal mt-4 max-w-xl text-[17px] leading-relaxed text-white/65" style="--d: 100ms">{{ $subtitle }}</p>
        @endif
    </div>
</section>
