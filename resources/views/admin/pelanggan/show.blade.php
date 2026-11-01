@extends('layouts.admin')

@section('content')
    <div class="max-w-xl mx-auto py-8">
        <div class="bg-white rounded-2xl shadow p-8 border border-gray-100">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Detail Pelanggan</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex"><dt class="w-32 text-gray-500">Nama</dt><dd class="font-medium">{{ $pelanggan->name }}</dd></div>
                <div class="flex"><dt class="w-32 text-gray-500">Email</dt><dd>{{ $pelanggan->email }}</dd></div>
                <div class="flex"><dt class="w-32 text-gray-500">No HP</dt><dd>{{ $pelanggan->no_hp ?? '-' }}</dd></div>
                <div class="flex"><dt class="w-32 text-gray-500">Alamat</dt><dd>{{ $pelanggan->alamat ?? '-' }}</dd></div>
            </dl>
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('admin.pelanggan.index') }}" class="px-4 py-2 rounded-lg border text-gray-600 hover:bg-gray-50">Kembali</a>
                <a href="{{ route('admin.pelanggan.edit', $pelanggan->id) }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Edit</a>
            </div>
        </div>
    </div>
@endsection
