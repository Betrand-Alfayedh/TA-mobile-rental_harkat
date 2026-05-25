<nav x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)" x-show="show"
    x-transition:enter="transform transition duration-500 ease-out" x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-md flex justify-around py-2 md:hidden">
    <a href="{{ route('supir.dashboard') }}"
        class="flex flex-col items-center {{ request()->routeIs('driver.dashboard') ? 'text-green-600' : 'text-gray-600 hover:text-green-600' }}">
        <i data-lucide="home" class="w-6 h-6"></i>
        <span class="text-xs">Home</span>
    </a>

    <a href=""
        class="flex flex-col items-center {{ request()->routeIs('driver.jobs') ? 'text-green-600' : 'text-gray-600 hover:text-green-600' }}">
        <i data-lucide="briefcase" class="w-6 h-6"></i>
        <span class="text-xs">Jobs</span>
    </a>

    <a href=""
        class="flex flex-col items-center {{ request()->routeIs('driver.status') ? 'text-green-600' : 'text-gray-600 hover:text-green-600' }}">
        <i data-lucide="user-check" class="w-6 h-6"></i>
        <span class="text-xs">Status</span>
    </a>

    <a href=""
        class="flex flex-col items-center {{ request()->routeIs('driver.profile') ? 'text-green-600' : 'text-gray-600 hover:text-green-600' }}">
        <i data-lucide="user" class="w-6 h-6"></i>
        <span class="text-xs">Profile</span>
    </a>
</nav>
