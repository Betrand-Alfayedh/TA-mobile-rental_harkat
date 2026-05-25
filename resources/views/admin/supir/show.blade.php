@extends('layouts.admin')

@section('content')
    <div class="bg-white p-6 rounded-lg shadow max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center">Detail Supir</h2>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Gambar Supir -->
            <div class="flex-shrink-0">
                @if ($supir->gambar)
                    <img src="{{ asset('storage/' . $supir->gambar) }}" alt="Gambar Supir"
                        class="w-full max-w-xs h-auto rounded shadow object-contain mx-auto">
                @else
                    <div class="w-64 h-40 bg-gray-200 flex items-center justify-center rounded">
                        <span class="text-gray-500">Gambar tidak tersedia</span>
                    </div>
                @endif
            </div>

            <!-- Detail Data -->
            <div class="flex-1 space-y-2">
                <p><strong>Nama Supir:</strong> {{ $supir->nama }}</p>
                <p><strong>Nomor HP:</strong> {{ $supir->no_hp }}</p>
                <p><strong>Merk:</strong> {{ $supir->alamat }}</p>
                <p><strong>Status:</strong>
                    @if ($supir->status == 1)
                        <span class="text-green-600 font-semibold">Siap</span>
                    @else
                        <span class="text-red-600 font-semibold">Bertugas</span>
                    @endif
                </p>
            </div>
        </div>

        <!-- Tombol Kembali -->
        <div class="mt-6 text-center">
            <a href="{{ route('admin.supir.index') }}"
                class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800 transition">
                ← Kembali ke Daftar Supir
            </a>
        </div>
    </div>
@endsection
