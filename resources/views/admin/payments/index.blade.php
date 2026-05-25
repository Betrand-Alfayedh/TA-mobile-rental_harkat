@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="text-2xl font-bold mb-6">Daftar Pembayaran Customer</h1>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">
                {{ session('success') }}
            </div>
        @endif

        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-4 py-2">No</th>
                    <th class="border px-4 py-2">Booking ID</th>
                    <th class="border px-4 py-2">Pelanggan</th>
                    <th class="border px-4 py-2">Tanggal Booking</th>
                    <th class="border px-4 py-2">Tanggal Mulai Sewa</th>
                    <th class="border px-4 py-2">Jatuh Tempo</th>
                    <th class="border px-4 py-2">Jenis</th>
                    <th class="border px-4 py-2">Jumlah</th>
                    <th class="border px-4 py-2">Status</th>
                    <th class="border px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-4 py-2">{{ $loop->iteration }}</td>
                        <td class="border px-4 py-2">
                            #{{ $payment->booking->id }} <br>
                            <small>{{ $payment->booking_id }}
                                ({{ $payment->booking->user_id }} -
                                {{ $payment->booking->tanggal_booking }})
                            </small>
                        </td>
                        <td class="border px-4 py-2">{{ $payment->booking->user->name }}</td>
                        <td class="border px-4 py-2">{{ $payment->booking->tanggal_booking_format }}</td>

                        <td class="border px-4 py-2">
                            @foreach ($payment->booking->details as $detail)
                                <div>{{ $detail->tanggal_mulai_format }}</div>
                            @endforeach
                        </td>

                        <td class="border px-4 py-2">
                            {{ $payment->jatuh_tempo_format }}
                        </td>

                        <td class="border px-4 py-2 font-semibold">
                            {{ $payment->jenis_text }}
                        </td>
                        <td class="border px-4 py-2">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</td>
                        <td class="border px-4 py-2">
                            {{-- Status + tombol lihat bukti kalau ada --}}
                            @if ($payment->foto_bukti)
                                <a href="{{ asset('storage/' . $payment->foto_bukti) }}" target="_blank"
                                    class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Lihat Bukti
                                </a>
                                <br>
                            @endif
                            <span class="font-semibold">
                                {{ $payment->status_pembayaran_text }}
                            </span>
                        </td>
                        <td class="border px-4 py-2 space-x-2">
                            @if ($payment->status_pembayaran == 2)
                                {{-- Pending: admin bisa verifikasi / tolak --}}
                                <form action="{{ route('admin.pembayaran.verifikasi', $payment->id) }}" method="POST"
                                    class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                        class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                        Verifikasi
                                    </button>
                                </form>

                                <!-- Tombol tolak (buka modal) -->
                                <button type="button"
                                    onclick="document.getElementById('tolak-{{ $payment->id }}').showModal()"
                                    class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                    Tolak
                                </button>

                                <!-- Modal -->
                                <dialog id="tolak-{{ $payment->id }}" class="p-4 rounded-lg">
                                    <form method="POST" action="{{ route('admin.pembayaran.tolak', $payment->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <label class="block mb-2">Alasan penolakan:</label>
                                        <textarea name="catatan_admin" rows="3" class="w-full border rounded p-2"></textarea>

                                        <div class="mt-4 flex justify-end gap-2">
                                            <button type="submit"
                                                class="px-3 py-1 bg-red-600 text-white rounded">Tolak</button>

                                            <button type="button"
                                                onclick="document.getElementById('tolak-{{ $payment->id }}').close()"
                                                class="px-3 py-1 bg-gray-300 rounded">Batal</button>
                                        </div>
                                    </form>
                                </dialog>
                            @elseif ($payment->status_pembayaran == 1)
                                <span class="text-green-600 font-semibold">✅ Diverifikasi</span>
                            @elseif ($payment->status_pembayaran == 0)
                                <span class="text-red-600 font-semibold">❌ Ditolak / Belum Bayar</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border px-4 py-4 text-center text-gray-500">
                            Belum ada pembayaran
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
@endsection
