@php
    $isEdit = isset($tipeMobil);
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
    <h3 class="text-base font-bold text-gray-900">Jenis mobil</h3>
    <p class="mb-5 text-sm text-gray-500">Kelompok untuk model mobil, misalnya MPV, SUV, atau Sedan.</p>

    <label for="nama_tipe" class="text-sm font-medium text-gray-700">Nama jenis</label>
    <input type="text" name="nama_tipe" id="nama_tipe" class="{{ $inp }}" placeholder="Contoh: MPV"
        value="{{ old('nama_tipe', $tipeMobil->nama_tipe ?? '') }}" required>
</section>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.tipe-mobil.index') }}"
        class="rounded-xl border border-hk-line bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-hk-ink px-6 py-2.5 text-sm font-semibold text-white  hover:bg-black">
        {{ $isEdit ? 'Simpan perubahan' : 'Simpan jenis' }}
    </button>
</div>
