@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')
    @php
        // [judul, nilai, keterangan, tautan, teks tautan]
        $kartu = [
            ['Total mobil', $totalMobil, 'unit terdaftar', route('admin.mobil.index'), 'Lihat unit'],
            ['Mobil tersedia', $mobilTersedia, 'siap disewa', route('admin.mobil.index'), 'Lihat unit'],
            ['Mobil disewa', $mobilDisewa, 'sedang di jalan', route('admin.booking.index'), 'Lihat pesanan'],
            ['Total sopir', $totalSupir, 'sopir terdaftar', route('admin.supir.index'), 'Lihat sopir'],
            ['Sopir siap', $supirSiap, 'menunggu tugas', route('admin.supir.index'), 'Lihat sopir'],
            ['Sopir bertugas', $supirBertugas, 'sedang mengantar', route('admin.supir.index'), 'Lihat sopir'],
        ];
        $pintas = [
            ['Buat pesanan sewa', 'plus', route('admin.booking.create')],
            ['Cek pembayaran', 'wallet', route('admin.pembayaran.index')],
            ['Proses pengembalian', 'undo-2', route('admin.pengembalian.index')],
            ['Rekap bulanan', 'bar-chart-3', route('admin.rekap')],
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl">Selamat datang, {{ Auth::user()->name }}</h1>
            <p class="mt-1 text-sm text-hk-mute">Ringkasan armada dan pesanan hari ini.</p>
        </div>
        <a href="{{ route('admin.booking.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-hk-ink px-4 py-2.5 text-sm font-medium text-white hover:bg-black">
            <i data-lucide="plus" class="h-4 w-4"></i> Buat pesanan sewa
        </a>
    </div>

    {{-- Angka utama --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kartu as [$judul, $nilai, $ket, $url, $teksTautan])
            <div class="panel">
                <div class="panel-card p-4">
                    <div class="label">{{ $judul }}</div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="mono text-3xl font-semibold tracking-tight">{{ $nilai }}</span>
                        <span class="text-sm text-hk-mute">{{ $ket }}</span>
                    </div>
                </div>
                <a href="{{ $url }}" class="flex items-center justify-between px-3 pb-1.5 pt-2.5 text-xs text-hk-mute hover:text-hk-ink">
                    <span>{{ $teksTautan }}</span>
                    <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        {{-- Pesanan terbaru --}}
        <div class="panel min-w-0 lg:col-span-2">
            <div class="flex items-center justify-between pr-2">
                <span class="label !pb-2.5">Pesanan terbaru</span>
                <a href="{{ route('admin.booking.index') }}" class="text-xs text-hk-mute hover:text-hk-ink">Lihat semua</a>
            </div>
            <div class="panel-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table style="box-shadow:none;border-radius:0">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($terbaru as $b)
                                <tr>
                                    <td class="mono text-hk-mute">#{{ $b->id }}</td>
                                    <td class="font-medium">{{ $b->user->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap text-hk-mute">{{ $b->tanggal_booking_format }}</td>
                                    <td class="mono whitespace-nowrap">{{ $b->total_harga_rp }}</td>
                                    <td>{!! $b->status_badge !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="!py-10 text-center text-hk-mute">Belum ada pesanan. Buat pesanan pertama dari tombol di atas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Perlu tindakan + pintasan --}}
        <div class="space-y-6">
            <div class="panel">
                <span class="label">Perlu tindakan</span>
                <a href="{{ route('admin.pembayaran.index') }}" class="panel-card flex items-center justify-between p-4 hover:border-hk-ink/30">
                    <div>
                        <div class="mono text-2xl font-semibold">{{ $menungguVerifikasi }}</div>
                        <div class="text-sm text-hk-mute">bukti bayar menunggu verifikasi</div>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-hk-mute"></i>
                </a>
                <a href="{{ route('admin.booking.index', ['status' => 1]) }}" class="mt-1.5 flex items-center justify-between px-3 py-2 text-xs text-hk-mute hover:text-hk-ink">
                    <span>Unit bersopir belum ditugaskan</span>
                    <span class="mono text-hk-ink">{{ $perluPenugasan }}</span>
                </a>
                <div class="flex items-center justify-between px-3 py-2 text-xs text-hk-mute">
                    <span>Pesanan sedang berjalan</span>
                    <span class="mono text-hk-ink">{{ $pesananBerjalan }}</span>
                </div>
            </div>

            <div class="panel">
                <span class="label">Pintasan</span>
                <div class="panel-card divide-y divide-hk-line">
                    @foreach ($pintas as [$judul, $ikon, $url])
                        <a href="{{ $url }}" class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-hk-panel first:rounded-t-xl last:rounded-b-xl">
                            <i data-lucide="{{ $ikon }}" class="h-4 w-4 text-hk-mute"></i>
                            <span class="flex-1">{{ $judul }}</span>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-hk-mute"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
