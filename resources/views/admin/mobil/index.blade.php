@extends('layouts.admin')

@section('content')
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Mobil</h2>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('admin.mobil.create') }}"
                class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow">
                + Tambah Mobil
            </a>
        </div>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left tracking-wider">No</th>
                        <th class="px-6 py-3 text-left tracking-wider">Nama
                            Mobil</th>
                        <th class="px-6 py-3 text-left tracking-wider">Plat
                            Nomor</th>
                        <th class="px-6 py-3 text-left tracking-wider">Merk</th>
                        <th class="px-6 py-3 text-left tracking-wider">Tahun
                        </th>
                        <th class="px-6 py-3 text-left tracking-wider">Harga
                            Sewa</th>
                        <th class="px-6 py-3 text-left tracking-wider">Harga
                            Sewa ALL IN</th>
                        <th class="px-6 py-3 text-left tracking-wider">Status
                        </th>
                        <th class="px-6 py-3 text-left tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($mobils as $index => $mobil)
                        @php
                            $isDisabled = $mobil->status_approval != 1;
                            $rowClass =
                                $mobil->status_approval == 0
                                    ? 'bg-red-100 opacity-80 line-through'
                                    : ($mobil->status_approval == 2
                                        ? 'bg-gray-200 opacity-70'
                                        : '');
                        @endphp

                        <tr class="{{ $rowClass }}">
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ $loop->iteration }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ $mobil->masterMobil->nama }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ $mobil->plat_nomor }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ $mobil->merk }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                {{ $mobil->tahun }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                Rp. {{ number_format($mobil->harga_sewa, 0, ',', '.') }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm {{ $isDisabled ? 'text-gray-500' : 'text-gray-700' }}">
                                Rp. {{ number_format($mobil->harga_all_in, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($mobil->status_approval == 0)
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 font-bold">Data
                                        ditolak admin</span>
                                @elseif($mobil->status_approval == 2)
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">Waiting for
                                        approval</span>
                                @else
                                    @if (in_array($mobil->status, [\App\Models\Mobil::STATUS_MAINTENANCE, \App\Models\Mobil::STATUS_TERSEDIA]))
                                        <span
                                            class="badge-toggle px-2 py-1 text-xs rounded-full {{ $mobil->status_badge_class }} cursor-pointer hover:opacity-80 transition"
                                            data-id="{{ $mobil->id }}" data-status="{{ $mobil->status }}">
                                            {{ $mobil->status_text }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full {{ $mobil->status_badge_class }}">
                                            {{ $mobil->status_text }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.mobil.show', $mobil->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium {{ $isDisabled ? 'pointer-events-none opacity-50' : '' }}"
                                        title="Lihat Detail">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </a>
                                    <a href="{{ route('admin.mobil.edit', $mobil->id) }}"
                                        class="text-yellow-600 hover:text-indigo-900 font-medium {{ $isDisabled ? 'pointer-events-none opacity-50' : '' }}"
                                        title="Edit">
                                        <i data-lucide="pencil" class="w-5 h-5"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Modal Konfirmasi -->
        <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-96">
                <h2 class="text-lg font-bold mb-4">Konfirmasi Ubah Status</h2>
                <p id="statusModalText" class="mb-6 text-gray-700"></p>
                <div class="flex justify-end gap-2">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-200 rounded-md">Batal</button>
                    <button id="confirmBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md">Ya, Ubah</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".badge-toggle").forEach(function(badge) {
                badge.addEventListener("click", function() {
                    let mobilId = this.dataset.id;
                    let currentStatus = this.dataset.status;
                    let badgeEl = this;

                    let nextStatusText = currentStatus ==
                        "{{ \App\Models\Mobil::STATUS_MAINTENANCE }}" ?
                        "Tersedia" :
                        "Maintenance";

                    Swal.fire({
                        title: "Ubah Status Mobil?",
                        text: `Apakah kamu yakin ingin mengubah status mobil ini menjadi ${nextStatusText}?`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, ubah",
                        cancelButtonText: "Batal",
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/admin/mobil/update-status/${mobilId}`, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        "Content-Type": "application/json",
                                    },
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        badgeEl.textContent = data.new_status;
                                        badgeEl.dataset.status =
                                            (currentStatus ==
                                                "{{ \App\Models\Mobil::STATUS_MAINTENANCE }}"
                                                ) ?
                                            "{{ \App\Models\Mobil::STATUS_TERSEDIA }}" :
                                            "{{ \App\Models\Mobil::STATUS_MAINTENANCE }}";
                                        badgeEl.className =
                                            `badge-toggle px-2 py-1 text-xs rounded-full ${data.badge_class} cursor-pointer hover:opacity-80 transition`;

                                        Swal.fire("Berhasil!",
                                            "Status mobil berhasil diperbarui.",
                                            "success");
                                    } else {
                                        Swal.fire("Gagal!", data.message ??
                                            "Terjadi kesalahan.", "error");
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    Swal.fire("Error!", "Terjadi masalah pada server.",
                                        "error");
                                });
                        }
                    });
                });
            });
        });
    </script>
@endpush
