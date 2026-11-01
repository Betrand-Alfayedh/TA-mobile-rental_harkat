<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RekapService;
use Illuminate\Http\Request;

/** Rekap operasional bulanan (sama dengan API admin). */
class RekapController extends Controller
{
    public function index(Request $request, RekapService $service)
    {
        $request->validate(['bulan' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']]);
        $bulan = $request->input('bulan', now()->format('Y-m'));

        return view('admin.rekap.index', ['rekap' => $service->bulanan($bulan), 'bulan' => $bulan]);
    }
}
