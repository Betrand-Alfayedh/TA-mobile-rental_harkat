import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../../core/widgets/state_views.dart';
import '../../../models/sopir_model.dart';

Color warnaStatusUnit(int status) => switch (status) {
      1 => AppColors.statusBooked,
      2 => AppColors.statusOngoing,
      3 => AppColors.statusDone,
      _ => AppColors.statusCancel,
    };

String labelStatusUnit(int status) => switch (status) {
      1 => 'Menunggu jemput',
      2 => 'Sedang berjalan',
      3 => 'Selesai',
      _ => 'Dibatalkan',
    };

/// Kartu satu unit sewa untuk sopir (tanpa harga atau pembayaran).
class UnitCard extends StatelessWidget {
  final SopirUnitModel unit;

  /// Tombol aksi di bawah kartu (mis. Terima/Tolak atau Laporan kondisi).
  final Widget? aksi;
  final bool tampilkanStatus;

  const UnitCard({super.key, required this.unit, this.aksi, this.tampilkanStatus = true});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return SoftCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(color: AppColors.primarySoft, borderRadius: BorderRadius.circular(14)),
                child: const Icon(Icons.directions_car_rounded, color: AppColors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(unit.namaLengkap.isEmpty ? 'Mobil' : unit.namaLengkap,
                        style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                    if (unit.platNomor != null)
                      Text(unit.platNomor!, style: t(fontSize: 12.5, color: AppColors.muted)),
                  ],
                ),
              ),
              if (tampilkanStatus) ...[
                const SizedBox(width: 8),
                StatusPill(label: labelStatusUnit(unit.status), color: warnaStatusUnit(unit.status)),
              ],
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: AppColors.bg, borderRadius: BorderRadius.circular(16)),
            child: Column(children: [
              _baris(Icons.event_available_rounded, 'Mulai', Formatters.toShortDateTime(unit.tanggalSewa)),
              _baris(Icons.event_rounded, 'Kembali', Formatters.toShortDateTime(unit.tanggalKembali)),
              if (unit.lamaSewa > 0) _baris(Icons.timelapse_rounded, 'Lama', Formatters.lamaSewa(unit.lamaSewa)),
              if (unit.asalKota != null && unit.asalKota!.isNotEmpty)
                _baris(Icons.location_on_outlined, 'Asal kota', unit.asalKota!),
            ]),
          ),
          if (unit.penyewaNama != null) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                const Icon(Icons.person_outline_rounded, size: 18, color: AppColors.muted),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(unit.penyewaNama!, style: t(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.ink)),
                ),
                if (unit.penyewaNoHp != null && unit.penyewaNoHp!.isNotEmpty)
                  TextButton.icon(
                    onPressed: () => launchUrl(Uri(scheme: 'tel', path: unit.penyewaNoHp)),
                    style: TextButton.styleFrom(
                      backgroundColor: AppColors.primarySoft,
                      shape: const StadiumBorder(),
                      minimumSize: const Size(48, 40),
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                    ),
                    icon: const Icon(Icons.call_rounded, size: 16),
                    label: Text(unit.penyewaNoHp!, style: t(fontSize: 12.5, fontWeight: FontWeight.w700)),
                  ),
              ],
            ),
          ],
          if (aksi != null) ...[const SizedBox(height: 14), aksi!],
        ],
      ),
    );
  }

  Widget _baris(IconData icon, String label, String nilai) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          children: [
            Icon(icon, size: 16, color: AppColors.muted),
            const SizedBox(width: 8),
            SizedBox(
              width: 72,
              child: Text(label, style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.muted)),
            ),
            Expanded(
              child: Text(nilai, style: GoogleFonts.plusJakartaSans(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.ink)),
            ),
          ],
        ),
      );
}
