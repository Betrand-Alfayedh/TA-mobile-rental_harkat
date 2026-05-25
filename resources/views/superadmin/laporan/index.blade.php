@extends('layouts.superadmin')

@section('content')
    <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
        <span>📑</span> Daftar Laporan Admin
    </h2>

    @if ($files->isEmpty())
        <p class="text-gray-500 italic">Belum ada laporan yang digenerate admin.</p>
    @else
        <ul class="grid gap-3">
            @foreach ($files as $file)
                <li>
                    <a href="{{ asset('storage/laporan/' . basename($file)) }}" target="_blank"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-200 hover:bg-gray-100 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m2 0h.01M6 20h12a2 2 0 002-2V8a2 2 0 00-2-2h-4l-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="font-medium">{{ basename($file) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

@endsection
