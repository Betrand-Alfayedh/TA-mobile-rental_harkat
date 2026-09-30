# Product Requirements Document (PRD)
## Sistem Informasi Rental Mobil — Harkat Rent Car (Web Application)

---

## 1. Ringkasan Produk

**Nama Produk:** Harkat Rent Car — Web Management System  
**Versi:** 1.0  
**Tanggal:** 26 Mei 2026  
**Teknologi:** Laravel 11, Blade Template, Tailwind CSS, Vite, MySQL  
**Tujuan:** Menyediakan platform web untuk pengelolaan operasional rental mobil yang mencakup manajemen armada, booking, pembayaran, supir, pengembalian, dan pelaporan.

---

## 2. Latar Belakang & Tujuan

Harkat Rent Car membutuhkan sistem informasi berbasis web untuk mengelola seluruh proses bisnis penyewaan mobil secara digital. Sistem ini melayani empat peran pengguna (role) dengan hak akses dan fitur yang berbeda-beda.

### Tujuan Utama:
- Digitalisasi proses booking dan pembayaran rental mobil
- Mempermudah admin dalam mengelola armada, supir, dan transaksi
- Memberikan kontrol approval oleh SuperAdmin untuk data kritis (mobil & supir baru)
- Menyediakan laporan operasional dalam format PDF
- Memberikan akses customer untuk melihat katalog mobil, melakukan booking, dan upload bukti pembayaran

---

## 3. Pengguna & Peran (User Roles)

| No | Role | Kode | Deskripsi |
|----|------|------|-----------|
| 1 | SuperAdmin | 1 | Mengelola user, approval data mobil/supir, melihat laporan |
| 2 | Admin | 2 | Mengelola operasional harian: mobil, supir, booking, pembayaran, pengembalian, laporan |
| 3 | Supir | 3 | Menerima/menolak job offer, update status ketersediaan |
| 4 | Customer | 4 | Melihat katalog mobil, melakukan booking, upload pembayaran, melihat riwayat |

---

## 4. Arsitektur Sistem

### 4.1 Tech Stack
- **Backend:** PHP 8.x, Laravel 11
- **Frontend:** Blade Template Engine, Tailwind CSS, Alpine.js
- **Build Tool:** Vite
- **Database:** MySQL
- **Authentication:** Laravel Sanctum (API), Session-based (Web), Google OAuth
- **PDF Generation:** Barryvdh/DomPDF
- **Real-time:** Laravel Echo (Broadcasting)

### 4.2 Struktur Multi-Auth
Sistem menggunakan single `users` table dengan kolom `role` (integer) untuk membedakan hak akses. Middleware `role:{RoleName}` digunakan untuk proteksi route.

---

## 5. Fitur & Modul

### 5.1 Modul Autentikasi (Semua Role)

| Fitur | Deskripsi |
|-------|-----------|
| Login Universal | Form login tunggal dengan parameter role (`/login/{role}`) |
| Register | Registrasi khusus Customer |
| Google OAuth | Login via Google (Customer) |
| Forgot Password | Reset password via email |
| Email Verification | Verifikasi email wajib untuk Customer |
| Logout | Logout untuk semua role |

**Acceptance Criteria:**
- User diarahkan ke dashboard sesuai role setelah login
- Customer wajib verifikasi email sebelum mengakses fitur booking
- Profil Customer harus lengkap (alamat, no_hp) sebelum bisa booking

---

### 5.2 Modul Landing Page (Public)

| Fitur | Deskripsi |
|-------|-----------|
| Katalog Mobil | Menampilkan daftar master mobil yang tersedia dan sudah di-approve |
| Detail Mobil | Halaman detail per mobil (resource route) |

**Acceptance Criteria:**
- Hanya mobil dengan `status != 0` (bukan rusak) dan `status_approval = 1` (approved) yang ditampilkan
- Halaman dapat diakses tanpa login

---

### 5.3 Modul Customer (Role: Customer)

#### 5.3.1 Profil
| Fitur | Deskripsi |
|-------|-----------|
| Lihat Profil | Menampilkan data profil customer |
| Edit Profil | Update nama, email, no_hp, alamat, asal_kota |
| Hapus Akun | Soft delete akun customer |
| Autocomplete Kota | Pencarian kota otomatis |

#### 5.3.2 Booking
| Fitur | Deskripsi |
|-------|-----------|
| Cek Ketersediaan Mobil | Pengecekan mobil tersedia berdasarkan tanggal |
| Buat Booking | Form pemesanan dengan pilihan mobil, tanggal sewa, tanggal kembali, opsi supir |
| Daftar Booking | Melihat semua booking aktif |
| Riwayat Booking | Melihat riwayat booking yang sudah selesai/dibatalkan |

