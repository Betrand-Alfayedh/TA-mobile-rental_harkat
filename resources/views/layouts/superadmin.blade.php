{{-- resources/views/layouts/superadmin.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Scripts dari Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <div class="px-6 py-4 border-b">
                <h1 class="text-xl font-bold text-blue-600">Super Admin</h1>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <x-super-admin.sidebar-item href="{{ route('superadmin.dashboard') }}" :active="request()->routeIs('superadmin.dashboard')">
                    Dashboard
                </x-super-admin.sidebar-item>

                <x-super-admin.sidebar-item href="{{ route('superadmin.approvals.index') }}" :active="request()->routeIs('superadmin.approvals.*')">
                    Approvals
                </x-super-admin.sidebar-item>

                {{-- <x-super-admin.sidebar-item href="{{ route('superadmin.mobil.index') }}" :active="request()->routeIs('superadmin.mobil.*')">
                    Data Mobil
                </x-super-admin.sidebar-item> --}}

                <x-super-admin.sidebar-item href="{{ route('superadmin.listLaporan') }}" :active="request()->routeIs('superadmin.listLaporan')">
                    Laporan
                </x-super-admin.sidebar-item>

                <x-super-admin.sidebar-item href="{{ route('superadmin.users.index') }}" :active="request()->routeIs('superadmin.users.*')">
                    Kelola Users
                </x-super-admin.sidebar-item>
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col">
            {{-- Header / Topbar --}}
            <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold">@yield('title')</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">Halo, {{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:underline">Logout</button>
                    </form>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Script global --}}
    <script src="//unpkg.com/alpinejs" defer></script>
    {{-- ✅ ini fix: harus sama dengan @push('scripts') --}}
    @stack('scripts')
</body>

</html>
