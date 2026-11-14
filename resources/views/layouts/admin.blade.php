@php
    // Daftar menu: dipakai sidebar dan penunjuk lokasi (breadcrumb) di topbar.
    $nav = [
        ['label' => 'Menu utama', 'items' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']],
        ]],
        ['label' => 'Transaksi', 'items' => [
            ['label' => 'Pesanan Sewa', 'icon' => 'receipt', 'route' => 'admin.booking.index', 'match' => ['admin.booking.*', 'admin.laporan-kondisi.*']],
            ['label' => 'Pembayaran', 'icon' => 'wallet', 'route' => 'admin.pembayaran.index', 'match' => ['admin.pembayaran.*'], 'badge' => \App\Models\Pembayaran::where('status_pembayaran', 2)->count()],
            ['label' => 'Pengembalian Mobil', 'icon' => 'undo-2', 'route' => 'admin.pengembalian.index', 'match' => ['admin.pengembalian.*']],
        ]],
        ['label' => 'Armada', 'items' => [
            ['label' => 'Unit Mobil', 'icon' => 'car', 'route' => 'admin.mobil.index', 'match' => ['admin.mobil.*']],
            ['label' => 'Model Mobil', 'icon' => 'layers', 'route' => 'admin.master-mobils.index', 'match' => ['admin.master-mobils.*']],
            ['label' => 'Jenis Mobil', 'icon' => 'tags', 'route' => 'admin.tipe-mobil.index', 'match' => ['admin.tipe-mobil.*']],
        ]],
        ['label' => 'Pengguna', 'items' => [
            ['label' => 'Pelanggan', 'icon' => 'users-round', 'route' => 'admin.pelanggan.index', 'match' => ['admin.pelanggan.*']],
            ['label' => 'Sopir', 'icon' => 'user-check', 'route' => 'admin.supir.index', 'match' => ['admin.supir.*']],
        ]],
        ['label' => 'Laporan', 'items' => [
            ['label' => 'Rekap Bulanan', 'icon' => 'bar-chart-3', 'route' => 'admin.rekap', 'match' => ['admin.rekap']],
            ['label' => 'Unduh Laporan PDF', 'icon' => 'file-down', 'route' => 'admin.laporan', 'match' => []],
        ]],
    ];

    // Cari menu yang sedang aktif untuk breadcrumb.
    $crumb = [];
    foreach ($nav as $grup) {
        foreach ($grup['items'] as $i) {
            if ($i['match'] && request()->routeIs(...$i['match'])) {
                $crumb = [$grup['label'], $i['label']];
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    <title>{{ end($crumb) ?: 'Panel Admin' }} - Rental Mobil Harkat Yogyakarta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    <style>
        body {
            font-family: 'Geist Variable', Geist, ui-sans-serif, system-ui, sans-serif;
            background: #fff;
            color: #17171A;
            -webkit-font-smoothing: antialiased;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Label kecil dan angka memakai huruf monospace, seperti di panel referensi. */
        .mono {
            font-family: 'Geist Mono Variable', 'Geist Mono', ui-monospace, monospace;
        }

        .label {
            font-family: 'Geist Mono Variable', 'Geist Mono', ui-monospace, monospace;
            font-size: .72rem;
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #86847D;
        }

        /* ── Panel: cangkang abu hangat, isinya kartu putih ── */
        .panel {
            background: #F3F2EE;
            border: 1px solid #E2DFD8;
            border-radius: 18px;
            padding: 6px;
        }

        .panel>.label {
            display: block;
            padding: 10px 12px 12px;
        }

        .panel-card {
            background: #fff;
            border: 1px solid #E2DFD8;
            border-radius: 13px;
        }

        /* ── Judul halaman ── */
        main h1,
        main h2 {
            font-weight: 600;
            letter-spacing: -0.02em;
            color: #17171A;
        }

        /* ── Tabel ── */
        main table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .875rem;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 0 0 1px #E2DFD8;
        }

        /* Sudut dibulatkan lewat sel, supaya tabel lebar tidak terpotong dan bisa digulir. */
        main table thead th:first-child { border-top-left-radius: 14px; }
        main table thead th:last-child { border-top-right-radius: 14px; }
        main table tbody tr:last-child td:first-child { border-bottom-left-radius: 14px; }
        main table tbody tr:last-child td:last-child { border-bottom-right-radius: 14px; }

        main table table {
            box-shadow: none;
            border-radius: 10px;
            border: 1px solid #E2DFD8;
        }

        main table th,
        main table th[class] {
            background: #F3F2EE;
            color: #86847D;
            font-family: 'Geist Mono Variable', 'Geist Mono', ui-monospace, monospace;
            font-weight: 500;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: .8rem 1rem;
            text-align: left;
            border: 0;
            border-bottom: 1px solid #E2DFD8;
        }

        main table td,
        main table td[class] {
            padding: .85rem 1rem;
            border: 0;
            border-bottom: 1px solid #EFEDE8;
            vertical-align: middle;
        }

        main table tbody tr:last-child td {
            border-bottom: 0;
        }

        main table tbody tr:hover>td {
            background: #FAF9F6;
        }

        /* ── Isian formulir ── */
        main input:not([type=checkbox]):not([type=radio]):not([type=file]):not([type=hidden]),
        main select,
        main textarea {
            border: 1px solid #E2DFD8 !important;
            border-radius: 10px !important;
            background-color: #fff;
            transition: box-shadow .2s, border-color .2s;
        }

        main ::placeholder {
            color: #9ca3af;
            opacity: 1;
        }

        main input:focus,
        main select:focus,
        main textarea:focus {
            outline: none !important;
            border-color: #17171A !important;
            box-shadow: 0 0 0 3px rgba(23, 23, 26, .08) !important;
        }

        /* ── Tombol aksi: biru dan hijau lama jadi hitam ── */
        main .bg-blue-500,
        main .bg-blue-600,
        main .bg-green-600,
        main .bg-green-500 {
            background-color: #17171A !important;
        }

        main .hover\:bg-blue-600:hover,
        main .hover\:bg-blue-700:hover,
        main .hover\:bg-green-700:hover {
            background-color: #000 !important;
        }

        main .text-blue-500,
        main .text-blue-600 {
            color: #17171A !important;
        }

        /* ── Lencana status: pil putih bergaris dengan titik warna ── */
        main .rounded-full.bg-green-100,
        main .rounded-full.bg-amber-100,
        main .rounded-full.bg-yellow-100,
        main .rounded-full.bg-red-100,
        main .rounded-full.bg-gray-100,
        main .rounded-full.bg-blue-100 {
            background: #fff !important;
            border: 1px solid #E2DFD8;
            font-weight: 500;
            white-space: nowrap;
        }

        main .rounded-full.bg-green-100::before,
        main .rounded-full.bg-amber-100::before,
        main .rounded-full.bg-yellow-100::before,
        main .rounded-full.bg-red-100::before,
        main .rounded-full.bg-gray-100::before,
        main .rounded-full.bg-blue-100::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            margin-right: 6px;
            vertical-align: 1px;
            background: currentColor;
        }

        /* Ikon aksi di tabel dibuat netral; ikon hapus tetap merah. */
        main table td a svg,
        main table td button svg {
            stroke: #5B5A55;
        }

        main table td .text-red-500 svg,
        main table td .text-red-600 svg,
        main table td svg.text-red-500,
        main table td svg.text-red-600 {
            stroke: #DC2626;
        }

        main table td .text-indigo-600,
        main table td .text-yellow-600 {
            color: #5B5A55;
        }

        main button,
        main a.inline-block,
        main a.inline-flex {
            transition: background-color .2s, transform .15s;
        }

        main button:active {
            transform: scale(.98);
        }

        dialog {
            border: 1px solid #E2DFD8;
            border-radius: 16px;
            box-shadow: 0 24px 60px -24px rgba(23, 23, 26, .35);
        }

        dialog::backdrop {
            background: rgba(23, 23, 26, .35);
        }

        :focus-visible {
            outline: 2px solid #17171A;
            outline-offset: 2px;
        }
    </style>
</head>

<body class="h-full" x-data="{ sidebarOpen: false }" :class="{ 'overflow-hidden': sidebarOpen }">

    <x-admin.sidebar :nav="$nav" />

    <div class="flex min-h-screen flex-col lg:pl-64">
        <x-admin.topbar :crumb="$crumb" />

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    {{-- Ikon lucide sudah dirender oleh bundel Vite (resources/js/app.js). --}}
    @stack('scripts')
</body>

</html>
