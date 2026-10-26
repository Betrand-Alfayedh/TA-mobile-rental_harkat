# Technical Requirements Document (TRD)
## RESTful API — Sistem Informasi Manajemen Rental Mobil Harkat

**Versi:** 2.0 · **Tanggal:** 6 Oktober 2026 · Pasangan dari [PRD_API.md](PRD_API.md)

---

## 1. Teknologi

| Komponen | Pilihan |
|---|---|
| Bahasa/Framework | PHP, Laravel 12 |
| Autentikasi | Laravel Sanctum (token personal) |
| Basis data | MySQL (pengujian: `db_rentcar_test`, `RefreshDatabase`) |
| Penyimpanan berkas | Disk lokal `public` (foto bukti, kondisi, mobil) |
| Antrian/jadwal | `schedule` (`booking:check-dp` tiap 5 menit) |
| Pengujian | PHPUnit + koleksi Postman |

## 2. Arsitektur

```
Flutter ──HTTPS/JSON──► routes/api.php
                          │ middleware: auth:sanctum → api.role:<peran> → throttle
                          ▼
                    Controller (tipis: validasi, otorisasi, format respons)
                          ▼
                    Service (aturan bisnis, transaksi DB)   ◄── dipakai juga oleh controller web
                          ▼
                    Model Eloquent ──► MySQL
                          ▲
                    Http/Resources (bentuk JSON)
```

Aturan: controller tidak menghitung biaya atau mengubah status; itu tugas Service. Web dan API memanggil Service yang sama sehingga perilakunya tidak bisa berbeda.

### Struktur berkas

```
app/Http/Controllers/Api/            (pelanggan: sudah ada)
app/Http/Controllers/Api/Admin/      BookingApi, PembayaranApi, DetailApi (penugasan, jemput, pengembalian),
                                     DashboardApi, LaporanKondisiApi, MobilApi, SupirApi, PenyewaApi, RekapApi  [ada]
app/Http/Controllers/Api/Sopir/      DashboardApi (+status), TawaranApi, TugasApi (+laporan kondisi)  [ada]
app/Http/Controllers/Api/Concerns/   RespondsJson (ok / fail / page: format respons seragam)
app/Http/Middleware/ApiRole.php      alias `api.role`
app/Http/Requests/                   FormRequest per aksi dengan validasi
app/Http/Resources/                  + AdminBookingResource, AdminDetailResource, AdminPembayaranResource [ada];
                                     LaporanKondisiResource, TawaranResource, SopirTugasResource, SupirResource, PenyewaResource [ada]
app/Services/                        BookingService, PembayaranService, PenugasanService, JemputService,
                                     PengembalianService, LaporanKondisiService, RekapService [ada]
```

## 3. Autentikasi dan Otorisasi

1. `POST /api/login` memeriksa kredensial, lalu: akun `aktif = false` → 403; sopir `status_approval ≠ 1` → 403; peran ∉ {2,3,4} → 403.
2. Token dibuat dengan `createToken('mobile-app', [<peran>])` (kemampuan: `admin`, `sopir`, `customer`) dan **kedaluwarsa 7 hari** (`config/sanctum.php` → `expiration = 10080`).
3. Middleware `api.role:admin` (`ApiRole`; alias `role` yang sudah ada khusus web dan mengarahkan ke halaman login) membandingkan `$request->user()->role` dengan peran yang diizinkan; tidak cocok → 403. Pemeriksaan memakai data pengguna di database, sehingga perubahan peran, penonaktifan, atau pencabutan persetujuan sopir langsung berlaku pada token yang sudah terbit. Middleware ini diprioritaskan **sebelum** pengikatan model (`prependToPriorityList`) agar non-admin selalu mendapat 403, bukan 404 yang membocorkan keberadaan id.
4. Pembatasan laju: `throttle:login` (5/menit, sudah ada).
5. Kepemilikan data: endpoint pelanggan selalu menyaring `user_id = auth()->id()`; endpoint sopir menyaring `supir_id` milik pengguna itu.

```php
// routes/api.php (ringkas)
Route::middleware(['auth:sanctum', 'api.role:admin'])->prefix('admin')->group(/* … */);
Route::middleware(['auth:sanctum', 'api.role:sopir'])->prefix('sopir')->group(/* … */);
Route::middleware(['auth:sanctum', 'api.role:customer'])->group(/* bookings, pembayaran */);
```

## 4. Model Data

### 4.1 Tabel yang dipakai (sudah ada)
`users` (role 2/3/4), `supirs` (`user_id`, `status`, `status_approval`), `tipe_mobils`, `master_mobils`, `mobils`, `mobil_images`, `bookings`, `booking_details` (`supir_id`, `pakai_supir`, `status`), `pembayarans`, `job_offers`, `pengembalians`.

### 4.2 Migrasi baru (hanya tambahan)

