import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Server palsu untuk tes. Rute dipetakan dari "METHOD /path" (tanpa query) ke (status, body).
/// Isi multipart sampai ke sini sebagai teks mentah di [http.Request.body].
class Server {
  final Map<String, (int, Object)> rute;

  /// Penangan dinamis (mengutamakan atas [rute]): untuk respons yang bergantung pada query atau perlu ditunda.
  final Map<String, Future<(int, Object)> Function(http.Request)> dinamis = {};
  final List<http.Request> log = [];

  Server([Map<String, (int, Object)>? rute]) : rute = rute ?? {};

  MockClient get client => MockClient((req) async {
        log.add(req);
        final kunci = '${req.method} ${req.url.path}';
        final (code, body) = dinamis[kunci] != null
            ? await dinamis[kunci]!(req)
            : (rute[kunci] ?? (404, {'status': false, 'message': 'tak ada rute $kunci'}));
        return http.Response(jsonEncode(body), code, request: req, headers: {'content-type': 'application/json'});
      });

  List<String> get kunci => log.map((r) => '${r.method} ${r.url.path}').toList();
  Iterable<http.Request> permintaan(String metodeDanPath) => log.where((r) => '${r.method} ${r.url.path}' == metodeDanPath);
  Map<String, dynamic> body(String metodeDanPath) => jsonDecode(permintaan(metodeDanPath).last.body) as Map<String, dynamic>;
  void bersihkan() => log.clear();
}

/// Jalankan [body] dengan klien HTTP palsu terpasang di seluruh zona (termasuk lanjutan asinkron).
Future<T> pakaiServer<T>(Server s, Future<T> Function() body) => http.runWithClient(body, () => s.client);

/// Sesi login palsu: token tersimpan agar permintaan membawa Authorization.
void siapkanSesi() {
  SharedPreferences.setMockInitialValues({});
  FlutterSecureStorage.setMockInitialValues({'auth_token': 'tok'});
}

(int, Object) ok(Object? data, {Map<String, dynamic>? meta, String message = 'OK'}) =>
    (200, {'status': true, 'message': message, 'data': data, if (meta != null) 'meta': meta});

(int, Object) galat(int kode, String pesan) => (kode, {'status': false, 'message': pesan});

Map<String, dynamic> meta({int page = 1, int last = 1, int total = 0}) => {'page': page, 'per_page': 15, 'total': total, 'last_page': last};

// ── Fixture ───────────────────────────────────────────────────

Map<String, dynamic> pembayaranJson(int id, {int jenis = 1, int status = 0, int jumlah = 400000, int booking = 1, String? foto}) => {
      'id': id, 'booking_id': booking, 'jenis': jenis, 'jenis_text': jenis == 1 ? 'DP' : 'Pelunasan', 'jumlah': jumlah,
      'status_pembayaran': status, 'status_pembayaran_text': '', 'foto_bukti': foto,
      'penyewa': 'Budi', 'total_pesanan': 800000, 'status_pesanan': 'Booked',
    };

Map<String, dynamic> unitJson(int id, {int status = 1, bool bersopir = false, int? supirId, bool menunggu = false, Map<String, dynamic>? sebelum, Map<String, dynamic>? pengembalian}) => {
      'id': id, 'status': status, 'status_label': '', 'lama_sewa': 2, 'harga': 800000,
      'pakai_supir': bersopir, 'supir_id': supirId, 'menunggu_supir': menunggu,
      'mobil': {'id': 1, 'plat_nomor': 'AB 1234 CD', 'merk': 'Toyota', 'master_mobil': {'id': 1, 'nama': 'Avanza'}},
      'supir': supirId == null ? null : {'id': supirId, 'nama': 'Joko', 'no_hp': '0813', 'status': 2},
      'tanggal_sewa': '2026-10-08 09:00:00', 'tanggal_kembali': '2026-10-10 09:00:00',
      'laporan_sebelum': sebelum, 'laporan_sesudah': null, 'pengembalian': pengembalian,
    };

Map<String, dynamic> bookingJson(int id, {
  int status = 1,
  int dpStatus = 1,
  List<Map<String, dynamic>>? units,
  List<Map<String, dynamic>>? pembayarans,
}) =>
    {
      'id': id, 'status': status, 'status_label': '', 'tanggal_booking_format': '06 Okt 2026',
      'asal_kota_label': 'Yogyakarta', 'jaminan_label': 'KTP', 'total_harga': 800000, 'uang_muka': 400000,
      'penyewa': {'id': 9, 'name': 'Budi Santoso', 'email': 'budi@x.com', 'no_hp': '0812', 'alamat': 'Jl. A', 'asal_kota': 'Sleman'},
      'pembayarans': pembayarans ?? [pembayaranJson(1, status: dpStatus, booking: id)],
      'details': units ?? [unitJson(11)],
    };

Map<String, dynamic> mobilJson(int id, {String plat = 'AB 1 CD', int status = 1}) => {
      'id': id, 'plat_nomor': plat, 'merk': 'Toyota', 'tahun': 2023, 'harga_sewa': 500000, 'harga_all_in': 800000,
      'status': status, 'gambar': null, 'master_mobil': {'id': 1, 'nama': 'Avanza', 'tipe': {'id': 1, 'nama_tipe': 'MPV'}},
    };

Map<String, dynamic> dashboardJson({int menunggu = 2, int perluSopir = 1}) => {
      'pesanan': {'masuk_hari_ini': 3, 'booked': 4, 'berjalan': 1, 'selesai': 7, 'dibatalkan': 0},
      'perlu_tindakan': {'pembayaran_menunggu_verifikasi': menunggu, 'perlu_penugasan_sopir': perluSopir, 'jemput_hari_ini': 1, 'kembali_hari_ini': 0, 'terlambat_kembali': 0},
      'armada': {'total': 10, 'tersedia': 6, 'disewa': 2, 'maintenance': 1, 'rusak': 1},
      'sopir': {'tersedia': 2, 'bertugas': 1, 'libur': 0},
    };
