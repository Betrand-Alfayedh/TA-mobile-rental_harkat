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
                'Booking Aktif',
                trailing: RoundIconButton(
                  icon: Icons.refresh_rounded,
                  tooltip: 'Segarkan',
                  onPressed: () => context.read<BookingProvider>().loadBookings(),
                ),
              ),
              Expanded(
                child: Consumer<BookingProvider>(
                  builder: (_, prov, __) {
                    if (prov.state == BookingLoadState.loading) {
                      return const Center(child: CircularProgressIndicator(color: AppColors.primary));
                    }
                    if (prov.state == BookingLoadState.error) {
                      return ErrorState(message: prov.error, onRetry: prov.loadBookings);
                    }
                    if (prov.bookings.isEmpty) {
                      return const EmptyState(
                        icon: Icons.bookmark_border_rounded,
                        title: 'Belum Ada Booking',
                        subtitle: 'Booking mobil favorit Anda sekarang!',
                      );
                    }
                    return RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: prov.loadBookings,
                      child: ListView.separated(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                        itemCount: prov.bookings.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 14),
                        itemBuilder: (_, i) => _BookingCard(booking: prov.bookings[i]),
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

class _BookingCard extends StatelessWidget {
  final BookingModel booking;
  const _BookingCard({required this.booking});

  Future<void> _confirmCancel(BuildContext context) async {
    final yes = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Batalkan Booking?'),
        content: Text('Booking #${booking.id} akan dibatalkan dan tidak dapat dikembalikan.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            style: TextButton.styleFrom(foregroundColor: AppColors.muted),
            child: const Text('Tidak'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(backgroundColor: AppColors.error, minimumSize: const Size(64, 44)),
            child: const Text('Ya, Batalkan'),
          ),
        ],
      ),
    );
    if (yes != true || !context.mounted) return;

    // Setelah dibatalkan kartu ini hilang dari daftar, jadi ambil messenger/provider sebelum await.
    final messenger = ScaffoldMessenger.of(context);
    final prov      = context.read<BookingProvider>();
    final ok        = await prov.cancelBooking(booking.id);
    messenger.showSnackBar(SnackBar(
      content: Text(ok ? 'Booking berhasil dibatalkan' : prov.error),
      backgroundColor: ok ? AppColors.success : AppColors.error,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final t         = GoogleFonts.plusJakartaSans;
    final dp        = booking.pembayaranDp;
    final pelunasan = booking.pelunasan;
    final firstCar  = booking.details.isNotEmpty ? booking.details.first : null;
    final st        = bookingStatusStyle(booking.status);
    final foto      = firstCar?.mobil?.primaryImage ?? '';
    final nama      = firstCar?.mobil?.masterMobil?.nama ?? firstCar?.mobil?.merk ?? 'Mobil';

    return SoftCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Kepala: nomor + status ──────────────────────
          Row(children: [
            Text('Booking #${booking.id}', style: t(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.muted)),
            const Spacer(),
            SoftBadge(label: booking.statusLabel ?? 'Tidak diketahui', color: st.fg, background: st.bg),
          ]),
          const SizedBox(height: 14),

          if (firstCar != null) ...[
            Row(children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: SizedBox(
                  width: 84, height: 64,
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
                  Text('${firstCar.lamaSewa} hari · ${firstCar.pakaiSupir ? "Dengan sopir" : "Tanpa sopir"}',
                      style: t(fontSize: 12, color: AppColors.muted)),
                  if (booking.details.length > 1)
                    Text('+${booking.details.length - 1} mobil lainnya', style: t(fontSize: 11.5, color: AppColors.hint)),
                ]),
              ),
            ]),
            const SizedBox(height: 14),
            _Tanggal(icon: Icons.calendar_today_outlined, label: firstCar.tanggalMulaiFormat ?? firstCar.tanggalSewa),
            const SizedBox(height: 6),
            _Tanggal(icon: Icons.event_available_outlined, label: firstCar.tanggalSelesaiFormat ?? firstCar.tanggalKembali),
            const SizedBox(height: 14),
          ],

          // ── Total + status DP ───────────────────────────
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: AppColors.bg, borderRadius: BorderRadius.circular(16)),
            child: Row(children: [
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Total sewa', style: t(fontSize: 11.5, color: AppColors.muted)),
                  const SizedBox(height: 2),
                  Text(booking.totalHargaRp ?? Formatters.toRupiah(booking.totalHarga),
                      style: t(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.ink)),
                ]),
              ),
              if (dp != null) _DpBadge(pembayaran: dp),
            ]),
          ),

          // ── Bayar DP ────────────────────────────────────
          if (dp != null && dp.belumBayar) ...[
            const SizedBox(height: 14),
            PrimaryButton(
              label: 'Bayar DP Sekarang',
              trailingIcon: Icons.arrow_forward_rounded,
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => PaymentScreen(booking: booking))),
            ),
          ],

          if (dp != null && dp.menungguVerifik) ...[
            const SizedBox(height: 14),
            const InfoBanner('Menunggu verifikasi admin'),
          ],

          // ── Pelunasan (muncul setelah DP lunas) ─────────
          if (dp != null && dp.sudahBayar && pelunasan != null && !pelunasan.sudahBayar) ...[
            const SizedBox(height: 16),
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Text('Pelunasan', style: t(fontSize: 12.5, color: AppColors.muted)),
              Text(Formatters.toRupiah(pelunasan.jumlah).replaceFirst('Rp ', 'Rp'),
                  style: t(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.ink)),
            ]),
            const SizedBox(height: 10),
            if (pelunasan.menungguVerifik)
              const InfoBanner('Pelunasan menunggu verifikasi admin')
            else
              PrimaryButton(
                label: 'Bayar Pelunasan',
                trailingIcon: Icons.arrow_forward_rounded,
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => PaymentScreen(booking: booking, pembayaran: pelunasan))),
              ),
          ],

          // ── Batalkan (hanya selama DP belum lunas; setelah itu hubungi admin) ──
          if (booking.isBooked && !(dp?.sudahBayar ?? false)) ...[
            const SizedBox(height: 6),
            SizedBox(
              width: double.infinity,
              child: TextButton(
                onPressed: () => _confirmCancel(context),
                style: TextButton.styleFrom(foregroundColor: AppColors.error, minimumSize: const Size(64, 48)),
                child: Text('Batalkan Booking', style: t(fontWeight: FontWeight.w700)),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Tanggal extends StatelessWidget {
  final IconData icon;
  final String label;
  const _Tanggal({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) => Row(children: [
        Icon(icon, size: 15, color: AppColors.muted),
        const SizedBox(width: 8),
        Expanded(
          child: Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: GoogleFonts.plusJakartaSans(fontSize: 12.5, fontWeight: FontWeight.w500, color: AppColors.ink)),
        ),
      ]);
}

class _DpBadge extends StatelessWidget {
  final PembayaranModel pembayaran;
  const _DpBadge({required this.pembayaran});

  @override
  Widget build(BuildContext context) {
    final (label, fg, bg) = pembayaran.sudahBayar
        ? ('DP Lunas', AppColors.success, AppColors.successSoft)
        : pembayaran.menungguVerifik
            ? ('Verifikasi DP', AppColors.info, AppColors.infoSoft)
            : ('DP Belum Bayar', AppColors.error, AppColors.errorSoft);
    return SoftBadge(label: label, color: fg, background: bg);
  }
}
