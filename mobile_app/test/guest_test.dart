import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/providers/auth_provider.dart';
import 'package:harkat_rental/providers/mobil_provider.dart';
import 'package:harkat_rental/screens/home/home_screen.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support.dart';

/// Tamu (belum login): katalog dapat dilihat tanpa token, tab lain meminta login.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({});
  });

  Future<Server> bukaBeranda(WidgetTester tester) async {
    final s = Server({
      'GET /api/mobils': ok([mobilJson(1, plat: 'AB 1 CD')], meta: {'current_page': 1, 'last_page': 1, 'per_page': 10, 'total': 1}),
      'GET /api/tipe-mobils': ok([]),
    });
    await pakaiServer(s, () async {
      await tester.pumpWidget(MultiProvider(
        providers: [ChangeNotifierProvider(create: (_) => AuthProvider()), ChangeNotifierProvider(create: (_) => MobilProvider())],
        child: const MaterialApp(home: HomeScreen()),
      ));
      await tester.pump(const Duration(milliseconds: 300));
      await tester.pump(const Duration(milliseconds: 300));
    });
    return s;
  }

  testWidgets('tamu: katalog dimuat tanpa header Authorization dan tidak memanggil API yang butuh login', (tester) async {
    final s = await bukaBeranda(tester);
    expect(s.permintaan('GET /api/mobils'), isNotEmpty);
    for (final r in s.log) {
      expect(r.headers.containsKey('Authorization'), isFalse, reason: '${r.method} ${r.url.path}');
    }
    expect(s.kunci.where((k) => k.contains('/bookings') || k.contains('/admin') || k.contains('/sopir')), isEmpty);
  });

  testWidgets('tamu: tab Booking, Riwayat, dan Profil menampilkan ajakan masuk', (tester) async {
    await bukaBeranda(tester);
    for (final (tab, judul) in [('Booking', 'Booking Anda'), ('Riwayat', 'Riwayat sewa'), ('Profil', 'Profil')]) {
      await tester.tap(find.text(tab).last);
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.text('$judul perlu login'), findsOneWidget, reason: tab);
      expect(find.widgetWithText(ElevatedButton, 'Masuk'), findsOneWidget, reason: tab);
    }
  });
}
