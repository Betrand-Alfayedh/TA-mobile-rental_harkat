# Product Requirements Document (PRD)
## RESTful API — Sistem Informasi Manajemen Rental Mobil Harkat

**Versi:** 2.0 · **Tanggal:** 6 Oktober 2026 · **Pembaruan dari:** API khusus pelanggan (v1)
**Konsumen API:** aplikasi mobile Flutter (pelanggan, admin, sopir)

---

## 1. Tujuan

Menyediakan antarmuka data tunggal bagi aplikasi mobile untuk mengelola siklus penyewaan mobil: pemesanan oleh pelanggan, verifikasi pembayaran dan penugasan oleh admin, serta penerimaan tugas dan pelaporan kondisi oleh sopir. API tidak memiliki antarmuka web; web yang ada di repo ini terpisah dan tidak menjadi bagian dari lingkup.

## 2. Pengguna dan Hak Akses

| Peran | Kode | Prefiks route | Hak utama |
|---|---|---|---|
| Pelanggan | 4 | `/api/*` | Katalog, pesan, bayar, riwayat |
| Admin | 2 | `/api/admin/*` | Kelola pesanan, pembayaran, penugasan, jemput, pengembalian, armada, sopir, penyewa, rekap |
| Sopir | 3 | `/api/sopir/*` | Status, tawaran, tugas, riwayat, laporan kondisi |

Super Admin (1) di luar lingkup. Setiap endpoint menolak peran lain dengan **403**.

## 3. Konvensi

- Basis URL: `/api`. Format JSON; unggah foto memakai `multipart/form-data`.
- Autentikasi: `Authorization: Bearer <token>` (Laravel Sanctum). Token berkedaluwarsa 7 hari.
- Envelope respons: `{ "status": true|false, "message": "...", "data": ... }`.
- Kode: 200/201 sukses · 401 belum login/token kedaluwarsa · 403 peran tidak berhak · 404 tidak ditemukan · 409 konflik (mis. tugas sudah diambil sopir lain) · 422 validasi/aturan bisnis gagal (`errors` per kolom) · 429 terlalu banyak percobaan.
- Daftar besar dipaginasi: `?page=&per_page=`.
- Nominal sebagai bilangan bulat rupiah; tanggal ISO 8601.

## 4. Kebutuhan Fungsional

Tanda: **[ADA]** sudah ada dan teruji · **[UBAH]** ada, perlu penyesuaian · **[BARU]**. Prioritas P1 (inti) · P2 (penting) · P3 (tambahan).

### 4.1 Autentikasi dan Profil (semua peran)
| ID | Endpoint | Keterangan | Status | Prio |
|---|---|---|---|---|
| A1 | `POST /register` | Daftar pelanggan (peran 4) | [UBAH] controller ada, route belum | P1 |
| A2 | `POST /login` | Menerima peran 2/3/4; mengembalikan token + peran; menolak akun nonaktif dan sopir belum disetujui; pembatasan 5/menit | [UBAH] | P1 |
| A3 | `POST /logout` | Cabut token | [ADA] | P1 |
| A4 | `GET /me`, `PUT /me`, `PUT /me/password` | Profil | [ADA] | P1 |

### 4.2 Pelanggan
| ID | Endpoint | Keterangan | Status | Prio |
|---|---|---|---|---|
| C1 | `GET /mobils`, `/mobils/available`, `/mobils/{id}`, `/tipe-mobils` | Katalog dan ketersediaan per tanggal | [ADA] | P1 |
| C2 | `POST /bookings/estimate` | Estimasi total dan DP | [ADA] | P1 |
| C3 | `POST /bookings` | Buat pesanan (multi-mobil, dengan/tanpa sopir); tolak jadwal bentrok | [ADA] | P1 |
| C4 | `GET /bookings`, `/bookings/riwayat`, `/bookings/{id}` | Pesanan aktif dan riwayat | [ADA] | P1 |
| C5 | `POST /bookings/{id}/cancel` | Batalkan (hanya status Booked) | [ADA] | P1 |
| C6 | `GET /bookings/{id}/pembayaran`, `/semua-pembayaran`, `POST …/pembayaran/upload` | Lihat tagihan, unggah bukti DP/pelunasan/denda | [ADA] | P1 |

