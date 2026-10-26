import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/models/sopir_model.dart';
import 'package:harkat_rental/providers/sopir_provider.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> unit(int id, {int status = 1}) => {
      'id': id, 'status': status, 'status_label': '', 'lama_sewa': 2,
      'mobil': {'id': 1, 'nama': 'Avanza', 'merk': 'Toyota', 'plat_nomor': 'AB 1 CD'},
      'tanggal_sewa': '2026-10-08 09:00:00', 'tanggal_kembali': '2026-10-10 09:00:00',
      'penyewa': {'nama': 'Budi', 'no_hp': '0812'},
    };

/// Server palsu: [rute] memetakan "METHOD /path" ke (status, body). Rute yang tidak ada → 404.
Future<T> withServer<T>(Future<T> Function() body, Map<String, (int, Object)> rute, {List<String>? log}) =>
    http.runWithClient(body, () => MockClient((req) async {
          final key = '${req.method} ${req.url.path}';
          log?.add(key);
          final (code, json) = rute[key] ?? (404, {'status': false, 'message': 'tidak ada rute $key'});
          return http.Response(jsonEncode(json), code, request: req, headers: {'content-type': 'application/json'});
        }));

const dashboardOk = (200, {
  'status': true,
  'data': {'sopir': {'id': 1, 'nama': 'Joko', 'status': 1, 'status_text': 'Available'}, 'tugas_aktif': 1, 'tawaran_baru': 1, 'tugas_selesai': 0},
});

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({'auth_token': 'tok'});
  });

  TawaranModel tawaran(int id) => TawaranModel.fromJson({'id': id, 'status': 0, 'unit': unit(10 + id)});

  test('refreshAll memuat dashboard, tawaran, tugas, dan riwayat', () async {
    final prov = SopirProvider();
    await withServer(prov.refreshAll, {
      'GET /api/sopir/dashboard': dashboardOk,
      'GET /api/sopir/tawaran': (200, {'status': true, 'data': [{'id': 1, 'status': 0, 'unit': unit(11)}]}),
      'GET /api/sopir/tugas': (200, {'status': true, 'data': [unit(5, status: 2)]}),
      'GET /api/sopir/riwayat': (200, {'status': true, 'data': []}),
    });

    expect(prov.dashboard.value?.nama, 'Joko');
    expect(prov.tawaran.value.single.unit.id, 11);
    expect(prov.tugas.value.single.status, 2);
    expect(prov.riwayat.loaded, isTrue);
    expect(prov.riwayat.value, isEmpty);
    expect(prov.tawaran.loading, isFalse);
  });

  test('gagal memuat: keadaan galat dengan pesan, tidak macet loading', () async {
    final prov = SopirProvider();
    await withServer(prov.muatTugas, {'GET /api/sopir/tugas': (500, {'status': false, 'message': 'Server error'})});

    expect(prov.tugas.loading, isFalse);
    expect(prov.tugas.loaded, isTrue);
    expect(prov.tugas.error, 'Server error');
  });

  test('respons rusak (bukan JSON/format salah) tidak melempar', () async {
    final prov = SopirProvider();
    await http.runWithClient(prov.muatTawaran,
        () => MockClient((req) async => http.Response('<html>oops</html>', 200, request: req)));

    expect(prov.tawaran.loading, isFalse);
    expect(prov.tawaran.error, isNotNull);

    // data tidak berbentuk daftar → ditangkap, bukan crash
    await withServer(prov.muatTawaran, {'GET /api/sopir/tawaran': (200, {'status': true, 'data': 'bukan daftar'})});
    expect(prov.tawaran.loading, isFalse);
    expect(prov.tawaran.error, 'Data tidak dapat dimuat.');
  });

  test('polling senyap: gagal tidak menghapus data lama dan tidak menampilkan galat', () async {
    final prov = SopirProvider();
    await withServer(prov.muatTawaran, {'GET /api/sopir/tawaran': (200, {'status': true, 'data': [{'id': 1, 'status': 0, 'unit': unit(11)}]})});
    expect(prov.tawaran.value, hasLength(1));

    await withServer(() => prov.muatTawaran(silent: true), {'GET /api/sopir/tawaran': (500, {'message': 'x'})});

    expect(prov.tawaran.value, hasLength(1));
    expect(prov.tawaran.error, isNull);
  });

  test('terima berhasil: memanggil endpoint lalu memuat ulang semuanya', () async {
    final prov = SopirProvider();
    final log = <String>[];
    final galat = await withServer(() => prov.terima(tawaran(1)), {
      'POST /api/sopir/tawaran/1/terima': (200, {'status': true, 'message': 'Tugas diterima.', 'data': unit(11)}),
      'GET /api/sopir/dashboard': dashboardOk,
      'GET /api/sopir/tawaran': (200, {'status': true, 'data': []}),
      'GET /api/sopir/tugas': (200, {'status': true, 'data': [unit(11)]}),
      'GET /api/sopir/riwayat': (200, {'status': true, 'data': []}),
    }, log: log);

    expect(galat, isNull);
    expect(log.first, 'POST /api/sopir/tawaran/1/terima');
    expect(log.where((e) => e.startsWith('GET')).length, 4);
    expect(prov.tugas.value, hasLength(1));
    expect(prov.submitting, isFalse);
  });

  test('terima kalah bersaing (409): pesan dari server dan daftar disegarkan', () async {
    final prov = SopirProvider();
    final log = <String>[];
    final galat = await withServer(() => prov.terima(tawaran(1)), {
      'POST /api/sopir/tawaran/1/terima': (409, {'status': false, 'message': 'Job sudah diambil supir lain atau booking tidak aktif.'}),
      'GET /api/sopir/dashboard': dashboardOk,
      'GET /api/sopir/tawaran': (200, {'status': true, 'data': []}),
      'GET /api/sopir/tugas': (200, {'status': true, 'data': []}),
      'GET /api/sopir/riwayat': (200, {'status': true, 'data': []}),
    }, log: log);

    expect(galat, 'Job sudah diambil supir lain atau booking tidak aktif.');
    expect(log.where((e) => e.startsWith('GET')).length, 4, reason: 'tawaran basi harus hilang dari daftar');
    expect(prov.submitting, isFalse);
  });

  test('tolak: memanggil endpoint tolak lalu menyegarkan tawaran', () async {
    final prov = SopirProvider();
    final log = <String>[];
    final galat = await withServer(() => prov.tolak(tawaran(2)), {
      'POST /api/sopir/tawaran/2/tolak': (200, {'status': true, 'message': 'Tawaran ditolak.'}),
      'GET /api/sopir/dashboard': dashboardOk,
      'GET /api/sopir/tawaran': (200, {'status': true, 'data': []}),
    }, log: log);

    expect(galat, isNull);
    expect(log, contains('POST /api/sopir/tawaran/2/tolak'));
    expect(prov.tawaran.loaded, isTrue);
  });

  test('ubah status: kirim status 0/1; dikunci saat bertugas (422)', () async {
    final prov = SopirProvider();
    String? body;
    final ok = await http.runWithClient(() => prov.ubahStatus(false), () => MockClient((req) async {
          if (req.method == 'PATCH') body = req.body;
          return http.Response(jsonEncode(req.method == 'PATCH' ? {'status': true, 'data': {'status': 0}} : dashboardOk.$2), 200, request: req);
        }));
    expect(ok, isNull);
    expect(jsonDecode(body!), {'status': 0});

    final galat = await withServer(() => prov.ubahStatus(true), {
      'PATCH /api/sopir/status': (422, {'status': false, 'message': 'Anda sedang bertugas, status tidak dapat diubah.'}),
    });
    expect(galat, 'Anda sedang bertugas, status tidak dapat diubah.');
  });

  test('aksi ganda ditolak selama permintaan pertama berjalan (cegah klik dua kali)', () async {
    final prov = SopirProvider();
    final lepas = Future<void>.delayed(const Duration(milliseconds: 50));
    var panggilan = 0;

    final hasil = await http.runWithClient(() async {
      final pertama = prov.tolak(tawaran(1));
      final kedua = prov.tolak(tawaran(1));      // sebelum yang pertama selesai
      return [await pertama, await kedua];
    }, () => MockClient((req) async {
          if (req.method == 'POST') {
            panggilan++;
            await lepas;
          }
          return http.Response(jsonEncode({'status': true, 'data': []}), 200, request: req);
        }));

    expect(panggilan, 1);
    expect(hasil[0], isNull);
    expect(hasil[1], contains('masih diproses'));
    expect(prov.submitting, isFalse);
  });

  test('galat jaringan pada aksi: pesan ramah dan tombol tidak terkunci selamanya', () async {
    final prov = SopirProvider();
    final galat = await http.runWithClient(() => prov.terima(tawaran(1)),
        () => MockClient((_) async => throw Exception('putus')));

    expect(galat, isNotNull);
    expect(prov.submitting, isFalse);
  });

  test('reset membuang data pengguna sebelumnya', () async {
    final prov = SopirProvider();
    await withServer(prov.muatTugas, {'GET /api/sopir/tugas': (200, {'status': true, 'data': [unit(5)]})});
    expect(prov.tugas.value, hasLength(1));

    prov.reset();

    expect(prov.tugas.value, isEmpty);
    expect(prov.tugas.loaded, isFalse);
    expect(prov.dashboard.value, isNull);
  });
}
