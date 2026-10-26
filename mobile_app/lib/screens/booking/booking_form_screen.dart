import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../providers/auth_provider.dart';
import '../../core/utils/profile_guard.dart';
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
  /// Harus sama dengan BookingService::MIN_LEAD_HOURS di server.
  static const int _minLeadHours = 3;

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
    );
    if (picked == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );

    if (time == null) return; // dibatalkan: jangan diam-diam memakai jam default

    final combined = DateTime(picked.year, picked.month, picked.day, time.hour, time.minute);

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

    await context.read<BookingProvider>().fetchEstimate([{
      'mobil_id':        _selectedMobil!.id,
      'tanggal_sewa':    _fmt(_tanggalSewa!),
      'tanggal_kembali': _fmt(_tanggalKembali!),
      'pakai_supir':     _pakaiSupir ? 1 : 0,
    }]);
    if (!mounted) return;

    setState(() {
      _estimating = false;
    });
    // pull estimate data from provider
    _est = context.read<BookingProvider>().estimateData;
    setState(() {});
  }

  String _fmt(DateTime dt) => DateFormat('yyyy-MM-dd HH:mm:ss').format(dt);

  Future<void> _submit() async {
    if (!await ensureProfileComplete(context) || !mounted) return;
    if (!_formKey.currentState!.validate()) return;
    if (_selectedMobil == null) { _showSnack('Pilih mobil terlebih dahulu'); return; }
    if (_tanggalSewa == null)   { _showSnack('Pilih tanggal sewa');           return; }
    if (_tanggalKembali == null){ _showSnack('Pilih tanggal kembali');         return; }
    if (_tanggalSewa!.isBefore(DateTime.now().add(const Duration(hours: _minLeadHours)))) {
      _showSnack('Tanggal sewa minimal $_minLeadHours jam dari sekarang'); return;
    }
    if (!_tanggalKembali!.isAfter(_tanggalSewa!)) { _showSnack('Tanggal kembali harus setelah tanggal sewa'); return; }
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
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Booking berhasil! Segera bayar DP.'),
        backgroundColor: AppColors.success,
      ));
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const BookingListScreen()), (r) => r.isFirst);
    } else {
      final prov = context.read<BookingProvider>();
      if (prov.errorCode == 'INCOMPLETE_PROFILE') {
        // Profil di server belum lengkap walau data lokal terlihat lengkap: sinkronkan lalu arahkan.
        await context.read<AuthProvider>().refreshMe();
        if (!mounted) return;
        await ensureProfileComplete(context);
        return;
      }
      _showSnack(prov.error);
    }
  }

  void _showSnack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg), backgroundColor: AppColors.error,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(
            children: [
              const ScreenHeader('Form Pemesanan'),
              Expanded(
                child: Form(
                  key: _formKey,
                  child: ListView(
                    padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                    children: [
                      // ── Mobil terpilih ────────────────────────
                      if (_selectedMobil != null) _SelectedCarCard(mobil: _selectedMobil!),
                      const SizedBox(height: 20),

                      // ── Jadwal ────────────────────────────────
                      const SectionLabel('Jadwal Sewa'),
                      Row(children: [
                        Expanded(child: _DatePicker(
                          label: 'Tanggal sewa',
                          value: _tanggalSewa,
                          onTap: () => _pickDate(true),
                          icon:  Icons.calendar_today_outlined,
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: _DatePicker(
                          label:   'Tanggal kembali',
                          value:   _tanggalKembali,
                          onTap:   () => _pickDate(false),
                          icon:    Icons.event_available_outlined,
                          enabled: _tanggalSewa != null,
                        )),
                      ]),
                      Padding(
                        padding: const EdgeInsets.only(top: 8, left: 4),
                        child: Text('Waktu sewa minimal $_minLeadHours jam dari sekarang.',
                            style: t(fontSize: 12, color: AppColors.muted)),
                      ),
                      const SizedBox(height: 20),

                      // ── Sopir ─────────────────────────────────
                      const SectionLabel('Opsi Sopir'),
                      SoftCard(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                        child: SwitchListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text('Pakai sopir', style: t(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
                          subtitle: Text(
                            '${rp(_pakaiSupir ? (_selectedMobil?.hargaAllIn ?? 0) : (_selectedMobil?.hargaSewa ?? 0))} / hari',
                            style: t(fontSize: 12, color: AppColors.muted),
                          ),
                          value: _pakaiSupir,
                          onChanged: (v) => setState(() { _pakaiSupir = v; _est = null; }),
                          activeThumbColor: Colors.white,
                          activeTrackColor: AppColors.primary,
                          inactiveThumbColor: Colors.white,
                          inactiveTrackColor: AppColors.hint,
                          trackOutlineColor: const WidgetStatePropertyAll(Colors.transparent),
                        ),
                      ),
                      const SizedBox(height: 20),

                      // ── Asal kota ─────────────────────────────
                      const SectionLabel('Asal Kota'),
                      Row(children: [
                        Expanded(child: ChoiceTile(
                          title: 'Yogyakarta', selected: _asalKota == 1,
                          onTap: () => setState(() { _asalKota = 1; _jaminan = 2; }),
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: ChoiceTile(
                          title: 'Luar Kota', selected: _asalKota == 2,
                          onTap: () => setState(() { _asalKota = 2; _jaminan = 1; }),
                        )),
                      ]),
                      if (_asalKota == 2) ...[
                        const SizedBox(height: 12),
                        TextFormField(
                          initialValue: _namaKota,
                          style: t(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.ink),
                          onChanged: (v) => _namaKota = v,
                          decoration: const InputDecoration(
                            hintText: 'Nama kota asal',
                            prefixIcon: Icon(Icons.location_city_outlined, size: 20, color: AppColors.hint),
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),

                      // ── Jaminan ───────────────────────────────
                      const SectionLabel('Jaminan'),
                      Row(children: [
                        Expanded(child: ChoiceTile(
                          title: 'KTP / Passport', selected: _jaminan == 1,
                          onTap: () => setState(() => _jaminan = 1),
                        )),
                        const SizedBox(width: 12),
                        Expanded(child: ChoiceTile(
                          title: 'KTP + Motor', selected: _jaminan == 2,
                          onTap: () => setState(() => _jaminan = 2),
                        )),
                      ]),
                      const SizedBox(height: 24),

                      // ── Estimasi ──────────────────────────────
                      if (_tanggalSewa != null && _tanggalKembali != null && _selectedMobil != null) ...[
                        SecondaryButton(
                          onPressed: _estimating ? null : _estimate,
                          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                            _estimating
                                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2.2))
                                : const Icon(Icons.calculate_outlined, size: 20, color: AppColors.primary),
                            const SizedBox(width: 10),
                            Text(_estimating ? 'Menghitung...' : 'Hitung Estimasi Harga',
                                style: t(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
                          ]),
                        ),
                        const SizedBox(height: 16),
                      ],
                      if (_est != null) _EstimateCard(estimate: _est!),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: BottomActionBar(
        child: Consumer<BookingProvider>(
          builder: (_, prov, __) => PrimaryButton(
            label: 'Konfirmasi Pemesanan',
            loading: prov.isLoading,
            onPressed: _submit,
          ),
        ),
      ),
    );
  }
}

// ── Sub-widgets ─────────────────────────────────────────────

class _SelectedCarCard extends StatelessWidget {
  final MobilModel mobil;
  const _SelectedCarCard({required this.mobil});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return SoftCard(
      padding: const EdgeInsets.all(12),
      child: Row(children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(14),
          child: SizedBox(
            width: 84, height: 64,
            child: mobil.primaryImage.isNotEmpty
                ? CachedNetworkImage(imageUrl: mobil.primaryImage, fit: BoxFit.cover,
                    placeholder: (_, __) => const FotoKosong(), errorWidget: (_, __, ___) => const FotoKosong())
                : const FotoKosong(),
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(mobil.masterMobil?.nama ?? mobil.merk, maxLines: 1, overflow: TextOverflow.ellipsis,
                style: t(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink)),
            const SizedBox(height: 2),
            Text(mobil.platNomor, style: t(fontSize: 12, color: AppColors.muted)),
            const SizedBox(height: 4),
            Text('${rp(mobil.hargaSewa)} / hari', style: t(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.ink)),
          ]),
        ),
      ]),
    );
  }
}

class _DatePicker extends StatelessWidget {
  final String label;
  final DateTime? value;
  final VoidCallback onTap;
  final IconData icon;
  final bool enabled;
  const _DatePicker({required this.label, required this.value, required this.onTap, required this.icon, this.enabled = true});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Semantics(
      button: true,
      enabled: enabled,
      label: label,
      child: GestureDetector(
        onTap: enabled ? onTap : null,
        child: Opacity(
          opacity: enabled ? 1 : 0.55,
          child: Container(
            constraints: const BoxConstraints(minHeight: 84),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: value != null ? AppColors.primary : AppColors.line, width: value != null ? 1.5 : 1),
            ),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Icon(icon, size: 15, color: AppColors.muted),
                const SizedBox(width: 6),
                Flexible(child: Text(label, style: t(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.muted))),
              ]),
              const SizedBox(height: 6),
              value != null
                  ? Text(DateFormat('dd MMM yyyy\nHH:mm').format(value!),
                      style: t(fontSize: 13, height: 1.3, fontWeight: FontWeight.w700, color: AppColors.ink))
                  : Text('Pilih tanggal', style: t(fontSize: 13, color: AppColors.hint)),
            ]),
          ),
        ),
      ),
    );
  }
}