### 4.3 Admin (`/api/admin`)
| ID | Endpoint | Keterangan | Status | Prio |
|---|---|---|---|---|
| M1 | `GET /dashboard` | Jumlah pesanan hari ini, armada per status, pembayaran menunggu verifikasi, tawaran belum dijawab | [BARU] | P1 |
| M2 | `GET /bookings[/{id}]` | Semua pesanan, filter status/tanggal/pencarian | [BARU] | P1 |
| M3 | `POST /bookings/{id}/cancel` | Batalkan pesanan + alasan | [BARU] (logika ada) | P1 |
| M4 | `GET /pembayaran` | Daftar, filter status (menunggu verifikasi) | [BARU] | P1 |
| M5 | `POST /pembayaran/{id}/verifikasi` | Status menunggu → lunas | [BARU] (logika ada) | P1 |
| M6 | `POST /pembayaran/{id}/tolak` | Kembalikan ke belum bayar + alasan | [BARU] (logika ada) | P1 |
| M7 | `POST /pembayaran/{id}/lunas` | Tandai pelunasan diterima langsung (tunai) | [BARU] (logika ada) | P1 |
| M8 | `POST /detail/{id}/penugasan` | Kirim tawaran ke sopir tersedia dan disetujui; hanya detail `pakai_supir = 1` | [BARU] | P1 |
| M9 | `PATCH /detail/{id}/jemput` | Syarat DP lunas; pesanan → Ongoing, mobil → Disewa | [BARU] (logika ada) | P1 |
| M10 | `POST /detail/{id}/pengembalian` | Waktu kembali, kondisi (1 baik/0 rusak), foto; denda Rp50.000/jam keterlambatan; pesanan → Done bila semua unit kembali; mobil → maintenance atau rusak; sopir dibebaskan | [BARU] (logika ada) | P1 |
| M11 | `GET\|POST /detail/{id}/laporan-kondisi` | Laporan sebelum/sesudah oleh admin | [BARU] | P2 |
| M12 | `GET /referensi`, `GET\|POST\|PUT\|DELETE /mobils`, `POST /mobils/{id}/gambar`, `PATCH /mobils/{id}/status` | Armada tanpa approval Super Admin (langsung disetujui) | [BARU] | P2 |
| M13 | `GET\|POST\|PUT /supirs` | Akun sopir, aktif/nonaktif | [BARU] | P2 |
| M14 | `GET\|POST\|PUT /penyewa` | Data penyewa (email wajib; kata sandi acak untuk yang dicatat admin) | [ADA] | P3 |
| M15 | `GET /rekap?bulan=YYYY-MM` | Jumlah pesanan, pendapatan, denda, utilisasi armada | [BARU] | P2 |
| M16 | `POST /bookings` | Pesanan manual atas nama penyewa | [BARU] | P3 |

### 4.4 Sopir (`/api/sopir`)
| ID | Endpoint | Keterangan | Status | Prio |
|---|---|---|---|---|
| S1 | `GET /dashboard` | Ringkasan tugas aktif dan tawaran | [BARU] | P1 |
| S2 | `PATCH /status` | Tersedia/libur; ditolak bila sedang bertugas | [BARU] (logika ada) | P1 |
| S3 | `GET /tawaran` | Tawaran terbuka untuk sopir tersebut | [BARU] (query ada) | P1 |
| S4 | `POST /tawaran/{id}/terima` | Terpasang ke detail; tawaran sopir lain ditutup; status Bertugas; ditolak bila sudah diambil | [BARU] (logika ada) | P1 |
| S5 | `POST /tawaran/{id}/tolak` | Tawaran → ditolak | [BARU] | P1 |
| S6 | `GET /tugas`, `GET /riwayat` | Tugas aktif dan selesai | [BARU] | P1 |
| S7 | `POST /tugas/{id}/laporan-kondisi` | Laporan sebelum/sesudah + foto | [BARU] | P2 |

