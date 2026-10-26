import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/bukti_validator.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../providers/booking_provider.dart';
import '../../models/booking_model.dart';

class PaymentScreen extends StatefulWidget {
  final BookingModel booking;

  /// Tagihan yang dibayar. Kosong = DP booking ini.
  final PembayaranModel? pembayaran;
  const PaymentScreen({super.key, required this.booking, this.pembayaran});
  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  File?  _image;
  int    _metode      = 3; // default: QRIS
  bool   _uploading   = false;
  final _picker       = ImagePicker();

  PembayaranModel? get _dp => widget.pembayaran ?? widget.booking.pembayaranDp;
  bool get _isDp => (_dp?.jenis ?? 1) == 1;

  Future<void> _pickImage(ImageSource src) async {
    try {
      final picked = await _picker.pickImage(source: src, imageQuality: 70, maxWidth: 1200);
      if (picked == null) return;
      final file = File(picked.path);
      // Cek format dan ukuran di perangkat agar tidak menunggu unggahan lama lalu ditolak server.
      final galat = BuktiValidator.periksa(path: picked.path, bytes: await file.length());
      if (galat != null) {
        if (mounted) _showSnack(galat, isError: true);
        return;
      }
      if (mounted) setState(() => _image = file);
    } catch (_) {
      // Izin kamera/galeri ditolak atau pemilih gambar gagal.
      if (mounted) _showSnack('Foto tidak dapat diambil. Periksa izin kamera/galeri di pengaturan HP.', isError: true);
    }
  }

  void _showImageSourceSheet() {
    final t = GoogleFonts.plusJakartaSans;
    Widget pilihan(IconData icon, String label, ImageSource src) => ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 4),
          leading: Container(
            width: 44, height: 44,
            decoration: BoxDecoration(color: AppColors.primarySoft, borderRadius: BorderRadius.circular(14)),
            child: Icon(icon, color: AppColors.primary),
          ),
          title: Text(label, style: t(fontSize: 15, fontWeight: FontWeight.w600, color: AppColors.ink)),
          onTap: () { Navigator.pop(context); _pickImage(src); },
        );

    showModalBottomSheet(
      context: context,
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            pilihan(Icons.photo_camera_outlined, 'Kamera', ImageSource.camera),
            pilihan(Icons.photo_library_outlined, 'Galeri', ImageSource.gallery),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Future<void> _upload() async {
    if (_image == null) {
      _showSnack('Pilih foto bukti pembayaran terlebih dahulu', isError: true);
      return;
    }
    setState(() => _uploading = true);

    final ok = await context.read<BookingProvider>().uploadBukti(
      bookingId: widget.booking.id,
      imageFile: _image!,
      metode:    _metode,
      pembayaranId: _isDp ? null : _dp?.id,
    );

    if (!mounted) return;
    setState(() => _uploading = false);

    if (ok) {
      _showSnack('Bukti pembayaran berhasil diupload!');
      await Future.delayed(const Duration(seconds: 1));
      if (mounted) Navigator.pop(context);
    } else {
      _showSnack(context.read<BookingProvider>().error, isError: true);
    }
  }

  void _showSnack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? AppColors.error : AppColors.success,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final dp     = _dp;
    final gutter = screenGutter(context);
    final t      = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(
            children: [
              ScreenHeader(_isDp ? 'Pembayaran DP' : 'Pembayaran Pelunasan'),
              Expanded(
                child: ListView(
                  padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                  children: [
                    // ── Tagihan ────────────────────────────────
                    if (dp != null) _TagihanCard(isDp: _isDp, pembayaran: dp),
                    const SizedBox(height: 20),

                    // ── Ringkasan booking ──────────────────────
                    _SummaryCard(booking: widget.booking),
                    const SizedBox(height: 24),

                    // ── Metode ─────────────────────────────────
                    const SectionLabel('Metode Pembayaran'),
                    Row(children: [
                      Expanded(child: ChoiceTile(compact: true, title: 'Cash', icon: Icons.payments_outlined, selected: _metode == 1, onTap: () => setState(() => _metode = 1))),
                      const SizedBox(width: 10),
                      Expanded(child: ChoiceTile(compact: true, title: 'Transfer', icon: Icons.account_balance_outlined, selected: _metode == 2, onTap: () => setState(() => _metode = 2))),
                      const SizedBox(width: 10),
                      Expanded(child: ChoiceTile(compact: true, title: 'QRIS', icon: Icons.qr_code_rounded, selected: _metode == 3, onTap: () => setState(() => _metode = 3))),
                    ]),
                    const SizedBox(height: 20),

                    // ── QRIS ───────────────────────────────────
                    if (_metode == 3) ...[
                      SoftCard(
                        child: Column(children: [
                          Container(
                            width: 168, height: 168,
                            decoration: BoxDecoration(color: AppColors.surfaceAlt, borderRadius: BorderRadius.circular(20)),
                            child: const Icon(Icons.qr_code_2_rounded, size: 128, color: AppColors.ink),
                          ),
                          const SizedBox(height: 12),
                          Text('Scan QR Code untuk membayar', style: t(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.ink)),
                          Text('Harkat Car Rental', style: t(fontSize: 12, color: AppColors.muted)),
                        ]),
                      ),
                      const SizedBox(height: 20),
                    ],

                    // ── Unggah bukti ───────────────────────────
                    const SectionLabel('Upload Bukti Pembayaran'),
                    GestureDetector(
                      onTap: _showImageSourceSheet,
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        height: 190,
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(color: _image != null ? AppColors.primary : AppColors.line, width: _image != null ? 1.5 : 1),
                        ),
                        child: _image != null
                            ? Image.file(_image!, fit: BoxFit.cover, width: double.infinity)
                            : Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Container(
                                    width: 56, height: 56,
                                    decoration: const BoxDecoration(color: AppColors.primarySoft, shape: BoxShape.circle),
                                    child: const Icon(Icons.cloud_upload_outlined, color: AppColors.primary, size: 28),
                                  ),
                                  const SizedBox(height: 12),
                                  Text('Ketuk untuk upload foto', style: t(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.ink)),
                                  const SizedBox(height: 2),
                                  Text('JPG, JPEG, PNG (maks. 5 MB)', style: t(fontSize: 12, color: AppColors.muted)),
                                ],
                              ),
                      ),
                    ),
                    if (_image != null)
                      Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton.icon(
                          onPressed: () => setState(() => _image = null),
                          icon: const Icon(Icons.delete_outline_rounded, color: AppColors.error, size: 18),
                          label: Text('Hapus foto', style: t(color: AppColors.error, fontSize: 13, fontWeight: FontWeight.w600)),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: BottomActionBar(
        child: PrimaryButton(
          label: 'Kirim Bukti Pembayaran',
          loading: _uploading,
          onPressed: _image == null ? null : _upload,
        ),
      ),
    );
  }
}

