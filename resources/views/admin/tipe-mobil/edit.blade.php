@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.tipe-mobil.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Jenis Mobil</a>
        <h2 class="mb-6 mt-2 text-2xl">Edit Jenis Mobil</h2>

        <form action="{{ route('admin.tipe-mobil.update', $tipeMobil->id) }}" method="POST" class="space-y-6">
            @method('PUT')
            @include('admin.tipe-mobil.form')
        </form>
    </div>
@endsection
