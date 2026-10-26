# Class Diagram
# Sistem Informasi Manajemen Rental Mobil Harkat Yogyakarta (Aplikasi Mobile Flutter + RESTful API Laravel)

> **Versi:** 1.0 | **Tanggal:** 7 Oktober 2026
> **Penulis:** Betrand Alfayedh
> **Sumber:** kode program yang telah dibangun dan diuji (`app/Models`, `app/Services`, `app/Http/Controllers/Api`, `mobile_app/lib`). Nama kelas, atribut, dan metode diambil dari kode, bukan dirancang ulang.
> **Dokumen pasangan:** [erd.md](erd.md) (struktur tabel), [usecase.md](usecase.md)

---

## 1. Pendahuluan

Sistem terdiri dari dua bagian yang dijelaskan dengan class diagram terpisah:

| Bagian | Teknologi | Diagram |
|--------|-----------|---------|
| **RESTful API** | Laravel 12, Sanctum, MySQL | 1) kelas entitas, 2) lapisan API untuk pelanggan, admin, dan sopir |
| **Aplikasi mobile** | Flutter (Provider) | 3) kelas aplikasi mobile |

Lapisan pada RESTful API mengikuti pola berikut: **Controller** menerima permintaan dan membentuk respons, **Service** menjalankan aturan bisnis di dalam transaksi basis data, dan **Model** mewakili tabel. Controller yang hanya membaca atau mengubah satu tabel secara sederhana langsung memakai Model tanpa Service.

### 1.1 Notasi

| Notasi | Arti |
|--------|------|
| Kotak tiga bagian | Nama kelas, atribut, metode |
| `+` | Anggota publik |
| `<<Controller>>`, `<<Service>>`, `<<Model>>` | Stereotipe peran kelas |
| `A "1" -- "0..*" B` | Asosiasi dengan kardinalitas (1 A memiliki nol atau banyak B) |
| `A "1" *-- "1..*" B` | Komposisi: B tidak ada tanpa A |
| `A ..> B` | A bergantung pada (memakai) B |
| `A <\|-- B` | B mewarisi A |
| `A o-- B` | Agregasi: A menyimpan B |

Atribut entitas mengikuti kolom tabel pada [erd.md](erd.md). Pada diagram hanya atribut inti dan metode perilaku yang ditampilkan; kamus data lengkap ada di ERD.

---

## 2. Class Diagram Entitas (Model)

Kelas entitas adalah model Eloquent yang memetakan tabel. Enumerasi status disimpan sebagai bilangan bulat; artinya dijelaskan di tabel 2.1.

