@php
    $isEdit = isset($supir);
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

{{-- Nama Supir --}}
<div>
    <label for="name" class="block text-sm font-medium text-gray-700">Nama Supir</label>
    <input type="text" name="name" id="name"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('name', $isEdit ? $supir->user->name : '') }}" required>
</div>

{{-- Email --}}
<div>
    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
    <input type="email" name="email" id="email"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('email', $isEdit ? $supir->user->email : '') }}" required>
</div>

{{-- password --}}
@if (!$isEdit)
    {{-- Ini muncul hanya kalau create --}}
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" id="password"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required>
    </div>
@else
    {{-- Kalau edit, field bisa kosong dan optional --}}
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password Baru (Opsional)</label>
        <input type="password" name="password" id="password"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Biarkan kosong jika tidak ingin mengganti password">
    </div>
@endif


{{-- No HP --}}
<div>
    <label for="no_hp" class="block text-sm font-medium text-gray-700">No Telepon</label>
    <input type="number" name="no_hp" id="no_hp"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        value="{{ old('no_hp', $isEdit ? $supir->user->no_hp : '') }}" required>
</div>

{{-- Alamat --}}
<div>
    <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat</label>
    <textarea name="alamat" id="alamat"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        required>{{ old('alamat', $isEdit ? $supir->user->alamat : '') }}</textarea>
</div>

{{-- Status (hanya tampil kalau edit) --}}
@if ($isEdit)
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option>--Pilih Status--</option>
            <option value="0" {{ $supir->status == 0 ? 'selected' : '' }}>Unavailable</option>
            <option value="1" {{ $supir->status == 1 ? 'selected' : '' }}>Available</option>
            <option value="2" {{ $supir->status == 2 ? 'selected' : '' }}>Bertugas</option>
        </select>
    </div>
@endif

{{-- Gambar Supir --}}
<div>
    <label for="gambar" class="block text-sm font-medium text-gray-700">Foto Supir</label>
    <input type="file" name="gambar" id="gambar"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>


{{-- Tombol Submit --}}
<div class="flex justify-end space-x-2">
    <a href="{{ route('admin.supir.index') }}"
        class="inline-block px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Batal</a>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        {{ isset($supir) ? 'Update' : 'Simpan' }}
    </button>
</div>
