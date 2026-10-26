# Technical Requirements Document (TRD)
## Aplikasi Mobile — Sistem Informasi Manajemen Rental Mobil Harkat

**Versi:** 2.0 · **Tanggal:** 6 Oktober 2026 · Pasangan dari [PRD_Mobile.md](PRD_Mobile.md) · API: [TRD_API.md](TRD_API.md)

---

## 1. Teknologi

| Komponen | Pilihan | Catatan |
|---|---|---|
| Framework | Flutter, Dart `>=3.3.0 <4.0.0` | Android saja |
| State | `provider` (`ChangeNotifier`) | Sudah dipakai |
| HTTP | `http` lewat `ApiService` | Pola `onUnauthorized`, `displayMessage`, `code` dipakai ulang |
| Penyimpanan token | **`flutter_secure_storage`** | Terpasang namun belum dipakai; menggantikan `shared_preferences` untuk token |
| Penyimpanan ringan | `shared_preferences` | Hanya preferensi non-sensitif |
| Foto | `image_picker` | Kompres sebelum unggah |
| Format | `intl` (id_ID) | Rupiah dan tanggal |
| Gambar jaringan | `cached_network_image` | |

Paket `dio` dan `google_sign_in` sudah ada di `pubspec.yaml`; `dio` tidak dipakai (tidak ditambah ketergantungan baru). Login Google tetap untuk pelanggan dan tidak diperluas.

## 2. Arsitektur

```
lib/
├── main.dart                 MultiProvider, _SessionGuard, RoleShell
├── core/
│   ├── constants/            api_constants.dart (+ endpoint admin/sopir), app_colors.dart
│   ├── services/             api_service.dart (+ metode sopir), storage_service.dart (token di secure storage)  [ada]
│   ├── widgets/              state_views.dart (EmptyState, ErrorState, StatusPill, DaftarMuat)  [ada]
│   └── utils/                formatters.dart, profile_guard.dart
├── models/
│   ├── user_model.dart (+ getter peran)   mobil_model.dart   booking_model.dart
│   ├── sopir_model.dart  (dashboard, tawaran, unit/tugas, laporan kondisi)  [ada]
│   ├── admin_model.dart   (pesanan, unit, pembayaran, dashboard, denda)  [ada]
│   └── master_model.dart  (sopir, penyewa, referensi, rekap)  [ada]
├── providers/
│   ├── auth_provider.dart  mobil_provider.dart  booking_provider.dart   (ada)
│   ├── admin_provider.dart        dashboard, pesanan, pembayaran, aksi per pesanan/unit  [ada]
│   ├── admin_master_provider.dart armada, sopir, penyewa, referensi, rekap  [ada]
│   ├── muat.dart / paging.dart    Muat, Halaman (paginasi), Hasil, ambilHalaman  [ada]
│   └── sopir_provider.dart        tawaran, tugas, riwayat, status, laporan kondisi  [ada]
└── screens/
    ├── role_shell.dart       shellFor(user): admin → AdminShell, sopir → SopirShell, lainnya → HomeScreen  [ada]
    ├── auth/ splash_screen.dart home/ mobil/ booking/ payment/ riwayat/ profile/   (pelanggan, ada)
    ├── admin/   admin_shell, admin_home_screen, pesanan_list_screen, pesanan_detail_screen, pembayaran_list_screen,
    │            pembayaran_sheet, pengembalian_form_screen, armada_list_screen, armada_form_screen,
    │            sopir_admin_screens, penyewa_screens, rekap_screen, lainnya_screen, widgets/admin_widgets  [ada]
    └── sopir/   sopir_shell, sopir_home, tawaran_list, tawaran_detail, tugas_list,
                 riwayat_sopir, laporan_kondisi_form
```

Alur data: `Screen → Provider → ApiService → API`. Layar tidak memanggil `ApiService` langsung; provider menyimpan keadaan (`loading`, `error`, `data`) dan memberi tahu UI.

### 2.1 Pemilihan layar berdasarkan peran

```dart
// setelah login / pemulihan sesi
Widget shellFor(UserModel u) => switch (u.role) {
  2 => const AdminShell(),
  3 => const SopirShell(),
  4 => const HomeScreen(),      // pelanggan (ada)
  _ => const UnsupportedRoleScreen(),
};
```

`_SessionGuard` yang ada menangani 401 (token kedaluwarsa) untuk semua peran. Provider admin dan sopir hanya didaftarkan saat peran sesuai (`ProxyProvider`/dibuat di shell), sehingga pelanggan tidak memuat kode staf.

## 3. Komunikasi dengan API

| Aspek | Nilai |
|---|---|
| Basis URL | Dapat dikonfigurasi (`api_constants.dart`); pengembangan: IP laptop jaringan lokal, port 8000, `http://…/api` |
| Header | `Authorization: Bearer <token>`, `Accept: application/json` |
| Batas waktu | 10 detik (30 detik untuk unggah multipart) |
| Respons | Amplop `{status, message, data}`; `ApiResponse` membungkus sukses/gagal dan `code` |
| Galat | 401 → `onUnauthorized` (hapus sesi, ke login); 403 → pesan hak akses; 422 → tampilkan pesan per kolom; jaringan → pesan ramah |
| Unggah | `http.MultipartRequest` (field `foto`/`gambar`/`bukti`) |
| Angka | Resource API mengirim integer; model Dart memakai `_asInt` toleran (string/double) agar tidak crash |

## 4. Endpoint yang Dipakai per Peran