```mermaid
classDiagram
    direction LR

    class User {
        <<Model>>
        +int id
        +string name
        +string email
        +string password
        +int role
        +string no_hp
        +string alamat
        +string asal_kota
        +bool aktif
        +string google_id
        +getRoleName() string
        +isRole(roleName) bool
        +supir() Supir
        +bookings() List~Booking~
    }

    class Supir {
        <<Model>>
        +int id
        +int user_id
        +int status
        +int status_approval
        +string gambar
        +getIsAvailable() bool
        +getStatusText() string
        +user() User
        +jobOffers() List~JobOffer~
    }

    class TipeMobil {
        <<Model>>
        +int id
        +string nama_tipe
        +masterMobils() List~MasterMobil~
    }

    class MasterMobil {
        <<Model>>
        +int id
        +string nama
        +int tipe_id
        +tipe() TipeMobil
        +mobils() List~Mobil~
    }

    class Mobil {
        <<Model>>
        +int id
        +int master_mobil_id
        +string plat_nomor
        +string merk
        +int tahun
        +decimal harga_sewa
        +decimal harga_all_in
        +int status
        +int status_approval
        +string gambar
        +getHargaPerHari(pakaiSupir) int
        +isAvailableFor(start, end) bool
        +scopeFreeBetween(query, start, end)
        +images() List~MobilImage~
        +bookingDetails() List~BookingDetail~
    }

    class MobilImage {
        <<Model>>
        +int id
        +int mobil_id
        +string image_path
    }

    class Booking {
        <<Model>>
        +int id
        +int user_id
        +datetime tanggal_booking
        +int asal_kota
        +string nama_kota
        +int status
        +int jaminan
        +decimal uang_muka
        +decimal total_harga
        +canBeCanceledByCustomer() bool
        +pembayaranDp() Pembayaran
        +pelunasan() Pembayaran
        +details() List~BookingDetail~
        +pembayarans() List~Pembayaran~
    }

    class BookingDetail {
        <<Model>>
        +int id
        +int booking_id
        +int mobil_id
        +int supir_id
        +bool pakai_supir
        +datetime tanggal_sewa
        +datetime tanggal_kembali
        +int lama_sewa
        +int status
        +bebaskanSupir() void
        +getMenungguSupir() bool
        +pengembalian() Pengembalian
        +laporanKondisis() List~LaporanKondisi~
        +jobOffers() List~JobOffer~
    }

    class Pembayaran {
        <<Model>>
        +int id
        +int booking_id
        +decimal jumlah
        +int jenis
        +int status_pembayaran
        +int metode_pembayaran
        +string foto_bukti
        +datetime jatuh_tempo
        +datetime tanggal_pembayaran
        +string catatan_admin
        +string keterangan
        +int dicatat_oleh
        +alasanTidakBisaUpload() string
    }

    class Pengembalian {
        <<Model>>
        +int DENDA_PER_JAM
        +int id
        +int booking_detail_id
        +datetime tanggal_kembali_aktual
        +int kondisi
        +bool denda_flag
        +int nominal_denda
        +string catatan
        +string gambar
        +int dicatat_oleh
    }

    class JobOffer {
        <<Model>>
        +int id
        +int booking_detail_id
        +int supir_id
        +int status
    }

    class LaporanKondisi {
        <<Model>>
        +int SEBELUM
        +int SESUDAH
        +int id
        +int booking_detail_id
        +int jenis
        +int kondisi
        +string catatan
        +string foto
        +int dibuat_oleh
        +getKondisiLabel() string
        +getJenisLabel() string
    }

    User "1" -- "0..1" Supir : memiliki data
    User "1" -- "0..*" Booking : memesan
    User "1" -- "0..*" LaporanKondisi : membuat
    User "0..1" -- "0..*" Pembayaran : mencatat
    User "0..1" -- "0..*" Pengembalian : mencatat

    TipeMobil "1" -- "0..*" MasterMobil : mengelompokkan
    MasterMobil "1" -- "0..*" Mobil : model dari
    Mobil "1" *-- "0..*" MobilImage : memiliki foto

    Booking "1" *-- "1..*" BookingDetail : berisi
    Booking "1" -- "0..*" Pembayaran : ditagih melalui
    Mobil "1" -- "0..*" BookingDetail : disewa dalam
    Supir "0..1" -- "0..*" BookingDetail : bertugas pada

    BookingDetail "1" -- "0..*" JobOffer : ditawarkan melalui
    Supir "1" -- "0..*" JobOffer : menerima
    BookingDetail "1" -- "0..1" Pengembalian : dikembalikan melalui
    BookingDetail "1" -- "0..2" LaporanKondisi : dilaporkan dalam
```

### 2.1 Arti Nilai Status

