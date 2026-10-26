<header class="bg-white shadow px-6 py-4 flex justify-between items-center md:ml-64">
    <h1 class="text-lg font-bold text-blue-900">
        Halo, {{ auth()->user()->name }}
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

                <!-- Saklar: bulatan digambar dengan ::after supaya ikut bergeser saat dicentang -->
                <div
                    class="relative h-6 w-11 rounded-full bg-gray-200 transition-colors peer-checked:bg-green-500 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform after:content-[''] peer-checked:after:translate-x-5">
                </div>
            </label>
        </form>
    </div>
</header>
