import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/core/utils/bukti_validator.dart';
import 'package:harkat_rental/providers/booking_provider.dart';
import 'package:harkat_rental/providers/mobil_provider.dart';

import 'support.dart';

(int, Object) halaman(List<int> ids, {int page = 1, Object last = 2}) =>
    ok([for (final i in ids) mobilJson(i, plat: 'AB $i CD')], meta: {'current_page': page, 'last_page': last, 'per_page': 10, 'total': 20});

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(siapkanSesi);

  group('BuktiValidator (aturan sama dengan server: jpg/png, maks 5 MB)', () {
    test('jpg, jpeg, png diterima, huruf besar juga', () {
      for (final f in ['/a/bukti.jpg', '/a/bukti.JPEG', '/a/bukti.png', '/a/BUKTI.PNG']) {
        expect(BuktiValidator.periksa(path: f, bytes: 1000), isNull, reason: f);
      }
    });

    test('format lain ditolak: pdf, gif, heic, tanpa ekstensi', () {
      for (final f in ['/a/b.pdf', '/a/b.gif', '/a/b.heic', '/a/b', '/a/b.']) {
        expect(BuktiValidator.periksa(path: f, bytes: 1000), 'Format foto harus JPG atau PNG.', reason: f);
      }
    });

    test('batas ukuran: tepat 5 MB boleh, lebih 1 byte ditolak, kosong ditolak', () {
      expect(BuktiValidator.periksa(path: 'a.jpg', bytes: BuktiValidator.maksBytes), isNull);
      expect(BuktiValidator.periksa(path: 'a.jpg', bytes: BuktiValidator.maksBytes + 1), 'Ukuran foto maksimal 5 MB.');
      expect(BuktiValidator.periksa(path: 'a.jpg', bytes: 0), contains('kosong'));
    });

    test('titik pada nama folder tidak dianggap ekstensi', () {
      expect(BuktiValidator.periksa(path: '/storage/emulated.0/DCIM/foto', bytes: 100), 'Format foto harus JPG atau PNG.');
    });
  });

  group('MobilProvider (BUGS #22, #45)', () {
    test('muat lagi gagal: daftar yang sudah ada TIDAK hilang dan tidak masuk layar galat', () async {
      final s = Server();
      var panggilan = 0;
      s.dinamis['GET /api/mobils'] = (req) async {
        panggilan++;
        return panggilan == 1 ? halaman([1, 2]) : galat(500, 'Server error');
      };
      final prov = MobilProvider();

      await pakaiServer(s, () async {
        await prov.loadMobils(reset: true);
        expect(prov.mobils, hasLength(2));
        expect(prov.hasMore, isTrue);

        await prov.loadMore();           // halaman 2 gagal
      });

      expect(prov.mobils, hasLength(2), reason: 'daftar tidak boleh hilang');
      expect(prov.state, MobilLoadState.loaded, reason: 'layar galat penuh hanya untuk daftar kosong');
      expect(prov.error, 'Server error');
      expect(prov.hasMore, isTrue, reason: 'halaman berikutnya bisa dicoba lagi saat digulir');
    });

    test('muat pertama gagal dan daftar kosong: keadaan galat', () async {
      final prov = MobilProvider();
      await pakaiServer(Server({'GET /api/mobils': galat(500, 'Server error')}), () => prov.loadMobils(reset: true));

      expect(prov.state, MobilLoadState.error);
      expect(prov.mobils, isEmpty);
    });

    test('halaman berikutnya ditambahkan; berhenti di halaman terakhir', () async {
      final s = Server();
      s.dinamis['GET /api/mobils'] = (req) async {
        final page = int.parse(req.url.queryParameters['page']!);
        return halaman(page == 1 ? [1, 2] : [3, 4], page: page, last: 2);
      };
      final prov = MobilProvider();

      await pakaiServer(s, () async {
        await prov.loadMobils(reset: true);
        await prov.loadMore();
        final sebelum = s.log.length;
        await prov.loadMore();           // sudah halaman terakhir
        expect(s.log.length, sebelum);
      });

      expect(prov.mobils.map((m) => m.id), [1, 2, 3, 4]);
      expect(prov.hasMore, isFalse);
    });

    test('last_page berupa string tetap terbaca (tidak crash)', () async {
      final prov = MobilProvider();
      await pakaiServer(Server({'GET /api/mobils': halaman([1], last: '3')}), () => prov.loadMobils(reset: true));

      expect(prov.state, MobilLoadState.loaded);
      expect(prov.hasMore, isTrue);
    });

    test('ganti filter saat muat lagi berjalan: hasil lama dibuang, tidak bercampur', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/mobils'] = (req) async {
        final q = req.url.queryParameters['search'] ?? '';
        final page = int.parse(req.url.queryParameters['page']!);
        if (q.isEmpty && page == 2) {
          await lambat.future;                 // halaman 2 dari pencarian lama lambat
          return halaman([98, 99], page: 2);
        }
        return q.isEmpty ? halaman([1, 2]) : halaman([50], last: 1);
      };
      final prov = MobilProvider();

      await pakaiServer(s, () async {
        await prov.loadMobils(reset: true);
        final lagi = prov.loadMore();
        await prov.applyFilter(search: 'avanza');   // pengguna mengetik pencarian
        lambat.complete();
        await lagi;
      });

      expect(prov.mobils.map((m) => m.id), [50], reason: 'halaman 2 pencarian lama tidak boleh ikut masuk');
    });

    test('reset (logout) mengosongkan semuanya dan membuang respons yang terlambat', () async {
      final lambat = Completer<void>();
      final s = Server();
      s.dinamis['GET /api/mobils'] = (req) async {
        await lambat.future;
        return halaman([1, 2]);
      };
      final prov = MobilProvider();

      await pakaiServer(s, () async {
        final f = prov.loadMobils(reset: true);
        prov.reset();
        lambat.complete();
        await f;
      });

      expect(prov.mobils, isEmpty);
      expect(prov.state, MobilLoadState.initial);
      expect(prov.search, '');
    });
  });

  group('BookingProvider.reset (BUGS #40)', () {
    test('data pesanan pengguna sebelumnya dibuang', () async {
      final prov = BookingProvider();
      await pakaiServer(Server({'GET /api/bookings': ok([])}), prov.loadBookings);
      expect(prov.state, BookingLoadState.loaded);

      prov.reset();

      expect(prov.bookings, isEmpty);
      expect(prov.riwayats, isEmpty);
      expect(prov.state, BookingLoadState.initial);
      expect(prov.error, '');
      expect(prov.estimateData, isNull);
    });
  });
}