| Kelas | Atribut | Nilai |
|-------|---------|-------|
| User | `role` | 1 Super Admin (di luar lingkup), 2 Admin, 3 Sopir, 4 Pelanggan |
| Supir | `status` | 0 libur, 1 tersedia, 2 bertugas |
| Supir, Mobil | `status_approval` | 0 ditolak, 1 disetujui, 2 menunggu |
| Mobil | `status` | 0 rusak, 1 tersedia, 2 dibooking, 3 disewa, 4 maintenance |
| Booking, BookingDetail | `status` | 0 batal, 1 booked, 2 ongoing, 3 done |
| Booking | `jaminan` | 1 KTP atau paspor, 2 KTP dan motor |
| Booking | `asal_kota` | 1 Yogyakarta, selain itu luar kota (nama pada `nama_kota`) |
| Pembayaran | `jenis` | 1 uang muka (DP), 2 pelunasan (termasuk denda) |
| Pembayaran | `status_pembayaran` | 0 belum bayar, 2 menunggu verifikasi, 1 lunas |
| Pembayaran | `metode_pembayaran` | 1 tunai, 2 transfer, 3 QRIS |
| Pengembalian | `kondisi` | 1 baik, 0 rusak |
| JobOffer | `status` | 0 terbuka, 1 diterima, 2 ditutup, 3 ditolak |
| LaporanKondisi | `jenis` | 1 sebelum sewa, 2 sesudah sewa |
| LaporanKondisi | `kondisi` | 1 baik, 2 lecet atau perlu perhatian, 3 rusak |

### 2.2 Keterangan Kelas

| Kelas | Tabel | Tanggung jawab |
|-------|-------|----------------|
| User | `users` | Akun semua peran; `aktif = 0` menolak login |
| Supir | `supirs` | Data sopir dan ketersediaannya; identitas (nama, surel, nomor HP) ada pada User |
| TipeMobil, MasterMobil | `tipe_mobils`, `master_mobils` | Pengelompokan armada menjadi tipe dan model |
| Mobil | `mobils` | Unit armada; `isAvailableFor` memeriksa bentrok jadwal berdasarkan tumpang tindih tanggal |
| MobilImage | `mobil_images` | Foto tambahan unit |
| Booking | `bookings` | Satu pesanan pelanggan; menyimpan jaminan, uang muka, dan total |
| BookingDetail | `booking_details` | Satu unit mobil dalam pesanan beserta jadwal dan sopir yang bertugas |
| Pembayaran | `pembayarans` | Tagihan uang muka, pelunasan, dan denda beserta bukti bayarnya |
| Pengembalian | `pengembalians` | Catatan kembalinya unit; `DENDA_PER_JAM` = Rp50.000 |
| JobOffer | `job_offers` | Tawaran tugas kepada sopir untuk satu unit sewa |
| LaporanKondisi | `laporan_kondisis` | Laporan kondisi kendaraan sebelum dan sesudah sewa |

---

## 3. Class Diagram Lapisan API

Setiap permintaan melewati dua pemeriksaan sebelum masuk ke Controller: token Sanctum (`auth:sanctum`) dan middleware **ApiRole** yang memeriksa peran, status `aktif`, serta, untuk sopir, persetujuan akun. Peran dibaca dari basis data, bukan dari token, sehingga perubahan peran atau penonaktifan akun langsung berlaku. Seluruh Controller memakai trait **RespondsJson** untuk membentuk respons seragam (`status`, `message`, `data`, `meta`).

```mermaid
classDiagram
    direction LR
    class ApiRole {
        <<Middleware>>
        +int[] PERAN
        +handle(request, next, peran) Response
    }
    class RespondsJson {
        <<Trait>>
        +ok(data, message, code) JsonResponse
        +fail(message, code) JsonResponse
        +page(paginator, resource) JsonResponse
    }
    class User {
        <<Model>>
    }
    ApiRole ..> User : memeriksa role dan aktif
    ApiRole ..> Supir : memeriksa status_approval sopir
    class Supir {
        <<Model>>
    }
```

### 3.1 API untuk Pelanggan

Seluruh rute pelanggan berada di bawah `api.role:customer`. Registrasi, login, dan login Google tidak memerlukan token.

