import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/auth_provider.dart';
import '../../providers/booking_provider.dart';
import '../../models/mobil_model.dart';
import '../booking/booking_list_screen.dart';

class BookingFormScreen extends StatefulWidget {
  final MobilModel? initialMobil;
  const BookingFormScreen({super.key, this.initialMobil});
  @override
  State<BookingFormScreen> createState() => _BookingFormScreenState();
}

class _BookingFormScreenState extends State<BookingFormScreen> {
  final _formKey = GlobalKey<FormState>();

  // Selected car + options
  MobilModel? _selectedMobil;
  bool        _pakaiSupir   = false;
  DateTime?   _tanggalSewa;
  DateTime?   _tanggalKembali;

  // Booking meta
  int    _asalKota  = 1; // 1=Yogyakarta, 2=Luar Kota
  String _namaKota  = '';
  int    _jaminan   = 1; // 1=KTP/Passport, 2=KTP+Motor

  // Estimate result
  Map<String, dynamic>? _est;
  bool _estimating = false;

  @override
  void initState() {
    super.initState();
    _selectedMobil = widget.initialMobil;

    // Pre-fill asal_kota from user profile
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final user = context.read<AuthProvider>().user;
      if (user != null) {
        final kota = (user.asalKota ?? '').toLowerCase();
        setState(() {
          if (kota == 'yogyakarta') {
            _asalKota = 1;
            _jaminan  = 2;
          } else {
            _asalKota = 2;
            _namaKota = user.asalKota ?? '';
            _jaminan  = 1;
          }
        });
      }
    });
  }

  Future<void> _pickDate(bool isSewa) async {
    final now   = DateTime.now();
    final first = isSewa ? now : (_tanggalSewa ?? now);
    final picked = await showDatePicker(
      context: context,
      initialDate: first,
      firstDate:   first,
      lastDate:    now.add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.dark(primary: AppColors.accent, surface: AppColors.surface, onSurface: AppColors.textPrimary),
          dialogBackgroundColor: AppColors.surface,
        ),
        child: child!,
      ),
    );
    if (picked == null) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.dark(primary: AppColors.accent, surface: AppColors.surface, onSurface: AppColors.textPrimary),
          dialogBackgroundColor: AppColors.surface,
        ),
        child: child!,
      ),
    );

    final combined = DateTime(picked.year, picked.month, picked.day, time?.hour ?? 8, time?.minute ?? 0);

    setState(() {
      if (isSewa) {
        _tanggalSewa   = combined;
        _tanggalKembali = null;
        _est = null;
      } else {
        _tanggalKembali = combined;
        _est = null;
      }
    });
  }

  Future<void> _estimate() async {
    if (_selectedMobil == null || _tanggalSewa == null || _tanggalKembali == null) return;
    setState(() => _estimating = true);

    final res = await context.read<BookingProvider>().fetchEstimate([{
      'mobil_id':        _selectedMobil!.id,
      'tanggal_sewa':    _fmt(_tanggalSewa!),
      'tanggal_kembali': _fmt(_tanggalKembali!),
      'pakai_supir':     _pakaiSupir ? 1 : 0,
    }]);

    setState(() {
      _estimating = false;
    });
    // pull estimate data from provider
    _est = context.read<BookingProvider>().estimateData;
    setState(() {});
  }

  String _fmt(DateTime dt) => DateFormat('yyyy-MM-dd HH:mm:ss').format(dt);

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedMobil == null) { _showSnack('Pilih mobil terlebih dahulu'); return; }
    if (_tanggalSewa == null)   { _showSnack('Pilih tanggal sewa');           return; }
    if (_tanggalKembali == null){ _showSnack('Pilih tanggal kembali');         return; }
    if (_tanggalKembali!.isBefore(_tanggalSewa!)) { _showSnack('Tanggal kembali harus setelah tanggal sewa'); return; }
    if (_asalKota == 2 && _namaKota.isEmpty) { _showSnack('Masukkan nama kota asal'); return; }

    final payload = {
      'asal_kota': _asalKota,
      'nama_kota': _asalKota == 2 ? _namaKota : null,
      'jaminan':   _jaminan,
      'mobils': [{
        'mobil_id':        _selectedMobil!.id,
        'tanggal_sewa':    _fmt(_tanggalSewa!),
        'tanggal_kembali': _fmt(_tanggalKembali!),
        'pakai_supir':     _pakaiSupir ? 1 : 0,
      }],
    };

    final booking = await context.read<BookingProvider>().createBooking(payload);
    if (!mounted) return;

    if (booking != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('Booking berhasil! Segera bayar DP.'),
        backgroundColor: AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ));
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const BookingListScreen()), (r) => r.isFirst);
    } else {
      _showSnack(context.read<BookingProvider>().error);
    }
  }

  void _showSnack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg), backgroundColor: AppColors.error,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: AppColors.textPrimary), onPressed: () => Navigator.pop(context)),
        title: Text('Form Pemesanan', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            // ── Selected Car ────────────────────────────────
            if (_selectedMobil != null) _SelectedCarCard(mobil: _selectedMobil!),
            const SizedBox(height: 20),

            // ── Date pickers ────────────────────────────────
            _SectionTitle('Jadwal Sewa'),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: _DatePicker(
                  label:    'Tanggal Sewa',
                  value:    _tanggalSewa,
                  onTap:    () => _pickDate(true),
                  icon:     Icons.calendar_today_outlined,
                )),
                const SizedBox(width: 12),
                Expanded(child: _DatePicker(
                  label:    'Tanggal Kembali',
                  value:    _tanggalKembali,
                  onTap:    () => _pickDate(false),
                  icon:     Icons.event_available_outlined,
                  enabled:  _tanggalSewa != null,
                )),
              ],
            ),
            const SizedBox(height: 20),

            // ── With Driver toggle ──────────────────────────
            _SectionTitle('Opsi Supir'),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
              child: SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text('Pakai Supir', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14, fontWeight: FontWeight.w600)),
                subtitle: Text(
                  _pakaiSupir
                      ? 'Harga: ${Formatters.toRupiah(_selectedMobil?.hargaAllIn ?? 0)}/hari'
                      : 'Harga: ${Formatters.toRupiah(_selectedMobil?.hargaSewa ?? 0)}/hari',
                  style: GoogleFonts.outfit(color: AppColors.textSecondary, fontSize: 12),
                ),
                value: _pakaiSupir,
                onChanged: (v) => setState(() { _pakaiSupir = v; _est = null; }),
                activeColor: AppColors.accent,
              ),
            ),
            const SizedBox(height: 20),

            // ── Origin city ─────────────────────────────────
            _SectionTitle('Asal Kota'),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
              child: Column(
                children: [
                  RadioListTile<int>(
                    title: Text('Yogyakarta', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14)),
                    value: 1, groupValue: _asalKota,
                    onChanged: (v) => setState(() { _asalKota = v!; _jaminan = 2; }),
                    activeColor: AppColors.accent,
                  ),
                  const Divider(height: 1, color: AppColors.border),
                  RadioListTile<int>(
                    title: Text('Luar Kota', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14)),
                    value: 2, groupValue: _asalKota,
                    onChanged: (v) => setState(() { _asalKota = v!; _jaminan = 1; }),
                    activeColor: AppColors.accent,
                  ),
                  if (_asalKota == 2)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                      child: TextFormField(
                        initialValue: _namaKota,
                        style: GoogleFonts.outfit(color: AppColors.textPrimary),
                        onChanged: (v) => _namaKota = v,
                        decoration: InputDecoration(
                          hintText: 'Nama kota asal',
                          hintStyle: GoogleFonts.outfit(color: AppColors.textHint),
                          filled: true, fillColor: AppColors.surfaceLight,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // ── Collateral ──────────────────────────────────
            _SectionTitle('Jaminan'),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.border)),
              child: Column(
                children: [
                  RadioListTile<int>(
                    title: Text('KTP / Passport', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14)),
                    value: 1, groupValue: _jaminan,
                    onChanged: (v) => setState(() => _jaminan = v!),
                    activeColor: AppColors.accent,
                  ),
                  const Divider(height: 1, color: AppColors.border),
                  RadioListTile<int>(
                    title: Text('KTP + Motor', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14)),
                    value: 2, groupValue: _jaminan,
                    onChanged: (v) => setState(() => _jaminan = v!),
                    activeColor: AppColors.accent,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // ── Estimate Button ─────────────────────────────
            if (_tanggalSewa != null && _tanggalKembali != null && _selectedMobil != null) ...[
              OutlinedButton.icon(
                onPressed: _estimating ? null : _estimate,
                icon: _estimating ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.accent)) : const Icon(Icons.calculate_outlined, color: AppColors.accent),
                label: Text(_estimating ? 'Menghitung...' : 'Hitung Estimasi Harga', style: GoogleFonts.outfit(color: AppColors.accent, fontWeight: FontWeight.w600)),
                style: OutlinedButton.styleFrom(side: const BorderSide(color: AppColors.accent), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)), padding: const EdgeInsets.symmetric(vertical: 14)),
              ),
              const SizedBox(height: 16),
            ],

            // ── Estimate Result ─────────────────────────────
            if (_est != null) _EstimateCard(estimate: _est!),
            if (_est != null) const SizedBox(height: 24),

            // ── Submit ──────────────────────────────────────
            Consumer<BookingProvider>(
              builder: (_, prov, __) => SizedBox(
                width: double.infinity, height: 56,
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [BoxShadow(color: AppColors.primary.withOpacity(0.4), blurRadius: 16, offset: const Offset(0, 6))],
                  ),
                  child: ElevatedButton(
                    onPressed: prov.isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.transparent, shadowColor: Colors.transparent, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18))),
                    child: prov.isLoading
                        ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5))
                        : Text('Konfirmasi Pemesanan', style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }
}

