<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupirResource;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Akun sopir dikelola admin: langsung aktif dan disetujui (tanpa approval Super Admin). */
class SupirApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/supirs?q=&status=0|1|2 */
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:0,1,2', 'per_page' => 'nullable|integer|min:1|max:50']);

        $supirs = Supir::with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%")->orWhere('no_hp', 'like', "%{$request->q}%")))
            ->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 15));

        return $this->page($supirs, SupirResource::class);
    }

    public function show(int $id)
    {
        return $this->ok(new SupirResource(Supir::with('user')->findOrFail($id)));
    }

    /** POST /api/admin/supirs  { name, email, password, no_hp, alamat, gambar? } */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:50',
            'no_hp'    => 'required|string|max:20',
            'alamat'   => 'required|string|max:255',
            'gambar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $supir = DB::transaction(function () use ($data, $request) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']),
                'no_hp' => $data['no_hp'], 'alamat' => $data['alamat'], 'role' => 3]);

            return Supir::create([
                'user_id'         => $user->id,
                'status'          => 1,
                'status_approval' => 1,
                'gambar'          => $request->hasFile('gambar') ? $request->file('gambar')->store('gambar_supir', 'public') : null,
            ]);
        });

        return $this->ok(new SupirResource($supir->load('user')), 'Sopir ditambahkan.', 201);
    }

    /** PUT /api/admin/supirs/{id}  { name, email, password?, no_hp, alamat, status? 0|1, aktif? } */
    public function update(Request $request, int $id)
    {
        $supir = Supir::with('user')->findOrFail($id);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($supir->user_id)],
            'password' => 'nullable|string|min:8|max:50',
            'no_hp'    => 'required|string|max:20',
            'alamat'   => 'required|string|max:255',
            'status'   => 'nullable|in:0,1',
            'aktif'    => 'nullable|boolean',
        ]);

        $nonaktifkan = $request->has('aktif') && ! $request->boolean('aktif');
        if ($nonaktifkan && (int) $supir->status === 2) {
            return $this->fail('Sopir sedang bertugas; nonaktifkan setelah tugasnya selesai.');
        }
        if ($request->filled('status') && (int) $supir->status === 2) {
            return $this->fail('Sopir sedang bertugas, status tidak dapat diubah.');
        }

        DB::transaction(function () use ($supir, $data, $request, $nonaktifkan) {
            $supir->user->fill(collect($data)->only(['name', 'email', 'no_hp', 'alamat'])->all());
            if (! empty($data['password'])) {
                $supir->user->password = Hash::make($data['password']);
            }
            if ($request->has('aktif')) {
                $supir->user->aktif = $request->boolean('aktif');
            }
            $supir->user->save();

            if ($request->filled('status')) {
                $supir->update(['status' => (int) $request->status]);
            }
            if ($nonaktifkan || ! empty($data['password'])) {
                $supir->user->tokens()->delete(); // sesi lama tidak berlaku lagi
            }
        });

        return $this->ok(new SupirResource($supir->fresh('user')), 'Data sopir diperbarui.');
    }
}
