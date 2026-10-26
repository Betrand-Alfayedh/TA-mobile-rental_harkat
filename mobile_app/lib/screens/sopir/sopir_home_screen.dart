import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/auth_provider.dart';
import '../../providers/sopir_provider.dart';
import 'widgets/unit_card.dart';

/// Beranda sopir: status ketersediaan, ringkasan, dan tugas berikutnya.
class SopirHomeScreen extends StatelessWidget {
  /// Dipanggil saat sopir menekan ringkasan tawaran (shell berpindah ke tab Tawaran).
  final VoidCallback onLihatTawaran;
  const SopirHomeScreen({super.key, required this.onLihatTawaran});

  Future<void> _ubahStatus(BuildContext context, bool tersedia) async {
    final galat = await context.read<SopirProvider>().ubahStatus(tersedia);
    if (galat != null && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(galat), backgroundColor: AppColors.error));
    }
  }

  Future<void> _segarkan(SopirProvider p) => Future.wait([p.muatDashboard(), p.muatTugas()]);

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SopirProvider>();
    final nama = context.select<AuthProvider, String>((a) => a.user?.name ?? '');
    final d = prov.dashboard;
    final berikutnya = prov.tugas.value.isEmpty ? null : prov.tugas.value.first;
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: Builder(builder: (_) {
          if (!d.loaded && d.loading) return const Center(child: CircularProgressIndicator());
          if (d.value == null) {
            return ErrorState(message: d.error ?? 'Data belum tersedia.', onRetry: () => _segarkan(prov));
          }
          final data = d.value!;
          final warnaStatus = data.bertugas ? AppColors.info : data.tersedia ? AppColors.success : AppColors.hint;

          return RefreshIndicator(
            onRefresh: () => _segarkan(prov),
            child: ContentWidth(
              max: 720,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(gutter, 20, gutter, 24),
                children: [
                  Text('Selamat bertugas', style: t(fontSize: 13, fontWeight: FontWeight.w500, color: AppColors.muted)),
                  const SizedBox(height: 2),
                  Text(nama.isEmpty ? data.nama : nama,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: t(fontSize: 28, height: 1.15, fontWeight: FontWeight.w800, color: AppColors.ink)),
                  const SizedBox(height: 20),

                  // ── Status ketersediaan ─────────────────────
                  SoftCard(
                    padding: const EdgeInsets.all(18),
                    child: Row(
                      children: [
                        Container(
                          width: 44, height: 44,
                          decoration: BoxDecoration(color: warnaStatus.withValues(alpha: 0.14), shape: BoxShape.circle),
                          child: Icon(Icons.circle, size: 14, color: warnaStatus),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(data.statusText, style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                              const SizedBox(height: 2),
                              Text(
                                data.bertugas
                                    ? 'Status dikunci selama Anda bertugas.'
                                    : data.tersedia ? 'Anda dapat menerima tawaran tugas.' : 'Anda tidak akan menerima tawaran.',
                                style: t(fontSize: 12, height: 1.4, color: AppColors.muted),
                              ),
                            ],
                          ),
                        ),
                        Switch(
                          value: data.tersedia,
                          onChanged: (data.bertugas || prov.submitting) ? null : (v) => _ubahStatus(context, v),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // ── Ringkasan ───────────────────────────────
                  Row(
                    children: [
                      _stat('Tawaran baru', data.tawaranBaru, Icons.notifications_active_rounded, AppColors.warning, onTap: onLihatTawaran),
                      const SizedBox(width: 12),
                      _stat('Tugas aktif', data.tugasAktif, Icons.directions_car_rounded, AppColors.info),
                      const SizedBox(width: 12),
                      _stat('Selesai', data.tugasSelesai, Icons.check_circle_rounded, AppColors.success),
                    ],
                  ),
                  const SizedBox(height: 26),

                  // ── Tugas berikutnya ────────────────────────
                  Text('Tugas berikutnya', style: t(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.ink)),
                  const SizedBox(height: 12),
                  if (berikutnya == null)
                    SoftCard(
                      padding: const EdgeInsets.all(20),
                      child: Text('Belum ada tugas aktif.', style: t(fontSize: 13.5, color: AppColors.muted)),
                    )
                  else
                    UnitCard(unit: berikutnya),
                ],
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _stat(String label, int nilai, IconData icon, Color warna, {VoidCallback? onTap}) {
    final t = GoogleFonts.plusJakartaSans;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
          decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
          child: Column(
            children: [
              Container(
                width: 38, height: 38,
                decoration: BoxDecoration(color: warna.withValues(alpha: 0.14), shape: BoxShape.circle),
                child: Icon(icon, color: warna, size: 20),
              ),
              const SizedBox(height: 10),
              Text('$nilai', style: t(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.ink)),
              const SizedBox(height: 2),
              Text(label, textAlign: TextAlign.center, style: t(fontSize: 11, color: AppColors.muted)),
            ],
          ),
        ),
      ),
    );
  }
}
