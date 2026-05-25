// ─────────────────────────────────────────────────────────────
//  API Constants — Harkat Rental Mobile
//  Update BASE_URL to your Laravel server IP/domain.
// ─────────────────────────────────────────────────────────────

class ApiConstants {
  // ⚠️ IP komputer ini di jaringan WiFi yang sama dengan HP
  // Jalankan: ipconfig | findstr IPv4  untuk cek IP terbaru
  static const String baseUrl = 'http://192.168.0.184:8000';

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
  static String semuaPembayaran(int id) => '$apiPrefix/bookings/$id/semua-pembayaran';
}
