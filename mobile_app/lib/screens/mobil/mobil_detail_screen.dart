import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/mobil_provider.dart';
import '../../providers/auth_provider.dart';
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

  void _onBookNow() {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
      return;
    }
    if (_mobil == null) return;
    Navigator.push(context, MaterialPageRoute(builder: (_) => BookingFormScreen(initialMobil: _mobil!)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: AppColors.accent))
          : _error.isNotEmpty
              ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                  const Icon(Icons.error_outline, color: AppColors.error, size: 56),
                  const SizedBox(height: 12),
                  Text(_error, style: GoogleFonts.outfit(color: AppColors.textSecondary)),
                  const SizedBox(height: 16),
                  ElevatedButton(onPressed: _load, child: const Text('Coba Lagi')),
                ]))
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final m      = _mobil!;
    final images = m.images.isNotEmpty ? m.images : (m.gambar != null ? [m.gambar!] : <String>[]);

    return CustomScrollView(
      slivers: [
        // ── Image SliverAppBar ──────────────────────────────
        SliverAppBar(
          expandedHeight: 280,
          pinned: true,
          backgroundColor: AppColors.surface,
          leading: IconButton(
            icon: Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Colors.black45, shape: BoxShape.circle),
              child: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18)),
            onPressed: () => Navigator.pop(context),
          ),
          flexibleSpace: FlexibleSpaceBar(
            background: Stack(
              children: [
                if (images.isNotEmpty)
                  PageView.builder(
                    itemCount: images.length,
                    onPageChanged: (i) => setState(() => _imgIdx = i),
                    itemBuilder: (_, i) => CachedNetworkImage(imageUrl: images[i], fit: BoxFit.cover,
                      placeholder: (_, __) => Container(color: AppColors.surfaceLight),
                      errorWidget: (_, __, ___) => Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, size: 80, color: AppColors.border))),
                  )
                else
                  Container(color: AppColors.surfaceLight, child: const Center(child: Icon(Icons.directions_car, size: 80, color: AppColors.border))),

                if (images.length > 1)
                  Positioned(bottom: 12, left: 0, right: 0,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(images.length, (i) => AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        margin: const EdgeInsets.symmetric(horizontal: 3),
                        width: _imgIdx == i ? 20 : 6, height: 6,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(3),
                          color: _imgIdx == i ? AppColors.accent : Colors.white38),
                      )),
                    )),
              ],
            ),
          ),
        ),

        // ── Detail Content ──────────────────────────────────
        SliverToBoxAdapter(
          child: Container(
            decoration: const BoxDecoration(color: AppColors.background),
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Title + Status
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(m.merk, style: GoogleFonts.outfit(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
                        if (m.masterMobil?.nama != null)
                          Text(m.masterMobil!.nama, style: GoogleFonts.outfit(fontSize: 14, color: AppColors.textSecondary)),
                      ],
                    )),
                    _StatusChip(status: m.status, label: m.statusText ?? 'Tersedia'),
                  ],
                ),
                const SizedBox(height: 20),

                // ── Specs Grid ─────────────────────────────
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border)),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          _SpecItem(icon: Icons.calendar_today_outlined, label: 'Tahun', value: m.tahun.toString()),
                          _SpecItem(icon: Icons.pin_outlined,            label: 'Plat Nomor', value: m.platNomor),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          _SpecItem(icon: Icons.category_outlined, label: 'Tipe', value: m.tipeNama ?? '-'),
                          _SpecItem(icon: Icons.person_outline,    label: 'Dengan Supir', value: 'Tersedia'),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // ── Pricing ────────────────────────────────
                Text('Harga Sewa', style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(child: _PriceCard(label: 'Tanpa Supir', price: m.hargaSewa, color: AppColors.primaryLight)),
                    const SizedBox(width: 12),
                    Expanded(child: _PriceCard(label: 'Dengan Supir', price: m.hargaAllIn, color: AppColors.accent)),
                  ],
                ),
                const SizedBox(height: 28),

                // ── CTA ────────────────────────────────────
                if (m.isAvailable)
                  SizedBox(
                    width: double.infinity, height: 56,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: AppColors.primaryGradient,
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: [BoxShadow(color: AppColors.primary.withOpacity(0.4), blurRadius: 16, offset: const Offset(0, 6))],
                      ),
                      child: ElevatedButton.icon(
                        onPressed: _onBookNow,
                        icon: const Icon(Icons.bookmark_add_rounded, color: Colors.white),
                        label: Text('Pesan Sekarang', style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.transparent, shadowColor: Colors.transparent, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18))),
                      ),
                    ),
                  )
                else
                  Container(
                    width: double.infinity, height: 56,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(color: AppColors.surfaceLight, borderRadius: BorderRadius.circular(18)),
                    child: Text('Mobil Sedang Tidak Tersedia', style: GoogleFonts.outfit(fontSize: 14, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
                  ),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  final int status;
  final String label;
  const _StatusChip({required this.status, required this.label});
  @override
  Widget build(BuildContext context) {
    final color = status == 1 ? AppColors.success : (status == 2 ? AppColors.warning : AppColors.error);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(color: color.withOpacity(0.15), borderRadius: BorderRadius.circular(20), border: Border.all(color: color.withOpacity(0.5))),
      child: Text(label, style: GoogleFonts.outfit(fontSize: 11, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

class _SpecItem extends StatelessWidget {
  final IconData icon;
  final String label, value;
  const _SpecItem({required this.icon, required this.label, required this.value});
  @override
  Widget build(BuildContext context) => Expanded(
    child: Row(
      children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.15), borderRadius: BorderRadius.circular(10)),
          child: Icon(icon, color: AppColors.primaryLight, size: 18)),
        const SizedBox(width: 10),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
            Text(value,  style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
          ],
        ),
      ],
    ),
  );
}

class _PriceCard extends StatelessWidget {
  final String label;
  final int price;
  final Color color;
  const _PriceCard({required this.label, required this.price, required this.color});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(14), border: Border.all(color: color.withOpacity(0.3))),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textSecondary)),
        const SizedBox(height: 4),
        Text(Formatters.toRupiah(price), style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w800, color: color)),
        Text('/hari', style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textHint)),
      ],
    ),
  );
}
