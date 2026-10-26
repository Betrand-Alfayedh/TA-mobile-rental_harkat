@extends('layouts.landing')

@section('title', 'Pesanan Saya | Harkat Rent Car')

@section('pagehead')
    <x-page-head title="Pesanan saya"
        subtitle="Pantau booking yang sedang berjalan, bayar DP, dan lihat status pembayaran." />
@endsection

@section('content')
    <div class="relative z-10 -mt-16 space-y-6">
        @forelse ($bookings as $booking)
            <div class="reveal" style="--d: {{ min($loop->index, 4) * 70 }}ms">
                @include('customer.booking.partials.booking-card', ['booking' => $booking, 'history' => false])
            </div>
        @empty
            <div class="bezel">
                <div class="bezel-core px-8 py-20 text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-mint text-forest">
                        <x-line-icon name="car" class="h-8 w-8" />
                    </span>
                    <h2 class="mt-6 text-xl font-bold tracking-tight text-forest">Belum ada pesanan aktif</h2>
                    <p class="mx-auto mt-2 max-w-sm text-ink/60">
                        Pilih mobil dari armada kami, lalu booking dalam beberapa menit.
                    </p>
                    <a href="{{ route('mobil.index') }}" class="btn btn-primary mt-8">
                        Lihat armada <span class="btn-icon"><x-line-icon name="arrow-right" class="h-4 w-4" /></span>
                    </a>
                </div>
            </div>
        @endforelse

        @if ($bookings->isNotEmpty())
            <p class="pt-2 text-center text-sm text-ink/50">
                Booking yang sudah selesai atau dibatalkan ada di
                <a href="{{ route('riwayat.index') }}" class="font-semibold text-forest underline-offset-4 hover:underline">Riwayat</a>.
            </p>
        @endif
    </div>
@endsection
