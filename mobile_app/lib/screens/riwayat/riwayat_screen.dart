import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../core/widgets/state_views.dart';
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
    final gutter = screenGutter(context);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(
            children: [
              TabHeader(
                'Riwayat Sewa',
                trailing: RoundIconButton(
                  icon: Icons.refresh_rounded,
                  tooltip: 'Segarkan',
                  onPressed: () => context.read<BookingProvider>().loadRiwayat(),
                ),
              ),
              Expanded(
                child: Consumer<BookingProvider>(
                  builder: (_, prov, __) {
                    if (prov.riwState == BookingLoadState.loading) {
                      return const Center(child: CircularProgressIndicator(color: AppColors.primary));
                    }
                    if (prov.riwState == BookingLoadState.error) {
                      return ErrorState(message: prov.error, onRetry: prov.loadRiwayat);
                    }
                    if (prov.riwayats.isEmpty) {
                      return const EmptyState(
                        icon: Icons.history_rounded,
                        title: 'Belum Ada Riwayat',
                        subtitle: 'Riwayat sewa akan muncul di sini',
                      );
                    }
                    return RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: prov.loadRiwayat,
                      child: ListView.separated(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                        itemCount: prov.riwayats.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 14),
                        itemBuilder: (_, i) => _RiwayatCard(booking: prov.riwayats[i]),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RiwayatCard extends StatelessWidget {
  final BookingModel booking;
  const _RiwayatCard({required this.booking});

  @override
  Widget build(BuildContext context) {
    final t        = GoogleFonts.plusJakartaSans;
    final firstCar = booking.details.isNotEmpty ? booking.details.first : null;
    final selesai  = booking.isDone;
    final st       = bookingStatusStyle(selesai ? 3 : 0);
    final foto     = firstCar?.mobil?.primaryImage ?? '';
    final nama     = firstCar?.mobil?.masterMobil?.nama ?? firstCar?.mobil?.merk ?? 'Mobil';

    return SoftCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Text('Booking #${booking.id}', style: t(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.muted)),
            const Spacer(),
            SoftBadge(label: selesai ? 'Selesai' : 'Dibatalkan', color: st.fg, background: st.bg),
          ]),
          const SizedBox(height: 14),
          if (firstCar != null)
            Row(children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: SizedBox(
                  width: 72, height: 56,
                  child: foto.isNotEmpty
                      ? CachedNetworkImage(imageUrl: foto, fit: BoxFit.cover,
                          placeholder: (_, __) => const FotoKosong(), errorWidget: (_, __, ___) => const FotoKosong())
                      : const FotoKosong(),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(nama, maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: t(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink)),
                  const SizedBox(height: 2),
                  Text('${firstCar.lamaSewa} hari', style: t(fontSize: 12, color: AppColors.muted)),
                ]),
              ),
              const SizedBox(width: 8),
              Text((booking.totalHargaRp ?? Formatters.toRupiah(booking.totalHarga)).replaceFirst('Rp ', 'Rp'),
                  style: t(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.ink)),
            ]),
          const SizedBox(height: 12),
          Row(children: [
            const Icon(Icons.calendar_today_outlined, size: 14, color: AppColors.muted),
            const SizedBox(width: 8),
            Expanded(
              child: Text(Formatters.toShortDateTime(booking.tanggalBooking),
                  style: t(fontSize: 12, color: AppColors.muted)),
            ),
            if (booking.details.length > 1)
              Text('+${booking.details.length - 1} mobil lainnya', style: t(fontSize: 11.5, color: AppColors.hint)),
          ]),
        ],
      ),
    );
  }
}