// ── Sub-widgets ─────────────────────────────────────────────

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);
  @override
  Widget build(BuildContext context) => Text(title, style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary));
}

class _SelectedCarCard extends StatelessWidget {
  final MobilModel mobil;
  const _SelectedCarCard({required this.mobil});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary.withOpacity(0.2), AppColors.primaryLight.withOpacity(0.1)]), borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.primaryLight.withOpacity(0.4))),
    child: Row(
      children: [
        Container(width: 56, height: 56, decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.3), borderRadius: BorderRadius.circular(12)), child: const Icon(Icons.directions_car_rounded, color: AppColors.accent, size: 28)),
        const SizedBox(width: 14),
        Expanded(child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(mobil.merk, style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
            Text(mobil.platNomor, style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
            Text(Formatters.toRupiah(mobil.hargaSewa) + '/hari', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.accent, fontWeight: FontWeight.w600)),
          ],
        )),
        const Icon(Icons.check_circle_rounded, color: AppColors.success, size: 24),
      ],
    ),
  );
}

class _DatePicker extends StatelessWidget {
  final String label;
  final DateTime? value;
  final VoidCallback onTap;
  final IconData icon;
  final bool enabled;
  const _DatePicker({required this.label, required this.value, required this.onTap, required this.icon, this.enabled = true});

