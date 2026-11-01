<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Services\LaporanKondisiService;
use Illuminate\Http\Request;

/** Laporan kondisi kendaraan per unit sewa (sama dengan API admin). */
class LaporanKondisiController extends Controller
{
    public function show(BookingDetail $detail)
    {
        $detail->load(['mobil.masterMobil', 'booking.user']);
        $laporans = $detail->laporanKondisis()->with('pembuat')->orderBy('jenis')->get()->keyBy('jenis');

        return view('admin.laporan-kondisi.show', compact('detail', 'laporans'));
    }

    public function store(Request $request, BookingDetail $detail, LaporanKondisiService $service)
    {
        $data = $request->validate([
            'jenis'   => 'required|in:1,2',
            'kondisi' => 'required|in:1,2,3',
            'catatan' => 'nullable|string|max:500',
            'foto'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $service->simpan($detail, $request->user(), $data, $request->file('foto'));
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Laporan kondisi tersimpan.');
    }
}
