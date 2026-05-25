@extends('layouts.supir')

@section('title', 'Dashboard Supir')

@section('content')
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white shadow rounded-xl p-6 flex flex-col items-center">
            <div class="text-blue-600 text-3xl font-bold">⚡</div>
            <p class="mt-2 text-gray-600">Status</p>
            <span class="mt-1 font-semibold text-gray-800">
                @if (auth()->user()->supir->is_available)
                    <p class="text-green-600 font-semibold">Ready to Drive 🚗</p>
                @else
                    <p class="text-gray-500">Not Available</p>
                @endif
            </span>
        </div>

        <div class="bg-white shadow rounded-xl p-6 flex flex-col items-center">
            <div class="text-green-600 text-3xl font-bold">📋</div>
            <p class="mt-2 text-gray-600">Job Aktif</p>
            <span class="mt-1 font-semibold text-gray-800">{{ count($jobAktif) }}</span>
        </div>

        <div class="bg-white shadow rounded-xl p-6 flex flex-col items-center">
            <div class="text-purple-600 text-3xl font-bold">✅</div>
            <p class="mt-2 text-gray-600">Selesai</p>
            <span class="mt-1 font-semibold text-gray-800">{{ count($riwayatJob) }}</span>
        </div>
        {{-- Job Aktif --}}
        <div class="bg-white shadow rounded-xl p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🚀 Job Aktif</h3>
            @if (count($jobAktif) > 0)
                <ul class="space-y-3">
                    @foreach ($jobAktif as $job)
                        <li class="p-4 border rounded-lg hover:bg-gray-50 transition">
                            <span class="font-semibold">{{ $job->mobil->merk }} {{ $job->mobil->nama_mobil }}</span>
                            <div class="text-sm text-gray-600">
                                {{ $job->tanggal_sewa }} s/d {{ $job->tanggal_kembali }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Belum ada job aktif 🚫</p>
            @endif
        </div>

        {{-- Riwayat Job --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📑 Riwayat Job</h3>
            @if (count($riwayatJob) > 0)
                <ul class="space-y-3">
                    @foreach ($riwayatJob as $job)
                        <li class="p-4 border rounded-lg hover:bg-gray-50 transition">
                            <span class="font-semibold">{{ $job->mobil }}</span>
                            <div class="text-sm text-gray-600">
                                {{ $job->tanggal_sewa }} s/d {{ $job->tanggal_kembali }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Belum ada riwayat job 📭</p>
            @endif
        </div>
    </div>
@endsection
