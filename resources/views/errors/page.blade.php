@extends('layouts.landing')

@section('title', "Error $code | Harkat Rental")

@section('content')
    <section class="min-h-screen flex flex-col justify-center items-center text-center bg-gray-900 text-white px-4">
        <div class="max-w-lg">
            <div class="mb-6 animate-bounce">
                @if ($code == 404)
                    <x-lucide-map-pin class="w-16 h-16 text-emerald-400 mx-auto" />
                @elseif ($code == 500)
                    <x-lucide-server-crash class="w-16 h-16 text-emerald-400 mx-auto" />
                @elseif ($code == 403)
                    <x-lucide-shield-alert class="w-16 h-16 text-emerald-400 mx-auto" />
                @else
                    <x-lucide-alert-triangle class="w-16 h-16 text-emerald-400 mx-auto" />
                @endif
            </div>

            {{-- Kode dan pesan --}}
            <h1 class="text-7xl font-extrabold text-emerald-500 mb-4 tracking-tight">{{ $code ?? 'Error' }}</h1>
            <p class="text-lg text-gray-300 mb-8 leading-relaxed">
                {{ $message ?? 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi nanti.' }}
            </p>

            {{-- Tombol kembali --}}
            <a href="{{ auth('web')->check() ? url('/home') : url('/') }}"
                class="px-6 py-3 bg-emerald-500 rounded-xl text-white font-semibold hover:bg-emerald-600 hover:shadow-[0_0_18px_rgba(52,211,153,0.5)] transition duration-200">
                🔙 Balik ke Beranda
            </a>
        </div>
    </section>
@endsection
