<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateMobilStatusJob;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    public function index()
    {
        $pelunasan = Pembayaran::with(['booking.user', 'booking.details.mobil'])
            ->where('jenis', 2) // pelunasan
            ->get();
        return view('admin.pengembalian.index', compact('pelunasan'));
    }
    public function show($id)
    {
        $pelunasan = Pembayaran::with(['booking.user', 'booking.details.mobil'])
            ->where('jenis', 2)
            ->findOrFail($id);

        return view('admin.pengembalian.show', compact('pelunasan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_detail_id' => 'required|exists:booking_details,id',
            'tanggal_kembali_aktual' => 'required|date',
            'kondisi' => 'required|in:0,1',
            'catatan' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::transaction(function () use ($request) {

            // Ambil booking detail beserta mobil & booking
            $bookingDetail = BookingDetail::with(['mobil', 'booking.pelunasan', 'pengembalian'])->findOrFail($request->booking_detail_id);

            // Cek apakah pengembalian untuk unit ini sudah ada
            if ($bookingDetail->pengembalian) {
                throw new \Exception("Unit mobil ini sudah dikembalikan sebelumnya.");
            }

            $rencana = Carbon::parse($bookingDetail->tanggal_kembali);
            $aktual = Carbon::parse($request->tanggal_kembali_aktual);

            $pengembalian = new Pengembalian([
                'booking_detail_id'      => $request->booking_detail_id,
                'tanggal_kembali_aktual' => $request->tanggal_kembali_aktual,
                'kondisi'                => $request->kondisi,
                'catatan'                => $request->catatan,
            ]);

            // Upload gambar
            if ($request->hasFile('gambar')) {
                $filename = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $path = $request->file('gambar')->storeAs('pengembalian', $filename, 'public');
                $pengembalian->gambar = $path;
            }

            // Hitung denda (per jam)
            if ($aktual->gt($rencana)) {
                $diffHours = $rencana->diffInHours($aktual);
                $nominalDenda = $diffHours * 50000;

                $pengembalian->denda_flag = 1;
                $pengembalian->nominal_denda = $nominalDenda;
            } else {
                $pengembalian->denda_flag = 0;
                $pengembalian->nominal_denda = 0;
            }

            $pengembalian->save();

            // Update pelunasan (Pembayaran jenis=2) hanya sekali
            if ($pengembalian->denda_flag == 1) {
                $booking = $bookingDetail->booking;
                $pelunasan = $booking->pelunasan; // hasOne where jenis=2

                if ($pelunasan) {
                    // Tambahkan denda ke jumlah saat ini
                    $pelunasan->jumlah += $pengembalian->nominal_denda;
                    $pelunasan->save();
                } else {
                    // Jika belum ada pelunasan, buat baru
                    $booking->pelunasan()->create([
                        'jumlah' => $pengembalian->nominal_denda,
                        'status' => 0,
                    ]);
                }
            }

            $bookingDetail->update(['status' => 3]);
            // Update status mobil jadi maintenance dulu
            $bookingDetail->mobil->update(['status' => Mobil::STATUS_MAINTENANCE]);
        });

        return redirect()->route('admin.pengembalian.index')
            ->with('success', 'Pengembalian berhasil dicatat.');
    }
}
