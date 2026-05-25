<div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg z-40 h-full transition-all duration-300 flex flex-col"
    :class="sidebarOpen ? 'w-64' : 'w-16 fixed inset-y-0 left-0'">

    <div class="h-16 flex items-center justify-center border-b">
        <span x-show="sidebarOpen" class="text-xl font-bold">Admin Panel</span>
        <button x-show="!sidebarOpen" @click="sidebarOpen = true"
            class="text-2xl text-gray-600 hover:text-black">☰</button>
    </div>

    <nav class="flex-1 px-2 py-4 space-y-2">
        @php
            $navItems = [
                [
                    'route' => 'admin.dashboard',
                    'icon' => 'layout-dashboard',
                    'label' => 'Dashboard',
                    'match' => 'admin',
                ],
                [
                    'route' => 'admin.tipe-mobil.index',
                    'icon' => 'car',
                    'label' => 'Manajemen Tipe mobil',
                    'match' => 'admin/tipe-mobil',
                ],
                [
                    'route' => 'admin.master-mobils.index',
                    'icon' => 'car',
                    'label' => 'Manajemen Master Mobil',
                    'match' => 'admin/master-mobils',
                ],
                [
                    'route' => 'admin.mobil.index',
                    'icon' => 'car',
                    'label' => 'Manajemen Mobil',
                    'match' => 'admin/mobil',
                ],
                [
                    'route' => 'admin.supir.index',
                    'icon' => 'shield-user',
                    'label' => 'Manajemen Supir',
                    'match' => 'admin/supir',
                ],
                [
                    'route' => 'admin.pelanggan.index',
                    'icon' => 'users-round',
                    'label' => 'Manajemen Pelanggan',
                    'match' => 'admin/pelanggan',
                ],
                [
                    'route' => 'admin.booking.index',
                    'icon' => 'arrow-left-right',
                    'label' => 'Manajemen Penyewaan Mobil',
                    'match' => 'admin/transaksi',
                ],
                [
                    'route' => 'admin.pembayaran.index',
                    'icon' => 'arrow-left-right',
                    'label' => 'Manajemen Pembayaran Mobil',
                    'match' => 'admin/pembayaran',
                ],
                [
                    'route' => 'admin.pengembalian.index',
                    'icon' => 'arrow-left-right',
                    'label' => 'Manajemen Pengembalian Mobil',
                    'match' => 'admin/pengembalian',
                ],
            ];

        @endphp

        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}"
                class="flex items-center space-x-3 hover:bg-gray-200 px-3 py-2 rounded transition group {{ request()->is($item['match']) ? 'bg-gray-200' : '' }}">
                <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                <span x-show="sidebarOpen" class="text-sm">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
