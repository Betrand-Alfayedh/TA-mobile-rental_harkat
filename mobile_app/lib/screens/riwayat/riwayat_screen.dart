import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/booking_provider.dart';
import '../../models/booking_model.dart';

class RiwayatScreen extends StatefulWidget {
  const RiwayatScreen({super.key});
  @override
  State<RiwayatScreen> createState() => _RiwayatScreenState();
}

class _RiwayatScreenState extends State<RiwayatScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BookingProvider>().loadRiwayat();
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
        title: Text('Riwayat Sewa', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppColors.textSecondary),
            onPressed: () => context.read<BookingProvider>().loadRiwayat(),
          ),
        ],
      ),
      body: Consumer<BookingProvider>(
        builder: (_, prov, __) {
          if (prov.riwState == BookingLoadState.loading) {
            return const Center(child: CircularProgressIndicator(color: AppColors.accent));
          }
          if (prov.riwState == BookingLoadState.error) {
            return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.cloud_off_rounded, size: 64, color: AppColors.border),
              const SizedBox(height: 12),
              Text(prov.error, style: GoogleFonts.outfit(color: AppColors.textSecondary)),
              const SizedBox(height: 16),
              ElevatedButton(onPressed: prov.loadRiwayat, child: const Text('Coba Lagi')),
            ]));
          }
          if (prov.riwayats.isEmpty) {
            return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.history_rounded, size: 64, color: AppColors.border),
              const SizedBox(height: 16),
              Text('Belum Ada Riwayat', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
              const SizedBox(height: 4),
              Text('Riwayat sewa akan muncul di sini', style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary)),
            ]));
          }
          return RefreshIndicator(
            color: AppColors.accent,
            backgroundColor: AppColors.surface,
            onRefresh: prov.loadRiwayat,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: prov.riwayats.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (_, i) => _RiwayatCard(booking: prov.riwayats[i]),
            ),
          );
        },
      ),
    );
  }
}

class _RiwayatCard extends StatelessWidget {
  final BookingModel booking;
  const _RiwayatCard({required this.booking});

  Color get _statusColor => booking.isDone ? AppColors.statusDone : AppColors.statusCancel;
  String get _statusLabel => booking.isDone ? 'Selesai' : 'Dibatalkan';
  IconData get _statusIcon => booking.isDone ? Icons.check_circle_rounded : Icons.cancel_rounded;

  @override
  Widget build(BuildContext context) {
    final firstCar = booking.details.isNotEmpty ? booking.details.first : null;
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: _statusColor.withOpacity(0.08),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
            ),
            child: Row(
              children: [
                Icon(_statusIcon, color: _statusColor, size: 18),
                const SizedBox(width: 8),
                Text(_statusLabel, style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: _statusColor)),
                const Spacer(),
                Text('#${booking.id}', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (firstCar != null) ...[
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: _statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
                        child: Icon(Icons.directions_car_rounded, color: _statusColor, size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(firstCar.mobil?.merk ?? 'Mobil', style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                          Text('${firstCar.lamaSewa} hari', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
                        ],
                      )),
                      Text(booking.totalHargaRp ?? Formatters.toRupiah(booking.totalHarga),
                        style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],
                // Dates
                Row(children: [
                  const Icon(Icons.calendar_today_outlined, size: 12, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(Formatters.toShortDateTime(booking.tanggalBooking),
                    style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textSecondary)),
                ]),
                if (booking.details.length > 1) ...[
                  const SizedBox(height: 6),
                  Text('+${booking.details.length - 1} mobil lainnya', style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textHint)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
