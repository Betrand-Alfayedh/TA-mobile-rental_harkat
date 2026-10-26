<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $role = $request->role;

        $users = User::where('role', '<>', 1);

        if ($search) {
            $users->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $users->where('role', $role);
        }

        $users = $users->orderBy('id', 'desc')->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('superadmin.users.partials.table', compact('users'))->render();
        }

        return view('superadmin.users.index', compact('users', 'role', 'search'));
    }


    public function create()
    {
        return view('superadmin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|integer|in:2,3,4', // Admin, Supir, Customer
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('superadmin.users.index')->with('success', 'User berhasil ditambahkan');
    }


    /** SuperAdmin tidak boleh diubah/dihapus lewat menu ini (termasuk akun sendiri). */
    private function guardTarget(User $user): void
    {
        abort_if($user->role === 1, 403, 'Akun SuperAdmin tidak dapat diubah atau dihapus dari sini.');
    }

    public function edit(User $user)
    {
        $this->guardTarget($user);

        return view('superadmin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->guardTarget($user);

        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role'     => 'required|integer|in:2,3,4',
        ]);

        $data = $request->only(['name', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('superadmin.users.index')->with('success', 'User berhasil diupdate');
    }


    public function destroy(User $user)
    {
        $this->guardTarget($user);

        // Menghapus user men-cascade ke booking, detail, dan pembayaran → riwayat keuangan hilang.
        if ($user->bookings()->exists()) {
            return back()->with('error', 'User ini punya riwayat booking sehingga tidak dapat dihapus.');
        }

        $user->delete();
        return redirect()->route('superadmin.users.index')->with('success', 'User berhasil dihapus');
    }
}
