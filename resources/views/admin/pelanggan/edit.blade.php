@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.pelanggan.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Pelanggan</a>
        <h2 class="mb-6 mt-2 text-2xl">Edit Pelanggan</h2>

        <form action="{{ route('admin.pelanggan.update', $pelanggan->id) }}" method="POST" class="space-y-6">
            @method('PUT')
            @include('admin.pelanggan.form')
        </form>
    </div>
@endsection
