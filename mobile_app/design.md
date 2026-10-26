# Design Direction — Harkat Rental (Flutter, Android)

Arah desain mengikuti referensi: **terang, hangat, elegan, bergaya editorial premium**. Satu warna merek hijau hutan gelap di atas latar putih tulang, tipografi tegas dengan judul besar, banyak ruang kosong, kartu putih berlengan lembut, dan foto mobil sebagai pusat perhatian.

Dokumen ini adalah acuan tunggal untuk seluruh layar (Pelanggan, Admin, Sopir). Semua nilai ada di sini supaya `AppColors`, `ThemeData`, dan widget bersama ditulis dari satu sumber.

---

## 1. Prinsip

1. **Tenang dan lapang.** Latar terang, satu warna aksen, sedikit garis. Pemisah dibuat dengan jarak dan bayangan lembut, bukan garis tebal.
2. **Foto yang berbicara.** Mobil adalah konten utama. Kartu dan banner memberi ruang pada gambar, teks seperlunya.
3. **Hierarki jelas.** Judul besar berlapis dua warna (hitam kehijauan + hijau merek), teks sekunder abu hangat, harga paling tebal pada kartu.
4. **Satu aksi utama per layar.** Tombol hijau pekat penuh hanya untuk aksi utama (Pesan, Bayar, Simpan). Aksi lain memakai tombol garis atau teks.
5. **Responsif tanpa kejutan.** Satu bahasa visual untuk semua ukuran layar; kolom dan jarak menyesuaikan, bukan gayanya.
6. **Admin dan Sopir ikut bahasa yang sama,** lebih padat dan fungsional (daftar, status, aksi), tanpa banner foto besar.

---

## 2. Warna

Mode **terang saja** (aplikasi tidak lagi memakai tema gelap indigo). Nilai di bawah menggantikan isi `lib/core/constants/app_colors.dart`.

| Token | Hex | Pemakaian |
|---|---|---|
| `bg` | `#F6F5F1` | Latar layar (putih tulang hangat) |
| `surface` | `#FFFFFF` | Kartu, sheet, kolom input |
| `surfaceAlt` | `#EFEEE9` | Chip tidak aktif, pill pencarian, placeholder gambar |
| `primary` | `#1E3A2B` | Tombol utama, chip aktif, navigasi aktif, judul aksen |
| `primaryPressed` | `#142A1E` | Status ditekan |
| `primarySoft` | `#E3ECE6` | Latar ikon, badge lembut, highlight |
| `ink` | `#16201A` | Judul dan teks utama (hitam kehijauan) |
| `muted` | `#6B7280` | Teks sekunder, label, keterangan |
| `hint` | `#9CA3AF` | Placeholder, ikon nonaktif |
| `line` | `#E7E5DF` | Garis tipis dan pembatas bila perlu |
| `onPrimary` | `#FFFFFF` | Teks dan ikon di atas hijau |
| `danger` | `#D64545` | Error, titik notifikasi, status batal |
| `warning` | `#C98A12` | Menunggu, DP hampir jatuh tempo |
| `success` | `#2E7D4F` | Lunas, selesai, tersedia |
| `info` | `#2F6FB5` | Sedang berjalan |

Status pesanan memakai pasangan **teks + latar lembut** (misalnya `success` di atas `#E3F1E8`), tidak pernah warna pekat penuh.

| Status | Teks | Latar |
|---|---|---|
| Booked | `warning` | `#FBF0D9` |
| Ongoing | `info` | `#E3EEF9` |
| Done | `success` | `#E3F1E8` |
| Batal | `danger` | `#FBE5E5` |

Overlay foto pada banner: gradien hitam `0% → 65%` dari kanan-atas ke kiri-bawah agar teks putih terbaca.

---

## 3. Tipografi

Font: **Plus Jakarta Sans** (paket `google_fonts` yang sudah ada). Geometris, hangat, dan terbaca baik untuk angka harga. Cadangan: `Outfit`.

| Gaya | Ukuran / tinggi baris | Berat | Pemakaian |
|---|---|---|---|
| `display` | 32 / 1.1 | 800 | Judul sambutan beranda ("Sewa Mobil / Dengan Cara Anda") |
| `title1` | 22 / 1.2 | 700 | Judul layar |
| `title2` | 18 / 1.25 | 700 | Judul seksi ("Mobil Populer") |
| `body` | 14 / 1.45 | 500 | Isi umum |
| `bodySmall` | 12.5 / 1.4 | 500 | Keterangan, spesifikasi |
| `label` | 12 / 1.2 | 600 | Chip, tab, badge |
| `eyebrow` | 10 / 1.2, jarak huruf 1.6 | 700 | HURUF KAPITAL kecil di atas judul banner |
| `price` | 16 / 1.2 | 800 | Harga pada kartu |
| `button` | 14.5 / 1 | 700 | Teks tombol |