#### 5.3.3 Pembayaran
| Fitur | Deskripsi |
|-------|-----------|
| Lihat Detail Pembayaran | Informasi jumlah, jatuh tempo, metode |
| Upload Bukti Bayar | Upload foto bukti transfer/pembayaran |

**Acceptance Criteria:**
- Booking memiliki status: Canceled (0), Booked (1), Ongoing (2), Done (3)
- Pembayaran terdiri dari 2 jenis: DP (1) dan Pelunasan (2)
- Metode pembayaran: Cash (1), Transfer (2), QRIS (3)
- Status pembayaran: Belum Bayar (0), Sudah Bayar (1), Pending (2)
- Jika melewati jatuh tempo dan belum bayar, status otomatis menjadi CANCELED
- Jaminan yang tersedia: KTP/Passport (1), KTP & Motor (2)

---

### 5.4 Modul Admin (Role: Admin)

#### 5.4.1 Dashboard
| Fitur | Deskripsi |
|-------|-----------|
| Statistik | Total mobil, mobil tersedia, mobil disewa, total supir, supir bertugas, supir siap |

#### 5.4.2 Manajemen Tipe Mobil
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Tipe Mobil | Tambah, edit, hapus kategori/tipe mobil |

#### 5.4.3 Manajemen Master Mobil
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Master Mobil | Tambah, edit data master mobil (nama, tipe) |
| Update Tipe | Mengubah tipe mobil pada master data |

#### 5.4.4 Manajemen Mobil (Unit)
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Mobil | Tambah, edit, hapus unit mobil |
| Update Status | Mengubah status mobil (Rusak/Tersedia/Dibooking/Disewa/Maintenance) |
| Multi Gambar | Upload multiple gambar per mobil |

**Status Mobil:**
| Kode | Status |
|------|--------|
| 0 | Rusak |
| 1 | Tersedia |
| 2 | Telah Dibooking |
| 3 | Telah Disewa |
| 4 | Maintenance |

**Status Approval Mobil:**
| Kode | Status |
|------|--------|
| 0 | Rejected |
| 1 | Approved |
| 2 | Pending |

#### 5.4.5 Manajemen Supir
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Supir | Tambah, edit, hapus data supir |
| Status Supir | Unavailable (0), Available (1), Bertugas (2) |

#### 5.4.6 Manajemen Pelanggan
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Pelanggan | Lihat, edit data pelanggan/customer |

#### 5.4.7 Manajemen Booking
| Fitur | Deskripsi |
|-------|-----------|
| CRUD Booking | Lihat, kelola semua booking |
| Konfirmasi Jemput | Konfirmasi penjemputan mobil per booking detail |
| Assign Supir | Mengirim job offer ke supir yang tersedia |

#### 5.4.8 Manajemen Pembayaran
| Fitur | Deskripsi |
|-------|-----------|
| Daftar Pembayaran | Melihat semua pembayaran masuk |
| Verifikasi Pembayaran | Approve bukti pembayaran dari customer |
| Tolak Pembayaran | Reject pembayaran dengan catatan admin |

#### 5.4.9 Manajemen Pengembalian
| Fitur | Deskripsi |
|-------|-----------|
| Daftar Pengembalian | Melihat semua pengembalian |
| Detail Pengembalian | Melihat kondisi mobil saat dikembalikan |
| Catat Pengembalian | Input tanggal aktual, kondisi, denda, catatan, foto |
| Denda | Flag denda dan nominal jika ada kerusakan |

#### 5.4.10 Laporan
| Fitur | Deskripsi |
|-------|-----------|
| Generate Laporan PDF | Laporan berisi: jumlah booking, jumlah mobil dibooking, total pendapatan, rata-rata lama sewa, mobil terpopuler |
| Simpan Laporan | File PDF disimpan di `storage/app/public/laporan/` |

---

### 5.5 Modul SuperAdmin (Role: SuperAdmin)

#### 5.5.1 Dashboard
| Fitur | Deskripsi |
|-------|-----------|
| Overview | Ringkasan data sistem |

#### 5.5.2 Manajemen User
| Fitur | Deskripsi |
|-------|-----------|
| CRUD User | Tambah, edit, hapus semua user (Admin, Supir, Customer) |

#### 5.5.3 Approval System
| Fitur | Deskripsi |
|-------|-----------|
| Daftar Approval | Melihat semua request approval (mobil baru, supir baru) |
| Detail Approval | Melihat data lama vs data baru (old_data, new_data) |
| Approve | Menyetujui perubahan/penambahan data |
| Reject | Menolak dengan catatan |

