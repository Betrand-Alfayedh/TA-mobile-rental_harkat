@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')
    @php
        $kartu = [
            ['Total mobil', $totalMobil, 'car', 'bg-emerald-50 text-emerald-600', route('admin.mobil.index')],
            ['Mobil tersedia', $mobilTersedia, 'circle-check', 'bg-green-50 text-green-600', route('admin.mobil.index')],
            ['Mobil sedang disewa', $mobilDisewa, 'key-round', 'bg-amber-50 text-amber-600', route('admin.booking.index')],
            ['Total sopir', $totalSupir, 'users-round', 'bg-sky-50 text-sky-600', route('admin.supir.index')],
            ['Sopir siap', $supirSiap, 'user-check', 'bg-green-50 text-green-600', route('admin.supir.index')],
            ['Sopir bertugas', $supirBertugas, 'route', 'bg-amber-50 text-amber-600', route('admin.supir.index')],
        ];
        $pintas = [
            ['Buat pesanan sewa', 'Catat pesanan pelanggan yang datang langsung', 'plus-circle', route('admin.booking.create')],
            ['Cek pembayaran', 'Verifikasi bukti bayar yang masuk', 'wallet', route('admin.pembayaran.index')],
            ['Proses pengembalian', 'Catat mobil yang sudah kembali', 'undo-2', route('admin.pengembalian.index')],
            ['Rekap bulanan', 'Pesanan, pendapatan, dan armada', 'bar-chart-3', route('admin.rekap')],
        ];
    @endphp

    <div class="mb-6 rounded-2xl bg-gradient-to-br from-forest to-forest-soft p-6 text-white shadow-sm sm:p-8">
        <h1 class="!text-white text-2xl font-bold">Halo, {{ Auth::user()->name }}</h1>
        <p class="mt-1 text-sm text-white/75">Selamat datang di sistem manajemen Rental Mobil Harkat Yogyakarta.</p>
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Ringkasan armada</h2>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kartu as [$judul, $nilai, $ikon, $warna, $url])
            <a href="{{ $url }}"
                class="group flex items-center gap-4 rounded-2xl border border-[#E1E9E4] bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $warna }}">
                    <i data-lucide="{{ $ikon }}" class="h-6 w-6"></i>
                </span>
                <span>
                    <span class="block text-3xl font-bold leading-none text-gray-900">{{ $nilai }}</span>
                    <span class="mt-1 block text-sm text-gray-500">{{ $judul }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Pintasan</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($pintas as [$judul, $ket, $ikon, $url])
            <a href="{{ $url }}"
                class="rounded-2xl border border-[#E1E9E4] bg-white p-5 transition hover:border-leaf hover:shadow-md">
                <i data-lucide="{{ $ikon }}" class="mb-3 h-6 w-6 text-leaf"></i>
                <div class="font-semibold text-gray-900">{{ $judul }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $ket }}</div>
            </a>
        @endforeach
    </div>
@endsection
