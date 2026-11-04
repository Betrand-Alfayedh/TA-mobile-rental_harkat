@extends('layouts.admin')

@section('content')
    @php
        $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $badge = [
            0 => ['Belum dibayar', 'bg-gray-100 text-gray-700'],
            1 => ['Lunas', 'bg-green-100 text-green-700'],
            2 => ['Menunggu verifikasi', 'bg-amber-100 text-amber-700'],
        ];
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl">Pembayaran</h1>
        <p class="mt-1 text-sm text-gray-500">Uang muka (DP) dan pelunasan dari setiap pesanan sewa.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto rounded-2xl">
        <table>
            <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Jatuh tempo</th>
                    <th>Jenis</th>
                    <th class="!text-right">Jumlah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $batal = $payment->booking->status == \App\Models\Booking::STATUS_CANCELED;
                        $st = (int) $payment->status_pembayaran;
                    @endphp
                    <tr>
                        <td>
                            <div class="font-semibold text-gray-900">#{{ $payment->booking->id }}</div>
                            <div class="whitespace-nowrap text-xs text-gray-500">{{ $payment->booking->tanggal_booking_format }}</div>
                        </td>
                        <td class="min-w-[8rem]">{{ $payment->booking->user->name }}</td>
                        <td class="whitespace-nowrap">{{ $payment->jatuh_tempo_format }}</td>
                        <td>
                            <span class="font-medium">{{ $payment->jenis_text }}</span>
                        </td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ $rp($payment->jumlah) }}</td>
                        <td>
                            @if ($batal && $st !== 1)
                                <span class="inline-block whitespace-nowrap rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Pesanan dibatalkan</span>
                            @else
                                <span class="inline-block whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[$st][1] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $badge[$st][0] ?? $payment->status_pembayaran_text }}
                                </span>
                            @endif
                            @if ($payment->foto_bukti)
                                <a href="{{ asset('storage/' . $payment->foto_bukti) }}" target="_blank"
                                    class="mt-1 block whitespace-nowrap text-xs font-medium text-leaf-dark hover:underline">Lihat bukti bayar</a>
                            @endif
                        </td>
                        <td>
                            @if ($st == 2)
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('admin.pembayaran.verifikasi', $payment->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit"
                                            class="whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">Verifikasi</button>
                                    </form>
                                    <button type="button"
                                        onclick="document.getElementById('tolak-{{ $payment->id }}').showModal()"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Tolak</button>
                                </div>

                                <dialog id="tolak-{{ $payment->id }}" class="w-full max-w-md p-6">
                                    <form method="POST" action="{{ route('admin.pembayaran.tolak', $payment->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <h3 class="mb-3 text-lg font-bold">Tolak pembayaran</h3>
                                        <label class="mb-1 block text-sm text-gray-600">Alasan penolakan</label>
                                        <textarea name="catatan_admin" rows="3" class="w-full p-2" required></textarea>
                                        <div class="mt-4 flex justify-end gap-2">
                                            <button type="button"
                                                onclick="document.getElementById('tolak-{{ $payment->id }}').close()"
                                                class="rounded-lg bg-gray-100 px-4 py-2 text-sm hover:bg-gray-200">Batal</button>
                                            <button type="submit"
                                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Tolak</button>
                                        </div>
                                    </form>
                                </dialog>
                            @elseif ($st == 0 && !$batal)
                                <form action="{{ route('admin.pembayaran.lunas', $payment->id) }}" method="POST"
                                    class="flex flex-wrap items-center gap-2"
                                    onsubmit="return confirm('Tandai pembayaran ini LUNAS?')">
                                    @csrf
                                    @method('PUT')
                                    <select name="metode_pembayaran" class="px-2 py-1.5 text-xs">
                                        <option value="1">Tunai</option>
                                        <option value="2">Transfer</option>
                                        <option value="3">QRIS</option>
                                    </select>
                                    <button type="submit"
                                        class="whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">Tandai lunas</button>
                                </form>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="!py-10 text-center text-gray-500">Belum ada pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
