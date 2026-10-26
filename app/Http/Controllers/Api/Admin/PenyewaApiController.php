<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\PenyewaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Data penyewa (pelanggan, peran 4). Penyewa yang dicatat admin diberi kata sandi acak yang tidak
 * diketahui siapa pun; mereka masuk lewat atur-ulang kata sandi bila ingin memakai aplikasi.
 */
class PenyewaApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/penyewa?q= */
    public function index(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:50']);

        $p = User::where('role', 4)
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->q}%")
                ->orWhere('no_hp', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")))
            ->withCount('bookings')
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 15));

        return $this->page($p, PenyewaResource::class);
    }

    public function show(int $id)
    {
        return $this->ok(new PenyewaResource(User::where('role', 4)->withCount('bookings')->findOrFail($id)));
    }

    /** POST /api/admin/penyewa  { name, email, no_hp, alamat, asal_kota } */
    public function store(Request $request)
    {
        $data = $request->validate($this->aturan());

        $u = User::create($data + ['role' => 4, 'password' => Hash::make(Str::random(32))]);

        return $this->ok(new PenyewaResource($u), 'Penyewa ditambahkan.', 201);
    }

    /** PUT /api/admin/penyewa/{id} */
    public function update(Request $request, int $id)
    {
        $u = User::where('role', 4)->findOrFail($id);
        $u->update($request->validate($this->aturan($u->id)));

        return $this->ok(new PenyewaResource($u->fresh()), 'Data penyewa diperbarui.');
    }

    private function aturan(?int $ignoreId = null): array
    {
        return [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'no_hp'     => 'required|string|max:20',
            'alamat'    => 'required|string|max:255',
            'asal_kota' => 'required|string|max:255',
        ];
    }
}
