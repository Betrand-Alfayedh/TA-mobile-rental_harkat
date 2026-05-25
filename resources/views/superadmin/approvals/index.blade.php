@extends('layouts.superadmin')

@section('content')
    <div class="max-w-6xl mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold mb-6">Approval Management</h1>

        {{-- Tabs --}}
        <div class="flex gap-4 mb-6">
            <a href="{{ route('superadmin.approvals.index', ['status' => 2]) }}"
                class="px-4 py-2 rounded-lg {{ $status == 2 ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                Pending
            </a>
            <a href="{{ route('superadmin.approvals.index', ['status' => 1]) }}"
                class="px-4 py-2 rounded-lg {{ $status == 1 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                Approved
            </a>
            <a href="{{ route('superadmin.approvals.index', ['status' => 0]) }}"
                class="px-4 py-2 rounded-lg {{ $status == 0 ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                Rejected
            </a>
        </div>

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full text-sm text-left border">
                <thead class="bg-gray-100 text-gray-700">
                    <trclass="border-t cursor-pointer"
                    x-data="{ open: false }"
                    @click="open = !open">
                    <th class="px-4 py-3 border">ID</th>
                    <th class="px-4 py-3 border">Tipe</th>
                    <th class="px-4 py-3 border">Data</th>
                    <th class="px-4 py-3 border">Requested By</th>
                    <th class="px-4 py-3 border">Status</th>
                    <th class="px-4 py-3 border">Aksi</th>
                    </trclass=>
                </thead>
                <tbody x-data="{ openRow: null }">
                    @forelse($approvals as $approval)
                        <tr class="border-t cursor-pointer"
                            @click="openRow === {{ $approval->id }} ? openRow = null : openRow = {{ $approval->id }}">
                            {{-- ID --}}
                            <td class="px-4 py-3">{{ $approval->id }}</td>
                            {{-- Tipe --}}
                            <td class="px-4 py-3">{{ class_basename($approval->approvable_type) }}</td>
                            {{-- Data --}}
                            <td class="px-4 py-3">
                                @if ($approval->approvable_type === 'App\\Models\\Mobil')
                                    <strong>{{ $approval->approvable->masterMobil->nama }}</strong><br>
                                    {{ $approval->approvable->merk }} - {{ $approval->approvable->plat_nomor }}
                                @elseif($approval->approvable_type === 'App\\Models\\Supir')
                                    <strong>{{ $approval->approvable->nama }}</strong><br>
                                    {{ $approval->approvable->email }}
                                @endif
                            </td>
                            {{-- Requester --}}
                            <td class="px-4 py-3">{{ $approval->requester->name }}</td>
                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded {{ $approval->status_color }}">
                                    {{ $approval->status_label }}
                                </span>
                            </td>
                            {{-- Aksi --}}
                            <td class="px-4 py-3">
                                @if ($approval->status == 2)
                                    <div class="flex gap-2">
                                        {{-- Approve --}}
                                        <form action="{{ route('superadmin.approvals.approve', $approval->id) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form action="{{ route('superadmin.approvals.reject', $approval->id) }}"
                                            method="POST" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="note" placeholder="Alasan"
                                                class="border rounded px-2 py-1 text-sm">
                                            <button type="submit"
                                                class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-indigo-600">Detail ↓</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Detail --}}
                        <tr x-show="openRow === {{ $approval->id }}" x-transition @click.stop class="bg-gray-50">
                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @if (empty($approval->old_data))
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">
                                        Created
                                    </span>
                                @else
                                    <span class="ml-2 px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">
                                        Updated
                                    </span>
                                @endif
                            </td>

                            <td colspan="6" class="px-6 py-4">
                                @if (empty($approval->old_data))
                                    <h3 class="font-semibold mb-2">Data Baru</h3>
                                    <ul class="list-disc pl-5 text-sm text-gray-700">
                                        @foreach ($approval->new_data ?? [] as $key => $val)
                                            @php
                                                // Jika key harga, format pakai number_format
                                                if (in_array($key, ['harga_sewa', 'harga_all_in'])) {
                                                    $val = 'Rp ' . number_format($val, 0, ',', '.');
                                                }
                                            @endphp
                                            <li>
                                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                <span class="text-green-600">{{ $val }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <h3 class="font-semibold mb-2">Perubahan Data</h3>
                                    <ul class="list-disc pl-5 text-sm text-gray-700">
                                        @foreach ($approval->new_data ?? [] as $key => $val)
                                            @php
                                                $oldVal = $approval->old_data[$key] ?? null;

                                                // Format harga
                                                if (in_array($key, ['harga_sewa', 'harga_all_in'])) {
                                                    $val = 'Rp ' . number_format($val, 0, ',', '.');
                                                    $oldVal =
                                                        $oldVal !== null
                                                            ? 'Rp ' . number_format($oldVal, 0, ',', '.')
                                                            : '-';
                                                }
                                            @endphp

                                            @if ($val != $oldVal)
                                                <li>
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                    {{-- Kalo field gambar, tampilkan preview --}}
                                                    @if (Str::contains($key, 'gambar'))
                                                        @if ($oldVal)
                                                            <img src="{{ asset('public/gambar_mobil/' . $oldVal) }}" alt="Old Image"
                                                                class="h-20 inline-block rounded border">
                                                            →
                                                        @endif
                                                        <img src="{{ asset('public/gambar_mobil/' . $val) }}" alt="New Image"
                                                            class="h-20 inline-block rounded border">
                                                    @else
                                                        <span class="text-red-600 line-through">{{ $oldVal ?? '-' }}</span>
                                                        →
                                                        <span class="text-green-600">{{ $val }}</span>
                                                    @endif
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-gray-500">
                                Belum ada data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
