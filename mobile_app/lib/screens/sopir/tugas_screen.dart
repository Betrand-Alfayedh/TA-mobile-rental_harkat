import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/sopir_model.dart';
import '../../providers/sopir_provider.dart';
import 'laporan_kondisi_screen.dart';
import 'widgets/unit_card.dart';

/// Tugas aktif sopir: info perjalanan dan tombol laporan kondisi kendaraan.
class TugasScreen extends StatelessWidget {
  const TugasScreen({super.key});

  Future<void> _lapor(BuildContext context, SopirUnitModel u, int jenis) async {
    final terkirim = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
          builder: (_) => LaporanKondisiScreen(unit: u, jenis: jenis)),
    );
    if (terkirim == true && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Laporan kondisi tersimpan.'),
        backgroundColor: AppColors.success,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SopirProvider>();
    final s = prov.tugas;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const TabHeader('Tugas Aktif'),
          Expanded(
              child: DaftarMuat<SopirUnitModel>(
            loading: s.loading,
            loaded: s.loaded,
            error: s.error,
            items: s.value,
            onRefresh: prov.muatTugas,
            empty: const EmptyState(
              icon: Icons.directions_car_outlined,
              title: 'Belum ada tugas aktif',
              subtitle: 'Tugas yang Anda terima akan tampil di sini.',
            ),
            itemBuilder: (ctx, u) => UnitCard(
              unit: u,
              aksi: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _ringkasanLaporan('Sebelum sewa', u.laporanSebelum),
                  _ringkasanLaporan('Sesudah sewa', u.laporanSesudah),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: u.bisaLaporSebelum
                              ? () => _lapor(ctx, u, 1)
                              : null,
                          child: Text(
                              u.laporanSebelum == null
                                  ? 'Lapor sebelum'
                                  : 'Ubah laporan sebelum',
                              textAlign: TextAlign.center),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: OutlinedButton(
                          // Laporan sesudah baru dapat dibuat setelah unit dijemput.
                          onPressed: u.bisaLaporSesudah
                              ? () => _lapor(ctx, u, 2)
                              : null,
                          child: Text(
                              u.laporanSesudah == null
                                  ? 'Lapor sesudah'
                                  : 'Ubah laporan sesudah',
                              textAlign: TextAlign.center),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          )),
        ]),
      ),
    );
  }

  Widget _ringkasanLaporan(String label, LaporanKondisiModel? l) {
    final warna = switch (l?.kondisi) {
      1 => AppColors.success,
      2 => AppColors.warning,
      3 => AppColors.error,
      _ => AppColors.hint
    };
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Icon(
              l == null
                  ? Icons.radio_button_unchecked
                  : Icons.check_circle_rounded,
              size: 16,
              color: warna),
          const SizedBox(width: 8),
          Text('$label: ',
              style: GoogleFonts.plusJakartaSans(
                  fontSize: 12, color: AppColors.muted)),
          Text(l?.kondisiLabel ?? 'belum dilaporkan',
              style: GoogleFonts.plusJakartaSans(
                  fontSize: 12, fontWeight: FontWeight.w600, color: warna)),
        ],
      ),
    );
  }
}
