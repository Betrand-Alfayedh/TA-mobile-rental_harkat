# Daftar Bug & Risiko — Harkat Rental

Hasil audit kode (web Laravel, API, dan aplikasi Flutter). Diurutkan dari paling kritis.
Centang `[x]` setelah diperbaiki.

Label: **[W]** web · **[A]** API · **[M]** mobile

## Urutan pengerjaan

1. Kelompok booking & pembayaran: #6, #7, #8, #10, #11, #21
2. Keamanan dasar: #1, #2, #3, #9
3. Stabilitas mobile: #4, #5, #20, #25
4. Alur staff (supir, pengembalian, approval): #12–#17

---

## Kritis

- [x] **#1 [W]** Upload gambar mobil memakai nama asli file → bisa jadi webshell `.php`. `Admin/MobilController.php:50-53,123-126`, `Admin/PengembalianController.php:65`. Fix: `hashName()` + paksa ekstensi.
- [x] **#2 [W]** SuperAdmin bisa menghapus akun apa saja (termasuk diri sendiri & SuperAdmin lain); `delete()` men-cascade ke seluruh booking & pembayaran. `SuperAdmin/UserController.php:90-93`.
- [x] **#3 [W]** IDOR pembayaran customer: `show`/`uploadBukti` tanpa cek `user_id`; bisa menimpa DP yang sudah lunas. `Customer/PembayaranController.php:11-44`.
- [x] **#4 [M]** Tidak ada penanganan 401 / auto-logout (token dicabut saat login di HP lain). `api_service.dart`.
- [x] **#5 [M]** `AuthProvider.init()` menghapus sesi saat offline/server error; tanpa `try/catch` splash bisa macet. `auth_provider.dart:31-39`.

## Tinggi

- [x] **#6 [W][A]** Booking tanpa cek ketersediaan/overlap, tanpa transaksi DB, status mobil global (mobil yang dibooking bulan depan hilang dari pencarian besok).
- [x] **#7 [W][A]** Tanggal sewa masa lalu / terlalu dekat diterima → `jatuh_tempo` sudah lewat → cron langsung membatalkan.
- [x] **#8 [W]** Booking web: rule `mobils` tidak wajib (booking kosong), tanpa transaksi, email DP tanpa `try/catch` (500 + duplikat).
- [x] **#9 [W][A]** Tidak ada rate limiting: `/login`, `/api/login`, `/api/auth/google/token`.
- [x] **#10 [W][A]** Upload bukti ke booking canceled / lewat jatuh tempo; web bisa menimpa pembayaran yang sudah lunas.
- [x] **#11 [W]** Verifikasi pembayaran tidak idempotent (pelunasan ganda); status "ditolak" = 0 sehingga cron bisa membatalkan booking yang baru ditolak. `Admin/PembayaranController.php`.
- [x] **#12 [W]** `Admin/BookingController` update/show/destroy memakai skema lama (500); route `destroy` tanpa method.
- [x] **#13 [W]** Edit supir tidak pernah diterapkan (`approve()` tidak menerapkan `new_data`); hash password bocor ke tabel `approvals`.
- [x] **#14 [W]** Alur supir: supir tidak kembali ke status 1 setelah pengembalian; `acceptJob` IDOR + race; `konfirmasiJemput` tidak mengubah status detail.
- [x] **#15 [W]** Approval tidak cek status pending, null-deref, rollback pakai snapshot basi, gambar lama sudah terhapus.
- [x] **#16 [W]** Edit mobil menyimpan `status` dari request; `status_approval=2` langsung berlaku pada mobil live.
- [x] **#17 [W]** Pengembalian tanpa validasi alur; pelunasan dibuat dengan `jenis` salah; denda per jam vs kolom `date`.
- [ ] **#18 [W]** Hapus akun customer menghapus data keuangan, mobil terkunci di status 2, akun Google tidak bisa lolos `current_password`.
- [x] **#19 [A]** `/api/*` tanpa token bisa dialihkan 302 alih-alih 401 JSON (`Middleware/Authenticate.php`). Perlu cek runtime. *(7 Okt 2026: `/api/*` selalu 401 JSON, diuji tanpa header Accept)*
- [x] **#20 [M]** State loading macet di `MobilProvider` & `AuthProvider` (tanpa `try/catch`).
- [x] **#21 [M]** Timeout 10 dtk pada `POST /bookings` → booking ganda (email sinkron di server).
- [ ] **#22 [M]** Filter katalog bocor antar layar; race pagination (halaman 2 bisa terlewat). *(Sebagian 7 Okt 2026: race pagination diperbaiki dengan nomor permintaan; filter yang dibagi antar layar belum diubah.)*
- [ ] **#23 [M]** Release build: base URL IP LAN, HTTP cleartext, signing memakai debug key.
- [x] **#24 [M]** Token di SharedPreferences (bukan secure storage); Sanctum tanpa kedaluwarsa. *(6 Okt 2026: token kini di `flutter_secure_storage` dengan migrasi otomatis dari penyimpanan lama; token API kedaluwarsa 7 hari.)*
- [x] **#25 [M]** Update profil gagal 422 bila ada field kosong; `INCOMPLETE_PROFILE` tidak ditangani.
- [ ] **#26 [M]** QRIS hanya ikon placeholder; tidak ada nomor rekening/QR sungguhan.

