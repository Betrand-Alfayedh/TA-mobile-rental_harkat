@extends('layouts.landing')

@section('content')
    <div class="max-w-5xl mx-auto mt-12 pt-20 bg-white shadow-xl rounded-2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            {{-- Gambar Mobil --}}
            <div class="flex items-center justify-center">
                <img src="{{ Storage::url($mobil->gambar) }}" alt="{{ $mobil->nama_mobil }}"
                    class="w-full h-80 object-contain rounded-lg shadow-md">
            </div>

            {{-- Detail Mobil --}}
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-3">{{ $mobil->nama_mobil }}</h2>

                <p class="text-lg text-green-600 font-semibold mb-4">
                    Rp {{ number_format($mobil->harga_sewa, 0, ',', '.') }}
                    <span class="text-sm text-gray-500">/ hari</span>
                </p>
                <p class="text-lg text-green-600 font-semibold mb-4">
                    Rp {{ number_format($mobil->harga_all_in, 0, ',', '.') }}
                    <span class="text-sm text-gray-500">/ hari (dengan supir)</span>
                </p>

                {{-- Spesifikasi Mobil --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-3 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500">Tipe</p>
                        <p class="font-semibold text-gray-800">{{ $mobil->type->nama_tipe ?? '-' }}</p>
                    </div>
                </div>
                {{-- Tombol Booking --}}
                @if (Auth::check() && Auth::user()->isRole('Customer'))
                    <a href="{{ route('booking.create') }}"
                        class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg shadow hover:bg-green-700 transition">
                        🚗 Booking Sekarang
                    </a>
                @else
                    <a href="#" id="openCustomerLoginModalMobile"
                        class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg shadow hover:bg-green-700 transition">
                        🚗 Booking Sekarang
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
