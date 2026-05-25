<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipeMobil;
use Illuminate\Http\Request;

class TipeMobilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $types = TipeMobil::get();
        return view('admin.tipe-mobil.index', compact('types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tipe-mobil.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_tipe' => 'required|string|max:255',
        ]);

        TipeMobil::create($request->all());

        return redirect()->route('admin.tipe-mobil.index')->with('success', 'Tipe mobil berhasil ditambahkan.');
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
    public function edit(TipeMobil $tipeMobil)
    {
        return view('admin.tipe-mobil.edit', compact('tipeMobil'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipeMobil $tipeMobil)
    {
        $request->validate([
            'nama_tipe' => 'required|string|max:255',
        ]);

        $tipeMobil->update($request->all());

        return redirect()->route('admin.tipe-mobil.index')->with('success', 'Tipe mobil berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
