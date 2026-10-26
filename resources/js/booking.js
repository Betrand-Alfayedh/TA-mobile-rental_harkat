/**
 * Form booking (customer.booking.create): satu atau lebih mobil, masing-masing dengan tanggalnya sendiri.
 * Template per mobil ada di #mobil-template; ringkasan biaya di kartu samping.
 */
document.addEventListener("DOMContentLoaded", () => {
    const wrapper = document.getElementById("mobils-wrapper");
    // Skrip ini hanya untuk form booking; halaman lain tidak punya elemen-elemennya.
    if (!wrapper) return;

    // Harus sama dengan BookingService::MIN_LEAD_HOURS di server.
    const MIN_LEAD_HOURS = 3;

    const form = document.getElementById("bookingForm");
    const indicator = document.getElementById("indicator");
    const prevBtn = document.getElementById("prev-mobil");
    const nextBtn = document.getElementById("next-mobil");
    const slideLabel = document.getElementById("slideLabel");
    const submitBtn = document.getElementById("submitBooking");

    let index = 0;
    let currentSlide = 0;

    const items = () => [...wrapper.querySelectorAll(".mobil-item")];
    const rp = n => "Rp " + Math.round(n).toLocaleString("id-ID");
    const pad = n => String(n).padStart(2, "0");
    const toLocalInput = d =>
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

    // ===== SLIDER =====
    function slideTo(i) {
        currentSlide = Math.max(0, Math.min(i, items().length - 1));
        wrapper.style.transform = `translateX(-${currentSlide * 100}%)`;
        updateNav();
    }

    function updateNav() {
        const list = items();
        const total = list.length;

        list.forEach((el, i) => {
            el.querySelector(".mobil-no").textContent = i + 1;
            // satu-satunya mobil tidak bisa dihapus
            el.querySelector(".remove-mobil").classList.toggle("invisible", total === 1);
        });

        slideLabel.textContent = `Mobil ${currentSlide + 1}/${total}`;
        prevBtn.disabled = currentSlide === 0;
        nextBtn.disabled = currentSlide >= total - 1;

        indicator.innerHTML = "";
        indicator.classList.toggle("hidden", total === 1);
        for (let i = 0; i < total; i++) {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.setAttribute("aria-label", `Mobil ${i + 1}`);
            dot.className = "h-2 rounded-full transition-all duration-500 ease-spring " +
                (i === currentSlide ? "w-6 bg-leaf" : "w-2 bg-forest/20 hover:bg-forest/40");
            dot.addEventListener("click", () => slideTo(i));
            indicator.appendChild(dot);
        }
    }

    // ===== HARGA =====
    function hitungRingkasan() {
        const list = items();
        const total = list.reduce((sum, el) => sum + (parseInt(el.dataset.total) || 0), 0);

        document.getElementById("sumCount").textContent = list.length;
        document.getElementById("sumTotal").textContent = rp(total);
        document.getElementById("sumDp").textContent = rp(Math.floor(total / 2));
        document.getElementById("sumSisa").textContent = rp(total - Math.floor(total / 2));
        document.getElementById("totalHarga").value = total;
    }

    function hitungHarga(item) {
        const sewa = new Date(item.querySelector(".tanggal-sewa").value);
        const kembali = new Date(item.querySelector(".tanggal-kembali").value);
        const select = item.querySelector(".mobil-select");
        const opt = select.selectedOptions[0];
        const pakaiSupir = item.querySelector(".pakai-supir-checkbox").checked;
        const info = item.querySelector(".mobil-info");

        const harga = select.value ? parseInt(opt.dataset.harga) : 0;
        const allIn = select.value ? parseInt(opt.dataset.hargaAllin) : 0;

        info.innerHTML = select.value
            ? `Lepas kunci <b class="text-ink">${rp(harga)}</b>/hari &middot; Dengan supir <b class="text-ink">${rp(allIn)}</b>/hari`
            : "";

        if (select.value && !isNaN(sewa) && !isNaN(kembali) && kembali > sewa) {
            // sama seperti server: dibulatkan ke atas per 24 jam, minimal 1 hari
            const lama = Math.max(1, Math.ceil((kembali - sewa) / (1000 * 60 * 60 * 24)));
            const total = lama * (pakaiSupir ? allIn : harga);
            item.querySelector(".lama-sewa").value = lama;
            item.querySelector(".total-harga-mobil").value = total.toLocaleString("id-ID");
            item.dataset.total = total;
        } else {
            item.querySelector(".lama-sewa").value = "";
            item.querySelector(".total-harga-mobil").value = "";
            item.dataset.total = 0;
        }
        hitungRingkasan();
    }

    // ===== KETERSEDIAAN =====
    // Opsi mobil dinonaktifkan bila bentrok di tanggal itu, atau sudah dipilih di mobil lain pada booking ini.
    function syncOptions() {
        const chosen = items().map(el => el.querySelector(".mobil-select").value).filter(Boolean);
        items().forEach(el => {
            const select = el.querySelector(".mobil-select");
            [...select.options].forEach(o => {
                if (!o.value) return;
                const taken = o.dataset.free === "0";
                const usedElsewhere = chosen.includes(o.value) && select.value !== o.value;
                o.disabled = taken || usedElsewhere;
                o.hidden = o.disabled;
            });
        });
    }

    async function cekTersedia(item, manual = false) {
        const sewa = item.querySelector(".tanggal-sewa").value;
        const kembali = item.querySelector(".tanggal-kembali").value;
        const hasil = item.querySelector(".cek-hasil");
        const warna = ok => (hasil.className = "cek-hasil text-sm " + (ok ? "text-forest" : "text-red-600"));

        if (!sewa || !kembali) {
            if (manual) {
                hasil.textContent = "Isi tanggal mulai dan kembali dulu.";
                warna(false);
            }
            return;
        }
        if (new Date(kembali) <= new Date(sewa)) {
            hasil.textContent = "Tanggal kembali harus setelah mulai sewa.";
            warna(false);
            return;
        }

        hasil.textContent = "Memeriksa...";
        hasil.className = "cek-hasil text-sm text-ink/55";
        try {
            const { data } = await window.axios.post(form.dataset.checkUrl, {
                tanggal_sewa: sewa,
                tanggal_kembali: kembali,
            });
            const free = new Set(data.mobils.map(m => String(m.id)));
            const select = item.querySelector(".mobil-select");

            [...select.options].forEach(o => {
                if (o.value) o.dataset.free = free.has(o.value) ? "1" : "0";
            });
            if (select.value && !free.has(select.value)) select.value = ""; // pilihan lama bentrok

            syncOptions();
            hitungHarga(item);
            hasil.textContent = free.size
                ? `${free.size} mobil tersedia di tanggal ini`
                : "Tidak ada mobil tersedia di tanggal ini";
            warna(free.size > 0);
        } catch (e) {
            hasil.textContent = e.response?.data?.message ?? "Gagal memeriksa ketersediaan. Coba lagi.";
            warna(false);
        }
    }

    // ===== SATU MOBIL =====
    function attachListeners(item) {
        const tanggalSewa = item.querySelector(".tanggal-sewa");
        const tanggalKembali = item.querySelector(".tanggal-kembali");
        const mobilSelect = item.querySelector(".mobil-select");
        const pakaiSupir = item.querySelector(".pakai-supir-checkbox");
        const hiddenSupir = item.querySelector(".hidden-pakai-supir");

        tanggalSewa.min = toLocalInput(new Date(Date.now() + MIN_LEAD_HOURS * 3600 * 1000));

        tanggalSewa.addEventListener("change", () => {
            if (tanggalSewa.value) {
                // kembali otomatis = sewa + 1 hari, jam sama
                const sewa = new Date(tanggalSewa.value);
                const kembali = new Date(sewa);
                kembali.setDate(sewa.getDate() + 1);
                tanggalKembali.value = toLocalInput(kembali);
                tanggalKembali.min = toLocalInput(new Date(sewa.getTime() + 60 * 1000));
            }
            hitungHarga(item);
            cekTersedia(item);
        });

        tanggalKembali.addEventListener("change", () => {
            if (tanggalKembali.value && tanggalSewa.value) {
                // jam kembali mengikuti jam sewa (tarif per 24 jam)
                const sewa = new Date(tanggalSewa.value);
                const kembali = new Date(tanggalKembali.value);
                kembali.setHours(sewa.getHours(), sewa.getMinutes(), 0, 0);
                tanggalKembali.value = toLocalInput(kembali);
            }
            hitungHarga(item);
            cekTersedia(item);
        });

        mobilSelect.addEventListener("change", () => {
            syncOptions();
            hitungHarga(item);
        });

        pakaiSupir.addEventListener("change", () => {
            hiddenSupir.value = pakaiSupir.checked ? 1 : 0;
            hitungHarga(item);
        });

        item.querySelector(".check-mobils").addEventListener("click", () => cekTersedia(item, true));

        item.querySelector(".remove-mobil").addEventListener("click", () => {
            if (items().length === 1) return;
            item.remove();
            syncOptions();
            hitungRingkasan();
            slideTo(Math.min(currentSlide, items().length - 1));
        });
    }

    function addMobil() {
        const template = document.getElementById("mobil-template").firstElementChild.cloneNode(true);

        template.querySelectorAll("input,select").forEach(el => {
            if (el.name) el.name = el.name.replace(/\d+/, index);
            if (el.type === "checkbox") el.checked = false;
            if (el.type === "datetime-local") el.value = "";
            if (el.classList.contains("lama-sewa") || el.classList.contains("total-harga-mobil")) el.value = "";
        });
        template.dataset.total = 0;

        wrapper.appendChild(template);
        attachListeners(template);
        syncOptions();
        hitungRingkasan();
        slideTo(items().length - 1);
        index++;
    }

    // ===== INIT =====
    document.getElementById("add-mobil").addEventListener("click", addMobil);
    prevBtn.addEventListener("click", () => slideTo(currentSlide - 1));
    nextBtn.addEventListener("click", () => slideTo(currentSlide + 1));

    document.getElementById("asalKota").addEventListener("change", e => {
        document.getElementById("namaKotaWrapper").classList.toggle("hidden", e.target.value !== "2");
    });

    // Bila ada isian wajib yang kosong di mobil lain, geser ke mobil itu agar pesan validasi terlihat.
    form.addEventListener("invalid", e => {
        const item = e.target.closest?.(".mobil-item");
        if (item) slideTo(items().indexOf(item));
    }, true);

    // Cegah klik ganda: server sudah menolak booking kembar, tapi tombol tetap dikunci.
    form.addEventListener("submit", () => {
        submitBtn.disabled = true;
        submitBtn.firstElementChild.textContent = "Memproses...";
    });

    // Tambah 1 mobil default pas load
    addMobil();
});