- [x] **#48 [W][A][M]** Pelunasan tidak bisa diselesaikan lewat aplikasi mana pun: customer (web & API) hanya bisa upload bukti **DP**; admin hanya punya tombol Verifikasi/Tolak bila status pending (ada bukti). Akibatnya pelunasan (sisa 50% + denda) selalu "Belum Bayar" dan booking bisa "Done" tanpa pelunasan. Perlu keputusan: pelunasan tunai oleh admin ("Tandai lunas"), dan/atau upload bukti pelunasan oleh customer.
- [x] **#49 [W]** Tidak ada cara membatalkan booking (customer maupun admin); pembatalan hanya lewat cron `booking:check-dp` (butuh `php artisan schedule:work` / cron `schedule:run` aktif, kalau tidak booking yang lewat jatuh tempo tidak pernah dibatalkan).

- [x] **#50 [W]** `resources/js/echo.js` melempar galat bila kunci Pusher tidak diisi, sehingga seluruh `app.js` batal: **Alpine** (sidebar/dropdown/modal hapus/navigasi supir) dan `booking.js` (slider form booking web) tidak pernah berjalan. Sekarang Echo hanya dibuat bila `VITE_PUSHER_APP_KEY` ada; `booking.js` berhenti lebih awal di halaman non-booking; `layouts/supir` memakai `window.Echo?.channel`.

- [x] **#51 [W]** Supir hanya mengetahui tawaran job lewat popup realtime (Pusher/Echo, `layouts/supir.blade.php`); dashboard supir tidak punya daftar tawaran terbuka. Tanpa kunci `PUSHER_*` di `.env`, popup tidak pernah muncul sehingga supir tidak bisa menerima job. Perlu daftar "Tawaran job" di dashboard (query `JobOffer` status 0 milik supir) sebagai cadangan.

## Sedang

- [x] **#27 [W]** Timezone `UTC` → jam meleset 7 jam (WIB).
- [ ] **#28 [W]** Verifikasi email tidak ditegakkan & alurnya error (`route('dashboard')`); pre-registration account takeover via Google.
- [ ] **#29 [W][A]** `google_id` tidak tersimpan (tidak ada di `$fillable`).
- [ ] **#30 [W]** Link DP di email salah (404/IDOR); link email job supir salah (405).
- [ ] **#31 [W]** `checkMobils` mengembalikan semua mobil (rusak/belum approved) & tidak memfilter status booking.
- [ ] **#32 [W]** Cron `CancelUnpaidBookings` tidak atomik (email tanpa try/catch, tanpa transaksi). *(Sebagian: tidak lagi menimpa status mobil maintenance/rusak.)*
- [x] **#33 [A]** Token tidak dicabut saat ganti/reset password; `/api/register` tanpa route; pelunasan tidak bisa diupload dari API. *(7 Okt 2026: sesi lain dicabut saat ganti sandi; `/api/register` terdaftar; unggah pelunasan lewat API tersedia)*
- [ ] **#34 [W]** Path gambar salah di view (`asset('public/...')`, folder gambar tambahan tidak cocok); kolom `nama_mobil` (sudah di-drop) masih dipakai di PDF/laporan/email.
- [ ] **#35 [W]** Laporan menghitung booking canceled; `DATEDIFF` membuat sewa sehari = 0.
- [ ] **#36 [W]** Hapus Mobil/Master/Tipe berpotensi cascade ke booking; banyak route `destroy` tanpa method.
- [ ] **#37 [W]** `PelangganController` memakai field `nama` yang tidak ada; password default hardcode.
- [ ] **#38 [W]** Supir bisa login sebelum approval (**API sudah ditutup 6 Okt 2026**: login API menolak sopir belum disetujui dan akun nonaktif; login web belum); `UserController` bisa membuat role 3 tanpa baris `supirs`.
- [ ] **#39 [W]** List admin tanpa pagination, banyak N+1.
- [x] **#40 [M]** Logout tidak memanggil `GoogleSignIn.signOut()`; state provider tidak dibersihkan. *(7 Okt 2026: `GoogleSignIn.signOut()` saat logout; provider pelanggan, sopir, dan admin direset)*
- [x] **#41 [M]** Tidak ada validasi waktu lampau; membatalkan time picker diam-diam jadi 08:00.
- [ ] **#42 [M]** Estimasi harga gagal tanpa pesan; tidak menampilkan jumlah hari (dibulatkan 24 jam); context dipakai setelah `await`.
- [x] **#43 [M]** Setelah booking sukses user terjebak di layar tanpa navigasi. *(sudah ada: setelah pesanan sukses diarahkan ke daftar pesanan)*
- [x] **#44 [M]** Upload bukti: izin ditolak tidak tertangani; format/ukuran tidak dicek sebelum upload. *(7 Okt 2026: format JPG/PNG dan ukuran ≤ 5 MB dicek di perangkat; izin kamera/galeri ditangani)*
- [x] **#45 [M]** Pull-to-refresh rusak; error `loadMore` menghapus seluruh daftar. *(7 Okt 2026: gagal muat lanjutan tidak menghapus daftar)*
- [ ] **#46 [M]** Pesan error mentah/Inggris; `ApiResponse.success` mensyaratkan `status==true`.
- [ ] **#47 [M]** Gambar utama tidak tampil bila ada gambar tambahan; grid rawan overflow; banyak endpoint tidak dipakai; "Lupa password?" tombol kosong.

