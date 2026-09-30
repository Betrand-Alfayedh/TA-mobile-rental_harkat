# Product Requirements Document (PRD)
## Aplikasi Mobile Rental Mobil — Harkat Rent Car

---

## 1. Ringkasan Produk

**Nama Produk:** Harkat Rental — Mobile App  
**Versi:** 1.0.0  
**Tanggal:** 26 Mei 2026  
**Platform:** Android (Flutter)  
**Target User:** Customer  
**Tujuan:** Menyediakan aplikasi mobile bagi pelanggan untuk melihat katalog mobil, melakukan booking, upload bukti pembayaran, dan melihat riwayat penyewaan secara mudah dan cepat.

---

## 2. Latar Belakang & Tujuan

Harkat Rent Car membutuhkan aplikasi mobile untuk memberikan pengalaman yang lebih baik bagi pelanggan dalam melakukan penyewaan mobil. Aplikasi ini berfungsi sebagai client yang terhubung ke backend Laravel melalui REST API.

### Tujuan Utama:
- Memberikan akses mudah bagi customer untuk menyewa mobil dari smartphone
- Menyediakan katalog mobil dengan pencarian dan filter
- Memungkinkan booking mobil dengan estimasi harga real-time
- Mempermudah proses pembayaran dengan upload bukti bayar langsung dari kamera/galeri
- Menampilkan riwayat booking dan status pembayaran secara real-time

---

## 3. Target Pengguna

| Aspek | Detail |
|-------|--------|
| Role | Customer (role code: 4) |
| Platform | Android |
| Minimum SDK | Android 5.0 (API 21) |
| Bahasa | Indonesia |
| Lokasi | Yogyakarta dan sekitarnya |

---

## 4. Arsitektur Teknis

### 4.1 Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Framework | Flutter (Dart SDK >=3.3.0 <4.0.0) |
| State Management | Provider (ChangeNotifier) |
| HTTP Client | http package + custom ApiService wrapper |
| Authentication | Laravel Sanctum Bearer Token |
| Google Sign-In | google_sign_in package |
| Local Storage | SharedPreferences (token & user data) |
| Image Handling | cached_network_image, image_picker |
| UI Components | Google Fonts (Outfit), Shimmer, Lottie, Carousel Slider |
| Date/Time | intl (locale id_ID), table_calendar |
| Connectivity | connectivity_plus |

### 4.2 Arsitektur Aplikasi

```
lib/
├── main.dart                    # Entry point, MultiProvider setup
├── core/
│   ├── constants/
│   │   ├── api_constants.dart   # Base URL & endpoint definitions
│   │   └── app_colors.dart      # Color palette (Dark theme)
│   ├── services/
│   │   ├── api_service.dart     # HTTP client with auth & error handling
│   │   └── storage_service.dart # SharedPreferences wrapper
│   └── utils/
│       └── formatters.dart      # Rupiah, date, status formatters
├── models/
│   ├── user_model.dart          # User data model
│   ├── mobil_model.dart         # Car + TipeMobil + MasterMobil models
│   └── booking_model.dart       # Booking, BookingDetail, Pembayaran models
├── providers/
│   ├── auth_provider.dart       # Auth state & session management
│   ├── mobil_provider.dart      # Car catalog state & pagination
│   └── booking_provider.dart    # Booking & payment state
└── screens/
    ├── splash_screen.dart       # Animated splash + session restore
    ├── auth/
    │   └── login_screen.dart    # Email/password + Google login
    ├── home/
    │   └── home_screen.dart     # Main shell with bottom nav (4 tabs)
    ├── mobil/
    │   ├── mobil_list_screen.dart    # Car catalog with search & filter
    │   └── mobil_detail_screen.dart  # Car detail with image carousel
    ├── booking/
    │   ├── booking_list_screen.dart  # Active bookings list
    │   └── booking_form_screen.dart  # Booking creation form
    ├── payment/
    │   └── payment_screen.dart      # Payment detail & upload bukti
    ├── riwayat/
    │   └── riwayat_screen.dart      # Booking history
    └── profile/
        └── profile_screen.dart      # View/edit profile + logout
```

