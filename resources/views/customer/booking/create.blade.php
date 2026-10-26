@extends('layouts.landing')

@section('title', 'Booking Mobil | Harkat Rent Car')

@section('pagehead')
    <x-page-head title="Booking mobil"
        subtitle="Pilih tanggal dan mobil, biayanya langsung terhitung. DP 50% dibayar setelah booking."
        :back="['Semua armada', route('mobil.index')]" />
@endsection

@section('content')
    <form action="{{ route('booking.store') }}" method="POST" id="bookingForm"
        data-check-url="{{ route('booking.checkMobils') }}"
        class="relative z-10 -mt-16 grid gap-6 lg:grid-cols-12 lg:items-start">
        @csrf

        <div class="space-y-6 lg:col-span-8">
            @if ($errors->any())
                <div class="flex gap-3 rounded-3xl bg-red-50 p-5 text-sm text-red-700 ring-1 ring-red-200">
                    <x-line-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Mobil & tanggal --}}
            <section class="bezel">
                <div class="bezel-core p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight text-forest">Mobil yang disewa</h2>
                            <p class="mt-1 text-sm text-ink/55">Boleh lebih dari satu mobil, masing-masing dengan tanggalnya sendiri.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="prev-mobil" aria-label="Mobil sebelumnya"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-forest/15 text-forest transition-colors duration-500 ease-spring hover:bg-forest hover:text-white disabled:pointer-events-none disabled:opacity-30">
                                <x-line-icon name="arrow-left" class="h-4 w-4" />
                            </button>
                            <span id="slideLabel" class="min-w-[4.5rem] text-center text-sm font-semibold text-forest">Mobil 1/1</span>
                            <button type="button" id="next-mobil" aria-label="Mobil berikutnya"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-forest/15 text-forest transition-colors duration-500 ease-spring hover:bg-forest hover:text-white disabled:pointer-events-none disabled:opacity-30">
                                <x-line-icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="-mx-1 mt-6 overflow-hidden px-1 py-1">
                        <div id="mobils-wrapper" class="flex transition-transform duration-700 ease-spring"></div>
                    </div>

                    <div id="indicator" class="mt-5 flex items-center justify-center gap-2"></div>

                    <div class="mt-6">
                        <button type="button" id="add-mobil" class="btn btn-outline">
                            Tambah mobil <span class="btn-icon text-base leading-none">+</span>
                        </button>
                    </div>
                </div>
            </section>

            {{-- Data penyewa --}}
            <section class="bezel">
                <div class="bezel-core p-6 sm:p-8">
                    <h2 class="text-lg font-bold tracking-tight text-forest">Data penyewa</h2>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-ink/80">Atas nama</label>
                            <input type="text" value="{{ $user->name }}" readonly class="field bg-paper text-ink/70">
                        </div>

                        <div>
                            <label for="asalKota" class="mb-1.5 block text-sm font-semibold text-ink/80">Asal kota</label>
                            <select name="asal_kota" id="asalKota" class="field" required>
                                <option value="">Pilih asal kota</option>
                                <option value="1" {{ $asal_kota_booking == 1 ? 'selected' : '' }}>Warga Yogyakarta</option>
                                <option value="2" {{ $asal_kota_booking == 2 ? 'selected' : '' }}>Dari luar Yogyakarta</option>
                            </select>
                        </div>

                        <div id="namaKotaWrapper" class="{{ $asal_kota_booking == 2 ? '' : 'hidden' }}">
                            <label for="namaKota" class="mb-1.5 block text-sm font-semibold text-ink/80">Nama kota</label>
                            <input type="text" id="namaKota" name="nama_kota" value="{{ $nama_kota_booking }}"
                                placeholder="Contoh: Padang" class="field">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="jaminan" class="mb-1.5 block text-sm font-semibold text-ink/80">Jaminan</label>
                            <select name="jaminan" id="jaminan" class="field" required>
                                <option value="">Pilih jaminan</option>
                                <option value="1" {{ $jaminan_default == 1 ? 'selected' : '' }}>KTP atau paspor</option>
                                <option value="2" {{ $jaminan_default == 2 ? 'selected' : '' }}>KTP dan motor (untuk warga Yogyakarta)</option>
                            </select>
                            <p class="mt-2 text-sm text-ink/50">Jaminan dibawa saat pengambilan mobil dan dikembalikan setelah mobil kembali.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Ringkasan biaya --}}
        <aside class="lg:sticky lg:top-28 lg:col-span-4">
            <div class="bezel-dark">
                <div class="bezel-core p-7">
                    <h2 class="text-lg font-bold tracking-tight text-white">Ringkasan biaya</h2>

                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-white/55">Jumlah mobil</dt>
                            <dd id="sumCount" class="font-semibold text-white">1</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-white/55">Total sewa</dt>
                            <dd id="sumTotal" class="text-2xl font-extrabold tracking-tight text-white">Rp 0</dd>
                        </div>
                        <div class="border-t border-white/10 pt-4">
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-white/55">DP 50% (setelah booking)</dt>
                                <dd id="sumDp" class="text-lg font-bold text-leaf">Rp 0</dd>
                            </div>
                            <div class="mt-3 flex items-baseline justify-between gap-3">
                                <dt class="text-white/55">Sisa saat sewa</dt>
                                <dd id="sumSisa" class="font-semibold text-white/90">Rp 0</dd>
                            </div>
                        </div>
                    </dl>

                    <input type="hidden" name="total_harga" id="totalHarga">

                    <button type="submit" id="submitBooking" class="btn btn-primary mt-8 w-full justify-between">
                        <span>Booking sekarang</span>
                        <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                    </button>

                    <p class="mt-5 text-xs leading-relaxed text-white/45">
                        Bayar DP sebelum jatuh tempo agar booking tidak dibatalkan otomatis. Tarif dibulatkan per 24 jam.
                    </p>
                </div>
            </div>
        </aside>
    </form>

    {{-- Templat satu mobil (disalin oleh booking.js) --}}
    <div id="mobil-template" class="hidden">
        <div class="mobil-item w-full shrink-0 px-1">
            <div class="rounded-3xl border border-line bg-paper/60 p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-bold text-forest">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-mint">
                            <x-line-icon name="car" class="h-4 w-4" />
                        </span>
                        Mobil <span class="mobil-no">1</span>
                    </span>
                    <button type="button"
                        class="remove-mobil text-sm font-semibold text-red-600 underline-offset-4 transition-colors duration-500 ease-spring hover:text-red-700 hover:underline">
                        Hapus
                    </button>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-ink/80">Mulai sewa</label>
                        <input type="datetime-local" name="mobils[0][tanggal_sewa]" class="field tanggal-sewa" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-ink/80">Kembali</label>
                        <input type="datetime-local" name="mobils[0][tanggal_kembali]" class="field tanggal-kembali" required>
                    </div>
                </div>
                <p class="mt-2 text-xs text-ink/50">Mulai sewa minimal 3 jam dari sekarang.</p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" class="check-mobils btn btn-outline !py-2 !pr-2 text-xs">
                        Cek mobil tersedia
                        <span class="btn-icon !h-6 !w-6"><x-line-icon name="search" class="h-3.5 w-3.5" /></span>
                    </button>
                    <span class="cek-hasil text-sm text-ink/55"></span>
                </div>

                <div class="mt-5">
                    <label class="mb-1.5 block text-sm font-semibold text-ink/80">Pilih mobil</label>
                    <select name="mobils[0][mobil_id]" class="field mobil-select" required>
                        <option value="">Pilih mobil</option>
                        @foreach ($mobils as $mobil)
                            <option value="{{ $mobil->id }}" data-harga="{{ $mobil->harga_sewa }}"
                                data-harga-allin="{{ $mobil->harga_all_in ?? $mobil->harga_sewa }}">
                                {{ $mobil->masterMobil->nama }} &middot; {{ $mobil->merk }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mobil-info mt-2 text-sm text-ink/55"></p>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl bg-white px-4 py-3 ring-1 ring-forest/10">
                    <div>
                        <p class="text-sm font-semibold text-ink/80">Pakai supir</p>
                        <p class="text-xs text-ink/50">Tarif memakai harga dengan supir.</p>
                    </div>
                    <input type="hidden" class="hidden-pakai-supir" name="mobils[0][pakai_supir]" value="0">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="pakai-supir-checkbox peer sr-only">
                        <span
                            class="h-7 w-12 rounded-full bg-line transition-colors duration-500 ease-spring peer-checked:bg-leaf peer-focus-visible:ring-4 peer-focus-visible:ring-leaf/25 after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-500 after:ease-spring peer-checked:after:translate-x-5"></span>
                    </label>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-mint/70 px-4 py-3">
                        <label class="text-xs font-medium text-forest/70">Lama sewa (hari)</label>
                        <input type="number" readonly tabindex="-1"
                            class="lama-sewa block w-full border-0 bg-transparent p-0 text-lg font-bold text-forest focus:ring-0">
                    </div>
                    <div class="rounded-2xl bg-mint/70 px-4 py-3">
                        <label class="text-xs font-medium text-forest/70">Total harga mobil (Rp)</label>
                        <input type="text" readonly tabindex="-1"
                            class="total-harga-mobil block w-full border-0 bg-transparent p-0 text-lg font-bold text-forest focus:ring-0">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
