@extends('layouts.admin')

@section('content')
    @php
        $details = $pelunasan->booking->details;
        $inp = 'mt-1.5 w-full rounded-[10px] border border-hk-line bg-white px-3.5 py-2.5 text-sm';
    @endphp
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.pengembalian.index') }}" class="text-sm text-hk-mute hover:text-hk-ink">&larr; Kembali ke Pengembalian Mobil</a>
        <h1 class="mb-6 mt-2 text-2xl">Proses Pengembalian Mobil</h1>

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="panel mb-6">
            <span class="label">Pesanan</span>
            <div class="panel-card space-y-4 p-5">
                <p class="text-sm"><span class="text-hk-mute">Pelanggan</span> &nbsp; <b>{{ $pelunasan->booking->user->name }}</b></p>

                <div>
                    <label for="pilihMobil" class="label">Unit mobil</label>
                    <select id="pilihMobil" class="{{ $inp }}">
                        <option value="">-- Pilih Mobil --</option>
                        @foreach ($pelunasan->booking->details as $detail)
                            <option value="{{ $detail->id }}" data-mulai="{{ $detail->tanggal_mulai_format }}"
                                data-selesai="{{ $detail->tanggal_selesai_iso }}">
                                {{ $detail->mobil->masterMobil->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <p><span class="block text-hk-mute">Tanggal sewa</span><span id="tanggal-sewa-display">-</span></p>
                    <p><span class="block text-hk-mute">Tanggal kembali (rencana)</span><span id="tanggal-kembali-rencana" data-date="">-</span></p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.pengembalian.store') }}" method="POST" enctype="multipart/form-data"
            onsubmit="return confirm('Catat pengembalian kendaraan ini? Tindakan ini tidak dapat dibatalkan.')"
            class="panel">
            @csrf
            <span class="label">Catatan pengembalian</span>
            <div class="panel-card space-y-4 p-5">
                <input type="hidden" name="booking_detail_id" id="bookingDetailId" value="">

                <div>
                    <label for="tglKembaliReal" class="label">Tanggal kembali aktual</label>
                    <input type="datetime-local" name="tanggal_kembali_aktual" id="tglKembaliReal" required class="{{ $inp }}">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="kondisi" class="label">Kondisi mobil</label>
                        <select name="kondisi" id="kondisi" class="{{ $inp }}">
                            <option value="1">Baik</option>
                            <option value="0">Rusak</option>
                        </select>
                    </div>
                    <div>
                        <label for="dendaDisplay" class="label">Denda keterlambatan</label>
                        <input type="text" id="dendaDisplay" class="{{ $inp }} bg-hk-panel" readonly placeholder="Isi tanggal kembali">
                        <input type="hidden" name="denda_flag" id="dendaFlag" value="0">
                        <input type="hidden" name="nominal_denda" id="nominalDenda" value="0">
                    </div>
                </div>

                <div>
                    <label for="catatan" class="label">Catatan</label>
                    <textarea name="catatan" id="catatan" rows="3" maxlength="500" class="{{ $inp }}"></textarea>
                </div>

                <div>
                    <label for="gambar" class="label">Foto mobil</label>
                    <input type="file" name="gambar" id="gambar" accept="image/jpeg,image/png" class="{{ $inp }}">
                </div>

                <button type="submit" class="w-full rounded-xl bg-hk-ink px-4 py-3 text-sm font-medium text-white hover:bg-black">
                    Simpan pengembalian
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const pilihMobil = document.getElementById("pilihMobil");
            const tanggalSewaDisplay = document.getElementById("tanggal-sewa-display");
            const spanRencana = document.getElementById("tanggal-kembali-rencana");
            const bookingDetailId = document.getElementById("bookingDetailId");

            const tglKembaliReal = document.getElementById("tglKembaliReal");
            const dendaDisplay = document.getElementById("dendaDisplay");
            const dendaFlag = document.getElementById("dendaFlag");
            const nominalDenda = document.getElementById("nominalDenda");

            let rencanaDate = null;

            function updateDetailMobil() {
                const option = pilihMobil.options[pilihMobil.selectedIndex];
                const mulai = option.dataset.mulai;
                const selesai = option.dataset.selesai;

                if (mulai && selesai) {
                    tanggalSewaDisplay.textContent = mulai; // format readable
                    spanRencana.textContent = new Date(selesai).toLocaleString("id-ID"); // format ID
                    spanRencana.dataset.date = selesai;
                    bookingDetailId.value = option.value;

                    rencanaDate = new Date(selesai);
                } else {
                    tanggalSewaDisplay.textContent = "-";
                    spanRencana.textContent = "-";
                    bookingDetailId.value = "";
                    rencanaDate = null;
                }

                // reset denda
                dendaDisplay.value = "";
                dendaFlag.value = 0;
                nominalDenda.value = 0;
                tglKembaliReal.value = "";
            }

            pilihMobil.addEventListener("change", updateDetailMobil);

            // otomatis pilih mobil pertama kalau ada
            if (pilihMobil.options.length > 1) {
                pilihMobil.selectedIndex = 1;
                updateDetailMobil();
            }

            tglKembaliReal.addEventListener("change", function() {
                if (!rencanaDate) return;

                let realDate = new Date(this.value);
                let denda = 0;
                let flag = 0;

                if (realDate > rencanaDate) {
                    let diffMs = realDate.getTime() - rencanaDate.getTime();
                    let diffHours = Math.ceil(diffMs / (1000 * 60 * 60)); // jam yang sudah dimulai dihitung penuh, sama dengan server
                    denda = diffHours * 50000;
                    flag = 1;
                }

                dendaDisplay.value = flag === 1 ? "Rp " + denda.toLocaleString("id-ID") : "Tidak Ada Denda";
                dendaFlag.value = flag;
                nominalDenda.value = denda;
            });
        });
    </script>
@endsection
