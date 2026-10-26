import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../constants/api_constants.dart';
import 'storage_service.dart';

// ─────────────────────────────────────────────────────────────
//  ApiResponse — uniform wrapper for all API calls
// ─────────────────────────────────────────────────────────────

class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;
  final int statusCode;

  /// Kode khusus dari server (mis. `INCOMPLETE_PROFILE`), jika ada.
  final String? code;

  const ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
    required this.statusCode,
    this.code,
  });

  /// Pesan untuk ditampilkan ke user: error validasi pertama dari server jika ada, kalau tidak [message].
  String get displayMessage {
    final e = errors;
    if (e != null && e.isNotEmpty) {
      final v = e.values.first;
      if (v is List && v.isNotEmpty) return v.first.toString();
      if (v is String && v.isNotEmpty) return v;
    }
    return message;
  }
}

// ─────────────────────────────────────────────────────────────
//  ApiService — HTTP client with timeout + auto Bearer token
// ─────────────────────────────────────────────────────────────

class ApiService {
  /// Maximum time to wait for any single request.
  static const Duration _timeout = Duration(seconds: 10);

  /// Pembuatan booking menulis banyak data di server, beri waktu lebih lama agar tidak timeout
  /// padahal booking sudah tersimpan (yang membuat user menekan ulang dan memesan dua kali).
  static const Duration _createTimeout = Duration(seconds: 30);

  // ── Helpers ────────────────────────────────────────────────

  static Future<Map<String, String>> _headers({bool auth = false}) async {
    final headers = <String, String>{
      'Accept':       'application/json',
      'Content-Type': 'application/json',
    };
    if (auth) {
      final token = await StorageService.getToken();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
    }
    return headers;
  }

  /// Dipanggil saat request yang membawa token dijawab 401 (token dicabut / kedaluwarsa,
  /// mis. login di HP lain). Diset oleh AuthProvider untuk logout otomatis.
  static void Function()? onUnauthorized;

  static const String _sessionExpiredMsg = 'Sesi Anda telah berakhir. Silakan login kembali.';

  static ApiResponse<Map<String, dynamic>> _parse(http.Response response) {
    // 401 pada request ber-token = sesi mati. (401 saat login Google/email tidak membawa token.)
    final sessionDead = response.statusCode == 401 &&
        (response.request?.headers.containsKey('Authorization') ?? false);
    if (sessionDead) onUnauthorized?.call();

    try {
      final body = jsonDecode(response.body) as Map<String, dynamic>;
      final ok   = response.statusCode >= 200 && response.statusCode < 300;
      return ApiResponse(
        success:    ok && (body['status'] == true),
        message:    sessionDead ? _sessionExpiredMsg : (body['message']?.toString() ?? ''),
        data:       body,
        errors:     body['errors'] as Map<String, dynamic>?,
        statusCode: response.statusCode,
        code:       body['code']?.toString(),
      );
    } catch (e) {
      return ApiResponse(
        success:    false,
        message:    sessionDead ? _sessionExpiredMsg : 'Terjadi kesalahan saat memproses respons server.',
        statusCode: response.statusCode,
      );
    }
  }

  static ApiResponse<Map<String, dynamic>> _networkError(Object e) {
    String msg;
    if (e is SocketException)  msg = 'Tidak dapat terhubung ke server. Pastikan server menyala dan HP/laptop terhubung WiFi yang sama.';
    else if (e is TimeoutException) msg = 'Koneksi timeout. Server tidak merespons dalam 10 detik.';
    else if (e is HttpException)    msg = 'Kesalahan jaringan: ${e.message}';
    else                            msg = 'Terjadi kesalahan: ${e.toString()}';
    return ApiResponse(success: false, message: msg, statusCode: 0);
  }

  // ── Auth Endpoints ─────────────────────────────────────────