| Peran | Endpoint (lihat PRD_API) |
|---|---|
| Umum | `POST /login`, `/register`, `/logout`, `GET\|PUT /me`, `PUT /me/password` |
| Pelanggan | `/mobils*`, `/tipe-mobils`, `/bookings*`, `/bookings/{id}/pembayaran*` |
| Admin | `/admin/dashboard`, `/admin/bookings*`, `/admin/pembayaran*`, `/admin/detail/{id}/penugasan\|jemput\|pengembalian\|laporan-kondisi`, `/admin/mobils`, `/admin/supirs`, `/admin/penyewa`, `/admin/rekap` |
| Sopir | `/sopir/dashboard`, `/sopir/status`, `/sopir/tawaran*`, `/sopir/tugas*`, `/sopir/riwayat` |

## 5. Keadaan dan Penyegaran

- Tidak memakai soket. **Polling**: layar Tawaran (sopir) dan Pesanan masuk (admin) memanggil ulang tiap 30 detik saat terlihat (`Timer.periodic`, dibatalkan di `dispose` dan saat aplikasi masuk latar belakang lewat `WidgetsBindingObserver`).
- **Tarik-untuk-segarkan** di semua daftar.
- Setelah aksi (verifikasi, terima, dsb.) provider memuat ulang item terkait agar UI selalu sesuai server.
- Tombol aksi: `isSubmitting` mencegah klik ganda; `try/catch` di provider memastikan `isSubmitting` selalu kembali `false` (menghindari bug tombol berputar terus).

## 6. Keamanan

| Hal | Kontrol |
|---|---|
| Token | `flutter_secure_storage` (Android Keystore); dihapus saat logout dan 401 |
| Peran | Dibaca dari respons login/`/me`; pelindung server tetap utama (403) |
| Unggahan | Pilih jpg/png, kompres (kualitas ≈ 80, sisi terpanjang ≤ 1600 px) hingga ≤ 2 MB sebelum dikirim |
| Log | Tidak mencetak token atau kata sandi |
| Jaringan | Pengembangan memakai HTTP lokal (`usesCleartextTraffic` hanya pada build debug); produksi wajib HTTPS |
| Izin Android | Kamera dan penyimpanan hanya yang diperlukan `image_picker` |

## 7. Panduan Antarmuka

- Tema hijau Harkat yang sudah ada (`app_colors.dart`); font Outfit.
- Komponen bersama: `StatusBadge`, `RupiahText`, `EmptyState`, `ErrorState`, `ConfirmDialog`, `PrimaryButton` (dengan indikator), `PhotoPicker`. Ditempatkan di `lib/core/widgets/` agar tidak diduplikasi per layar.
- Setiap layar daftar: `loading` (shimmer) → `data` / `kosong` / `galat` (coba lagi).
- Konfirmasi sebelum aksi tak terpulihkan.

## 8. Peta Layar → Provider → Endpoint (inti)

| Layar | Provider | Endpoint |
|---|---|---|
| AdminHome | `AdminProvider.dashboard` | `GET /admin/dashboard` |
| PesananList/Detail | `AdminProvider.bookings` | `GET /admin/bookings[/{id}]` |
| Detail → Verifikasi/Tolak | `AdminProvider.verifikasi/tolak` | `POST /admin/pembayaran/{id}/verifikasi\|tolak` |
| Detail → Tugaskan | `AdminProvider.tugaskan` | `POST /admin/detail/{id}/penugasan` |
| Detail → Jemput | `AdminProvider.jemput` | `PATCH /admin/detail/{id}/jemput` |
| PengembalianForm | `AdminProvider.catatPengembalian` | `POST /admin/detail/{id}/pengembalian` |
| TawaranList | `SopirProvider.tawaran` | `GET /sopir/tawaran` |
| Tawaran → Terima/Tolak | `SopirProvider.terima/tolak` | `POST /sopir/tawaran/{id}/terima\|tolak` |
| LaporanKondisiForm | `SopirProvider/AdminProvider.laporKondisi` | `POST …/laporan-kondisi` (multipart) |

## 9. Strategi Pengujian

| Tingkat | Alat | Cakupan |
|---|---|---|
| Unit | `flutter_test` | Model (penguraian JSON dengan angka sebagai string/double), `shellFor` per peran, format rupiah dan tanggal |
| Widget | `flutter_test` | Form login (validasi), `PrimaryButton` (loading), daftar dengan keadaan kosong/galat |
| Integrasi manual | Perangkat/emulator + server lokal | Skenario penerimaan PRD §9 dengan tiga akun uji |
| Black box | Tabel kasus uji (ID, fitur, masukan, hasil diharapkan, hasil aktual) | Bahan Bab IV: login, pesan, verifikasi, tugaskan, terima/tolak, jemput, pengembalian, laporan kondisi |
| Analisis statis | `flutter analyze` | Nol galat; peringatan ditangani |

## 10. Build dan Lingkungan

- Jalankan: `flutter pub get`, lalu `flutter run`. Basis URL diubah di `api_constants.dart` sesuai IP laptop.
- Server: `php artisan serve --host=0.0.0.0` agar perangkat satu jaringan dapat menjangkau.
- Emulator: Android (disarankan tanpa Android Studio penuh: `cmdline-tools` + emulator di drive D).
- Rilis: `flutter build apk --release`; HTTPS dan basis URL produksi dikonfigurasi terpisah.

## 11. Risiko Teknis

| Risiko | Mitigasi |
|---|---|
| Satu basis kode untuk tiga peran membesar | Pisah folder `screens/admin`, `screens/sopir`; provider per domain |
| Data berubah antar-perangkat | Polling + muat ulang setelah aksi |
| Angka desimal sebagai string merusak parsing | Resource API mengirim integer; parser model toleran |
| Foto besar gagal diunggah di jaringan lambat | Kompres, batas waktu 30 detik, pesan coba lagi |
| Waktu pengerjaan layar admin | Dahulukan P1 (pesanan, verifikasi, tugaskan, jemput, pengembalian) |
