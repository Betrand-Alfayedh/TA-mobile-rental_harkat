@extends('layouts.superadmin')

@section('title', 'Kelola Admin')

@section('content')
    <h2 class="text-2xl font-bold mb-6">Kelola Users</h2>

    <a href="{{ route('superadmin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg mb-4 inline-block">+
        Tambah Users</a>
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:gap-4">
        <input type="text" id="search" placeholder="Cari nama atau email" value="{{ $search ?? '' }}"
            class="border rounded px-2 py-1 w-full md:w-64">

        <select id="role" class="border rounded px-2 py-1">
            <option value="">Semua Role</option>
            <option value="2" {{ ($role ?? '') == 2 ? 'selected' : '' }}>Admin</option>
            <option value="3" {{ ($role ?? '') == 3 ? 'selected' : '' }}>Supir</option>
            <option value="4" {{ ($role ?? '') == 4 ? 'selected' : '' }}>Customer</option>
        </select>
    </div>
    <div id="table-wrapper">
        @include('superadmin.users.partials.table', ['users' => $users])
    </div>
    <script>
        const searchInput = document.getElementById('search');
        const roleSelect = document.getElementById('role');
        const tableWrapper = document.getElementById('table-wrapper');

        function fetchUsers() {
            const search = searchInput.value;
            const role = roleSelect.value;

            fetch(`{{ route('superadmin.users.index') }}?search=${search}&role=${role}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => tableWrapper.innerHTML = html);
        }

        searchInput.addEventListener('input', () => {
            fetchUsers();
        });

        roleSelect.addEventListener('change', () => {
            fetchUsers();
        });
    </script>
@endsection
