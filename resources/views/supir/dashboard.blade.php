@extends('layouts.supir')

@section('title', 'Dashboard Supir')

@php
    $supir = auth()->user()->supir;
    $bisaTerima = (int) $supir->status === 1;
    $nama = fn($d) => $d->mobil->masterMobil->nama ?? $d->mobil->merk ?? 'Mobil';
@endphp

@section('content')
    <div class="mx-auto max-w-5xl pb-24 md:pb-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h2>
        <p class="mt-1 text-sm text-gray-500">Terima tawaran job dan pantau tugas Anda.</p>

        {{-- Ringkasan --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Status Anda</p>
                <span class="mt-2 inline-block rounded-full px-3 py-1 text-sm font-semibold {{ $supir->status_badge_class }}">
                    {{ $supir->status_text }}
                </span>
            </div>
            <a href="#job-aktif" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:ring-green-300">
                <p class="text-sm text-gray-500">Job aktif</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ count($jobAktif) }}</p>
            </a>
            <a href="#riwayat" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:ring-green-300">
                <p class="text-sm text-gray-500">Selesai</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ count($riwayatJob) }}</p>
            </a>
        </div>

        {{-- Tawaran job --}}
        <section id="tawaran" class="mt-10">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-900">Tawaran job</h3>
                @if ($tawaran->isNotEmpty())
                    <span class="rounded-full bg-green-600 px-2.5 py-0.5 text-xs font-bold text-white">{{ $tawaran->count() }}</span>
                @endif
            </div>

            @unless ($bisaTerima)
                <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    Anda sedang {{ (int) $supir->status === 2 ? 'bertugas' : 'tidak tersedia' }}.
                    @if ((int) $supir->status !== 2)
                        Aktifkan saklar status di kanan atas agar bisa menerima job.
                    @else
                        Tawaran baru bisa diterima setelah tugas Anda selesai.
                    @endif
                </p>
            @endunless

            <div class="mt-4 space-y-3">
                @forelse ($tawaran as $t)
                    @php $d = $t->booking_detail; @endphp
                    <div class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-bold text-gray-900">{{ $nama($d) }}</p>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $d->tanggal_mulai_format }} s/d {{ $d->tanggal_selesai_format }}
                                &middot; {{ $d->lama_sewa }} hari
                            </p>
                        </div>
                        <form method="POST" action="{{ route('supir.acceptJob') }}">
                            @csrf
                            <input type="hidden" name="job_offer_id" value="{{ $t->id }}">
                            <button type="submit" @disabled(! $bisaTerima)
                                class="w-full rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-300 sm:w-auto">
                                Terima job
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">
                        Belum ada tawaran job. Tawaran dari admin akan muncul di sini.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Job aktif --}}
        <section id="job-aktif" class="mt-10">
            <h3 class="text-lg font-bold text-gray-900">Job aktif</h3>
            <div class="mt-4 space-y-3">
                @forelse ($jobAktif as $job)
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-bold text-gray-900">{{ $nama($job) }}</p>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">{{ $job->status_label }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $job->tanggal_mulai_format }} s/d {{ $job->tanggal_selesai_format }}</p>
                        @if ($job->booking?->user)
                            <p class="mt-1 text-sm text-gray-500">Penyewa: {{ $job->booking->user->name }}</p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">Belum ada job aktif.</div>
                @endforelse
            </div>
        </section>

        {{-- Riwayat --}}
        <section id="riwayat" class="mt-10">
            <h3 class="text-lg font-bold text-gray-900">Riwayat job</h3>
            <div class="mt-4 space-y-3">
                @forelse ($riwayatJob as $job)
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <p class="font-bold text-gray-900">{{ $nama($job) }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $job->tanggal_mulai_format }} s/d {{ $job->tanggal_selesai_format }}</p>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">Belum ada riwayat job.</div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Tanpa Pusher, tawaran baru tidak "didorong" ke halaman: segarkan daftar tiap 30 detik. --}}
    @unless (config('broadcasting.connections.pusher.key'))
        <script>
            setInterval(() => {
                if (document.visibilityState === 'visible') location.reload();
            }, 30000);
        </script>
    @endunless
@endsection
