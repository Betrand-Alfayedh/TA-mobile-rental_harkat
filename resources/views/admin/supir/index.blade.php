@extends('layouts.admin')

@section('content')
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Supir</h2>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('admin.supir.create') }}"
                class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow">
                + Tambah Supir
            </a>
        </div>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left tracking-wider">No</th>
                        <th class="px-4 py-3 text-left tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-left tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left tracking-wider">No HP</th>
                        <th class="px-4 py-3 text-left tracking-wider">Alamat</th>
                        <th class="px-4 py-3 text-left tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supirs as $supir)
                        @if ($supir->status_approval == 2)
                            <tr class="bg-gray-200 opacity-70">
                                <td colspan="7" class="text-center text-gray-600 italic">
                                    Waiting for approval SuperAdmin
                                </td>
                            </tr>
                        @elseif ($supir->status_approval == 0)
                            <tr class="bg-red-100 opacity-90">
                                <td colspan="7" class="text-center text-red-600 font-bold">
                                    Data Rejected by SuperAdmin
                                </td>
                            </tr>
                        @else
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2">{{ $supir->user->name }}</td>
                                <td class="px-4 py-2">{{ $supir->user->email }}</td>
                                <td class="px-4 py-2">{{ $supir->user->no_hp }}</td>
                                <td class="px-4 py-2">{{ $supir->user->alamat }}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $supir->status_badge_class }}">
                                        {{ $supir->status_text }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.supir.show', $supir->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium" title="Lihat Detail">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </a>
                                        <a href="{{ route('admin.supir.edit', $supir->id) }}"
                                            class="text-yellow-600 hover:text-indigo-900 font-medium" title="Edit">
                                            <i data-lucide="pencil" class="w-5 h-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($supirs->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">Belum ada data supir.</td>
                        </tr>
                    @endif
                </tbody>

            </table>
        </div>
    </div>
@endsection
