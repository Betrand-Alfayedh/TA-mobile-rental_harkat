<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\TipeMobil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MobilController extends Controller
{
    public function index()
    {
        $mobils = Mobil::with('images')->get();
        return view('admin.mobil.index', compact('mobils'));
    }

    public function create()
    {
        $tipeMobil = TipeMobil::all();
        $masterMobils = MasterMobil::all();
        return view('admin.mobil.create', compact('tipeMobil', 'masterMobils'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'master_mobil_id' => 'required|exists:master_mobils,id',
            'plat_nomor' => [
                'required',
                'unique:mobils,plat_nomor',
                'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/'
            ],
            'merk' => 'required',
            'tahun' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'harga_sewa' => 'required|numeric',
            'harga_all_in' => 'nullable|numeric',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        $data = $request->except('gambar', 'images');
        $data['status_approval'] = 2;

        // Upload gambar utama ke public/gambar_mobil
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('gambar_mobil'), $filename);
            $data['gambar'] = $filename;
        }

        $mobil = Mobil::create($data);

        // Upload additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $filename = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('mobil_images_additionals'), $filename);
                $mobil->images()->create(['image_path' => $filename]);
            }
        }

        // Approval request
        $mobil->approvals()->create([
            'requested_by' => $user->id,
            'status' => 2,
            'old_data' => null,
            'new_data' => $mobil,
        ]);

        return redirect()->route('admin.mobil.index')->with('success', "Mobil berhasil ditambahkan, menunggu approval super admin.");
    }

    public function show(Mobil $mobil)
    {
        $mobil->load('images');
        return view('admin.mobil.show', compact('mobil'));
    }

    public function edit(Mobil $mobil)
    {
        $mobilData = $mobil;
        $mobilData->load('images');
        $tipeMobil = TipeMobil::all();
        $masterMobils = MasterMobil::all();

        return view('admin.mobil.edit', compact('mobilData', 'masterMobils', 'tipeMobil'));
    }

    public function update(Request $request, Mobil $mobil)
    {
        $user = Auth::user();
        $request->validate([
            'master_mobil_id' => 'required|exists:master_mobils,id',
            'plat_nomor' => [
                'required',
                'unique:mobils,plat_nomor,' . $mobil->id,
                'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/'
            ],
            'merk' => 'required',
            'tahun' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'harga_sewa' => 'required|numeric',
            'harga_all_in' => 'nullable|numeric',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        // Snapshot data lama
        $old = $mobil->only(['master_mobil_id', 'plat_nomor', 'merk', 'tahun', 'harga_sewa', 'harga_all_in', 'status', 'gambar']);

        $data = $request->except('gambar', 'images');
        $data['status_approval'] = 2;

        // Upload gambar utama
        if ($request->hasFile('gambar')) {
            if ($mobil->gambar && file_exists(public_path('gambar_mobil/' . $mobil->gambar))) {
                unlink(public_path('gambar_mobil/' . $mobil->gambar));
            }
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('gambar_mobil'), $filename);
            $data['gambar'] = $filename;
        }

        $mobil->update($data);

        // Upload additional images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $filename = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('mobil_images_additionals'), $filename);
                $mobil->images()->create(['image_path' => $filename]);
            }
        }

        // Approval snapshot
        $mobil->approvals()->create([
            'requested_by' => $user->id,
            'status' => 2,
            'old_data' => $old,
            'new_data' => $mobil->only(['master_mobil_id', 'plat_nomor', 'merk', 'tahun', 'harga_sewa', 'harga_all_in', 'status', 'gambar']),
        ]);

        return redirect()->route('admin.mobil.index')->with('success', "Mobil berhasil diupdate, menunggu approval super admin.");
    }

    public function updateStatus($id)
    {
        $mobil = Mobil::findOrFail($id);

        if ($mobil->status === Mobil::STATUS_MAINTENANCE) {
            $mobil->status = Mobil::STATUS_TERSEDIA;
        } elseif ($mobil->status === Mobil::STATUS_TERSEDIA) {
            $mobil->status = Mobil::STATUS_MAINTENANCE;
        } else {
            return response()->json(['success' => false, 'message' => 'Status tidak bisa diubah'], 400);
        }

        $mobil->save();

        return response()->json([
            'success' => true,
            'new_status' => $mobil->status_text,
            'badge_class' => $mobil->status_badge_class,
        ]);
    }
}
