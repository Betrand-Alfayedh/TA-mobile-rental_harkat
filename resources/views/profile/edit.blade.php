@extends('layouts.landing')

@section('content')
    <div class="max-w-3xl mx-auto px-4 pt-20">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">✏️ Edit Profil Kamu</h1>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                {{-- Nama --}}
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- No HP --}}
                <div>
                    <label class="block text-gray-600 font-medium mb-1">No HP</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('no_hp') border-red-500 @enderror">
                    @error('no_hp')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Asal Kota (AutoComplete) --}}
                <div class="relative">
                    <label class="block text-gray-600 font-medium mb-1">Asal Kota</label>
                    <input type="text" id="asal_kota_input" placeholder="Cari kota..."
                        value="{{ old('asal_kota', $user->asal_kota) }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('asal_kota') border-red-500 @enderror">
                    <div id="asal_kota_list"
                        class="absolute z-50 w-full mt-1 max-h-48 overflow-auto rounded-lg shadow-lg bg-white hidden border border-gray-200">
                    </div>
                    <input type="hidden" name="asal_kota" id="asal_kota_hidden"
                        value="{{ old('asal_kota', $user->asal_kota) }}">
                    @error('asal_kota')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat --}}
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Alamat</label>
                    <textarea name="alamat" rows="3"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('alamat') border-red-500 @enderror">{{ old('alamat', $user->alamat) }}</textarea>
                    @error('alamat')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                {{-- Ganti Password --}}
                <div class="pt-4 border-t border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-700 mb-3">🔐 Ganti Password</h2>

                    <div class="space-y-4">
                        {{-- Password Baru --}}
                        <div>
                            <label class="block text-gray-600 font-medium mb-1">Password Baru</label>
                            <input type="password" name="password"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2 @error('password') border-red-500 @enderror"
                                placeholder="Masukkan password baru">
                            @error('password')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label class="block text-gray-600 font-medium mb-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500 px-4 py-2"
                                placeholder="Ulangi password baru">
                        </div>
                    </div>
                </div>


                {{-- Submit --}}
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-green-600 text-white px-6 py-2 rounded-xl hover:bg-green-700 transition shadow-md">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Autocomplete Script (sama kaya sebelumnya) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('asal_kota_input');
            const list = document.getElementById('asal_kota_list');
            const hidden = document.getElementById('asal_kota_hidden');
            let timeout = null;
            let currentIndex = -1;

            function renderList(data) {
                if (data.length === 0) {
                    list.innerHTML = '<p class="p-2 text-gray-500">Tidak ada hasil</p>';
                } else {
                    list.innerHTML = data.map(city => {
                        const fullName = `${city.name}, ${city.country}`;
                        return `
                <div class="p-2 cursor-pointer hover:bg-green-100" data-name="${fullName}">
                    <span class="font-medium">${city.name}</span>,
                    <span class="text-gray-500 text-sm">${city.country}</span>
                </div>
            `;
                    }).join('');
                }
                list.classList.remove('hidden');

                list.querySelectorAll('div[data-name]').forEach((item, index) => {
                    item.addEventListener('click', () => {
                        selectItem(item.dataset.name);
                    });
                    item.dataset.index = index;
                });
            }


            function selectItem(name) {
                input.value = name;
                hidden.value = name;
                list.classList.add('hidden');
                currentIndex = -1;
            }

            input.addEventListener('input', function() {
                clearTimeout(timeout);
                const query = input.value.trim();

                if (query.length === 0) {
                    list.classList.add('hidden');
                    list.innerHTML = '';
                    hidden.value = '';
                    return;
                }

                timeout = setTimeout(() => {
                    fetch(`/autocomplete-cities?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(renderList)
                        .catch(err => {
                            console.error('Error fetching cities:', err);
                            list.innerHTML =
                                '<p class="p-2 text-red-500">Gagal memuat data</p>';
                            list.classList.remove('hidden');
                        });
                }, 250);
            });

            input.addEventListener('keydown', function(e) {
                const items = list.querySelectorAll('div[data-name]');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentIndex = (currentIndex + 1) % items.length;
                    highlightItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentIndex = (currentIndex - 1 + items.length) % items.length;
                    highlightItem(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex >= 0 && currentIndex < items.length) {
                        selectItem(items[currentIndex].dataset.name);
                    }
                }
            });

            function highlightItem(items) {
                items.forEach(item => item.classList.remove('bg-green-200'));
                if (currentIndex >= 0 && currentIndex < items.length) {
                    items[currentIndex].classList.add('bg-green-200');
                    items[currentIndex].scrollIntoView({
                        block: "nearest"
                    });
                }
            }

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.add('hidden');
                }
            });
        });
    </script>
@endsection
