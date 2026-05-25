<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterMobil;
use App\Models\TipeMobil;
use Illuminate\Http\Request;

class MasterMobilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $masterMobils = MasterMobil::with('tipe')->paginate(10);
        $tipeMobils = TipeMobil::all();
        return view('admin.master-mobil.index', compact('masterMobils','tipeMobils'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tipeMobils = TipeMobil::all();
        return view('admin.master-mobil.create', compact('tipeMobils'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|unique:master_mobils,nama',
            'tipe_id' => 'required|exists:tipe_mobils,id',
        ]);

        MasterMobil::create($request->all());

        return redirect()->route('admin.master-mobils.index')->with('success', 'Master Mobil berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterMobil $masterMobil)
    {
        $tipeMobils = TipeMobil::all();
        return view('admin.master-mobil.edit', compact('masterMobil', 'tipeMobils'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterMobil $masterMobil)
    {
        $request->validate([
            'nama' => 'required|unique:master_mobils,nama,' . $masterMobil->id,
            'tipe_id' => 'required|exists:tipe_mobils,id',
        ]);

        $masterMobil->update($request->all());

        return redirect()->route('master-mobils.index')->with('success', 'Master Mobil berhasil diupdate.');
    }

    public function updateTipe(Request $request, $id)
    {
        $request->validate([
            'tipe_id' => 'required|exists:tipe_mobils,id',
        ]);

        $masterMobil = MasterMobil::findOrFail($id);
        $masterMobil->tipe_id = $request->tipe_id;
        $masterMobil->save();

        return response()->json(['success' => true, 'message' => 'Tipe mobil berhasil diupdate!']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
