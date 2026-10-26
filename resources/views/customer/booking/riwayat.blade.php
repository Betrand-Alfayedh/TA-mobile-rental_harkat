@extends('layouts.landing')

@section('title', 'Riwayat Pesanan | Harkat Rent Car')

@section('pagehead')
    <x-page-head title="Riwayat pesanan" subtitle="Semua booking Anda yang sudah selesai atau dibatalkan." />
@endsection

@section('content')
    <div class="relative z-10 -mt-16 space-y-6">
        @forelse ($riwayats as $riwayat)
            <div class="reveal" style="--d: {{ min($loop->index, 4) * 70 }}ms">
                @include('customer.booking.partials.booking-card', ['booking' => $riwayat, 'history' => true])
            </div>
        @empty
            <div class="bezel">
                <div class="bezel-core px-8 py-20 text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-mint text-forest">
                        <x-line-icon name="receipt" class="h-8 w-8" />
                    </span>
                    <h2 class="mt-6 text-xl font-bold tracking-tight text-forest">Belum ada riwayat</h2>
                    <p class="mx-auto mt-2 max-w-sm text-ink/60">
                        Booking yang sudah selesai atau dibatalkan akan muncul di sini.
                    </p>
                </div>
            </div>
        @endforelse
    </div>
@endsection
