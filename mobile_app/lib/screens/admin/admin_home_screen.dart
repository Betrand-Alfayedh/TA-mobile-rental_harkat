import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/admin_provider.dart';
import '../../providers/auth_provider.dart';

/// Beranda admin: yang perlu ditindak hari ini, ringkasan pesanan, armada, dan sopir.
class AdminHomeScreen extends StatelessWidget {
  /// Navigasi cepat ke tab lain: 1 Pesanan, 2 Pembayaran, 3 Armada.
  final void Function(int tab) pindahTab;
  const AdminHomeScreen({super.key, required this.pindahTab});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final nama = context.select<AuthProvider, String>((a) => a.user?.name ?? '');
    final d = prov.dashboard;
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: Builder(builder: (_) {
          if (!d.loaded && d.loading) return const Center(child: CircularProgressIndicator());
          if (d.value == null) {
            return ErrorState(message: d.error ?? 'Data belum tersedia.', onRetry: prov.muatDashboard);
          }
          final x = d.value!;

          return RefreshIndicator(
            onRefresh: prov.muatDashboard,
            child: ContentWidth(
              max: 720,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(gutter, 20, gutter, 24),
                children: [
                  Text('Halo, $nama',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: t(fontSize: 28, height: 1.15, fontWeight: FontWeight.w800, color: AppColors.ink)),
                  const SizedBox(height: 6),
                  Text(x.totalPerluTindakan == 0 ? 'Tidak ada yang perlu ditindak saat ini.' : '${x.totalPerluTindakan} hal perlu ditindak.',
                      style: t(fontSize: 13.5, color: AppColors.muted)),
                  const SizedBox(height: 22),
                  _judul('Perlu ditindak'),
                  _tindakan(Icons.fact_check_rounded, 'Pembayaran menunggu verifikasi', x.menungguVerifikasi, AppColors.warning, () => pindahTab(2)),
                  _tindakan(Icons.person_add_alt_1_rounded, 'Unit perlu penugasan sopir', x.perluPenugasan, AppColors.info, () => pindahTab(1)),
                  _tindakan(Icons.alarm_rounded, 'Terlambat dikembalikan', x.terlambat, AppColors.error, () => pindahTab(1)),
                  _tindakan(Icons.key_rounded, 'Jemput hari ini', x.jemputHariIni, AppColors.success, () => pindahTab(1)),
                  _tindakan(Icons.assignment_return_rounded, 'Kembali hari ini', x.kembaliHariIni, AppColors.info, () => pindahTab(1)),
                  const SizedBox(height: 22),
                  _judul('Pesanan'),
                  Row(children: [
                    _angka('Masuk hari ini', x.masukHariIni),
                    _angka('Booked', x.booked, warna: AppColors.warning),
                    _angka('Berjalan', x.berjalan, warna: AppColors.info),
                    _angka('Selesai', x.selesai, warna: AppColors.success),
                  ]),
                  const SizedBox(height: 22),
                  _judul('Armada (${x.armadaTotal} unit)'),
                  Row(children: [
                    _angka('Tersedia', x.armadaTersedia, warna: AppColors.success),
                    _angka('Disewa', x.armadaDisewa, warna: AppColors.info),
                    _angka('Maint.', x.armadaMaintenance, warna: AppColors.warning),
                    _angka('Rusak', x.armadaRusak, warna: AppColors.error),
                  ]),
                  const SizedBox(height: 22),
                  _judul('Sopir'),
                  Row(children: [
                    _angka('Tersedia', x.sopirTersedia, warna: AppColors.success),
                    _angka('Bertugas', x.sopirBertugas, warna: AppColors.info),
                    _angka('Libur', x.sopirLibur),
                  ]),
                ],
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _judul(String t) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Text(t, style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
      );

  Widget _tindakan(IconData icon, String label, int jumlah, Color warna, VoidCallback onTap) {
    final aktif = jumlah > 0;
    final t = GoogleFonts.plusJakartaSans;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(20), boxShadow: AppColors.softShadow),
          child: Row(children: [
            Container(
              width: 42, height: 42,
              decoration: BoxDecoration(color: (aktif ? warna : AppColors.hint).withValues(alpha: 0.14), shape: BoxShape.circle),
              child: Icon(icon, size: 20, color: aktif ? warna : AppColors.hint),
            ),
            const SizedBox(width: 12),
            Expanded(child: Text(label, style: t(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.ink))),
            Text('$jumlah', style: t(fontSize: 19, fontWeight: FontWeight.w800, color: aktif ? warna : AppColors.hint)),
            const SizedBox(width: 4),
            const Icon(Icons.chevron_right_rounded, size: 20, color: AppColors.hint),
          ]),
        ),
      ),
    );
  }

  Widget _angka(String label, int nilai, {Color? warna}) => Expanded(
        child: Container(
          margin: const EdgeInsets.symmetric(horizontal: 4),
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 4),
          decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(20), boxShadow: AppColors.softShadow),
          child: Column(children: [
            Text('$nilai', style: GoogleFonts.plusJakartaSans(fontSize: 21, fontWeight: FontWeight.w800, color: warna ?? AppColors.ink)),
            const SizedBox(height: 3),
            Text(label, textAlign: TextAlign.center, style: GoogleFonts.plusJakartaSans(fontSize: 10.5, color: AppColors.muted)),
          ]),
        ),
      );
}
