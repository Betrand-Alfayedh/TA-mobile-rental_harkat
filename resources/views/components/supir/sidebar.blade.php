<aside class="hidden md:flex md:flex-col w-64 bg-blue-900 text-white p-6 fixed h-screen">

    <h2 class="text-xl font-bold flex items-center gap-2 mb-6">
        🚗 Supir Panel
    </h2>

    <nav class="flex flex-col space-y-3">
        <a href="{{ route('supir.dashboard') }}" class="hover:bg-blue-700 px-3 py-2 rounded-md">🏠 Dashboard</a>
        <a href="" class="hover:bg-blue-700 px-3 py-2 rounded-md">📋 Job Aktif</a>
        <a href="" class="hover:bg-blue-700 px-3 py-2 rounded-md">📑 Riwayat</a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="mt-auto">
        @csrf
        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 py-2 rounded-md">
            Logout
        </button>
    </form>
</aside>
