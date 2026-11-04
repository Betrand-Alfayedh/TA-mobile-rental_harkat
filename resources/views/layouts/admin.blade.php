@php
    // Daftar menu: dipakai sidebar dan penunjuk lokasi (breadcrumb) di topbar.
    $nav = [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']],
        ['label' => 'Transaksi', 'icon' => 'receipt', 'items' => [
            ['label' => 'Pesanan Sewa', 'route' => 'admin.booking.index', 'match' => ['admin.booking.*', 'admin.laporan-kondisi.*']],
            ['label' => 'Pembayaran', 'route' => 'admin.pembayaran.index', 'match' => ['admin.pembayaran.*']],
            ['label' => 'Pengembalian Mobil', 'route' => 'admin.pengembalian.index', 'match' => ['admin.pengembalian.*']],
        ]],
        ['label' => 'Armada', 'icon' => 'car', 'items' => [
            ['label' => 'Unit Mobil', 'route' => 'admin.mobil.index', 'match' => ['admin.mobil.*']],
            ['label' => 'Model Mobil', 'route' => 'admin.master-mobils.index', 'match' => ['admin.master-mobils.*']],
            ['label' => 'Jenis Mobil', 'route' => 'admin.tipe-mobil.index', 'match' => ['admin.tipe-mobil.*']],
        ]],
        ['label' => 'Pengguna', 'icon' => 'users-round', 'items' => [
            ['label' => 'Pelanggan', 'route' => 'admin.pelanggan.index', 'match' => ['admin.pelanggan.*']],
            ['label' => 'Sopir', 'route' => 'admin.supir.index', 'match' => ['admin.supir.*']],
        ]],
        ['label' => 'Laporan', 'icon' => 'bar-chart-3', 'items' => [
            ['label' => 'Rekap Bulanan', 'route' => 'admin.rekap', 'match' => ['admin.rekap']],
            ['label' => 'Unduh Laporan PDF', 'route' => 'admin.laporan', 'match' => [], 'download' => true],
        ]],
    ];

    // Cari menu yang sedang aktif untuk breadcrumb.
    $crumb = [];
    foreach ($nav as $n) {
        if (isset($n['items'])) {
            foreach ($n['items'] as $i) {
                if (request()->routeIs(...$i['match'] ?: ['-'])) {
                    $crumb = [$n['label'], $i['label']];
                }
            }
        } elseif (request()->routeIs(...$n['match'])) {
            $crumb = [$n['label']];
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif']
                    },
                    colors: {
                        forest: {
                            DEFAULT: '#0B3B2A',
                            deep: '#06261B',
                            soft: '#145A41'
                        },
                        leaf: {
                            DEFAULT: '#16A34A',
                            dark: '#12873E'
                        },
                        mint: '#EAF6EE'
                    }
                }
            }
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            background: #F3F6F4;
            color: #14231B;
        }

        [x-cloak] {
            display: none !important;
        }

        /* ── Judul halaman ── */
        main h1,
        main h2 {
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0E1A14;
        }

        /* ── Tabel ── */
        main table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .875rem;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #E1E9E4, 0 1px 3px rgba(11, 59, 42, .06);
        }

        main table table {
            box-shadow: none;
            border-radius: 10px;
            border: 1px solid #E1E9E4;
        }

        main table th,
        main table th[class] {
            background: #F0F5F2;
            color: #4A6055;
            font-weight: 600;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .8rem 1rem;
            text-align: left;
            border: 0;
            border-bottom: 1px solid #E1E9E4;
        }

        main table td,
        main table td[class] {
            padding: .85rem 1rem;
            border: 0;
            border-bottom: 1px solid #EDF2EE;
            vertical-align: middle;
        }

        main table tbody tr:last-child td {
            border-bottom: 0;
        }

        main table tbody tr:hover > td {
            background: #F8FBF9;
        }

        /* ── Isian formulir ── */
        main input:not([type=checkbox]):not([type=radio]):not([type=file]):not([type=hidden]),
        main select,
        main textarea {
            border: 1px solid #D3DFD8 !important;
            border-radius: 10px !important;
            background-color: #fff;
            transition: box-shadow .2s, border-color .2s;
        }

        main input:focus,
        main select:focus,
        main textarea:focus {
            outline: none !important;
            border-color: #16A34A !important;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, .15) !important;
        }

        /* ── Warna aksi utama: biru lama diganti hijau merek ── */
        main .bg-blue-500,
        main .bg-blue-600 {
            background-color: #16A34A !important;
        }

        main .hover\:bg-blue-600:hover,
        main .hover\:bg-blue-700:hover {
            background-color: #12873E !important;
        }

        main .text-blue-500,
        main .text-blue-600 {
            color: #12873E !important;
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
            border: 0;
            border-radius: 16px;
            box-shadow: 0 24px 60px -20px rgba(11, 59, 42, .45);
        }

        dialog::backdrop {
            background: rgba(6, 38, 27, .45);
        }
    </style>
</head>

<body class="h-full" x-data="{ sidebarOpen: false }" :class="{ 'overflow-hidden': sidebarOpen }">

    <x-admin.sidebar :nav="$nav" />

    <div class="lg:pl-64 min-h-screen flex flex-col">
        <x-admin.topbar :crumb="$crumb" />

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @yield('content')
        </main>
    </div>

    {{-- lucide icons --}}
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
    <script>
        (lucide.createIcons || lucide.replace)()
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>

</html>
