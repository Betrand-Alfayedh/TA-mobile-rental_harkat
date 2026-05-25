document.addEventListener("DOMContentLoaded", () => {
    let index = 0;
    let currentSlide = 0;

    const wrapper = document.getElementById("mobils-wrapper");
    const indicator = document.getElementById("indicator");

    // ===== HELPER =====
    function slideTo(i) {
        currentSlide = i;
        wrapper.style.transform = `translateX(-${currentSlide * 100}%)`;
        updateIndicator();
    }

    function updateIndicator() {
        let total = wrapper.querySelectorAll(".mobil-item").length;
        indicator.innerHTML = "";
        for (let i = 0; i < total; i++) {
            let dot = document.createElement("div");
            dot.className = "w-3 h-3 rounded-full cursor-pointer transition " +
                (i === currentSlide ? "bg-green-500 scale-110" : "bg-gray-400");
            dot.addEventListener("click", () => slideTo(i));
            indicator.appendChild(dot);
        }
    }

    function hitungHarga(item) {
        const tglSewa = new Date(item.querySelector(".tanggal-sewa").value);
        const tglKembali = new Date(item.querySelector(".tanggal-kembali").value);
        const mobilSelect = item.querySelector(".mobil-select");
        const pakaiSupir = item.querySelector(".pakai-supir-checkbox");

        if (!tglSewa || !tglKembali || !mobilSelect.value) return;

        let lama = Math.max(1, Math.ceil((tglKembali - tglSewa) / (1000 * 60 * 60 * 24)));
        item.querySelector(".lama-sewa").value = lama;

        let hargaSewa = parseInt(mobilSelect.selectedOptions[0].dataset.harga);
        let hargaAllIn = parseInt(mobilSelect.selectedOptions[0].dataset.hargaAllin);
        let total = pakaiSupir.checked ? lama * hargaAllIn : lama * hargaSewa;

        item.querySelector(".total-harga-mobil").value = total.toLocaleString("id-ID");

        // total global
        let totalAll = [...document.querySelectorAll(".total-harga-mobil")]
            .reduce((sum, el) => sum + (parseInt(el.value.replace(/\./g, "")) || 0), 0);

        document.getElementById("totalHarga").value = totalAll.toLocaleString("id-ID");
        document.getElementById("uangMuka").value = (totalAll / 2).toLocaleString("id-ID");
    }

    function attachListeners(item) {
        const tanggalSewa = item.querySelector(".tanggal-sewa");
        const tanggalKembali = item.querySelector(".tanggal-kembali");
        const mobilSelect = item.querySelector(".mobil-select");
        const pakaiSupir = item.querySelector(".pakai-supir-checkbox");
        const hiddenSupir = item.querySelector(".hidden-pakai-supir");

        // === Atur tanggal kembali otomatis ===
        tanggalSewa.addEventListener("change", () => {
            if (tanggalSewa.value) {
                let sewa = new Date(tanggalSewa.value);

                // bikin kembali = sewa + 1 hari
                let kembali = new Date(sewa);
                kembali.setDate(sewa.getDate() + 1);

                // format yyyy-MM-ddTHH:mm
                let yyyy = kembali.getFullYear();
                let mm = String(kembali.getMonth() + 1).padStart(2, '0');
                let dd = String(kembali.getDate()).padStart(2, '0');
                let hh = String(sewa.getHours()).padStart(2, '0');
                let min = String(sewa.getMinutes()).padStart(2, '0');
                let formatted = `${yyyy}-${mm}-${dd}T${hh}:${min}`;

                tanggalKembali.value = formatted;
            }
            hitungHarga(item);
        });
        // === Kalau user ubah tanggal kembali, jam tetep ikut jam sewa ===
        tanggalKembali.addEventListener("change", () => {
            if (tanggalKembali.value && tanggalSewa.value) {
                let sewa = new Date(tanggalSewa.value);
                let kembali = new Date(tanggalKembali.value);

                // jam kembali = jam sewa
                kembali.setHours(sewa.getHours(), sewa.getMinutes(), 0, 0);

                let yyyy = kembali.getFullYear();
                let mm = String(kembali.getMonth() + 1).padStart(2, '0');
                let dd = String(kembali.getDate()).padStart(2, '0');
                let hh = String(sewa.getHours()).padStart(2, '0');
                let min = String(sewa.getMinutes()).padStart(2, '0');
                let formatted = `${yyyy}-${mm}-${dd}T${hh}:${min}`;

                tanggalKembali.value = formatted;
            }
            hitungHarga(item);
        });
        [tanggalSewa, tanggalKembali, mobilSelect].forEach(el =>
            el.addEventListener("change", () => hitungHarga(item))
        );
        pakaiSupir.addEventListener("change", () => {
            hiddenSupir.value = pakaiSupir.checked ? 1 : 0;
            hitungHarga(item);
        });

        item.querySelector(".remove-mobil").addEventListener("click", () => {
            if (wrapper.querySelectorAll(".mobil-item").length === 1) return;
            item.remove();
            if (currentSlide >= wrapper.querySelectorAll(".mobil-item").length) {
                currentSlide--;
            }
            slideTo(currentSlide);
        });
    }

    function addMobil() {
        let template = document.getElementById("mobil-template").firstElementChild.cloneNode(true);

        template.querySelectorAll("input,select").forEach(el => {
            if (el.name) el.name = el.name.replace(/\d+/, index);
            if (el.type === "checkbox") el.checked = false;
            if (el.type === "datetime-local") el.value = "";
            if (el.classList.contains("lama-sewa") || el.classList.contains("total-harga-mobil")) el.value = "";
        });

        wrapper.appendChild(template);
        attachListeners(template);
        slideTo(wrapper.querySelectorAll(".mobil-item").length - 1);
        index++;
    }

    // ===== INIT =====
    document.getElementById("add-mobil").addEventListener("click", addMobil);
    document.getElementById("prev-mobil").addEventListener("click", () => {
        if (currentSlide > 0) slideTo(currentSlide - 1);
    });
    document.getElementById("next-mobil").addEventListener("click", () => {
        if (currentSlide < wrapper.querySelectorAll(".mobil-item").length - 1) slideTo(currentSlide + 1);
    });

    document.getElementById("asalKota").addEventListener("change", e => {
        document.getElementById("namaKotaWrapper").classList.toggle("hidden", e.target.value !== "2");
    });

    // Tambah 1 mobil default pas load
    addMobil();
});
