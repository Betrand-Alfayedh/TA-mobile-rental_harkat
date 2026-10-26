@extends('layouts.landing')

@php
    $mobil->loadMissing('masterMobil.tipe', 'images');
    $nama = $mobil->masterMobil->nama ?? $mobil->merk;
    $isCustomer = auth('web')->check() && auth('web')->user()->isRole('Customer');
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp

@section('title', $nama . ' | Harkat Rent Car')

@section('content')
    <div class="pt-24">
        <a href="{{ route('mobil.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-forest transition-colors duration-500 ease-spring hover:text-leaf">
            <x-line-icon name="arrow-left" class="h-4 w-4" /> Semua armada
        </a>

        <div class="mt-6 grid gap-10 lg:grid-cols-2 lg:gap-14">
            <div class="reveal">
                <div class="bezel">
                    <div class="bezel-core overflow-hidden">
                        <x-car-image :file="$mobil->gambar" :alt="$nama" class="h-80 sm:h-[26rem]" />
                    </div>
                </div>

                @if ($mobil->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-4 gap-3">
                        @foreach ($mobil->images as $img)
                            <img src="{{ asset('mobil_images_additionals/' . $img->image_path) }}" alt="{{ $nama }}"
                                loading="lazy" class="aspect-[4/3] w-full rounded-2xl bg-mint object-cover">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="reveal" style="--d: 100ms">
                @if ($mobil->masterMobil?->tipe)
                    <span class="inline-block rounded-full bg-mint px-3 py-1 text-xs font-semibold text-forest">
                        {{ $mobil->masterMobil->tipe->nama_tipe }}
                    </span>
                @endif
                <h1 class="mt-4 text-[clamp(2rem,4.5vw,3rem)] font-extrabold leading-[1.05] tracking-[-0.03em] text-forest">
                    {{ $nama }}
                </h1>
                <p class="mt-2 text-ink/55">{{ $mobil->merk }} &middot; {{ $mobil->tahun }}</p>

                <dl class="mt-8 divide-y divide-line rounded-2xl bg-white ring-1 ring-forest/10">
                    <div class="flex items-baseline justify-between gap-4 px-5 py-4">
                        <dt class="text-ink/60">Lepas kunci</dt>
                        <dd class="text-xl font-bold text-ink">{{ $rp($mobil->harga_sewa) }}<span class="text-sm font-medium text-ink/50"> /hari</span></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 px-5 py-4">
                        <dt class="text-ink/60">Dengan supir</dt>
                        <dd class="text-xl font-bold text-ink">{{ $rp($mobil->harga_all_in) }}<span class="text-sm font-medium text-ink/50"> /hari</span></dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm leading-relaxed text-ink/55">
                    Tarif dibulatkan per 24 jam. DP 50% dibayar saat booking, sisanya dilunasi saat sewa.
                </p>

                <div class="mt-8">
                    @if ($isCustomer)
                        <a href="{{ route('booking.create', ['mobil_id' => $mobil->id]) }}" class="btn btn-primary">
                            Booking sekarang <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                        </a>
                    @else
                        <a href="#" data-open-login class="btn btn-primary">
                            Booking sekarang <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