### 4.3 Komunikasi dengan Backend

| Aspek | Detail |
|-------|--------|
| Protocol | HTTP REST API |
| Base URL | Configurable (default: `http://192.168.0.184:8000/api`) |
| Auth Method | Bearer Token (Laravel Sanctum) |
| Timeout | 10 detik (30 detik untuk upload file) |
| Error Handling | Unified ApiResponse wrapper dengan network error detection |
| Content Type | application/json (multipart/form-data untuk upload) |

---

## 5. Fitur & Modul

### 5.1 Splash Screen

| Fitur | Deskripsi |
|-------|-----------|
| Animasi Logo | Fade-in + scale animation saat app launch |
| Session Restore | Cek token di local storage, validasi via GET /me |
| Auto Navigate | Ke HomeScreen jika authenticated, LoginScreen jika tidak |

**Acceptance Criteria:**
- Jika token valid → langsung ke Home
- Jika token expired/invalid → clear storage, ke Login
- Animasi berjalan minimal 1.2 detik untuk UX yang smooth

---

### 5.2 Autentikasi

| Fitur | Deskripsi |
|-------|-----------|
| Login Email/Password | Form login dengan validasi email & password (min 6 char) |
| Login Google | One-tap Google Sign-In, kirim idToken ke backend |
| Auto Register | Akun dibuat otomatis saat login pertama via Google |
| Session Persist | Token & user data disimpan di SharedPreferences |
| Logout | Revoke token di server + clear local storage |

**API Endpoints:**
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | /api/login | Login email/password |
| POST | /api/auth/google/token | Login/register via Google idToken |
| POST | /api/logout | Revoke token |
| GET | /api/me | Get current user data |

**Acceptance Criteria:**
- Loading state ditampilkan saat proses login
- Error message ditampilkan via SnackBar
- Setelah login berhasil, navigasi ke Home dan clear navigation stack

---

### 5.3 Beranda (Home Tab)

| Fitur | Deskripsi |
|-------|-----------|
| Hero Header | Greeting personalized dengan nama user |
| Search Bar | Tap untuk navigasi ke halaman katalog mobil |
| Promo Banner | Banner promosi statis |
| Quick Stats | Statistik ringkas (Armada 50+, Rating 4.9, Support 24/7) |
| Mobil Tersedia | Grid 2 kolom menampilkan 6 mobil pertama |
| Lihat Semua | Link ke halaman katalog lengkap |

**Acceptance Criteria:**
- Data mobil di-load saat tab pertama kali dibuka
- Menampilkan gambar, merk, tipe, dan harga per hari
- Tap card → navigasi ke detail mobil

---

### 5.4 Katalog Mobil

#### 5.4.1 Daftar Mobil (MobilListScreen)
| Fitur | Deskripsi |
|-------|-----------|
| Pencarian | Search by nama/merk mobil |
| Filter Tipe | Filter berdasarkan tipe mobil (dropdown) |
| Infinite Scroll | Pagination otomatis saat scroll ke bawah |
| Pull to Refresh | Refresh data dengan gesture pull-down |
| Car Card | Gambar, merk, tipe, harga, status badge |

#### 5.4.2 Detail Mobil (MobilDetailScreen)
| Fitur | Deskripsi |
|-------|-----------|
| Image Carousel | Slider gambar mobil (multiple images) |
| Info Lengkap | Merk, plat nomor, tahun, tipe, status |
| Harga | Harga sewa/hari dan harga all-in (dengan supir) |
| Tombol Booking | CTA "Pesan Sekarang" → navigasi ke form booking |

**API Endpoints:**
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/mobils | List mobil (search, type, page, per_page) |
| GET | /api/mobils/{id} | Detail mobil |
| GET | /api/mobils/available | Mobil tersedia berdasarkan tanggal |
| GET | /api/tipe-mobils | List tipe mobil |

