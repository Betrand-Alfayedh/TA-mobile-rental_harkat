@php
    $isEdit = isset($masterMobil);
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
    <h3 class="text-base font-bold text-gray-900">Model mobil</h3>
    <p class="mb-5 text-sm text-gray-500">Nama model dan jenisnya. Unit mobil dibuat dari model ini.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label for="nama" class="text-sm font-medium text-gray-700">Nama model</label>
            <input type="text" name="nama" id="nama" class="{{ $inp }}" placeholder="Contoh: Toyota Avanza"
                value="{{ old('nama', $masterMobil->nama ?? '') }}" required>
        </div>

        <div>
            <label for="tipe_id" class="text-sm font-medium text-gray-700">Jenis mobil</label>
            <select name="tipe_id" id="tipe_id" class="{{ $inp }}" required>
                <option value="">Pilih jenis mobil</option>
                @foreach ($tipeMobils as $tipe)
                    <option value="{{ $tipe->id }}" @selected(old('tipe_id', $masterMobil->tipe_id ?? '') == $tipe->id)>
                        {{ $tipe->nama_tipe }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.master-mobils.index') }}"
        class="rounded-xl border border-hk-line bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-hk-ink px-6 py-2.5 text-sm font-semibold text-white  hover:bg-black">
        {{ $isEdit ? 'Simpan perubahan' : 'Simpan model' }}
    </button>
</div>
