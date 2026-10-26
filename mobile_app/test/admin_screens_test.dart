import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:harkat_rental/providers/admin_master_provider.dart';
import 'package:harkat_rental/providers/admin_provider.dart';
import 'package:harkat_rental/providers/auth_provider.dart';
import 'package:harkat_rental/screens/admin/admin_home_screen.dart';
import 'package:harkat_rental/screens/admin/armada_form_screen.dart';
import 'package:harkat_rental/screens/admin/pembayaran_list_screen.dart';
import 'package:harkat_rental/screens/admin/pengembalian_form_screen.dart';
import 'package:harkat_rental/screens/admin/penyewa_screens.dart';
import 'package:harkat_rental/screens/admin/pesanan_detail_screen.dart';
import 'package:harkat_rental/screens/admin/pesanan_list_screen.dart';
import 'package:harkat_rental/screens/admin/sopir_admin_screens.dart';
import 'package:harkat_rental/models/admin_model.dart';
import 'package:provider/provider.dart';

import 'support.dart';

/// Pasang [layar] dengan provider admin, lalu jalankan [uji] — semuanya dalam satu zona klien palsu
/// supaya lanjutan asinkron (setelah dialog) tetap memakai server palsu.
Future<void> uji(
  WidgetTester tester,
  Server s,
  Widget layar,
  Future<void> Function(AdminProvider admin, AdminMasterProvider master) isi,
) =>
    pakaiServer(s, () async {
      // Layar uji tinggi agar seluruh daftar dibangun (ListView membangun item secara lazy).
      tester.view.physicalSize = const Size(800, 3200);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.reset);
      final a = AdminProvider();
      final m = AdminMasterProvider();
      await tester.pumpWidget(MultiProvider(
        key: UniqueKey(), // pohon baru: initState berjalan lagi bila dipakai berulang dalam satu tes
        providers: [
          ChangeNotifierProvider(create: (_) => AuthProvider()),
          ChangeNotifierProvider.value(value: a),
          ChangeNotifierProvider.value(value: m),
        ],
        child: MaterialApp(home: layar),
      ));
      await isi(a, m);
    });

Server serverPesanan(Map<String, dynamic> booking) => Server({
      'GET /api/admin/bookings/${booking['id']}': ok(booking),
      'GET /api/admin/bookings': ok([booking], meta: meta(total: 1)),
      'GET /api/admin/pembayaran': ok([], meta: meta()),
      'GET /api/admin/dashboard': ok(dashboardJson()),
    });

bool aktif(WidgetTester tester, Finder f) {
  final w = tester.widget(f);
  if (w is ElevatedButton) return w.onPressed != null;
  if (w is OutlinedButton) return w.onPressed != null;
  throw StateError('bukan tombol: $w');
}

