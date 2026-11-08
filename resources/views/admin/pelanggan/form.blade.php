@php
    $isEdit = isset($pelanggan);
    $inp = 'mt-1 block w-full px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400';
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
        <div class="mb-1 font-semibold">Data belum bisa disimpan:</div>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@csrf

<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Data pelanggan</h3>
    <p class="mb-5 text-sm text-gray-500">Identitas dan kontak yang dipakai saat membuat pesanan.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="nama" class="text-sm font-medium text-gray-700">Nama lengkap</label>
            <input type="text" name="nama" id="nama" class="{{ $inp }}" placeholder="Contoh: Budi Santoso"
                value="{{ old('nama', $pelanggan->name ?? '') }}" required>
        </div>

        <div>
            <label for="email" class="text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" class="{{ $inp }}" placeholder="nama@email.com"
                value="{{ old('email', $pelanggan->email ?? '') }}" required>
        </div>

        <div>
            <label for="no_hp" class="text-sm font-medium text-gray-700">No. telepon</label>
            <input type="text" name="no_hp" id="no_hp" class="{{ $inp }}" placeholder="08xxxxxxxxxx" required
                value="{{ old('no_hp', $pelanggan->no_hp ?? '') }}">
        </div>

        <div class="md:col-span-2">
            <label for="alamat" class="text-sm font-medium text-gray-700">Alamat</label>
            <textarea name="alamat" id="alamat" rows="3" class="{{ $inp }}" placeholder="Alamat lengkap">{{ old('alamat', $pelanggan->alamat ?? '') }}</textarea>
        </div>
    </div>
</section>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.pelanggan.index') }}"
        class="rounded-xl border border-hk-line bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-hk-ink px-6 py-2.5 text-sm font-semibold text-white  hover:bg-black">
        {{ $isEdit ? 'Simpan perubahan' : 'Simpan pelanggan' }}
    </button>
</div>
