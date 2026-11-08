@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.supir.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Sopir</a>
        <h2 class="mb-6 mt-2 text-2xl">Tambah Sopir</h2>

        <form action="{{ route('admin.supir.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @include('admin.supir.form')
        </form>
    </div>
@endsection
