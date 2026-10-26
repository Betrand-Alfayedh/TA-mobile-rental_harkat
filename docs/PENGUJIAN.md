# Pengujian Sistem — Black Box Testing

**Sistem:** Sistem Informasi Manajemen Rental Mobil Harkat (aplikasi mobile Flutter + RESTful API Laravel)  
**Tanggal pengujian:** 7 Oktober 2026  
**Dokumen terkait:** [PRD_API.md](PRD_API.md) · [PRD_Mobile.md](PRD_Mobile.md) · [PLANNING.md](PLANNING.md) · [BUGS.md](../BUGS.md) · [Pengujian_BlackBox.xlsx](Pengujian_BlackBox.xlsx)

---

## 1. Metode

Pengujian memakai **black box testing**: setiap fitur diuji dari sisi masukan dan keluaran tanpa melihat kode di dalamnya. Kasus uji disusun dengan **partisi kesetaraan** (kelas masukan valid dan tidak valid) dan **analisis nilai batas** (mis. 24 jam vs 25 jam, ukuran berkas tepat 5 MB vs 5 MB + 1 byte, keterlambatan 59/60/61 menit).

Tiga cara uji dipakai:

| Cara | Alat | Cakupan |
|---|---|---|
| Otomatis — API | PHPUnit (basis data MySQL uji `db_rentcar_test`) | 107 tes, 872 assertion: setiap endpoint dengan masukan valid, tidak valid, batas, dan hak akses |
| Otomatis — aplikasi | Flutter test (unit, provider dengan server palsu, widget) | 145 tes: model data, aturan klien, keadaan layar, alur konfirmasi sampai permintaan terkirim |
| Manual — perangkat | Aplikasi di HP/emulator terhubung server lokal | Alur utama pelanggan → admin → sopir (bagian 4) |

Total **252 tes otomatis** dan **25 langkah uji manual**. Setiap kasus pada bagian 3 menunjuk tes yang membuktikannya (kolom *Bukti*).

**Kelas masukan:** V = valid · T = tidak valid · B = nilai batas · K = keamanan/kebijakan.

---

## 2. Ringkasan hasil

| Modul | Jumlah kasus | Otomatis | Manual | Hasil |
|---|---:|---:|---:|---|
| Autentikasi | 21 | 21 | 0 | Sesuai |
| Hak akses | 9 | 9 | 0 | Sesuai |
| Pemesanan | 18 | 17 | 1 | Sesuai |
| Pembayaran | 11 | 11 | 0 | Sesuai |
| Pembatalan | 5 | 5 | 0 | Sesuai |
| Admin | 10 | 10 | 0 | Sesuai |
| Penugasan sopir | 10 | 10 | 0 | Sesuai |
| Jemput | 4 | 4 | 0 | Sesuai |
| Laporan kondisi | 8 | 8 | 0 | Sesuai |
| Pengembalian | 10 | 10 | 0 | Sesuai |
| Armada | 6 | 6 | 0 | Sesuai |
| Sopir (admin) | 5 | 5 | 0 | Sesuai |
| Penyewa | 3 | 3 | 0 | Sesuai |
| Rekap | 2 | 2 | 0 | Sesuai |
| Mobile | 10 | 9 | 1 | Sesuai |
| **Total** | **132** | **130** | **2** | **Seluruhnya sesuai** |

- Seluruh 132 kasus pada tabel berstatus **Sesuai** pada pengujian akhir tanggal 7 Oktober 2026 (107 tes PHPUnit dan 145 tes Flutter lulus).
- Selama pengujian ditemukan **14 temuan** yang seluruhnya sudah diperbaiki (bagian 5). Temuan ditemukan karena kasus uji ditulis lebih dulu dari perbaikannya: tes gagal, penyebab dicari, lalu diperbaiki.
- Keterbatasan yang diketahui dicantumkan apa adanya di bagian 6.

---

## 3. Kasus uji

Kolom *Hasil aktual* seluruhnya **Sesuai** untuk kasus berstatus lulus pada pengujian akhir.