class _EstimateCard extends StatelessWidget {
  final Map<String, dynamic> estimate;
  const _EstimateCard({required this.estimate});

  @override
  Widget build(BuildContext context) {
    final t     = GoogleFonts.plusJakartaSans;
    final total = estimate['total'] as int? ?? 0;
    final dp    = estimate['uang_muka'] as int? ?? 0;
    return SoftCard(
      color: AppColors.primarySoft,
      padding: const EdgeInsets.all(18),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          const Icon(Icons.receipt_long_outlined, color: AppColors.primary, size: 18),
          const SizedBox(width: 8),
          Text('Estimasi Biaya', style: t(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.primary)),
        ]),
        const SizedBox(height: 14),
        _EstRow('Total sewa', rp(total)),
        const SizedBox(height: 8),
        _EstRow('DP (50%)', rp(dp)),
        const Divider(height: 24, color: AppColors.line),
        _EstRow('Sisa pelunasan', rp(total - dp), bold: true),
      ]),
    );
  }
}

class _EstRow extends StatelessWidget {
  final String label, value;
  final bool bold;
  const _EstRow(this.label, this.value, {this.bold = false});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Text(label, style: t(fontSize: 13, color: AppColors.muted)),
      Text(value, style: t(fontSize: bold ? 15 : 13, fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: AppColors.ink)),
    ]);
  }
}