**Acceptance Criteria:**
- Pagination: 10 item per halaman
- Hanya mobil dengan status tersedia & approved yang ditampilkan
- Gambar menggunakan cache (CachedNetworkImage)
- Shimmer loading placeholder saat data belum ready

---

### 5.5 Booking

#### 5.5.1 Daftar Booking Aktif (BookingListScreen)
| Fitur | Deskripsi |
|-------|-----------|
| List Booking | Menampilkan semua booking aktif (status: Booked, Ongoing) |
| Status Badge | Warna berbeda per status (Booked=kuning, Ongoing=biru) |
| Info Ringkas | Tanggal booking, total harga, jumlah mobil |
| Tap Detail | Navigasi ke detail booking |
| FAB | Floating Action Button untuk buat booking baru |

#### 5.5.2 Form Booking (BookingFormScreen)
| Fitur | Deskripsi |
|-------|-----------|
| Mobil Terpilih | Card menampilkan mobil yang dipilih |
| Tanggal Sewa | Date + Time picker |
| Tanggal Kembali | Date + Time picker (min = tanggal sewa) |
| Opsi Supir | Toggle switch dengan info harga |
| Asal Kota | Radio: Yogyakarta / Luar Kota (+ input nama kota) |
| Jaminan | Radio: KTP/Passport atau KTP+Motor |
| Estimasi Harga | Tombol hitung estimasi → tampilkan total, DP, sisa |
| Konfirmasi | Submit booking ke server |

**Logika Bisnis:**
- Jika asal kota = Yogyakarta → jaminan default = KTP+Motor
- Jika asal kota = Luar Kota → jaminan default = KTP/Passport
- Asal kota di-prefill dari profil user
- Harga per hari = `harga_sewa` (tanpa supir) atau `harga_all_in` (dengan supir)
- DP = 50% dari total harga

#### 5.5.3 Estimasi Harga
| Parameter | Kalkulasi |
|-----------|-----------|
| Lama Sewa | tanggal_kembali - tanggal_sewa (dalam hari) |
| Harga/Hari | harga_sewa atau harga_all_in |
| Total | harga/hari × lama_sewa |
| Uang Muka (DP) | 50% × total |
| Sisa Pelunasan | total - DP |

**API Endpoints:**
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/bookings | List booking aktif |
| POST | /api/bookings | Buat booking baru |
| GET | /api/bookings/{id} | Detail booking |
| GET | /api/bookings/riwayat | Riwayat booking (done/canceled) |
| POST | /api/bookings/estimate | Hitung estimasi harga |

**Acceptance Criteria:**
- Validasi: semua field wajib diisi
- Tanggal kembali harus setelah tanggal sewa
- Setelah booking berhasil → navigasi ke daftar booking + SnackBar sukses
- Loading indicator saat submit

---

### 5.6 Pembayaran

| Fitur | Deskripsi |
|-------|-----------|
| Detail Pembayaran | Jenis (DP/Pelunasan), jumlah, jatuh tempo, status |
| Upload Bukti | Pilih foto dari kamera/galeri |
| Metode Pembayaran | Default: QRIS (kode 3) |
| Status Tracking | Belum Bayar → Menunggu Verifikasi → Sudah Bayar |
| Catatan Admin | Tampilkan catatan jika pembayaran ditolak |

**API Endpoints:**
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/bookings/{id}/pembayaran | Detail pembayaran aktif |
| POST | /api/bookings/{id}/pembayaran/upload | Upload bukti bayar (multipart) |
| GET | /api/bookings/{id}/semua-pembayaran | Semua pembayaran (DP + Pelunasan) |

**Acceptance Criteria:**
- Upload menggunakan multipart/form-data
- Timeout upload: 30 detik
- Setelah upload berhasil → refresh daftar booking
- Tampilkan preview foto sebelum upload

