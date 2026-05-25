@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Edit Mobil</h2>

        <form action="{{ route('admin.mobil.update', $mobilData->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6 bg-white p-6 shadow-md rounded">
            @method('PUT')

            @include('admin.mobil.form', [
                'mobilData' => $mobilData,
                'submitButtonText' => 'Update',
            ])
        </form>
    </div>
@endsection
