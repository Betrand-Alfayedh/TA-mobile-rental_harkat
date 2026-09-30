import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/booking_provider.dart';
import '../../models/booking_model.dart';

class PaymentScreen extends StatefulWidget {
  final BookingModel booking;
  const PaymentScreen({super.key, required this.booking});
  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  File?  _image;
  int    _metode      = 3; // default: QRIS
  bool   _uploading   = false;
  final _picker       = ImagePicker();

  PembayaranModel? get _dp => widget.booking.pembayaranDp;

  Future<void> _pickImage(ImageSource src) async {
    final picked = await _picker.pickImage(source: src, imageQuality: 70, maxWidth: 1200);
    if (picked != null) setState(() => _image = File(picked.path));
  }

  void _showImageSourceSheet() {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(width: 40, height: 4, margin: const EdgeInsets.only(top: 12), decoration: BoxDecoration(color: AppColors.border, borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 16),
            ListTile(
              leading: Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.15), borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.photo_camera_outlined, color: AppColors.primaryLight)),
              title: Text('Kamera', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontWeight: FontWeight.w600)),
              onTap: () { Navigator.pop(context); _pickImage(ImageSource.camera); },
            ),
            ListTile(
              leading: Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.15), borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.photo_library_outlined, color: AppColors.primaryLight)),
              title: Text('Galeri', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontWeight: FontWeight.w600)),
              onTap: () { Navigator.pop(context); _pickImage(ImageSource.gallery); },
            ),
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
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final dp = _dp;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: AppColors.textPrimary), onPressed: () => Navigator.pop(context)),
        title: Text('Pembayaran DP', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // ── Booking Summary ─────────────────────────────
          _SummaryCard(booking: widget.booking),
          const SizedBox(height: 20),

          // ── DP Info ─────────────────────────────────────
          if (dp != null) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [AppColors.accent.withOpacity(0.15), AppColors.accent.withOpacity(0.05)]),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.accent.withOpacity(0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    const Icon(Icons.monetization_on_outlined, color: AppColors.accent, size: 20),
                    const SizedBox(width: 8),
                    Text('Tagihan DP', style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.accent)),
                  ]),
                  const SizedBox(height: 12),
                  _Row('Jumlah DP',   Formatters.toRupiah(dp.jumlah)),
                  const SizedBox(height: 6),
                  if (dp.jatuhTempo != null)
                    _Row('Jatuh Tempo', Formatters.toShortDateTime(dp.jatuhTempo)),
                  const SizedBox(height: 6),
                  _Row('Status', dp.statusPembayaranText ?? '-'),
                ],
              ),
            ),
            const SizedBox(height: 20),
          ],

          // ── Payment Method ──────────────────────────────
          Text('Metode Pembayaran', style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
            child: Column(
              children: [
                _MetodeRadio(value: 1, group: _metode, label: 'Cash',     icon: Icons.money_rounded,            onChanged: (v) => setState(() => _metode = v)),
                const Divider(height: 1, color: AppColors.border),
                _MetodeRadio(value: 2, group: _metode, label: 'Transfer', icon: Icons.account_balance_outlined, onChanged: (v) => setState(() => _metode = v)),
                const Divider(height: 1, color: AppColors.border),
                _MetodeRadio(value: 3, group: _metode, label: 'QRIS',     icon: Icons.qr_code_rounded,          onChanged: (v) => setState(() => _metode = v)),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // ── QRIS Info ───────────────────────────────────
          if (_metode == 3) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border)),
              child: Column(children: [
                Container(width: 180, height: 180, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                  child: const Icon(Icons.qr_code_2_rounded, size: 140, color: Colors.black87)),
                const SizedBox(height: 12),
                Text('Scan QR Code untuk membayar', style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
                Text('Harkat Car Rental', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textHint)),
              ]),
            ),
            const SizedBox(height: 20),
          ],

          // ── Upload Proof ────────────────────────────────
          Text('Upload Bukti Pembayaran', style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
          const SizedBox(height: 12),
          GestureDetector(
            onTap: _showImageSourceSheet,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              height: 180,
              decoration: BoxDecoration(
                color: _image != null ? Colors.transparent : AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: _image != null ? AppColors.success : AppColors.border, width: _image != null ? 2 : 1),
              ),
              child: _image != null
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: Image.file(_image!, fit: BoxFit.cover, width: double.infinity))
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.15), shape: BoxShape.circle),
                          child: const Icon(Icons.cloud_upload_outlined, color: AppColors.primaryLight, size: 32)),
                        const SizedBox(height: 12),
                        Text('Ketuk untuk upload foto', style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
                        Text('JPG, JPEG, PNG (maks. 5 MB)', style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textHint)),
                      ],
                    ),
            ),
          ),
          if (_image != null) ...[
            const SizedBox(height: 8),
            TextButton.icon(
              onPressed: () => setState(() => _image = null),
              icon: const Icon(Icons.delete_outline, color: AppColors.error, size: 16),
              label: Text('Hapus foto', style: GoogleFonts.outfit(color: AppColors.error, fontSize: 13)),
            ),
          ],
          const SizedBox(height: 28),

          // ── Submit ──────────────────────────────────────
          SizedBox(
            width: double.infinity, height: 56,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: _image != null ? AppColors.primaryGradient : const LinearGradient(colors: [AppColors.surfaceLight, AppColors.surfaceLight]),
                borderRadius: BorderRadius.circular(18),
                boxShadow: _image != null ? [BoxShadow(color: AppColors.primary.withOpacity(0.4), blurRadius: 16, offset: const Offset(0, 6))] : [],
              ),
              child: ElevatedButton(
                onPressed: (_uploading || _image == null) ? null : _upload,
                style: ElevatedButton.styleFrom(backgroundColor: Colors.transparent, shadowColor: Colors.transparent, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18))),
                child: _uploading
                    ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5))
                    : Text('Upload Bukti Pembayaran', style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
              ),
            ),
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final BookingModel booking;
  const _SummaryCard({required this.booking});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border)),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Ringkasan Booking #${booking.id}', style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        const SizedBox(height: 10),
        ...booking.details.map((d) => Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Row(children: [
            const Icon(Icons.directions_car_outlined, size: 14, color: AppColors.textSecondary),
            const SizedBox(width: 8),
            Expanded(child: Text(d.mobil?.merk ?? 'Mobil', style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textPrimary))),
            Text('${d.lamaSewa} hari', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
          ]),
        )),
        const Divider(height: 16, color: AppColors.border),
        _Row('Total',    booking.totalHargaRp   ?? Formatters.toRupiah(booking.totalHarga)),
        const SizedBox(height: 4),
        _Row('DP (50%)', booking.uangMukaRp      ?? Formatters.toRupiah(booking.uangMuka)),
      ],
    ),
  );
}

class _Row extends StatelessWidget {
  final String label, value;
  const _Row(this.label, this.value);
  @override
  Widget build(BuildContext context) => Row(
    mainAxisAlignment: MainAxisAlignment.spaceBetween,
    children: [
      Text(label, style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
      Text(value,  style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
    ],
  );
}

class _MetodeRadio extends StatelessWidget {
  final int value, group;
  final String label;
  final IconData icon;
  final void Function(int) onChanged;
  const _MetodeRadio({required this.value, required this.group, required this.label, required this.icon, required this.onChanged});
  @override
  Widget build(BuildContext context) => RadioListTile<int>(
    value: value, groupValue: group,
    onChanged: (v) => onChanged(v!),
    activeColor: AppColors.accent,
    title: Row(children: [
      Icon(icon, color: value == group ? AppColors.accent : AppColors.textSecondary, size: 20),
      const SizedBox(width: 10),
      Text(label, style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14, fontWeight: FontWeight.w600)),
    ]),
  );
}
