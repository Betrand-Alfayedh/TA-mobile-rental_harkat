{{--
    Kartu satu booking (dipakai halaman Pesanan dan Riwayat).
    $booking : Booking dengan details.mobil.masterMobil, details.supir.user, pembayaranDp, pelunasan
    $history : true bila dipakai di halaman riwayat (tanpa tombol aksi)
--}}
@php
    $history ??= false;
    $dp = $booking->pembayaranDp;
    $pelunasan = $booking->pelunasan;
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');

    [$statusLabel, $statusClass] = match ($booking->status) {
        \App\Models\Booking::STATUS_BOOKED => ['Dipesan', 'bg-amber-100 text-amber-800'],
        \App\Models\Booking::STATUS_ONGOING => ['Sedang berjalan', 'bg-mint text-forest'],
        \App\Models\Booking::STATUS_DONE => ['Selesai', 'bg-emerald-100 text-emerald-800'],
        default => ['Dibatalkan', 'bg-red-100 text-red-700'],
    };
@endphp

<article class="bezel">
    <div class="bezel-core overflow-hidden">
        {{-- Kepala --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold tracking-tight text-forest">Booking #{{ $booking->id }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <span class="text-sm text-ink/50">Dipesan {{ $booking->tanggal_booking_format }}</span>
        </div>

        <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-12">
            {{-- Mobil --}}
            <div class="space-y-5 lg:col-span-7">
                @foreach ($booking->details as $detail)
                    <div class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-mint text-forest">
                            <x-line-icon name="car" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <h3 class="font-bold tracking-tight text-forest">{{ $detail->mobil->masterMobil->nama ?? 'Mobil' }}</h3>
                                <span class="rounded-full bg-paper px-2.5 py-0.5 text-xs font-semibold text-ink/60 ring-1 ring-forest/10">
                                    {{ $detail->pakai_supir ? 'Dengan supir' : 'Lepas kunci' }}
                                </span>
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-ink/60">
                                <x-line-icon name="calendar" class="h-4 w-4 shrink-0" />
                                {{ $detail->tanggal_mulai_format }}
                                <x-line-icon name="arrow-right" class="h-3.5 w-3.5 shrink-0 text-ink/30" />
                                {{ $detail->tanggal_selesai_format }}
                            </p>
                            @if ($detail->supir)
                                <p class="mt-1 flex items-center gap-2 text-sm text-ink/60">
                                    <x-line-icon name="steering" class="h-4 w-4 shrink-0" />
                                    Supir: {{ $detail->supir->user->name }}
                                </p>
                            @elseif ($detail->pakai_supir)
                                <p class="mt-1 text-sm text-ink/50">Supir sedang dicarikan admin.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Biaya & pembayaran --}}
            <div class="lg:col-span-5 lg:border-l lg:border-line lg:pl-8">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-ink/55">Total sewa</dt>
                        <dd class="text-lg font-bold tracking-tight text-ink">{{ $booking->total_harga_rp }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-ink/55">DP 50%</dt>
                        <dd class="font-semibold text-ink">{{ $booking->uang_muka_rp }}</dd>
                    </div>
                    @if ($pelunasan)
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-ink/55">Pelunasan</dt>
                            <dd class="font-semibold text-ink">
                                {{ $rp($pelunasan->jumlah) }}
                                <span class="ml-1 text-xs font-medium {{ (int) $pelunasan->status_pembayaran === 1 ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ (int) $pelunasan->status_pembayaran === 1 ? 'Lunas' : 'Belum lunas' }}
                                </span>
                            </dd>
                        </div>
                    @endif
                </dl>

                @if (! $history && $dp)
                    <div class="mt-6 border-t border-line pt-5">
                        @switch((int) $dp->status_pembayaran)
                            @case(0)
                                @if ($dp->catatan_admin)
                                    <div class="mb-4 flex gap-2 rounded-2xl bg-red-50 p-3 text-sm text-red-700">
                                        <x-line-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span><strong>Pembayaran ditolak.</strong> {{ $dp->catatan_admin }}</span>
                                    </div>
                                @endif
                                <p class="mb-4 flex items-center gap-2 text-sm text-ink/60">
                                    <x-line-icon name="clock" class="h-4 w-4" />
                                    Bayar DP sebelum {{ $dp->jatuh_tempo?->translatedFormat('d M Y, H:i') ?? '-' }}
                                </p>
                                <a href="{{ route('pembayaran.show', $booking->id) }}" class="btn btn-primary">
                                    Bayar DP <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                                </a>
                            @break

                            @case(2)
                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">
                                    <x-line-icon name="clock" class="h-4 w-4" /> Menunggu verifikasi admin
                                </span>
                            @break

                            @case(1)
                                <span class="inline-flex items-center gap-2 rounded-full bg-mint px-4 py-2 text-sm font-semibold text-forest">
                                    <x-line-icon name="check" class="h-4 w-4" /> DP lunas
                                </span>
                                @if ($pelunasan && (int) $pelunasan->status_pembayaran !== 1)
                                    <p class="mt-3 text-sm leading-relaxed text-ink/55">
                                        Sisa pelunasan dibayar saat pengembalian mobil, bisa tunai di lokasi.
                                    </p>
                                @endif
                            @break
                        @endswitch

                        @if ($booking->canBeCanceledByCustomer())
                            <form action="{{ route('booking.cancel', $booking->id) }}" method="POST" class="mt-4"
                                onsubmit="return confirm('Batalkan booking ini?')">
                                @csrf
                                <button type="submit"
                                    class="text-sm font-semibold text-red-600 underline-offset-4 transition-colors duration-500 ease-spring hover:text-red-700 hover:underline">
                                    Batalkan booking
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</article>
