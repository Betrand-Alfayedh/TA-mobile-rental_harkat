import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/admin_model.dart';
import '../../providers/admin_provider.dart';
import 'pembayaran_sheet.dart';
import 'pesanan_detail_screen.dart';
import 'widgets/admin_widgets.dart';

/// Daftar pembayaran. Bawaannya "menunggu verifikasi": pekerjaan harian utama admin.
class PembayaranListScreen extends StatelessWidget {
  const PembayaranListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const TabHeader('Pembayaran'),
        BarisFilter<int>(
          pilihan: const [('Menunggu verifikasi', 2), ('Belum dibayar', 0), ('Lunas', 1), ('Semua', null)],
          terpilih: prov.filterStatusPembayaran,
          onPilih: (s) => prov.aturFilterPembayaran(status: s, semua: s == null),
        ),
        Expanded(
          child: DaftarBerhalaman<PembayaranAdminModel>(
            halaman: prov.pembayaran,
            onRefresh: prov.muatPembayaran,
            onLagi: prov.pembayaranLagi,
            empty: EmptyState(
              icon: Icons.payments_outlined,
              title: prov.filterStatusPembayaran == 2 ? 'Tidak ada pembayaran menunggu verifikasi' : 'Tidak ada pembayaran',
              subtitle: 'Bukti yang diunggah pelanggan akan muncul di sini.',
            ),
            itemBuilder: (ctx, p) => _KartuPembayaran(p),
          ),
        ),
      ]))),
    );
  }
}

class _KartuPembayaran extends StatelessWidget {
  final PembayaranAdminModel p;
  const _KartuPembayaran(this.p);

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: () => bukaPembayaran(context, p),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(22),
          boxShadow: AppColors.softShadow,
          border: p.menungguVerifikasi ? Border.all(color: AppColors.warning.withValues(alpha: 0.6), width: 1.5) : null,
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(p.judul, style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700))),
            StatusPill(label: labelStatusPembayaran(p.status), color: warnaStatusPembayaran(p.status)),
          ]),
          const SizedBox(height: 4),
          Text(Formatters.toRupiah(p.jumlah),
              style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.ink)),
          const SizedBox(height: 6),
          Text('#${p.bookingId}  ${p.penyewa ?? ''}', style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.textSecondary)),
          if (p.jatuhTempo != null)
            Text('Jatuh tempo ${Formatters.toShortDateTime(p.jatuhTempo)}',
                style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.textHint)),
          const SizedBox(height: 4),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => PesananDetailScreen(bookingId: p.bookingId))),
              child: const Text('Lihat pesanan'),
            ),
          ),
        ]),
      ),
    );
  }
}
