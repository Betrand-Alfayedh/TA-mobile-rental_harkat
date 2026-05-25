@extends('layouts.superadmin')

@section('title', 'Update Users')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-2xl shadow">
    <h2 class="text-xl font-bold mb-4">Edit User</h2>

    <form action="{{ route('superadmin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Nama -->
        <div class="mb-3">
            <label class="block text-sm font-medium">Nama</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded p-2"
                required>
            @error('name')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                class="w-full border rounded p-2" required>
            @error('email')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="block text-sm font-medium">Password (kosongkan jika tidak diubah)</label>
            <input type="password" name="password" class="w-full border rounded p-2">
            @error('password')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Role -->
        <div class="mb-3">
            <label class="block text-sm font-medium">Role</label>
            <select name="role" class="w-full border rounded p-2" required>
                <option value="2" {{ $user->role == 2 ? 'selected' : '' }}>Admin</option>
                <option value="3" {{ $user->role == 3 ? 'selected' : '' }}>Supir</option>
                <option value="4" {{ $user->role == 4 ? 'selected' : '' }}>Customer</option>    
            </select>
            @error('role')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
    </form>
</div>
@endsection
