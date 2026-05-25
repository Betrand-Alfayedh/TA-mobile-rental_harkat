@extends('layouts.landing')

@section('content')
    <div class="max-w-xl mx-auto px-4 pt-20">
        <h1 class="text-2xl font-bold mb-6">💳 Bayar DP Booking</h1>

        {{-- Info Rekening (Mandiri theme) --}}
        <div class="mb-6">
            <p class="text-gray-700 mb-2">Transfer DP ke rekening berikut:</p>

            <div class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg p-4 shadow">
                <div class="flex items-center gap-4">
                    {{-- Logo Mandiri --}}
                    <img src="{{ asset('images/bank-mandiri.png') }}" alt="Mandiri" class="w-20 h-auto rounded-md shadow-sm">

                    <div>
                        <p class="font-semibold text-amber-700">Bank Mandiri</p>
                        {{-- clickable nomor rekening (auto-copy) --}}
                        <p id="rek-number" class="text-lg font-mono tracking-wider select-all cursor-pointer"
                            title="Klik untuk menyalin" onclick="copyRek()">
                            1370016710069
                        </p>
                        <p class="text-sm text-gray-600">a.n HARKATr</p>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2">
                    {{-- Salin tombol --}}
                    <button onclick="copyRek()"
                        class="flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-md text-sm shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16h8M8 12h8m-5-8h5a2 2 0 012 2v12a2 2 0 01-2 2h-5" />
                        </svg>
                        Copy
                    </button>

                    {{-- Optional: show small hint --}}
                    <span class="text-xs text-gray-500">Klik nomor atau tombol untuk salin</span>
                </div>
            </div>
        </div>

        {{-- Detail Booking --}}
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <p><span class="font-semibold">Uang Muka:</span> {{ $booking->uang_muka_rp }}</p>
            <p><span class="font-semibold">Jatuh Tempo:</span> {{ $pembayaran->jatuh_tempo ?? '-' }}</p>
        </div>

        @if ($pembayaran->status_pembayaran == 0)
            {{-- Form Upload Bukti --}}
            <form action="{{ route('pembayaran.upload', $booking->id) }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf
                <div>
                    <label class="block font-medium text-gray-700">Upload Bukti Pembayaran</label>
                    <input type="file" name="bukti_bayar" class="mt-2 block w-full border rounded p-2">
                    @error('bukti_bayar')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700">
                    Upload & Konfirmasi
                </button>
            </form>
        @elseif($pembayaran->status_pembayaran == 2)
            <p class="text-yellow-600 font-semibold">⚠️ Bukti bayar sudah dikirim, tunggu verifikasi admin.</p>
        @elseif($pembayaran->status_pembayaran == 1)
            <p class="text-green-600 font-semibold">✅ Pembayaran sudah diverifikasi.</p>
        @endif
    </div>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyRek() {
            const rekElem = document.getElementById("rek-number");
            const rek = rekElem.innerText.trim();

            // fallback jika clipboard API gak tersedia
            if (!navigator.clipboard) {
                // create temporary input to copy
                const tmp = document.createElement('input');
                tmp.value = rek;
                document.body.appendChild(tmp);
                tmp.select();
                try {
                    document.execCommand('copy');
                    tmp.remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        text: 'Nomor rekening ' + rek + ' sudah dicopy.',
                        showConfirmButton: false,
                        timer: 1600
                    });
                } catch (err) {
                    tmp.remove();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Tidak bisa menyalin ke clipboard.',
                    });
                }
                return;
            }

            navigator.clipboard.writeText(rek).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disalin!',
                    text: 'Nomor rekening ' + rek + ' sudah dicopy.',
                    showConfirmButton: false,
                    timer: 1600
                });
            }).catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Tidak bisa menyalin ke clipboard.',
                });
            });
        }
    </script>
@endsection