**Approval menggunakan polymorphic relation** (`approvable_type`: Mobil atau Supir)

**Status Approval:**
| Kode | Status |
|------|--------|
| 0 | Rejected |
| 1 | Approved |
| 2 | Pending |

#### 5.5.4 Laporan
| Fitur | Deskripsi |
|-------|-----------|
| Lihat Daftar Laporan | Melihat laporan yang sudah di-generate oleh Admin |

---

### 5.6 Modul Supir (Role: Supir)

| Fitur | Deskripsi |
|-------|-----------|
| Dashboard | Melihat status dan job yang tersedia |
| Update Status | Mengubah ketersediaan (Available/Unavailable) |
| Accept Job | Menerima job offer dari Admin |

---

## 6. Model Data (Entity Relationship)

### Entitas Utama:

```
User (1) ──── (N) Booking
Booking (1) ──── (N) BookingDetail
Booking (1) ──── (1) Pembayaran (DP)
Booking (1) ──── (1) Pembayaran (Pelunasan)
BookingDetail (N) ──── (1) Mobil
BookingDetail (N) ──── (1) Supir
BookingDetail (1) ──── (1) Pengembalian
BookingDetail (1) ──── (N) JobOffer
MasterMobil (1) ──── (N) Mobil
TipeMobil (1) ──── (N) MasterMobil
Mobil (1) ──── (N) MobilImage
User (1) ──── (1) Supir
Mobil/Supir ──── (N) Approval (Polymorphic)
```

### Tabel Utama:

| Tabel | Deskripsi |
|-------|-----------|
| users | Data semua pengguna (multi-role) |
| bookings | Header booking (user, tanggal, status, total harga, uang muka, jaminan) |
| booking_details | Detail per mobil dalam booking (mobil, supir, tanggal sewa/kembali, status) |
| mobils | Unit mobil (plat, merk, tahun, harga, status, approval) |
| master_mobils | Data master mobil (nama, tipe) |
| tipe_mobils | Kategori/tipe mobil |
| mobil_images | Gambar mobil (multiple) |
| supirs | Data supir (user_id, status, approval, gambar) |
| pembayarans | Data pembayaran (booking_id, jumlah, metode, jenis, bukti, status, jatuh_tempo) |
| pengembalians | Data pengembalian (booking_detail_id, tanggal aktual, kondisi, denda, catatan) |
| approvals | Approval polymorphic (approvable, requester, approver, status, old/new data) |
| job_offers | Penawaran job ke supir |
| cities | Data kota untuk autocomplete |

---

## 7. Alur Bisnis Utama (Business Flow)

### 7.1 Alur Booking Customer
```
1. Customer login & verifikasi email
2. Lengkapi profil (alamat, no_hp)
3. Lihat katalog mobil di landing page
4. Pilih mobil → Cek ketersediaan berdasarkan tanggal
5. Isi form booking (tanggal sewa, tanggal kembali, opsi supir, jaminan)
6. Sistem hitung total harga & uang muka
7. Booking tersimpan dengan status "Booked"
8. Customer upload bukti pembayaran DP
9. Admin verifikasi pembayaran
10. Admin assign supir (jika pakai supir)
11. Supir accept job → Status "Ongoing"
12. Pengembalian mobil → Admin catat kondisi
13. Customer bayar pelunasan (jika ada sisa)
14. Status booking → "Done"
```

### 7.2 Alur Approval (SuperAdmin)
```
1. Admin tambah mobil/supir baru → status_approval = Pending (2)
2. Sistem buat record di tabel approvals
3. SuperAdmin lihat daftar approval
4. SuperAdmin approve → status_approval = Approved (1)
   ATAU reject → status_approval = Rejected (0) + catatan
5. Data mobil/supir aktif setelah approved
```

### 7.3 Alur Pengembalian
```
1. Booking detail dengan status "Ongoing"
2. Customer kembalikan mobil
3. Admin input data pengembalian:
   - Tanggal kembali aktual
   - Kondisi mobil (Baik/Rusak)
   - Flag denda & nominal (jika ada)
   - Catatan & foto
4. Status booking detail → "Done"
5. Jika semua detail done → Booking status → "Done"
```

---

## 8. Harga & Kalkulasi