Aturan:
- Judul besar dibagi dua baris; baris kedua berwarna `primary`.
- Angka harga memakai angka tabular, format `Rp350.000` dan satuan `/ hari` dalam `muted`.
- Skala teks sistem dihormati sampai 1.3×; layout tidak boleh terpotong (gunakan `Flexible`, `maxLines`, `overflow`).

---

## 4. Bentuk, jarak, dan kedalaman

- **Grid jarak 4 dp:** 4, 8, 12, 16, 20, 24, 32.
- **Gutter layar:** 20 dp (16 dp pada lebar < 360 dp).
- **Radius:** kartu 22, banner 24, tombol dan pill 999 (penuh), input 16, sheet 28 (sudut atas), gambar di dalam kartu 16.
- **Bayangan** sangat lembut: `0 8 24 rgba(22,32,26,0.06)` untuk kartu; `0 12 32 rgba(22,32,26,0.10)` untuk elemen mengambang (bar navigasi, tombol aksi).
- Tidak ada garis tepi pada kartu putih di atas `bg`. Garis `line` hanya untuk daftar padat (Admin) dan pemisah dalam sheet.
- Ikon: **Lucide-style outline**, tebal 1.75, ukuran 20 (kecil 16, navigasi 24). Gunakan `Icons.*_rounded` atau set outline yang seragam; jangan campur gaya terisi dan outline.

---

## 5. Komponen

### 5.1 Header beranda
Baris atas: pin lokasi + "Yogyakarta" + chevron (kiri), lonceng dengan titik merah bila ada notifikasi, avatar bulat 40 dp (kanan). Di bawahnya judul `display` dua warna dan teks pendukung `muted` satu sampai dua baris. Di sisi kanan judul: **tombol bulat 52 dp hijau** berpanah (menuju katalog).

### 5.2 Pencarian
Pill penuh tinggi 52, latar `surface`, bayangan lembut, ikon cari + placeholder "Cari mobil, merek, atau tipe…". Di kanan, tombol filter bulat 52 `surface`. Mengetuk membuka sheet filter (tipe, rentang harga, transmisi, kursi).

### 5.3 Chip kategori
Baris gulir horizontal, tinggi 40, radius penuh. Aktif: latar `primary`, teks `onPrimary`, ikon putih. Tidak aktif: latar `surfaceAlt`, teks `ink`, ikon `muted`. Isi dari tipe mobil di data (Semua, SUV, MPV, Sedan, dan seterusnya), bukan daftar tetap.

### 5.4 Banner promosi
Kartu radius 24, tinggi 168, foto penuh dengan overlay gradien. Isi: `eyebrow` ("JELAJAHI LEBIH"), judul putih 2 baris, satu kalimat pendek, tombol pill putih "Pesan Sekarang →". Indikator titik di kanan bawah bila lebih dari satu banner. Isi banner **berasal dari data nyata** (armada unggulan), bukan klaim tetap.

### 5.5 Kartu mobil (grid dua kolom)
- Latar `surface`, radius 22, bayangan lembut, padding 12.
- Gambar mobil (rasio 4:3) di area atas dengan latar `surfaceAlt`, `BoxFit.contain` atau `cover` sesuai foto.
- Tombol hati bulat 32 di kanan atas (favorit hanya bila fitur disediakan; bila tidak, hilangkan).
- Nama mobil `body` berat 700, satu baris.
- Harga: `Rp350.000` (`price`) + `/ hari` (`muted`).
- Baris spesifikasi dua item: ikon kursi + "5 Kursi", ikon bahan bakar/transmisi + "Matic" (`bodySmall`, `muted`).
- Ketuk membuka detail mobil; animasi skala 0.98 saat ditekan.

### 5.6 Navigasi bawah (mengambang)
Bar putih radius 28, bayangan mengambang, jarak 12 dp dari tepi bawah dan samping. Item aktif: **lingkaran hijau 56 dp** berisi ikon putih, naik sedikit dari bar, label di bawahnya; item lain ikon `hint` dengan label kecil.

| Peran | Tab |
|---|---|
| Pelanggan | Beranda, Jelajah, Pesanan, Profil |
| Admin | Beranda, Pesanan, Pembayaran, Lainnya |
| Sopir | Beranda, Tawaran, Tugas, Riwayat |

