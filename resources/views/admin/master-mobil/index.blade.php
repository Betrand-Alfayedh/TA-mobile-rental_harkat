@extends('layouts.admin')

@section('content')
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">Daftar Master Mobil</h2>

        @if (session('success'))
            <div class="mb-4 bg-green-100 text-green-800 px-4 py-2 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('admin.master-mobils.create') }}"
            class="mb-6 inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium px-5 py-2.5 rounded-xl shadow transition">
            ➕ Tambah Master Mobil
        </a>


        <table class="w-full border-collapse bg-white rounded-xl overflow-hidden shadow">
            <thead class="bg-gradient-to-r from-emerald-500 to-green-400 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Tipe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($masterMobils as $m)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $m->nama }}</td>
                        <td class="px-4 py-3">
                            <select
                                class="tipe-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 
                                    focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 
                                    transition cursor-pointer shadow-sm hover:bg-white"
                                data-id="{{ $m->id }}"
                                data-nama="{{ $m->nama }}">
                                @foreach ($tipeMobils as $tipe)
                                    <option value="{{ $tipe->id }}" {{ $m->tipe_id == $tipe->id ? 'selected' : '' }}>
                                        {{ $tipe->nama_tipe }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            {{ $masterMobils->links() }}
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.tipe-select').forEach(select => {
                select.addEventListener('change', function() {
                    let id = this.dataset.id;
                    let tipe_id = this.value;
                    let row = this.closest('tr');
                    let namaMobil = this.dataset.nama;
                    let newTipeText = this.options[this.selectedIndex].text; // ambil nama tipe baru
                    let oldTipeText = this.getAttribute('data-current-text'); // nama tipe lama

                    Swal.fire({
                        title: 'Konfirmasi Perubahan',
                        html: `Yakin mau ganti tipe mobil <b>${namaMobil}</b> dari <b>${oldTipeText}</b> ke <b>${newTipeText}</b>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#10B981',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, update!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/admin/master-mobils/${id}/update-tipe`, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        tipe_id
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: data.message,
                                            showConfirmButton: false,
                                            timer: 2000,
                                            timerProgressBar: true
                                        });

                                        row.classList.add('bg-green-50');
                                        setTimeout(() => row.classList.remove(
                                            'bg-green-50'), 1200);

                                        // update data-current biar konsisten
                                        this.setAttribute('data-current', tipe_id);
                                        this.setAttribute('data-current-text',
                                            newTipeText);
                                    }
                                })
                                .catch(err => console.error(err));
                        } else {
                            // ❌ batal → balikin dropdown ke value lama
                            this.value = this.getAttribute('data-current');
                        }
                    });
                });

                // simpan value & text awal
                select.setAttribute('data-current', select.value);
                select.setAttribute('data-current-text', select.options[select.selectedIndex].text);
            });
        });
    </script>
@endpush