```mermaid
classDiagram
    direction LR
    class AuthController {
        <<Controller>>
        +register(request)
        +login(request)
        +logout(request)
        +me(request)
        +updateProfile(request)
        +changePassword(request)
    }
    class GoogleApiController {
        <<Controller>>
        +loginWithToken(request)
    }
    class MobilApiController {
        <<Controller>>
        +index(request)
        +show(id)
        +tipeIndex()
        +available(request)
    }
    class BookingApiController {
        <<Controller>>
        +index(request)
        +store(request, service)
        +show(request, id)
        +cancel(request, id, service)
        +riwayat(request)
        +estimate(request)
    }
    class PembayaranApiController {
        <<Controller>>
        +show(request, bookingId)
        +uploadBukti(request, bookingId)
        +allPayments(request, bookingId)
    }
    class BookingService {
        <<Service>>
        +int MIN_LEAD_HOURS
        +create(userId, data) Booking
        +cancel(booking, reason) void
    }
    class User { <<Model>> }
    class Mobil { <<Model>> }
    class TipeMobil { <<Model>> }
    class Booking { <<Model>> }
    class BookingDetail { <<Model>> }
    class Pembayaran { <<Model>> }
    class JobOffer { <<Model>> }

    AuthController ..> User
    GoogleApiController ..> User
    MobilApiController ..> Mobil
    MobilApiController ..> TipeMobil
    BookingApiController ..> BookingService
    BookingApiController ..> Booking
    BookingApiController ..> Mobil
    PembayaranApiController ..> Booking
    PembayaranApiController ..> Pembayaran
    BookingService ..> Booking
    BookingService ..> BookingDetail
    BookingService ..> Mobil
    BookingService ..> Pembayaran
    BookingService ..> JobOffer
```

### 3.2 API untuk Admin

Seluruh rute admin berada di bawah `api.role:admin`.

```mermaid
classDiagram
    direction LR
    class AdminBookingController {
        <<Controller>>
        +index(request)
        +show(id)
        +cancel(request, id, service)
    }
    class AdminPembayaranController {
        <<Controller>>
        +index(request)
        +verifikasi(id, request, service)
        +lunas(id, request, service)
        +tolak(id, request, service)
    }
    class AdminDetailController {
        <<Controller>>
        +penugasan(detail, service)
        +jemput(detail, service)
        +pengembalian(detail, request, service)
    }
    class AdminLaporanKondisiController {
        <<Controller>>
        +index(detail)
        +store(request, detail, service)
    }
    class AdminMobilController {
        <<Controller>>
        +referensi()
        +index(request)
        +show(id)
        +store(request)
        +update(id)
        +gambar(id)
        +status(id)
        +destroy(id)
    }
    class AdminSupirController {
        <<Controller>>
        +index(request)
        +show(id)
        +store(request)
        +update(id)
    }
    class AdminPenyewaController {
        <<Controller>>
        +index(request)
        +show(id)
        +store(request)
        +update(id)
    }
    class AdminDashboardController {
        <<Controller>>
        +index()
    }
    class AdminRekapController {
        <<Controller>>
        +bulanan(request)
    }

    class BookingService {
        <<Service>>
        +create(userId, data) Booking
        +cancel(booking, reason) void
    }
    class PembayaranService {
        <<Service>>
        +verifikasi(pembayaran, oleh) Pembayaran
        +tandaiLunas(pembayaran, metode, oleh) Pembayaran
        +tolak(pembayaran, catatan, oleh) Pembayaran
        +kirimInvoice(pembayaran) string
    }
    class PenugasanService {
        <<Service>>
        +tawarkan(detail) int
        +terima(offerId, supir) void
        +tolak(offerId, supir) void
    }
    class JemputService {
        <<Service>>
        +konfirmasi(detail) BookingDetail
    }
    class PengembalianService {
        <<Service>>
        +catat(detailId, data, gambar, oleh) Pengembalian
    }
    class LaporanKondisiService {
        <<Service>>
        +simpan(detail, oleh, data, foto) LaporanKondisi
    }
    class RekapService {
        <<Service>>
        +bulanan(bulan) array
    }

    AdminBookingController ..> BookingService
    AdminPembayaranController ..> PembayaranService
    AdminDetailController ..> PenugasanService
    AdminDetailController ..> JemputService
    AdminDetailController ..> PengembalianService
    AdminLaporanKondisiController ..> LaporanKondisiService
    AdminRekapController ..> RekapService

    AdminMobilController ..> Mobil
    AdminMobilController ..> MasterMobil
    AdminMobilController ..> TipeMobil
    AdminSupirController ..> Supir
    AdminSupirController ..> User
    AdminPenyewaController ..> User
    AdminDashboardController ..> Booking
    AdminDashboardController ..> BookingDetail
    AdminDashboardController ..> Pembayaran
    AdminDashboardController ..> Mobil
    AdminDashboardController ..> Supir
    AdminBookingController ..> Booking
    AdminPembayaranController ..> Pembayaran
    AdminDetailController ..> BookingDetail
    AdminDetailController ..> Pengembalian

    PembayaranService ..> Pembayaran
    PembayaranService ..> Booking
    PenugasanService ..> JobOffer
    PenugasanService ..> BookingDetail
    PenugasanService ..> Supir
    JemputService ..> BookingDetail
    JemputService ..> Mobil
    PengembalianService ..> Pengembalian
    PengembalianService ..> Pembayaran
    PengembalianService ..> Mobil
    PengembalianService ..> BookingDetail
    LaporanKondisiService ..> LaporanKondisi
    LaporanKondisiService ..> BookingDetail
    RekapService ..> Booking
    RekapService ..> Pembayaran
    RekapService ..> Pengembalian

    class User { <<Model>> }
    class Supir { <<Model>> }
    class Mobil { <<Model>> }
    class MasterMobil { <<Model>> }
    class TipeMobil { <<Model>> }
    class Booking { <<Model>> }
    class BookingDetail { <<Model>> }
    class Pembayaran { <<Model>> }
    class Pengembalian { <<Model>> }
    class JobOffer { <<Model>> }
    class LaporanKondisi { <<Model>> }
```

