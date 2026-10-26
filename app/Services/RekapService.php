<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use Carbon\Carbon;

/**
 * Rekap operasional bulanan. Pesanan dibatalkan tidak dihitung sebagai sewa;
 * pendapatan dihitung dari pembayaran yang benar-benar lunas pada bulan itu (dasar kas).
 */
class RekapService
{
    /** @param string $bulan format Y-m, mis. 2026-10 */
    public function bulanan(string $bulan): array
    {
        $awal  = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $pesanan = Booking::whereBetween('tanggal_booking', [$awal, $akhir])
            ->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        $lunas = Pembayaran::where('status_pembayaran', 1)
            ->whereBetween('tanggal_pembayaran', [$awal, $akhir])
            ->selectRaw('jenis, SUM(jumlah) as total')->groupBy('jenis')->pluck('total', 'jenis');

        $denda = (int) Pengembalian::whereBetween('tanggal_kembali_aktual', [$awal, $akhir])->sum('nominal_denda');

        // Unit sewa yang mulai pada bulan itu dan tidak dibatalkan.
        $unit = BookingDetail::with('mobil.masterMobil')
            ->whereBetween('tanggal_sewa', [$awal, $akhir])
            ->where('status', '!=', Booking::STATUS_CANCELED)
            ->get();

        $perMobil = $unit->groupBy('mobil_id')->map(fn ($g) => [
            'mobil_id'    => $g->first()->mobil_id,
            'nama'        => $g->first()->mobil?->masterMobil?->nama,
            'plat_nomor'  => $g->first()->mobil?->plat_nomor,
            'jumlah_sewa' => $g->count(),
            'total_hari'  => (int) $g->sum('lama_sewa'),
        ])->sortByDesc('jumlah_sewa')->values();

        $totalArmada = Mobil::count();
        $hariBulan   = $awal->daysInMonth;

        return [
            'bulan' => $bulan,
            'pesanan' => [
                'total'      => (int) $pesanan->sum(),
                'booked'     => (int) ($pesanan[Booking::STATUS_BOOKED] ?? 0),
                'berjalan'   => (int) ($pesanan[Booking::STATUS_ONGOING] ?? 0),
                'selesai'    => (int) ($pesanan[Booking::STATUS_DONE] ?? 0),
                'dibatalkan' => (int) ($pesanan[Booking::STATUS_CANCELED] ?? 0),
            ],
            'pendapatan' => [
                'dp'         => (int) ($lunas[1] ?? 0),
                'pelunasan'  => (int) ($lunas[2] ?? 0),
                'total'      => (int) $lunas->sum(),
                'denda_tercatat' => $denda,
            ],
            'armada' => [
                'total_unit_disewa' => $unit->count(),
                'total_hari_sewa'   => (int) $unit->sum('lama_sewa'),
                'utilisasi_persen'  => $totalArmada > 0 ? round($unit->sum('lama_sewa') / ($totalArmada * $hariBulan) * 100, 1) : 0.0,
                'per_mobil'         => $perMobil,
            ],
        ];
    }
}