```php
// create_laporan_kondisis_table
Schema::create('laporan_kondisis', function (Blueprint $t) {
    $t->id();
    $t->foreignId('booking_detail_id')->constrained('booking_details')->cascadeOnDelete();
    $t->unsignedTinyInteger('jenis');          // 1 sebelum, 2 sesudah
    $t->unsignedTinyInteger('kondisi');        // 1 baik, 2 lecet/perlu perhatian, 3 rusak
    $t->text('catatan')->nullable();
    $t->string('foto')->nullable();
    $t->foreignId('dibuat_oleh')->constrained('users');
    $t->timestamps();
    $t->unique(['booking_detail_id', 'jenis']);   // satu laporan per jenis; ulang = perbarui
});

// add_aktif_to_users           : boolean('aktif')->default(true)
// add_dicatat_oleh_to_pembayarans_and_pengembalians : foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete()
// job_offers.status            : kolom tinyint, nilai 3 (ditolak) tidak butuh migrasi; hanya konstanta baru di model
```

### 4.3 ERD ringkas (relasi inti)
```
users 1─1 supirs
users 1─N bookings 1─N booking_details N─1 mobils N─1 master_mobils N─1 tipe_mobils
bookings 1─N pembayarans
booking_details 1─N job_offers N─1 supirs
booking_details 1─1 pengembalians
booking_details 1─N laporan_kondisis   (maks 2: sebelum, sesudah)
```

## 5. Rancangan Layanan (Service)

| Service | Method | Perilaku |
|---|---|---|
| `BookingService` (ada) | `create`, `cancel` | Tidak diubah; dipanggil oleh API admin dan pelanggan |
| `PembayaranService` | `verifikasi(p, admin)`, `tolak(p, admin, alasan)`, `tandaiLunas(p, admin, metode)` | Mengubah status pembayaran; mencatat `dicatat_oleh`; menolak transisi tidak valid dengan `DomainException` |
| `PenugasanService` | `tawarkan(detail)`, `terima(offer, supir)`, `tolak(offer, supir)` | `tawarkan`: hanya `pakai_supir = 1`, sopir `status = 1` dan disetujui, **tidak mengubah** `pakai_supir`. `terima`: dalam transaksi dengan `lockForUpdate` pada detail; gagal bila `supir_id` sudah terisi; tutup tawaran lain; sopir → 2. `tolak`: tawaran → 3 |
| `PengembalianService` | `catat(detail, data, admin)` | Menghitung jam terlambat (`ceil`), denda = jam × `Pengembalian::DENDA_PER_JAM`; menagihkan denda ke pelunasan/tagihan terpisah; detail → 3; `bebaskanSupir()`; mobil → maintenance (kondisi baik, dicek admin sebelum tersedia kembali) atau rusak; booking → Done bila semua detail 3 (observer yang ada) |
| `JemputService` | `konfirmasi(detail)` | Syarat DP lunas; booking dan detail → Ongoing; mobil → Disewa |
| `LaporanKondisiService` | `simpan(detail, jenis, data, foto, user)` | Memvalidasi pengguna berhak (admin, atau sopir yang ditugaskan); menyimpan foto; upsert per jenis |
| `RekapService` | `bulanan(Y-m)` | Jumlah pesanan per status, pendapatan (pembayaran lunas), total denda, jumlah hari sewa per mobil |

Semua perubahan lintas tabel dibungkus `DB::transaction`.

### Perbaikan yang menyertai pemindahan
- `Admin\BookingController::assignJobSupir` memanggil `PenugasanService::tawarkan` (menutup kelemahan: sopir tidak disetujui menerima tawaran, `pakai_supir` tertimpa 2).
- `Admin\PembayaranController`, `PengembalianController`, dan jemput di `BookingController` memanggil layanan yang sama. Tes web di `StaffFlowTest` dan `CancelAndPelunasanTest` menjadi jaring pengaman.

## 6. Spesifikasi Endpoint Kunci

### Login — `POST /api/login`
```json
// request
{ "email": "admin@harkat.test", "password": "••••••••" }
// 200
{ "status": true, "message": "Login berhasil.",
  "data": { "token": "…", "user": { "id": 2, "name": "Admin", "role": 2, "role_name": "Admin" } } }
// 403 { "status": false, "message": "Akun dinonaktifkan." }
```

### Verifikasi pembayaran — `POST /api/admin/pembayaran/{id}/verifikasi`
`200` bila status = 2 (menunggu). `422` bila status bukan 2: pesan "Pembayaran belum menunggu verifikasi."

### Penugasan — `POST /api/admin/detail/{id}/penugasan`
Syarat: `pakai_supir = 1`, booking Booked, detail belum bersopir. Respons: jumlah sopir yang ditawari. `422` bila tidak ada sopir tersedia.

### Terima tugas — `POST /api/sopir/tawaran/{id}/terima`
`200` bila berhasil. `409` bila "Tugas sudah diambil sopir lain". `403` bila tawaran bukan milik sopir.

