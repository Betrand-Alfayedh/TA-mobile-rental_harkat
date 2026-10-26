import 'package:flutter/material.dart';

// ─────────────────────────────────────────────────────────────
//  Palet Harkat Rental — monokrom bersih: putih, abu, CTA hitam.
//  Hijau/merah/kuning hanya untuk status (semantik). Acuan: docs/Luxury Car Rental…jpg.
// ─────────────────────────────────────────────────────────────

class AppColors {
  // Inti
  static const Color bg             = Color(0xFFF7F7F8);
  static const Color surface        = Color(0xFFFFFFFF);
  static const Color surfaceAlt     = Color(0xFFF1F1F2);
  static const Color primary        = Color(0xFF111111);
  static const Color primaryPressed = Color(0xFF000000);
  static const Color primarySoft    = Color(0xFFEDEDEE);
  static const Color ink            = Color(0xFF111111);
  static const Color muted          = Color(0xFF6B7280);
  static const Color hint           = Color(0xFF9CA3AF);
  static const Color line           = Color(0xFFE9E9EB);
  static const Color onPrimary      = Color(0xFFFFFFFF);

  // Semantik
  static const Color success = Color(0xFF2E7D4F);
  static const Color warning = Color(0xFFC98A12);
  static const Color error   = Color(0xFFD64545);
  static const Color info    = Color(0xFF2F6FB5);

  // Latar lembut untuk badge status
  static const Color successSoft = Color(0xFFE3F1E8);
  static const Color warningSoft = Color(0xFFFBF0D9);
  static const Color errorSoft   = Color(0xFFFBE5E5);
  static const Color infoSoft    = Color(0xFFE3EEF9);

  // Status pesanan
  static const Color statusBooked  = warning;
  static const Color statusOngoing = info;
  static const Color statusDone    = success;
  static const Color statusCancel  = error;

  // Bayangan lembut kartu dan elemen mengambang
  static List<BoxShadow> get softShadow => [
        BoxShadow(color: ink.withOpacity(0.05), blurRadius: 14, offset: const Offset(0, 4)),
      ];
  static List<BoxShadow> get floatShadow => [
        BoxShadow(color: ink.withOpacity(0.08), blurRadius: 24, offset: const Offset(0, 8)),
      ];

  // ── Nama lama (dipakai layar yang belum didesain ulang) ────
  // Dipetakan ke palet baru agar tetap terkompilasi; dihapus bertahap
  // seiring layar dikerjakan ulang.
  static const Color primaryLight   = Color(0xFF3A3A3A);
  static const Color primaryDark    = primaryPressed;
  static const Color primarySurface = primarySoft;
  static const Color accent         = primary;
  static const Color accentLight    = primarySoft;
  static const Color accentDark     = primaryPressed;
  static const Color background     = bg;
  static const Color surfaceLight   = surfaceAlt;
  static const Color border         = line;
  static const Color textPrimary    = ink;
  static const Color textSecondary  = muted;
  static const Color textHint       = hint;

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF111111), Color(0xFF2B2B2B)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFF000000), Color(0xFF111111), Color(0xFF2B2B2B)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );
  static const LinearGradient goldGradient = primaryGradient;
}