  @override
  Widget build(BuildContext context) => GestureDetector(
    onTap: enabled ? onTap : null,
    child: Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: enabled ? AppColors.surface : AppColors.surfaceLight,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: value != null ? AppColors.accent.withOpacity(0.5) : AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [Icon(icon, size: 14, color: AppColors.textSecondary), const SizedBox(width: 4), Text(label, style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary))]),
          const SizedBox(height: 4),
          value != null
              ? Text(DateFormat('dd MMM yyyy\nHH:mm').format(value!), style: GoogleFonts.outfit(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textPrimary))
              : Text('Pilih tanggal', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textHint)),
        ],
      ),
    ),
  );
}

class _EstimateCard extends StatelessWidget {
  final Map<String, dynamic> estimate;
  const _EstimateCard({required this.estimate});
  @override
  Widget build(BuildContext context) {
    final total    = estimate['total'] as int? ?? 0;
    final dp       = estimate['uang_muka'] as int? ?? 0;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.success.withOpacity(0.1), AppColors.success.withOpacity(0.05)]), borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.success.withOpacity(0.4))),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [const Icon(Icons.receipt_long_outlined, color: AppColors.success, size: 18), const SizedBox(width: 8), Text('Estimasi Biaya', style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.success))]),
          const SizedBox(height: 12),
          _EstRow('Total Sewa',    Formatters.toRupiah(total)),
          const SizedBox(height: 6),
          _EstRow('DP (50%)',      Formatters.toRupiah(dp)),
          const Divider(height: 16, color: AppColors.border),
          _EstRow('Sisa Pelunasan', Formatters.toRupiah(total - dp), bold: true),
        ],
      ),
    );
  }
}

class _EstRow extends StatelessWidget {
  final String label, value;
  final bool bold;
  const _EstRow(this.label, this.value, {this.bold = false});
  @override
  Widget build(BuildContext context) => Row(
    mainAxisAlignment: MainAxisAlignment.spaceBetween,
    children: [
      Text(label, style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary)),
      Text(value,  style: GoogleFonts.outfit(fontSize: 13, fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: bold ? AppColors.textPrimary : AppColors.textSecondary)),
    ],
  );
}
