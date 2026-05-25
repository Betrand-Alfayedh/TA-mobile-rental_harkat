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
    await tester.pump();

    // Verify key UI elements are present
    expect(find.text('Customer Login'), findsOneWidget);
    expect(find.text('Login'),          findsOneWidget);
    expect(find.text('Login via Google'), findsOneWidget);
  });
}