| Parameter | Deskripsi |
|-----------|-----------|
| `harga_sewa` | Harga per hari tanpa supir |
| `harga_all_in` | Harga per hari dengan supir (all-in) |
| `lama_sewa` | Selisih hari antara tanggal_sewa dan tanggal_kembali |
| `total_harga` | Σ (harga_per_hari × lama_sewa) untuk semua mobil dalam booking |
| `uang_muka` | DP yang harus dibayar di awal |

---

## 9. Notifikasi & Real-time

| Fitur | Deskripsi |
|-------|-----------|
| Broadcasting | Laravel Echo untuk notifikasi real-time |
| Job Offer | Notifikasi ke supir saat ada job baru |
| Email Verification | Email verifikasi saat registrasi |
| Password Reset | Email reset password |

---

## 10. Keamanan

| Aspek | Implementasi |
|-------|-------------|
| Authentication | Session-based dengan Laravel built-in auth |
| Authorization | Middleware `role:{RoleName}` per route group |
| Email Verification | Wajib untuk Customer sebelum akses fitur |
| CSRF Protection | Token CSRF pada semua form |
| Password Hashing | Bcrypt (Laravel default) |
| Google OAuth | Login alternatif via Google |
| File Upload | Validasi tipe & ukuran file untuk bukti pembayaran dan gambar |

---

## 11. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| Performance | Halaman load < 3 detik |
| Responsive | UI responsive untuk desktop dan tablet (admin panel) |
| Browser Support | Chrome, Firefox, Edge (versi terbaru) |
| Scalability | Mendukung hingga 100 unit mobil dan 50 supir aktif |
| Availability | 99% uptime (hosting standard) |
| Backup | Database backup harian |
| Locale | Bahasa Indonesia, format tanggal & mata uang Indonesia (Rp) |

---

## 12. Batasan & Asumsi

### Batasan:
- Web app ditujukan untuk Admin, SuperAdmin, dan Supir sebagai panel manajemen
- Customer mengakses web untuk booking dan pembayaran (selain via mobile app)
- Sistem tidak menangani pembayaran gateway otomatis (manual upload bukti)
- Laporan hanya dalam format PDF

### Asumsi:
- Setiap booking bisa memiliki lebih dari satu mobil (multi-detail)
- Satu supir hanya bisa handle satu job aktif pada satu waktu
- Harga sudah termasuk semua biaya (tidak ada biaya tambahan selain denda)
- Asal kota Yogyakarta (kode 1) adalah base location rental

---

## 13. Roadmap & Prioritas

| Prioritas | Fitur | Status |
|-----------|-------|--------|
| P0 (Critical) | Login/Register, Manajemen Mobil, Booking, Pembayaran | ✅ Implemented |
| P0 (Critical) | Dashboard Admin, Pengembalian | ✅ Implemented |
| P1 (High) | Approval System (SuperAdmin) | ✅ Implemented |
| P1 (High) | Manajemen Supir & Job Offer | ✅ Implemented |
| P1 (High) | Laporan PDF | ✅ Implemented |
| P2 (Medium) | Google OAuth | ✅ Implemented |
| P2 (Medium) | Real-time Notification (Broadcasting) | ✅ Implemented |
| P3 (Low) | Payment Gateway Integration | ❌ Future |
| P3 (Low) | Advanced Analytics Dashboard | ❌ Future |

---

## 14. Lampiran

### 14.1 URL Structure

| Prefix | Role | Contoh |
|--------|------|--------|
| `/` | Public | Landing page, katalog mobil |
| `/login/{role}` | Guest | Login per role |
| `/admin/*` | Admin | `/admin/mobil`, `/admin/booking` |
| `/superadmin/*` | SuperAdmin | `/superadmin/approvals` |
| `/supir/*` | Supir | `/supir/dashboard` |
| `/booking/*` | Customer | `/booking/create` |
| `/pembayaran/*` | Customer | `/pembayaran/{booking}` |

### 14.2 Daftar View Templates

```
resources/views/
├── admin/
│   ├── dashboard.blade.php
│   ├── master-mobil/
│   ├── mobil/
│   ├── payments/
│   ├── pelanggan/
│   ├── pengembalian/
│   ├── supir/
│   ├── tipe-mobil/
│   └── transaksi/
├── superadmin/
│   ├── dashboard.blade.php
│   ├── approvals/
│   ├── laporan/
│   └── users/
├── supir/
│   └── dashboard.blade.php
├── customer/
│   ├── booking/
│   ├── mobil/
│   └── pembayaran/
├── auth/
├── profile/
├── layouts/
├── components/
├── emails/
├── invoices/
├── laporan/
└── errors/
```

---

*Dokumen ini merupakan referensi utama untuk pengembangan dan pemeliharaan bagian web dari sistem Harkat Rent Car.*
