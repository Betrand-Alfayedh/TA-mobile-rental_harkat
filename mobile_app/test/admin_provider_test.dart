import 'dart:async';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/models/admin_model.dart';
import 'package:harkat_rental/providers/admin_provider.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

import 'support.dart';

/// Rute dasar untuk segarkan-setelah-aksi (detail, daftar pesanan, daftar pembayaran, dashboard).
Server serverDasar() => Server({
      'GET /api/admin/bookings/1': ok(bookingJson(1)),
      'GET /api/admin/bookings': ok([bookingJson(1)], meta: meta(total: 1)),
      'GET /api/admin/pembayaran': ok([pembayaranJson(1, status: 2)], meta: meta(total: 1)),
      'GET /api/admin/dashboard': ok(dashboardJson()),
    });

PembayaranAdminModel bayar({int status = 2, int id = 1}) => PembayaranAdminModel.fromJson(pembayaranJson(id, status: status));
UnitAdminModel unit({int id = 11}) => UnitAdminModel.fromJson(unitJson(id));
BookingAdminModel pesanan({int id = 1}) => BookingAdminModel.fromJson(bookingJson(id));

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(siapkanSesi);

  group('pemuatan dan paginasi', () {
    test('dashboard dimuat', () async {
      final prov = AdminProvider();
      await pakaiServer(serverDasar(), prov.muatDashboard);

      expect(prov.dashboard.value?.menungguVerifikasi, 2);
      expect(prov.dashboard.loading, isFalse);
    });

    test('pesanan: halaman 1, lalu "lagi" menambah halaman 2, berhenti di halaman terakhir', () async {
      final s = Server({'GET /api/admin/bookings': ok([])});
      s.dinamis['GET /api/admin/bookings'] = (req) async {
        final page = int.parse(req.url.queryParameters['page']!);
        return ok([bookingJson(page * 10), bookingJson(page * 10 + 1)], meta: meta(page: page, last: 2, total: 4));
      };
      final prov = AdminProvider();

      await pakaiServer(s, () async {
        await prov.muatPesanan();
        expect(prov.bookings.items.map((b) => b.id), [10, 11]);
        expect(prov.bookings.hasMore, isTrue);

        await prov.pesananLagi();
        expect(prov.bookings.items.map((b) => b.id), [10, 11, 20, 21]);
        expect(prov.bookings.hasMore, isFalse);

        final sebelum = s.log.length;
        await prov.pesananLagi();    // sudah halaman terakhir → tidak ada permintaan
        expect(s.log.length, sebelum);
      });
    });

    test('filter dan kata kunci dikirim sebagai query', () async {
      final s = serverDasar();
      final prov = AdminProvider();

      await pakaiServer(s, () => prov.aturFilterPesanan(status: 2, kataKunci: '  Budi  '));

      final q = s.permintaan('GET /api/admin/bookings').last.url.queryParameters;
      expect(q['status'], '2');
      expect(q['q'], 'Budi');
      expect(q['page'], '1');

      await pakaiServer(s, () => prov.aturFilterPesanan(hapusStatus: true));
      expect(s.permintaan('GET /api/admin/bookings').last.url.queryParameters.containsKey('status'), isFalse);
    });

    test('pembayaran dimulai dari filter "menunggu verifikasi"', () async {
      final s = serverDasar();
      final prov = AdminProvider();
      await pakaiServer(s, prov.muatPembayaran);

      expect(s.permintaan('GET /api/admin/pembayaran').last.url.queryParameters['status'], '2');

      await pakaiServer(s, () => prov.aturFilterPembayaran(semua: true));
      expect(s.permintaan('GET /api/admin/pembayaran').last.url.queryParameters.containsKey('status'), isFalse);
    });

    test('respons lama (filter sudah berganti) tidak menimpa hasil terbaru', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/admin/bookings'] = (req) async {
        if (req.url.queryParameters['status'] == null) {
          await lambat.future;                                         // permintaan pertama (Semua) lambat
          return ok([bookingJson(100)], meta: meta(total: 1));
        }
        return ok([bookingJson(200, status: 1)], meta: meta(total: 1));   // permintaan kedua (Booked) cepat
      };
      final prov = AdminProvider();

      await pakaiServer(s, () async {
        final pertama = prov.muatPesanan();
        await prov.aturFilterPesanan(status: 1);
        lambat.complete();
        await pertama;
      });

      expect(prov.bookings.items.map((b) => b.id), [200], reason: 'hasil filter terbaru harus bertahan');
    });

    test('galat memuat: pesan tampil; penyegaran senyap tidak menghapus data lama', () async {
      final prov = AdminProvider();
      await pakaiServer(serverDasar(), prov.muatPesanan);
      expect(prov.bookings.items, hasLength(1));

      await pakaiServer(Server({'GET /api/admin/bookings': galat(500, 'x')}), () => prov.muatPesanan(silent: true));
      expect(prov.bookings.items, hasLength(1));
      expect(prov.bookings.error, isNull);

      await pakaiServer(Server({'GET /api/admin/bookings': galat(500, 'Server error')}), prov.muatPesanan);
      expect(prov.bookings.error, 'Server error');
      expect(prov.bookings.loading, isFalse);
    });

    test('respons rusak tidak melempar dan tidak macet di loading', () async {
      final prov = AdminProvider();
      await http.runWithClient(prov.muatPesanan, () => MockClient((req) async => http.Response('<html/>', 200, request: req)));
      expect(prov.bookings.loading, isFalse);
      expect(prov.bookings.error, isNotNull);

      await pakaiServer(Server({'GET /api/admin/bookings': ok('bukan daftar', meta: meta())}), prov.muatPesanan);
      expect(prov.bookings.loading, isFalse);
    });

    test('detail: membuka pesanan lain tidak tercampur dengan respons lama', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/admin/bookings/1'] = (_) async { await lambat.future; return ok(bookingJson(1)); };
      s.rute['GET /api/admin/bookings/2'] = ok(bookingJson(2));
      final prov = AdminProvider();

      await pakaiServer(s, () async {
        final a = prov.muatDetail(1);
        await prov.muatDetail(2);
        lambat.complete();
        await a;
      });

      expect(prov.detail.value?.id, 2);
    });
  });

  group('aksi pembayaran', () {
    test('verifikasi: memanggil endpoint lalu menyegarkan detail, daftar, dan dashboard', () async {
      final s = serverDasar()..rute['POST /api/admin/pembayaran/1/verifikasi'] = ok({'pembayaran': pembayaranJson(1, status: 1), 'invoice_terkirim': true});
      final prov = AdminProvider();

      final h = await pakaiServer(s, () => prov.verifikasi(bayar()));

      expect(h.ok, isTrue);
      expect(h.info, '');
      expect(s.kunci.first, 'POST /api/admin/pembayaran/1/verifikasi');
      expect(s.kunci, containsAll(['GET /api/admin/bookings/1', 'GET /api/admin/bookings', 'GET /api/admin/pembayaran', 'GET /api/admin/dashboard']));
      expect(prov.submitting, isFalse);
    });

    test('verifikasi: invoice gagal terkirim tetap sukses tetapi memberi info', () async {
      final s = serverDasar()..rute['POST /api/admin/pembayaran/1/verifikasi'] = ok({'pembayaran': pembayaranJson(1, status: 1), 'invoice_terkirim': false});
      final h = await pakaiServer(s, () => AdminProvider().verifikasi(bayar()));

      expect(h.ok, isTrue);
      expect(h.info, contains('invoice gagal dikirim'));
    });

    test('ditolak server (422): galat dari server, tidak ada penyegaran', () async {
      final s = serverDasar()..rute['POST /api/admin/pembayaran/1/verifikasi'] = galat(422, 'Pembayaran ini tidak dalam status menunggu verifikasi.');
      final h = await pakaiServer(s, () => AdminProvider().verifikasi(bayar()));

      expect(h.galat, 'Pembayaran ini tidak dalam status menunggu verifikasi.');
      expect(s.kunci, ['POST /api/admin/pembayaran/1/verifikasi']);
    });

    test('tolak: kirim alasan; alasan kosong tidak dikirim', () async {
      final s = serverDasar()..rute['POST /api/admin/pembayaran/1/tolak'] = ok(pembayaranJson(1));
      final prov = AdminProvider();

      await pakaiServer(s, () => prov.tolak(bayar(), catatan: '  Nominal kurang '));
      expect(s.body('POST /api/admin/pembayaran/1/tolak'), {'catatan_admin': 'Nominal kurang'});

      await pakaiServer(s, () => prov.tolak(bayar(), catatan: '   '));
      expect(s.body('POST /api/admin/pembayaran/1/tolak'), isEmpty);
    });

    test('tandai lunas: kirim metode pembayaran', () async {
      final s = serverDasar()..rute['POST /api/admin/pembayaran/2/lunas'] = ok({'pembayaran': pembayaranJson(2, status: 1), 'invoice_terkirim': true});
      final h = await pakaiServer(s, () => AdminProvider().tandaiLunas(bayar(status: 0, id: 2), 3));

      expect(h.ok, isTrue);
      expect(s.body('POST /api/admin/pembayaran/2/lunas'), {'metode_pembayaran': 3});
    });
  });

  group('aksi pesanan dan unit', () {
    test('batalkan: alasan dikirim sebagai catatan', () async {
      final s = serverDasar()..rute['POST /api/admin/bookings/1/cancel'] = ok(bookingJson(1, status: 0));
      final h = await pakaiServer(s, () => AdminProvider().batalkan(pesanan(), catatan: 'Pelanggan batal'));

      expect(h.ok, isTrue);
      expect(s.body('POST /api/admin/bookings/1/cancel'), {'catatan': 'Pelanggan batal'});
    });

    test('tugaskan: info jumlah sopir yang ditawari', () async {
      final s = serverDasar()..rute['POST /api/admin/detail/11/penugasan'] = ok({'jumlah_sopir': 3, 'detail': unitJson(11)});
      final h = await pakaiServer(s, () => AdminProvider().tugaskan(1, unit()));

      expect(h.info, 'Tugas ditawarkan ke 3 sopir.');
    });

    test('tugaskan tanpa sopir tersedia: pesan server', () async {
      final s = serverDasar()..rute['POST /api/admin/detail/11/penugasan'] = galat(422, 'Tidak ada supir yang available.');
      final h = await pakaiServer(s, () => AdminProvider().tugaskan(1, unit()));

      expect(h.galat, 'Tidak ada supir yang available.');
    });

    test('jemput: PATCH ke endpoint unit; DP belum lunas ditolak server', () async {
      final s = serverDasar()..rute['PATCH /api/admin/detail/11/jemput'] = ok(unitJson(11, status: 2));
      expect((await pakaiServer(s, () => AdminProvider().jemput(1, unit()))).ok, isTrue);

      final s2 = serverDasar()..rute['PATCH /api/admin/detail/11/jemput'] = galat(422, 'DP belum diverifikasi. Verifikasi pembayaran DP terlebih dahulu.');
      expect((await pakaiServer(s2, () => AdminProvider().jemput(1, unit()))).galat, contains('DP belum diverifikasi'));
    });

    test('pengembalian: field multipart benar; terlambat memberi info denda', () async {
      final s = serverDasar()..rute['POST /api/admin/detail/11/pengembalian'] = ok({'jam_terlambat': 2, 'nominal_denda': 100000, 'status_pesanan': 'Done', 'detail': unitJson(11, status: 3)});
      final h = await pakaiServer(s, () => AdminProvider().catatPengembalian(1, unit(),
          tanggalAktual: '2026-10-10 10:30:00', kondisi: 1, catatan: ' Aman '));

      final r = s.permintaan('POST /api/admin/detail/11/pengembalian').single;
      expect(r.headers['content-type'], startsWith('multipart/form-data'));
      expect(r.headers['Authorization'], 'Bearer tok');
      expect(r.body, matches(RegExp(r'name="tanggal_kembali_aktual"\s+2026-10-10 10:30:00')));
      expect(r.body, matches(RegExp(r'name="kondisi"\s+1')));
      expect(r.body, matches(RegExp(r'name="catatan"\s+Aman')));
      expect(h.info, 'Terlambat 2 jam, denda tertagih.');
    });

    test('pengembalian tepat waktu: tanpa info denda; sudah dikembalikan: galat server', () async {
      final s = serverDasar()..rute['POST /api/admin/detail/11/pengembalian'] = ok({'jam_terlambat': 0, 'nominal_denda': 0});
      expect((await pakaiServer(s, () => AdminProvider().catatPengembalian(1, unit(), tanggalAktual: 'x', kondisi: 1))).info, '');

      final s2 = serverDasar()..rute['POST /api/admin/detail/11/pengembalian'] = galat(422, 'Unit mobil ini sudah dikembalikan sebelumnya.');
      expect((await pakaiServer(s2, () => AdminProvider().catatPengembalian(1, unit(), tanggalAktual: 'x', kondisi: 1))).galat, contains('sudah dikembalikan'));
    });

    test('laporan kondisi admin: jenis dan kondisi terkirim', () async {
      final s = serverDasar()..rute['POST /api/admin/detail/11/laporan-kondisi'] = ok({}, message: 'ok');
      final h = await pakaiServer(s, () => AdminProvider().kirimLaporan(1, unit(), jenis: 2, kondisi: 3, catatan: 'Kaca pecah'));

      expect(h.ok, isTrue);
      final r = s.permintaan('POST /api/admin/detail/11/laporan-kondisi').single;
      expect(r.body, matches(RegExp(r'name="jenis"\s+2')));
      expect(r.body, matches(RegExp(r'name="kondisi"\s+3')));
    });
  });

  group('keamanan aksi', () {
    test('aksi ganda ditolak selama permintaan pertama berjalan', () async {
      final lepas = Completer<void>();
      var panggilan = 0;
      final s = serverDasar();
      s.dinamis['POST /api/admin/pembayaran/1/verifikasi'] = (_) async {
        panggilan++;
        await lepas.future;
        return ok({'invoice_terkirim': true});
      };
      final prov = AdminProvider();

      final hasil = await pakaiServer(s, () async {
        final a = prov.verifikasi(bayar());
        final b = prov.verifikasi(bayar());           // sebelum yang pertama selesai
        lepas.complete();
        return [await a, await b];
      });

      expect(panggilan, 1);
      expect(hasil[0].ok, isTrue);
      expect(hasil[1].galat, contains('masih diproses'));
      expect(prov.submitting, isFalse);
    });

    test('galat jaringan: pesan galat dan tombol tidak terkunci selamanya', () async {
      final prov = AdminProvider();
      final h = await http.runWithClient(() => prov.verifikasi(bayar()), () => MockClient((_) async => throw Exception('putus')));

      expect(h.galat, isNotNull);
      expect(prov.submitting, isFalse);
    });

    test('reset membuang data dan filter pengguna sebelumnya', () async {
      final prov = AdminProvider();
      await pakaiServer(serverDasar(), () async {
        await prov.muatDashboard();
        await prov.aturFilterPesanan(status: 2, kataKunci: 'x');
      });
      expect(prov.bookings.loaded, isTrue);

      prov.reset();

      expect(prov.dashboard.value, isNull);
      expect(prov.bookings.items, isEmpty);
      expect(prov.kataKunciPesanan, '');
      expect(prov.filterStatusPesanan, isNull);
      expect(prov.filterStatusPembayaran, 2);
    });

    test('respons yang datang setelah reset (logout) dibuang', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/admin/bookings'] = (_) async { await lambat.future; return ok([bookingJson(1)], meta: meta(total: 1)); };
      final prov = AdminProvider();

      await pakaiServer(s, () async {
        final f = prov.muatPesanan();
        prov.reset();            // pengguna logout saat permintaan berjalan
        lambat.complete();
        await f;
      });

      expect(prov.bookings.items, isEmpty, reason: 'data pengguna lama tidak boleh muncul');
    });
  });
}
