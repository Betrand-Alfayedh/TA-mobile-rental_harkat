@extends('layouts.admin')

@section('content')
    <div>
        <h2 class="text-2xl font-bold mb-4">Rekap Bulanan</h2>

        <form method="GET" class="mb-6 flex items-center gap-2">
            <input type="month" name="bulan" value="{{ $bulan }}" class="border rounded px-3 py-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Tampilkan</button>
        </form>

        @php $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6 text-center">
            @foreach (['total' => 'Total pesanan', 'booked' => 'Booked', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $k => $l)
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold">{{ $rekap['pesanan'][$k] }}</div>
                    <div class="text-xs text-gray-500">{{ $l }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="font-semibold mb-2">Pendapatan (pembayaran lunas)</h3>
                <table class="w-full text-sm">
                    <tr><td>Uang muka</td><td class="text-right">{{ $rp($rekap['pendapatan']['dp']) }}</td></tr>
                    <tr><td>Pelunasan</td><td class="text-right">{{ $rp($rekap['pendapatan']['pelunasan']) }}</td></tr>
                    <tr class="font-semibold border-t"><td>Total</td><td class="text-right">{{ $rp($rekap['pendapatan']['total']) }}</td></tr>
                    <tr class="text-gray-500"><td>Denda tercatat</td><td class="text-right">{{ $rp($rekap['pendapatan']['denda_tercatat']) }}</td></tr>
                </table>
            </div>
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="font-semibold mb-2">Armada</h3>
                <table class="w-full text-sm">
                    <tr><td>Unit disewa</td><td class="text-right">{{ $rekap['armada']['total_unit_disewa'] }}</td></tr>
                    <tr><td>Total hari sewa</td><td class="text-right">{{ $rekap['armada']['total_hari_sewa'] }}</td></tr>
                    <tr><td>Utilisasi</td><td class="text-right">{{ $rekap['armada']['utilisasi_persen'] }}%</td></tr>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Mobil</th>
                        <th class="px-4 py-3 text-left">Plat</th>
                        <th class="px-4 py-3 text-right">Jumlah sewa</th>
                        <th class="px-4 py-3 text-right">Total hari</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap['armada']['per_mobil'] as $m)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $m['nama'] ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $m['plat_nomor'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">{{ $m['jumlah_sewa'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $m['total_hari'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada sewa pada bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
