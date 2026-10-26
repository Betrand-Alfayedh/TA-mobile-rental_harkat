@extends('layouts.landing')

@section('title', 'Bayar DP | Harkat Rent Car')

@php
    $status = (int) $pembayaran->status_pembayaran;
    // Alasan bukti belum boleh diunggah (booking batal, lewat jatuh tempo, dst.); null bila boleh.
    $blokir = $status === 0 ? $pembayaran->alasanTidakBisaUpload() : null;
@endphp

@section('pagehead')
    <x-page-head :title="'Bayar DP booking #' . $booking->id"
        subtitle="Transfer atau scan QRIS, lalu unggah bukti pembayarannya. Admin akan memverifikasinya."
        :back="['Pesanan saya', route('booking.index')]" />
@endsection

@section('content')
    <div class="relative z-10 -mt-16 grid gap-6 lg:grid-cols-12 lg:items-start">
        <div class="space-y-6 lg:col-span-7">

            {{-- Jumlah yang harus dibayar --}}
            <section class="bezel-dark">
                <div class="bezel-core p-7 sm:p-8">
                    <p class="text-sm text-white/55">Jumlah DP yang harus dibayar</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">{{ $booking->uang_muka_rp }}</p>
                    <dl class="mt-7 flex flex-wrap gap-x-10 gap-y-4 text-sm">
                        <div>
                            <dt class="text-white/50">Total sewa</dt>
                            <dd class="mt-0.5 font-semibold text-white">{{ $booking->total_harga_rp }}</dd>
                        </div>
                        <div>
                            <dt class="text-white/50">Bayar sebelum</dt>
                            <dd class="mt-0.5 font-semibold text-white">{{ $pembayaran->jatuh_tempo?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Cara membayar --}}
            <section class="bezel">
                <div class="bezel-core p-6 sm:p-8">
                    <h2 class="text-lg font-bold tracking-tight text-forest">Cara membayar</h2>

                    <div class="mt-5 inline-flex rounded-full bg-paper p-1 ring-1 ring-forest/10" role="tablist">
                        <button type="button" role="tab" aria-selected="true" data-tab="transfer"
                            class="pay-tab rounded-full bg-forest px-5 py-2 text-sm font-semibold text-white transition-colors duration-500 ease-spring">
                            Transfer bank
                        </button>
                        <button type="button" role="tab" aria-selected="false" data-tab="qris"
                            class="pay-tab rounded-full px-5 py-2 text-sm font-semibold text-ink/65 transition-colors duration-500 ease-spring">
                            QRIS
                        </button>
                    </div>

                    <div id="panel-transfer" class="mt-6">
                        <div class="flex flex-wrap items-center justify-between gap-5 rounded-3xl bg-mint/60 p-5 ring-1 ring-forest/10">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('images/bank-mandiri.png') }}" alt="Bank Mandiri"
                                    class="h-10 w-auto rounded-lg bg-white p-1.5">
                                <div>
                                    <p class="text-sm font-semibold text-forest">Bank Mandiri</p>
                                    <p id="rek-number" class="font-mono text-xl font-bold tracking-wider text-ink">1370016710069</p>
                                    <p class="text-sm text-ink/55">a.n. HARKAT</p>
                                </div>
                            </div>
                            <button type="button" onclick="copyRek()" class="btn btn-primary !py-2.5">
                                Salin nomor <span class="btn-icon"><x-line-icon name="receipt" class="h-4 w-4" /></span>
                            </button>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-ink/55">
                            Transfer tepat sebesar jumlah DP di atas, lalu simpan bukti transfernya.
                        </p>
                    </div>

                    <div id="panel-qris" class="mt-6 hidden">
                        <div class="mx-auto max-w-xs overflow-hidden rounded-3xl bg-white p-3 ring-1 ring-forest/10">
                            <img src="{{ asset('images/qris.jpg') }}" alt="Kode QRIS Harkat Rent Car" loading="lazy" class="h-auto w-full rounded-2xl">
                        </div>
                        <p class="mt-4 text-center text-sm leading-relaxed text-ink/55">
                            Scan dengan aplikasi bank atau e-wallet, lalu simpan bukti pembayarannya.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        {{-- Unggah bukti --}}
        <aside class="lg:sticky lg:top-28 lg:col-span-5">
            <div class="bezel">
                <div class="bezel-core p-6 sm:p-8">
                    <h2 class="text-lg font-bold tracking-tight text-forest">Bukti pembayaran</h2>

                    @if ($status === 1)
                        <div class="mt-6 rounded-3xl bg-mint p-6 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-leaf">
                                <x-line-icon name="check" class="h-6 w-6" />
                            </span>
                            <p class="mt-4 font-bold text-forest">Pembayaran sudah diverifikasi</p>
                            <p class="mt-1 text-sm text-ink/60">Mobil Anda aman. Tinggal diambil di hari sewa.</p>
                        </div>
                    @elseif ($status === 2)
                        <div class="mt-6 rounded-3xl bg-amber-50 p-6 text-center ring-1 ring-amber-200">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-amber-600">
                                <x-line-icon name="clock" class="h-6 w-6" />
                            </span>
                            <p class="mt-4 font-bold text-amber-900">Menunggu verifikasi admin</p>
                            <p class="mt-1 text-sm text-amber-800/80">Bukti Anda sudah terkirim. Invoice akan dikirim ke email setelah diverifikasi.</p>
                        </div>
                    @elseif ($blokir)
                        <div class="mt-6 flex gap-3 rounded-3xl bg-red-50 p-5 text-sm text-red-700 ring-1 ring-red-200">
                            <x-line-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                            <p>{{ $blokir }}</p>
                        </div>
                    @else
                        @if ($pembayaran->catatan_admin)
                            <div class="mt-5 flex gap-3 rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                                <x-line-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                                <span><strong>Pembayaran sebelumnya ditolak.</strong> {{ $pembayaran->catatan_admin }}</span>
                            </div>
                        @endif

                        <form action="{{ route('pembayaran.upload', $booking->id) }}" method="POST" enctype="multipart/form-data"
                            id="uploadForm" class="mt-6">
                            @csrf

                            <label for="bukti_bayar" id="dropzone"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-3xl border border-dashed border-forest/25 bg-paper px-6 py-10 text-center transition-colors duration-500 ease-spring hover:border-leaf hover:bg-mint/50">
                                <span id="dropIdle" class="flex flex-col items-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-forest">
                                        <x-line-icon name="receipt" class="h-6 w-6" />
                                    </span>
                                    <span class="mt-4 text-sm font-semibold text-forest">Pilih foto bukti pembayaran</span>
                                    <span class="mt-1 text-xs text-ink/50">JPG atau PNG, maksimal 2 MB</span>
                                </span>
                                <span id="dropPreview" class="hidden w-full">
                                    <img id="previewImg" alt="Pratinjau bukti" class="mx-auto max-h-56 rounded-2xl object-contain">
                                    <span id="previewName" class="mt-3 block truncate text-sm font-semibold text-forest"></span>
                                    <span class="mt-1 block text-xs text-ink/50">Ketuk untuk mengganti foto</span>
                                </span>
                            </label>
                            <input type="file" id="bukti_bayar" name="bukti_bayar" accept="image/png,image/jpeg" class="sr-only" required>

                            <p id="fileError" class="mt-3 hidden text-sm text-red-600"></p>
                            @error('bukti_bayar')
                                <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <button type="submit" id="uploadBtn" class="btn btn-primary mt-6 w-full justify-between">
                                <span>Kirim bukti pembayaran</span>
                                <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        // Tab transfer / QRIS
        document.querySelectorAll('.pay-tab').forEach(tab => tab.addEventListener('click', () => {
            document.querySelectorAll('.pay-tab').forEach(t => {
                const on = t === tab;
                t.setAttribute('aria-selected', on);
                t.classList.toggle('bg-forest', on);
                t.classList.toggle('text-white', on);
                t.classList.toggle('text-ink/65', !on);
                document.getElementById('panel-' + t.dataset.tab).classList.toggle('hidden', !on);
            });
        }));

        // Salin nomor rekening
        function copyRek() {
            const rek = document.getElementById('rek-number').innerText.trim();
            const ok = () => Swal.fire({
                icon: 'success',
                title: 'Nomor rekening disalin',
                text: rek,
                showConfirmButton: false,
                timer: 1500
            });
            const fail = () => Swal.fire({
                icon: 'error',
                title: 'Tidak bisa menyalin',
                text: 'Salin nomor secara manual.',
                confirmButtonColor: '#16A34A'
            });

            if (navigator.clipboard) {
                navigator.clipboard.writeText(rek).then(ok).catch(fail);
                return;
            }
            const tmp = document.createElement('input');
            tmp.value = rek;
            document.body.appendChild(tmp);
            tmp.select();
            try {
                document.execCommand('copy') ? ok() : fail();
            } catch (e) {
                fail();
            }
            tmp.remove();
        }

        // Unggah bukti: pratinjau + cek jenis/ukuran sebelum dikirim
        (() => {
            const input = document.getElementById('bukti_bayar');
            if (!input) return;
            const err = document.getElementById('fileError');
            const btn = document.getElementById('uploadBtn');

            input.addEventListener('change', () => {
                const f = input.files[0];
                err.classList.add('hidden');
                if (!f) return;

                const okType = ['image/png', 'image/jpeg'].includes(f.type);
                const okSize = f.size <= 2 * 1024 * 1024;
                if (!okType || !okSize) {
                    err.textContent = !okType ? 'Format harus JPG atau PNG.' : 'Ukuran foto maksimal 2 MB.';
                    err.classList.remove('hidden');
                    input.value = '';
                    return;
                }

                document.getElementById('previewImg').src = URL.createObjectURL(f);
                document.getElementById('previewName').textContent = f.name;
                document.getElementById('dropIdle').classList.add('hidden');
                document.getElementById('dropPreview').classList.remove('hidden');
            });

            document.getElementById('uploadForm').addEventListener('submit', () => {
                btn.disabled = true;
                btn.firstElementChild.textContent = 'Mengirim...';
            });
        })();
    </script>
@endpush
