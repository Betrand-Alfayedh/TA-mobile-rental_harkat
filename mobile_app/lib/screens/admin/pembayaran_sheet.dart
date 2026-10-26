import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/admin_model.dart';
import '../../providers/admin_provider.dart';
import 'widgets/admin_widgets.dart';

/// Buka lembar rincian pembayaran: bukti transfer dan aksi (verifikasi, tolak, atau tandai lunas).
Future<void> bukaPembayaran(BuildContext context, PembayaranAdminModel p) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    builder: (_) => PembayaranSheet(pembayaran: p),
  );
}

class PembayaranSheet extends StatefulWidget {
  final PembayaranAdminModel pembayaran;
  const PembayaranSheet({super.key, required this.pembayaran});

  @override
  State<PembayaranSheet> createState() => _PembayaranSheetState();
}

class _PembayaranSheetState extends State<PembayaranSheet> {
  int _metode = 1;   // 1 tunai, 2 transfer, 3 QRIS

  PembayaranAdminModel get p => widget.pembayaran;

  Future<void> _verifikasi() async {
    final prov = context.read<AdminProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final ya = await konfirmasi(context,
        judul: 'Verifikasi pembayaran?',
        isi: '${p.judul}\n${Formatters.toRupiah(p.jumlah)}\n\nStatus menjadi lunas dan invoice dikirim ke email pelanggan.',
        tombol: 'Verifikasi',
        warna: AppColors.success);
    if (!ya) return;
    final h = await prov.verifikasi(p);
    _selesai(nav, messenger, h.galat, 'Pembayaran diverifikasi.', h.info);
  }

  Future<void> _tolak() async {
    final prov = context.read<AdminProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final alasan = await mintaTeks(context,
        judul: 'Tolak pembayaran',
        label: 'Alasan (mis. nominal kurang)',
        tombol: 'Tolak',
        warna: AppColors.error);
    if (alasan == null) return;
    final h = await prov.tolak(p, catatan: alasan);
    _selesai(nav, messenger, h.galat, 'Pembayaran ditolak. Pelanggan dapat mengunggah ulang.', h.info);
  }

  Future<void> _lunas() async {
    final prov = context.read<AdminProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    const nama = {1: 'tunai', 2: 'transfer', 3: 'QRIS'};
    final ya = await konfirmasi(context,
        judul: 'Tandai lunas?',
        isi: '${p.judul}\n${Formatters.toRupiah(p.jumlah)} diterima ${nama[_metode]}.',
        tombol: 'Tandai lunas',
        warna: AppColors.success);
    if (!ya) return;
    final h = await prov.tandaiLunas(p, _metode);
    _selesai(nav, messenger, h.galat, 'Pembayaran dicatat lunas.', h.info);
  }

  void _selesai(NavigatorState nav, ScaffoldMessengerState messenger, String? galat, String sukses, String? info) {
    if (galat == null && nav.mounted) nav.pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(galat ?? ((info != null && info.isNotEmpty) ? '$sukses $info' : sukses)),
        backgroundColor: galat == null ? AppColors.success : AppColors.error,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final submitting = context.select<AdminProvider, bool>((a) => a.submitting);

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Expanded(child: Text(p.judul, style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w800))),
                StatusPill(label: labelStatusPembayaran(p.status), color: warnaStatusPembayaran(p.status)),
              ]),
              const SizedBox(height: 4),
              Text(Formatters.toRupiah(p.jumlah),
                  style: GoogleFonts.plusJakartaSans(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.ink)),
              const SizedBox(height: 12),
              BarisInfo('Pesanan', '#${p.bookingId}'),
              if (p.penyewa != null) BarisInfo('Penyewa', p.penyewa!),
              if (p.metodeText != null && p.metode != null) BarisInfo('Metode', p.metodeText!),
              if (p.jatuhTempo != null) BarisInfo('Jatuh tempo', Formatters.toShortDateTime(p.jatuhTempo)),
              if (p.tanggalPembayaran != null) BarisInfo('Dibayar', Formatters.toShortDateTime(p.tanggalPembayaran)),
              if (p.catatanAdmin != null && p.catatanAdmin!.isNotEmpty) BarisInfo('Catatan', p.catatanAdmin!),
              if (p.fotoBukti != null) ...[
                const SizedBox(height: 8),
                Text('Bukti pembayaran', style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: () => _perbesar(context, p.fotoBukti!),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: Image.network(p.fotoBukti!, height: 220, width: double.infinity, fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                              height: 80,
                              alignment: Alignment.center,
                              color: AppColors.surfaceAlt,
                              child: const Text('Bukti tidak dapat dimuat'),
                            )),
                  ),
                ),
              ],
              const SizedBox(height: 16),
              if (p.menungguVerifikasi)
                Row(children: [
                  Expanded(
                    child: OutlinedButton(onPressed: submitting ? null : _tolak, child: const Text('Tolak')),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: submitting ? null : _verifikasi,
                      child: submitting ? const _Putar() : const Text('Verifikasi'),
                    ),
                  ),
                ])
              else if (p.belumBayar) ...[
                Text('Terima pembayaran langsung', style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                Row(children: [
                  for (final e in const {1: 'Tunai', 2: 'Transfer', 3: 'QRIS'}.entries) ...[
                    if (e.key != 1) const SizedBox(width: 10),
                    Expanded(
                      child: ChoiceTile(
                        compact: true,
                        title: e.value,
                        selected: _metode == e.key,
                        onTap: submitting ? () {} : () => setState(() => _metode = e.key),
                      ),
                    ),
                  ],
                ]),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  height: 54,
                  child: ElevatedButton(
                    onPressed: submitting ? null : _lunas,
                    child: submitting ? const _Putar() : const Text('Tandai lunas'),
                  ),
                ),
              ] else
                Text('Pembayaran ini sudah lunas.', style: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary)),
            ],
          ),
        ),
      ),
    );
  }

  void _perbesar(BuildContext context, String url) {
    showDialog<void>(
      context: context,
      builder: (_) => Dialog.fullscreen(
        backgroundColor: Colors.black,
        child: Stack(children: [
          Center(child: InteractiveViewer(child: Image.network(url, errorBuilder: (_, __, ___) => const Text('Bukti tidak dapat dimuat')))),
          Positioned(top: 8, right: 8, child: IconButton(icon: const Icon(Icons.close_rounded), onPressed: () => Navigator.pop(context))),
        ]),
      ),
    );
  }
}

class _Putar extends StatelessWidget {
  const _Putar();

  @override
  Widget build(BuildContext context) =>
      const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white));
}
