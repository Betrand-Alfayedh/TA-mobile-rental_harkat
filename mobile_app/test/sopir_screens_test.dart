import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/models/sopir_model.dart';
import 'package:harkat_rental/providers/auth_provider.dart';
import 'package:harkat_rental/providers/sopir_provider.dart';
import 'package:harkat_rental/screens/sopir/laporan_kondisi_screen.dart';
import 'package:harkat_rental/screens/sopir/sopir_home_screen.dart';
import 'package:harkat_rental/screens/sopir/tawaran_screen.dart';
import 'package:harkat_rental/screens/sopir/tugas_screen.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, dynamic> unit(int id, {int status = 1, Map<String, dynamic>? sebelum}) => {
      'id': id, 'status': status, 'lama_sewa': 2,
      'mobil': {'id': 1, 'nama': 'Avanza', 'merk': 'Toyota', 'plat_nomor': 'AB 1 CD'},
      'tanggal_sewa': '2026-10-08 09:00:00', 'tanggal_kembali': '2026-10-10 09:00:00',
      'penyewa': {'nama': 'Budi', 'no_hp': '0812'}, 'asal_kota': 'Yogyakarta',
      'laporan_sebelum': sebelum, 'laporan_sesudah': null,
    };

class Rekam {
  final List<http.BaseRequest> requests = [];
  List<String> get log => requests.map((r) => '${r.method} ${r.url.path}').toList();
}

/// Pasang layar dengan SopirProvider yang bicara ke server palsu. [rute] diperiksa per "METHOD /path".
Future<(SopirProvider, Rekam)> pasang(
  WidgetTester tester,
  Widget layar,
  Map<String, (int, Object)> rute,
) async {
  final rekam = Rekam();
  final prov = SopirProvider();
  final client = MockClient((req) async {
    rekam.requests.add(req);
    final (code, body) = rute['${req.method} ${req.url.path}'] ?? (404, {'status': false, 'message': 'tak ada rute'});
    return http.Response(jsonEncode(body), code, request: req, headers: {'content-type': 'application/json'});
  });

  await http.runWithClient(() async {
    await tester.pumpWidget(MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider.value(value: prov),
      ],
      child: MaterialApp(home: layar),
    ));
  }, () => client);
  return (prov, rekam);
}

