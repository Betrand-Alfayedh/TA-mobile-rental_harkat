@php
    $isEdit = $mobilData->exists;
    $cover = $isEdit && $mobilData->gambar ? asset('gambar_mobil/' . $mobilData->gambar) : null;
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

{{-- Data unit --}}
<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Data unit</h3>
    <p class="mb-5 text-sm text-gray-500">Model mobil, plat nomor, dan identitas unit.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="master_mobil_id" class="text-sm font-medium text-gray-700">Model mobil</label>
            <select name="master_mobil_id" id="master_mobil_id" class="{{ $inp }}" required>
                <option value="">Pilih model mobil</option>
                @foreach ($masterMobils as $m)
                    <option value="{{ $m->id }}"
                        {{ old('master_mobil_id', $mobilData->master_mobil_id ?? '') == $m->id ? 'selected' : '' }}>
                        {{ $m->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="plat_nomor" class="text-sm font-medium text-gray-700">Plat nomor</label>
            <input type="text" name="plat_nomor" id="plat_nomor" class="{{ $inp }} uppercase"
                value="{{ old('plat_nomor', $mobilData->plat_nomor ?? '') }}" placeholder="Contoh: AB 1234 CD"
                pattern="^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$" title="Format plat nomor: AB 1234 CD">
        </div>

        <div>
            <label for="merk" class="text-sm font-medium text-gray-700">Merek</label>
            <input type="text" name="merk" id="merk" class="{{ $inp }}"
                value="{{ old('merk', $mobilData->merk ?? '') }}" placeholder="Contoh: Toyota" required>
        </div>

        <div>
            <label for="tahun" class="text-sm font-medium text-gray-700">Tahun</label>
            <input type="number" name="tahun" id="tahun" class="{{ $inp }}"
                value="{{ old('tahun', $mobilData->tahun ?? '') }}" placeholder="Contoh: 2023" required>
        </div>
    </div>
</section>

{{-- Harga --}}
<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Harga sewa</h3>
    <p class="mb-5 text-sm text-gray-500">Tarif per hari dalam rupiah.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label for="harga_sewa" class="text-sm font-medium text-gray-700">Lepas kunci (tanpa sopir)</label>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm text-gray-500">Rp</span>
                <input type="number" name="harga_sewa" id="harga_sewa" class="block w-full py-2.5 pl-10 pr-3.5 text-sm"
                    value="{{ old('harga_sewa', $mobilData->harga_sewa ?? '') }}" placeholder="350000" required>
            </div>
        </div>
        <div>
            <label for="harga_all_in" class="text-sm font-medium text-gray-700">All in (dengan sopir)</label>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm text-gray-500">Rp</span>
                <input type="number" name="harga_all_in" id="harga_all_in" class="block w-full py-2.5 pl-10 pr-3.5 text-sm"
                    value="{{ old('harga_all_in', $mobilData->harga_all_in ?? '') }}" placeholder="550000">
            </div>
        </div>
    </div>
</section>

{{-- Foto --}}
<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Foto</h3>
    <p class="mb-5 text-sm text-gray-500">Format JPG, PNG, atau WebP, maksimal 8 MB per foto.</p>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Cover --}}
        <div>
            <div class="text-sm font-medium text-gray-700">Foto utama (cover)</div>
            <label for="gambar"
                class="mt-1 flex h-48 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-hk-line bg-hk-panel text-center transition hover:border-hk-ink hover:bg-hk-panel">
                <img id="cover-preview" src="{{ $cover }}" alt="Foto utama"
                    class="{{ $cover ? '' : 'hidden' }} h-full w-full object-cover">
                <span id="cover-hint" class="{{ $cover ? 'hidden' : '' }} px-4 text-sm text-gray-500">
                    <span class="block font-semibold text-hk-ink">Klik untuk memilih foto</span>
                    Tampil sebagai gambar utama di katalog
                </span>
            </label>
            <input type="file" name="gambar" id="gambar" accept="image/*" class="sr-only"
                @if (!$isEdit) required @endif>
            @if ($isEdit)
                <p class="mt-1 text-xs text-gray-500">Biarkan kosong jika tidak ingin mengganti foto.</p>
            @endif
        </div>

        {{-- Foto tambahan --}}
        <div>
            <div class="text-sm font-medium text-gray-700">Foto tambahan (boleh lebih dari satu)</div>
            <label for="images"
                class="mt-1 flex h-20 cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-hk-line bg-hk-panel px-4 text-center text-sm transition hover:border-hk-ink hover:bg-hk-panel">
                <span class="font-semibold text-hk-ink">Klik untuk menambah foto</span>
            </label>
            <input type="file" name="images[]" id="images" multiple accept="image/*" class="sr-only">

            <div id="additional-preview" class="mt-3 grid grid-cols-3 gap-2">
                @if ($isEdit)
                    @foreach ($mobilData->images as $img)
                        <img src="{{ asset('mobil_images_additionals/' . $img->image_path) }}" alt="Foto tambahan"
                            class="h-20 w-full rounded-lg object-cover ring-1 ring-hk-line">
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Tombol --}}
<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.mobil.index') }}"
        class="rounded-xl border border-hk-line bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-hk-ink px-6 py-2.5 text-sm font-semibold text-white  hover:bg-black">
        {{ $submitButtonText }}
    </button>
</div>

<script>
    const coverInput = document.getElementById('gambar');
    const imagesInput = document.getElementById('images');

    coverInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const p = document.getElementById('cover-preview');
            p.src = URL.createObjectURL(this.files[0]);
            p.classList.remove('hidden');
            document.getElementById('cover-hint').classList.add('hidden');
        }
    });

    // Foto yang baru dipilih ditampilkan di samping foto yang sudah tersimpan.
    imagesInput.addEventListener('change', function() {
        const box = document.getElementById('additional-preview');
        box.querySelectorAll('[data-baru]').forEach(el => el.remove());
        Array.from(this.files).forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.dataset.baru = '1';
            img.className = 'h-20 w-full rounded-lg object-cover ring-2 ring-hk-ink';
            box.appendChild(img);
        });
    });

    // Format plat nomor otomatis: AB 1234 CD
    document.getElementById('plat_nomor').addEventListener('input', function(e) {
        const v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        e.target.value = v.replace(/^([A-Z]{0,2})(\d{0,4})([A-Z]{0,3}).*/, (_, a, b, c) => [a, b, c].filter(Boolean).join(' '));
    });
</script>