## Rendah

- [ ] **[W]** `RoleMiddleware` melogout lewat GET (sebaiknya 403).
- [ ] **[W]** URL ganda `/superadmin/superadmin/laporan`, `/supir/supir/...`; `laporan/generate` memakai GET dengan efek samping.
- [ ] **[W]** Cache `type` (`rememberForever`) tidak pernah dibersihkan.
- [ ] **[W]** Nama route `login` duplikat (GET & POST).
- [ ] **[W]** `DpReminderNotification` hanya terkirim saat booking dibuat (tidak ada reminder terjadwal).
- [ ] **[W]** Channel broadcast tidak aktif (private channel kemungkinan selalu 403).
- [x] **[W]** Upload ulang bukti tidak menghapus file lama (file yatim). *(7 Okt 2026: sisi API diperbaiki dan diuji)*
- [x] **[A]** `per_page` tidak dibatasi; item `mobil_id` duplikat dalam satu request tidak ditolak; pencarian hanya `master_mobil.nama`, bukan `merk`. *(7 Okt 2026: per_page katalog dibatasi 1–50; duplikat mobil_id sudah ditolak validasi)*
- [x] **[A]** `BookingDetail::$appends` memuat `total_harga_rp` tanpa accessor (cek runtime). *(7 Okt 2026: atribut tanpa accessor dihapus)*
- [ ] **[A]** Harga detail booking dihitung dari harga mobil saat ini, bukan harga saat booking.
- [ ] **[M]** Permission storage berlebih di manifest; ±13 dependency tidak dipakai; tanggal tanpa locale `id`; aksesibilitas; `TextEditingController` di profile tidak di-dispose.
- [ ] **[M]** `Booking::computedStatus` masih memakai `status_detail` (kolom tidak ada).

## Redesain website customer (meniru struktur indorent.co.id, tetap hijau)

Cakupan: **halaman publik/customer** (beranda, armada, detail mobil, header, footer, modal login, halaman pesanan/profil/pembayaran). Panel admin, superadmin, dan supir **belum** disentuh (masih ada emoji di sidebar/label mereka).

- **Arah desain:** hijau hutan gelap (`#0B3B2A`) untuk permukaan besar + hijau `#16A34A` (sama dengan aplikasi mobile) untuk aksi; satu keluarga huruf Plus Jakarta Sans; ikon garis tipis buatan sendiri (`<x-line-icon>`), tanpa emoji/FontAwesome/Lucide tebal; kartu "double-bezel"; tombol pill dengan panah di lingkaran sendiri; navigasi pil melayang, menu mobile layar penuh (hamburger → X); satu animasi masuk (IntersectionObserver, hanya `transform` + `opacity`, mengikuti `prefers-reduced-motion`).
- **Beranda** (`main-content.blade.php`): hero foto serah-terima kunci + ringkasan angka dari data asli (jumlah unit, tarif mulai, DP 50%) → armada dengan tab tipe & geser (tanpa Swiper) → layanan (bento) → 5 langkah sewa → ajakan → lokasi + peta + WhatsApp. Semua teks mencerminkan fitur nyata (DP 50%, lepas kunci/dengan supir, jaminan, invoice email, denda per jam); tidak ada angka karangan.
- **Armada** (`customer/mobil/*`): pencarian langsung + chip tipe tanpa jQuery, keadaan kosong, halaman detail memakai kolom yang benar (sebelumnya `nama_mobil`/`Storage::url` yang sudah tidak ada).
- **Foto mobil** memakai `asset('gambar_mobil/...')` (sebelumnya `asset('public/gambar_mobil/...')` → 404 bila docroot = `public/`); unit tanpa foto menampilkan tampilan pengganti (`<x-car-image>`).
- **Dihapus:** loading screen 1,5 dtk (juga memanggil elemen yang tidak ada), AOS, Swiper, FontAwesome, tautan footer mati (`/tentang`, `/faq`, `/syarat`, `/privasi`, `/kontak`).
- Komponen baru: `x-line-icon` (bukan `x-icon`, karena paket `blade-ui-kit/blade-icons` sudah memakai nama itu), `x-car-image`. Token warna/animasi di `tailwind.config.js`, kelas `.btn*`, `.bezel*`, `.field`, `.reveal` di `resources/css/app.css`.
- Tes: `tests/Feature/PublicPagesTest.php` (halaman publik + halaman customer tidak memuat emoji, filter/pencarian katalog, keadaan armada kosong).
- Catatan: `public/hot` ada → Vite dev server (`npm run dev`) sedang dipakai; untuk produksi jalankan `npm run build`.
- Sebagian #34: path foto mobil di halaman **customer** sudah benar; view admin/PDF/laporan belum.

