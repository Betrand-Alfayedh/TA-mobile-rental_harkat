@php
    $isEdit = isset($mobilData);
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

@csrf
{{-- Nama Mobil --}}
<div>
    <label for="nama_mobil" class="block text-sm font-medium text-gray-700">Nama Mobil</label>
    <select name="master_mobil_id" id="master_mobil_id"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        required>
        <option value="">-- Pilih Mobil --</option>
        @foreach ($masterMobils as $m)
            <option value="{{ $m->id }}"
                {{ old('master_mobil_id', $mobilData->master_mobil_id ?? '') == $m->id ? 'selected' : '' }}>
                {{ $m->nama }}
            </option>
        @endforeach
    </select>
</div>

{{-- Plat Nomor --}}
<div>
    <label for="plat_nomor" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
    <input type="text" name="plat_nomor" id="plat_nomor"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('plat_nomor', $mobilData->plat_nomor ?? '') }}" placeholder="Contoh: AB 1234 CD"
        pattern="^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$" title="Format plat nomor: AB 1234 CD"
        style="text-transform: uppercase;">
</div>

{{-- Merk --}}
<div>
    <label for="merk" class="block text-sm font-medium text-gray-700">Merk</label>
    <input type="text" name="merk" id="merk"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('merk', $mobilData->merk ?? '') }}" required>
</div>

{{-- Tahun --}}
<div>
    <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
    <input type="number" name="tahun" id="tahun"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('tahun', $mobilData->tahun ?? '') }}" required>
</div>

{{-- Harga Sewa --}}
<div>
    <label for="harga_sewa" class="block text-sm font-medium text-gray-700">Harga Sewa</label>
    <input type="number" name="harga_sewa" id="harga_sewa"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('harga_sewa', $mobilData->harga_sewa ?? '') }}" required>
</div>

{{-- Harga Sewa All In --}}
<div>
    <label for="harga_all_in" class="block text-sm font-medium text-gray-700">Harga Sewa ALL IN</label>
    <input type="number" name="harga_all_in" id="harga_all_in"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('harga_all_in', $mobilData->harga_all_in ?? '') }}">
</div>

{{-- Cover Image --}}
<div>
    <label for="gambar" class="block text-sm font-medium text-gray-700">Cover Image</label>
    <input type="file" name="gambar" id="gambar"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @if (!isset($mobilData)) required @endif>
</div>

{{-- Additional Images --}}
<div>
    <label for="images" class="block text-sm font-medium text-gray-700">Foto Tambahan (Bisa pilih lebih dari
        1)</label>
    <input type="file" name="images[]" id="images" multiple
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>


{{-- Tombol Submit --}}
<div class="mt-6">
    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md shadow hover:bg-indigo-700">
        {{ $submitButtonText }}
    </button>
</div>
<script>
    const gambarInput = document.getElementById('gambar');
    const imagesInput = document.getElementById('images');

    gambarInput.addEventListener('change', function() {
        const preview = document.getElementById('cover-preview');
        if (this.files && this.files[0]) {
            preview.src = URL.createObjectURL(this.files[0]);
        }
    });

    imagesInput.addEventListener('change', function() {
        const previewContainer = document.getElementById('additional-preview');
        previewContainer.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'w-32 h-20 object-cover mr-2 mb-2';
            previewContainer.appendChild(img);
        });
    });
</script>
{{-- Auto format plat nomor --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let input = document.getElementById('plat_nomor');
        if (input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                let formatted = value.replace(/^([A-Z]{0,2})(\d{0,4})([A-Z]{0,3}).*/, function(_, p1,
                    p2, p3) {
                    return [p1, p2, p3].filter(Boolean).join(' ');
                });
                e.target.value = formatted;
            });
        }
    });
</script>

{{-- Tempat preview --}}
<div class="mt-2">
    <img id="cover-preview" class="w-40 h-24 object-cover mb-2">
    <div id="additional-preview" class="flex flex-wrap"></div>
</div>
