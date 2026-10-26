import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../profile/profile_screen.dart';
import 'penyewa_screens.dart';
import 'rekap_screen.dart';
import 'sopir_admin_screens.dart';

/// Menu tambahan admin: sopir, penyewa, rekap, dan profil.
class LainnyaScreen extends StatelessWidget {
  const LainnyaScreen({super.key});

  @override
  Widget build(BuildContext context) {
    void buka(Widget layar) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => layar));
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    Widget menu(IconData icon, String judul, String sub, Widget layar) => Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Semantics(
            button: true,
            child: GestureDetector(
              onTap: () => buka(layar),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
                child: Row(children: [
                  Container(
                    width: 46, height: 46,
                    decoration: BoxDecoration(color: AppColors.primarySoft, borderRadius: BorderRadius.circular(16)),
                    child: Icon(icon, color: AppColors.primary, size: 22),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(judul, style: t(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink)),
                      const SizedBox(height: 2),
                      Text(sub, style: t(fontSize: 12.5, height: 1.35, color: AppColors.muted)),
                    ]),
                  ),
                  const Icon(Icons.chevron_right_rounded, color: AppColors.hint),
                ]),
              ),
            ),
          ),
        );

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(children: [
            const TabHeader('Lainnya'),
            Expanded(
              child: ListView(
                padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                children: [
                  menu(Icons.badge_rounded, 'Sopir', 'Kelola akun dan status sopir', const SopirAdminListScreen()),
                  menu(Icons.people_rounded, 'Penyewa', 'Data pelanggan', const PenyewaListScreen()),
                  menu(Icons.bar_chart_rounded, 'Rekap bulanan', 'Pesanan, pendapatan, dan pemakaian armada', const RekapScreen()),
                  menu(Icons.person_rounded, 'Profil dan keluar', 'Ubah data akun atau keluar', const ProfileScreen()),
                ],
              ),
            ),
          ]),
        ),
      ),
    );
  }
}
