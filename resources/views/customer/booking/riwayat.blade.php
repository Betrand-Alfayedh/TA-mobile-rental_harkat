@extends('layouts.landing')

@section('content')
    <div class="max-w-4xl mx-auto px-4 pt-20">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📋 Pesanan Aktif</h1>

        @if ($riwayats->isEmpty())
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayats as $riwayat)
                        <tr class="bg-white hover:bg-green-50 cursor-pointer"
                            onclick="document.getElementById('detail-{{ $riwayat->id }}').classList.toggle('hidden')">
                            <td class="border px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="border px-4 py-2">{{ $riwayat->details->count() }}</td>
                            <td class="border px-4 py-2">{{ $riwayat->uang_muka_rp }}</td>
                            <td class="border px-4 py-2">{{ $riwayat->total_harga_rp }}</td>
                            <td class="border px-4 py-2">{!! $riwayat->status_badge !!}</td>
                            <td class="border px-4 py-2">{{ $riwayat->pembayaranDp->jatuh_tempo }}</td>
                        </tr>

                        {{-- Detail per mobil --}}
                        <tr id="detail-{{ $riwayat->id }}" class="hidden">
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
                                        @foreach ($riwayat->details as $detail)
                                            <tr>
                                                <td class="border px-2 py-1">{{ $detail->mobil->nama_mobil ?? '-' }}</td>
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