Finder tombol(String teks) => find.ancestor(of: find.text(teks), matching: find.byWidgetPredicate((w) => w is ElevatedButton || w is OutlinedButton));

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUpAll(() => initializeDateFormatting('id_ID'));   // seperti main.dart
  setUp(siapkanSesi);

  group('PesananDetailScreen: aksi mengikuti keadaan pesanan', () {
    testWidgets('Booked + DP lunas + lepas kunci: jemput aktif dan terkirim setelah konfirmasi', (tester) async {
      final s = serverPesanan(bookingJson(1, status: 1, dpStatus: 1))
        ..rute['PATCH /api/admin/detail/11/jemput'] = ok(unitJson(11, status: 2));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Budi Santoso'), findsOneWidget);
        expect(find.text('Toyota Avanza'), findsOneWidget);
        expect(aktif(tester, tombol('Konfirmasi jemput')), isTrue);
        expect(find.text('Tugaskan sopir'), findsNothing, reason: 'lepas kunci tidak butuh sopir');

        await tester.tap(find.text('Konfirmasi jemput'));
        await tester.pumpAndSettle();
        expect(find.text('Konfirmasi jemput?'), findsOneWidget);
        expect(s.permintaan('PATCH /api/admin/detail/11/jemput'), isEmpty, reason: 'belum ada permintaan sebelum dikonfirmasi');

        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Konfirmasi')));
        await tester.pumpAndSettle();
        expect(s.permintaan('PATCH /api/admin/detail/11/jemput'), hasLength(1));
        expect(find.text('Unit dijemput.'), findsOneWidget);
      });
    });

    testWidgets('DP belum lunas: jemput terkunci dengan alasan di label', (tester) async {
      final s = serverPesanan(bookingJson(1, status: 1, dpStatus: 0));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Jemput (DP belum lunas)'), findsOneWidget);
        expect(aktif(tester, tombol('Jemput (DP belum lunas)')), isFalse);
      });
    });

    testWidgets('unit bersopir: tombol tugaskan; setelah ditawarkan berubah menjadi status menunggu', (tester) async {
      final belum = serverPesanan(bookingJson(1, units: [unitJson(11, bersopir: true)]))
        ..rute['POST /api/admin/detail/11/penugasan'] = ok({'jumlah_sopir': 2, 'detail': unitJson(11)});

      await uji(tester, belum, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Belum ditugaskan'), findsOneWidget);

        await tester.tap(find.text('Tugaskan sopir'));
        await tester.pumpAndSettle();
        await tester.tap(find.text('Kirim tawaran'));
        await tester.pumpAndSettle();

        expect(belum.permintaan('POST /api/admin/detail/11/penugasan'), hasLength(1));
        expect(find.text('Tugas ditawarkan ke 2 sopir.'), findsOneWidget);
      });

      final menunggu = serverPesanan(bookingJson(1, units: [unitJson(11, bersopir: true, menunggu: true)]));
      await uji(tester, menunggu, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Menunggu jawaban sopir...'), findsOneWidget);
        expect(find.text('Tugaskan sopir'), findsNothing);
      });
    });

    testWidgets('unit sudah bersopir: nama sopir tampil, tidak ada tombol tugaskan', (tester) async {
      final s = serverPesanan(bookingJson(1, units: [unitJson(11, bersopir: true, supirId: 4)]));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.textContaining('Joko'), findsOneWidget);
        expect(find.text('Tugaskan sopir'), findsNothing);
      });
    });

    testWidgets('unit berjalan: hanya catat pengembalian (tanpa jemput), pesanan tidak bisa dibatalkan', (tester) async {
      final s = serverPesanan(bookingJson(1, status: 2, units: [unitJson(11, status: 2)]));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Catat pengembalian'), findsOneWidget);
        expect(find.text('Konfirmasi jemput'), findsNothing);
        expect(find.text('Batalkan pesanan'), findsNothing);
      });
    });

    testWidgets('pesanan selesai: tanpa aksi pengembalian/jemput/batal; laporan sesudah masih bisa', (tester) async {
      final s = serverPesanan(bookingJson(1, status: 3, units: [unitJson(11, status: 3, pengembalian: {
        'tanggal_kembali_aktual': '2026-10-10 11:00:00', 'kondisi': 1, 'kondisi_label': 'Baik', 'denda_flag': true, 'nominal_denda': 100000,
      })]));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Catat pengembalian'), findsNothing);
        expect(find.text('Batalkan pesanan'), findsNothing);
        expect(find.text('Lapor sesudah'), findsOneWidget);
        expect(find.text('Denda'), findsOneWidget);
      });
    });

    testWidgets('batalkan: dialog alasan, lalu POST cancel dengan catatan', (tester) async {
      final s = serverPesanan(bookingJson(1))..rute['POST /api/admin/bookings/1/cancel'] = ok(bookingJson(1, status: 0));

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        await tester.ensureVisible(find.text('Batalkan pesanan'));
        await tester.tap(find.text('Batalkan pesanan'));
        await tester.pumpAndSettle();
        await tester.enterText(find.byType(TextField), 'Pelanggan batal');
        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Batalkan pesanan')));
        await tester.pumpAndSettle();

        expect(s.body('POST /api/admin/bookings/1/cancel'), {'catatan': 'Pelanggan batal'});
      });
    });

    testWidgets('pembayaran menunggu verifikasi: buka lembar, verifikasi terkirim setelah konfirmasi', (tester) async {
      final s = serverPesanan(bookingJson(1, pembayarans: [pembayaranJson(1, status: 2)]))
        ..rute['POST /api/admin/pembayaran/1/verifikasi'] = ok({'pembayaran': pembayaranJson(1, status: 1), 'invoice_terkirim': true});

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        await tester.tap(find.text('DP'));
        await tester.pumpAndSettle();
        expect(find.text('Verifikasi'), findsOneWidget);
        expect(find.text('Tolak'), findsOneWidget);

        await tester.tap(find.text('Verifikasi'));
        await tester.pumpAndSettle();
        expect(s.permintaan('POST /api/admin/pembayaran/1/verifikasi'), isEmpty);

        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Verifikasi')));
        await tester.pumpAndSettle();
        expect(s.permintaan('POST /api/admin/pembayaran/1/verifikasi'), hasLength(1));
        expect(find.text('Pembayaran diverifikasi.'), findsOneWidget);
      });
    });

    testWidgets('pembayaran belum dibayar: tandai lunas dengan metode terpilih', (tester) async {
      final s = serverPesanan(bookingJson(1, pembayarans: [pembayaranJson(1, status: 0)]))
        ..rute['POST /api/admin/pembayaran/1/lunas'] = ok({'pembayaran': pembayaranJson(1, status: 1), 'invoice_terkirim': true});

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        await tester.tap(find.text('DP'));
        await tester.pumpAndSettle();
        await tester.tap(find.text('QRIS'));
        await tester.pump();
        await tester.tap(find.text('Tandai lunas'));
        await tester.pumpAndSettle();
        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Tandai lunas')));
        await tester.pumpAndSettle();

        expect(s.body('POST /api/admin/pembayaran/1/lunas'), {'metode_pembayaran': 3});
      });
    });

    testWidgets('galat memuat: pesan dan coba lagi, bukan layar putih', (tester) async {
      final s = Server({'GET /api/admin/bookings/1': galat(404, 'Data tidak ditemukan.')});

      await uji(tester, s, const PesananDetailScreen(bookingId: 1), (_, __) async {
        await tester.pumpAndSettle();
        expect(find.text('Data tidak ditemukan.'), findsOneWidget);
        expect(find.text('Coba lagi'), findsOneWidget);
      });
    });
  });

  group('PengembalianFormScreen', () {
    UnitAdminModel unit() => UnitAdminModel.fromJson(unitJson(11, status: 2));

    UnitAdminModel unitDenganJadwal(String kembali) => UnitAdminModel.fromJson({...unitJson(11, status: 2), 'tanggal_kembali': kembali});

    testWidgets('jadwal kembali sudah lewat: pratinjau denda tampil', (tester) async {
      // tanggal tetap di masa lalu supaya tes tidak bergantung pada jam sistem
      await uji(tester, Server(), PengembalianFormScreen(bookingId: 1, unit: unitDenganJadwal('2020-01-01 09:00:00')), (_, __) async {
        await tester.pump();
        expect(find.textContaining('Terlambat'), findsOneWidget);
        expect(find.textContaining('akan ditagihkan'), findsOneWidget);
      });
    });

    testWidgets('jadwal kembali masih jauh: tepat waktu, tanpa denda', (tester) async {
      await uji(tester, Server(), PengembalianFormScreen(bookingId: 1, unit: unitDenganJadwal('2099-01-01 09:00:00')), (_, __) async {
        await tester.pump();
        expect(find.text('Tepat waktu, tidak ada denda.'), findsOneWidget);
        expect(find.textContaining('Terlambat'), findsNothing);
      });
    });

    testWidgets('kirim: konfirmasi ringkasan, lalu multipart terkirim', (tester) async {
      final s = serverPesanan(bookingJson(1, status: 2, units: [unitJson(11, status: 2)]))
        ..rute['POST /api/admin/detail/11/pengembalian'] = ok({'jam_terlambat': 3, 'nominal_denda': 150000, 'status_pesanan': 'Done'});

      await uji(tester, s, PengembalianFormScreen(bookingId: 1, unit: unit()), (_, __) async {
        await tester.pump();
        await tester.tap(find.text('Rusak'));
        await tester.pump();
        expect(find.textContaining('ditandai rusak'), findsOneWidget);

        await tester.ensureVisible(tombol('Catat pengembalian'));
        await tester.tap(tombol('Catat pengembalian'));
        await tester.pumpAndSettle();
        expect(find.text('Catat pengembalian?'), findsOneWidget);
        expect(find.textContaining('tidak dapat dibatalkan'), findsOneWidget);
        expect(s.permintaan('POST /api/admin/detail/11/pengembalian'), isEmpty);

        await tester.tap(find.descendant(of: find.byType(AlertDialog), matching: find.text('Catat')));
        await tester.pumpAndSettle();

        final r = s.permintaan('POST /api/admin/detail/11/pengembalian').single;
        expect(r.body, matches(RegExp(r'name="kondisi"\s+0')));
        expect(r.body, contains('name="tanggal_kembali_aktual"'));
      });
    });
  });

  group('daftar', () {
    testWidgets('pesanan: filter status mengirim query dan kata kunci dipakai', (tester) async {
      final s = serverPesanan(bookingJson(1));

      await uji(tester, s, const PesananListScreen(), (a, _) async {
        await a.muatPesanan();
        await tester.pumpAndSettle();
        expect(find.textContaining('Budi Santoso'), findsOneWidget);

        await tester.tap(find.text('Berjalan'));
        await tester.pumpAndSettle();
        expect(s.permintaan('GET /api/admin/bookings').last.url.queryParameters['status'], '2');

        await tester.enterText(find.byType(TextField), 'Budi');
        await tester.testTextInput.receiveAction(TextInputAction.search);
        await tester.pumpAndSettle();
        expect(s.permintaan('GET /api/admin/bookings').last.url.queryParameters['q'], 'Budi');
      });
    });

    testWidgets('pesanan: lencana "menunggu verifikasi" dan "perlu sopir" pada kartu', (tester) async {
      final s = serverPesanan(bookingJson(1, pembayarans: [pembayaranJson(1, status: 2)], units: [unitJson(11, bersopir: true)]));

      await uji(tester, s, const PesananListScreen(), (a, _) async {
        await a.muatPesanan();
        await tester.pumpAndSettle();
        expect(find.text('Pembayaran menunggu verifikasi'), findsOneWidget);
        expect(find.text('Perlu sopir'), findsOneWidget);
      });
    });

    testWidgets('pesanan kosong: pesan jelas', (tester) async {
      final s = Server({'GET /api/admin/bookings': ok([], meta: meta())});

      await uji(tester, s, const PesananListScreen(), (a, _) async {
        await a.muatPesanan();
        await tester.pumpAndSettle();
        expect(find.text('Tidak ada pesanan'), findsOneWidget);
      });
    });

    testWidgets('pembayaran: kosong pada filter menunggu verifikasi memberi pesan yang tepat', (tester) async {
      final s = Server({'GET /api/admin/pembayaran': ok([], meta: meta())});

      await uji(tester, s, const PembayaranListScreen(), (a, _) async {
        await a.muatPembayaran();
        await tester.pumpAndSettle();
        expect(find.text('Tidak ada pembayaran menunggu verifikasi'), findsOneWidget);
      });
    });

    testWidgets('pembayaran: ketuk kartu membuka lembar dengan tombol verifikasi', (tester) async {
      final s = Server({'GET /api/admin/pembayaran': ok([pembayaranJson(7, status: 2, booking: 3)], meta: meta(total: 1))});

      await uji(tester, s, const PembayaranListScreen(), (a, _) async {
        await a.muatPembayaran();
        await tester.pumpAndSettle();
        await tester.tap(find.text('DP'));
        await tester.pumpAndSettle();
        expect(find.text('Verifikasi'), findsOneWidget);
        expect(find.text('Pesanan'), findsOneWidget);
      });
    });
  });

  group('AdminHomeScreen', () {
    testWidgets('perlu ditindak tampil dengan angka; ketuk pindah ke tab yang tepat', (tester) async {
      final s = Server({'GET /api/admin/dashboard': ok(dashboardJson(menunggu: 4, perluSopir: 2))});
      int? tujuan;

      await uji(tester, s, AdminHomeScreen(pindahTab: (t) => tujuan = t), (a, _) async {
        await a.muatDashboard();
        await tester.pumpAndSettle();
        expect(find.text('6 hal perlu ditindak.'), findsOneWidget);
        expect(find.text('4'), findsWidgets);

        await tester.tap(find.text('Pembayaran menunggu verifikasi'));
        expect(tujuan, 2);
        await tester.tap(find.text('Unit perlu penugasan sopir'));
        expect(tujuan, 1);
      });
    });

    testWidgets('galat dashboard: pesan dan coba lagi', (tester) async {
      final s = Server({'GET /api/admin/dashboard': galat(500, 'Server error')});

      await uji(tester, s, AdminHomeScreen(pindahTab: (_) {}), (a, _) async {
        await a.muatDashboard();
        await tester.pumpAndSettle();
        expect(find.text('Server error'), findsOneWidget);
        expect(find.text('Coba lagi'), findsOneWidget);
      });
    });
  });

  group('form master', () {
    Server serverReferensi() => Server({
          'GET /api/admin/referensi': ok({'tipe': [{'id': 1, 'nama_tipe': 'MPV'}], 'model': [{'id': 1, 'nama': 'Avanza', 'tipe_id': 1}]}),
          'GET /api/admin/mobils': ok([], meta: meta()),
        });

    testWidgets('mobil: plat salah ditolak di klien tanpa permintaan', (tester) async {
      final s = serverReferensi();

      await uji(tester, s, const ArmadaFormScreen(), (_, __) async {
        await tester.pumpAndSettle();
        await tester.enterText(find.widgetWithText(TextFormField, 'Plat nomor'), 'ab-1234');
        await tester.enterText(find.widgetWithText(TextFormField, 'Merk'), 'Toyota');
        await tester.enterText(find.widgetWithText(TextFormField, 'Tahun'), '2023');
        await tester.enterText(find.widgetWithText(TextFormField, 'Harga sewa per hari (Rp)'), '500000');
        await tester.ensureVisible(find.widgetWithText(ElevatedButton, 'Tambah mobil'));
        await tester.tap(find.widgetWithText(ElevatedButton, 'Tambah mobil'));
        await tester.pumpAndSettle();

        expect(find.text('Format: AB 1234 CD (dipisah spasi)'), findsOneWidget);
        expect(find.text('Pilih model mobil'), findsOneWidget);
        expect(s.permintaan('POST /api/admin/mobils'), isEmpty);
      });
    });

    testWidgets('mobil: isian valid terkirim dengan plat huruf besar', (tester) async {
      final s = serverReferensi()..rute['POST /api/admin/mobils'] = ok(mobilJson(9));

      await uji(tester, s, const ArmadaFormScreen(), (_, __) async {
        await tester.pumpAndSettle();
        await tester.tap(find.byType(DropdownButtonFormField<int>));
        await tester.pumpAndSettle();
        await tester.tap(find.text('Avanza').last);
        await tester.pumpAndSettle();
        await tester.enterText(find.widgetWithText(TextFormField, 'Plat nomor'), 'ab 1234 cd');
        await tester.enterText(find.widgetWithText(TextFormField, 'Merk'), 'Toyota');
        await tester.enterText(find.widgetWithText(TextFormField, 'Tahun'), '2023');
        await tester.enterText(find.widgetWithText(TextFormField, 'Harga sewa per hari (Rp)'), '500000');
        await tester.ensureVisible(find.widgetWithText(ElevatedButton, 'Tambah mobil'));
        await tester.tap(find.widgetWithText(ElevatedButton, 'Tambah mobil'));
        await tester.pumpAndSettle();

        final b = s.body('POST /api/admin/mobils');
        expect(b['plat_nomor'], 'AB 1234 CD');
        expect(b['master_mobil_id'], 1);
        expect(b['harga_sewa'], 500000);
        expect(b['tahun'], 2023);
        expect(b.containsKey('harga_all_in'), isFalse, reason: 'kosong tidak dikirim');
      });
    });

    testWidgets('sopir baru: kata sandi kurang dari 8 karakter ditolak di klien', (tester) async {
      final s = Server();

      await uji(tester, s, const SopirFormScreen(), (_, __) async {
        await tester.enterText(find.widgetWithText(TextFormField, 'Nama'), 'Joko');
        await tester.enterText(find.widgetWithText(TextFormField, 'Email (untuk login)'), 'joko@x.com');
        await tester.enterText(find.widgetWithText(TextFormField, 'Kata sandi'), '123');
        await tester.enterText(find.widgetWithText(TextFormField, 'No. HP'), '0813');
        await tester.enterText(find.widgetWithText(TextFormField, 'Alamat'), 'Bantul');
        await tester.ensureVisible(find.widgetWithText(ElevatedButton, 'Tambah sopir'));
        await tester.tap(find.widgetWithText(ElevatedButton, 'Tambah sopir'));
        await tester.pumpAndSettle();

        expect(find.text('Minimal 8 karakter'), findsOneWidget);
        expect(s.log, isEmpty);
      });
    });

    testWidgets('penyewa: email tidak valid ditolak; isian valid terkirim', (tester) async {
      final s = Server({'POST /api/admin/penyewa': ok({'id': 1}), 'GET /api/admin/penyewa': ok([], meta: meta())});

      await uji(tester, s, const PenyewaFormScreen(), (_, __) async {
        Future<void> isi(String email) async {
          await tester.enterText(find.widgetWithText(TextFormField, 'Nama'), 'Budi');
          await tester.enterText(find.widgetWithText(TextFormField, 'Email (invoice dikirim ke sini)'), email);
          await tester.enterText(find.widgetWithText(TextFormField, 'No. HP'), '0811');
          await tester.enterText(find.widgetWithText(TextFormField, 'Alamat'), 'Jl. A');
          await tester.enterText(find.widgetWithText(TextFormField, 'Asal kota'), 'Sleman');
          await tester.ensureVisible(find.widgetWithText(ElevatedButton, 'Tambah penyewa'));
          await tester.tap(find.widgetWithText(ElevatedButton, 'Tambah penyewa'));
          await tester.pumpAndSettle();
        }

        await isi('bukan-email');
        expect(find.text('Email tidak valid'), findsOneWidget);
        expect(s.permintaan('POST /api/admin/penyewa'), isEmpty);

        await isi('budi@x.com');
        expect(s.body('POST /api/admin/penyewa')['email'], 'budi@x.com');
      });
    });
  });
}
