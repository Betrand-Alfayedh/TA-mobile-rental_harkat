import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:harkat_rental/providers/auth_provider.dart';
import 'package:harkat_rental/providers/mobil_provider.dart';
import 'package:harkat_rental/providers/booking_provider.dart';
import 'package:harkat_rental/screens/auth/login_screen.dart';

void main() {
  testWidgets('Login screen renders correctly', (WidgetTester tester) async {
    // Wrap with MultiProvider — same as main.dart
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => AuthProvider()),
          ChangeNotifierProvider(create: (_) => MobilProvider()),
          ChangeNotifierProvider(create: (_) => BookingProvider()),
        ],
        child: const MaterialApp(
          home: LoginScreen(),
        ),
      ),
    );

    // Pump a frame so widgets settle
    await tester.pump(const Duration(seconds: 1));

    // Verify key UI elements are present
    expect(find.text('Masuk ke'),               findsOneWidget);
    expect(find.text('Harkat Rental'),          findsOneWidget);
    expect(find.text('Masuk'),                  findsOneWidget);
    expect(find.text('Lanjutkan dengan Google'), findsOneWidget);

    // Validasi klien: isian kosong ditolak tanpa permintaan ke server
    await tester.tap(find.text('Masuk'));
    await tester.pump();
    expect(find.text('Email tidak valid'), findsOneWidget);
    expect(find.text('Password minimal 6 karakter'), findsOneWidget);
  });
}
