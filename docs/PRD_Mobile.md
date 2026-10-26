# Product Requirements Document (PRD)
## Aplikasi Mobile — Sistem Informasi Manajemen Rental Mobil Harkat

**Versi:** 2.0 · **Tanggal:** 6 Oktober 2026 · **Platform:** Android (Flutter)
**Pengguna:** Pelanggan, Admin, Sopir (satu aplikasi, tiga peran)
**Menggantikan:** PRD v1.0 (khusus pelanggan), diarsipkan di `docs/arsip/`.
Dokumen terkait: [PRD_API.md](PRD_API.md) · [TRD_Mobile.md](TRD_Mobile.md) · [PLANNING.md](PLANNING.md)

---

## 1. Ringkasan

Aplikasi mobile Rental Mobil Harkat Yogyakarta untuk mengelola seluruh siklus sewa: pelanggan memesan dan membayar, admin memverifikasi dan mengelola operasional, sopir menerima tugas dan melaporkan kondisi kendaraan. Aplikasi terhubung ke RESTful API Laravel; tidak ada antarmuka web dalam lingkup.

## 2. Latar Belakang dan Tujuan

Pengelolaan operasional internal (data armada, jadwal sewa, penugasan sopir, pencatatan pengembalian) masih semi-digital dengan spreadsheet sehingga rawan salah catat dan menyulitkan koordinasi admin dan sopir. Pemesanan pelanggan juga perlu masuk langsung ke sistem agar tidak dicatat ulang.

**Tujuan:**
1. Pelanggan memesan mobil sendiri dengan jadwal yang divalidasi dan biaya yang transparan.
2. Admin memverifikasi pembayaran dan mengelola pesanan, armada, serta sopir di satu tempat.
3. Sopir menerima atau menolak tugas dan melaporkan kondisi kendaraan dari ponsel.
4. Seluruh data tersimpan terpusat dan dapat ditelusuri (siapa mencatat apa).

## 3. Pengguna

| Peran | Kode | Konteks | Tujuan utama |
|---|---|---|---|
| Pelanggan | 4 | Wisatawan/warga Yogyakarta | Menyewa mobil dengan cepat |
| Admin | 2 | Kantor Harkat (Sewon, Bantul) | Memproses pesanan tanpa salah catat |
| Sopir | 3 | Lapangan | Tahu tugas dan melapor tanpa telepon |

Android 5.0+ (API 21), bahasa Indonesia. Layar yang tampil ditentukan oleh peran setelah login.

## 4. Alur Pengguna Utama

```
Pelanggan: login → pilih mobil → isi tanggal → estimasi → pesan → unggah bukti DP → pantau status
Admin    : pesanan masuk → verifikasi DP → (dengan sopir) tugaskan → hari jemput: laporan kondisi + konfirmasi jemput
           → mobil kembali: laporan kondisi + catat pengembalian/denda → verifikasi pelunasan
Sopir    : terima tawaran → tugas aktif → laporan kondisi sebelum → (selesai) laporan sesudah
```

## 5. Fitur per Peran

Tanda: **[ADA]** sudah ada di aplikasi · **[BARU]**. Prioritas P1 inti · P2 penting · P3 tambahan.

### 5.1 Umum
| ID | Fitur | Status | Prio |
|---|---|---|---|
| U1 | Splash, pemulihan sesi, validasi token | [ADA] | P1 |
| U2 | Login email/kata sandi; **arahkan ke layar sesuai peran** | [UBAH] | P1 |
| U3 | Daftar akun pelanggan | [BARU di layar] | P1 |
| U4 | Profil, ubah data, ubah kata sandi, keluar | [ADA] | P1 |
| U5 | Token di penyimpanan aman; sesi kedaluwarsa → kembali ke login dengan pesan | [UBAH] | P1 |

### 5.2 Pelanggan (semua [ADA], disesuaikan bila perlu)
| ID | Fitur | Prio |
|---|---|---|
| C1 | Katalog: cari, filter tipe, detail dengan galeri | P1 |
| C2 | Form pesanan multi-mobil, pilihan dengan/tanpa sopir, cek jadwal, estimasi biaya dan DP | P1 |
| C3 | Pesanan aktif dengan status, batalkan (saat Booked) | P1 |
| C4 | Pembayaran: lihat tagihan (DP/pelunasan/denda), unggah bukti dari kamera/galeri | P1 |
| C5 | Riwayat pesanan | P1 |
| C6 | Segarkan status berkala (tarik-untuk-segarkan + polling) | P2 |

