import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/mobil_provider.dart';
import '../../providers/auth_provider.dart';
import '../../core/utils/profile_guard.dart';
import '../../models/mobil_model.dart';
import '../booking/booking_form_screen.dart';
import '../auth/login_screen.dart';

class MobilDetailScreen extends StatefulWidget {
  final int mobilId;
  const MobilDetailScreen({super.key, required this.mobilId});
  @override
  State<MobilDetailScreen> createState() => _MobilDetailScreenState();
}

class _MobilDetailScreenState extends State<MobilDetailScreen> {
  MobilModel? _mobil;
  bool        _loading = true;
  String      _error   = '';
  int         _imgIdx  = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = ''; });
    final m = await context.read<MobilProvider>().getMobilDetail(widget.mobilId);
    if (!mounted) return;
    setState(() {
      _mobil   = m;
      _loading = false;
      if (m == null) _error = 'Gagal memuat data mobil.';
    });
  }

  Future<void> _onBookNow() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
      return;
    }
    if (_mobil == null) return;
    if (!await ensureProfileComplete(context) || !mounted) return;
    Navigator.push(context, MaterialPageRoute(builder: (_) => BookingFormScreen(initialMobil: _mobil!)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error.isNotEmpty
              ? SafeArea(
                  child: Column(children: [
                    const Align(alignment: Alignment.centerLeft, child: Padding(padding: EdgeInsets.only(left: 12, top: 8), child: BackCircleButton())),
                    Expanded(child: ErrorState(message: _error, onRetry: _load)),
                  ]),
                )
              : _buildContent(),
      bottomNavigationBar: (!_loading && _error.isEmpty && _mobil != null) ? _BarPesan(mobil: _mobil!, onPesan: _onBookNow) : null,
    );
  }

  Widget _buildContent() {
    final m      = _mobil!;
    final images = m.images.isNotEmpty ? m.images : (m.gambar != null && m.gambar!.isNotEmpty ? [m.gambar!] : <String>[]);
    final gutter = screenGutter(context);
    final t      = GoogleFonts.plusJakartaSans;
    final nama   = m.masterMobil?.nama ?? m.merk;
    final top    = MediaQuery.paddingOf(context).top;
    final tinggiFoto = MediaQuery.sizeOf(context).width >= 600 ? 380.0 : 320.0;

    final status = m.status == 1
        ? (AppColors.success, AppColors.successSoft)
        : (m.status == 2 ? (AppColors.warning, AppColors.warningSoft) : (AppColors.error, AppColors.errorSoft));

    return SingleChildScrollView(
      child: ContentWidth(
        max: 720,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Foto penuh + tombol kembali ───────────────
            SizedBox(
              height: tinggiFoto,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (images.isNotEmpty)
                    PageView.builder(
                      itemCount: images.length,
                      onPageChanged: (i) => setState(() => _imgIdx = i),
                      itemBuilder: (_, i) => CachedNetworkImage(
                        imageUrl: images[i],
                        fit: BoxFit.cover,
                        placeholder: (_, __) => const FotoKosong(),
                        errorWidget: (_, __, ___) => const FotoKosong(),
                      ),
                    )
                  else
                    const FotoKosong(),
                  Positioned(top: top + 8, left: 12, child: const BackCircleButton()),
                  // Tutup melengkung: sheet konten naik menimpa bawah foto.
                  Positioned(
                    left: 0, right: 0, bottom: -1,
                    child: Container(
                      height: 28,
                      decoration: const BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
                    ),
                  ),
                  if (images.length > 1)
                    Positioned(
                      top: top + 14, right: 14,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(14)),
                        child: Text('${_imgIdx + 1}/${images.length}', style: t(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white)),
                      ),
                    ),
                  if (images.length > 1)
                    Positioned(
                      bottom: 40, left: 0, right: 0,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(images.length, (i) => AnimatedContainer(
                          duration: const Duration(milliseconds: 250),
                          margin: const EdgeInsets.symmetric(horizontal: 3),
                          width: _imgIdx == i ? 20 : 6,
                          height: 6,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(3),
                            color: _imgIdx == i ? Colors.white : Colors.white54,
                          ),
                        )),
                      ),
                    ),
                ],
              ),
            ),

            Padding(
              padding: EdgeInsets.fromLTRB(gutter + 4, 0, gutter + 4, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── Judul + status ──────────────────────
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(nama, style: t(fontSize: 26, height: 1.15, fontWeight: FontWeight.w800, color: AppColors.ink)),
                            const SizedBox(height: 4),
                            Text([m.merk, if (m.tipeNama != null) m.tipeNama!].join(' · '),
                                style: t(fontSize: 13.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      SoftBadge(label: m.statusText ?? (m.isAvailable ? 'Tersedia' : 'Tidak tersedia'), color: status.$1, background: status.$2),
                    ],
                  ),
                  const SizedBox(height: 22),

                  // ── Spesifikasi ─────────────────────────
                  Text('Spesifikasi', style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                  const SizedBox(height: 12),
                  LayoutBuilder(builder: (_, c) {
                    const gap = 10.0;
                    final w = (c.maxWidth - gap * 2) / 3;
                    return Wrap(spacing: gap, runSpacing: gap, children: [
                      _Spek(w: w, icon: Icons.calendar_today_outlined, label: 'Tahun', value: m.tahun > 0 ? '${m.tahun}' : '-'),
                      _Spek(w: w, icon: Icons.category_outlined, label: 'Tipe', value: m.tipeNama ?? '-'),
                      _Spek(w: w, icon: Icons.pin_outlined, label: 'Plat nomor', value: m.platNomor.isEmpty ? '-' : m.platNomor),
                    ]);
                  }),
                  const SizedBox(height: 24),

                  // ── Harga ───────────────────────────────
                  Text('Harga sewa', style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 4),
                    decoration: BoxDecoration(
                      border: Border.all(color: AppColors.line),
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: Column(children: [
                      _BarisHarga(label: 'Tanpa sopir', price: m.hargaSewa),
                      const Divider(height: 1),
                      _BarisHarga(label: 'Dengan sopir', price: m.hargaAllIn),
                    ]),
                  ),
                  const SizedBox(height: 10),
                  Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Icon(Icons.info_outline_rounded, size: 16, color: AppColors.muted),
                    const SizedBox(width: 8),
                    Expanded(child: Text('Pilih opsi sopir saat mengisi form pemesanan.', style: t(fontSize: 12, height: 1.4, color: AppColors.muted))),
                  ]),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Spek extends StatelessWidget {
  final double w;
  final IconData icon;
  final String label, value;
  const _Spek({required this.w, required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Container(
      width: w,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
      decoration: BoxDecoration(color: AppColors.surfaceAlt, borderRadius: BorderRadius.circular(16)),
      child: Column(children: [
        Icon(icon, size: 22, color: AppColors.ink),
        const SizedBox(height: 8),
        Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center,
            style: t(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.ink)),
        const SizedBox(height: 2),
        Text(label, maxLines: 1, overflow: TextOverflow.ellipsis,
            style: t(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.muted)),
      ]),
    );
  }
}

class _BarisHarga extends StatelessWidget {
  final String label;
  final int price;
  const _BarisHarga({required this.label, required this.price});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 14),
      child: Row(children: [
        Expanded(child: Text(label, style: t(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.muted))),
        Text.rich(TextSpan(children: [
          TextSpan(text: rp(price), style: t(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.ink)),
          TextSpan(text: ' / hari', style: t(fontSize: 12, fontWeight: FontWeight.w500, color: AppColors.muted)),
        ])),
      ]),
    );
  }
}

