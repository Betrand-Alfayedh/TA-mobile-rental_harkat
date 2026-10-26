import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../screens/profile/profile_screen.dart';
import '../constants/app_colors.dart';

/// Booking butuh no. HP, alamat, dan asal kota. Jika belum lengkap, arahkan user ke Profil
/// (mode edit) daripada membiarkan ia mengisi form lalu ditolak server.
/// Mengembalikan true bila profil sudah lengkap dan booking boleh dilanjutkan.
Future<bool> ensureProfileComplete(BuildContext context) async {
  final user = context.read<AuthProvider>().user;
  if (user == null || user.isProfileComplete) return true;

  final go = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Lengkapi Profil'),
      content: Text(
        'Isi nomor HP, alamat, dan asal kota terlebih dahulu sebelum melakukan booking.',
        style: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Nanti')),
        ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Lengkapi')),
      ],
    ),
  );

  if (go == true && context.mounted) {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const ProfileScreen(startEditing: true)),
    );
  }
  return false;
}
