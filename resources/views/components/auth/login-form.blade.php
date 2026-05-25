@props([
    'roleName' => 'Customer',
    'color' => 'green',
    'loginFrom' => 'customer',
])

@php
    $isCustomer = strtolower($roleName) === 'customer' || strtolower($loginFrom) === 'customer';
    $roleSlug = strtolower($roleName);
@endphp

{{-- LOGIN CARD --}}
<div
    class="relative w-full max-w-md mx-auto transform transition-all duration-500 ease-out scale-95 opacity-0 animate-slide-down">
    <div class="relative bg-white/95 shadow-2xl rounded-2xl p-8 ring-1 ring-black/5 backdrop-blur">

        {{-- Gradient Background --}}
        <div
            class="absolute inset-0 bg-gradient-to-tr from-{{ $color }}-100 to-transparent opacity-20 rounded-2xl">
        </div>

        {{-- Logo --}}
        <div class="flex justify-center mb-6 relative z-10">
            <img src="{{ asset('images/favicon.png') }}" alt="Logo Harkat Rental"
                class="w-20 h-20 rounded-full shadow-lg border border-gray-200 bg-white p-2">
        </div>

        {{-- Title --}}
        <h2 class="text-2xl font-bold text-center text-{{ $color }}-600 mb-6 relative z-10">
            {{ $roleName }} Login
        </h2>

        {{-- FORM LOGIN --}}
        <form method="POST" action="{{ route('login') }}" class="relative z-10 space-y-4">
            @csrf
            <input type="hidden" name="role" value="{{ $roleSlug }}">

            {{-- EMAIL --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">📧</span>
                    <input id="email" type="email" name="email" required autocomplete="username"
                        value="{{ old('email') }}"
                        class="pl-10 pr-3 py-2 w-full rounded-lg border border-gray-300 focus:border-{{ $color }}-500 focus:ring focus:ring-{{ $color }}-200 transition bg-white/90">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            {{-- PASSWORD --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">🔑</span>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="pl-10 pr-10 py-2 w-full rounded-lg border border-gray-300 focus:border-{{ $color }}-500 focus:ring focus:ring-{{ $color }}-200 transition bg-white/90">

                    {{-- Toggle Eye --}}
                    <button type="button" onclick="togglePassword()"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-{{ $color }}-600 focus:outline-none hover:scale-110 transition-transform duration-200 ease-out animate-pulse-on-hover">
                        {{-- Eye Open --}}
                        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{-- Eye Closed --}}
                        <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" class="w-5 h-5 hidden">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.269-2.943-9.543-7a9.973 9.973 0 012.24-3.685m3.317-2.547A9.956 9.956 0 0112 5c4.478 0 8.269 2.943 9.543 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            {{-- LUPA PASSWORD --}}
            <div class="flex justify-end">
                <a href="{{ route('password.request') }}"
                    class="text-sm text-gray-500 hover:text-{{ $color }}-600 transition">
                    Lupa password?
                </a>
            </div>

            {{-- GOOGLE LOGIN (only customer) --}}
            @if ($isCustomer)
                <a href="{{ route('google.login') }}"
                    class="flex items-center justify-center gap-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg transition transform hover:scale-105 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5">
                        <path fill="#4285F4"
                            d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.4 30.2 0 24 0 14.6 0 6.6 5.4 2.5 13.3l7.9 6.1C12 13.1 17.5 9.5 24 9.5z" />
                        <path fill="#34A853"
                            d="M46.1 24.5c0-1.5-.1-2.6-.4-3.7H24v7.3h12.7c-.5 2.9-2 5.3-4.3 7l6.7 5.2c3.9-3.6 6.1-8.9 6.1-15.8z" />
                        <path fill="#FBBC05"
                            d="M10.4 28.5c-.5-1.5-.8-3.1-.8-4.5s.3-3.1.8-4.5l-7.9-6.1C1 16.6 0 20.2 0 24s1 7.4 2.5 10.6l7.9-6.1z" />
                        <path fill="#EA4335"
                            d="M24 48c6.5 0 12-2.1 16-5.8l-6.7-5.2c-2 1.3-4.6 2.1-9.3 2.1-6.5 0-12-4.2-14-10.1l-7.9 6.1C6.6 42.6 14.6 48 24 48z" />
                    </svg>
                    <span>Login via Google</span>
                </a>
            @endif

            {{-- SUBMIT --}}
            <button type="submit"
                class="w-full bg-{{ $color }}-600 text-white py-2 px-4 rounded-lg hover:bg-{{ $color }}-700 transition transform hover:scale-105 shadow-md">
                Login
            </button>
        </form>
    </div>
</div>

{{-- SCRIPTS --}}
<script>
    function togglePassword() {
        const input = document.getElementById("password");
        const eyeOpen = document.getElementById("eye-open");
        const eyeClosed = document.getElementById("eye-closed");
        if (input.type === "password") {
            input.type = "text";
            eyeOpen.classList.add("hidden");
            eyeClosed.classList.remove("hidden");
        } else {
            input.type = "password";
            eyeOpen.classList.remove("hidden");
            eyeClosed.classList.add("hidden");
        }
    }
</script>

{{-- STYLES --}}
<style>
    .animate-pulse-on-hover:hover svg {
        animation: pulse .6s ease-in-out
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1)
        }

        50% {
            transform: scale(1.2)
        }
    }

    @keyframes slide-down {
        0% {
            transform: translateY(-50px);
            opacity: 0
        }

        100% {
            transform: translateY(0);
            opacity: 1
        }
    }

    .animate-slide-down {
        animation: slide-down .8s ease-out forwards
    }
</style>
