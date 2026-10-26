@props([
    'roleName' => 'Customer',
    'color' => 'green',
    'loginFrom' => 'customer',
])

@php
    $isCustomer = strtolower($roleName) === 'customer' || strtolower($loginFrom) === 'customer';
    $roleSlug = strtolower($roleName);

    // Kelas ditulis utuh (bukan "text-{$color}-600") agar Tailwind ikut membuatnya.
    $c = [
        'green' => ['title' => 'text-forest', 'btn' => 'bg-leaf hover:bg-leaf-dark', 'focus' => 'focus:border-leaf focus:ring-leaf/20', 'link' => 'hover:text-leaf'],
        'red' => ['title' => 'text-red-700', 'btn' => 'bg-red-600 hover:bg-red-700', 'focus' => 'focus:border-red-500 focus:ring-red-500/20', 'link' => 'hover:text-red-600'],
        'blue' => ['title' => 'text-blue-700', 'btn' => 'bg-blue-600 hover:bg-blue-700', 'focus' => 'focus:border-blue-500 focus:ring-blue-500/20', 'link' => 'hover:text-blue-600'],
    ][$color] ?? null;
    $c ??= [
        'title' => 'text-forest', 'btn' => 'bg-leaf hover:bg-leaf-dark', 'focus' => 'focus:border-leaf focus:ring-leaf/20', 'link' => 'hover:text-leaf',
    ];
    $input = 'w-full rounded-xl border border-line bg-white py-3 pl-11 text-sm text-ink transition focus:outline-none focus:ring-4 ' . $c['focus'];
@endphp

<div class="rounded-[2rem] bg-white/60 p-1.5 ring-1 ring-black/5">
    <div class="rounded-[calc(2rem-0.375rem)] bg-white p-8 shadow-[inset_0_1px_1px_rgba(255,255,255,0.9)] sm:p-9">

        <div class="flex items-center gap-3">
            <img src="{{ asset('images/favicon.png') }}" alt="Harkat Rent Car"
                class="h-12 w-12 rounded-full border border-line bg-white p-1.5">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight {{ $c['title'] }}">Masuk sebagai {{ $roleName }}</h2>
                <p class="text-sm text-ink/55">Harkat Rent Car</p>
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="role" value="{{ $roleSlug }}">

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-ink/80">Email</label>
                <div class="relative">
                    <x-line-icon name="mail" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink/35" />
                    <input id="email" type="email" name="email" required autocomplete="username"
                        value="{{ old('email') }}" placeholder="nama@email.com" class="{{ $input }} pr-4">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="block text-sm font-semibold text-ink/80">Kata sandi</label>
                    <a href="{{ route('password.request') }}"
                        class="text-sm text-ink/55 transition-colors duration-500 ease-spring {{ $c['link'] }}">Lupa kata sandi?</a>
                </div>
                <div class="relative">
                    <x-line-icon name="key" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink/35" />
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="{{ $input }} pr-12">
                    <button type="button" onclick="togglePassword()" aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-ink/45 transition-colors duration-500 ease-spring hover:bg-mint hover:text-forest">
                        <x-line-icon id="eye-open" name="eye" class="h-5 w-5" />
                        <x-line-icon id="eye-closed" name="eye-off" class="hidden h-5 w-5" />
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <button type="submit"
                class="group flex w-full items-center justify-center rounded-full py-3.5 text-sm font-semibold text-white transition-all duration-500 ease-spring active:scale-[0.98] {{ $c['btn'] }}">
                Masuk
            </button>

            @if ($isCustomer)
                <div class="flex items-center gap-3 text-xs text-ink/40">
                    <span class="h-px flex-1 bg-line"></span>atau<span class="h-px flex-1 bg-line"></span>
                </div>

                <a href="{{ route('google.login') }}"
                    class="flex items-center justify-center gap-3 rounded-full border border-line bg-white py-3 text-sm font-semibold text-ink/80 transition-colors duration-500 ease-spring hover:bg-paper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-5 w-5">
                        <path fill="#4285F4"
                            d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.4 30.2 0 24 0 14.6 0 6.6 5.4 2.5 13.3l7.9 6.1C12 13.1 17.5 9.5 24 9.5z" />
                        <path fill="#34A853"
                            d="M46.1 24.5c0-1.5-.1-2.6-.4-3.7H24v7.3h12.7c-.5 2.9-2 5.3-4.3 7l6.7 5.2c3.9-3.6 6.1-8.9 6.1-15.8z" />
                        <path fill="#FBBC05"
                            d="M10.4 28.5c-.5-1.5-.8-3.1-.8-4.5s.3-3.1.8-4.5l-7.9-6.1C1 16.6 0 20.2 0 24s1 7.4 2.5 10.6l7.9-6.1z" />
                        <path fill="#EA4335"
                            d="M24 48c6.5 0 12-2.1 16-5.8l-6.7-5.2c-2 1.3-4.6 2.1-9.3 2.1-6.5 0-12-4.2-14-10.1l-7.9 6.1C6.6 42.6 14.6 48 24 48z" />
                    </svg>
                    Masuk dengan Google
                </a>

                <p class="text-center text-sm text-ink/55">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-semibold text-forest underline-offset-4 hover:underline">Daftar</a>
                </p>
            @endif
        </form>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById("password");
        const eyeOpen = document.getElementById("eye-open");
        const eyeClosed = document.getElementById("eye-closed");
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        eyeOpen.classList.toggle("hidden", show);
        eyeClosed.classList.toggle("hidden", !show);
    }
</script>
