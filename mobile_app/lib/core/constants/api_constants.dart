// ─────────────────────────────────────────────────────────────
//  API Constants — Harkat Rental Mobile
//  Update BASE_URL to your Laravel server IP/domain.
// ─────────────────────────────────────────────────────────────

class ApiConstants {
  // ⚠️ IP komputer ini di jaringan WiFi yang sama dengan HP
  // Jalankan: ipconfig | findstr IPv4  untuk cek IP terbaru
  static const String baseUrl = 'http://192.168.101.91:8000';

  static const String apiPrefix = '$baseUrl/api';

  // Auth
  static const String login         = '$apiPrefix/login';
  static const String logout        = '$apiPrefix/logout';
  static const String me            = '$apiPrefix/me';
  static const String meUpdate      = '$apiPrefix/me';
  static const String mePassword    = '$apiPrefix/me/password';
  static const String googleToken   = '$apiPrefix/auth/google/token';

  // Cars
  static const String mobils        = '$apiPrefix/mobils';
  static const String tipeMobils    = '$apiPrefix/tipe-mobils';
  static const String mobilAvail    = '$apiPrefix/mobils/available';

  static String mobilDetail(int id) => '$apiPrefix/mobils/$id';

  // Bookings
  static const String bookings        = '$apiPrefix/bookings';
  static const String bookingRiwayat  = '$apiPrefix/bookings/riwayat';
  static const String bookingEstimate = '$apiPrefix/bookings/estimate';

  static String bookingDetail(int id) => '$apiPrefix/bookings/$id';
  static String pembayaranShow(int id) => '$apiPrefix/bookings/$id/pembayaran';
  static String pembayaranUpload(int id) => '$apiPrefix/bookings/$id/pembayaran/upload';
  static String bookingCancel(int id) => '$apiPrefix/bookings/$id/cancel';
  static String semuaPembayaran(int id) => '$apiPrefix/bookings/$id/semua-pembayaran';

  // Sopir
  static const String sopirDashboard = '$apiPrefix/sopir/dashboard';
  static const String sopirStatus    = '$apiPrefix/sopir/status';
  static const String sopirTawaran   = '$apiPrefix/sopir/tawaran';
  static const String sopirTugas     = '$apiPrefix/sopir/tugas';
  static const String sopirRiwayat   = '$apiPrefix/sopir/riwayat';

  static String tawaranTerima(int id) => '$apiPrefix/sopir/tawaran/$id/terima';
  static String tawaranTolak(int id)  => '$apiPrefix/sopir/tawaran/$id/tolak';
  static String sopirLaporan(int id)  => '$apiPrefix/sopir/tugas/$id/laporan-kondisi';

  // Admin
  static const String _admin = '$apiPrefix/admin';
  static const String adminDashboard = '$_admin/dashboard';
  static const String adminBookings  = '$_admin/bookings';
  static const String adminPembayaran = '$_admin/pembayaran';
  static const String adminReferensi = '$_admin/referensi';
  static const String adminMobils    = '$_admin/mobils';
  static const String adminSupirs    = '$_admin/supirs';
  static const String adminPenyewa   = '$_admin/penyewa';
  static const String adminRekap     = '$_admin/rekap';

  static String adminBooking(int id)        => '$adminBookings/$id';
  static String adminBookingBatal(int id)   => '$adminBookings/$id/cancel';
  static String pembayaranVerifikasi(int id) => '$adminPembayaran/$id/verifikasi';
  static String pembayaranTolak(int id)     => '$adminPembayaran/$id/tolak';
  static String pembayaranLunas(int id)     => '$adminPembayaran/$id/lunas';
  static String detailPenugasan(int id)     => '$_admin/detail/$id/penugasan';
  static String detailJemput(int id)        => '$_admin/detail/$id/jemput';
  static String detailPengembalian(int id)  => '$_admin/detail/$id/pengembalian';
  static String detailLaporan(int id)       => '$_admin/detail/$id/laporan-kondisi';
  static String adminMobil(int id)          => '$adminMobils/$id';
  static String adminMobilStatus(int id)    => '$adminMobils/$id/status';
  static String adminMobilGambar(int id)    => '$adminMobils/$id/gambar';
  static String adminSupir(int id)          => '$adminSupirs/$id';
  static String adminPenyewaItem(int id)    => '$adminPenyewa/$id';
}

