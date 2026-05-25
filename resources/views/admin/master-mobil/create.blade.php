@extends('layouts.admin')

@section('content')
    <div class="max-w-xl mx-auto py-10">
        <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">➕ Tambah Master Mobil</h2>

            <form method="POST" action="{{ route('admin.master-mobils.store') }}" class="space-y-5">
                @csrf

                <!-- Nama Mobil -->
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Nama Mobil</label>
                    <input type="text" name="nama" value="{{ old('nama') }}"
                        class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 transition shadow-sm"
                        placeholder="Masukkan nama mobil..." required>
                    @error('nama')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipe Mobil -->
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Tipe Mobil</label>
                    <select name="tipe_id"
                        class="w-full border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 transition shadow-sm"
                        required>
                        <option value="">-- Pilih Tipe --</option>
                        @foreach ($tipeMobils as $tipe)
                            <option value="{{ $tipe->id }}" {{ old('tipe_id') == $tipe->id ? 'selected' : '' }}>
                                {{ $tipe->nama_tipe }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipe_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol -->
                <div class="pt-2 flex justify-end gap-3">
                    <a href="{{ route('admin.master-mobils.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-medium shadow transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
