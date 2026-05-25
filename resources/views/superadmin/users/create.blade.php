@extends('layouts.superadmin')

@section('title', 'Tambah Users')

@section('content')
    <div class="max-w-xl mx-auto bg-white p-6 rounded-2xl shadow">
        <h2 class="text-xl font-bold mb-4">Tambah User Baru</h2>

        <form action="{{ route('superadmin.users.store') }}" method="POST">
            @csrf

            <!-- Nama -->
            <div class="mb-3">
                <label class="block text-sm font-medium">Nama</label>
                <input type="text" name="name" class="w-full border rounded p-2" required>
                @error('name')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="block text-sm font-medium">Email</label>
                <input type="email" name="email" class="w-full border rounded p-2" required>
                @error('email')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="block text-sm font-medium">Password</label>
                <input type="password" name="password" class="w-full border rounded p-2" required>
                @error('password')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role -->
            <div class="mb-3">
                <label class="block text-sm font-medium">Role</label>
                <select name="role" class="w-full border rounded p-2" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="2">Admin</option>
                    <option value="3">Supir</option>
                </select>
                @error('role')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
        </form>
    </div>
@endsection
