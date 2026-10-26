<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Services\PengembalianService;
use Illuminate\Http\Request;

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

    public function store(Request $request, PengembalianService $service)
    {
        $request->validate([
            'booking_detail_id' => 'required|exists:booking_details,id',
            'tanggal_kembali_aktual' => 'required|date',
            'kondisi' => 'required|in:0,1',
            'catatan' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $service->catat(
                (int) $request->booking_detail_id,
                $request->only(['tanggal_kembali_aktual', 'kondisi', 'catatan']),
                $request->file('gambar'),
                auth()->user()
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pengembalian.index')
            ->with('success', 'Pengembalian berhasil dicatat.');
    }
}
