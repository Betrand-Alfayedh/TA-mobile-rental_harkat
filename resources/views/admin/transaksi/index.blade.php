@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="text-2xl font-bold mb-6">Daftar Booking</h1>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4 flex gap-2">
            <a href="{{ route('admin.booking.create') }}"
                class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow">
                + Tambah Booking
            </a>

            <a href="{{ route('admin.laporan') }}"
                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-md shadow transition transform hover:scale-105">
                <i data-lucide="scroll-text" class="w-5 h-5"></i>
                Generate Laporan
            </a>
        </div>

        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-4 py-2">No</th>
                    <th class="border px-4 py-2">Pelanggan</th>
                    <th class="border px-4 py-2">Asal Kota</th>
                    <th class="border px-4 py-2">Jumlah Mobil</th>
                    <th class="border px-4 py-2">Jaminan</th>
                    <th class="border px-4 py-2">Uang Muka</th>
                    <th class="border px-4 py-2">Total Harga</th>
                    <th class="border px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr class="bg-white hover:bg-gray-50 cursor-pointer"
                        onclick="document.getElementById('detail-{{ $booking->id }}').classList.toggle('hidden')">
                        <td class="border px-4 py-2">{{ $loop->iteration }}</td>
                        <td class="border px-4 py-2">{{ $booking->user->name ?? '-' }}</td>
                        <td class="border px-4 py-2">{{ $booking->asal_kota_label }}</td>
                        <td class="border px-4 py-2">{{ $booking->details->count() }}</td>
                        <td class="border px-4 py-2">{{ $booking->jaminan_label }}</td>
                        <td class="border px-4 py-2">{{ $booking->uang_muka_rp }}</td>
                        <td class="border px-4 py-2">{{ $booking->total_harga_rp }}</td>
                        <td class="border px-4 py-2">{!! $booking->status_badge !!}</td>
                    </tr>

                    {{-- Row buat detail --}}
                    <tr id="detail-{{ $booking->id }}" class="hidden">
                        <td colspan="6" class="border px-4 py-2 bg-gray-50">
                            <table class="w-full border">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border px-2 py-1">Mobil</th>
                                        <th class="border px-2 py-1">Supir</th>
                                        <th class="border px-2 py-1">Tanggal</th>
                                        <th class="border px-2 py-1">Status Mobil</th>
                                        <th class="border px-2 py-1">Mulai Sewa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->details as $detail)
                                        <tr>
                                            <td class="border px-2 py-1">{{ $detail->mobil->masterMobil->nama ?? '-' }}
                                            </td>
                                            <td class="border px-2 py-1">
                                                <x-pakai-supir-label :pakaiSupir="$detail->pakai_supir" :supir="$detail->supir"
                                                    :detail="$detail" />
                                            </td>
                                            <td class="border px-2 py-1">
                                                {{ $detail->tanggal_mulai_format }} s/d
                                                {{ $detail->tanggal_selesai_format }}
                                            </td>
                                            <td class="border px-2 py-1">
                                                {{ $detail->status_label }}
                                            </td>
                                            <td class="border px-2 py-1">
                                                @if ($detail->mobil->status === \App\Models\Mobil::STATUS_DIBOOKING)
                                                    <form
                                                        action="{{ route('admin.booking.konfirmasiJemput', $detail->id) }}"
                                                        method="POST" class="konfirmasi-jemput-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="button"
                                                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded shadow konfirmasi-jemput-btn"
                                                            data-customer="{{ $booking->user->name ?? '-' }}"
                                                            data-nama="{{ $detail->mobil->masterMobil->nama ?? '-' }}">
                                                            ✔ Konfirmasi Jemput
                                                        </button>
                                                    </form>
                                                @else
                                                    <span
                                                        class="px-2 py-1 rounded {{ $detail->mobil->status_badge_class }}">
                                                        {{ $detail->mobil->status_text }}
                                                    </span>
                                                @endif
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
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.konfirmasi-jemput-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    let form = this.closest('form');
                    let url = form.action;
                    let csrfToken = form.querySelector('input[name="_token"]').value;
                    let namaMobil = this.dataset.nama;
                    let namaCustomer = this.dataset.customer;

                    Swal.fire({
                        title: 'Konfirmasi Jemput',
                        html: `Yakin customer <b>${namaCustomer}</b> sudah jemput mobil <b>${namaMobil}</b>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#10B981',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, konfirmasi!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(url, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({})
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        // Toast sukses
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: `Mobil ${namaMobil} berhasil dijemput oleh ${namaCustomer}!`,
                                            showConfirmButton: false,
                                            timer: 2000,
                                            timerProgressBar: true
                                        });

                                        // Update status span
                                        let statusSpan = btn.closest('td')
                                            .querySelector('span');
                                        if (statusSpan) {
                                            statusSpan.textContent = data
                                                .mobil_status_text;
                                            statusSpan.className =
                                                `px-2 py-1 rounded ${data.mobil_status_badge_class}`;
                                        }

                                        // Highlight row detail
                                        let detailRow = btn.closest('tr');
                                        detailRow.classList.add('bg-green-50');
                                        setTimeout(() => detailRow.classList.remove(
                                            'bg-green-50'), 1200);

                                        // Disable tombol supaya gak diklik lagi
                                        btn.disabled = true;
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops!',
                                            text: data.message ||
                                                'Terjadi kesalahan.',
                                        });
                                    }
                                })
                                .catch(err => console.error(err));
                        }
                    });
                });
            });
        });
    </script>
@endpush
