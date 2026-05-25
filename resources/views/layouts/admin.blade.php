<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    <title>Admin Panel - Rental Mobil Harkat Yogyakarta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 h-full" x-data="{ sidebarOpen: true }"
    :class="{ 'overflow-hidden': sidebarOpen && window.innerWidth < 768 }">

    <div class="flex h-screen">

        <x-admin.sidebar />

        <!-- Overlay (mobile) -->
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden"
            @click="sidebarOpen = false"></div>

        <div class="flex-1 flex flex-col transition-all duration-300" :class="sidebarOpen ? 'md:ml-64' : 'md:ml-16'">
            <x-admin.topbar />

            <main class="flex-1 p-6 overflow-y-auto transition-all duration-300">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- lucide icons --}}
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
    <script>
        lucide.replace()
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>

</html>
