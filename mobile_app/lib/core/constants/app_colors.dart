import 'package:flutter/material.dart';

// ─────────────────────────────────────────────────────────────
//  App Color Palette — Harkat Rental
//  Primary: Deep Blue-Indigo  |  Accent: Amber/Gold
// ─────────────────────────────────────────────────────────────

class AppColors {
  // Primary palette
  static const Color primary        = Color(0xFF1A237E); // Deep Indigo
  static const Color primaryLight   = Color(0xFF3949AB);
  static const Color primaryDark    = Color(0xFF0D1452);
  static const Color primarySurface = Color(0xFFE8EAF6);

  // Accent
  static const Color accent      = Color(0xFFF59E0B); // Amber
  static const Color accentLight = Color(0xFFFCD34D);
  static const Color accentDark  = Color(0xFFD97706);

  // Semantic
  static const Color success = Color(0xFF10B981);
  static const Color warning = Color(0xFFF59E0B);
  static const Color error   = Color(0xFFEF4444);
  static const Color info    = Color(0xFF3B82F6);

  // Neutrals (Dark-mode friendly)
  static const Color background    = Color(0xFF0F172A); // Slate 900
  static const Color surface       = Color(0xFF1E293B); // Slate 800
  static const Color surfaceLight  = Color(0xFF334155); // Slate 700
  static const Color border        = Color(0xFF475569); // Slate 600
  static const Color textPrimary   = Color(0xFFF1F5F9); // Slate 100
  static const Color textSecondary = Color(0xFF94A3B8); // Slate 400
  static const Color textHint      = Color(0xFF64748B); // Slate 500

  // Status chips
  static const Color statusBooked  = Color(0xFFF59E0B);
  static const Color statusOngoing = Color(0xFF3B82F6);
  static const Color statusDone    = Color(0xFF10B981);
  static const Color statusCancel  = Color(0xFFEF4444);

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF1A237E), Color(0xFF3949AB)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFF0D1452), Color(0xFF1A237E), Color(0xFF283593)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient goldGradient = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFFCD34D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