**Lanjutan: halaman Booking, Pesanan, Riwayat, Pembayaran**
- Pola baru: pita judul hijau gelap (`<x-page-head>`, dipanggil lewat `@section('pagehead')`) yang ditimpa kartu bezel.
- **Form booking** (`customer/booking/create.blade.php`, `resources/js/booking.js` ditulis ulang): dua kolom dengan *ringkasan biaya* yang menempel saat scroll (jumlah mobil, total, DP 50%, sisa), kartu per mobil dengan geser/penanda, tombol *Pakai supir* berbentuk saklar, info harga per mobil, tanggal mulai minimal 3 jam dari sekarang, pesan galat bila isian mobil lain kosong akan menggeser ke mobil itu, tombol dikunci saat dikirim.
- **Tombol "Cek mobil tersedia" sebelumnya tidak punya fungsi sama sekali** (endpoint `booking.checkMobils` ada, tetapi JS-nya tidak ada). Sekarang jalan otomatis saat tanggal diisi: opsi mobil yang bentrok dinonaktifkan, dan mobil yang sudah dipilih di baris lain tidak bisa dipilih ganda.
- **Pesanan & Riwayat**: tabel diganti kartu per booking (`partials/booking-card.blade.php`) berisi mobil, tanggal, status, total, DP, pelunasan, dan aksi (Bayar DP / menunggu verifikasi / DP lunas / batalkan). Halaman Riwayat sebelumnya memakai kolom `nama_mobil` yang sudah di-drop (nama mobil kosong) dan `pembayaranDp->jatuh_tempo` tanpa pengaman null.
- **Pembayaran DP**: jumlah besar, tab Transfer bank (salin nomor rekening) / QRIS (`images/qris.jpg`), unggah bukti dengan pratinjau + cek jenis/ukuran di sisi klien; status menunggu/lunas/ditolak/booking batal ditampilkan sesuai `Pembayaran::alasanTidakBisaUpload()`.
- **Tailwind sekarang memindai `resources/js/**/*.js`** (`tailwind.config.js`); sebelumnya kelas yang hanya dipakai di JS (mis. `invisible`) tidak pernah dibuat.
- Tes tambahan di `PublicPagesTest` (data booking asli, status pembayaran, dan keberadaan semua elemen yang dicari `booking.js`).
- Belum didesain ulang: halaman profil (`profile/index`, `profile/edit`) dan pendaftaran (`auth/cust-reg`), serta panel admin/superadmin/supir.

## Catatan perbaikan #48 (pelunasan) & #49 (pembatalan)

