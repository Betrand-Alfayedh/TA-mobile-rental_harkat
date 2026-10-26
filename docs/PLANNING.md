# Planning Perubahan Sistem — Harkat Rent Car (Mobile + API)

**Tanggal:** 6 Oktober 2026
**Keputusan arah (hasil bimbingan):** pelanggan memesan lewat aplikasi mobile; seluruh peran (pelanggan, admin, sopir) memakai **satu aplikasi Flutter**; Laravel berfungsi sebagai **RESTful API**. Web yang sudah ada **tidak dihapus**, hanya tidak menjadi bagian TA.

Dokumen terkait: [PRD_Mobile.md](PRD_Mobile.md) · [PRD_API.md](PRD_API.md) · [TRD_Mobile.md](TRD_Mobile.md) · [TRD_API.md](TRD_API.md)

---

## 1. Kondisi Sekarang vs Target

| Area | Sekarang | Target |
|---|---|---|
| Peran di aplikasi | Pelanggan saja | Pelanggan, Admin, Sopir |
| API | Pelanggan (`/api/login`, `/mobils`, `/bookings`, `/pembayaran`) | + `/api/admin/*` dan `/api/sopir/*`, dengan pengecekan peran |
| Login API | Menolak selain peran 4 | Menerima 2, 3, 4; menolak akun nonaktif dan sopir belum disetujui |
| Logika bisnis | Tersebar di controller web | Dipusatkan di kelas layanan (`app/Services`), dipakai web dan API |
| Penolakan tugas oleh sopir | Belum ada | Ada (`job_offers.status = 3`) |
| Laporan kondisi kendaraan | Belum ada | Tabel `laporan_kondisis` + endpoint + foto |
| Rekap | Query laporan masih salah (BUGS #35) | Ringkasan bulanan benar dan teruji |
| Token di aplikasi | `SharedPreferences` | `flutter_secure_storage` |
| Web | Berjalan | **Tetap berjalan, tidak disentuh** |

## 2. Prinsip Pengerjaan

1. **Tambah, jangan ubah.** Migrasi hanya menambah tabel atau kolom; tidak ada yang dihapus atau diubah bentuknya.
2. **Satu logika, dua pintu.** Aturan bisnis hidup di `app/Services`; controller web dan API hanya memanggilnya. Setiap pemindahan harus lolos tes web yang ada.
3. **Tes dulu pada alur inti.** Tiap fase ditutup dengan tes PHPUnit lulus (`db_rentcar_test`) dan pemeriksaan web masih normal.
4. **Satu fase = satu hasil yang bisa didemokan** (Postman atau layar aplikasi).
5. **Dokumen lama tidak ditimpa.** PRD lama diarsipkan di `docs/arsip/`.

## 3. Alur Bisnis Target

```
PELANGGAN                    ADMIN                           SOPIR
1 daftar / login
2 lihat armada, cek jadwal
3 buat pesanan → biaya + DP 50%
4 unggah bukti DP        ─►  5 verifikasi DP (terima/tolak)
                             6 pesanan dengan sopir:    ─►   7 terima / tolak tugas
                               tugaskan sopir
                             8 hari jemput: laporan kondisi (sebelum),
                               konfirmasi jemput (butuh DP terverifikasi)
9 pantau status          ◄─  10 mobil kembali: laporan kondisi (sesudah),
                                catat pengembalian + denda → pesanan Done
11 unggah bukti pelunasan ─► 12 verifikasi / tandai lunas
```

Pesanan **manual oleh admin** (pelanggan datang langsung atau lewat WA) disediakan sebagai fitur tambahan prioritas rendah.

## 4. Fase Pengerjaan (8 minggu)

### Fase 0 — Fondasi dokumen (minggu 1)
- PRD dan TRD baru (dokumen ini), arsip PRD lama.
- **Selesai bila:** lima dokumen di `docs/` disetujui.

### Fase 1 — Fondasi API (minggu 2) — **SELESAI 6 Okt 2026**
| # | Tugas | Berkas |
|---|---|---|
| 1.1 | Migrasi: `laporan_kondisis`, `users.aktif`, `pembayarans.dicatat_oleh`, `pengembalians.dicatat_oleh`, status tawaran 3 | `database/migrations/*` |
| 1.2 | Middleware peran `api.role:admin\|sopir\|customer` + alias | `app/Http/Middleware/ApiRole.php`, `bootstrap/app.php` |
| 1.3 | Login API semua peran + `POST /api/register` didaftarkan di route | `Api/AuthController`, `routes/api.php` |
| 1.4 | Token berkedaluwarsa (7 hari), login akun nonaktif ditolak (BUGS #38) | `config/sanctum.php`, `AuthController` |
| 1.5 | Pindahkan logika verifikasi/tolak/lunas, penugasan, jemput, pengembalian ke layanan | `app/Services/{PembayaranService,PenugasanService,PengembalianService}.php` |
| 1.6 | Perbaiki `assignJobSupir` (hanya sopir aktif dan disetujui; tidak menimpa `pakai_supir`) | `Admin/BookingController`, `PenugasanService` |
| 1.7 | Tes: login per peran, akses lintas peran ditolak, regresi web | `tests/Feature/*` |
- **Selesai bila:** semua tes lulus; admin dan sopir bisa login lewat Postman; pelanggan tidak bisa membuka `/api/admin/*`.
- **Hasil:** 43 tes lulus (274 assertion) + 1 tes web baru. Catatan: 14 tes bawaan Breeze (`Auth\*`, `ProfileTest`) gagal karena merujuk route yang memang tidak ada di project ini; tidak terkait perubahan. `LaporanKondisiService` dan `RekapService` dibuat pada Fase 3.

### Fase 2 — API Admin: alur inti (minggu 3–4) — **SELESAI 6 Okt 2026**
| # | Tugas |
|---|---|
| 2.1 | `GET /admin/bookings[/{id}]`, `POST /admin/bookings/{id}/cancel` |
| 2.2 | `GET /admin/pembayaran`, `POST …/verifikasi`, `…/tolak`, `…/lunas` |
| 2.3 | `POST /admin/detail/{id}/penugasan`, `PATCH /admin/detail/{id}/jemput` |
| 2.4 | `POST /admin/detail/{id}/pengembalian` (denda otomatis) |
| 2.5 | `GET /admin/dashboard` |
| 2.6 | Tes fitur per endpoint, termasuk kasus gagal (DP belum lunas, bentrok, peran salah) |
- **Selesai bila:** satu pesanan dapat berjalan dari Booked sampai Done hanya lewat API (skenario Postman terdokumentasi).
- **Hasil:** 11 endpoint admin; `AdminApiTest` (8 tes, 111 assertion) mencakup alur penuh pelanggan → admin → sopir → jemput → kembali terlambat → pelunasan, serta kasus gagal dan 403/401 pada seluruh endpoint. Total tes project: 52 lulus (391 assertion). Koleksi Postman: `docs/postman/Harkat-API.postman_collection.json`. Penerimaan tugas oleh sopir di tes alur penuh masih lewat service; endpoint sopir menyusul di Fase 3.

### Fase 3 — API Sopir, laporan kondisi, master, rekap (minggu 5) — **SELESAI 6 Okt 2026**
| # | Tugas |
|---|---|
| 3.1 | `GET /sopir/dashboard`, `PATCH /sopir/status`, `GET /sopir/tawaran`, `POST …/terima`, `POST …/tolak`, `GET /sopir/tugas`, `GET /sopir/riwayat` |
| 3.2 | Laporan kondisi: `GET\|POST /admin/detail/{id}/laporan-kondisi`, `POST /sopir/tugas/{id}/laporan-kondisi` (unggah foto tervalidasi) |
| 3.3 | CRUD `admin/mobils`, `admin/supirs`, `admin/penyewa` (tanpa alur approval Super Admin) |
| 3.4 | `GET /admin/rekap?bulan=` (memperbaiki BUGS #35) |
| 3.5 | Koleksi Postman lengkap |
- **Selesai bila:** seluruh endpoint di PRD_API tersedia dan lulus tes.
- **Hasil:** 27 endpoint baru (admin: laporan kondisi 2, referensi 1, armada 7, sopir 4, penyewa 4, rekap 1; sopir: 8). Tes baru `SopirApiTest` (8) dan `AdminMasterApiTest` (10). Total tes project: **70 lulus (611 assertion)**. Koleksi Postman lengkap (40 request, admin dan sopir). Temuan saat pengujian: pembatas peran harus berjalan sebelum pengikatan model (non-admin yang menebak id mendapat 404, bukan 403); sudah diperbaiki lewat prioritas middleware.
- **Keputusan:** email penyewa wajib (invoice tetap terkirim); penyewa yang dicatat admin diberi kata sandi acak. Penolakan tawaran/konflik dijawab 409; pelanggaran aturan lain 422.

### Fase 4 — Aplikasi Flutter: fondasi dan sopir (minggu 6) — **SELESAI 6 Okt 2026**
| # | Tugas |
|---|---|
| 4.1 | Token ke `flutter_secure_storage`; `UserModel` memuat peran; `RoleShell` mengarahkan layar |
| 4.2 | Provider dan layar sopir: tawaran (terima/tolak), tugas aktif, riwayat, status, laporan kondisi |
- **Selesai bila:** sopir menerima tawaran dari admin (uji dengan Postman) dan mengirim laporan kondisi.
- **Hasil:** token di `flutter_secure_storage` (token lama dimigrasikan otomatis); `UserModel` memuat getter peran; `shellFor` mengarahkan admin/sopir/pelanggan; layar sopir lengkap (beranda, tawaran terima/tolak, tugas, riwayat, laporan kondisi dengan foto, profil); polling 30 detik hanya saat aplikasi terlihat; data sopir dibuang saat logout. Tes Flutter: 48 lulus (10 lama + 38 baru: penyimpanan, model, pemilihan layar, provider, dan layar). `flutter analyze`: tidak ada masalah baru (80, sama dengan sebelum Fase 4). Layar admin berupa penampung (`AdminShell`) sampai Fase 5. **Belum diuji di perangkat/emulator**; pengujian manual dengan tiga akun dijadwalkan di Fase 6.

### Fase 5 — Aplikasi Flutter: admin (minggu 7) — **SELESAI 6 Okt 2026**
| # | Tugas |
|---|---|
| 5.1 | Beranda admin, daftar pesanan, detail dengan aksi (verifikasi, tugaskan, jemput, kembali, batal) |
| 5.2 | Pembayaran, armada, sopir, penyewa, rekap |
- **Selesai bila:** alur lengkap pelanggan → admin → sopir berjalan di tiga akun pada perangkat atau emulator.
- **Hasil:** 5 tab admin (Beranda, Pesanan, Pembayaran dengan lencana, Armada, Lainnya). Beranda: hal yang perlu ditindak (tap pindah tab). Pesanan: cari, filter status, paginasi, detail dengan semua aksi (verifikasi/tolak/tandai lunas, tugaskan sopir, jemput, laporan kondisi, pengembalian dengan pratinjau denda, batalkan), setiap aksi lewat konfirmasi. Armada, sopir, penyewa: daftar + form dengan validasi sisi klien. Rekap bulanan dengan pemilih bulan. Satu perubahan backend: detail pesanan kini memuat laporan kondisi tiap unit. Tes Flutter: **134 lulus** (48 sebelum Fase 5 + 86 baru: model, dua provider, layar). Tes backend: 70 lulus (613 assertion). `flutter analyze`: tidak ada masalah baru (80, sama dengan baseline). **Belum diuji di perangkat/emulator**; skenario tiga akun menjadi inti Fase 6.

### Fase 6 — Pengujian, perbaikan, laporan (minggu 8) — **SELESAI 7 Okt 2026** (bagian sistem)
- Black box (Postman + skenario aplikasi), perbaikan temuan, pembaruan `BUGS.md`, bahan Bab IV–V.
- **Hasil:** katalog **132 kasus black box** (partisi kesetaraan dan nilai batas) dengan bukti yang diverifikasi otomatis ke nama tes yang ada; 25 langkah uji manual di perangkat (dikonfirmasi pengguna sesuai); **14 temuan** diperbaiki, termasuk satu bug produksi (kolom `keterangan` pembayaran tidak pernah dimigrasikan). Tes otomatis: 107 PHPUnit (872 assertion) + 145 Flutter, seluruhnya lulus. Berkas: `docs/PENGUJIAN.md`, `docs/Pengujian_BlackBox.xlsx`, `docs/BAHAN_BAB_IV_V.md`. **Belum:** tangkapan layar sebagai bukti manual; penulisan Bab I–V di dokumen TA (.docx).
- Jika waktu mepet, tangguhkan: pesanan manual admin, CRUD penyewa, grafik rekap, push notification.

## 5. Daftar Bug Terbuka yang Disentuh Rencana Ini

| Bug | Penanganan |
|---|---|
| #38 supir bisa login sebelum approval | Fase 1.4 |
| `assignJobSupir` mengirim ke sopir belum disetujui dan menimpa `pakai_supir` | Fase 1.6 |
| #35 laporan/rekap salah | Fase 3.4 |
| #34 path gambar / `nama_mobil` | Dipastikan benar di Resource baru |
| #36 FK cascade | Dievaluasi saat migrasi Fase 1; tidak diubah bila berisiko bagi web |
| #22 #23 #24 mobile | #24 (token aman) **selesai** di Fase 4; #22 #23 dicek ulang saat layar baru dibuat |

## 6. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Tiga peran dalam 8 minggu | Jadwal molor | Prioritaskan alur inti; fitur tambahan masuk daftar tangguhan |
| Pemindahan logika merusak web | Web error | Tes web yang ada (30 tes) dijalankan setiap kali; pindahkan satu fungsi per commit |
| Perubahan skema mengganggu pemilik repo | Konflik | Hanya migrasi tambahan; tidak ada penghapusan |
| Foto kondisi memakan penyimpanan | Disk penuh | Batas ukuran 2 MB, jenis jpg/png, penamaan acak |
| Tidak ada notifikasi realtime | Admin/sopir telat tahu | Polling tiap 30 detik pada layar tawaran/pesanan + tarik-untuk-segarkan |
| Akun pengujian | Data campur | Seeder terpisah untuk 1 admin, 2 sopir, 2 pelanggan |

## 7. Di Luar Lingkup

Super Admin dan alur approval; login Google untuk staf (tetap tersedia untuk pelanggan, tidak dibahas di TA); pembayaran online/payment gateway; push notification (opsional); iOS; antarmuka web sebagai bagian TA.