### 5.7 Tombol
- **Utama:** `primary`, teks putih, tinggi 54, radius penuh; lebar penuh pada form dan bar bawah.
- **Sekunder:** latar `surface`, garis `line`, teks `ink`.
- **Teks:** tanpa latar, teks `primary` berat 700.
- **Berbahaya:** teks/ikon `danger`, latar `#FBE5E5` pada sheet konfirmasi.
- Status memuat: teks diganti indikator melingkar putih 18 dp, tombol tidak bisa ditekan ulang.

### 5.8 Input
Latar `surface`, radius 16, tinggi 54, tanpa garis saat diam, garis `primary` 1.5 saat fokus, garis `danger` saat error dengan pesan di bawah dalam `danger`. Label di atas kolom (`label`, `muted`), bukan label mengambang.

### 5.9 Badge status
Pill kecil (tinggi 26, padding horizontal 10), pasangan teks dan latar lembut dari tabel status.

### 5.10 Sheet, dialog, snackbar
- **Bottom sheet:** latar `surface`, radius atas 28, pegangan 36×4 `line`, tombol aksi di bawah.
- **Dialog:** radius 24, judul `title2`, aksi sejajar kanan.
- **Snackbar:** mengambang, latar `ink`, teks putih, radius 14.

### 5.11 Keadaan kosong, memuat, dan galat
- **Memuat:** kerangka shimmer abu hangat (`surfaceAlt` → `#F6F5F1`), bentuknya sama dengan konten asli.
- **Kosong:** ilustrasi garis sederhana atau ikon 48 dp dalam lingkaran `primarySoft`, judul, satu kalimat, satu tombol bila ada aksi.
- **Galat:** pesan dari server apa adanya, tombol "Coba lagi".

---

## 6. Susunan layar utama

### Pelanggan
- **Beranda:** header → pencarian → chip tipe → banner → "Mobil Populer" + "Lihat semua" → grid dua kolom.
- **Jelajah (katalog):** pencarian + chip + filter, grid, muat bertahap saat gulir.
- **Detail mobil:** foto besar (carousel) melebar sampai tepi atas, tombol kembali dan favorit melayang di atasnya; di bawah: nama, tipe, harga, spesifikasi dalam kartu kecil, deskripsi; **bar bawah** berisi harga + tombol "Pesan Sekarang".
- **Form pemesanan:** langkah singkat (tanggal dan jam, opsi sopir, ringkasan biaya); ringkasan biaya dalam kartu putih dengan rincian baris dan total tebal; DP 50% ditonjolkan beserta batas waktu bayar.
- **Pembayaran:** kartu tagihan, petunjuk transfer, area unggah bukti dengan pratinjau, tombol utama "Kirim Bukti".
- **Pesanan dan riwayat:** kartu dengan foto kecil, kode, tanggal, badge status, dan aksi sesuai status.
- **Profil:** avatar, data diri, kelengkapan profil sebagai kartu pengingat bila belum lengkap, keluar.

### Admin
- **Beranda:** kartu ringkasan (pesanan hari ini, menunggu verifikasi, sedang berjalan, armada tersedia) dalam grid 2×2, daftar "Perlu tindakan" di bawahnya.
- **Daftar (pesanan, pembayaran, armada, sopir, penyewa):** pencarian + chip filter status, baris kartu padat, tarik untuk menyegarkan.
- **Detail pesanan:** kartu bertahap (informasi, pembayaran, unit mobil dan sopir), tombol aksi sesuai status di bar bawah.
- **Formulir:** bagian-bagian dalam kartu putih, tombol simpan di bar bawah.

### Sopir
- **Beranda:** sakelar status ketersediaan besar di bagian atas, tugas aktif dalam satu kartu menonjol, jumlah tawaran masuk.
- **Tawaran:** kartu per tawaran (mobil, jadwal, lokasi) dengan dua tombol berdampingan "Tolak" dan "Terima".
- **Tugas dan laporan kondisi:** daftar langkah, unggah foto kondisi, catatan.

---

## 7. Responsif

Lebar diukur dari `MediaQuery` / `LayoutBuilder` pada konten, bukan perangkat.

| Kelas | Lebar | Perilaku |
|---|---|---|
| Ringkas | < 360 dp | Gutter 16, grid mobil 2 kolom dengan kartu lebih rapat, chip dan judul memakai skala 0.94 |
| Telepon | 360–599 dp | Rancangan acuan: gutter 20, grid 2 kolom |
| Tablet kecil | 600–839 dp | Grid 3 kolom, konten dibatasi lebar maksimum 720 dp dan dipusatkan, form satu kolom di tengah |
| Tablet besar / lanskap | ≥ 840 dp | Grid 4 kolom; daftar admin dua kolom (daftar + detail); bar navigasi tetap di bawah, tidak diganti rail |

