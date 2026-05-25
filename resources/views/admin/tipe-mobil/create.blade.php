@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Tambah Tipe Mobil</h2>

        <form action="{{ route('admin.tipe-mobil.store') }}" method="POST" enctype="multipart/form-data"
            class="space-y-6 bg-white p-6 shadow-md rounded">
            @include('admin.tipe-mobil.form')
        </form>
    </div>
@endsection
