# Bahan Bab IV dan Bab V

Draf ini memuat angka dan fakta yang **bersumber dari sistem dan pengujian yang benar-benar dijalankan**. Bagian bertanda **[sesuaikan]** harus dicocokkan dengan rumusan masalah dan tujuan final di dokumen TA setelah Bab I–II disesuaikan ke arah tiga peran (pelanggan, admin, sopir). Tangkapan layar belum ada dan perlu diambil dari aplikasi.

Rincian pengujian: [PENGUJIAN.md](PENGUJIAN.md) dan [Pengujian_BlackBox.xlsx](Pengujian_BlackBox.xlsx).

---

## BAB IV HASIL DAN PEMBAHASAN

### 4.1 Implementasi Sistem

#### 4.1.1 Lingkungan Pengembangan

| Komponen | Teknologi |
|---|---|
| Aplikasi mobile | Flutter (Dart), platform Android, pengelolaan state dengan Provider |
| Back end | PHP, Laravel 12, RESTful API |
| Autentikasi | Laravel Sanctum (token dengan masa berlaku 7 hari dan kemampuan sesuai peran) |
| Basis data | MySQL |
| Pengujian | PHPUnit, Flutter test, Postman |

#### 4.1.2 Arsitektur Sistem

```
Aplikasi Flutter (Pelanggan | Admin | Sopir)
        │  HTTPS/HTTP, JSON, Bearer token
        ▼
RESTful API Laravel
   routes/api.php → middleware (autentikasi, peran) → controller (tipis)
        → service (aturan bisnis, transaksi) → model Eloquent
        ▼
MySQL
```

Controller hanya memvalidasi masukan dan membentuk respons. Aturan bisnis (hitung biaya, cek bentrok jadwal, verifikasi pembayaran, penugasan sopir, jemput, pengembalian dan denda, rekap) berada di tujuh kelas layanan (`app/Services`). Perubahan yang melibatkan beberapa tabel dijalankan dalam transaksi basis data.

#### 4.1.3 Implementasi RESTful API

API menyediakan **58 endpoint**: 20 untuk autentikasi, katalog, dan pelanggan; 30 untuk admin; dan 8 untuk sopir. Pembatasan hak akses dilakukan di setiap kelompok route berdasarkan peran, dan dibaca dari basis data pada setiap permintaan sehingga penonaktifan akun atau pencabutan persetujuan sopir langsung berlaku.

| Kelompok | Fungsi utama |
|---|---|
| Autentikasi dan profil | Daftar, login, logout, profil, ganti kata sandi (sesi lain dicabut) |
| Pelanggan | Katalog dan ketersediaan, estimasi biaya, pemesanan, unggah bukti pembayaran, pembatalan, riwayat |
| Admin (30) | Dashboard; daftar dan detail pesanan; verifikasi, penolakan, dan pelunasan pembayaran; penugasan sopir; konfirmasi jemput; laporan kondisi; pengembalian dan denda; pembatalan; CRUD armada, sopir, penyewa; rekap bulanan |
| Sopir (8) | Status tersedia/libur; tawaran (terima atau tolak); tugas aktif dan riwayat; laporan kondisi dengan foto |

Aturan bisnis utama yang diimplementasikan:

1. Ketersediaan mobil ditentukan oleh **bentrok tanggal**, bukan status global, sehingga mobil yang dipesan untuk periode lain tetap dapat disewa.
2. Biaya = tarif × jumlah hari, dengan lama sewa **dibulatkan ke atas per 24 jam**; uang muka 50%.
3. Pemesanan minimal **3 jam** sebelum waktu sewa; batas pembayaran DP paling lama 4 hari atau 2 jam sebelum sewa, dan pesanan yang DP-nya lewat batas dibatalkan otomatis oleh penjadwal.
4. Pengambilan kendaraan (jemput) hanya dapat dilakukan setelah DP diverifikasi.
5. Denda keterlambatan **Rp50.000 per jam yang sudah dimulai**, ditambahkan ke tagihan pelunasan.
6. Tawaran tugas dikirim ke seluruh sopir yang tersedia, disetujui, dan aktif; **hanya satu sopir yang dapat menerima** (penguncian baris basis data), sopir yang menolak tidak ditawari ulang untuk unit yang sama.
7. Pesanan berstatus **Done** setelah seluruh unit dikembalikan.

#### 4.1.4 Implementasi Aplikasi Mobile

Satu aplikasi melayani tiga peran. Setelah login, aplikasi menampilkan antarmuka sesuai peran pengguna.

| Peran | Layar utama |
|---|---|
| Pelanggan | Beranda, katalog dan detail mobil, form pemesanan dengan estimasi biaya, daftar pesanan, pembayaran (unggah bukti), riwayat, profil |
| Admin | Beranda (hal yang perlu ditindak), pesanan (cari, filter, detail dengan seluruh aksi), pembayaran (lembar bukti, verifikasi/tolak), armada, sopir, penyewa, rekap bulanan, profil |
| Sopir | Beranda (status ketersediaan, ringkasan), tawaran (terima/tolak), tugas aktif (laporan kondisi), riwayat, profil |