Aturan umum:
- Gunakan `SafeArea`; bar bawah dan bar navigasi menghormati inset gestur.
- Konten gulir diberi padding bawah setara tinggi bar navigasi + 16 dp agar tidak tertutup.
- Rasio kartu mobil tetap; **tinggi mengikuti isi** (jangan tinggi tetap) supaya skala teks besar tidak terpotong.
- Papan ketik tidak boleh menutup input: form memakai `resizeToAvoidBottomInset` dan gulir otomatis ke bidang fokus.
- Orientasi lanskap pada telepon: bar navigasi disembunyikan saat papan ketik muncul; banner mengecil menjadi tinggi 140.

---

## 8. Gerak

- Transisi halaman: geser horizontal halus bawaan Material (250 ms, `easeOutCubic`).
- Kartu: skala 0.98 saat ditekan (120 ms).
- Masuk daftar: fade + naik 8 dp, berurutan 40 ms per item (maksimum 8 item pertama).
- Navigasi bawah: lingkaran aktif bergeser dengan `AnimatedAlign` 220 ms.
- Hormati `MediaQuery.disableAnimations`: matikan gerak non-esensial.

---

## 9. Aksesibilitas

- Kontras teks terhadap latar minimal 4.5:1 (`ink` di `bg` ≈ 15:1; putih di `primary` ≈ 11:1; `muted` hanya untuk teks ≥ 12 sp berat 500).
- Area sentuh minimal 48×48 dp, termasuk tombol hati, lonceng, dan filter.
- Semua ikon tanpa label diberi `Semantics`/`tooltip`; gambar mobil diberi deskripsi nama mobil.
- Status tidak hanya dibedakan warna: selalu disertai teks badge.
- Tidak ada informasi yang hanya muncul lewat gerak.

---

## 10. Konten dan bahasa

- Bahasa Indonesia, ringkas, nada sopan dan jelas ("Pesan Sekarang", "Kirim Bukti", "Coba lagi").
- Mata uang `Rp` tanpa spasi, pemisah ribuan titik; tanggal `12 Okt 2026`, jam 24 jam `09.00`.
- **Tidak ada angka atau klaim karangan.** Hapus statistik tetap di beranda pelanggan ("Armada 50+", "Rating 4.9★", "24/7 Support"). Ganti dengan data nyata dari API (jumlah armada tersedia) atau hilangkan.
- Pesan galat memakai pesan dari server; jangan ditulis ulang di aplikasi.

---

## 11. Pemetaan ke kode

| Berkas | Perubahan |
|---|---|
| `lib/core/constants/app_colors.dart` | Ganti isi dengan token bagian 2 (nama lama dipetakan dulu agar layar yang ada tetap terkompilasi, lalu dibersihkan) |
| `lib/main.dart` (`_buildDarkTheme`) | Diganti `_buildLightTheme()`: `ColorScheme.light` dari token, `GoogleFonts.plusJakartaSansTextTheme`, tema `AppBar`, `NavigationBar`, tombol, input, sheet, dialog, snackbar sesuai bagian 5 |
| `lib/core/widgets/` | Tambah widget bersama: `MobilCard`, `CategoryChip`, `PillSearchField`, `StatusBadge`, `PrimaryButton`, `SectionHeader`, `FloatingNavBar`, serta perluas `state_views.dart` (memuat, kosong, galat) |
| `lib/screens/**` | Dikerjakan per peran (urutan di bagian 12), hanya menyentuh tampilan; `Provider` dan `ApiService` tidak diubah |

---

## 12. Urutan pengerjaan

1. Token dan tema (`app_colors.dart`, `main.dart`) + widget bersama.
2. Splash dan Login.
3. Pelanggan: Beranda, Katalog, Detail Mobil, Form Pemesanan, Pembayaran, Pesanan, Profil.
4. Sopir: Beranda, Tawaran, Tugas, Riwayat, Laporan Kondisi.
5. Admin: Beranda, Pesanan, Pembayaran, Armada, Sopir, Penyewa, Rekap, Lainnya.
6. Uji responsif (360 / 411 / 600 dp dan skala teks 1.3×), lalu ambil tangkapan layar untuk Bab III 3.4 dan Bab IV.

Aturan kerja: ubah tampilan saja; alur, aturan bisnis, dan pemanggilan API yang sudah lulus pengujian tidak boleh berubah.
