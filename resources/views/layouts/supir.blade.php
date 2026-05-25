<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <title>Supir Panel - Rental Mobil Harkat Yogyakarta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="user-id" content="{{ auth()->user()->id }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 h-full">
    @if (session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <x-supir.sidebar />

        <!-- Content -->
        <div class="flex-1 flex flex-col">
            <x-supir.topbar />

            <!-- Main Section with Alpine State -->
            <main class="flex-1 p-4 md:ml-64">
                @yield('content')
                <div x-data="{ open: false, job: {} }" x-show="open" x-transition.opacity.scale.duration.300ms id="jobOfferModal"
                    class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50"
                    style="display: none;">
                    <div class="bg-white p-6 rounded-2xl shadow-2xl w-96">
                        <h2 class="text-xl font-bold mb-4">📢 Job Baru!</h2>
                        <p><b>ID:</b> <span x-text="job.id"></span></p>
                        <p><b>Mobil:</b> <span x-text="job.mobil"></span></p>
                        <p><b>Tanggal Sewa:</b> <span x-text="job.tanggal_sewa"></span></p>
                        <p><b>Tanggal Kembali:</b> <span x-text="job.tanggal_kembali"></span></p>

                        <div class="flex justify-end gap-2 mt-4">
                            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition"
                                @click="open = false">
                                Abaikan
                            </button>
                            <form method="POST" action={{ route('supir.acceptJob') }}>
                                @csrf
                                <input type="hidden" name="job_offer_id" x-model="job.id">
                                <button
                                    class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                                    Terima
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <x-supir.bottomnav />
    <!-- Pusher & Echo -->
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@^1.15/dist/echo.iife.js"></script>

    <script>
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ env('PUSHER_APP_KEY') }}',
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            forceTLS: true
        });

        const supirId = {{ Auth::user()->supir->id }};

        window.Echo.channel(`supir.${supirId}`)
            .listen('JobAssigned', (data) => {
                console.log("Job Offer diterima:", data);

                // Inject ke Alpine component
                let modal = document.getElementById('jobOfferModal');
                let scope = Alpine.$data(modal);

                scope.job = data; // masukin data job
                scope.open = true; // buka modal
            });
    </script>


</body>

</html>
