<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Supir;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Auth;
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
            'password' => 'required|string|max:20',
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

        // 3. Insert ke tabel supirs
        $supir = Supir::create([
            'user_id' => $user->id,
            'status' => 1,
            'gambar' => $gambarPath,
            'status_approval' => 2,
        ]);

        // 4. Request approval
        $supir->approvals()->create([
            'requested_by' => Auth::user()->id,
            'old_data' => null,
            'new_data' => [
                'name' => $supir->user->name,
                'email' => $supir->user->email,
                'no_hp' => $supir->user->no_hp,
                'alamat' => $supir->user->alamat,
                'status' => $supir->status,
                'gambar' => $supir->gambar,
            ],
        ]);

        return redirect()->route('admin.supir.index')
            ->with('success', 'Supir berhasil ditambahkan, menunggu approval super admin.');
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
            'password' => 'nullable|string|max:20',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'status' => 'required|in:0,1,2',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:8192',
        ]);

        // snapshot lama (current state)
        $old = [
            'name' => $supir->user->name,
            'email' => $supir->user->email,
            'no_hp' => $supir->user->no_hp,
            'alamat' => $supir->user->alamat,
            'status' => $supir->status,
            'gambar' => $supir->gambar,
        ];

        // snapshot baru (request)
        $gambarPath = $request->hasFile('gambar') ? $request->file('gambar')->store('gambar_supir', 'public') : $supir->gambar;

        $new = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->filled('password') ? Hash::make($request->password) : null,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'status' => $request->status,
            'gambar' => $gambarPath,
        ];
        $data['status_approval'] = 2;
        // simpan di approvals
        $supir->approvals()->create([
            'requested_by' => Auth::id(),
            'status' => 2, // pending
            'old_data' => $old,
            'new_data' => $new,
        ]);

        return redirect()->route('admin.supir.index')
            ->with('success', 'Update request supir berhasil, menunggu approval SuperAdmin.');
    }
}