### Pengembalian — `POST /api/admin/detail/{id}/pengembalian` (multipart)
```
tanggal_kembali_aktual (datetime, wajib) · kondisi (1 baik | 0 rusak) · catatan · gambar (jpg|png ≤ 2 MB)
```
Respons memuat `denda_flag`, `jam_terlambat`, `nominal_denda`, status akhir booking.

### Laporan kondisi — `POST /api/admin/detail/{id}/laporan-kondisi` dan `POST /api/sopir/tugas/{id}/laporan-kondisi` (multipart)
```
jenis (1|2, wajib) · kondisi (1|2|3, wajib) · catatan · foto (jpg|png ≤ 2 MB)
```

Daftar lengkap endpoint dan peran ada di [PRD_API.md §4](PRD_API.md).

## 7. Penanganan Galat

- Pengecualian domain (`DomainException`) dipetakan ke **422** dengan pesan Indonesia; konflik rebutan (`DomainException` berkode 409, mis. tugas sudah diambil sopir lain) ke **409**.
- `ValidationException` → 422 dengan `errors`.
- `AuthenticationException` → 401; `AuthorizationException`/peran salah → 403; `ModelNotFoundException` → 404.
- Galat tak terduga → 500 dengan pesan generik; rinci dicatat di log (tidak dikirim ke klien).
- Handler untuk `api/*` selalu mengembalikan JSON (tidak pernah halaman HTML).

## 8. Keamanan

| Ancaman | Kontrol |
|---|---|
| Akses lintas peran | `api.role:` pada setiap grup route + tes 403 per grup |
| Pelanggan membaca pesanan orang lain | Query selalu dibatasi `user_id`; tes IDOR |
| Brute force | `throttle:login` |
| Unggahan berbahaya | `mimes:jpg,jpeg,png`, `max:2048`, nama acak, simpan di disk `public`, bukan di folder kode |
| Token dicuri | Kedaluwarsa 7 hari, logout mencabut token, penyimpanan aman di klien |
| Rebutan data | `lockForUpdate` + transaksi saat terima tugas |
| Mass assignment | `$fillable` eksplisit, `FormRequest` memilih kolom |
| Transport | HTTPS pada lingkungan produksi (pengembangan: HTTP lokal) |

## 9. Strategi Pengujian

| Tingkat | Alat | Cakupan |
|---|---|---|
| Fitur (otomatis) | PHPUnit, `db_rentcar_test` | Setiap endpoint: sukses, validasi, 401, 403, aturan bisnis. Tes baru: `StaffAuthTest`, `AdminBookingApiTest`, `PembayaranApiTest`, `PenugasanApiTest`, `PengembalianApiTest`, `SopirApiTest`, `LaporanKondisiApiTest`, `RekapApiTest` |
| Regresi web | Tes yang ada (30 tes, 222 assertion) | Harus tetap lulus setelah tiap pemindahan logika |
| Black box | Koleksi Postman (environment: `base_url`, `token_admin`, `token_sopir`, `token_customer`) | Kelas valid/tidak valid per masukan; menjadi bahan Bab IV |
| Alur | Skenario end-to-end Postman | Booked → Ongoing → Done dengan sopir; penolakan sopir; bentrok jadwal |

Kasus uji wajib: jemput saat DP belum lunas; dua sopir menerima tugas yang sama; denda 0/1/2 jam; sopir nonaktif login; pelanggan memanggil `/admin/*`; unggah berkas bukan gambar.

## 10. Data Uji (Seeder)

1 admin, 2 sopir (1 tersedia, 1 libur), 2 pelanggan, ±5 mobil lintas tipe, beberapa pesanan di tiap status. Kata sandi uji hanya di seeder, bukan di dokumen.

## 11. Konfigurasi dan Operasional

- `.env`: `DB_*`, `APP_URL`, `FILESYSTEM_DISK=public`; `php artisan storage:link`.
- Jalankan: `php artisan serve --host=0.0.0.0` (agar terjangkau dari perangkat), `php artisan schedule:work` untuk pembatalan DP otomatis.
- Migrasi: `php artisan migrate` (hanya tambahan; aman bagi web).

## 12. Risiko Teknis

| Risiko | Mitigasi |
|---|---|
| Logika web dan API menyimpang | Satu Service untuk keduanya + tes regresi |
| `pakai_supir` bernilai 2 pada data lama | Layanan baru tidak menulis nilai 2; pembacaan menganggap 2 sama dengan 1 (sudah ditawarkan) |
| Foreign key cascade (BUGS #36) | Migrasi baru memakai `cascadeOnDelete` hanya pada tabel anak baru; tabel lama tidak diubah |
| Waktu server vs zona | `APP_TIMEZONE=Asia/Jakarta`; perbandingan jadwal memakai Carbon |