Beberapa keputusan perancangan pada sisi aplikasi:

- Token disimpan di penyimpanan aman perangkat (Android Keystore), bukan penyimpanan biasa.
- Aksi yang tidak dapat dibatalkan (verifikasi, jemput, pengembalian, pembatalan) selalu meminta konfirmasi dengan ringkasan.
- Tombol dikunci selama permintaan berjalan sehingga klik ganda tidak menggandakan aksi.
- Pembaruan data memakai penyegaran berkala 30 detik saat aplikasi terlihat dan tarik-untuk-segarkan, bukan koneksi realtime.
- Respons yang datang terlambat (setelah filter berganti atau pengguna keluar) dibuang agar data tidak bercampur.
- Sopir tidak pernah menerima data keuangan (harga, DP, total) dari API.

**[Sisipkan tangkapan layar: login; katalog; form pemesanan; pembayaran; beranda admin; detail pesanan admin; lembar verifikasi; tawaran sopir; laporan kondisi; pengembalian dengan pratinjau denda; rekap.]**

### 4.2 Pengujian Sistem

#### 4.2.1 Metode

Pengujian memakai **black box testing** dengan teknik **partisi kesetaraan** dan **analisis nilai batas**. Pengujian dilakukan secara otomatis pada API (PHPUnit, basis data MySQL uji) dan pada aplikasi (Flutter test), serta secara manual di perangkat untuk alur utama.

| Cara uji | Jumlah | Keterangan |
|---|---:|---|
| PHPUnit (API) | 107 tes | 872 assertion |
| Flutter test (aplikasi) | 145 tes | unit, provider dengan server palsu, dan widget |
| Skenario manual | 25 langkah | alur pelanggan → admin → sopir, tanpa sopir dan dengan sopir |
| Kasus uji black box | 132 kasus | terpetakan ke tes otomatis atau skenario manual |

#### 4.2.2 Hasil Pengujian

Tabel kasus uji lengkap (ID, masukan, hasil yang diharapkan, bukti, hasil aktual) terdapat pada Lampiran (lihat `Pengujian_BlackBox.xlsx`). Ringkasan per modul:

| Modul | Kasus | Hasil |
|---|---:|---|
| Autentikasi | 21 | Sesuai |
| Hak akses | 9 | Sesuai |
| Pemesanan | 18 | Sesuai |
| Pembayaran | 11 | Sesuai |
| Pembatalan | 5 | Sesuai |
| Admin (pesanan dan pembayaran) | 10 | Sesuai |
| Penugasan sopir | 10 | Sesuai |
| Jemput | 4 | Sesuai |
| Laporan kondisi | 8 | Sesuai |
| Pengembalian | 10 | Sesuai |
| Armada | 6 | Sesuai |
| Sopir (admin) | 5 | Sesuai |
| Penyewa | 3 | Sesuai |
| Rekap | 2 | Sesuai |
| Ketahanan aplikasi mobile | 10 | Sesuai |
| **Total** | **132** | **Seluruhnya sesuai** |

Skenario manual dijalankan pada tanggal 7 Oktober 2026 dengan aplikasi pada perangkat yang terhubung ke server lokal. Seluruh langkah memberikan hasil sesuai harapan, termasuk jalur tanpa sopir (A1–A9), jalur dengan sopir (B1–B9), cabang penolakan dan penguncian (C1–C3), serta lanjutan sampai pengembalian dan pelunasan (D1–D4).

#### 4.2.3 Temuan Selama Pengujian

Pengujian menemukan **14 temuan** yang seluruhnya telah diperbaiki. Temuan penting:

| No | Temuan | Perbaikan |
|---:|---|---|
| 1 | Kolom `keterangan` pada tabel pembayaran tidak pernah dibuatkan migrasinya, sehingga pengembalian terlambat pada pesanan yang pelunasannya sudah lunas gagal dengan galat 500 | Migrasi kolom |
| 2 | Pembatas peran berjalan setelah pengikatan model sehingga non-admin dapat membedakan id yang ada dan tidak ada (404 vs 403) | Prioritas middleware peran |
| 3 | Penugasan sopir menawarkan ke sopir yang belum disetujui dan menimpa jenis sewa | Layanan penugasan dengan penyaringan sopir |
| 4 | Angka desimal dari basis data (`"500000.00"`) terbaca 0 oleh aplikasi | Parser angka toleran |
| 5 | Respons lama dapat menimpa data terbaru (filter berganti, ganti akun) | Nomor generasi permintaan |
| 6 | Unggah ulang bukti meninggalkan file lama di server | Penghapusan file lama |
| 7 | Token tidak dicabut saat kata sandi diganti | Sesi lain dicabut |

