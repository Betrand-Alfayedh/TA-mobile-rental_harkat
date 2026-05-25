<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Mobil;
use App\Models\TipeMobil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Mobil::with('masterMobil.tipe')
            ->where('status', '<>', 0)->where('status_approval', 1);

        // Search by nama master mobil
        if ($request->filled('search')) {
            $query->whereHas('masterMobil', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by tipe
        if ($request->filled('type')) {
            $query->whereHas('masterMobil.tipe', function ($q) use ($request) {
                $q->where('nama_tipe', $request->type);
            });
        }

        $mobils = $query->paginate(8)->withQueryString();
        $tipeMobils = TipeMobil::all();
        $user = Auth::user();

        if ($request->ajax()) {
            return view('customer.mobil.partials.list-mobil', compact('mobils'))->render();
        }

        return view('customer.mobil.index', compact('mobils', 'tipeMobils', 'user'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mobil = Mobil::findOrFail($id);
        $user = Auth::user();
        return view('customer.mobil.show', compact('mobil', 'user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