## 5. Aturan Bisnis

| Aturan | Nilai |
|---|---|
| Ketersediaan mobil | Ditentukan bentrok tanggal, bukan status "dibooking" |
| Lama sewa | Dibulatkan ke atas per 24 jam |
| Uang muka | 50% total |
| Jatuh tempo DP | min(4 hari, mulai sewa − 2 jam), minimal 1 jam; lewat batas → pesanan batal otomatis |
| Pemesanan paling lambat | 3 jam sebelum mulai (`MIN_LEAD_HOURS`) |
| Pembatalan | Hanya saat Booked |
| Jemput | Butuh DP terverifikasi |
| Denda | Rp50.000 per jam terlambat, ditambahkan ke tagihan pelunasan (atau tagihan denda terpisah bila sudah lunas) |
| Pesanan Done | Saat semua detail dikembalikan |
| Sopir menerima tugas | Status 1 (tersedia) dan akun disetujui |
| Metode pembayaran | 1 tunai · 2 transfer · 3 QRIS |

### Status
- Pesanan: 0 Canceled · 1 Booked · 2 Ongoing · 3 Done.
- Pembayaran: 0 belum bayar · 2 menunggu verifikasi · 1 lunas. Jenis: 1 DP · 2 pelunasan.
- Tawaran: 0 terbuka · 1 diterima · 2 ditutup · **3 ditolak (baru)**.
- Sopir: 0 libur · 1 tersedia · 2 bertugas.
- Mobil: 0 rusak · 1 tersedia · 3 disewa · 4 maintenance (2 "dibooking" tidak dipakai).

## 6. Kebutuhan Non-Fungsional

- **Keamanan:** hash kata sandi; token Sanctum berkedaluwarsa; cek peran di setiap grup route; validasi unggahan (jpg/png, ≤ 2 MB, nama acak); akun nonaktif tidak dapat masuk; pembatasan laju login.
- **Integritas:** pembuatan pesanan, penugasan, dan pengembalian dalam transaksi DB; penerimaan tugas aman dari rebutan (kunci baris).
- **Kinerja:** daftar dipaginasi; relasi dimuat dengan `with()` (tanpa N+1); respons < 1 detik pada data uji.
- **Keterbacaan:** pesan galat berbahasa Indonesia, konsisten.
- **Kompatibilitas web:** perubahan skema hanya tambahan; seluruh tes web tetap lulus.

## 7. Perubahan Data

| Perubahan | Alasan |
|---|---|
| Tabel baru `laporan_kondisis` | Laporan kondisi (M11, S7) |
| `job_offers.status` menerima 3 | Sopir menolak |
| `pembayarans.dicatat_oleh`, `pengembalians.dicatat_oleh` (FK users, nullable) | Jejak pencatat |
| `users.aktif` (boolean, default true) | Menonaktifkan akun staf |

## 8. Kriteria Penerimaan

1. Pelanggan, admin, dan sopir dapat login; akses lintas peran menghasilkan 403.
2. Satu pesanan dengan sopir berjalan Booked → Ongoing → Done hanya lewat API.
3. Jadwal bentrok, DP belum lunas saat jemput, dan tawaran yang sudah diambil ditolak dengan pesan jelas.
4. Denda terhitung benar untuk keterlambatan 1 jam, 2 jam, dan tidak terlambat.
5. Semua tes otomatis lulus (termasuk tes web yang sudah ada) dan koleksi Postman lengkap.

## 9. Di Luar Lingkup

Super Admin dan approval; payment gateway; push notification; antarmuka web sebagai bagian TA.
