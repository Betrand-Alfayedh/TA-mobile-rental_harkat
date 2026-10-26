import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../models/mobil_model.dart';
import '../../providers/mobil_provider.dart';
import '../../screens/mobil/mobil_detail_screen.dart';
import '../constants/app_colors.dart';
import '../utils/formatters.dart';
import 'app_widgets.dart';

/// "Rp350.000" (tanpa spasi) sesuai design.md.
String rp(int v) => Formatters.toRupiah(v).replaceFirst('Rp ', 'Rp');

/// Jumlah kolom grid mobil menurut lebar area konten (design.md bagian 7).
int kolomMobil(double lebar) => lebar < 600 ? 2 : (lebar < 840 ? 3 : 4);

/// Tinggi sel grid: foto 4:3 + padding + blok teks, ikut skala teks sistem.
double tinggiSelMobil(BuildContext context, double lebarSel) =>
    (lebarSel - 24) * 0.75 + 24 + 10 + MediaQuery.textScalerOf(context).scale(78);

class FotoKosong extends StatelessWidget {
  const FotoKosong({super.key});
  @override
  Widget build(BuildContext context) => const ColoredBox(
        color: AppColors.surfaceAlt,
        child: Center(child: Icon(Icons.directions_car_outlined, color: AppColors.hint, size: 36)),
      );
}

/// Kartu mobil putih: foto, nama, tipe · tahun, harga per hari.
class MobilCard extends StatelessWidget {
  final MobilModel mobil;
  const MobilCard({super.key, required this.mobil});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    final nama = mobil.masterMobil?.nama ?? mobil.merk;
    final sub = [if (mobil.tipeNama != null) mobil.tipeNama!, if (mobil.tahun > 0) '${mobil.tahun}'].join(' · ');

    return Semantics(
      button: true,
      label: '$nama, ${rp(mobil.hargaSewa)} per hari${mobil.isAvailable ? '' : ', tidak tersedia'}',
      child: GestureDetector(
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MobilDetailScreen(mobilId: mobil.id))),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.line)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: AspectRatio(
                  aspectRatio: 4 / 3,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      mobil.primaryImage.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: mobil.primaryImage,
                              fit: BoxFit.cover,
                              placeholder: (_, __) => const FotoKosong(),
                              errorWidget: (_, __, ___) => const FotoKosong(),
                            )
                          : const FotoKosong(),
                      if (!mobil.isAvailable)
                        Container(
                          color: AppColors.ink.withOpacity(0.55),
                          alignment: Alignment.center,
                          child: Text('Tidak Tersedia',
                              style: t(fontSize: 11.5, fontWeight: FontWeight.w700, color: Colors.white)),
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Text(nama, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: t(fontSize: 13.5, fontWeight: FontWeight.w700, color: AppColors.ink)),
              const SizedBox(height: 2),
              Text(sub.isEmpty ? mobil.merk : sub, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: t(fontSize: 11.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
              const SizedBox(height: 8),
              Text.rich(
                TextSpan(children: [
                  TextSpan(text: rp(mobil.hargaSewa), style: t(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.ink)),
                  TextSpan(text: ' / hari', style: t(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.muted)),
                ]),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Kartu mobil horizontal (gaya daftar): foto kiri, nama, tipe, chip tahun/plat, harga, tombol panah.
class MobilCardH extends StatelessWidget {
  final MobilModel mobil;
  const MobilCardH({super.key, required this.mobil});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    final nama = mobil.masterMobil?.nama ?? mobil.merk;

    Widget chip(IconData ic, String txt) => Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(ic, size: 13, color: AppColors.muted),
          const SizedBox(width: 4),
          Text(txt, style: t(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.muted)),
        ]);

    return Semantics(
      button: true,
      label: '$nama, ${rp(mobil.hargaSewa)} per hari${mobil.isAvailable ? '' : ', tidak tersedia'}',
      child: GestureDetector(
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MobilDetailScreen(mobilId: mobil.id))),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.line),
          ),
          child: Row(children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: SizedBox(
                width: 108, height: 82,
                child: Stack(fit: StackFit.expand, children: [
                  mobil.primaryImage.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: mobil.primaryImage,
                          fit: BoxFit.cover,
                          memCacheWidth: 360,
                          placeholder: (_, __) => const FotoKosong(),
                          errorWidget: (_, __, ___) => const FotoKosong(),
                        )
                      : const FotoKosong(),
                  if (!mobil.isAvailable)
                    Container(
                      color: AppColors.ink.withOpacity(0.55),
                      alignment: Alignment.center,
                      child: Text('Tidak Tersedia', textAlign: TextAlign.center,
                          style: t(fontSize: 10.5, fontWeight: FontWeight.w700, color: Colors.white)),
                    ),
                ]),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(nama, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: t(fontSize: 14.5, fontWeight: FontWeight.w700, color: AppColors.ink)),
                const SizedBox(height: 2),
                Text(mobil.tipeNama ?? mobil.merk, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: t(fontSize: 11.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
                const SizedBox(height: 6),
                Wrap(spacing: 10, runSpacing: 2, children: [
                  if (mobil.tahun > 0) chip(Icons.calendar_today_outlined, '${mobil.tahun}'),
                  if (mobil.platNomor.isNotEmpty) chip(Icons.pin_outlined, mobil.platNomor),
                ]),
                const SizedBox(height: 6),
                Text.rich(
                  TextSpan(children: [
                    TextSpan(text: rp(mobil.hargaSewa), style: t(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.ink)),
                    TextSpan(text: ' /hari', style: t(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.muted)),
                  ]),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ]),
            ),
            const SizedBox(width: 8),
            Container(
              width: 34, height: 34,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.primary),
              child: const Icon(Icons.arrow_forward_rounded, size: 17, color: Colors.white),
            ),
          ]),
        ),
      ),
    );
  }
}

