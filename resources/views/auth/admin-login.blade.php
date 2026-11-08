<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <title>Masuk Panel Admin - Rental Mobil Harkat Yogyakarta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"Geist Mono"', 'ui-monospace', 'monospace']
                    },
                    colors: {
                        hk: {
                            ink: '#17171A',
                            mute: '#86847D',
                            panel: '#F3F2EE',
                            deep: '#EBE9E3',
                            line: '#E2DFD8'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: Geist, ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        :focus-visible {
            outline: 2px solid #17171A;
            outline-offset: 2px;
        }
    </style>
</head>

<body class="flex min-h-full items-center justify-center bg-hk-deep px-4 py-10 text-hk-ink">
    <main class="w-full max-w-[26rem]">
        <div class="rounded-[22px] border border-hk-line bg-hk-panel p-1.5">
            <div class="flex items-center justify-between px-3 pb-2.5 pt-2.5">
                <span class="font-mono text-[11px] font-medium uppercase tracking-[.08em] text-hk-mute">Panel admin</span>
                <span class="font-mono text-[11px] text-hk-mute">Harkat Rent Car</span>
            </div>

            <div class="rounded-2xl border border-hk-line bg-white p-7 sm:p-8">
                <img src="{{ asset('images/favicon.png') }}" alt=""
                    class="h-11 w-11 rounded-xl border border-hk-line bg-white p-1.5">

                <h1 class="mt-5 text-2xl font-semibold tracking-tight">Masuk ke panel admin</h1>
                <p class="mt-1.5 text-sm text-hk-mute">Kelola armada, pesanan, dan pembayaran Rental Mobil Harkat Yogyakarta.</p>

                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-hk-line bg-hk-panel px-4 py-3 text-sm">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                    @csrf
                    <input type="hidden" name="role" value="admin">

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="nama@email.com"
                            class="block w-full rounded-xl border border-hk-line bg-white px-3.5 py-2.5 text-sm placeholder:text-hk-mute/70 focus:border-hk-ink focus:outline-none focus:ring-4 focus:ring-hk-ink/10">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium">Kata sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-hk-mute hover:text-hk-ink">Lupa kata sandi?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                class="block w-full rounded-xl border border-hk-line bg-white py-2.5 pl-3.5 pr-20 text-sm focus:border-hk-ink focus:outline-none focus:ring-4 focus:ring-hk-ink/10">
                            <button type="button" id="toggle-pw"
                                class="absolute inset-y-0 right-1.5 my-1.5 rounded-lg px-3 text-xs font-medium text-hk-mute hover:bg-hk-panel hover:text-hk-ink">Tampilkan</button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full rounded-xl bg-hk-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-black active:scale-[.99]">
                        Masuk
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-5 text-center text-xs text-hk-mute">Halaman ini khusus admin Harkat Rent Car.</p>
    </main>

    <script>
        const pw = document.getElementById('password');
        document.getElementById('toggle-pw').addEventListener('click', function() {
            const tampil = pw.type === 'password';
            pw.type = tampil ? 'text' : 'password';
            this.textContent = tampil ? 'Sembunyikan' : 'Tampilkan';
        });
    </script>
</body>

</html>