  static Future<ApiResponse<Map<String, dynamic>>> login({
    required String email,
    required String password,
  }) async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.login),
        headers: await _headers(),
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> logout() async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.logout),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> loginWithGoogle({
    required String idToken,
  }) async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.googleToken),
        headers: await _headers(),
        body: jsonEncode({'id_token': idToken}),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getMe() async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.me),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> updateProfile(
      Map<String, dynamic> data) async {
    try {
      final res = await http.put(
        Uri.parse(ApiConstants.meUpdate),
        headers: await _headers(auth: true),
        body: jsonEncode(data),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  // ── Car Endpoints ──────────────────────────────────────────

  static Future<ApiResponse<Map<String, dynamic>>> getMobils({
    String? search,
    String? type,
    int page = 1,
    int perPage = 10,
  }) async {
    try {
      final uri = Uri.parse(ApiConstants.mobils).replace(queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (type   != null && type.isNotEmpty)   'type':   type,
        'page':     page.toString(),
        'per_page': perPage.toString(),
      });
      final res = await http.get(uri, headers: await _headers()).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getMobilDetail(int id) async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.mobilDetail(id)),
        headers: await _headers(),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getTipeMobils() async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.tipeMobils),
        headers: await _headers(),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getAvailableMobils({
    required String tanggalSewa,
    required String tanggalKembali,
  }) async {
    try {
      final uri = Uri.parse(ApiConstants.mobilAvail).replace(queryParameters: {
        'tanggal_sewa':    tanggalSewa,
        'tanggal_kembali': tanggalKembali,
      });
      final res = await http.get(uri, headers: await _headers()).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  // ── Booking Endpoints ──────────────────────────────────────

  static Future<ApiResponse<Map<String, dynamic>>> getBookings() async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.bookings),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getRiwayat() async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.bookingRiwayat),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getBookingDetail(int id) async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.bookingDetail(id)),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> createBooking(
      Map<String, dynamic> payload) async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.bookings),
        headers: await _headers(auth: true),
        body: jsonEncode(payload),
      ).timeout(_createTimeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> cancelBooking(int id) async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.bookingCancel(id)),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> estimateBooking(
      List<Map<String, dynamic>> mobils) async {
    try {
      final res = await http.post(
        Uri.parse(ApiConstants.bookingEstimate),
        headers: await _headers(auth: true),
        body: jsonEncode({'mobils': mobils}),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  // ── Payment Endpoints ──────────────────────────────────────

  static Future<ApiResponse<Map<String, dynamic>>> getPembayaran(int bookingId) async {
    try {
      final res = await http.get(
        Uri.parse(ApiConstants.pembayaranShow(bookingId)),
        headers: await _headers(auth: true),
      ).timeout(_timeout);
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> uploadBukti({
    required int bookingId,
    required File imageFile,
    int metodePembayaran = 3,
    int? pembayaranId, // null = DP; isi untuk pelunasan/denda
  }) async {
    try {
      final token   = await StorageService.getToken();
      final request = http.MultipartRequest(
        'POST',
        Uri.parse(ApiConstants.pembayaranUpload(bookingId)),
      );
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept']        = 'application/json';
      request.fields['metode_pembayaran'] = metodePembayaran.toString();
      if (pembayaranId != null) request.fields['pembayaran_id'] = pembayaranId.toString();
      request.files.add(await http.MultipartFile.fromPath(
        'bukti_bayar',
        imageFile.path,
      ));

      final streamed  = await request.send().timeout(const Duration(seconds: 30));
      final response  = await http.Response.fromStream(streamed);
      return _parse(response);
    } catch (e) {
      return _networkError(e);
    }
  }

  // ── Sopir Endpoints ────────────────────────────────────────

  /// Pemanggil umum untuk endpoint ber-token (GET/POST/PATCH/PUT/DELETE). Galat jaringan dibungkus ApiResponse.
  static Future<ApiResponse<Map<String, dynamic>>> call(
    String method,
    String url, {
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) async {
    try {
      var uri = Uri.parse(url);
      if (query != null && query.isNotEmpty) uri = uri.replace(queryParameters: query);
      final headers = await _headers(auth: true);
      final encoded = body == null ? null : jsonEncode(body);
      final http.Response res = switch (method) {
        'POST'   => await http.post(uri, headers: headers, body: encoded).timeout(_timeout),
        'PATCH'  => await http.patch(uri, headers: headers, body: encoded).timeout(_timeout),
        'PUT'    => await http.put(uri, headers: headers, body: encoded).timeout(_timeout),
        'DELETE' => await http.delete(uri, headers: headers).timeout(_timeout),
        _        => await http.get(uri, headers: headers).timeout(_timeout),
      };
      return _parse(res);
    } catch (e) {
      return _networkError(e);
    }
  }

  /// Kirim form multipart (teks + berkas) dengan token. [files]: nama field → berkas.
  static Future<ApiResponse<Map<String, dynamic>>> multipart(
    String url, {
    Map<String, String> fields = const {},
    Map<String, File> files = const {},
  }) async {
    try {
      final token   = await StorageService.getToken();
      final request = http.MultipartRequest('POST', Uri.parse(url));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept']        = 'application/json';
      request.fields.addAll(fields);
      for (final e in files.entries) {
        request.files.add(await http.MultipartFile.fromPath(e.key, e.value.path));
      }
      final streamed = await request.send().timeout(const Duration(seconds: 30));
      return _parse(await http.Response.fromStream(streamed));
    } catch (e) {
      return _networkError(e);
    }
  }

  static Future<ApiResponse<Map<String, dynamic>>> getSopirDashboard() =>
      call('GET', ApiConstants.sopirDashboard);

  /// [tersedia] true → status 1, false → libur (0).
  static Future<ApiResponse<Map<String, dynamic>>> setSopirStatus(bool tersedia) =>
      call('PATCH', ApiConstants.sopirStatus, body: {'status': tersedia ? 1 : 0});

  static Future<ApiResponse<Map<String, dynamic>>> getTawaran() =>
      call('GET', ApiConstants.sopirTawaran);

  static Future<ApiResponse<Map<String, dynamic>>> terimaTawaran(int id) =>
      call('POST', ApiConstants.tawaranTerima(id));

  static Future<ApiResponse<Map<String, dynamic>>> tolakTawaran(int id) =>
      call('POST', ApiConstants.tawaranTolak(id));

  static Future<ApiResponse<Map<String, dynamic>>> getSopirTugas() =>
      call('GET', ApiConstants.sopirTugas);

  static Future<ApiResponse<Map<String, dynamic>>> getSopirRiwayat() =>
      call('GET', ApiConstants.sopirRiwayat);

  /// Laporan kondisi kendaraan. [jenis]: 1 sebelum, 2 sesudah. [kondisi]: 1 baik, 2 lecet, 3 rusak.
  static Future<ApiResponse<Map<String, dynamic>>> kirimLaporanKondisi({
    required int unitId,
    required int jenis,
    required int kondisi,
    String? catatan,
    File? foto,
  }) async {
    try {
      final token   = await StorageService.getToken();
      final request = http.MultipartRequest('POST', Uri.parse(ApiConstants.sopirLaporan(unitId)));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept']        = 'application/json';
      request.fields['jenis']   = jenis.toString();
      request.fields['kondisi'] = kondisi.toString();
      if (catatan != null && catatan.trim().isNotEmpty) request.fields['catatan'] = catatan.trim();
      if (foto != null) request.files.add(await http.MultipartFile.fromPath('foto', foto.path));

      final streamed = await request.send().timeout(const Duration(seconds: 30));
      return _parse(await http.Response.fromStream(streamed));
    } catch (e) {
      return _networkError(e);
    }
  }
}

