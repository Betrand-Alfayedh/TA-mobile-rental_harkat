@extends('layouts.landing') {{-- atau layout utama lu --}}

@section('content')
    <div class="max-w-7xl mx-auto px-6 pt-20">
        <h1 class="text-3xl font-bold mb-8 text-gray-800">🚗 Armada Harkat</h1>

        <!-- Search & Filter -->
        <form id="filterForm" method="GET"
            class="flex flex-col md:flex-row items-center justify-between gap-3 mb-8 bg-white shadow-md rounded-2xl p-4">
            <!-- Search -->
            <div class="relative w-full md:w-1/2">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                    🔍
                </span>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                    placeholder="Cari mobil..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-300 focus:outline-none transition">
            </div>

            <!-- Filter Tipe -->
            <select name="type" id="typeFilter"
                class="w-full md:w-1/4 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-300 focus:outline-none transition">
                <option value="">Semua Tipe</option>
                @foreach ($tipeMobils as $tipe)
                    <option value="{{ $tipe->nama_tipe }}" {{ request('type') == $tipe->nama_tipe ? 'selected' : '' }}>
                        {{ $tipe->nama_tipe }}
                    </option>
                @endforeach
            </select>
        </form>

        <!-- List Mobil -->
        <div id="mobilList">
            @include('customer.mobil.partials.list-mobil', ['mobils' => $mobils])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let timer;

            function fetchMobils() {
                $.ajax({
                    url: "{{ route('mobil.index') }}",
                    data: $('#filterForm').serialize(),
                    success: function(data) {
                        $('#mobilList').html(data);
                    }
                });
            }

            // Live search dengan debounce 300ms
            $('#searchInput').on('keyup', function() {
                clearTimeout(timer);
                timer = setTimeout(fetchMobils, 300);
            });

            // Auto filter saat ganti tipe
            $('#typeFilter').on('change', function() {
                fetchMobils();
            });
        });
    </script>
@endpush
