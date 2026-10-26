import 'dart:async';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/providers/admin_master_provider.dart';

import 'support.dart';

Server serverMaster() => Server({
      'GET /api/admin/mobils': ok([mobilJson(1), mobilJson(2, plat: 'B 2 EF')], meta: meta(total: 2)),
      'GET /api/admin/supirs': ok([{'id': 1, 'nama': 'Joko', 'status': 1, 'status_text': 'Available', 'aktif': true}], meta: meta(total: 1)),
      'GET /api/admin/penyewa': ok([{'id': 1, 'name': 'Budi', 'email': 'b@x.com'}], meta: meta(total: 1)),
    });

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(siapkanSesi);

  group('armada', () {
    test('daftar dimuat; filter dan pencarian dikirim sebagai query', () async {
      final s = serverMaster();
      final prov = AdminMasterProvider();

      await pakaiServer(s, () => prov.aturFilterMobil(status: 4, kataKunci: ' avanza '));

      expect(prov.mobils.items, hasLength(2));
      final q = s.permintaan('GET /api/admin/mobils').last.url.queryParameters;
      expect(q['status'], '4');
      expect(q['q'], 'avanza');

      await pakaiServer(s, () => prov.aturFilterMobil(hapusStatus: true));
      expect(s.permintaan('GET /api/admin/mobils').last.url.queryParameters.containsKey('status'), isFalse);
    });

    test('tambah tanpa gambar: JSON ke POST /mobils lalu daftar disegarkan', () async {
      final s = serverMaster()..rute['POST /api/admin/mobils'] = ok(mobilJson(3));
      final h = await pakaiServer(s, () => AdminMasterProvider().simpanMobil(null, {'plat_nomor': 'AB 1 CD', 'tahun': 2023}));

      expect(h.ok, isTrue);
      expect(s.body('POST /api/admin/mobils'), {'plat_nomor': 'AB 1 CD', 'tahun': 2023});
      expect(s.kunci, contains('GET /api/admin/mobils'));
    });

    test('tambah dengan gambar: multipart', () async {
      final f = File('${Directory.systemTemp.path}/mobil_uji.jpg')..writeAsBytesSync([1, 2, 3]);
      addTearDown(() => f.deleteSync());
      final s = serverMaster()..rute['POST /api/admin/mobils'] = ok(mobilJson(3));

      await pakaiServer(s, () => AdminMasterProvider().simpanMobil(null, {'plat_nomor': 'AB 1 CD', 'tahun': 2023}, gambar: f));

      final r = s.permintaan('POST /api/admin/mobils').single;
      expect(r.headers['content-type'], startsWith('multipart/form-data'));
      expect(r.body, matches(RegExp(r'name="plat_nomor"\s+AB 1 CD')));
      expect(r.body, contains('filename="mobil_uji.jpg"'));
    });

    test('ubah: PUT data, lalu gambar dikirim terpisah; gagal PUT → gambar tidak dikirim', () async {
      final f = File('${Directory.systemTemp.path}/mobil_uji2.jpg')..writeAsBytesSync([1, 2, 3]);
      addTearDown(() => f.deleteSync());

      final s = serverMaster()
        ..rute['PUT /api/admin/mobils/1'] = ok(mobilJson(1))
        ..rute['POST /api/admin/mobils/1/gambar'] = ok(mobilJson(1));
      expect((await pakaiServer(s, () => AdminMasterProvider().simpanMobil(1, {'merk': 'Honda'}, gambar: f))).ok, isTrue);
      expect(s.kunci.where((k) => k.startsWith('PUT') || k.startsWith('POST')), ['PUT /api/admin/mobils/1', 'POST /api/admin/mobils/1/gambar']);

      final gagal = serverMaster()..rute['PUT /api/admin/mobils/1'] = galat(422, 'Plat nomor sudah dipakai.');
      final h = await pakaiServer(gagal, () => AdminMasterProvider().simpanMobil(1, {'merk': 'Honda'}, gambar: f));
      expect(h.galat, 'Plat nomor sudah dipakai.');
      expect(gagal.kunci.where((k) => k.contains('gambar')), isEmpty);
    });

    test('ubah status: kirim status; mobil disewa ditolak server dengan pesan', () async {
      final s = serverMaster()..rute['PATCH /api/admin/mobils/1/status'] = ok(mobilJson(1, status: 4));
      expect((await pakaiServer(s, () => AdminMasterProvider().ubahStatusMobil(1, 4))).ok, isTrue);
      expect(s.body('PATCH /api/admin/mobils/1/status'), {'status': 4});

      final tolak = serverMaster()..rute['PATCH /api/admin/mobils/2/status'] = galat(422, 'Mobil sedang disewa; ubah status setelah dikembalikan.');
      expect((await pakaiServer(tolak, () => AdminMasterProvider().ubahStatusMobil(2, 0))).galat, contains('sedang disewa'));
    });

    test('hapus: sukses menyegarkan daftar; mobil berriwayat ditolak server', () async {
      final s = serverMaster()..rute['DELETE /api/admin/mobils/1'] = ok(null);
      expect((await pakaiServer(s, () => AdminMasterProvider().hapusMobil(1))).ok, isTrue);
      expect(s.kunci, contains('GET /api/admin/mobils'));

      final tolak = serverMaster()..rute['DELETE /api/admin/mobils/2'] = galat(422, 'Mobil sudah punya riwayat sewa dan tidak dapat dihapus.');
      final h = await pakaiServer(tolak, () => AdminMasterProvider().hapusMobil(2));
      expect(h.galat, contains('riwayat sewa'));
      expect(tolak.kunci, ['DELETE /api/admin/mobils/2'], reason: 'gagal → tidak ada penyegaran');
    });

    test('referensi dimuat sekali lalu dipakai ulang', () async {
      final s = serverMaster()..rute['GET /api/admin/referensi'] = ok({
            'tipe': [{'id': 1, 'nama_tipe': 'MPV'}],
            'model': [{'id': 1, 'nama': 'Avanza', 'tipe_id': 1}],
          });
      final prov = AdminMasterProvider();

      await pakaiServer(s, () async {
        await prov.muatReferensi();
        await prov.muatReferensi();
      });

      expect(prov.referensi.value.model.single.nama, 'Avanza');
      expect(s.permintaan('GET /api/admin/referensi'), hasLength(1));
    });
  });

  group('sopir dan penyewa', () {
    test('tambah sopir: JSON; ubah/nonaktifkan: PUT dengan aktif=false', () async {
      final s = serverMaster()
        ..rute['POST /api/admin/supirs'] = ok({'id': 2})
        ..rute['PUT /api/admin/supirs/1'] = ok({'id': 1});

      await pakaiServer(s, () => AdminMasterProvider().simpanSupir(null, {'name': 'Joko', 'email': 'j@x.com', 'password': 'rahasia123'}));
      expect(s.body('POST /api/admin/supirs')['password'], 'rahasia123');

      await pakaiServer(s, () => AdminMasterProvider().simpanSupir(1, {'name': 'Joko', 'aktif': false}));
      expect(s.body('PUT /api/admin/supirs/1')['aktif'], false);
    });

    test('sopir bertugas tidak dapat dinonaktifkan: pesan server tampil', () async {
      final s = serverMaster()..rute['PUT /api/admin/supirs/1'] = galat(422, 'Sopir sedang bertugas; nonaktifkan setelah tugasnya selesai.');
      final h = await pakaiServer(s, () => AdminMasterProvider().simpanSupir(1, {'aktif': false}));

      expect(h.galat, contains('sedang bertugas'));
    });

    test('email kembar: pesan validasi pertama dari server', () async {
      final s = serverMaster()..rute['POST /api/admin/penyewa'] = (422, {'status': false, 'message': 'The given data was invalid.', 'errors': {'email': ['Email sudah dipakai.']}});
      final h = await pakaiServer(s, () => AdminMasterProvider().simpanPenyewa(null, {'email': 'b@x.com'}));

      expect(h.galat, 'Email sudah dipakai.');
    });

    test('penyewa: tambah (POST) dan ubah (PUT)', () async {
      final s = serverMaster()
        ..rute['POST /api/admin/penyewa'] = ok({'id': 5})
        ..rute['PUT /api/admin/penyewa/5'] = ok({'id': 5});
      final prov = AdminMasterProvider();

      await pakaiServer(s, () => prov.simpanPenyewa(null, {'name': 'Budi'}));
      await pakaiServer(s, () => prov.simpanPenyewa(5, {'name': 'Budi S'}));

      expect(s.kunci.where((k) => k.startsWith('POST') || k.startsWith('PUT')), ['POST /api/admin/penyewa', 'PUT /api/admin/penyewa/5']);
    });

    test('pencarian penyewa dikirim sebagai query', () async {
      final s = serverMaster();
      await pakaiServer(s, () => AdminMasterProvider().aturKataKunciPenyewa(' Budi '));

      expect(s.permintaan('GET /api/admin/penyewa').last.url.queryParameters['q'], 'Budi');
    });
  });

  group('rekap', () {
    test('bulan dikirim sebagai query dan hasil terbaca', () async {
      final s = Server({'GET /api/admin/rekap': ok({
        'bulan': '2026-09',
        'pesanan': {'total': 3, 'selesai': 2, 'dibatalkan': 1},
        'pendapatan': {'total': 900000},
        'armada': {'utilisasi_persen': 4.5, 'per_mobil': []},
      })});
      final prov = AdminMasterProvider();

      await pakaiServer(s, () => prov.muatRekap('2026-09'));

      expect(s.permintaan('GET /api/admin/rekap').last.url.queryParameters['bulan'], '2026-09');
      expect(prov.bulanRekap, '2026-09');
      expect(prov.rekap.value?.pesananTotal, 3);
      expect(prov.rekap.value?.pendapatanTotal, 900000);
    });

    test('ganti bulan cepat: hasil bulan lama yang telat tidak menimpa', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/admin/rekap'] = (req) async {
        final bulan = req.url.queryParameters['bulan'];
        if (bulan == '2026-08') await lambat.future;
        return ok({'bulan': bulan, 'pesanan': {'total': bulan == '2026-08' ? 99 : 1}});
      };
      final prov = AdminMasterProvider();

      await pakaiServer(s, () async {
        final lama = prov.muatRekap('2026-08');
        await prov.muatRekap('2026-09');
        lambat.complete();
        await lama;
      });

      expect(prov.rekap.value?.bulan, '2026-09');
      expect(prov.rekap.value?.pesananTotal, 1);
    });

    test('galat server: pesan tampil, tidak macet', () async {
      final prov = AdminMasterProvider();
      await pakaiServer(Server({'GET /api/admin/rekap': galat(500, 'Server error')}), prov.muatRekap);

      expect(prov.rekap.error, 'Server error');
      expect(prov.rekap.loading, isFalse);
    });
  });

  test('reset membuang semua data dan filter', () async {
    final prov = AdminMasterProvider();
    await pakaiServer(serverMaster(), () async {
      await prov.aturFilterMobil(status: 1, kataKunci: 'x');
      await prov.muatSupir();
    });
    expect(prov.mobils.loaded, isTrue);

    prov.reset();

    expect(prov.mobils.items, isEmpty);
    expect(prov.supirs.items, isEmpty);
    expect(prov.kataKunciMobil, '');
    expect(prov.filterStatusMobil, isNull);
  });

  test('aksi ganda ditolak selama permintaan berjalan', () async {
    final lepas = Completer<void>();
    final s = serverMaster();
    s.dinamis['PATCH /api/admin/mobils/1/status'] = (_) async { await lepas.future; return ok(mobilJson(1)); };
    final prov = AdminMasterProvider();

    final hasil = await pakaiServer(s, () async {
      final a = prov.ubahStatusMobil(1, 4);
      final b = prov.ubahStatusMobil(1, 0);
      lepas.complete();
      return [await a, await b];
    });

    expect(hasil[0].ok, isTrue);
    expect(hasil[1].galat, contains('masih diproses'));
    expect(s.permintaan('PATCH /api/admin/mobils/1/status'), hasLength(1));
  });
}
