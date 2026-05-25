@extends('layouts.landing')

@section('content')
    <div class="max-w-4xl mx-auto px-4 pt-20">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📋 Pesanan Aktif</h1>

        @if ($bookings->isEmpty())
            <p class="text-gray-600">Belum ada pesanan aktif.</p>
        @else
            <table class="w-full border-collapse shadow rounded overflow-hidden">
                <thead>
                    <tr class="bg-green-600 text-white">
                        <th class="border px-4 py-2">No</th>
                        <th class="border px-4 py-2">Jumlah Mobil</th>
                        <th class="border px-4 py-2">Uang Muka</th>
                        <th class="border px-4 py-2">Total</th>
                        <th class="border px-4 py-2">Status</th>
                        <th class="border px-4 py-2">Jatuh Tempo</th>
                        <th class="border px-4 py-2">Aksi</th> {{-- ✅ kolom baru --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr class="bg-white hover:bg-green-50 cursor-pointer"
                            onclick="document.getElementById('detail-{{ $booking->id }}').classList.toggle('hidden')">
                            <td class="border px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="border px-4 py-2">{{ $booking->details->count() }}</td>
                            <td class="border px-4 py-2">{{ $booking->uang_muka_rp }}</td>
                            <td class="border px-4 py-2">{{ $booking->total_harga_rp }}</td>
                            <td class="border px-4 py-2">{!! $booking->status_badge !!}</td>
                            <td class="border px-4 py-2">{{ $booking->pembayaranDp->jatuh_tempo }}</td>
                            <td class="border px-4 py-2 text-center">
                                @switch($booking->pembayaranDp->status_pembayaran ?? null)
                                    @case(0)
                                        @if ($booking->pembayaranDp->catatan_admin)
                                            <p class="text-red-600 mb-2">
                                                ❌ Pembayaran ditolak: {{ $booking->pembayaranDp->catatan_admin }}
                                            </p>
                                        @endif
                                        <a href="{{ route('pembayaran.show', $booking->id) }}"
                                            class="bg-red-600 hover:bg-yellow-700 text-white text-sm px-3 py-1 rounded shadow">
                                            Bayar DP
                                        </a>
                                    @break

                                    @case(2)
                                        <span class="text-yellow-600 font-semibold">Menunggu Verifikasi</span>
                                    @break

                                    @case(1)
                                        <span class="text-green-600 font-semibold">Sudah Bayar</span>
                                    @break

                                    @default
                                        <span class="text-gray-500">Belum ada data pembayaran</span>
                                @endswitch

                            </td>
                        </tr>

                        {{-- Detail per mobil --}}
                        <tr id="detail-{{ $booking->id }}" class="hidden">
                            <td colspan="7" class="border px-4 py-2 bg-green-50">
                                <table class="w-full border">
                                    <thead>
                                        <tr class="bg-green-100 text-green-800">
                                            <th class="border px-2 py-1">Mobil</th>
                                            <th class="border px-2 py-1">Supir</th>
                                            <th class="border px-2 py-1">Tanggal</th>
                                            <th class="border px-2 py-1">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($booking->details as $detail)
                                            <tr>
                                                <td class="border px-2 py-1">{{ $detail->mobil->masterMobil->nama ?? '-' }}</td>
                                                <td class="border px-2 py-1">
                                                    {{ $detail->supir ? $detail->supir->user->name : 'Tanpa Supir' }}
                                                </td>
                                                <td class="border px-2 py-1">
                                                    {{ $detail->tanggal_mulai_format }} s/d
                                                    {{ $detail->tanggal_selesai_format }}
                                                </td>
                                                <td class="border px-2 py-1">
                                                    {{ $detail->status_label }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
