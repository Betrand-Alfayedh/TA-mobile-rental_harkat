import 'dart:convert';
import 'dart:io';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/core/services/storage_service.dart';
import 'package:harkat_rental/providers/auth_provider.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _user = {
  'id': 5, 'name': 'Budi', 'email': 'budi@x.com', 'role': 4,
  'no_hp': '0812', 'alamat': 'Jl. A', 'asal_kota': 'Yogyakarta',
};

/// Jalankan [body] dengan server palsu. [handler] menerima request dan mengembalikan (status, body).
Future<T> withServer<T>(
  Future<T> Function() body,
  (int, Object) Function(http.Request) handler,
) =>
    http.runWithClient(body, () => MockClient((req) async {
          final (code, json) = handler(req);
          return http.Response(jsonEncode(json), code,
              request: req, headers: {'content-type': 'application/json'});
        }));

Future<void> seedSession() async {
  SharedPreferences.setMockInitialValues({});
  FlutterSecureStorage.setMockInitialValues({});
  await StorageService.saveToken('tok');
  await StorageService.saveUserJson(jsonEncode(_user));
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('server error saat boot: sesi TIDAK dihapus, pakai profil tersimpan', () async {
    await seedSession();
    final auth = AuthProvider();
    await withServer(auth.init, (_) => (500, {'message': 'boom'}));

    expect(auth.isAuthenticated, isTrue);
    expect(auth.user?.noHp, '0812'); // profil lengkap dari cache
    expect(await StorageService.getToken(), 'tok');
  });

  test('offline saat boot: sesi dipertahankan', () async {
    await seedSession();
    final auth = AuthProvider();
    await http.runWithClient(auth.init, () => MockClient((_) async => throw const SocketException('offline')));

    expect(auth.isAuthenticated, isTrue);
    expect(await StorageService.getToken(), 'tok');
  });

  test('401 saat boot: token dihapus dan user dianggap belum login', () async {
    await seedSession();
    final auth = AuthProvider();
    await withServer(auth.init, (_) => (401, {'message': 'Unauthenticated.'}));

    expect(auth.isAuthenticated, isFalse);
    expect(await StorageService.getToken(), isNull);
  });

  test('respons /me rusak: tidak melempar dan tidak macet loading', () async {
    await seedSession();
    final auth = AuthProvider();
    await withServer(auth.init, (_) => (200, {'status': true, 'data': 'bukan-map'}));

    expect(auth.status, isNot(AuthStatus.loading));
  });

  test('401 pada request ber-token saat login: logout otomatis + sessionExpired', () async {
    await seedSession();
    final auth = AuthProvider();
    // boot sukses
    await withServer(auth.init, (_) => (200, {'status': true, 'data': _user}));
    expect(auth.isAuthenticated, isTrue);

    // token dicabut (login di HP lain) → request berikutnya 401
    await withServer(auth.refreshMe, (_) => (401, {'message': 'Unauthenticated.'}));

    expect(auth.isAuthenticated, isFalse);
    expect(auth.sessionExpired, isTrue);
    expect(auth.user, isNull);
  });

  test('login gagal (422) tidak memicu sessionExpired dan menampilkan pesan validasi', () async {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({});
    final auth = AuthProvider();
    final ok = await withServer(
      () => auth.login(email: 'a@a.com', password: 'x'),
      (_) => (422, {'message': 'The given data was invalid.', 'errors': {'email': ['Email atau password salah.']}}),
    );

    expect(ok, isFalse);
    expect(auth.sessionExpired, isFalse);
    expect(auth.error, 'Email atau password salah.');
  });
}