Pada kode, Controller admin berada pada namespace `App\Http\Controllers\Api\Admin` dengan nama `BookingApiController`, `PembayaranApiController`, dan seterusnya; diberi awalan *Admin* pada diagram agar tidak sama dengan Controller pelanggan yang bernama serupa.

### 3.3 API untuk Sopir

Seluruh rute sopir berada di bawah `api.role:sopir`.

```mermaid
classDiagram
    direction LR
    class SopirDashboardController {
        <<Controller>>
        +index(request)
        +status(request)
    }
    class SopirTawaranController {
        <<Controller>>
        +index(request)
        +terima(request, id, service)
        +tolak(request, id, service)
    }
    class SopirTugasController {
        <<Controller>>
        +index(request)
        +riwayat(request)
        +laporan(request, id, service)
    }
    class PenugasanService {
        <<Service>>
        +int TERBUKA
        +int DITERIMA
        +int DITUTUP
        +int DITOLAK
        +tawarkan(detail) int
        +terima(offerId, supir) void
        +tolak(offerId, supir) void
    }
    class LaporanKondisiService {
        <<Service>>
        +simpan(detail, oleh, data, foto) LaporanKondisi
    }
    class Booking { <<Model>> }
    class BookingDetail { <<Model>> }
    class JobOffer { <<Model>> }
    class Supir { <<Model>> }
    class LaporanKondisi { <<Model>> }

    SopirTawaranController ..> PenugasanService
    SopirTugasController ..> LaporanKondisiService
    SopirDashboardController ..> Booking
    SopirDashboardController ..> BookingDetail
    SopirDashboardController ..> JobOffer
    SopirTawaranController ..> JobOffer
    SopirTugasController ..> Booking
    SopirTugasController ..> BookingDetail
    PenugasanService ..> JobOffer
    PenugasanService ..> BookingDetail
    PenugasanService ..> Supir
    LaporanKondisiService ..> LaporanKondisi
    LaporanKondisiService ..> BookingDetail
```

Pada kode, nama Controller sopir adalah `DashboardApiController`, `TawaranApiController`, dan `TugasApiController` pada namespace `App\Http\Controllers\Api\Sopir`.

