@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.master-mobils.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Model Mobil</a>
        <h2 class="mb-6 mt-2 text-2xl">Tambah Model Mobil</h2>

        <form action="{{ route('admin.master-mobils.store') }}" method="POST" class="space-y-6">
            @include('admin.master-mobil.form')
        </form>
    </div>
@endsection
