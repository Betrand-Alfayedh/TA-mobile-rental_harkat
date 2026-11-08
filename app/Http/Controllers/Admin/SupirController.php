<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Supir;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupirController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $supirs = Supir::with('user')->get();
        return view('admin.supir.index', compact('supirs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.supir.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|max:50',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        // 1. Insert ke tabel users
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'role' => 3, // role supir
        ]);

        // 2. Handle gambar
        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('gambar_supir', 'public');
        }

        // 3. Insert ke tabel supirs. Admin menambah sopir secara langsung; tidak ada persetujuan lanjutan.
        Supir::create([
            'user_id' => $user->id,
            'status' => 1,
            'gambar' => $gambarPath,
            'status_approval' => 1,
        ]);

        return redirect()->route('admin.supir.index')
            ->with('success', 'Sopir berhasil ditambahkan dan langsung aktif.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Supir $supir)
    {
        return view('admin.supir.show', compact('supir'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supir $supir)
    {
        return view('admin.supir.edit', compact('supir'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supir $supir)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($supir->user_id),
            ],
            'password' => 'nullable|string|min:8|max:50',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'status' => 'nullable|in:0,1',
            'aktif' => 'nullable|boolean',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        $bertugas = (int) $supir->status === 2;
        if ($bertugas && $request->has('aktif') && ! $request->boolean('aktif')) {
            return back()->withErrors(['aktif' => 'Sopir sedang bertugas; nonaktifkan setelah tugasnya selesai.'])->withInput();
        }

        DB::transaction(function () use ($request, $supir, $bertugas) {
            $user = $supir->user;
            $user->fill($request->only('name', 'email', 'no_hp', 'alamat'));
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            if ($request->has('aktif')) {
                $user->aktif = $request->boolean('aktif');
            }
            $user->save();

            $dataSupir = [];
            if ($request->hasFile('gambar')) {
                $dataSupir['gambar'] = $request->file('gambar')->store('gambar_supir', 'public');
            }
            // Status sopir yang sedang bertugas diatur oleh alur penugasan, bukan dari form ini.
            if (! $bertugas && $request->filled('status')) {
                $dataSupir['status'] = (int) $request->status;
            }
            if ($dataSupir) {
                $supir->update($dataSupir);
            }
        });

        return redirect()->route('admin.supir.index')->with('success', 'Data sopir berhasil diperbarui.');
    }
}