### 3.4 Tanggung Jawab Service

| Service | Aturan bisnis yang dijalankan |
|---------|-------------------------------|
| BookingService | Pesanan paling cepat 3 jam lagi; mobil dikunci dan diperiksa bentrok jadwal; biaya dibulatkan ke atas per 24 jam; uang muka 50% dengan batas bayar paling lambat 4 hari atau 2 jam sebelum sewa; pembatalan membebaskan unit dan menutup tawaran |
| PembayaranService | Verifikasi bukti, pencatatan tunai, penolakan bukti dengan alasan, dan pengiriman invoice lewat surel; invoice dikirim setelah respons terkirim sehingga kegagalan surel tidak menggagalkan verifikasi |
| PenugasanService | Menawarkan unit bersopir kepada seluruh sopir yang tersedia; penerimaan diproses bergantian (kunci baris) sehingga hanya satu sopir yang mendapat tugas; tawaran lain ditutup |
| JemputService | Menandai unit sudah dijemput (ongoing) hanya bila uang muka lunas dan unit tersedia |
| PengembalianService | Mencatat kembalinya unit, menghitung denda Rp50.000 per jam yang sudah dimulai, menambahkannya ke tagihan, mengubah status mobil dan sopir, dan menutup pesanan bila semua unit kembali |
| LaporanKondisiService | Menyimpan laporan kondisi sebelum atau sesudah sewa (satu per jenis) beserta foto |
| RekapService | Menghitung rekap bulanan: jumlah pesanan per status, pendapatan, denda, dan utilisasi armada |

---

## 4. Class Diagram Aplikasi Mobile (Flutter)

Aplikasi memakai pola **Provider**: layar (*screen*) mengamati *provider*, *provider* memanggil **ApiService**, dan hasilnya disimpan sebagai model. Provider selain `AuthProvider` dibuat dengan `ChangeNotifierProxyProvider` yang bergantung pada `AuthProvider`, sehingga seluruh data pengguna sebelumnya dikosongkan saat logout.