### Autentikasi

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| AUT-01 | Login | Login admin dengan kredensial benar | V | email dan kata sandi admin | 200, token dan peran 2 (admin) | PHPUnit | StaffApiAuthTest::test_admin_sopir_and_customer_can_login_and_get_role | Sesuai |
| AUT-02 | Login | Login sopir dengan kredensial benar | V | email dan kata sandi sopir yang disetujui | 200, token dan peran 3 (sopir) | PHPUnit | StaffApiAuthTest::test_admin_sopir_and_customer_can_login_and_get_role | Sesuai |
| AUT-03 | Login | Login pelanggan dengan kredensial benar | V | email dan kata sandi pelanggan | 200, token dan peran 4 (pelanggan) | PHPUnit | StaffApiAuthTest::test_admin_sopir_and_customer_can_login_and_get_role | Sesuai |
| AUT-04 | Login | Kata sandi salah | T | email benar, kata sandi salah | 422, pesan 'Email atau password salah.' | PHPUnit | StaffApiAuthTest::test_login_rejects_superadmin_inactive_account_unapproved_driver_and_wrong_password | Sesuai |
| AUT-05 | Login | Email tidak terdaftar | T | email yang tidak ada di basis data | 422, pesan sama dengan AUT-04 (tidak membocorkan email mana yang terdaftar) | PHPUnit | ApiHardeningTest::test_login_validation_rejects_unknown_email_bad_format_and_empty_fields | Sesuai |
| AUT-06 | Login | Format email salah / kolom kosong | T | 'bukan-email'; tanpa kata sandi; kosong semua | 422 dengan galat per kolom | PHPUnit | ApiHardeningTest::test_login_validation_rejects_unknown_email_bad_format_and_empty_fields | Sesuai |
| AUT-07 | Login | Akun Super Admin | K | kredensial Super Admin | 403, tidak boleh masuk aplikasi mobile | PHPUnit | StaffApiAuthTest::test_login_rejects_superadmin_inactive_account_unapproved_driver_and_wrong_password | Sesuai |
| AUT-08 | Login | Akun dinonaktifkan | K | kredensial akun dengan aktif=false | 403, pesan akun dinonaktifkan | PHPUnit | StaffApiAuthTest::test_login_rejects_superadmin_inactive_account_unapproved_driver_and_wrong_password | Sesuai |
| AUT-09 | Login | Sopir belum disetujui | K | kredensial sopir status_approval ≠ 1 | 403, pesan akun sopir belum disetujui | PHPUnit | StaffApiAuthTest::test_login_rejects_superadmin_inactive_account_unapproved_driver_and_wrong_password | Sesuai |
| AUT-10 | Login | Percobaan login berulang | B | lebih dari 5 percobaan dalam 1 menit | 429 (dibatasi) | PHPUnit | SecurityTest::test_api_login_is_throttled_after_five_attempts | Sesuai |
| AUT-11 | Token | Masa berlaku dan kemampuan token | V | login berhasil | token kedaluwarsa 7 hari dan membawa kemampuan sesuai peran | PHPUnit | StaffApiAuthTest::test_token_expires_in_seven_days_and_carries_role_ability | Sesuai |
| AUT-12 | Token | Token kedaluwarsa | T | token yang sudah lewat masa berlakunya | 401 | PHPUnit | ApiHardeningTest::test_expired_token_is_rejected | Sesuai |
| AUT-13 | Token | Akses tanpa token | T | permintaan ke /api/me, /api/bookings, /api/admin/*, /api/sopir/* tanpa token dan tanpa header Accept | 401 berformat JSON (bukan pengalihan ke halaman login) | PHPUnit | ApiHardeningTest::test_api_without_token_is_401_json_never_a_redirect | Sesuai |
| AUT-14 | Registrasi | Daftar pelanggan baru | V | nama, email baru, sandi ≥ 8 karakter beserta konfirmasi | 201, akun peran 4 aktif dan token | PHPUnit | StaffApiAuthTest::test_register_creates_a_customer_and_returns_a_token | Sesuai |
| AUT-15 | Registrasi | Data pendaftaran tidak valid | T | email sudah terdaftar; sandi pendek; konfirmasi tidak cocok; nama kosong | 422 per kolom; akun tidak dibuat | PHPUnit | ApiHardeningTest::test_register_rejects_duplicate_email_short_password_and_mismatch | Sesuai |
| AUT-16 | Ganti sandi | Ganti sandi dengan sandi lama benar | V | sandi lama benar, sandi baru valid | 200; sesi di perangkat lain dicabut, sesi yang dipakai tetap berlaku | PHPUnit | ApiHardeningTest::test_changing_password_revokes_other_sessions_but_keeps_the_current_one | Sesuai |
| AUT-17 | Ganti sandi | Sandi lama salah | T | sandi lama salah | 422; tidak ada perubahan dan semua sesi tetap | PHPUnit | ApiHardeningTest::test_wrong_current_password_changes_nothing_and_keeps_all_sessions | Sesuai |
| AUT-18 | Sesi (mobile) | Token disimpan aman | K | login berhasil di aplikasi | token di penyimpanan aman (Keystore), bukan SharedPreferences; token lama dimigrasikan | Flutter | storage_test.dart › token disimpan di penyimpanan aman, bukan di SharedPreferences; storage_test.dart › token lama di SharedPreferences dimigrasikan lalu dihapus dari sana | Sesuai |
| AUT-19 | Sesi (mobile) | Token ditolak server (401) | T | token dicabut/kedaluwarsa saat memakai aplikasi | logout otomatis dan kembali ke login dengan pesan sesi berakhir | Flutter | session_test.dart › 401 pada request ber-token saat login: logout otomatis + sessionExpired | Sesuai |
| AUT-20 | Sesi (mobile) | Server tidak terjangkau saat membuka aplikasi | B | tanpa jaringan / server error 500 | sesi dipertahankan memakai profil tersimpan, pengguna tidak dikeluarkan | Flutter | session_test.dart › offline saat boot: sesi dipertahankan; session_test.dart › server error saat boot: sesi TIDAK dihapus, pakai profil tersimpan | Sesuai |
| AUT-21 | Peran (mobile) | Layar sesuai peran | V | login sebagai admin / sopir / pelanggan | admin → panel admin, sopir → panel sopir, pelanggan → beranda pelanggan | Flutter | sopir_model_test.dart › pemilihan layar per peran admin, sopir, dan pelanggan mendapat layar masing-masing | Sesuai |

### Hak akses

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| HAK-01 | Pembatasan peran | Pelanggan/sopir mengakses endpoint admin | K | semua endpoint /api/admin/* dengan token pelanggan atau sopir | 403 (tamu: 401) | PHPUnit | AdminApiTest::test_every_admin_endpoint_is_closed_to_customer_and_driver_and_guest; AdminMasterApiTest::test_new_admin_endpoints_are_closed_to_customer_and_driver | Sesuai |
| HAK-02 | Pembatasan peran | Admin/pelanggan mengakses endpoint sopir | K | semua endpoint /api/sopir/* | 403 (tamu: 401) | PHPUnit | SopirApiTest::test_sopir_endpoints_are_closed_to_other_roles_and_to_unapproved_drivers | Sesuai |
| HAK-03 | Pembatasan peran | Admin/sopir mengakses endpoint pelanggan | K | GET /api/bookings dengan token admin atau sopir | 403; pelanggan: 200 | PHPUnit | StaffApiAuthTest::test_customer_booking_endpoints_are_closed_to_staff | Sesuai |
| HAK-04 | Pembatasan peran | Peran diperiksa sebelum data dicari | K | non-admin menebak id yang tidak ada di /api/admin/detail/{id}/... | selalu 403 (bukan 404 yang membocorkan keberadaan id) | PHPUnit | AdminMasterApiTest::test_new_admin_endpoints_are_closed_to_customer_and_driver | Sesuai |
| HAK-05 | Pembatasan peran | Akun dinonaktifkan / persetujuan dicabut setelah login | K | token yang sudah terbit dipakai setelah akun dinonaktifkan atau persetujuan sopir dicabut | 403 pada permintaan berikutnya | PHPUnit | StaffApiAuthTest::test_api_role_middleware_allows_only_the_listed_role_and_always_returns_json; SopirApiTest::test_sopir_endpoints_are_closed_to_other_roles_and_to_unapproved_drivers | Sesuai |
| HAK-06 | Kepemilikan data | Pelanggan membuka/membatalkan/membayar pesanan orang lain | K | id pesanan milik pelanggan lain | 404; daftar pesanan tidak memuat milik orang lain | PHPUnit | BookingApiRulesTest::test_customer_cannot_read_or_cancel_someone_elses_booking; SecurityTest::test_customer_cannot_open_or_pay_someone_elses_booking | Sesuai |
| HAK-07 | Kepemilikan data | Sopir menjawab tawaran milik sopir lain | K | id tawaran milik sopir lain | ditolak (409); tawaran tidak berubah | PHPUnit | SopirApiTest::test_accept_wins_once_and_the_loser_gets_409; StaffApiAuthTest::test_driver_cannot_answer_someone_elses_offer | Sesuai |
| HAK-08 | Kepemilikan data | Sopir melapor kondisi unit sopir lain | K | id unit milik sopir lain | 404 | PHPUnit | SopirApiTest::test_condition_report_rules | Sesuai |
| HAK-09 | Kerahasiaan | Sopir tidak melihat data keuangan | K | GET /api/sopir/tawaran | respons tanpa harga, DP, atau total | PHPUnit | SopirApiTest::test_tawaran_lists_only_own_open_offers_without_financial_data | Sesuai |

### Pemesanan

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| PSN-01 | Estimasi biaya | Sewa kurang dari / tepat 24 jam | B | 5 jam dan 24 jam | dihitung 1 hari (tarif × 1) | PHPUnit | BookingApiRulesTest::test_estimate_rounds_up_per_24_hours_with_a_minimum_of_one_day_and_dp_is_half | Sesuai |
| PSN-02 | Estimasi biaya | Sewa lewat 24 jam | B | 25 jam dan 72 jam | dibulatkan ke atas per 24 jam: 2 hari dan 3 hari | PHPUnit | BookingApiRulesTest::test_estimate_rounds_up_per_24_hours_with_a_minimum_of_one_day_and_dp_is_half | Sesuai |
| PSN-03 | Estimasi biaya | Uang muka | V | total biaya | DP = 50% total (dibulatkan ke bawah) | PHPUnit | BookingApiRulesTest::test_estimate_rounds_up_per_24_hours_with_a_minimum_of_one_day_and_dp_is_half; BookingApiRulesTest::test_estimate_sums_multiple_cars_and_odd_totals_round_dp_down | Sesuai |
| PSN-04 | Estimasi biaya | Dengan sopir | V | pakai_supir = 1 | memakai tarif 'dengan sopir' (all-in) | PHPUnit | BookingApiRulesTest::test_estimate_rounds_up_per_24_hours_with_a_minimum_of_one_day_and_dp_is_half; BookingApiRulesTest::test_booking_with_driver_uses_all_in_price | Sesuai |
| PSN-05 | Estimasi biaya | Beberapa mobil dalam satu pesanan | V | dua mobil | total = jumlah subtotal tiap mobil | PHPUnit | BookingApiRulesTest::test_estimate_sums_multiple_cars_and_odd_totals_round_dp_down | Sesuai |
| PSN-06 | Buat pesanan | Pesanan valid | V | mobil tersedia, tanggal ≥ 3 jam ke depan, kembali setelah sewa, asal kota, jaminan | 201; status Booked; total dan DP benar; tagihan DP berstatus belum bayar | PHPUnit | BookingApiRulesTest::test_valid_booking_is_created_with_total_dp_and_a_payment_due_date; AdminApiTest::test_full_flow_customer_orders_admin_verifies_assigns_pickup_and_return_with_late_fee | Sesuai |
| PSN-07 | Buat pesanan | Jatuh tempo DP — sewa masih jauh | B | mulai sewa 10 hari lagi | jatuh tempo 4 hari dari sekarang | PHPUnit | BookingApiRulesTest::test_valid_booking_is_created_with_total_dp_and_a_payment_due_date | Sesuai |
| PSN-08 | Buat pesanan | Jatuh tempo DP — sewa dekat | B | mulai sewa 10 jam lagi | jatuh tempo 2 jam sebelum jam mulai sewa | PHPUnit | BookingApiRulesTest::test_payment_due_date_is_two_hours_before_pickup_when_pickup_is_soon | Sesuai |
| PSN-09 | Validasi tanggal | Mulai sewa kurang dari 3 jam | B | mulai +2 jam (ditolak); +4 jam (diterima) | 422 pada +2 jam; 201 pada +4 jam | PHPUnit | BookingApiRulesTest::test_pickup_less_than_three_hours_from_now_is_rejected | Sesuai |
| PSN-10 | Validasi tanggal | Tanggal kembali tidak setelah tanggal sewa | T | kembali sebelum atau sama dengan sewa | 422 pada tanggal_kembali | PHPUnit | BookingApiRulesTest::test_return_date_must_be_after_pickup | Sesuai |
| PSN-11 | Validasi masukan | Kolom tidak valid | T | mobils kosong; jaminan 9; asal_kota 7; kota lain tanpa nama kota; pakai_supir 2; mobil_id tidak ada | 422 dengan galat per kolom; tidak ada pesanan tersimpan | PHPUnit | BookingApiRulesTest::test_invalid_fields_are_rejected_with_field_errors | Sesuai |
| PSN-12 | Validasi masukan | Mobil yang sama dua kali dalam satu pesanan | T | dua item dengan mobil_id sama | 422 | PHPUnit | BookingApiRulesTest::test_same_car_twice_in_one_request_is_rejected | Sesuai |
| PSN-13 | Validasi profil | Profil pelanggan belum lengkap | T | alamat/no HP/asal kota kosong | 422 dengan kode INCOMPLETE_PROFILE; tidak ada pesanan | PHPUnit | BookingApiRulesTest::test_incomplete_profile_cannot_order_and_gets_a_machine_readable_code | Sesuai |
| PSN-14 | Ketersediaan | Mobil rusak / maintenance / belum disetujui | T | memesan mobil dengan status tersebut | 422, pesan mobil tidak tersedia | PHPUnit | BookingApiRulesTest::test_unavailable_cars_cannot_be_booked | Sesuai |
| PSN-15 | Ketersediaan | Jadwal bentrok | T | periode bersinggungan di tengah, atau menutupi seluruh periode pesanan lain | 422 | PHPUnit | BookingApiRulesTest::test_overlapping_dates_are_rejected_but_adjacent_and_other_periods_are_allowed; BookingAvailabilityTest::test_overlap_is_rejected_and_cancel_frees_the_car | Sesuai |
| PSN-16 | Ketersediaan | Periode lain untuk mobil yang sama | V | sebelum atau sesudah periode yang sudah dipesan | 201 (mobil yang dipesan untuk periode lain tetap bisa disewa) | PHPUnit | BookingApiRulesTest::test_overlapping_dates_are_rejected_but_adjacent_and_other_periods_are_allowed | Sesuai |
| PSN-17 | Atomik | Satu mobil bentrok dalam pesanan banyak mobil | T | dua mobil, salah satu bentrok | 422 dan tidak ada pesanan sebagian tersimpan | PHPUnit | BookingApiRulesTest::test_one_conflicting_car_rejects_the_whole_order_atomically | Sesuai |
| PSN-18 | Form (mobile) | Pesan dari aplikasi pelanggan | V | isi form dan tekan Pesan | pesanan tersimpan, tombol tidak berputar terus, pesanan muncul di tab Pesanan (Booked) | Manual | Skenario A1–A3 dan B1 (lihat daftar skenario manual) | Sesuai |

### Pembayaran

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| BYR-01 | Unggah bukti | Bukti DP valid | V | foto JPG/PNG ≤ 5 MB | 200; DP berstatus menunggu verifikasi; file tersimpan | PHPUnit | PembayaranUploadApiTest::test_valid_proof_moves_dp_to_waiting_verification_and_is_stored | Sesuai |
| BYR-02 | Unggah bukti | Metode pembayaran | V | metode 2 (transfer); tanpa metode | tersimpan metode 2; bawaan 3 (QRIS) bila tidak diisi | PHPUnit | PembayaranUploadApiTest::test_valid_proof_moves_dp_to_waiting_verification_and_is_stored; PembayaranUploadApiTest::test_default_method_is_qris_when_not_given | Sesuai |
| BYR-03 | Unggah bukti | Berkas tidak valid | T | PDF; GIF; > 5 MB; tanpa berkas; metode 9 | 422 per kolom; status dan penyimpanan tidak berubah | PHPUnit | PembayaranUploadApiTest::test_invalid_files_are_rejected_and_nothing_changes | Sesuai |
| BYR-04 | Unggah bukti | Ukuran tepat 5 MB | B | berkas 5120 KB | diterima | PHPUnit | PembayaranUploadApiTest::test_exactly_5_mb_is_accepted | Sesuai |
| BYR-05 | Unggah bukti | Waktu atau status tidak mengizinkan | T | DP sudah lunas; lewat jatuh tempo; pesanan dibatalkan | 422 dengan alasan yang jelas | PHPUnit | PembayaranUploadApiTest::test_upload_is_blocked_once_dp_is_confirmed_or_overdue_or_booking_is_canceled | Sesuai |
| BYR-06 | Unggah bukti | Unggah pada pesanan orang lain | K | id pesanan milik pelanggan lain | 404 | PHPUnit | PembayaranUploadApiTest::test_someone_elses_booking_is_not_found | Sesuai |
| BYR-07 | Unggah bukti | Unggah ulang saat masih menunggu | V | dua kali unggah berturut-turut | bukti diganti dan file lama dihapus (tidak ada file yatim) | PHPUnit | PembayaranUploadApiTest::test_reupload_while_waiting_replaces_the_proof_and_deletes_the_old_file | Sesuai |
| BYR-08 | Unggah bukti | Unggah ulang setelah ditolak admin | V | admin menolak, pelanggan mengunggah lagi | diterima; status kembali menunggu verifikasi | PHPUnit | PembayaranUploadApiTest::test_customer_can_upload_again_after_admin_rejects | Sesuai |
| BYR-09 | Pelunasan | Unggah bukti pelunasan setelah DP lunas | V | pembayaran_id pelunasan | 200; pelunasan menunggu verifikasi | PHPUnit | CancelAndPelunasanTest::test_customer_uploads_pelunasan_proof_via_api_after_dp_paid | Sesuai |
| BYR-10 | Pelunasan | Pelunasan saat DP belum lunas | T | unggah bukti pelunasan sebelum DP diverifikasi | 422, pesan selesaikan DP terlebih dahulu | PHPUnit | CancelAndPelunasanTest::test_pelunasan_upload_rejected_while_dp_not_verified | Sesuai |
| BYR-11 | Validasi di perangkat | Format dan ukuran bukti dicek sebelum diunggah | B | PDF/GIF/HEIC; tanpa ekstensi; kosong; tepat 5 MB; 5 MB + 1 byte | ditolak dengan pesan jelas di perangkat; tepat 5 MB diterima | Flutter | customer_fixes_test.dart › BuktiValidator (aturan sama dengan server: jpg/png, maks 5 MB) … | Sesuai |

### Pembatalan

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| BTL-01 | Oleh pelanggan | Batal saat DP belum dibayar | V | pelanggan membatalkan pesanan Booked tanpa DP lunas | pesanan Canceled; mobil dan sopir dibebaskan | PHPUnit | CancelAndPelunasanTest::test_customer_cancels_own_booking_only_while_dp_unpaid_and_resources_are_released | Sesuai |
| BTL-02 | Oleh pelanggan | Batal setelah DP lunas | T | pelanggan membatalkan setelah DP diverifikasi | ditolak; admin masih dapat membatalkan | PHPUnit | CancelAndPelunasanTest::test_customer_cannot_cancel_after_dp_verified_but_admin_can | Sesuai |
| BTL-03 | Status | Pesanan sedang berjalan | T | membatalkan pesanan Ongoing | ditolak | PHPUnit | CancelAndPelunasanTest::test_ongoing_booking_cannot_be_canceled | Sesuai |
| BTL-04 | Otomatis | DP melewati jatuh tempo | B | pesanan dengan DP belum dibayar dan lewat tempo; pesanan yang tidak lewat tempo | hanya yang lewat tempo dibatalkan oleh penjadwal | PHPUnit | CancelAndPelunasanTest::test_scheduler_cancels_only_overdue_unpaid_bookings | Sesuai |
| BTL-05 | Oleh admin | Batal pesanan Booked, lalu batal lagi | V/T | dua kali POST /admin/bookings/{id}/cancel | pertama 200 (Canceled); kedua 422; id tidak ada 404 | PHPUnit | AdminApiTest::test_admin_can_cancel_a_booked_order_but_not_twice | Sesuai |

### Admin

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| ADM-01 | Daftar pesanan | Filter, pencarian, paginasi | V | status=3; q=nama / no HP / #id; per_page=1; status=9 | hasil sesuai; meta halaman benar; status tidak valid 422 | PHPUnit | AdminApiTest::test_booking_list_filters_by_status_and_search_and_paginates | Sesuai |
| ADM-02 | Dashboard | Hitungan yang perlu ditindak | V | pesanan masuk, DP menunggu verifikasi, unit bersopir belum ditugaskan | angka sesuai; setelah ditawarkan tidak lagi dihitung 'perlu penugasan' | PHPUnit | AdminApiTest::test_dashboard_counts_what_needs_action | Sesuai |
| ADM-03 | Verifikasi DP | Verifikasi bukti DP | V | POST /admin/pembayaran/{id}/verifikasi pada DP menunggu | DP lunas; tagihan pelunasan terbentuk sekali; pencatat tersimpan; invoice terkirim | PHPUnit | AdminApiTest::test_full_flow_customer_orders_admin_verifies_assigns_pickup_and_return_with_late_fee | Sesuai |
| ADM-04 | Verifikasi DP | Tolak bukti DP | V | tolak dengan alasan | DP kembali belum bayar, bukti terhapus, alasan tersimpan; pelanggan dapat unggah ulang | PHPUnit | AdminApiTest::test_tolak_resets_payment_and_requires_waiting_status | Sesuai |
| ADM-05 | Verifikasi DP | Verifikasi/tolak saat tidak menunggu verifikasi | T | verifikasi atau tolak pada pembayaran yang sudah ditolak | 422 | PHPUnit | AdminApiTest::test_tolak_resets_payment_and_requires_waiting_status | Sesuai |
| ADM-06 | Pembayaran langsung | Tandai lunas tunai | V | metode 1/2/3 pada pembayaran belum bayar | lunas; pencatat tersimpan | PHPUnit | AdminApiTest::test_cash_payment_validates_method_and_pelunasan_needs_dp_first; CancelAndPelunasanTest::test_admin_can_mark_cash_payments_paid_and_pelunasan_needs_dp_first | Sesuai |
| ADM-07 | Pembayaran langsung | Metode tidak valid | T | metode 9 | 422 | PHPUnit | AdminApiTest::test_cash_payment_validates_method_and_pelunasan_needs_dp_first | Sesuai |
| ADM-08 | Pembayaran langsung | Pelunasan sebelum DP lunas / pesanan dibatalkan | T | tandai lunas pelunasan saat DP belum lunas; pada pesanan Canceled | 422 | PHPUnit | AdminApiTest::test_cash_payment_validates_method_and_pelunasan_needs_dp_first; CancelAndPelunasanTest::test_cannot_mark_paid_for_canceled_booking | Sesuai |
| ADM-09 | Layar (mobile) | Aksi pesanan mengikuti keadaan | V | pesanan Booked/Ongoing/Done, DP lunas/belum | tombol yang tampil dan terkunci sesuai keadaan; aksi tak dapat dibatalkan meminta konfirmasi | Flutter | admin_screens_test.dart › PesananDetailScreen: aksi mengikuti keadaan pesanan … | Sesuai |
| ADM-10 | Layar (mobile) | Verifikasi dari lembar pembayaran | V | ketuk pembayaran menunggu, Verifikasi, konfirmasi | permintaan terkirim hanya setelah konfirmasi | Flutter | admin_screens_test.dart › pembayaran menunggu verifikasi: buka lembar, verifikasi terkirim setelah konfirmasi | Sesuai |

### Penugasan sopir

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| PGS-01 | Tawaran | Siapa yang ditawari | V | tugaskan unit bersopir | hanya sopir tersedia, disetujui, dan aktif; nilai pakai_supir tidak ditimpa | PHPUnit | StaffApiAuthTest::test_tawarkan_only_offers_available_approved_active_drivers_and_keeps_pakai_supir | Sesuai |
| PGS-02 | Tawaran | Unit sewa lepas kunci | T | tugaskan unit tanpa sopir | 422 'Unit ini disewa tanpa sopir.' | PHPUnit | StaffApiAuthTest::test_tawarkan_rejects_a_unit_rented_without_driver; AdminApiTest::test_penugasan_rejected_for_self_drive_unit_and_when_no_driver | Sesuai |
| PGS-03 | Tawaran | Tidak ada sopir tersedia | T | semua sopir libur/bertugas | 422 'Tidak ada supir yang available.' | PHPUnit | StaffApiAuthTest::test_tawarkan_fails_when_no_driver_is_available | Sesuai |
| PGS-04 | Terima | Dua sopir menerima tawaran yang sama | B | sopir A lalu sopir B menerima | hanya satu menang; yang kalah mendapat 409; tawaran lain ditutup; sopir pemenang berstatus Bertugas | PHPUnit | StaffApiAuthTest::test_driver_can_accept_once_and_the_other_offer_is_closed; SopirApiTest::test_accept_wins_once_and_the_loser_gets_409 | Sesuai |
| PGS-05 | Tolak | Sopir menolak tawaran | V | POST /sopir/tawaran/{id}/tolak | status tawaran ditolak; unit tetap tanpa sopir; sopir yang menolak tidak ditawari ulang; ulang menolak 409 | PHPUnit | SopirApiTest::test_reject_marks_offer_and_cannot_be_repeated; StaffApiAuthTest::test_driver_reject_marks_offer_rejected_and_is_not_reoffered | Sesuai |
| PGS-06 | Terima | Sopir belum disetujui | K | terima oleh sopir status_approval ≠ 1 | ditolak | PHPUnit | StaffApiAuthTest::test_unapproved_driver_cannot_accept | Sesuai |
| PGS-07 | Tampilan sopir | Daftar tawaran sopir | V | GET /sopir/tawaran | hanya tawaran terbuka milik sendiri; yang sudah diambil sopir lain hilang | PHPUnit | SopirApiTest::test_tawaran_lists_only_own_open_offers_without_financial_data | Sesuai |
| PGS-08 | Layar sopir (mobile) | Terima dan tolak dengan konfirmasi | V | ketuk Terima / Tolak | dialog konfirmasi; batal tidak mengirim; konfirmasi mengirim ke endpoint yang tepat | Flutter | sopir_screens_test.dart › TawaranScreen terima: konfirmasi dulu, baru kirim ke server; sopir_screens_test.dart › TawaranScreen batal pada dialog: tidak ada permintaan | Sesuai |
| PGS-09 | Layar sopir (mobile) | Kalah bersaing (409) | B | tawaran sudah diambil sopir lain | pesan dari server dan daftar disegarkan sehingga tawaran basi hilang | Flutter | sopir_provider_test.dart › terima kalah bersaing (409): pesan dari server dan daftar disegarkan | Sesuai |
| PGS-10 | Status sopir | Ubah status tersedia/libur | V/T | status 0, 1, 2; saat sedang bertugas | 0 dan 1 diterima; 2 ditolak; saat bertugas status terkunci (422) | PHPUnit | SopirApiTest::test_status_toggle_is_locked_while_on_duty_and_validated | Sesuai |

### Jemput

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| JMP-01 | Konfirmasi jemput | DP belum terverifikasi | T | jemput sebelum DP lunas | 422 'DP belum diverifikasi…'; status tidak berubah | PHPUnit | StaffFlowTest::test_pickup_requires_verified_dp_then_pengembalian_charges_late_fee_and_frees_supir; AdminApiTest::test_full_flow_customer_orders_admin_verifies_assigns_pickup_and_return_with_late_fee | Sesuai |
| JMP-02 | Konfirmasi jemput | Jemput setelah DP lunas | V | PATCH /admin/detail/{id}/jemput | unit dan pesanan Ongoing; mobil Disewa | PHPUnit | StaffFlowTest::test_pickup_requires_verified_dp_then_pengembalian_charges_late_fee_and_frees_supir | Sesuai |
| JMP-03 | Konfirmasi jemput | Jemput dua kali | T | jemput unit yang sudah Ongoing | 422 | PHPUnit | StaffFlowTest::test_pickup_requires_verified_dp_then_pengembalian_charges_late_fee_and_frees_supir | Sesuai |
| JMP-04 | Layar (mobile) | Tombol jemput terkunci saat DP belum lunas | V | pesanan dengan DP belum lunas | tombol 'Jemput (DP belum lunas)' nonaktif | Flutter | admin_screens_test.dart › PesananDetailScreen … DP belum lunas: jemput terkunci dengan alasan di label | Sesuai |

### Laporan kondisi

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| KND-01 | Oleh sopir | Laporan sebelum sewa dengan foto | V | jenis 1, kondisi 1, foto JPG | 201; foto tersimpan; tampil di tugas sopir | PHPUnit | SopirApiTest::test_driver_reports_condition_with_photo_and_resending_replaces_it | Sesuai |
| KND-02 | Oleh sopir | Kirim ulang jenis yang sama | V | dua kali kirim jenis 1 | diperbarui (bukan dobel); foto lama dihapus | PHPUnit | SopirApiTest::test_driver_reports_condition_with_photo_and_resending_replaces_it | Sesuai |
| KND-03 | Aturan | Laporan sesudah sebelum dijemput | T | jenis 2 pada unit berstatus Booked | 422 | PHPUnit | SopirApiTest::test_condition_report_rules; AdminMasterApiTest::test_admin_reports_condition_for_self_drive_unit_and_lists_it | Sesuai |
| KND-04 | Aturan | Unit dibatalkan / sudah selesai | T | laporan sebelum pada unit Canceled atau Done | 422 | PHPUnit | SopirApiTest::test_condition_report_rules | Sesuai |
| KND-05 | Validasi | Masukan tidak valid | T | jenis 3; kondisi 9; foto PDF | 422 per kolom | PHPUnit | SopirApiTest::test_condition_report_rules | Sesuai |
| KND-06 | Oleh admin | Admin melapor untuk unit lepas kunci | V | jenis 1 lalu 2 pada unit Ongoing | 201; tampil di detail pesanan (laporan_sebelum/sesudah) | PHPUnit | AdminMasterApiTest::test_admin_reports_condition_for_self_drive_unit_and_lists_it | Sesuai |
| KND-07 | Layar (mobile) | Lecet/rusak wajib dijelaskan | V/T | kondisi lecet tanpa catatan; kondisi baik | lecet tanpa catatan ditolak di perangkat (tanpa permintaan); baik terkirim dengan field benar | Flutter | sopir_screens_test.dart › LaporanKondisiScreen kondisi lecet tanpa catatan: ditolak di klien, tidak ada permintaan; sopir_screens_test.dart › LaporanKondisiScreen kondisi baik: terkirim dengan field yang benar | Sesuai |
| KND-08 | Layar (mobile) | Tombol laporan mengikuti status unit | V | unit Booked / Ongoing | Booked: hanya 'sebelum'; Ongoing: keduanya | Flutter | sopir_screens_test.dart › TugasScreen booked: laporan sebelum aktif, sesudah dikunci; sopir_screens_test.dart › TugasScreen ongoing: keduanya aktif… | Sesuai |

### Pengembalian

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| KMB-01 | Denda | Batas keterlambatan per jam yang dimulai | B | lebih awal 5 jam; tepat waktu; telat 1, 59, 60, 61, 90 menit; 25 jam | denda 0, 0, 1, 1, 1, 2, 2, 25 jam (× Rp50.000) | PHPUnit | PengembalianRulesTest::test_late_fee_boundaries_per_started_hour | Sesuai |
| KMB-02 | Denda | Denda masuk tagihan pelunasan | V | telat 90 menit; tepat waktu | pelunasan bertambah Rp100.000; tepat waktu tidak berubah | PHPUnit | PengembalianRulesTest::test_late_fee_is_added_to_the_unpaid_settlement_and_on_time_does_not_change_it | Sesuai |
| KMB-03 | Denda | Pelunasan sudah lunas lalu terlambat | B | telat 120 menit pada pesanan yang pelunasannya lunas | tagihan denda terpisah (Rp100.000, belum bayar) | PHPUnit | PengembalianRulesTest::test_late_fee_creates_a_separate_bill_when_settlement_is_already_paid | Sesuai |
| KMB-04 | Status | Kondisi mobil saat kembali | V | kondisi baik; kondisi rusak | baik → Maintenance (dicek dulu); rusak → Rusak; sopir kembali Tersedia | PHPUnit | PengembalianRulesTest::test_car_status_depends_on_condition_and_supir_is_freed | Sesuai |
| KMB-05 | Status | Pesanan banyak unit | B | kembalikan unit pertama, lalu kedua | pesanan Done hanya setelah semua unit kembali | PHPUnit | PengembalianRulesTest::test_order_is_done_only_when_every_unit_is_returned | Sesuai |
| KMB-06 | Validasi | Masukan tidak valid | T | waktu sebelum jadwal sewa; tanggal bukan tanggal; kondisi 5; tanpa tanggal | 422; tidak ada data berubah | PHPUnit | PengembalianRulesTest::test_rejections_leave_everything_unchanged | Sesuai |
| KMB-07 | Status | Dikembalikan dua kali / belum dijemput | T | kembalikan unit yang sudah kembali; unit berstatus Booked | 422; hanya satu catatan pengembalian | PHPUnit | PengembalianRulesTest::test_rejections_leave_everything_unchanged; PengembalianRulesTest::test_unit_not_yet_picked_up_cannot_be_returned; StaffFlowTest::test_pengembalian_rejected_when_unit_not_picked_up | Sesuai |
| KMB-08 | Jejak | Pencatat tersimpan | K | admin mencatat pengembalian | kolom dicatat_oleh berisi id admin | PHPUnit | PengembalianRulesTest::test_return_is_recorded_with_the_admin_who_made_it | Sesuai |
| KMB-09 | Layar (mobile) | Pratinjau denda dan konfirmasi | V | jadwal sudah lewat; jadwal masih jauh; kondisi rusak | terlambat: pratinjau denda; tepat waktu: tanpa denda; rusak: peringatan; kirim setelah konfirmasi ringkasan | Flutter | admin_screens_test.dart › PengembalianFormScreen … | Sesuai |
| KMB-10 | Layar (mobile) | Pratinjau denda sama dengan aturan server | B | selisih 0, 1, 60, 61, 90 menit | 0, 1, 1, 2, 2 jam | Flutter | admin_model_test.dart › DendaKeterlambatan (pratinjau, aturan sama dengan server) … | Sesuai |

### Armada

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| ARM-01 | Tambah mobil | Tambah mobil valid | V | model, plat 'AB 1234 CD', merk, tahun, harga | 201; langsung aktif dan tersedia (tanpa persetujuan Super Admin) | PHPUnit | AdminMasterApiTest::test_mobil_create_is_live_immediately_and_validates_plate_and_uniqueness | Sesuai |
| ARM-02 | Tambah mobil | Plat tidak valid atau kembar | T | plat kembar; 'ab-1234'; tahun 1800; harga negatif | 422 per kolom | PHPUnit | AdminMasterApiTest::test_mobil_create_is_live_immediately_and_validates_plate_and_uniqueness | Sesuai |
| ARM-03 | Ubah mobil | Ubah data, cari, filter, ganti gambar | V | ubah harga; cari merk/plat; filter status; ganti gambar | data berubah; plat milik sendiri tidak dianggap kembar; gambar lama dihapus; status tidak valid 422 | PHPUnit | AdminMasterApiTest::test_mobil_update_search_filter_and_image_replace | Sesuai |
| ARM-04 | Status | Ubah status mobil | V/T | tersedia ↔ maintenance; mobil disewa; mobil punya pesanan aktif | diterima; disewa dan berpesanan aktif ditolak; status 'disewa' tidak dapat diatur manual | PHPUnit | AdminMasterApiTest::test_mobil_status_rules_and_delete_protection | Sesuai |
| ARM-05 | Hapus mobil | Hapus mobil | V/T | mobil belum pernah disewa; mobil dengan riwayat sewa | bersih: terhapus; berriwayat: 422 (disarankan ubah status) | PHPUnit | AdminMasterApiTest::test_mobil_status_rules_and_delete_protection | Sesuai |
| ARM-06 | Form (mobile) | Validasi form armada | V/T | plat 'ab-1234'; model belum dipilih; isian valid | plat salah dan model kosong ditolak di perangkat tanpa permintaan; valid terkirim dengan plat huruf besar | Flutter | admin_screens_test.dart › mobil: plat salah ditolak di klien tanpa permintaan; admin_screens_test.dart › mobil: isian valid terkirim dengan plat huruf besar | Sesuai |

### Sopir (admin)

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| SPA-01 | Tambah sopir | Sopir baru langsung dapat login | V | nama, email, sandi, HP, alamat | 201; aktif dan disetujui; dapat login sebagai peran 3 | PHPUnit | AdminMasterApiTest::test_new_driver_can_log_in_immediately_and_deactivation_revokes_access | Sesuai |
| SPA-02 | Tambah sopir | Email kembar | T | email yang sudah dipakai | 422 | PHPUnit | AdminMasterApiTest::test_new_driver_can_log_in_immediately_and_deactivation_revokes_access | Sesuai |
| SPA-03 | Nonaktifkan | Nonaktifkan sopir | V | aktif=false | token dicabut; tidak dapat login lagi | PHPUnit | AdminMasterApiTest::test_new_driver_can_log_in_immediately_and_deactivation_revokes_access | Sesuai |
| SPA-04 | Nonaktifkan | Sopir sedang bertugas | T | nonaktifkan atau ubah status saat bertugas | 422; ubah data lain tetap boleh | PHPUnit | AdminMasterApiTest::test_driver_on_duty_cannot_be_deactivated_or_have_status_changed_and_list_filters_work | Sesuai |
| SPA-05 | Form (mobile) | Kata sandi sopir baru | B | kata sandi 3 karakter | ditolak di perangkat ('Minimal 8 karakter') tanpa permintaan | Flutter | admin_screens_test.dart › form master sopir baru: kata sandi kurang dari 8 karakter ditolak di klien | Sesuai |

### Penyewa

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| PNY-01 | CRUD | Tambah, ubah, cari penyewa | V | nama, email, HP, alamat, asal kota | 201 / 200; hanya akun peran 4 yang dapat diakses; penyewa tanpa kata sandi yang diketahui | PHPUnit | AdminMasterApiTest::test_penyewa_crud_is_limited_to_customers | Sesuai |
| PNY-02 | CRUD | Email kembar / akun staf | T | email sudah ada; id akun admin | 422; akun staf → 404 | PHPUnit | AdminMasterApiTest::test_penyewa_crud_is_limited_to_customers | Sesuai |
| PNY-03 | Form (mobile) | Validasi email | T/V | 'bukan-email'; email valid | ditolak di perangkat; valid terkirim | Flutter | admin_screens_test.dart › form master penyewa: email tidak valid ditolak; isian valid terkirim | Sesuai |

### Rekap

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| REK-01 | Rekap bulanan | Perhitungan rekap | V | bulan dengan 1 pesanan selesai, 1 dibatalkan, pembayaran lunas dan belum, denda | pesanan dibatalkan tidak dihitung sebagai sewa; sewa 2 hari = 2 hari; pendapatan hanya dari pembayaran lunas; bulan lain tidak ikut | PHPUnit | AdminMasterApiTest::test_rekap_counts_the_month_correctly_excluding_canceled_and_using_real_rental_days | Sesuai |
| REK-02 | Rekap bulanan | Bulan tidak valid | T | bulan=2026-13 | 422; tanpa parameter = bulan berjalan | PHPUnit | AdminMasterApiTest::test_rekap_counts_the_month_correctly_excluding_canceled_and_using_real_rental_days | Sesuai |

### Mobile

| ID | Fitur | Skenario | Kelas | Masukan | Hasil yang diharapkan | Cara | Bukti | Hasil |
|---|---|---|:-:|---|---|---|---|:-:|
| MOB-01 | Respons server | Angka berformat tidak terduga | T | angka sebagai string atau desimal ('500000.00'), field hilang, elemen daftar bukan objek | terbaca tanpa crash (angka desimal terbaca benar) | Flutter | admin_model_test.dart › angka sebagai string/double dan field hilang tidak membuat crash; admin_model_test.dart › MobilModel kini tahan angka berupa string (harga desimal dari server); sopir_model_test.dart › angka sebagai string atau double tidak membuat crash; sopir_model_test.dart › field hilang atau null memakai nilai bawaan, tidak melempar | Sesuai |
| MOB-02 | Respons server | Respons rusak / bukan JSON | T | HTML atau data bukan daftar | keadaan galat dengan pesan; layar tidak macet di loading | Flutter | admin_provider_test.dart › pemuatan dan paginasi respons rusak tidak melempar dan tidak macet di loading; sopir_provider_test.dart › respons rusak (bukan JSON/format salah) tidak melempar | Sesuai |
| MOB-03 | Respons server | Respons lama datang terlambat | B | filter berganti / pindah pesanan / logout saat permintaan berjalan | hasil lama dibuang; hasil terbaru bertahan; data pengguna lama tidak muncul setelah logout | Flutter | admin_provider_test.dart › respons lama (filter sudah berganti) tidak menimpa hasil terbaru; admin_provider_test.dart › respons yang datang setelah reset (logout) dibuang; customer_fixes_test.dart › ganti filter saat muat lagi berjalan: hasil lama dibuang, tidak bercampur | Sesuai |
| MOB-04 | Respons server | Penyegaran latar belakang gagal | B | polling gagal saat data sudah tampil | data lama tetap tampil tanpa pesan galat | Flutter | admin_provider_test.dart › galat memuat: pesan tampil; penyegaran senyap tidak menghapus data lama; sopir_provider_test.dart › polling senyap: gagal tidak menghapus data lama dan tidak menampilkan galat | Sesuai |
| MOB-05 | Aksi | Klik ganda / permintaan berjalan | B | dua aksi berturut-turut sebelum yang pertama selesai | yang kedua ditolak ('masih diproses'); hanya satu permintaan terkirim | Flutter | admin_provider_test.dart › keamanan aksi aksi ganda ditolak selama permintaan pertama berjalan; sopir_provider_test.dart › aksi ganda ditolak selama permintaan pertama berjalan | Sesuai |
| MOB-06 | Aksi | Galat jaringan saat aksi | T | koneksi putus di tengah aksi | pesan galat dan tombol tidak terkunci selamanya | Flutter | admin_provider_test.dart › keamanan aksi galat jaringan: pesan galat dan tombol tidak terkunci selamanya; sopir_provider_test.dart › galat jaringan pada aksi: pesan ramah dan tombol tidak terkunci selamanya | Sesuai |
| MOB-07 | Keadaan layar | Daftar kosong dan galat memuat | V/T | tidak ada data; server error | pesan kosong yang jelas / pesan galat dengan tombol 'Coba lagi' (bukan layar putih) | Flutter | admin_screens_test.dart › daftar pesanan kosong: pesan jelas; admin_screens_test.dart › AdminHomeScreen galat dashboard: pesan dan coba lagi; sopir_screens_test.dart › kosong: pesan jelas, bukan layar putih; sopir_screens_test.dart › galat memuat: tombol coba lagi tampil | Sesuai |
| MOB-08 | Katalog | Muat halaman berikutnya gagal | B | halaman 2 gagal saat halaman 1 sudah tampil | daftar tidak hilang; tidak masuk layar galat penuh; dapat dicoba lagi saat digulir | Flutter | customer_fixes_test.dart › muat lagi gagal: daftar yang sudah ada TIDAK hilang dan tidak masuk layar galat | Sesuai |
| MOB-09 | Sesi | Data tidak terbawa antar akun | K | logout lalu login akun lain | daftar mobil, pesanan, dan data admin/sopir sebelumnya dibuang | Flutter | customer_fixes_test.dart › reset (logout) mengosongkan semuanya…; customer_fixes_test.dart › BookingProvider.reset (BUGS #40); sopir_provider_test.dart › reset membuang data pengguna sebelumnya; admin_provider_test.dart › keamanan aksi reset membuang data dan filter pengguna sebelumnya | Sesuai |
| MOB-10 | Polling | Penyegaran berkala | V | layar sopir/admin terbuka | dashboard dan tawaran/pembayaran disegarkan tiap 30 detik saat aplikasi terlihat; berhenti saat di latar belakang | Manual | Skenario B5–B6 (tawaran muncul setelah penyegaran) | Sesuai |

---

## 4. Skenario manual di perangkat

Dijalankan pengguna pada 7 Oktober 2026 dengan aplikasi terhubung ke server lokal, memakai akun contoh (pelanggan, admin, dan dua sopir). Hasil dikonfirmasi pengguna sesuai harapan. **Lampirkan tangkapan layar** untuk setiap langkah pada laporan akhir (kolom *Bukti* dikosongkan untuk diisi).

| ID | Jalur | Peran | Langkah | Hasil yang diharapkan | Hasil aktual | Bukti |
|---|---|---|---|---|:-:|---|
| A1 | A — tanpa sopir | Pelanggan | Login, buka Mobil, pilih mobil tersedia, tekan Pesan | Form pesanan terbuka | Sesuai | *(tangkapan layar)* |
| A2 | A — tanpa sopir | Pelanggan | Isi tanggal mulai (+1 hari) dan kembali (+3 hari), tanpa sopir, asal kota, jaminan | Ringkasan biaya: total = tarif × 2 hari, DP = 50% | Sesuai | *(tangkapan layar)* |
| A3 | A — tanpa sopir | Pelanggan | Tekan Pesan | Pesanan tersimpan; tab Pesanan: Booked dengan tagihan DP dan jatuh tempo | Sesuai | *(tangkapan layar)* |
| A4 | A — tanpa sopir | Pelanggan | Buka pembayaran, unggah foto bukti | DP berstatus Menunggu verifikasi; tombol unggah terkunci | Sesuai | *(tangkapan layar)* |
| A5 | A — tanpa sopir | Admin | Login, lihat Beranda | 'Pembayaran menunggu verifikasi' = 1; lencana tab Pembayaran = 1 | Sesuai | *(tangkapan layar)* |
| A6 | A — tanpa sopir | Admin | Tab Pembayaran, ketuk kartu | Lembar rincian dengan foto bukti, tombol Verifikasi dan Tolak | Sesuai | *(tangkapan layar)* |
| A7 | A — tanpa sopir | Admin | Verifikasi lalu konfirmasi | DP Lunas; tagihan pelunasan terbentuk | Sesuai | *(tangkapan layar)* |
| A8 | A — tanpa sopir | Admin | Buka detail pesanan | Unit 'Tanpa sopir'; tombol Konfirmasi jemput aktif; tidak ada tombol Tugaskan sopir | Sesuai | *(tangkapan layar)* |
| A9 | A — tanpa sopir | Pelanggan | Segarkan tab Pesanan | DP tampil Lunas | Sesuai | *(tangkapan layar)* |
| B1 | B — dengan sopir | Pelanggan | Pesan mobil lain dengan opsi dengan sopir | Harga memakai tarif dengan sopir (lebih tinggi) | Sesuai | *(tangkapan layar)* |
| B2 | B — dengan sopir | Pelanggan | Unggah bukti DP | Menunggu verifikasi | Sesuai | *(tangkapan layar)* |
| B3 | B — dengan sopir | Admin | Verifikasi DP | DP Lunas | Sesuai | *(tangkapan layar)* |
| B4 | B — dengan sopir | Admin | Buka detail pesanan | Sopir 'Belum ditugaskan'; tombol Tugaskan sopir; Beranda: unit perlu penugasan = 1 | Sesuai | *(tangkapan layar)* |
| B5 | B — dengan sopir | Admin | Tugaskan sopir lalu konfirmasi | 'Ditawarkan ke 2 sopir'; label 'Menunggu jawaban sopir...' | Sesuai | *(tangkapan layar)* |
| B6 | B — dengan sopir | Sopir 1 | Login, tab Tawaran | Lencana 1; kartu tanpa harga | Sesuai | *(tangkapan layar)* |
| B7 | B — dengan sopir | Sopir 1 | Terima lalu konfirmasi | Tugas pindah ke tab Tugas; status Bertugas, saklar terkunci | Sesuai | *(tangkapan layar)* |
| B8 | B — dengan sopir | Sopir 2 | Login, tab Tawaran | Tawaran sudah hilang; menerima memberi pesan sudah diambil | Sesuai | *(tangkapan layar)* |
| B9 | B — dengan sopir | Admin | Segarkan detail pesanan | Sopir tampil beserta nomornya | Sesuai | *(tangkapan layar)* |
| C1 | Cabang uji | Admin + Pelanggan | Tolak bukti dengan alasan; pelanggan melihat dan mengunggah ulang | DP kembali Belum bayar dengan alasan; unggah ulang diterima | Sesuai | *(tangkapan layar)* |
| C2 | Cabang uji | Admin | Pesanan yang DP-nya belum diverifikasi | Tombol 'Jemput (DP belum lunas)' terkunci | Sesuai | *(tangkapan layar)* |
| C3 | Cabang uji | Sopir 1 & 2 | Kedua sopir menolak tawaran | Admin: kembali 'Belum ditugaskan' dengan tombol Tugaskan; sopir yang menolak tidak ditawari ulang | Sesuai | *(tangkapan layar)* |
| D1 | Lanjutan | Sopir | Lapor sebelum (Baik) | Terkirim; 'Lapor sesudah' masih terkunci | Sesuai | *(tangkapan layar)* |
| D2 | Lanjutan | Admin | Konfirmasi jemput | Unit Berjalan; mobil Disewa di tab Armada | Sesuai | *(tangkapan layar)* |
| D3 | Lanjutan | Admin | Catat pengembalian lewat 90 menit dari jadwal | Pratinjau 'Terlambat 2 jam'; pesanan Selesai; mobil Maintenance; sopir Tersedia | Sesuai | *(tangkapan layar)* |
| D4 | Lanjutan | Admin | Tandai lunas pelunasan (tunai), buka Rekap bulanan | Pelunasan bertambah Rp100.000 (denda); rekap menghitung DP dan pelunasan lunas | Sesuai | *(tangkapan layar)* |

---

## 5. Temuan selama pengujian dan perbaikannya

| No | Temuan | Dampak | Ditemukan lewat | Perbaikan | Status |
|---:|---|---|---|---|---|
| 1 | Kolom 'keterangan' di tabel pembayarans tidak pernah dibuatkan migrasinya, padahal dipakai tagihan denda | Pengembalian terlambat pada pesanan yang pelunasannya sudah lunas gagal dengan galat 500 (juga di alur web) | Tes PengembalianRulesTest (Fase 6) | Migrasi 2026_10_07_000001_add_keterangan_to_pembayarans_table | Diperbaiki |
| 2 | Unggah ulang bukti saat status masih menunggu verifikasi meninggalkan file lama di server | File yatim menumpuk di penyimpanan | Tes PembayaranUploadApiTest (Fase 6) | File lama dihapus setelah bukti baru tersimpan | Diperbaiki |
| 3 | Parameter per_page katalog tidak dibatasi | Satu permintaan dapat memuat seluruh tabel | Pemeriksaan kode (Fase 6) | Dibatasi 1–50 | Diperbaiki |
| 4 | Token API tidak dicabut saat kata sandi diganti | Perangkat lain tetap login dengan kata sandi lama | Pemeriksaan kode (Fase 6) | Sesi lain dicabut, sesi yang dipakai tetap | Diperbaiki |
| 5 | Pembatas peran berjalan setelah pengikatan model | Non-admin yang menebak id mendapat 404 vs 403, membocorkan keberadaan data | Tes AdminMasterApiTest (Fase 3) | Middleware peran diprioritaskan sebelum pengikatan model | Diperbaiki |
| 6 | assignJobSupir menawarkan ke sopir belum disetujui dan menimpa pakai_supir menjadi 2 | Tugas sampai ke sopir yang tidak berhak; data jenis sewa rusak | Analisis kode (Fase 1) | PenugasanService: hanya sopir tersedia, disetujui, aktif; pakai_supir tidak ditimpa | Diperbaiki |
| 7 | ListTile di dalam kotak berlatar tanpa Material | Bagian Pembayaran dan Rekap di layar admin gagal tergambar (layar galat) | Tes widget (Fase 5) | Komponen BagianKartu berbasis Material | Diperbaiki |
| 8 | Angka desimal MySQL berbentuk string ('500000.00') terbaca 0 oleh aplikasi | Harga tampil Rp 0 atau tombol berputar terus | Tes model (Fase 5) | Parser angka toleran (asInt) | Diperbaiki |
| 9 | Respons lama (filter/pesanan/akun berganti) dapat menimpa data terbaru | Daftar bercampur atau menampilkan data pengguna sebelumnya | Tes provider (Fase 5–6) | Nomor generasi permintaan di semua provider daftar | Diperbaiki |
| 10 | Gagal memuat halaman berikutnya katalog menghapus seluruh daftar tampilan | Pelanggan kehilangan daftar mobil saat jaringan putus | Pemeriksaan kode dan tes (Fase 6, BUGS #45) | Daftar dipertahankan; galat penuh hanya untuk daftar kosong | Diperbaiki |
| 11 | Data provider pelanggan dan logout Google tidak dibersihkan saat logout | Data pengguna sebelumnya terlihat setelah ganti akun; akun Google terpilih otomatis | Pemeriksaan kode (Fase 6, BUGS #40) | reset() pada provider; GoogleSignIn.signOut() | Diperbaiki |
| 12 | Format dan ukuran bukti pembayaran tidak dicek di perangkat | Pengguna menunggu unggahan besar lalu ditolak server | Pemeriksaan kode (Fase 6, BUGS #44) | BuktiValidator (JPG/PNG, ≤ 5 MB) dan penanganan izin kamera/galeri | Diperbaiki |
| 13 | Rekap/laporan menghitung pesanan dibatalkan dan sewa sehari bernilai 0 | Angka laporan keliru | Analisis kode (Fase 3, BUGS #35) | RekapService (API). Laporan PDF di web belum diperbaiki | Diperbaiki (API) |
| 14 | Rute /api/register tidak terdaftar; atribut total_harga_rp tanpa accessor | Pendaftaran pelanggan dari aplikasi tidak bisa; atribut kosong pada serialisasi | Analisis kode (Fase 1 dan 6) | Rute didaftarkan dengan pembatasan laju; atribut dihapus | Diperbaiki |

---

## 6. Keterbatasan

1. Notifikasi memakai penyegaran berkala (30 detik) dan tarik-untuk-segarkan, bukan push/realtime; tawaran atau pembayaran baru dapat terlambat hingga 30 detik terlihat.
2. Pembayaran dicatat dengan bukti unggahan dan diverifikasi admin; tidak terhubung ke payment gateway. Nomor rekening/QRIS pada layar pembayaran masih placeholder (BUGS #26).
3. Invoice email tidak diuji ke penyedia email sungguhan: lingkungan pengembangan memakai MAIL_MAILER=log.
4. Aplikasi hanya diuji untuk Android; iOS tidak dibangun. Rilis produksi memerlukan HTTPS, alamat server tetap, dan penandatanganan rilis sendiri (BUGS #23).
5. Harga pada rincian unit dihitung dari tarif mobil saat ini, bukan tarif saat pemesanan; perubahan tarif memengaruhi tampilan pesanan lama.
6. Pesanan manual oleh admin (pelanggan datang langsung atau lewat WhatsApp) belum tersedia; admin mencatat penyewa tetapi pesanan dibuat oleh pelanggan lewat aplikasi.
7. Login Google dan sebagian modul web (hapus akun, verifikasi email, laporan PDF, daftar tanpa paginasi) berada di luar lingkup TA dan masih memiliki butir terbuka di BUGS.md.
8. Pengujian di perangkat dilakukan pada satu jaringan lokal dengan data contoh; belum diuji beban atau banyak pengguna bersamaan, selain uji rebutan tugas dan pesanan bentrok di tingkat basis data.

---

## 7. Cara menjalankan ulang pengujian

```powershell
# Basis data uji harus aktif (Laragon: Start All)
cd D:\Joki\TA-mobile-rental_harkat
php artisan test            # tes API (abaikan 14 tes bawaan Breeze: Auth\*, ProfileTest)
cd mobile_app
flutter test                # tes aplikasi
flutter analyze             # analisis statis
```
Koleksi Postman untuk uji manual API ada di `docs/postman/Harkat-API.postman_collection.json`.

