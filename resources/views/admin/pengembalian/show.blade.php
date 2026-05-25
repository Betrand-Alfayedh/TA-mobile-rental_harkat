@extends('layouts.admin')

@section('content')
    @php
        $details = $pelunasan->booking->details;
    @endphp
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Form Pengembalian Mobil</h1>

        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <p><b>Customer:</b> {{ $pelunasan->booking->user->name }}</p>

            {{-- Pilih unit mobil --}}
            <div class="mb-4">
                <label class="block font-medium">Pilih Unit Mobil</label>
                <select id="pilihMobil" class="w-full border rounded px-3 py-2">
                    <option value="">-- Pilih Mobil --</option>
                    @foreach ($pelunasan->booking->details as $detail)
                        <option value="{{ $detail->id }}" data-mulai="{{ $detail->tanggal_mulai_format }}"
                            data-selesai="{{ $detail->tanggal_selesai_iso }}">
                            {{ $detail->mobil->masterMobil->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <p><b>Tanggal Sewa:</b>
                <span id="tanggal-sewa-display">-</span>
            </p>
            <p><b>Tanggal Kembali (rencana):</b>
                <span id="tanggal-kembali-rencana" data-date="">-</span>
            </p>
        </div>

        <form action="{{ route('admin.pengembalian.store') }}" method="POST" enctype="multipart/form-data"
            class="bg-white p-4 rounded-lg shadow">
            @csrf
            <input type="hidden" name="booking_detail_id" id="bookingDetailId" value="">

            <div class="mb-4">
                <label class="block font-medium">Tanggal Kembali Aktual</label>
                <input type="datetime-local" name="tanggal_kembali_aktual" id="tglKembaliReal"
                    class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium">Kondisi Mobil</label>
                <select name="kondisi" class="w-full border rounded px-3 py-2">
                    <option value="1">Baik</option>
                    <option value="0">Rusak</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-medium">Denda</label>
                <input type="text" id="dendaDisplay" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                <input type="hidden" name="denda_flag" id="dendaFlag" value="0">
                <input type="hidden" name="nominal_denda" id="nominalDenda" value="0">
            </div>

            <div class="mb-4">
                <label class="block font-medium">Catatan</label>
                <textarea name="catatan" class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div class="mb-4">
                <label class="block font-medium">Foto Mobil</label>
                <input type="file" name="gambar" class="w-full border rounded px-3 py-2">
            </div>

            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg shadow">
                ✅ Simpan Pengembalian
            </button>
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
                    let diffHours = Math.floor(diffMs / (1000 * 60 * 60));
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