```mermaid
classDiagram
    direction LR
    class ChangeNotifier {
        <<Flutter>>
        +notifyListeners()
    }
    class ApiService {
        <<Service>>
        +Function onUnauthorized
        +call(method, url, body, query) ApiResponse
        +multipart(url, fields, files) ApiResponse
        +login(email, password) ApiResponse
        +getMe() ApiResponse
        +getMobils(...) ApiResponse
        +createBooking(payload) ApiResponse
        +uploadBukti(...) ApiResponse
    }
    class ApiResponse~T~ {
        +bool success
        +String message
        +T data
        +Map errors
        +int statusCode
        +String code
        +displayMessage() String
    }
    class StorageService {
        <<Service>>
        +saveToken(token) Future
        +getToken() Future
        +deleteToken() Future
        +saveUserJson(json) Future
        +getUserJson() Future
        +clearAll() Future
    }

    class AuthProvider {
        <<Provider>>
        +AuthStatus status
        +UserModel user
        +String error
        +init() Future
        +login(email, password) Future
        +loginWithGoogle() Future
        +logout() Future
        +updateProfile(data) Future
        +refreshMe() Future
    }
    class MobilProvider {
        <<Provider>>
        +List~MobilModel~ mobils
        +List~TipeMobilModel~ tipes
        +List~MobilModel~ available
        +loadMobils(reset) Future
        +loadMore() Future
        +applyFilter(search, type) Future
        +getMobilDetail(id) Future
        +loadAvailable(...) Future
        +reset()
    }
    class BookingProvider {
        <<Provider>>
        +List~BookingModel~ bookings
        +List~BookingModel~ riwayats
        +loadBookings() Future
        +loadRiwayat() Future
        +getDetail(id) Future
        +fetchEstimate(mobils) Future
        +createBooking(payload) Future
        +uploadBukti(...) Future
        +cancelBooking(id) Future
        +reset()
    }
    class SopirProvider {
        <<Provider>>
        +Muat dashboard
        +Muat tawaran
        +Muat tugas
        +Muat riwayat
        +startPolling()
        +stopPolling()
        +refreshAll() Future
        +terima(tawaran) Future
        +tolak(tawaran) Future
        +ubahStatus(tersedia) Future
        +kirimLaporan(...) Future
    }
    class AdminProvider {
        <<Provider>>
        +Muat dashboard
        +Halaman bookings
        +Halaman pembayaran
        +Muat detail
        +startPolling()
        +stopPolling()
        +muatPesanan() Future
        +muatPembayaran() Future
        +muatDetail(id) Future
        +catatPengembalian(...) Future
        +kirimLaporan(...) Future
    }
    class AdminMasterProvider {
        <<Provider>>
        +Halaman mobils
        +Halaman supirs
        +Halaman penyewa
        +Muat referensi
        +Muat rekap
        +muatMobil() Future
        +muatSupir() Future
        +muatPenyewa() Future
        +muatRekap(bulan) Future
    }

    class UserModel {
        <<Model>>
        +int id
        +String name
        +String email
        +String noHp
        +String alamat
        +String asalKota
        +int role
    }
    class MobilModel {
        <<Model>>
    }
    class TipeMobilModel {
        <<Model>>
    }
    class BookingModel {
        <<Model>>
        +int id
        +int uangMuka
        +int totalHarga
        +int status
        +List~BookingDetailModel~ details
        +PembayaranModel pembayaranDp
    }
    class BookingDetailModel {
        <<Model>>
        +MobilModel mobil
        +bool pakaiSupir
        +String tanggalSewa
        +String tanggalKembali
        +int lamaSewa
    }
    class PembayaranModel {
        <<Model>>
        +int jenis
        +int jumlah
        +int statusPembayaran
        +String fotoBukti
        +String jatuhTempo
    }
    class TawaranModel {
        <<Model>>
    }
    class SopirUnitModel {
        <<Model>>
    }
    class SopirDashboardModel {
        <<Model>>
    }
    class BookingAdminModel {
        <<Model>>
    }
    class PembayaranAdminModel {
        <<Model>>
    }
    class DashboardAdminModel {
        <<Model>>
    }
    class SupirAdminModel {
        <<Model>>
    }
    class PenyewaModel {
        <<Model>>
    }
    class RekapModel {
        <<Model>>
    }

    ChangeNotifier <|-- AuthProvider
    ChangeNotifier <|-- MobilProvider
    ChangeNotifier <|-- BookingProvider
    ChangeNotifier <|-- SopirProvider
    ChangeNotifier <|-- AdminProvider
    ChangeNotifier <|-- AdminMasterProvider

    AuthProvider ..> StorageService : simpan token dan pengguna
    AuthProvider ..> ApiService
    MobilProvider ..> ApiService
    BookingProvider ..> ApiService
    SopirProvider ..> ApiService
    AdminProvider ..> ApiService
    AdminMasterProvider ..> ApiService
    ApiService ..> StorageService : membaca token
    ApiService ..> ApiResponse : mengembalikan

    MobilProvider ..> AuthProvider : reset saat logout
    BookingProvider ..> AuthProvider : reset saat logout
    SopirProvider ..> AuthProvider : reset saat logout
    AdminProvider ..> AuthProvider : reset saat logout
    AdminMasterProvider ..> AuthProvider : reset saat logout

    AuthProvider o-- UserModel
    MobilProvider o-- MobilModel
    MobilProvider o-- TipeMobilModel
    BookingProvider o-- BookingModel
    BookingModel *-- BookingDetailModel
    BookingModel o-- PembayaranModel
    BookingDetailModel o-- MobilModel
    SopirProvider o-- SopirDashboardModel
    SopirProvider o-- TawaranModel
    SopirProvider o-- SopirUnitModel
    AdminProvider o-- DashboardAdminModel
    AdminProvider o-- BookingAdminModel
    AdminProvider o-- PembayaranAdminModel
    AdminMasterProvider o-- MobilModel
    AdminMasterProvider o-- SupirAdminModel
    AdminMasterProvider o-- PenyewaModel
    AdminMasterProvider o-- RekapModel
```

