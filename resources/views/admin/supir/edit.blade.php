@extends('layouts.admin')

@section('content')
    <div class="max-w-xl mx-auto py-6 px-4">
        <h2 class="text-xl font-bold mb-4">Edit Supir</h2>

        <form action="{{ route('admin.supir.update', $supir->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6 bg-white p-6 shadow-md rounded">
            @method('PUT')
            @include('admin.supir.form')
        </form>
    </div>
@endsection
