@extends('layouts.admin')

@section('content')
    <div class="max-w-xl mx-auto py-6 px-4">
        <h2 class="text-xl font-bold mb-4">Edit Pelanggan</h2>

        <form action="{{ route('admin.pelanggan.update', $pelanggan->id) }}" method="POST"
            class="space-y-6 bg-white p-6 shadow-md rounded">
            @method('PUT')
            @include('admin.pelanggan.form')
        </form>
    </div>
@endsection