**Pelunasan (#48)**
- Admin: tombol **Tandai Lunas** (pilih Cash/Transfer/QRIS) di *Manajemen Pembayaran* untuk pembayaran yang belum dibayar (DP maupun pelunasan), mis. tunai saat jemput/kembali. Pelunasan baru bisa ditandai lunas setelah DP lunas; booking batal ditolak. DP yang ditandai lunas otomatis membuat tagihan pelunasan (tidak ganda) dan mengirim invoice. (`Admin/PembayaranController::tandaiLunas` + `selesaikan()`, dipakai juga oleh `verifikasi`.)
- API: `POST /api/bookings/{id}/pembayaran/upload` menerima `pembayaran_id` (opsional; kosong = DP) sehingga customer bisa mengunggah bukti **pelunasan/denda**. Aturan unggah per jenis di `Pembayaran::alasanTidakBisaUpload()`: DP butuh booking aktif + belum lewat jatuh tempo; pelunasan butuh DP lunas + booking tidak batal, dan boleh terlambat.
- `GET /api/bookings` kini memuat `pelunasan` dan juga booking **Done yang pelunasannya belum lunas** (supaya masih bisa dibayar dari tab Booking); hilang setelah lunas.
- Mobile: kartu booking menampilkan jumlah pelunasan + tombol **Bayar Pelunasan** (membuka `PaymentScreen` untuk tagihan itu) atau status "menunggu verifikasi admin".
- Belum tercakup: unggah bukti pelunasan lewat **web customer** (halaman web customer masih hanya DP; admin tetap bisa Tandai Lunas); tagihan **denda terpisah** (dibuat bila pelunasan sudah lunas saat pengembalian) belum ditampilkan di mobile, tetapi admin bisa menandainya lunas.

**Pembatalan (#49)**
- `BookingService::cancel()`: transaksi + lock; semua unit dibatalkan, tawaran job supir ditutup, supir dibebaskan (`BookingDetail::bebaskanSupir()`), customer diberi email (alasan dinamis di `BookingCanceledNotification`). Hanya booking berstatus **Booked**.
- Customer (web & mobile `POST /api/bookings/{id}/cancel`): hanya selama **DP belum lunas** (`Booking::canBeCanceledByCustomer()`); setelah DP lunas harus menghubungi admin (kebijakan refund belum didefinisikan).
- Admin: tombol **Batalkan Booking** (alasan opsional) di *Manajemen Penyewaan*; bisa membatalkan walau DP sudah lunas (refund diurus manual). Booking On going tidak bisa dibatalkan (harus lewat pengembalian).
- Cron `booking:check-dp` kini memakai `cancel()` yang sama (atomik per booking, satu gagal tidak menghentikan yang lain). **Tetap butuh scheduler aktif**: `php artisan schedule:work` (dev) atau cron `* * * * * php artisan schedule:run` (server).
- Tombol batal di mobile: kartu booking, dengan dialog konfirmasi.

**Regresi yang ikut diperbaiki:** tombol *Konfirmasi Jemput* di admin hanya tampil bila status mobil "dibooking" (angka 2) — status yang tidak lagi ditulis sejak kelompok 1 — sehingga tombol tidak pernah muncul. Sekarang tampil bila unit & booking berstatus Booked.

- Tes: `tests/Feature/CancelAndPelunasanTest.php` (9 kasus) dan `mobile_app/test/booking_cancel_test.dart` (3 kasus).

## Ide / fitur ditunda (belum dikerjakan)

- **Invoice untuk customer.** Saat ini invoice PDF hanya dikirim sebagai lampiran email saat admin memverifikasi pembayaran, dan `.env` masih `MAIL_MAILER=log` (email hanya masuk `storage/logs/laravel.log`, tidak terkirim). Pilihan:
  - A. Isi `.env` dengan SMTP sungguhan (Gmail App Password / Mailtrap) → email benar-benar terkirim. *(usaha kecil)*
  - B. Endpoint `GET /api/bookings/{id}/invoice` (unduh PDF) + tombol "Lihat Invoice" di aplikasi. *(sedang)*
  - C. Tampilkan status "Sudah diverifikasi" lebih jelas di aplikasi. *(kecil)*
  - D. Push notification (Firebase). *(besar, biasanya di luar lingkup TA)*

## Catatan non-kode

- `APP_DEBUG=true`, `APP_ENV=local` di `.env` — ubah sebelum dipasang di server.
- `MAIL_MAILER=log` — email tidak benar-benar terkirim di lingkungan ini.
- `.env` berisi `GOOGLE_CLIENT_SECRET` milik pemilik project — jangan di-commit / dibagikan.

## Catatan perbaikan kelompok 1 (booking & pembayaran)

Cara kerja baru: **ketersediaan mobil ditentukan oleh tanggal booking**, bukan status global "dibooking" (status 2).
Status mobil sekarang hanya kondisi fisik (tersedia / disewa / rusak / maintenance).

- Logika booking di satu tempat: `app/Services/BookingService.php` (dipakai API, web customer, web admin).
  Transaksi DB + `lockForUpdate` pada baris mobil → dua booking bersamaan tidak bisa lolos dua-duanya.
- `Mobil::isAvailableFor()` dan scope `Mobil::freeBetween()` (cek bentrok tanggal, status, approval).
- Validasi: sewa minimal 3 jam dari sekarang (customer), kembali harus setelah sewa, mobil tidak boleh dobel.
  Konstanta `BookingService::MIN_LEAD_HOURS` (sama dengan `_minLeadHours` di `booking_form_screen.dart`).
- Email DP dikirim lewat `defer()` + `try/catch` (tidak menggagalkan booking, tidak membuat klien timeout).
- Upload bukti: `Pembayaran::alasanTidakBisaUpload()` (booking harus aktif, belum lunas, belum lewat jatuh tempo).
- Admin verifikasi/tolak: hanya status pending, transaksi + `firstOrCreate` pelunasan (tidak bisa ganda).
  Tolak → status kembali 0, bukti dihapus, jatuh tempo diperpanjang (tidak memendekkan) agar cron tidak langsung membatalkan.
- `jatuh_tempo` booking walk-in (admin) minimal 1 jam dari sekarang.
- Timezone aplikasi: `Asia/Jakarta` (`APP_TIMEZONE`). `PembayaranResource` mengirim tanggal sebagai `Y-m-d H:i:s` (bukan ISO `Z`).
- Flutter: timeout `createBooking` 30 dtk, validasi tanggal di form, pesan error validasi server ditampilkan.
- Tes: `tests/Feature/BookingAvailabilityTest.php` (memakai database MySQL terpisah `db_rentcar_test`, lihat `phpunit.xml`).

Perubahan pendukung yang ikut terkena:
- `Admin/BookingController::konfirmasiJemput`: guard tidak lagi mensyaratkan status mobil "dibooking".
- `Admin/DashboardController`: "mobil disewa" memakai status 3 (sebelumnya 2).
- `CancelUnpaidBookings`: hanya mereset mobil yang berstatus "dibooking".
- `resources/views/emails/invoice.blade.php`: route `customer.bookings.show` tidak ada → email invoice selalu gagal. Diganti `booking.index`.

## Catatan perbaikan kelompok 2 (keamanan dasar)

- **#1** Upload memakai `hashName()` (nama acak, ekstensi ditebak dari *isi* file, bukan nama dari klien) di `Admin/MobilController` (gambar utama & tambahan) dan `Admin/PengembalianController`. File `shell.php` berisi kode PHP kini tersimpan sebagai `.png`. Upload supir & bukti bayar sudah memakai `store()` (nama acak).
- **#2** `SuperAdmin/UserController`: `edit/update/destroy` menolak (403) akun SuperAdmin termasuk diri sendiri; `destroy` menolak user yang punya booking (mencegah cascade menghapus riwayat keuangan).
- **#3** `Customer/PembayaranController`: `show` & `uploadBukti` hanya untuk pemilik booking (404 untuk orang lain).
- **#9** Rate limiter `login` (5x/menit per email+IP) di `POST /login` dan `POST /api/login`, `google-login` (10x/menit per IP) di `POST /api/auth/google/token` (`AppServiceProvider`, `routes/web.php`, `routes/api.php`). Respons 429 berbentuk JSON untuk API, error form untuk web.
- Tes: `tests/Feature/SecurityTest.php`.
- Belum tercakup: `forgot-password` dan `register` belum diberi throttle (lihat #28 / catatan rendah). Supir baru yang login sebelum approval masih di #38.

## Catatan perbaikan kelompok 3 (stabilitas mobile)

- **#4** `ApiService._parse`: 401 pada request yang membawa token memanggil `ApiService.onUnauthorized`. `AuthProvider` meresponsnya dengan logout otomatis + flag `sessionExpired`; `_SessionGuard` di `main.dart` mengarahkan ke layar login dengan snackbar "Sesi Anda telah berakhir". 401 saat login (token Google tidak valid, dll.) tidak ikut memicu.
- **#5** `AuthProvider.init()`: dibungkus `try/catch`; token dihapus **hanya** bila server menjawab 401. Server error / offline / respons rusak → sesi dipulihkan dari profil lengkap yang disimpan lokal (`StorageService.saveUserJson`). Splash tidak bisa macet.
- **#20** `try/catch` di `MobilProvider` (tipe, daftar, detail, ketersediaan), `BookingProvider` (detail, estimasi), `AuthProvider` (login, update profil, refresh). State loading selalu kembali ke loaded/error.
- **#25** Flutter mengirim hanya field profil yang terisi; server (`AuthController::updateProfile`) mengabaikan field kosong (tidak mengosongkan data). `ApiResponse.code` membawa kode server; `ensureProfileComplete()` (`core/utils/profile_guard.dart`) mengarahkan user ke Profil (mode edit) sebelum membuka form booking, dan menangani `INCOMPLETE_PROFILE` dari server.
- Pesan error validasi server kini ditampilkan (`ApiResponse.displayMessage`) di login, profil, booking, upload bukti.
- Tes: `mobile_app/test/session_test.dart` (6 kasus: boot dengan server error/offline/401/respons rusak, 401 saat login, 422 login).
- Belum tercakup: logout tidak memanggil `GoogleSignIn.signOut()` dan data provider tidak dibersihkan saat sesi mati (#40); token masih di SharedPreferences (#24).

## Catatan perbaikan kelompok 4 (alur staff)

Prinsip baru: **perubahan data mobil/supir hanyalah usulan** sampai SuperAdmin menyetujui; data live tidak berubah (dan mobil tidak hilang dari listing) selama menunggu.

- **#12** `Admin/BookingController`: `show/edit/update/prepareTransaksiData` dihapus (memakai skema lama dan tidak dipanggil dari view mana pun); route `booking` dibatasi `index/create/store` (sebelumnya `destroy` dst. memanggil method yang tidak ada → 500).
- **#13** Edit supir kini benar-benar diterapkan saat approve (`ApprovalController::applyChanges`: nama, email, no HP, alamat, password, status, foto; foto lama dihapus). Hash password tidak ditampilkan di daftar approval.
- **#14**
  - `acceptJob`: offer harus milik supir yang login & masih terbuka; transaksi + `lockForUpdate`; hanya satu supir yang menang, tawaran supir lain ditutup (status 2); booking harus masih aktif; supir harus tersedia & ber-approval.
  - `updateStatus`: supir yang sedang bertugas tidak bisa mengganti statusnya.
  - Supir otomatis tersedia lagi saat pengembalian dicatat (bila tidak ada job aktif lain).
  - `konfirmasiJemput`: mengubah status *detail* (observer menyesuaikan booking), butuh DP terverifikasi, guard null mobil/booking, tidak bisa dijemput dua kali.
- **#15** Approve/reject hanya untuk status pending, guard data terkait terhapus, dalam transaksi. Rollback `old_data` dihapus (tidak perlu: data live tidak pernah diubah). Reject usulan membuang file baru yang tidak terpakai. Route + method `approvals.show` dihapus (tidak ada view-nya, selalu 500, tidak ada link).
- **#16** `Admin/MobilController::update`: hanya field tervalidasi masuk usulan (`status` tidak bisa diselundupkan); mobil tidak lagi diset `status_approval=2` saat diedit.
- **#17** `PengembalianController::store`: hanya unit ber-status ongoing; tanggal kembali ≥ tanggal sewa; denda per jam yang dimulai (`Pengembalian::DENDA_PER_JAM`); denda ditambahkan ke pelunasan yang belum lunas, atau dibuat tagihan denda terpisah (`jenis=2`, sebelumnya `jenis` default 1 dan kolom `status` yang salah); rusak → status mobil Rusak, baik → Maintenance; error ditampilkan ramah (bukan 500) dan file yang terlanjur diupload dihapus. Migration `2026_10_02_000001_...`: kolom `tanggal_kembali_aktual` menjadi datetime (jam tidak lagi hilang).
- Tes: `tests/Feature/StaffFlowTest.php` (5 kasus, 46 assertion).
- Belum tercakup (tetap di daftar): #38 (supir login sebelum approval), #36 (FK cascade), #34 (path gambar & `nama_mobil`), `assignJobSupir` masih mengirim ke semua supir tersedia (termasuk belum approved) dan menimpa `pakai_supir` jadi 2.

## Catatan #51: dashboard supir tanpa Pusher + tautan Portal supir

- `supir/dashboard` menampilkan **Tawaran job** (query `JobOffer` status 0 milik supir, unit belum punya supir, booking masih aktif) dengan tombol *Terima job* (memakai `supir.acceptJob` yang sudah aman dari race). Tombol dinonaktifkan dan diberi penjelasan bila supir sedang bertugas / tidak tersedia. Tanpa kunci Pusher, halaman menyegarkan diri tiap 30 detik (hanya saat tab terlihat).
- Dashboard memperbaiki bug lama: `is_available` & `nama_mobil` (kolom tidak ada), `{{ $job->mobil }}` mencetak JSON model, job batal ikut dihitung aktif. Sekarang memakai `masterMobil->nama`, tanggal terformat, nama penyewa; emoji panel supir dihapus; tautan sidebar/bottom-nav yang kosong diarahkan ke bagian dashboard.
- `layouts/supir`: Echo dibuat hanya bila `PUSHER_APP_KEY` ada (sebelumnya `new Echo()` melempar galat); popup realtime tetap berfungsi bila Pusher dikonfigurasi. `JobAssigned` memakai nama mobil & tanggal yang benar.
- `config/broadcasting.php`: default kini mengikuti `BROADCAST_CONNECTION` (Laravel 11+) dengan cadangan `BROADCAST_DRIVER`, lalu `log`. Sebelumnya `.env` Anda (`BROADCAST_CONNECTION=log`) diabaikan dan event job mencoba Pusher tanpa kunci.
- Saklar status supir (topbar): bulatan kini ikut bergeser saat aktif (sebelumnya diam di kiri).
- Header desktop situs publik: tautan **Portal supir** untuk tamu (`/login/supir`), selain di menu ponsel dan footer.
- Tes: tambahan di `StaffFlowTest` (tawaran tampil & bisa diterima, supir libur tidak bisa menerima, booking batal tidak tampil) dan `PublicPagesTest` (tautan Portal supir).

## Tes bawaan Breeze usang (sudah gagal sebelum audit)

`php artisan test` → `Auth/*`, `ProfileTest` gagal karena mengasumsikan `/login` langsung 200 dan route `dashboard` yang tidak ada (aplikasi sudah dikustomisasi). Tidak terkait perbaikan di atas. Tes baru kita (`BookingAvailabilityTest`, `SecurityTest`) lulus. Sebaiknya tes bawaan disesuaikan atau dihapus.

## Sudah diperbaiki sebelum audit

- [x] Vite manifest tidak ditemukan (`vite.config.js`)
- [x] Edit profil web: `password` NULL (`ProfileController::update`)
- [x] URL gambar mobil di API (`MobilResource`)
- [x] Harga detail booking di API = 0 (`BookingDetailResource`)
- [x] Nominal uang di API berupa string → crash Flutter (`BookingResource`, `PembayaranResource`)
- [x] Tombol booking berputar terus (try/catch di `BookingProvider`)
- [x] `BookingDetailObserver` memakai kolom `status_detail` yang tidak ada
- [x] `DialogTheme` → `DialogThemeData` (Flutter 3.38)
- [x] Warning `ListTile` di dalam `DecoratedBox`
- [x] Ikon emoji di halaman login
- [x] Tabel `personal_access_tokens` (migration Sanctum)
- [x] Seeder data demo (`DatabaseSeeder`)

## Fase 1 API staf (6 Okt 2026)
- [x] `assignJobSupir` hanya menawarkan ke sopir tersedia, disetujui, dan akun aktif; tidak lagi menimpa `pakai_supir` jadi 2 (status "menunggu" kini dibaca dari tawaran terbuka); sopir yang sudah menolak tidak ditawari ulang.
- [x] Logika pembayaran, penugasan, jemput, dan pengembalian dipindah ke `app/Services/*` (dipakai web dan API).
- [x] `POST /api/register` didaftarkan di route (sebelumnya hanya ada di controller). Login API menerima admin/sopir/pelanggan; token kedaluwarsa 7 hari.
- [x] Route `/api` selalu menjawab JSON (401/403), tidak pernah redirect.
- Tes baru: `tests/Feature/StaffApiAuthTest.php` (14 tes).

## Fase 2 API admin (6 Okt 2026)
- [x] 11 endpoint `/api/admin/*`: dashboard, pesanan (daftar dengan filter + detail + batal), pembayaran (daftar, verifikasi, tolak, lunas), per unit (penugasan, jemput, pengembalian).
- Tes baru: `tests/Feature/AdminApiTest.php` (8 tes). Koleksi Postman: `docs/postman/`.

## Fase 3 API sopir, master, rekap (6 Okt 2026)
- [x] 27 endpoint baru: sopir (dashboard, status, tawaran terima/tolak, tugas, riwayat, laporan kondisi) dan admin (laporan kondisi, referensi, armada, sopir, penyewa, rekap).
- [x] **#35 sisi API:** `RekapService` tidak menghitung pesanan dibatalkan dan memakai `lama_sewa` (sewa sehari = 1, bukan 0); pendapatan dari pembayaran yang benar-benar lunas. Laporan PDF di web (`LaporanController`) belum diperbaiki.
- [x] Pembatas peran dijalankan sebelum pengikatan model (id yang tidak ada tidak lagi mengungkap 404 ke non-admin).
- [x] Token sopir yang sudah terbit tidak berlaku lagi bila persetujuan dicabut.
- Tes baru: `SopirApiTest` (8), `AdminMasterApiTest` (10). Koleksi Postman lengkap di `docs/postman/`.

## Fase 4 Flutter: fondasi dan sopir (6 Okt 2026)
- [x] **#24:** token di penyimpanan aman (Android Keystore); token lama dimigrasikan dan salinan tidak aman dihapus; keystore rusak tidak membuat crash.
- [x] Pemilihan layar per peran (`shellFor`); admin dan sopir tidak lagi tersasar ke layar pelanggan.
- [x] Layar sopir: beranda, tawaran (terima/tolak dengan konfirmasi), tugas, riwayat, laporan kondisi (foto dikompres), status.
- [x] Data sopir dibuang saat logout/sesi berakhir (`ChangeNotifierProxyProvider`). Provider pelanggan (`MobilProvider`, `BookingProvider`) masih menyimpan data lama antar login (#40, belum).
- Tes: `storage_test`, `sopir_model_test`, `sopir_provider_test`, `sopir_screens_test`; `session_test` dan `booking_cancel_test` disesuaikan dengan penyimpanan aman.
- Belum: uji di perangkat/emulator; layar admin (Fase 5).

## Fase 5 Flutter: layar admin (6 Okt 2026)
- [x] Layar admin lengkap (lihat PLANNING Fase 5). Respons berhalaman memakai nomor generasi: hasil permintaan lama (filter berganti, pindah pesanan, logout) tidak menimpa data terbaru; ada tes untuk tiap kasus.
- [x] **Bug ditemukan tes widget dan diperbaiki:** `ListTile` di dalam kotak berlatar tanpa `Material` (bagian Pembayaran dan Rekap akan menjadi layar galat merah di debug). Komponen `BagianKartu` kini berbasis `Material`.
- [x] **Bug ditemukan tes model dan diperbaiki:** kolom desimal dari MySQL datang sebagai string ("500000.00") dan terbaca 0 oleh `asInt`. Kini terbaca benar; `MobilModel` (dipakai pelanggan juga) ikut tahan angka string.
- [x] Pratinjau denda di form pengembalian memakai aturan server (per jam yang sudah dimulai, Rp50.000); angka akhir tetap dihitung server.
- Belum: uji di perangkat/emulator; `MobilProvider`/`BookingProvider` pelanggan masih menyimpan data antar login (#40).

## Fase 6 Pengujian dan perbaikan (7 Okt 2026)
- [x] **Bug produksi ditemukan tes:** kolom `pembayarans.keterangan` dipakai tagihan denda tetapi tidak pernah dimigrasikan, sehingga pengembalian terlambat pada pesanan yang pelunasannya sudah lunas gagal 500 (juga di alur web). Migrasi `2026_10_07_000001_add_keterangan_to_pembayarans_table`.
- [x] File bukti yatim saat unggah ulang (API), `per_page` tak terbatas, token tidak dicabut saat ganti sandi, atribut `total_harga_rp` tanpa accessor.
- [x] Mobile: gagal muat lanjutan katalog tidak menghapus daftar; permintaan basi dibuang (nomor generasi); provider pelanggan direset saat logout; logout Google; validasi bukti di perangkat.
- Tes baru: `ApiHardeningTest` (7), `BookingApiRulesTest` (14), `PembayaranUploadApiTest` (8), `PengembalianRulesTest` (8), `customer_fixes_test` (Flutter, 11).
- Total tes otomatis: **107 PHPUnit (872 assertion) + 145 Flutter**. Katalog 132 kasus black box dan pemeriksa bukti: lihat `docs/PENGUJIAN.md`.
- Tetap terbuka (di luar lingkup TA atau berisiko): butir web (#18, #28–#32, #34, #36–#39, dll.), #23 (konfigurasi rilis), #26 (QRIS placeholder), #42, #46, #47, laporan PDF web (#35 sisi web).