Daftar lengkap ada pada `PENGUJIAN.md` bagian 5. Temuan 1, 2, 4, dan 5 ditemukan oleh kasus uji yang ditulis sebelum perbaikannya, bukan oleh pengamatan manual.

### 4.3 Pembahasan

**[sesuaikan dengan rumusan masalah final]**

1. **Aplikasi mobile Flutter untuk pengelolaan operasional rental.** Aplikasi mencakup pemesanan oleh pelanggan, pencatatan dan verifikasi pembayaran, pemantauan status armada, penugasan sopir, pencatatan pengembalian beserta denda keterlambatan, dan pelaporan kondisi kendaraan oleh sopir, seluruhnya dalam satu aplikasi dengan tiga peran. Seluruh fitur tersebut lulus pengujian (4.2.2).
2. **RESTful API Laravel dengan Sanctum dan pembatasan hak akses per peran.** API menyediakan 58 endpoint. Hak akses diuji pada setiap endpoint untuk peran yang tidak berhak (kasus HAK-01 sampai HAK-09), termasuk kemungkinan kebocoran keberadaan data.
3. **Pengurangan kesalahan pencatatan.** Aturan yang sebelumnya bergantung pada ketelitian manusia kini dijalankan sistem: bentrok jadwal ditolak, biaya dan DP dihitung otomatis, denda dihitung per jam, jemput ditolak sebelum DP lunas, dan satu tugas tidak dapat diterima dua sopir.
4. **Koordinasi admin dan sopir.** Penawaran, jawaban, dan status tugas tercatat di sistem dan terlihat oleh kedua pihak setelah penyegaran berkala; laporan kondisi bersama foto menjadi bukti sebelum dan sesudah sewa.

### 4.4 Keterbatasan

1. Pembaruan data memakai penyegaran berkala 30 detik, bukan notifikasi dorong; tawaran atau pembayaran baru dapat terlambat terlihat hingga 30 detik.
2. Pembayaran dicatat melalui bukti unggahan dan diverifikasi admin; belum terhubung ke payment gateway. Invoice surel diuji hanya sampai pencatatan log.
3. Aplikasi diuji untuk Android pada jaringan lokal dengan data contoh; belum diuji beban dan pengguna bersamaan dalam jumlah besar.
4. Rincian harga unit dihitung dari tarif mobil saat ini, bukan tarif saat pemesanan.
5. Pesanan manual oleh admin (pelanggan datang langsung atau lewat WhatsApp) belum tersedia.
6. Sebagian modul web yang berada di luar lingkup TA masih memiliki butir terbuka (`BUGS.md`).

---

## BAB V KESIMPULAN DAN SARAN

### 5.1 Kesimpulan

**[sesuaikan dengan tujuan penelitian final]**

1. Telah dibangun aplikasi mobile berbasis Flutter yang melayani tiga peran: pelanggan memesan dan membayar, admin memverifikasi pembayaran serta mengelola pesanan, armada, sopir, dan penyewa, dan sopir menerima atau menolak tugas serta melaporkan kondisi kendaraan.
2. Telah dibangun RESTful API berbasis Laravel dengan autentikasi Sanctum dan pembatasan hak akses berdasarkan peran, terdiri dari 58 endpoint, yang menghubungkan aplikasi dengan basis data MySQL.
3. Berdasarkan pengujian black box terhadap 132 kasus uji (107 tes otomatis API, 145 tes otomatis aplikasi, dan 25 langkah uji manual), seluruh fungsi berjalan sesuai harapan. Pengujian juga menemukan dan menutup 14 temuan selama pengembangan.
4. Sistem mengurangi risiko kesalahan pencatatan dengan menjalankan aturan bisnis secara otomatis (pemeriksaan bentrok jadwal, perhitungan biaya, uang muka, dan denda) serta memperjelas koordinasi antara admin dan sopir.

### 5.2 Saran

1. Mengganti penyegaran berkala dengan **notifikasi dorong** (Firebase Cloud Messaging) agar tawaran tugas dan bukti pembayaran baru langsung diterima.
2. Mengintegrasikan **payment gateway** atau pencocokan mutasi bank agar verifikasi pembayaran tidak sepenuhnya manual.
3. Menambahkan **pesanan manual oleh admin** untuk pelanggan yang datang langsung atau memesan lewat WhatsApp.
4. Menyimpan **harga saat pemesanan** pada setiap unit sewa agar perubahan tarif tidak memengaruhi pesanan lama.
5. Menyiapkan **rilis produksi**: HTTPS, alamat server tetap, penandatanganan rilis sendiri, dan pengujian beban.
6. Mengembangkan **versi iOS** dan fitur pelacakan lokasi kendaraan bila diperlukan.