---

### 5.7 Riwayat (RiwayatScreen)

| Fitur | Deskripsi |
|-------|-----------|
| List Riwayat | Booking dengan status Done (3) atau Canceled (0) |
| Status Badge | Done=hijau, Canceled=merah |
| Info | Tanggal, total harga, mobil yang disewa |
| Detail | Tap untuk lihat detail lengkap |

**Acceptance Criteria:**
- Data di-load saat tab Riwayat pertama kali dibuka
- Empty state jika belum ada riwayat

---

### 5.8 Profil (ProfileScreen)

| Fitur | Deskripsi |
|-------|-----------|
| Lihat Profil | Avatar (inisial), nama, email |
| Edit Profil | Toggle mode edit untuk nama, no_hp, alamat, asal_kota |
| Validasi Profil | Warning banner jika profil belum lengkap |
| Logout | Konfirmasi dialog → revoke token → ke LoginScreen |

**API Endpoints:**
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | /api/me | Get profile data |
| PUT | /api/me | Update profile |

**Acceptance Criteria:**
- Email tidak bisa diedit (read-only)
- Profil harus lengkap (alamat, no_hp, asal_kota) sebelum bisa booking
- Warning ditampilkan jika `isProfileComplete == false`
- Setelah save berhasil → keluar dari mode edit + SnackBar sukses

---

## 6. Data Models

### 6.1 UserModel
| Field | Type | Deskripsi |
|-------|------|-----------|
| id | int | Primary key |
| name | String | Nama lengkap |
| email | String | Email (unique) |
| noHp | String? | Nomor HP |
| alamat | String? | Alamat lengkap |
| asalKota | String? | Kota asal |
| role | int | Role code (4=Customer) |
| roleName | String? | Nama role |

### 6.2 MobilModel
| Field | Type | Deskripsi |
|-------|------|-----------|
| id | int | Primary key |
| platNomor | String | Plat nomor kendaraan |
| merk | String | Merk/nama mobil |
| tahun | int | Tahun produksi |
| hargaSewa | int | Harga sewa per hari (tanpa supir) |
| hargaAllIn | int | Harga all-in per hari (dengan supir) |
| status | int | Status mobil (1=Tersedia) |
| statusText | String? | Label status |
| gambar | String? | URL gambar utama |
| masterMobil | MasterMobilModel? | Data master mobil |
| images | List<String> | URL gambar multiple |

### 6.3 BookingModel
| Field | Type | Deskripsi |
|-------|------|-----------|
| id | int | Primary key |
| userId | int | ID customer |
| tanggalBooking | String | Tanggal booking dibuat |
| asalKota | int | 1=Yogyakarta, 2=Luar Kota |
| namaKota | String? | Nama kota (jika luar kota) |
| jaminan | int | 1=KTP/Passport, 2=KTP+Motor |
| uangMuka | int | Jumlah DP |
| totalHarga | int | Total harga sewa |
| status | int | 0=Canceled, 1=Booked, 2=Ongoing, 3=Done |
| details | List<BookingDetailModel> | Detail per mobil |
| pembayaranDp | PembayaranModel? | Data pembayaran DP |
| pelunasan | PembayaranModel? | Data pelunasan |

### 6.4 PembayaranModel
| Field | Type | Deskripsi |
|-------|------|-----------|
| id | int | Primary key |
| bookingId | int | FK ke booking |
| jenis | int | 1=DP, 2=Pelunasan |
| jumlah | int | Nominal pembayaran |
| metodePembayaran | int? | 1=Cash, 2=Transfer, 3=QRIS |
| statusPembayaran | int? | 0=Belum Bayar, 1=Sudah Bayar, 2=Pending |
| fotoBukti | String? | URL foto bukti bayar |
| jatuhTempo | String? | Deadline pembayaran |
| catatanAdmin | String? | Catatan dari admin |

---

## 7. Navigasi & User Flow

### 7.1 Struktur Navigasi

