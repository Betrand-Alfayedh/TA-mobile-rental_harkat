@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('content')
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-4">Dashboard Admin</h2>
        <p class="text-gray-600 mb-4">Selamat Datang di Sistem Manajemen Rental Mobil Harkat Yogyakarta</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-100 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800">Total Mobil</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $totalMobil }}</p>
            </div>
            <div class="bg-green-100 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800">Mobil Tersedia</h3>
                <p class="text-2xl font-bold text-green-600">{{ $mobilTersedia }}</p>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg">
                <h3 class="font-semibold text-yellow-800">Mobil Disewa</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ $mobilDisewa }}</p>
            </div>
            <div class="bg-blue-100 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800">Total Supir</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $totalSupir }}</p>
            </div>
            <div class="bg-green-100 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800">Supir Siap</h3>
                <p class="text-2xl font-bold text-green-600">{{ $supirSiap }}</p>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg">
                <h3 class="font-semibold text-yellow-800">Supir bertugas</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ $supirBertugas }}</p>
            </div>
        </div>
    </div>
@endsection
