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

  const ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
    required this.statusCode,
  });
}

// ─────────────────────────────────────────────────────────────
//  ApiService — HTTP client with timeout + auto Bearer token
// ─────────────────────────────────────────────────────────────

class ApiService {
  /// Maximum time to wait for any single request.
  static const Duration _timeout = Duration(seconds: 10);

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

  static ApiResponse<Map<String, dynamic>> _parse(http.Response response) {
    try {
      final body = jsonDecode(response.body) as Map<String, dynamic>;
      final ok   = response.statusCode >= 200 && response.statusCode < 300;
      return ApiResponse(
        success:    ok && (body['status'] == true),
        message:    body['message']?.toString() ?? '',
        data:       body,
        errors:     body['errors'] as Map<String, dynamic>?,
        statusCode: response.statusCode,
      );
    } catch (e) {
      return ApiResponse(
        success:    false,
        message:    'Terjadi kesalahan saat memproses respons server.',
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
}