### 4.1 Kelas Pendukung

| Kelas | Fungsi |
|-------|--------|
| `Muat<T>` | Membungkus satu data dengan keadaan `loading`, `loaded`, dan `error` agar layar dapat menampilkan indikator, pesan galat, atau isi |
| `Halaman<T>` | Daftar berhalaman dari API (item, halaman sekarang, halaman terakhir, status muat pertama dan lanjutan) |
| `Hasil` | Hasil sebuah aksi: `galat` bila gagal dan `info` untuk pesan tambahan (mis. invoice gagal dikirim) |
| `LaporanKondisiModel`, `UnitAdminModel`, `PengembalianAdminModel`, `DendaKeterlambatan` | Model rincian unit sewa, pengembalian, dan pratinjau denda |

### 4.2 Layar menurut Peran

Setelah login, `shellFor(user)` memilih kerangka layar sesuai peran.

| Peran | Kerangka | Layar |
|-------|----------|-------|
| Pelanggan | `HomeScreen` (setelah `SplashScreen` dan `LoginScreen`) | `MobilListScreen`, `MobilDetailScreen`, `BookingFormScreen`, `BookingListScreen`, `PaymentScreen`, `RiwayatScreen`, `ProfileScreen` |
| Admin | `AdminShell` | `AdminHomeScreen`, `PesananListScreen`, `PesananDetailScreen`, `PembayaranListScreen`, `PengembalianFormScreen`, `ArmadaListScreen`, `ArmadaFormScreen`, `SopirAdminListScreen`, `SopirFormScreen`, `PenyewaListScreen`, `PenyewaFormScreen`, `RekapScreen`, `LainnyaScreen` (menu data master) |
| Sopir | `SopirShell` | `SopirHomeScreen`, `TawaranScreen`, `TugasScreen`, `RiwayatSopirScreen`, `LaporanKondisiScreen` |

---

## 5. Keterlusuran ke Use Case

| Use case | Kelas utama (API) | Kelas utama (mobile) |
|----------|-------------------|----------------------|
| UC1 sampai UC4 Akun | AuthController, GoogleApiController, ApiRole | AuthProvider, StorageService |
| UC5 sampai UC6 Katalog dan estimasi | MobilApiController, BookingApiController (estimate) | MobilProvider, BookingProvider |
| UC7 Memesan Mobil | BookingApiController, BookingService | BookingProvider |
| UC8 Mengunggah Bukti | PembayaranApiController | BookingProvider |
| UC9 sampai UC10 Pesanan dan pembatalan | BookingApiController, BookingService | BookingProvider |
| UC11 sampai UC12 Dashboard dan pantauan | Admin DashboardApiController, Admin BookingApiController | AdminProvider |
| UC13 sampai UC15 Pembayaran | Admin PembayaranApiController, PembayaranService | AdminProvider |
| UC16 Menugaskan Sopir | Admin DetailApiController, PenugasanService | AdminProvider |
| UC17 Jemput | Admin DetailApiController, JemputService | AdminProvider |
| UC18 Pengembalian dan denda | Admin DetailApiController, PengembalianService | AdminProvider |
| UC19 Laporan kondisi | Admin LaporanKondisiApiController, Sopir TugasApiController, LaporanKondisiService | AdminProvider, SopirProvider |
| UC20 sampai UC22 Data master | Admin MobilApiController, SupirApiController, PenyewaApiController | AdminMasterProvider |
| UC23 Rekap bulanan | Admin RekapApiController, RekapService | AdminMasterProvider |
| UC24 sampai UC28 Tugas sopir | Sopir DashboardApiController, TawaranApiController, TugasApiController, PenugasanService | SopirProvider |
