<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use Illuminate\Support\Facades\File;

class LaporanController extends Controller
{
    public function generateLaporanAdmin()
    {
        // total booking
        $jumlahBooking = Booking::count();

        // jumlah mobil yang pernah dibooking
        $jumlahMobil = BookingDetail::distinct('mobil_id')->count('mobil_id');

        // total pendapatan
        $totalPendapatan = Booking::sum('total_harga');

        // rata-rata lama sewa
        $totalHari = BookingDetail::selectRaw('SUM(DATEDIFF(tanggal_kembali, tanggal_sewa)) as total_hari')->value('total_hari');
        $rataRataHari = $jumlahBooking > 0 ? round($totalHari / $jumlahBooking) : 0;

        // mobil paling sering dibooking (sekalian total hari sewa per mobil)
        $mobilPalingSering = BookingDetail::select(
            'mobil_id',
            DB::raw('COUNT(*) as jumlah'),
            DB::raw('SUM(DATEDIFF(tanggal_kembali, tanggal_sewa)) as total_hari'),
            DB::raw('SUM(
            CASE 
                WHEN booking_details.supir_id IS NOT NULL 
                    THEN mobils.harga_all_in * DATEDIFF(booking_details.tanggal_kembali, booking_details.tanggal_sewa)
                ELSE mobils.harga_sewa * DATEDIFF(booking_details.tanggal_kembali, booking_details.tanggal_sewa)
            END
        ) as total_pendapatan')
        )
            ->join('mobils', 'booking_details.mobil_id', '=', 'mobils.id')
            ->groupBy('mobil_id')
            ->orderByDesc('jumlah')
            ->take(5)
            ->with('mobil')
            ->get();


        // cari jumlah booking terbanyak
        $maxBooking = $mobilPalingSering->max('jumlah');

        // ambil semua mobil dengan jumlah terbanyak
        $mobilTerpopuler = $mobilPalingSering->where('jumlah', $maxBooking);

        // hitung persentase kontribusi tiap mobil
        foreach ($mobilTerpopuler as $item) {
            $item->kontribusi = $totalPendapatan > 0
                ? round(($item->total_pendapatan / $totalPendapatan) * 100, 2)
                : 0;
        }

        $pdf = Pdf::loadView('laporan.admin', compact(
            'jumlahBooking',
            'jumlahMobil',
            'totalPendapatan',
            'rataRataHari',
            'mobilTerpopuler'
        ));

        // bikin folder kalau belum ada
        $folderPath = storage_path('app/public/laporan');
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        // nama file unik per generate
        $filename = 'laporan_penyewaan_mobil_HarkatRentCar' . now()->format('Ymd_His') . '.pdf';
        $path = $folderPath . '/' . $filename;

        // simpan ke storage
        $pdf->save($path);

        return back()->with('success', 'Laporan berhasil digenerate dan disimpan!');
    }
}
