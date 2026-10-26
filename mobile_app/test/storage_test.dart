import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/core/services/storage_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('token disimpan di penyimpanan aman, bukan di SharedPreferences', () async {
    await StorageService.saveToken('rahasia');

    expect(await StorageService.getToken(), 'rahasia');
    final prefs = await SharedPreferences.getInstance();
    expect(prefs.getString('auth_token'), isNull);
    expect(await const FlutterSecureStorage().read(key: 'auth_token'), 'rahasia');
  });

  test('token lama di SharedPreferences dimigrasikan lalu dihapus dari sana', () async {
    SharedPreferences.setMockInitialValues({'auth_token': 'lama'});

    expect(await StorageService.getToken(), 'lama');

    final prefs = await SharedPreferences.getInstance();
    expect(prefs.getString('auth_token'), isNull, reason: 'salinan tidak aman harus hilang');
    expect(await const FlutterSecureStorage().read(key: 'auth_token'), 'lama');
  });

  test('tanpa token: null', () async {
    expect(await StorageService.getToken(), isNull);
  });

  test('clearAll menghapus token dan data pengguna', () async {
    await StorageService.saveToken('tok');
    await StorageService.saveUserJson('{"id":1}');

    await StorageService.clearAll();

    expect(await StorageService.getToken(), isNull);
    expect(await StorageService.getUserJson(), isNull);
  });

  test('menyimpan token baru menimpa token lama', () async {
    await StorageService.saveToken('a');
    await StorageService.saveToken('b');
    expect(await StorageService.getToken(), 'b');
  });
}
