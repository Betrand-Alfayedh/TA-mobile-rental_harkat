@php
    $isEdit = isset($transaksi);
@endphp

@if ($errors->any())
    <div class="mb-4 text-red-600 bg-red-100 border border-red-300 rounded p-4">
        <ul class="list-disc pl-4 mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $isEdit ? route('admin.booking.update', $transaksi->id) : route('admin.booking.store') }}" method="POST"
    class="space-y-6" id="adminBookingForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- Pelanggan --}}
    <div>
        <x-input-label for="pelanggan_id" :value="'Pelanggan'" />
        <select name="pelanggan_id" id="pelanggan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            required>
            <option value="">-- Pilih Pelanggan --</option>
            @foreach ($pelanggans as $p)
                <option value="{{ $p->id }}"
                    {{ old('pelanggan_id', $transaksi->pelanggan_id ?? '') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('pelanggan_id')" class="mt-2" />
    </div>

    {{-- Wrapper multiple mobil --}}
    <div class="overflow-hidden relative">
        <div id="mobils-wrapper" class="flex transition-transform duration-500 ease-in-out"></div>
    </div>

    <div id="indicator" class="flex justify-center mt-3 gap-2"></div>
    <div class="flex justify-between mt-4">
        <button type="button" id="prev-mobil" class="bg-gray-500 text-white px-4 py-2 rounded">⬅ Prev</button>
        <button type="button" id="add-mobil" class="bg-green-500 text-white px-4 py-2 rounded">+ Tambah Mobil</button>
        <button type="button" id="next-mobil" class="bg-gray-500 text-white px-4 py-2 rounded">Next ➡</button>
    </div>

    <hr class="my-4">

    {{-- Jaminan --}}
    <div>
        <x-input-label for="jaminan" :value="'Jaminan'" />
        <select name="jaminan" id="jaminan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">-- Pilih Jaminan --</option>
            <option value="1" {{ old('jaminan', $transaksi->jaminan ?? '') == 1 ? 'selected' : '' }}>KTP/Passport
            </option>
            <option value="2" {{ old('jaminan', $transaksi->jaminan ?? '') == 2 ? 'selected' : '' }}>KTP & Motor
                (lokal)</option>
        </select>
        <x-input-error :messages="$errors->get('jaminan')" class="mt-2" />
    </div>

    {{-- Asal Kota / Nama Kota --}}
    <div>
        <x-input-label for="asal_kota" :value="'Asal Kota'" />
        <select name="asal_kota" id="asalKota" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">-- Pilih Asal Kota --</option>
            <option value="1" {{ old('asal_kota', $transaksi->asal_kota ?? '') == 1 ? 'selected' : '' }}>Warga
                Yogyakarta</option>
            <option value="2" {{ old('asal_kota', $transaksi->asal_kota ?? '') == 2 ? 'selected' : '' }}>Lainnya...
            </option>
        </select>
    </div>

    <div class="mt-2 {{ old('asal_kota', $transaksi->asal_kota ?? '') == 2 ? '' : 'hidden' }}" id="namaKotaWrapper">
        <x-input-label for="nama_kota" :value="'Nama Kota'" />
        <x-text-input name="nama_kota" value="{{ old('nama_kota', $transaksi->nama_kota ?? '') }}"
            class="mt-1 block w-full" />
    </div>

    {{-- Uang Muka --}}
    <div>
        <x-input-label for="uang_muka" :value="'Uang Muka (50%)'" />
        <x-text-input name="uang_muka" id="uangMuka" value="{{ old('uang_muka', $transaksi->uang_muka ?? '') }}"
            class="mt-1 block w-full bg-gray-100" readonly />
    </div>

    {{-- Total Harga Semua Mobil --}}
    <div>
        <x-input-label for="total_harga" :value="'Total Harga Semua Mobil'" />
        <x-text-input name="total_harga" id="totalHarga"
            value="{{ old('total_harga', $transaksi->total_harga ?? '') }}" class="mt-1 block w-full bg-gray-100"
            readonly />
    </div>

    {{-- Status
    <div>
        <x-input-label for="status" :value="'Status Transaksi'" />
        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="1" {{ old('status', $transaksi->status ?? '') == 1 ? 'selected' : '' }}>Booked</option>
            <option value="2" {{ old('status', $transaksi->status ?? '') == 2 ? 'selected' : '' }}>Berjalan
            </option>
            <option value="3" {{ old('status', $transaksi->status ?? '') == 3 ? 'selected' : '' }}>Selesai
            </option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div> --}}

    <div class="mt-6">
        <x-primary-button>Simpan Transaksi</x-primary-button>
    </div>
</form>

{{-- TEMPLATE HIDDEN MULTIPLE MOBIL --}}
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
            <button type="button" class="check-mobils bg-green-500 text-white px-4 py-2 rounded">Cek Mobil
                Tersedia</button>
        </div>

        <div class="mb-2">
            <label class="block mb-1 font-semibold">Pilih Mobil</label>
            <select name="mobils[0][mobil_id]" class="w-full border rounded p-2 mobil-select" required>
                <option value="">-- Pilih Mobil --</option>
                @foreach ($mobils as $mobil)
                    <option value="{{ $mobil->id }}" data-harga="{{ $mobil->harga_sewa }}"
                        data-harga-allin="{{ $mobil->harga_all_in }}">
                        {{ $mobil->nama_mobil }} - Rp{{ number_format($mobil->harga_sewa, 0, ',', '.') }}/hari |
                        Include Supir: Rp{{ number_format($mobil->harga_all_in, 0, ',', '.') }}/hari
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