/// Chip tipe mobil dari data API ("Semua" + tiap tipe). Memilih ulang chip aktif = kembali ke Semua.
class TipeChips extends StatelessWidget {
  final String aktif;
  final ValueChanged<String> onPilih;
  const TipeChips({super.key, required this.aktif, required this.onPilih});

  @override
  Widget build(BuildContext context) {
    final tipes = context.select<MobilProvider, List<TipeMobilModel>>((p) => p.tipes);
    if (tipes.isEmpty) return const SizedBox.shrink();

    Widget chip(String label, String nilai) {
      final on = aktif == nilai;
      return Semantics(
        button: true,
        selected: on,
        child: GestureDetector(
          onTap: () => onPilih(on && nilai.isNotEmpty ? '' : nilai),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            height: 40,
            padding: const EdgeInsets.symmetric(horizontal: 18),
            alignment: Alignment.center,
            decoration: BoxDecoration(color: on ? AppColors.primary : AppColors.surfaceAlt, borderRadius: BorderRadius.circular(20)),
            child: Text(label,
                style: GoogleFonts.plusJakartaSans(fontSize: 12.5, fontWeight: FontWeight.w600, color: on ? Colors.white : AppColors.ink)),
          ),
        ),
      );
    }

    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: screenGutter(context)),
        itemCount: tipes.length + 1,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) => i == 0 ? chip('Semua', '') : chip(tipes[i - 1].namaTipe, tipes[i - 1].namaTipe),
      ),
    );
  }
}

/// Lencana lembut: teks berwarna di atas latar pucat.
class SoftBadge extends StatelessWidget {
  final String label;
  final Color color;
  final Color background;
  const SoftBadge({super.key, required this.label, required this.color, required this.background});

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(20)),
        child: Text(label, style: GoogleFonts.plusJakartaSans(fontSize: 11.5, fontWeight: FontWeight.w700, color: color)),
      );
}
