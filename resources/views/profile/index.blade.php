@extends('layouts.landing')

@section('content')
    <div class="max-w-4xl mx-auto px-4 pt-20">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">👤 Profil Kamu</h1>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- Card Profil --}}
        <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-200">
            <div class="flex items-center gap-6 mb-6">
                {{-- Foto Profil Placeholder --}}
                <div
                    class="w-20 h-20 rounded-full bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center text-white text-2xl font-bold shadow">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">{{ $user->name }}</h2>
                    <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                {{-- No HP --}}
                <div class="bg-gray-50 p-4 rounded-lg">
                    <span class="text-gray-500 text-sm">No HP</span>
                    <p class="text-gray-800 font-medium">{{ $user->no_hp ?? '-' }}</p>
                </div>

                {{-- Asal Kota --}}
                <div class="bg-gray-50 p-4 rounded-lg">
                    <span class="text-gray-500 text-sm">Asal Kota</span>
                    <p class="text-gray-800 font-medium">{{ $user->asal_kota ?? '-' }}</p>
                </div>

                {{-- Alamat --}}
                <div class="sm:col-span-2 bg-gray-50 p-4 rounded-lg">
                    <span class="text-gray-500 text-sm">Alamat</span>
                    <p class="text-gray-800 font-medium">{{ $user->alamat ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Action --}}
        <div class="flex justify-center mt-8 gap-4">
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-2 bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition shadow">
                ✏️ Edit Profil
            </a>

            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Yakin hapus akun?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-2 bg-red-500 text-white px-5 py-2.5 rounded-lg hover:bg-red-600 transition shadow">
                    🗑 Hapus Akun
                </button>
            </form>
        </div>
    </div>
@endsection
