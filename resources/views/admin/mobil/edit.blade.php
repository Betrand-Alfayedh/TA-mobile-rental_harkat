@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.mobil.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Unit Mobil</a>
        <h2 class="mb-6 mt-2 text-2xl">Edit Unit Mobil</h2>

        <form action="{{ route('admin.mobil.update', $mobilData->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6">
            @method('PUT')

            @include('admin.mobil.form', [
                'mobilData' => $mobilData,
                'submitButtonText' => 'Simpan perubahan',
            ])
        </form>
    </div>
@endsection
