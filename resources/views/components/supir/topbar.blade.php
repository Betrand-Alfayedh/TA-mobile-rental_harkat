<header class="bg-white shadow px-6 py-4 flex justify-between items-center md:ml-64">
    <h1 class="text-lg font-bold text-blue-900">
        Halo, {{ auth()->user()->name }} 👋
    </h1>

    <div class="flex items-center gap-3">
        <span class="px-2 py-1 text-xs font-medium rounded {{ auth()->user()->supir->status_badge_class }}">
            {{ auth()->user()->supir->status_text }}
        </span>
        <form action="{{ route('supir.updateStatus') }}" method="POST" onsubmit="setTimeout(() => this.submit(), 200)">
            @csrf
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" name="status" class="sr-only peer"
                    {{ auth()->user()->supir->status ? 'checked' : '' }} onchange="this.form.requestSubmit()">

                <!-- Background Switch -->
                <div
                    class="w-11 h-6 bg-gray-200 rounded-full relative 
                    transition-colors peer-checked:bg-green-500">
                    <!-- Bulatan -->
                    <div
                        class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full 
                        transition-transform peer-checked:translate-x-5">
                    </div>
                </div>
            </label>
        </form>
    </div>
</header>
