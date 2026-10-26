import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/providers/booking_provider.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<T> withServer<T>(Future<T> Function() body, (int, Object) Function(http.Request) handler) =>
    http.runWithClient(body, () => MockClient((req) async {
          final (code, json) = handler(req);
          return http.Response(jsonEncode(json), code, request: req, headers: {'content-type': 'application/json'});
        }));

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() {
    SharedPreferences.setMockInitialValues({'auth_token': 'tok'}); // token lama: dimigrasikan ke penyimpanan aman
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('cancelBooking sukses: memanggil endpoint cancel lalu memuat ulang daftar booking', () async {
    final calls = <String>[];
    final prov = BookingProvider();
    final ok = await withServer(() => prov.cancelBooking(7), (req) {
      calls.add('${req.method} ${req.url.path}');
      return req.url.path.endsWith('/cancel')
          ? (200, {'status': true, 'message': 'Booking berhasil dibatalkan.'})
          : (200, {'status': true, 'data': []});
    });

    expect(ok, isTrue);
    expect(calls, ['POST /api/bookings/7/cancel', 'GET /api/bookings']);
    expect(prov.bookings, isEmpty);
  });

  test('cancelBooking ditolak server (DP sudah dibayar): false + pesan dari server', () async {
    final prov = BookingProvider();
    final ok = await withServer(
      () => prov.cancelBooking(7),
      (_) => (422, {'status': false, 'message': 'Booking tidak dapat dibatalkan dari aplikasi. Hubungi admin.'}),
    );

    expect(ok, isFalse);
    expect(prov.error, 'Booking tidak dapat dibatalkan dari aplikasi. Hubungi admin.');
  });

  test('cancelBooking: respons rusak tidak melempar', () async {
    final prov = BookingProvider();
    final ok = await http.runWithClient(
      () => prov.cancelBooking(7),
      () => MockClient((_) async => http.Response('<html>500</html>', 500)),
    );

    expect(ok, isFalse);
  });
}
