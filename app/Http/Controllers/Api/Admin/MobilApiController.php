<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobilResource;
use App\Models\Booking;
use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\TipeMobil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

/** Armada untuk admin. Mobil yang dibuat admin langsung aktif (tanpa alur approval Super Admin). */
class MobilApiController extends Controller
{
    use RespondsJson;

    private const RELASI = ['masterMobil.tipe'];

    /** GET /api/admin/referensi — pilihan tipe dan model untuk form armada. */
    public function referensi()
    {
        return $this->ok([
            'tipe'   => TipeMobil::orderBy('nama_tipe')->get(['id', 'nama_tipe']),
            'model'  => MasterMobil::orderBy('nama')->get(['id', 'nama', 'tipe_id']),
            'status' => [Mobil::STATUS_TERSEDIA => 'Tersedia', Mobil::STATUS_MAINTENANCE => 'Maintenance', Mobil::STATUS_RUSAK => 'Rusak'],
        ]);
    }

    /** GET /api/admin/mobils?q=&status=&tipe_id=&per_page= */
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:0,1,3,4', 'tipe_id' => 'nullable|integer', 'per_page' => 'nullable|integer|min:1|max:50']);

        $mobils = Mobil::with(self::RELASI)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('tipe_id'), fn ($q) => $q->whereHas('masterMobil', fn ($m) => $m->where('tipe_id', $request->tipe_id)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('plat_nomor', 'like', "%{$request->q}%")
                ->orWhere('merk', 'like', "%{$request->q}%")
                ->orWhereHas('masterMobil', fn ($m) => $m->where('nama', 'like', "%{$request->q}%"))))
            ->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 15));

        return $this->page($mobils, MobilResource::class);
    }

    public function show(int $id)
    {
        return $this->ok(new MobilResource(Mobil::with(['masterMobil.tipe', 'images'])->findOrFail($id)));
    }

    /** POST /api/admin/mobils (multipart bila ada gambar) */
    public function store(Request $request)
    {
        $data = $request->validate($this->aturan());

        $mobil = Mobil::create(collect($data)->except('gambar')->all() + [
            'status'          => Mobil::STATUS_TERSEDIA,
            'status_approval' => Mobil::APPROVAL_APPROVED,
            'gambar'          => $this->simpanGambar($request),
        ]);

        return $this->ok(new MobilResource($mobil->load(self::RELASI)), 'Mobil ditambahkan.', 201);
    }

    /** PUT /api/admin/mobils/{id} — data teks. Gambar diganti lewat POST /mobils/{id}/gambar. */
    public function update(Request $request, int $id)
    {
        $mobil = Mobil::findOrFail($id);
        $mobil->update($request->validate($this->aturan($mobil->id, false)));

        return $this->ok(new MobilResource($mobil->fresh(self::RELASI)), 'Mobil diperbarui.');
    }

    /** POST /api/admin/mobils/{id}/gambar (multipart: gambar) */
    public function gambar(Request $request, int $id)
    {
        $request->validate(['gambar' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192']);
        $mobil = Mobil::findOrFail($id);
        $lama = $mobil->gambar;

        $mobil->update(['gambar' => $this->simpanGambar($request)]);
        if ($lama) {
            File::delete(public_path('gambar_mobil/' . $lama));
        }

        return $this->ok(new MobilResource($mobil->fresh(self::RELASI)), 'Gambar diperbarui.');
    }

    /** PATCH /api/admin/mobils/{id}/status  { status: 1 tersedia | 4 maintenance | 0 rusak } */
    public function status(Request $request, int $id)
    {
        $request->validate(['status' => ['required', Rule::in([Mobil::STATUS_TERSEDIA, Mobil::STATUS_MAINTENANCE, Mobil::STATUS_RUSAK])]]);
        $mobil = Mobil::findOrFail($id);

        if ($mobil->status === Mobil::STATUS_DISEWA) {
            return $this->fail('Mobil sedang disewa; ubah status setelah dikembalikan.');
        }
        if ((int) $request->status !== Mobil::STATUS_TERSEDIA && $this->punyaBookingAktif($mobil)) {
            return $this->fail('Mobil masih memiliki pesanan aktif; batalkan atau selesaikan dulu.');
        }

        $mobil->update(['status' => (int) $request->status]);

        return $this->ok(new MobilResource($mobil->fresh(self::RELASI)), 'Status mobil diperbarui.');
    }

    /** DELETE /api/admin/mobils/{id} — hanya mobil yang belum pernah disewa. */
    public function destroy(int $id)
    {
        $mobil = Mobil::findOrFail($id);

        if ($mobil->bookingDetails()->exists()) {
            return $this->fail('Mobil sudah punya riwayat sewa dan tidak dapat dihapus. Ubah statusnya menjadi rusak/maintenance.');
        }

        if ($mobil->gambar) {
            File::delete(public_path('gambar_mobil/' . $mobil->gambar));
        }
        $mobil->delete();

        return $this->ok(null, 'Mobil dihapus.');
    }

    private function aturan(?int $ignoreId = null, bool $denganGambar = true): array
    {
        return [
            'master_mobil_id' => 'required|exists:master_mobils,id',
            'plat_nomor'      => ['required', 'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/', Rule::unique('mobils', 'plat_nomor')->ignore($ignoreId)],
            'merk'            => 'required|string|max:100',
            'tahun'           => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'harga_sewa'      => 'required|integer|min:0',
            'harga_all_in'    => 'nullable|integer|min:0',
        ] + ($denganGambar ? ['gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192'] : []);
    }

    private function simpanGambar(Request $request): ?string
    {
        if (! $request->hasFile('gambar')) {
            return null;
        }
        $file = $request->file('gambar');
        $nama = $file->hashName(); // nama acak + ekstensi dari isi file
        $file->move(public_path('gambar_mobil'), $nama);

        return $nama;
    }

    private function punyaBookingAktif(Mobil $mobil): bool
    {
        return $mobil->bookingDetails()
            ->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_ONGOING])->exists();
    }
}
