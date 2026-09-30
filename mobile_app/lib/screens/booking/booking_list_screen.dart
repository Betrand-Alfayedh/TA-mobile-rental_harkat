import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/booking_provider.dart';
import '../../models/booking_model.dart';
import '../payment/payment_screen.dart';

class BookingListScreen extends StatefulWidget {
  const BookingListScreen({super.key});
  @override
  State<BookingListScreen> createState() => _BookingListScreenState();
}

class _BookingListScreenState extends State<BookingListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BookingProvider>().loadBookings();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: Text('Booking Aktif', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppColors.textSecondary),
            onPressed: () => context.read<BookingProvider>().loadBookings(),
          ),
        ],
      ),
      body: Consumer<BookingProvider>(
        builder: (_, prov, __) {
          if (prov.state == BookingLoadState.loading) {
            return const Center(child: CircularProgressIndicator(color: AppColors.accent));
          }
          if (prov.state == BookingLoadState.error) {
            return _ErrorView(message: prov.error, onRetry: prov.loadBookings);
          }
          if (prov.bookings.isEmpty) {
            return const _EmptyView(
              icon: Icons.bookmark_border_rounded,
              title: 'Belum Ada Booking',
              subtitle: 'Booking mobil favorit Anda sekarang!',
            );
          }
          return RefreshIndicator(
            color: AppColors.accent,
            backgroundColor: AppColors.surface,
            onRefresh: prov.loadBookings,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: prov.bookings.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (_, i) => _BookingCard(booking: prov.bookings[i]),
            ),
          );
        },
      ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  final BookingModel booking;
  const _BookingCard({required this.booking});

  Color get _statusColor {
    switch (booking.status) {
      case 1:  return AppColors.statusBooked;
      case 2:  return AppColors.statusOngoing;
      case 3:  return AppColors.statusDone;
      default: return AppColors.statusCancel;
    }
  }

  @override
  Widget build(BuildContext context) {
    final dp        = booking.pembayaranDp;
    final firstCar  = booking.details.isNotEmpty ? booking.details.first : null;

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 8, offset: const Offset(0, 3))],
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: _statusColor.withOpacity(0.1),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              border: Border(bottom: BorderSide(color: _statusColor.withOpacity(0.2))),
            ),
            child: Row(
              children: [
                Text('#${booking.id}', style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: _statusColor.withOpacity(0.2), borderRadius: BorderRadius.circular(20)),
                  child: Text(booking.statusLabel ?? 'Unknown', style: GoogleFonts.outfit(fontSize: 11, fontWeight: FontWeight.w700, color: _statusColor)),
                ),
              ],
            ),
          ),

          // Body
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (firstCar != null) ...[
                  Row(
                    children: [
                      Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.15), borderRadius: BorderRadius.circular(12)),
                        child: const Icon(Icons.directions_car_rounded, color: AppColors.primaryLight, size: 22)),
                      const SizedBox(width: 12),
                      Expanded(child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(firstCar.mobil?.merk ?? 'Mobil', style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                          Text('${firstCar.lamaSewa} hari • ${firstCar.pakaiSupir ? "Dengan Supir" : "Tanpa Supir"}', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
                        ],
                      )),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      const Icon(Icons.calendar_today_outlined, size: 13, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Expanded(child: Text(
                        firstCar.tanggalMulaiFormat ?? firstCar.tanggalSewa,
                        style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary),
                        overflow: TextOverflow.ellipsis,
                      )),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.event_available_outlined, size: 13, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Expanded(child: Text(
                        firstCar.tanggalSelesaiFormat ?? firstCar.tanggalKembali,
                        style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary),
                        overflow: TextOverflow.ellipsis,
                      )),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],

                // Total + DP
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('Total Sewa', style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textSecondary)),
                      Text(booking.totalHargaRp ?? Formatters.toRupiah(booking.totalHarga), style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
                    ]),
                    if (dp != null)
                      _DpChip(pembayaran: dp),
                  ],
                ),

                // Pay DP button
                if (dp != null && dp.belumBayar) ...[
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => PaymentScreen(booking: booking))),
                      icon: const Icon(Icons.payment_rounded, size: 18, color: Colors.white),
                      label: Text('Bayar DP Sekarang', style: GoogleFonts.outfit(fontWeight: FontWeight.w700, color: Colors.white)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.accent,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                    ),
                  ),
                ],

                if (dp != null && dp.menungguVerifik) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(color: AppColors.info.withOpacity(0.1), borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.info.withOpacity(0.3))),
                    child: Row(children: [
                      const Icon(Icons.info_outline, color: AppColors.info, size: 16),
                      const SizedBox(width: 8),
                      Text('Menunggu verifikasi admin', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.info)),
                    ]),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DpChip extends StatelessWidget {
  final PembayaranModel pembayaran;
  const _DpChip({required this.pembayaran});

  @override
  Widget build(BuildContext context) {
    Color color;
    String label;
    if (pembayaran.sudahBayar)      { color = AppColors.success; label = 'DP Lunas'; }
    else if (pembayaran.menungguVerifik) { color = AppColors.info;    label = 'Verifikasi'; }
    else                                 { color = AppColors.error;   label = 'Belum Bayar'; }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(20), border: Border.all(color: color.withOpacity(0.4))),
      child: Column(children: [
        Text('DP', style: GoogleFonts.outfit(fontSize: 9, color: color)),
        Text(label, style: GoogleFonts.outfit(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
      ]),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String title, subtitle;
  const _EmptyView({required this.icon, required this.title, required this.subtitle});
  @override
  Widget build(BuildContext context) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
    Icon(icon, size: 64, color: AppColors.border),
    const SizedBox(height: 16),
    Text(title,    style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
    const SizedBox(height: 4),
    Text(subtitle, style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary)),
  ]));
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});
  @override
  Widget build(BuildContext context) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
    const Icon(Icons.cloud_off_rounded, size: 64, color: AppColors.border),
    const SizedBox(height: 12),
    Text(message, style: GoogleFonts.outfit(color: AppColors.textSecondary), textAlign: TextAlign.center),
    const SizedBox(height: 16),
    ElevatedButton(onPressed: onRetry, style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))), child: const Text('Coba Lagi')),
  ]));
}
