import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/admin_model.dart';
import '../../providers/admin_provider.dart';
import 'pesanan_detail_screen.dart';
import 'widgets/admin_widgets.dart';

/// Daftar pesanan dengan pencarian (nama, HP, atau #id) dan filter status.
class PesananListScreen extends StatelessWidget {
  const PesananListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const TabHeader('Pesanan'),
        KotakCari(
          hint: 'Cari nama, no. HP, atau #id',
          awal: prov.kataKunciPesanan,
          onCari: (q) => prov.aturFilterPesanan(kataKunci: q),
        ),
        BarisFilter<int>(
          pilihan: const [('Semua', null), ('Booked', 1), ('Berjalan', 2), ('Selesai', 3), ('Dibatalkan', 0)],
          terpilih: prov.filterStatusPesanan,
          onPilih: (s) => prov.aturFilterPesanan(status: s, hapusStatus: s == null),
        ),
        Expanded(
          child: DaftarBerhalaman<BookingAdminModel>(
            halaman: prov.bookings,
            onRefresh: prov.muatPesanan,
            onLagi: prov.pesananLagi,
            empty: const EmptyState(
              icon: Icons.receipt_long_outlined,
              title: 'Tidak ada pesanan',
              subtitle: 'Pesanan dari pelanggan akan muncul di sini.',
            ),
            itemBuilder: (ctx, b) => _KartuPesanan(b),
          ),
        ),
      ]))),
    );
  }
}

class _KartuPesanan extends StatelessWidget {
  final BookingAdminModel b;
  const _KartuPesanan(this.b);

  @override
  Widget build(BuildContext context) {
    final namaMobil = b.units.map((u) => u.namaLengkap).where((e) => e.isNotEmpty).join(', ');
    final perluVerifikasi = b.jumlahMenungguVerifikasi > 0;
    final perluSopir = b.perluPenugasan > 0;

    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => PesananDetailScreen(bookingId: b.id))),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(
              child: Text('#${b.id}  ${b.penyewaNama}',
                  maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700)),
            ),
            StatusPill(label: labelStatusPesanan(b.status), color: warnaStatusPesanan(b.status)),
          ]),
          const SizedBox(height: 6),
          Text(namaMobil.isEmpty ? '${b.units.length} unit' : namaMobil,
              maxLines: 2, overflow: TextOverflow.ellipsis,
              style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.textSecondary)),
          const SizedBox(height: 8),
          Row(children: [
            Expanded(child: Text(Formatters.toRupiah(b.totalHarga), style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800, color: AppColors.ink))),
            Text(b.tanggalBooking ?? '', style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.textHint)),
          ]),
          if (perluVerifikasi || perluSopir) ...[
            const SizedBox(height: 8),
            Wrap(spacing: 8, runSpacing: 4, children: [
              if (perluVerifikasi) const StatusPill(label: 'Pembayaran menunggu verifikasi', color: AppColors.statusBooked),
              if (perluSopir) const StatusPill(label: 'Perlu sopir', color: AppColors.info),
            ]),
          ],
        ]),
      ),
    );
  }
}