/// Bar bawah: harga mulai + tombol pesan.
class _BarPesan extends StatelessWidget {
  final MobilModel mobil;
  final VoidCallback onPesan;
  const _BarPesan({required this.mobil, required this.onPesan});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        boxShadow: AppColors.floatShadow,
      ),
      child: SafeArea(
        top: false,
        child: ContentWidth(
          max: 720,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 16),
            child: Row(children: [
              Expanded(
                flex: 5,
                child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Mulai dari', style: t(fontSize: 11.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text.rich(
                      TextSpan(children: [
                        TextSpan(text: rp(mobil.hargaSewa), style: t(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.ink)),
                        TextSpan(text: ' / hari', style: t(fontSize: 11.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
                      ]),
                      maxLines: 1,
                    ),
                  ),
                ]),
              ),
              const SizedBox(width: 16),
              Expanded(
                flex: 6,
                child: mobil.isAvailable
                    ? PrimaryButton(label: 'Pesan Sekarang', onPressed: onPesan)
                    : Container(
                        height: 54,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(color: AppColors.surfaceAlt, borderRadius: BorderRadius.circular(27)),
                        child: Text('Tidak tersedia', style: t(fontSize: 13.5, fontWeight: FontWeight.w700, color: AppColors.muted)),
                      ),
              ),
            ]),
          ),
        ),
      ),
    );
  }
}
