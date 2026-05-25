@extends('layouts.landing')

@section('content')
    <div class="max-w-3xl mx-auto px-4 pt-20">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📅 Form Booking Mobil</h1>

        {{-- error alert --}}
        @if ($errors->any())
            <div class="text-red-500 mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>⚠️ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('booking.store') }}" method="POST" id="bookingForm">
            @csrf

            {{-- wrapper slider --}}
            <div class="overflow-hidden relative">
                <div id="mobils-wrapper" class="flex transition-transform duration-500 ease-in-out"></div>
            </div>

            {{-- indicator --}}
            <div id="indicator" class="flex justify-center mt-3 gap-2"></div>
            <div class="flex justify-between mt-4">
                <button type="button" id="prev-mobil" class="bg-gray-500 text-white px-4 py-2 rounded">⬅ Prev</button>
                <button type="button" id="add-mobil" class="bg-green-500 text-white px-4 py-2 rounded">+ Tambah
                    Mobil</button>
                <button type="button" id="next-mobil" class="bg-gray-500 text-white px-4 py-2 rounded">Next ➡</button>
            </div>

            <hr class="my-4">

            {{-- nama penyewa --}}
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Atas Nama</label>
                <input type="text" class="w-full border rounded p-2" value="{{ $user->name }}" readonly>
            </div>

            {{-- asal kota --}}
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Asal Kota</label>
                <select name="asal_kota" id="asalKota" class="w-full border rounded p-2" required>
                    <option value="">-- Pilih Asal Kota --</option>
                    <option value="1" {{ $asal_kota_booking == 1 ? 'selected' : '' }}>Warga Yogyakarta</option>
                    <option value="2" {{ $asal_kota_booking == 2 ? 'selected' : '' }}>Lainnya...</option>
                </select>
            </div>

            {{-- nama kota (muncul kalau asal_kota = 2) --}}
            <div class="mb-4 {{ $asal_kota_booking == 2 ? '' : 'hidden' }}" id="namaKotaWrapper">
                <label class="block mb-1 font-semibold">Nama Kota</label>
                <input type="text" name="nama_kota" class="w-full border rounded p-2" value="{{ $nama_kota_booking }}">
            </div>

            {{-- jaminan --}}
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Jaminan</label>
                <select name="jaminan" id="jaminan" class="w-full border rounded p-2" required>
                    <option value="">-- Pilih Jaminan --</option>
                    <option value="1" {{ $jaminan_default == 1 ? 'selected' : '' }}>KTP/Passport</option>
                    <option value="2" {{ $jaminan_default == 2 ? 'selected' : '' }}>KTP & Motor (untuk warga lokal)
                    </option>
                </select>
            </div>

            {{-- uang muka --}}
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Uang Muka (50%)</label>
                <input type="text" id="uangMuka" class="w-full border rounded p-2 bg-gray-100" readonly>
            </div>

            {{-- total harga semua --}}
            <div class="mb-4">
                <label class="block mb-1 font-semibold">💰 Total Harga Semua Mobil</label>
                <input type="text" name="total_harga" id="totalHarga" class="w-full border rounded p-2 bg-gray-100"
                    readonly>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Booking Sekarang
            </button>
        </form>
    </div>

    {{-- TEMPLATE (hidden) --}}
    <div id="mobil-template" class="hidden">
        <div class="mobil-item w-full flex-shrink-0 p-4 border rounded bg-white">
            <div class="flex gap-2 mb-2">
                <div>
                    <label class="block mb-1 font-semibold">📆 Tanggal & Jam Sewa</label>
                    <input type="datetime-local" name="mobils[0][tanggal_sewa]"
                        class="w-full border rounded p-2 tanggal-sewa" required>
                </div>
                <div>
                    <label class="block mb-1 font-semibold">📆 Tanggal & Jam Kembali</label>
                    <input type="datetime-local" name="mobils[0][tanggal_kembali]"
                        class="w-full border rounded p-2 tanggal-kembali" required>
                </div>
            </div>

            <div class="mb-4">
                <button type="button" class="check-mobils bg-green-500 text-white px-4 py-2 rounded">
                    Cek Mobil Tersedia
                </button>
            </div>

            <div class="mb-2">
                <label class="block mb-1 font-semibold">Pilih Mobil</label>
                <select name="mobils[0][mobil_id]" class="w-full border rounded p-2 mobil-select" required>
                    <option value="">-- Pilih Mobil --</option>
                    @foreach ($mobils as $mobil)
                        <option value="{{ $mobil->id }}" data-harga="{{ $mobil->harga_sewa }}"
                            data-harga-allin="{{ $mobil->harga_all_in }}">
                            {{ $mobil->masterMobil->nama }} - Rp{{ number_format($mobil->harga_sewa, 0, ',', '.') }}/hari |
                            Include Supir: Rp{{ number_format($mobil->harga_all_in ?? $mobil->harga_sewa, 0, ',', '.') }}/hari
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-2 flex items-center gap-2">
                <input type="hidden" class="hidden-pakai-supir" name="mobils[0][pakai_supir]" value="0">
                <input type="checkbox" class="pakai-supir-checkbox w-5 h-5">
                <label class="font-semibold">🧑‍✈️ Pakai Supir</label>
            </div>

            <div class="mb-2">
                <label class="block mb-1 font-semibold">⏳ Lama Sewa (hari)</label>
                <input type="number" class="w-full border rounded p-2 bg-gray-100 lama-sewa" readonly>
            </div>

            <div class="mb-2">
                <label class="block mb-1 font-semibold">💰 Total Harga Mobil</label>
                <input type="text" class="w-full border rounded p-2 bg-gray-100 total-harga-mobil" readonly>
            </div>

            <button type="button" class="remove-mobil mt-2 text-red-500">Hapus</button>
        </div>
    </div>
@endsection
