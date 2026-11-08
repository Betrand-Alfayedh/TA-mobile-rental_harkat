<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\TipeMobil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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

    /** Aturan isian yang sama untuk tambah dan ubah; $ignoreId dipakai agar plat milik unit ini tidak dianggap kembar. */
    private function aturan(?int $ignoreId = null): array
    {
        return [
            'master_mobil_id' => 'required|exists:master_mobils,id',
            'plat_nomor' => [
                'required',
                'unique:mobils,plat_nomor' . ($ignoreId ? ',' . $ignoreId : ''),
                'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/',
            ],
            'merk' => 'required',
            'tahun' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'harga_sewa' => 'required|numeric|min:0',
            'harga_all_in' => 'nullable|numeric|min:0',
            'gambar' => ($ignoreId ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg,webp|max:8192',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ];
    }

    private function simpanGambarUtama(Request $request): string
    {
        $file = $request->file('gambar');
        $nama = $file->hashName(); // nama acak + ekstensi dari isi file, bukan dari klien
        $file->move(public_path('gambar_mobil'), $nama);

        return $nama;
    }

    private function simpanFotoTambahan(Request $request, Mobil $mobil): void
    {
        foreach ($request->file('images', []) as $img) {
            $nama = $img->hashName();
            $img->move(public_path('mobil_images_additionals'), $nama);
            $mobil->images()->create(['image_path' => $nama]);
        }
    }

    public function store(Request $request)
    {
        $request->validate($this->aturan());

        // Admin menambah unit secara langsung; tidak ada persetujuan lanjutan.
        $mobil = Mobil::create($request->only('master_mobil_id', 'plat_nomor', 'merk', 'tahun', 'harga_sewa', 'harga_all_in') + [
            'status' => Mobil::STATUS_TERSEDIA,
            'status_approval' => Mobil::APPROVAL_APPROVED,
            'gambar' => $this->simpanGambarUtama($request),
        ]);
        $this->simpanFotoTambahan($request, $mobil);

        return redirect()->route('admin.mobil.index')->with('success', 'Unit mobil berhasil ditambahkan.');
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
        $request->validate($this->aturan($mobil->id));

        $mobil->fill($request->only('master_mobil_id', 'plat_nomor', 'merk', 'tahun', 'harga_sewa', 'harga_all_in'));

        $gambarLama = null;
        if ($request->hasFile('gambar')) {
            $gambarLama = $mobil->gambar;
            $mobil->gambar = $this->simpanGambarUtama($request);
        }
        $mobil->save();

        if ($gambarLama) {
            File::delete(public_path('gambar_mobil/' . $gambarLama));
        }
        $this->simpanFotoTambahan($request, $mobil);

        return redirect()->route('admin.mobil.index')->with('success', 'Unit mobil berhasil diperbarui.');
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
