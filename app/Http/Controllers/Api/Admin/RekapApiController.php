<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Services\RekapService;
use Illuminate\Http\Request;

class RekapApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/rekap?bulan=YYYY-MM (bawaan: bulan ini) */
    public function bulanan(Request $request, RekapService $service)
    {
        $request->validate(['bulan' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']]);

        return $this->ok($service->bulanan($request->input('bulan', now()->format('Y-m'))));
    }
}
