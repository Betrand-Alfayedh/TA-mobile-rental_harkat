import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';
import 'core/constants/app_colors.dart';
import 'providers/auth_provider.dart';
import 'providers/mobil_provider.dart';
import 'providers/booking_provider.dart';
import 'providers/sopir_provider.dart';
import 'providers/admin_provider.dart';
import 'providers/admin_master_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/splash_screen.dart';

/// Dipakai untuk berpindah layar dari luar widget (mis. saat sesi berakhir).
final navigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Indonesian date locale for Intl
  await initializeDateFormatting('id_ID', null);

  // Lock to portrait
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Status bar style
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor:            Colors.transparent,
    statusBarIconBrightness:   Brightness.dark,
    systemNavigationBarColor:  AppColors.bg,
    systemNavigationBarIconBrightness: Brightness.dark,
  ));

  runApp(const HarkatRentalApp());
}

class HarkatRentalApp extends StatelessWidget {
  const HarkatRentalApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProxyProvider<AuthProvider, MobilProvider>(
          create: (_) => MobilProvider(),
          update: (_, auth, mobil) {
            final p = mobil ?? MobilProvider();
            if (!auth.isAuthenticated) p.reset();
            return p;
          },
        ),
        ChangeNotifierProxyProvider<AuthProvider, BookingProvider>(
          create: (_) => BookingProvider(),
          update: (_, auth, booking) {
            final p = booking ?? BookingProvider();
            if (!auth.isAuthenticated) p.reset();
            return p;
          },
        ),
        // Data sopir dibuang saat logout / sesi berakhir agar tidak terbawa ke pengguna berikutnya.
        ChangeNotifierProxyProvider<AuthProvider, SopirProvider>(
          create: (_) => SopirProvider(),
          update: (_, auth, sopir) {
            final p = sopir ?? SopirProvider();
            if (!auth.isAuthenticated) p.reset();
            return p;
          },
        ),
        ChangeNotifierProxyProvider<AuthProvider, AdminProvider>(
          create: (_) => AdminProvider(),
          update: (_, auth, admin) {
            final p = admin ?? AdminProvider();
            if (!auth.isAuthenticated) p.reset();
            return p;
          },
        ),
        ChangeNotifierProxyProvider<AuthProvider, AdminMasterProvider>(
          create: (_) => AdminMasterProvider(),
          update: (_, auth, master) {
            final p = master ?? AdminMasterProvider();
            if (!auth.isAuthenticated) p.reset();
            return p;
          },
        ),
      ],
      child: MaterialApp(
        navigatorKey:     navigatorKey,
        builder:          (context, child) => _SessionGuard(child: child ?? const SizedBox.shrink()),
        title:            'Harkat Rental',
        debugShowCheckedModeBanner: false,
        themeMode:        ThemeMode.light,
        theme:            _buildLightTheme(),
        home:             const SplashScreen(),
      ),
    );
  }

  ThemeData _buildLightTheme() {
    final base = ThemeData.light(useMaterial3: true);
    final text = GoogleFonts.plusJakartaSansTextTheme(base.textTheme).apply(
      bodyColor:    AppColors.ink,
      displayColor: AppColors.ink,
    );
    OutlineInputBorder border(Color c, [double w = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: c == Colors.transparent ? BorderSide.none : BorderSide(color: c, width: w),
        );
    return base.copyWith(
      scaffoldBackgroundColor: AppColors.bg,
      colorScheme: const ColorScheme.light(
        primary:          AppColors.primary,
        primaryContainer: AppColors.primarySoft,
        secondary:        AppColors.primary,
        surface:          AppColors.surface,
        error:            AppColors.error,
        onPrimary:        AppColors.onPrimary,
        onSecondary:      AppColors.onPrimary,
        onSurface:        AppColors.ink,
      ),
      textTheme: text,
      splashFactory: InkRipple.splashFactory,
      appBarTheme: AppBarTheme(
        backgroundColor:  AppColors.bg,
        foregroundColor:  AppColors.ink,
        elevation:        0,
        scrolledUnderElevation: 0,
        centerTitle:      false,
        titleTextStyle:   GoogleFonts.plusJakartaSans(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.ink),
        systemOverlayStyle: const SystemUiOverlayStyle(
          statusBarColor:          Colors.transparent,
          statusBarIconBrightness: Brightness.dark,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor:  AppColors.surface,
        surfaceTintColor: Colors.transparent,
        elevation:        0,
        indicatorColor:   AppColors.primary,
        labelTextStyle:   WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return GoogleFonts.plusJakartaSans(fontSize: 11, fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? AppColors.primary : AppColors.hint);
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(color: selected ? Colors.white : AppColors.hint, size: 24);
        }),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: AppColors.onPrimary,
          elevation:       0,
          minimumSize:     const Size(64, 54),
          shape:           const StadiumBorder(),
          textStyle:       GoogleFonts.plusJakartaSans(fontSize: 14.5, fontWeight: FontWeight.w700),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: AppColors.onPrimary,
          minimumSize:     const Size(64, 54),
          shape:           const StadiumBorder(),
          textStyle:       GoogleFonts.plusJakartaSans(fontSize: 14.5, fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.ink,
          backgroundColor: AppColors.surface,
          side:            const BorderSide(color: AppColors.line),
          minimumSize:     const Size(64, 54),
          shape:           const StadiumBorder(),
          textStyle:       GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle:       GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700),
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 4,
        extendedTextStyle: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w700),
        shape: const StadiumBorder(),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.surfaceAlt,
        selectedColor:   AppColors.primarySoft,
        checkmarkColor:  AppColors.primary,
        side:            BorderSide.none,
        shape:           const StadiumBorder(),
        labelStyle:      GoogleFonts.plusJakartaSans(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.ink),
        secondaryLabelStyle: GoogleFonts.plusJakartaSans(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppColors.primary),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled:           true,
        fillColor:        AppColors.surface,
        contentPadding:   const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
        labelStyle:       GoogleFonts.plusJakartaSans(color: AppColors.muted, fontSize: 14),
        hintStyle:        GoogleFonts.plusJakartaSans(color: AppColors.hint, fontSize: 14),
        errorStyle:       GoogleFonts.plusJakartaSans(color: AppColors.error, fontSize: 12),
        border:           border(Colors.transparent),
        enabledBorder:    border(AppColors.line),
        focusedBorder:    border(AppColors.primary, 1.5),
        errorBorder:      border(AppColors.error),
        focusedErrorBorder: border(AppColors.error, 1.5),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: const WidgetStatePropertyAll(Colors.white),
        trackColor: WidgetStateProperty.resolveWith(
            (s) => s.contains(WidgetState.selected) ? AppColors.primary : AppColors.hint),
        trackOutlineColor: const WidgetStatePropertyAll(Colors.transparent),
      ),
      dividerTheme: const DividerThemeData(color: AppColors.line, thickness: 1, space: 1),
      progressIndicatorTheme: const ProgressIndicatorThemeData(color: AppColors.primary),
      snackBarTheme: SnackBarThemeData(
        behavior:         SnackBarBehavior.floating,
        backgroundColor:  AppColors.ink,
        shape:            RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        contentTextStyle: GoogleFonts.plusJakartaSans(fontSize: 13, color: Colors.white),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor:  AppColors.surface,
        surfaceTintColor: Colors.transparent,
        shape:            RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        titleTextStyle:   GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.ink),
        contentTextStyle: GoogleFonts.plusJakartaSans(fontSize: 13.5, color: AppColors.muted),
      ),
    );
  }
}

/// Saat server mencabut token (mis. login di HP lain), kembalikan user ke layar login dengan pesan jelas.
class _SessionGuard extends StatelessWidget {
  final Widget child;
  const _SessionGuard({required this.child});

  @override
  Widget build(BuildContext context) {
    final expired = context.select<AuthProvider, bool>((a) => a.sessionExpired);
    if (expired) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        final auth = context.read<AuthProvider>();
        if (!auth.sessionExpired) return;
        auth.ackSessionExpired();

        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (_) => false,
        );
        final ctx = navigatorKey.currentContext;
        if (ctx != null) {
          ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(
            content: const Text('Sesi Anda telah berakhir. Silakan login kembali.'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ));
        }
      });
    }
    return child;
  }
}