```
SplashScreen
├── LoginScreen (jika belum auth)
│   └── HomeScreen (setelah login berhasil)
└── HomeScreen (jika sudah auth)
    ├── Tab 0: Beranda
    │   ├── → MobilListScreen (Lihat Semua / Search)
    │   └── → MobilDetailScreen (tap car card)
    │       └── → BookingFormScreen (Pesan Sekarang)
    ├── Tab 1: Booking
    │   ├── → BookingFormScreen (FAB)
    │   └── → PaymentScreen (tap booking)
    ├── Tab 2: Riwayat
    │   └── → Detail booking (tap item)
    └── Tab 3: Profil
        └── → LoginScreen (setelah logout)
```

### 7.2 Bottom Navigation Bar

| Index | Label | Icon | Screen |
|-------|-------|------|--------|
| 0 | Beranda | home | _HomeTab |
| 1 | Booking | bookmark | BookingListScreen |
| 2 | Riwayat | history | RiwayatScreen |
| 3 | Profil | person | ProfileScreen |

---

## 8. Desain & UI/UX

### 8.1 Theme
| Aspek | Detail |
|-------|--------|
| Mode | Dark Theme (Material 3) |
| Font | Google Fonts — Outfit |
| Primary Color | Deep Indigo (#1A237E) |
| Accent Color | Amber (#F59E0B) |
| Background | Slate 900 (#0F172A) |
| Surface | Slate 800 (#1E293B) |
| Border | Slate 600 (#475569) |
| Text Primary | Slate 100 (#F1F5F9) |
| Text Secondary | Slate 400 (#94A3B8) |

### 8.2 Status Colors
| Status | Color | Hex |
|--------|-------|-----|
| Booked | Amber | #F59E0B |
| Ongoing | Blue | #3B82F6 |
| Done | Green | #10B981 |
| Canceled | Red | #EF4444 |

### 8.3 UI Components
- Rounded corners (14-20px border radius)
- Gradient backgrounds (Hero, buttons)
- Shadow effects untuk depth
- Shimmer loading placeholders
- Animated transitions (fade, scale)
- SnackBar untuk feedback (floating, rounded)

### 8.4 Orientasi
- Portrait only (locked)
- Status bar transparent

---

## 9. Keamanan

| Aspek | Implementasi |
|-------|-------------|
| Token Storage | SharedPreferences (encrypted via OS) |
| API Auth | Bearer Token pada setiap request authenticated |
| Token Validation | Auto-check saat app launch via GET /me |
| Session Expiry | Clear token & redirect ke login jika 401 |
| Google OAuth | Server-side validation via Google ID Token |
| Input Validation | Client-side form validation sebelum submit |
| Network Error | Handling SocketException, TimeoutException, HttpException |

---

## 10. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| Performance | API response < 3 detik, UI render < 16ms (60fps) |
| Offline | Graceful error handling saat tidak ada koneksi |
| Image Caching | CachedNetworkImage untuk mengurangi bandwidth |
| Min Android | API 21 (Android 5.0 Lollipop) |
| App Size | < 30MB (APK) |
| Locale | Indonesia (id_ID) untuk format tanggal & mata uang |
| Timeout | 10 detik (request biasa), 30 detik (upload file) |
| Pagination | 10 item per page dengan infinite scroll |

---

## 11. Batasan & Asumsi

### Batasan:
- Aplikasi hanya untuk role Customer (tidak ada fitur admin/supir)
- Hanya platform Android (iOS tidak di-build)
- Tidak ada fitur push notification (belum diimplementasi)
- Tidak ada offline mode (membutuhkan koneksi internet)
- Pembayaran manual (upload bukti, bukan payment gateway)
- Satu booking hanya bisa memilih satu mobil (dari form saat ini)

### Asumsi:
- User dan server berada di jaringan yang sama (development) atau server di-deploy publik (production)
- Backend Laravel sudah berjalan dan API tersedia
- Google OAuth sudah dikonfigurasi dengan Web Client ID yang benar
- Customer sudah memiliki akun (via register di web atau auto-create via Google)

---

## 12. API Contract Summary

### Public Endpoints (No Auth)
| Method | Endpoint | Response |
|--------|----------|----------|
| POST | /api/login | { token, user } |
| POST | /api/auth/google/token | { token, user } |
| GET | /api/mobils | { data: [...], meta: { last_page } } |
| GET | /api/mobils/{id} | { data: { mobil } } |
| GET | /api/mobils/available | { data: [...] } |
| GET | /api/tipe-mobils | { data: [...] } |

### Protected Endpoints (Bearer Token)
| Method | Endpoint | Response |
|--------|----------|----------|
| POST | /api/logout | { message } |
| GET | /api/me | { data: { user } } |
| PUT | /api/me | { data: { user } } |
| GET | /api/bookings | { data: [...] } |
| POST | /api/bookings | { data: { booking } } |
| GET | /api/bookings/{id} | { data: { booking + details + pembayaran } } |
| GET | /api/bookings/riwayat | { data: [...] } |
| POST | /api/bookings/estimate | { data: { total, uang_muka } } |
| GET | /api/bookings/{id}/pembayaran | { data: { pembayaran } } |
| POST | /api/bookings/{id}/pembayaran/upload | { message } (multipart) |
| GET | /api/bookings/{id}/semua-pembayaran | { data: [...] } |

---

## 13. Roadmap & Prioritas

| Prioritas | Fitur | Status |
|-----------|-------|--------|
| P0 (Critical) | Login (Email + Google) | ✅ Implemented |
| P0 (Critical) | Katalog Mobil (list, detail, search, filter) | ✅ Implemented |
| P0 (Critical) | Booking (form, estimasi, submit) | ✅ Implemented |
| P0 (Critical) | Pembayaran (upload bukti) | ✅ Implemented |
| P1 (High) | Profil (view, edit, validasi kelengkapan) | ✅ Implemented |
| P1 (High) | Riwayat Booking | ✅ Implemented |
| P1 (High) | Session Management (auto-restore, expiry) | ✅ Implemented |
| P2 (Medium) | Push Notification | ❌ Future |
| P2 (Medium) | iOS Support | ❌ Future |
| P3 (Low) | Offline Mode / Local Cache | ❌ Future |
| P3 (Low) | In-App Payment Gateway | ❌ Future |
| P3 (Low) | Rating & Review Mobil | ❌ Future |

---

## 14. Dependencies (pubspec.yaml)

| Package | Versi | Fungsi |
|---------|-------|--------|
| provider | ^6.1.2 | State management |
| http | ^1.2.1 | HTTP client |
| dio | ^5.4.3+1 | HTTP client (alternative) |
| google_sign_in | ^6.2.1 | Google OAuth |
| shared_preferences | ^2.2.3 | Local storage |
| flutter_secure_storage | ^9.2.2 | Secure storage |
| cached_network_image | ^3.3.1 | Image caching |
| image_picker | ^1.1.2 | Camera/gallery picker |
| google_fonts | ^6.2.1 | Custom fonts |
| shimmer | ^3.0.0 | Loading placeholder |
| lottie | ^3.1.2 | Animations |
| carousel_slider | ^4.2.1 | Image carousel |
| intl | ^0.19.0 | Date/number formatting |
| table_calendar | ^3.1.2 | Calendar widget |
| fluttertoast | ^8.2.8 | Toast messages |
| connectivity_plus | ^6.0.5 | Network status |
| url_launcher | ^6.3.0 | Open URLs |
| gap | ^3.0.1 | Spacing widget |
| badges | ^3.1.2 | Badge widget |
| flutter_svg | ^2.0.10+1 | SVG rendering |

---

*Dokumen ini merupakan referensi utama untuk pengembangan dan pemeliharaan aplikasi mobile Harkat Rent Car.*
