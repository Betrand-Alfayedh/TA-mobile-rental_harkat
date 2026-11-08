@extends('layouts.admin')

@section('content')
    @php $inp = 'mt-1 block w-full px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400'; @endphp

    <div class="mx-auto max-w-6xl">
        <a href="{{ route('admin.booking.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Kembali ke Pesanan Sewa</a>
        <h2 class="mb-1 mt-2 text-2xl">Buat Pesanan Sewa</h2>
        <p class="mb-6 text-sm text-gray-500">Untuk pelanggan yang memesan lewat telepon atau datang langsung.</p>

        <form action="{{ route('admin.booking.store') }}" method="POST" id="bookingForm"
            data-check-url="{{ route('admin.booking.cekMobil') }}" class="grid gap-6 lg:grid-cols-3 lg:items-start">
            @csrf

            <div class="space-y-6 lg:col-span-2">
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
                        <div class="mb-1 font-semibold">Pesanan belum bisa disimpan:</div>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Data pelanggan --}}
                <section class="rounded-2xl border border-hk-line bg-white p-6 ">
                    <h3 class="text-base font-bold text-gray-900">Data pelanggan</h3>
                    <p class="mb-5 text-sm text-gray-500">Pilih pelanggan, asal kota, dan jaminan yang dibawa.</p>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="pelanggan_id" class="text-sm font-medium text-gray-700">Pelanggan</label>
                            <select name="pelanggan_id" id="pelanggan_id" class="{{ $inp }}" required>
                                <option value="">Pilih pelanggan</option>
                                @foreach ($pelanggans as $p)
                                    <option value="{{ $p->id }}" @selected(old('pelanggan_id') == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="asalKota" class="text-sm font-medium text-gray-700">Asal kota</label>
                            <select name="asal_kota" id="asalKota" class="{{ $inp }}" required>
                                <option value="">Pilih asal kota</option>
                                <option value="1" @selected(old('asal_kota') == 1)>Warga Yogyakarta</option>
                                <option value="2" @selected(old('asal_kota') == 2)>Dari luar Yogyakarta</option>
                            </select>
                        </div>

                        <div id="namaKotaWrapper" class="{{ old('asal_kota') == 2 ? '' : 'hidden' }}">
                            <label for="namaKota" class="text-sm font-medium text-gray-700">Nama kota</label>
                            <input type="text" name="nama_kota" id="namaKota" class="{{ $inp }}"
                                placeholder="Contoh: Padang" value="{{ old('nama_kota') }}">
                        </div>

                        <div class="md:col-span-2">
                            <label for="jaminan" class="text-sm font-medium text-gray-700">Jaminan</label>
                            <select name="jaminan" id="jaminan" class="{{ $inp }}" required>
                                <option value="">Pilih jaminan</option>
                                <option value="1" @selected(old('jaminan') == 1)>KTP atau paspor</option>
                                <option value="2" @selected(old('jaminan') == 2)>KTP dan motor (untuk warga Yogyakarta)</option>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Mobil yang disewa --}}
                <section class="rounded-2xl border border-hk-line bg-white p-6 ">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Mobil yang disewa</h3>
                            <p class="text-sm text-gray-500">Boleh lebih dari satu mobil, masing-masing dengan tanggalnya sendiri.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="prev-mobil" aria-label="Mobil sebelumnya"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-hk-line text-gray-600 hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-30">&larr;</button>
                            <span id="slideLabel" class="min-w-[4.5rem] text-center text-sm font-semibold text-gray-700">Mobil 1/1</span>
                            <button type="button" id="next-mobil" aria-label="Mobil berikutnya"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-hk-line text-gray-600 hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-30">&rarr;</button>
                        </div>
                    </div>

                    <div class="-mx-1 mt-5 overflow-hidden px-1 py-1">
                        <div id="mobils-wrapper" class="flex transition-transform duration-500"></div>
                    </div>

                    <div id="indicator" class="mt-4 flex items-center justify-center gap-2"></div>

                    <button type="button" id="add-mobil"
                        class="mt-4 rounded-xl border border-hk-line bg-white px-4 py-2 text-sm font-semibold text-hk-ink hover:bg-hk-panel">
                        + Tambah mobil
                    </button>
                </section>
            </div>

            {{-- Ringkasan biaya --}}
            <aside class="lg:sticky lg:top-24">
                <div class="rounded-2xl bg-hk-ink p-6 text-white ">
                    <h3 class="text-base font-bold">Ringkasan biaya</h3>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-white/60">Jumlah mobil</dt>
                            <dd id="sumCount" class="font-semibold">1</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-white/60">Total sewa</dt>
                            <dd id="sumTotal" class="text-xl font-extrabold">Rp 0</dd>
                        </div>
                        <div class="space-y-3 border-t border-white/10 pt-3">
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-white/60">DP 50%</dt>
                                <dd id="sumDp" class="font-bold text-emerald-300">Rp 0</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-white/60">Sisa saat sewa</dt>
                                <dd id="sumSisa" class="font-semibold">Rp 0</dd>
                            </div>
                        </div>
                    </dl>

                    <input type="hidden" name="total_harga" id="totalHarga">

                    <button type="submit" id="submitBooking"
                        class="mt-6 w-full rounded-xl bg-white px-5 py-3 text-sm font-semibold text-hk-ink hover:bg-hk-panel disabled:opacity-60">
                        <span>Simpan pesanan</span>
                    </button>
                    <p class="mt-4 text-xs leading-relaxed text-white/50">
                        Pesanan dibuat dengan status menunggu DP. Tarif dibulatkan per 24 jam.
                    </p>
                </div>
            </aside>
        </form>

        {{-- Templat satu mobil (disalin oleh booking.js) --}}
        <div id="mobil-template" class="hidden">
            <div class="mobil-item w-full shrink-0 px-1">
                <div class="rounded-xl border border-hk-line bg-hk-panel p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900">Mobil <span class="mobil-no">1</span></span>
                        <button type="button" class="remove-mobil text-sm font-semibold text-red-600 hover:underline">Hapus</button>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Mulai sewa</label>
                            <input type="datetime-local" name="mobils[0][tanggal_sewa]"
                                class="{{ $inp }} tanggal-sewa" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Kembali</label>
                            <input type="datetime-local" name="mobils[0][tanggal_kembali]"
                                class="{{ $inp }} tanggal-kembali" required>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Mulai sewa minimal 3 jam dari sekarang.</p>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button"
                            class="check-mobils rounded-lg border border-hk-line bg-white px-3 py-1.5 text-xs font-semibold text-hk-ink hover:bg-hk-panel">
                            Cek mobil tersedia
                        </button>
                        <span class="cek-hasil text-sm text-gray-500"></span>
                    </div>

                    <div class="mt-4">
                        <label class="text-sm font-medium text-gray-700">Pilih mobil</label>
                        <select name="mobils[0][mobil_id]" class="{{ $inp }} mobil-select" required>
                            <option value="">Pilih mobil</option>
                            @foreach ($mobils as $mobil)
                                <option value="{{ $mobil->id }}" data-harga="{{ $mobil->harga_sewa }}"
                                    data-harga-allin="{{ $mobil->harga_all_in ?? $mobil->harga_sewa }}">
                                    {{ $mobil->masterMobil->nama }} &middot; {{ $mobil->merk }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mobil-info mt-2 text-sm text-gray-500"></p>
                    </div>

                    <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl bg-white px-4 py-3 ring-1 ring-hk-line">
                        <input type="hidden" class="hidden-pakai-supir" name="mobils[0][pakai_supir]" value="0">
                        <input type="checkbox" class="pakai-supir-checkbox h-4 w-4 rounded border-gray-300 text-hk-ink focus:ring-hk-ink">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Pakai supir</span>
                            <span class="block text-xs text-gray-500">Tarif memakai harga dengan supir.</span>
                        </span>
                    </label>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-hk-line bg-white px-4 py-3">
                            <label class="text-xs font-medium text-gray-600">Lama sewa (hari)</label>
                            <input type="number" readonly tabindex="-1"
                                style="border:0!important;background:transparent!important;padding:0!important;box-shadow:none!important" class="lama-sewa block w-full text-lg font-bold text-gray-900">
                        </div>
                        <div class="rounded-xl border border-hk-line bg-white px-4 py-3">
                            <label class="text-xs font-medium text-gray-600">Total harga mobil (Rp)</label>
                            <input type="text" readonly tabindex="-1"
                                style="border:0!important;background:transparent!important;padding:0!important;box-shadow:none!important" class="total-harga-mobil block w-full text-lg font-bold text-gray-900">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