/// Tagihan: kartu hijau pekat dengan jumlah besar.
class _TagihanCard extends StatelessWidget {
  final bool isDp;
  final PembayaranModel pembayaran;
  const _TagihanCard({required this.isDp, required this.pembayaran});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(24)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(isDp ? 'Jumlah DP' : 'Jumlah Pelunasan', style: t(fontSize: 12.5, fontWeight: FontWeight.w500, color: Colors.white70)),
        const SizedBox(height: 6),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text(rp(pembayaran.jumlah), style: t(fontSize: 30, fontWeight: FontWeight.w800, color: Colors.white)),
        ),
        const SizedBox(height: 14),
        Row(children: [
          if (pembayaran.jatuhTempo != null)
            Expanded(child: _Info('Jatuh tempo', Formatters.toShortDateTime(pembayaran.jatuhTempo))),
          Expanded(child: _Info('Status', pembayaran.statusPembayaranText ?? '-')),
        ]),
      ]),
    );
  }
}

class _Info extends StatelessWidget {
  final String label, value;
  const _Info(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label, style: t(fontSize: 11, color: Colors.white60)),
      const SizedBox(height: 2),
      Text(value, style: t(fontSize: 13, fontWeight: FontWeight.w700, color: Colors.white)),
    ]);
  }
}

class _SummaryCard extends StatelessWidget {
  final BookingModel booking;
  const _SummaryCard({required this.booking});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return SoftCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Ringkasan Booking #${booking.id}', style: t(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.ink)),
        const SizedBox(height: 12),
        ...booking.details.map((d) => Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Row(children: [
            const Icon(Icons.directions_car_outlined, size: 16, color: AppColors.muted),
            const SizedBox(width: 8),
            Expanded(child: Text(d.mobil?.merk ?? 'Mobil', style: t(fontSize: 13, color: AppColors.ink))),
            Text('${d.lamaSewa} hari', style: t(fontSize: 12, color: AppColors.muted)),
          ]),
        )),
        const Divider(height: 20),
        _Row('Total', booking.totalHargaRp ?? Formatters.toRupiah(booking.totalHarga)),
        const SizedBox(height: 6),
        _Row('DP (50%)', booking.uangMukaRp ?? Formatters.toRupiah(booking.uangMuka)),
      ]),
    );
  }
}

class _Row extends StatelessWidget {
  final String label, value;
  const _Row(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Text(label, style: t(fontSize: 12.5, color: AppColors.muted)),
      Text(value, style: t(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.ink)),
    ]);
  }
}