### 5.3 Admin [BARU]
| ID | Fitur | Prio |
|---|---|---|
| M1 | **Beranda:** pesanan hari ini, armada per status, pembayaran menunggu verifikasi | P1 |
| M2 | **Pesanan:** daftar dengan filter status dan pencarian; detail pesanan | P1 |
| M3 | **Aksi pesanan:** verifikasi/tolak DP, tandai pelunasan, batalkan dengan alasan | P1 |
| M4 | **Penugasan sopir** untuk unit bersopir; lihat jawaban sopir | P1 |
| M5 | **Konfirmasi jemput** (hanya bila DP lunas) | P1 |
| M6 | **Catat pengembalian:** waktu aktual, kondisi, foto, denda tampil otomatis | P1 |
| M7 | **Laporan kondisi** (sebelum/sesudah) beserta foto | P2 |
| M8 | **Armada:** daftar, tambah/ubah, atur status (tersedia/maintenance/rusak) | P2 |
| M9 | **Sopir:** daftar, tambah/ubah, aktif/nonaktif | P2 |
| M10 | **Rekap bulanan:** pesanan, pendapatan, denda | P2 |
| M11 | Penyewa: daftar dan data | P3 |
| M12 | Pesanan manual atas nama penyewa (datang langsung/WA) | P3 |

### 5.4 Sopir [BARU]
| ID | Fitur | Prio |
|---|---|---|
| S1 | **Beranda:** tugas aktif dan jumlah tawaran | P1 |
| S2 | **Tawaran:** rincian (mobil, tanggal, kota), **terima / tolak** | P1 |
| S3 | **Tugas aktif** dan **riwayat** | P1 |
| S4 | **Status** tersedia/libur (dikunci saat bertugas) | P1 |
| S5 | **Laporan kondisi** sebelum/sesudah + foto | P2 |

## 6. Navigasi

| Peran | Bottom navigation |
|---|---|
| Pelanggan | Beranda · Mobil · Pesanan · Riwayat · Profil |
| Admin | Beranda · Pesanan · Pembayaran · Armada · Lainnya (sopir, penyewa, rekap, profil) |
| Sopir | Beranda · Tawaran · Tugas · Riwayat · Profil |

## 7. Aturan Antarmuka dan Perilaku

- Tombol aksi dinonaktifkan dan menampilkan indikator selama permintaan berjalan; tidak pernah berputar tanpa akhir (selalu ada batas waktu dan pesan galat).
- Aksi yang tidak dapat dibatalkan (verifikasi, jemput, pengembalian, batal) meminta konfirmasi dengan ringkasan.
- Status ditampilkan sebagai lencana berwarna **dan** teks.
- Nominal berformat rupiah (`Rp 1.200.000`), tanggal berformat Indonesia.
- Galat jaringan ditampilkan ramah ("Tidak dapat terhubung ke server"); 401 mengarahkan ke login; 403 menampilkan "Anda tidak berhak mengakses fitur ini".
- Daftar panjang memakai pagination; semua layar daftar memiliki keadaan kosong dan keadaan galat dengan tombol coba lagi.
- Foto: dipilih dari kamera/galeri, ukuran dikompres sebelum unggah (≤ 2 MB).

## 8. Kebutuhan Non-Fungsional

- **Keamanan:** token di `flutter_secure_storage`; tidak ada kata sandi tersimpan; layar sesuai peran dan server tetap memeriksa peran.
- **Kinerja:** daftar tampil < 2 detik pada jaringan lokal; gambar di-cache.
- **Ketahanan:** sesi dipulihkan setelah aplikasi ditutup; permintaan gagal tidak membuat status ganda (tombol terkunci saat proses).
- **Kegunaan:** target sentuh ≥ 44 px, kontras teks memadai, satu aksi utama per layar.

## 9. Kriteria Penerimaan

1. Login tiga akun uji membuka tiga layar utama yang berbeda.
2. Pelanggan memesan mobil, mengunggah bukti DP; admin memverifikasi di aplikasi; status pelanggan berubah setelah disegarkan.
3. Admin menugaskan sopir; sopir menerima di ponselnya; tolak memunculkan kembali kebutuhan penugasan di admin.
4. Jemput ditolak bila DP belum lunas, dengan pesan jelas.
5. Pengembalian terlambat menghasilkan denda yang benar dan tertagih ke pelanggan.
6. Sopir mengirim laporan kondisi dengan foto dan admin dapat melihatnya.
7. Token kedaluwarsa mengembalikan pengguna ke login tanpa crash.

## 10. Di Luar Lingkup

Super Admin; login Google untuk staf; payment gateway; push notification (opsional kemudian); iOS; peta/pelacakan GPS; ulasan dan rating.