/// Jalankan [aksi] sambil klien palsu aktif (provider memanggil http saat dijalankan).
Future<T> denganServer<T>(Rekam rekam, Map<String, (int, Object)> rute, Future<T> Function() aksi) {
  final client = MockClient((req) async {
    rekam.requests.add(req);
    final (code, body) = rute['${req.method} ${req.url.path}'] ?? (404, {'status': false, 'message': 'tak ada rute'});
    return http.Response(jsonEncode(body), code, request: req, headers: {'content-type': 'application/json'});
  });
  return http.runWithClient(aksi, () => client);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({'auth_token': 'tok'});
  });

  group('TawaranScreen', () {
    testWidgets('kosong: pesan jelas, bukan layar putih', (tester) async {
      final (prov, rekam) = await pasang(tester, const TawaranScreen(), {});
      await denganServer(rekam, {'GET /api/sopir/tawaran': (200, {'status': true, 'data': []})}, prov.muatTawaran);
      await tester.pump();

      expect(find.text('Belum ada tawaran'), findsOneWidget);
    });

    testWidgets('galat memuat: tombol coba lagi tampil', (tester) async {
      final (prov, rekam) = await pasang(tester, const TawaranScreen(), {});
      await denganServer(rekam, {'GET /api/sopir/tawaran': (500, {'status': false, 'message': 'Server error'})}, prov.muatTawaran);
      await tester.pump();

      expect(find.text('Server error'), findsOneWidget);
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('terima: konfirmasi dulu, baru kirim ke server', (tester) async {
      final rute = {
        'GET /api/sopir/tawaran': (200, {'status': true, 'data': [{'id': 4, 'status': 0, 'unit': unit(11)}]}),
        'POST /api/sopir/tawaran/4/terima': (200, {'status': true, 'message': 'Tugas diterima.', 'data': unit(11)}),
        'GET /api/sopir/dashboard': (200, {'status': true, 'data': {'sopir': {'status': 2}}}),
        'GET /api/sopir/tugas': (200, {'status': true, 'data': []}),
        'GET /api/sopir/riwayat': (200, {'status': true, 'data': []}),
      };
      final rekam = Rekam();

      // Seluruh alur dalam satu zona klien: lanjutan setelah dialog ikut memakai server palsu.
      await denganServer(rekam, rute, () async {
        final prov = SopirProvider();
        await tester.pumpWidget(MultiProvider(
          providers: [ChangeNotifierProvider(create: (_) => AuthProvider()), ChangeNotifierProvider.value(value: prov)],
          child: const MaterialApp(home: TawaranScreen()),
        ));
        await prov.muatTawaran();
        await tester.pump();
        expect(find.text('Toyota Avanza'), findsOneWidget);
        expect(find.text('Budi'), findsOneWidget);

        await tester.tap(find.text('Terima'));
        await tester.pumpAndSettle();
        expect(find.text('Terima tugas?'), findsOneWidget);
        expect(rekam.log.where((e) => e.startsWith('POST')), isEmpty, reason: 'belum ada permintaan sebelum dikonfirmasi');

        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Terima')));
        await tester.pumpAndSettle();
        expect(rekam.log, contains('POST /api/sopir/tawaran/4/terima'));
        expect(find.text('Tugas diterima.'), findsOneWidget);   // umpan balik ke sopir
      });
    });

    testWidgets('batal pada dialog: tidak ada permintaan', (tester) async {
      final (prov, rekam) = await pasang(tester, const TawaranScreen(), {});
      await denganServer(rekam,
          {'GET /api/sopir/tawaran': (200, {'status': true, 'data': [{'id': 4, 'status': 0, 'unit': unit(11)}]})}, prov.muatTawaran);
      await tester.pump();

      await tester.tap(find.text('Tolak'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Batal'));
      await tester.pumpAndSettle();

      expect(rekam.log.where((e) => e.startsWith('POST')), isEmpty);
    });
  });

  group('TugasScreen', () {
    Future<(SopirProvider, Rekam)> denganTugas(WidgetTester tester, List<Map<String, dynamic>> data) async {
      final (prov, rekam) = await pasang(tester, const TugasScreen(), {});
      await denganServer(rekam, {'GET /api/sopir/tugas': (200, {'status': true, 'data': data})}, prov.muatTugas);
      await tester.pump();
      return (prov, rekam);
    }

    bool aktif(WidgetTester tester, String teks) {
      final b = tester.widget<OutlinedButton>(find.widgetWithText(OutlinedButton, teks));
      return b.onPressed != null;
    }

    testWidgets('booked: laporan sebelum aktif, sesudah dikunci', (tester) async {
      await denganTugas(tester, [unit(5, status: 1)]);

      expect(aktif(tester, 'Lapor sebelum'), isTrue);
      expect(aktif(tester, 'Lapor sesudah'), isFalse);
      expect(find.text('Menunggu jemput'), findsOneWidget);
    });

    testWidgets('ongoing: keduanya aktif; laporan yang ada ditampilkan dan bisa diubah', (tester) async {
      await denganTugas(tester, [
        unit(5, status: 2, sebelum: {'id': 1, 'jenis': 1, 'kondisi': 2, 'kondisi_label': 'Lecet / perlu perhatian'}),
      ]);

      expect(aktif(tester, 'Ubah laporan sebelum'), isTrue);
      expect(aktif(tester, 'Lapor sesudah'), isTrue);
      expect(find.text('Lecet / perlu perhatian'), findsOneWidget);
      expect(find.text('belum dilaporkan'), findsOneWidget);
    });

    testWidgets('kosong: pesan jelas', (tester) async {
      await denganTugas(tester, []);
      expect(find.text('Belum ada tugas aktif'), findsOneWidget);
    });
  });

  group('LaporanKondisiScreen', () {
    SopirUnitModel model() => SopirUnitModel.fromJson(unit(5, status: 2));

    testWidgets('kondisi lecet tanpa catatan: ditolak di klien, tidak ada permintaan', (tester) async {
      final (_, rekam) = await pasang(tester, LaporanKondisiScreen(unit: model(), jenis: 1), {});

      await tester.tap(find.text('Lecet / perlu perhatian'));
      await tester.pump();
      await tester.ensureVisible(find.text('Kirim laporan'));
      await tester.tap(find.text('Kirim laporan'));
      await tester.pump();

      expect(find.text('Jelaskan kondisi kendaraan pada catatan.'), findsOneWidget);
      expect(rekam.requests, isEmpty);
    });

    testWidgets('kondisi baik: terkirim dengan field yang benar', (tester) async {
      final (prov, rekam) = await pasang(tester, LaporanKondisiScreen(unit: model(), jenis: 2), {});

      await denganServer(rekam, {
        'POST /api/sopir/tugas/5/laporan-kondisi': (201, {'status': true, 'message': 'tersimpan', 'data': {}}),
        'GET /api/sopir/tugas': (200, {'status': true, 'data': []}),
        'GET /api/sopir/riwayat': (200, {'status': true, 'data': []}),
      }, () async {
        await tester.ensureVisible(find.text('Kirim laporan'));
        await tester.tap(find.text('Kirim laporan'));
        await tester.pumpAndSettle();
      });

      // MockClient menerima isi multipart sebagai teks mentah.
      final kirim = rekam.requests.firstWhere((r) => r.method == 'POST') as http.Request;
      expect(kirim.url.path, '/api/sopir/tugas/5/laporan-kondisi');
      expect(kirim.headers['Authorization'], 'Bearer tok');
      expect(kirim.headers['content-type'], startsWith('multipart/form-data'));
      expect(kirim.body, matches(RegExp(r'name="jenis"\s+2')));
      expect(kirim.body, matches(RegExp(r'name="kondisi"\s+1')));
      expect(prov.submitting, isFalse);
    });

    testWidgets('server menolak: pesan tampil dan layar tetap terbuka', (tester) async {
      final (_, rekam) = await pasang(tester, LaporanKondisiScreen(unit: model(), jenis: 2), {});

      await denganServer(rekam, {
        'POST /api/sopir/tugas/5/laporan-kondisi': (422, {'status': false, 'message': 'Laporan sesudah sewa baru dapat dibuat setelah unit dijemput.'}),
      }, () async {
        await tester.ensureVisible(find.text('Kirim laporan'));
        await tester.tap(find.text('Kirim laporan'));
        await tester.pumpAndSettle();
      });

      expect(find.text('Laporan sesudah sewa baru dapat dibuat setelah unit dijemput.'), findsOneWidget);
      expect(find.text('Kirim laporan'), findsOneWidget);   // masih di layar form
    });

    testWidgets('judul sesuai jenis dan laporan lama diprefill', (tester) async {
      final unitDenganLaporan = SopirUnitModel.fromJson(unit(5, status: 2, sebelum: {
        'id': 1, 'jenis': 1, 'kondisi': 3, 'kondisi_label': 'Rusak', 'catatan': 'Kaca pecah',
      }));
      await pasang(tester, LaporanKondisiScreen(unit: unitDenganLaporan, jenis: 1), {});

      expect(find.text('Kondisi sebelum sewa'), findsOneWidget);
      expect(find.text('Kaca pecah'), findsOneWidget);
      expect(find.textContaining('Mengirim ulang akan memperbaruinya'), findsOneWidget);
    });
  });

  group('SopirHomeScreen', () {
    Map<String, (int, Object)> dash(int status, String teks) => {
          'GET /api/sopir/dashboard': (200, {
            'status': true,
            'data': {'sopir': {'id': 1, 'nama': 'Joko', 'status': status, 'status_text': teks}, 'tugas_aktif': 1, 'tawaran_baru': 2, 'tugas_selesai': 5},
          }),
          'GET /api/sopir/tugas': (200, {'status': true, 'data': []}),
        };

    testWidgets('bertugas: saklar status terkunci dan ada penjelasan', (tester) async {
      final (prov, rekam) = await pasang(tester, SopirHomeScreen(onLihatTawaran: () {}), {});
      await denganServer(rekam, dash(2, 'Bertugas'), () => Future.wait([prov.muatDashboard(), prov.muatTugas()]));
      await tester.pump();

      expect(tester.widget<Switch>(find.byType(Switch)).onChanged, isNull);
      expect(find.text('Status dikunci selama Anda bertugas.'), findsOneWidget);
      expect(find.text('2'), findsOneWidget);   // tawaran baru
    });

    testWidgets('tersedia: saklar aktif', (tester) async {
      final (prov, rekam) = await pasang(tester, SopirHomeScreen(onLihatTawaran: () {}), {});
      await denganServer(rekam, dash(1, 'Available'), () => Future.wait([prov.muatDashboard(), prov.muatTugas()]));
      await tester.pump();

      final sw = tester.widget<Switch>(find.byType(Switch));
      expect(sw.value, isTrue);
      expect(sw.onChanged, isNotNull);
    });

    testWidgets('ketuk ringkasan tawaran memanggil navigasi ke tab Tawaran', (tester) async {
      var dibuka = false;
      final (prov, rekam) = await pasang(tester, SopirHomeScreen(onLihatTawaran: () => dibuka = true), {});
      await denganServer(rekam, dash(1, 'Available'), () => Future.wait([prov.muatDashboard(), prov.muatTugas()]));
      await tester.pump();

      await tester.tap(find.text('Tawaran baru'));
      expect(dibuka, isTrue);
    });
  });
}
