<?php

namespace App\Http\Controllers;

use App\Models\MasterMobil;
use App\Models\Mobil;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // Unit yang boleh ditampilkan: disetujui dan tidak rusak.
        $tampil = fn ($q) => $q->where('status', '<>', Mobil::STATUS_RUSAK)
            ->where('status_approval', Mobil::APPROVAL_APPROVED);

        $mobils = MasterMobil::with(['tipe', 'mobils' => $tampil])
            ->whereHas('mobils', $tampil)
            ->orderBy('nama')
            ->get();

        // Tab filter: hanya tipe yang punya mobil.
        $tipes = $mobils->pluck('tipe')->filter()->unique('id')->sortBy('nama_tipe')->values();

        $units = $mobils->flatMap->mobils;
        $stats = [
            'unit'  => $units->count(),
            'tipe'  => $tipes->count(),
            'mulai' => $units->min('harga_sewa'),
        ];

        $user = Auth::user();

        return view('main-content', compact('mobils', 'tipes', 'stats', 'user'));
    }
}
