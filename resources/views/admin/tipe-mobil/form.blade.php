@php
    $isEdit = isset($type);
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
    <label for="nama_tipe" class="block text-sm font-medium text-gray-700">Tipe Mobil</label>
    <input type="text" name="nama_tipe" id="nama_tipe"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('nama_tipe', $tipeMobil->nama_tipe ?? '') }}" required>
</div>

{{-- Tombol Submit --}}
<div class="flex justify-end space-x-2">
    <a href="{{ route('admin.tipe-mobil.index') }}"
        class="inline-block px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Batal</a>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        {{ isset($type) ? 'Update' : 'Simpan' }}
    </button>
</div>
