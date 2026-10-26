import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/auth_provider.dart';
import '../../providers/mobil_provider.dart';
import '../../models/mobil_model.dart';
import '../mobil/mobil_list_screen.dart';
import '../mobil/mobil_detail_screen.dart';
import '../booking/booking_list_screen.dart';
import '../riwayat/riwayat_screen.dart';
import '../profile/profile_screen.dart';
import '../auth/login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _navIndex = 0;

  static const _items = [
    NavItem(Icons.home_outlined, Icons.home_rounded, 'Beranda'),
    NavItem(Icons.bookmark_outline_rounded, Icons.bookmark_rounded, 'Booking'),
    NavItem(Icons.history_rounded, Icons.history_rounded, 'Riwayat'),
    NavItem(Icons.person_outline_rounded, Icons.person_rounded, 'Profil'),
  ];

  @override
  Widget build(BuildContext context) {
    // Tamu hanya melihat katalog; tab lain minta login (juga agar tidak memanggil API yang butuh token).
    final masuk = context.select<AuthProvider, bool>((a) => a.isAuthenticated);
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: IndexedStack(index: _navIndex, children: [
        _HomeTab(onProfil: () => setState(() => _navIndex = 3)),
        masuk ? const BookingListScreen() : const _GerbangTamu(judul: 'Booking Anda'),
        masuk ? const RiwayatScreen() : const _GerbangTamu(judul: 'Riwayat sewa'),
        masuk ? const ProfileScreen() : const _GerbangTamu(judul: 'Profil'),
      ]),
      bottomNavigationBar: FloatingNavBar(
        items: _items,
        index: _navIndex,
        onTap: (i) => setState(() => _navIndex = i),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  Tab Beranda
// ─────────────────────────────────────────────────────────────
class _GerbangTamu extends StatelessWidget {
  final String judul;
  const _GerbangTamu({required this.judul});

  @override
  Widget build(BuildContext context) => SafeArea(
        child: Column(children: [
          Expanded(
            child: EmptyState(
              icon: Icons.lock_outline_rounded,
              title: '$judul perlu login',
              subtitle: 'Katalog bisa dilihat tanpa akun. Masuk untuk memesan dan memantau sewa.',
            ),
          ),
          Padding(
            padding: EdgeInsets.fromLTRB(screenGutter(context), 0, screenGutter(context), 120),
            child: ContentWidth(
              max: 360,
              child: PrimaryButton(
                label: 'Masuk',
                trailingIcon: Icons.arrow_forward_rounded,
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen())),
              ),
            ),
          ),
        ]),
      );
}

class _HomeTab extends StatefulWidget {
  final VoidCallback onProfil;
  const _HomeTab({required this.onProfil});
  @override
  State<_HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<_HomeTab> {
  String _tipe = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<MobilProvider>();
      p.loadTipes();
      p.applyFilter(search: '', type: _tipe);
    });
  }

  Future<void> _muat() => context.read<MobilProvider>().applyFilter(search: '', type: _tipe);

  void _pilihTipe(String tipe) {
    setState(() => _tipe = tipe);
    _muat();
  }

  /// Buka katalog; setelah kembali, daftar beranda disegarkan sesuai chip yang aktif.
  Future<void> _bukaKatalog() async {
    await Navigator.push(context, MaterialPageRoute(builder: (_) => MobilListScreen(initialType: _tipe)));
    if (mounted) _muat();
  }

  @override
  Widget build(BuildContext context) {
    final user   = context.select<AuthProvider, dynamic>((a) => a.user);
    final gutter = screenGutter(context);
    final nama   = ((user?.name as String?) ?? '').trim();
    final depan  = nama.isEmpty ? '' : nama.split(' ').first;
    final inisial = nama.isEmpty ? '?' : nama[0].toUpperCase();

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _muat,
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: ContentWidth(
                max: 920,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(gutter, 12, gutter, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _TopBar(inisial: inisial, depan: depan, onProfil: widget.onProfil),
                      const SizedBox(height: 18),
                      _SearchPill(onTap: _bukaKatalog),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: TipeChips(aktif: _tipe, onPilih: _pilihTipe),
            ),
            SliverToBoxAdapter(
              child: ContentWidth(
                max: 920,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(gutter, 20, gutter, 0),
                  child: const _BannerArmada(),
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: ContentWidth(
                max: 920,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(gutter, 24, gutter, 0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Mobil Tersedia',
                          style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.ink)),
                      TextButton(
                        onPressed: _bukaKatalog,
                        style: TextButton.styleFrom(minimumSize: const Size(48, 48), padding: const EdgeInsets.symmetric(horizontal: 8)),
                        child: Row(mainAxisSize: MainAxisSize.min, children: [
                          Text('Lihat semua', style: GoogleFonts.plusJakartaSans(fontSize: 13, fontWeight: FontWeight.w600)),
                          const Icon(Icons.chevron_right_rounded, size: 18),
                        ]),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: ContentWidth(
                max: 920,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(gutter, 4, gutter, 24),
                  child: _GridMobil(onCoba: _muat),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Bilah atas: avatar + sapaan + lokasi ─────────────────────
class _TopBar extends StatelessWidget {
  final String inisial;
  final String depan;
  final VoidCallback onProfil;
  const _TopBar({required this.inisial, required this.depan, required this.onProfil});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Row(
      children: [
        Semantics(
          button: true,
          label: 'Buka profil',
          child: InkResponse(
            onTap: onProfil,
            radius: 28,
            child: Container(
              width: 46, height: 46,
              alignment: Alignment.center,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.surfaceAlt),
              child: inisial == '?'
                  ? const Icon(Icons.person_outline_rounded, size: 22, color: AppColors.ink)
                  : Text(inisial, style: t(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.ink)),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Selamat datang', style: t(fontSize: 12, fontWeight: FontWeight.w500, color: AppColors.muted)),
            Text(depan.isEmpty ? 'Harkat Rental' : depan,
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: t(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.ink)),
          ]),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(color: AppColors.surfaceAlt, borderRadius: BorderRadius.circular(20)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.location_on_outlined, size: 16, color: AppColors.ink),
            const SizedBox(width: 4),
            Text('Yogyakarta', style: t(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.ink)),
          ]),
        ),
      ],
    );
  }
}

// ── Pencarian (membuka katalog) ──────────────────────────────
class _SearchPill extends StatelessWidget {
  final VoidCallback onTap;
  const _SearchPill({required this.onTap});

  @override
  Widget build(BuildContext context) => Semantics(
        button: true,
        label: 'Cari mobil',
        child: GestureDetector(
          onTap: onTap,
          child: Container(
            height: 52,
            padding: const EdgeInsets.only(left: 16, right: 8),
            decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.line)),
            child: Row(children: [
              const Icon(Icons.search_rounded, color: AppColors.muted, size: 21),
              const SizedBox(width: 10),
              Expanded(
                child: Text('Cari mobil, merek, atau tipe…',
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.plusJakartaSans(fontSize: 13.5, fontWeight: FontWeight.w500, color: AppColors.muted)),
              ),
              Container(
                width: 38, height: 38,
                decoration: BoxDecoration(color: AppColors.surfaceAlt, borderRadius: BorderRadius.circular(12)),
                child: const Icon(Icons.tune_rounded, color: AppColors.ink, size: 19),
              ),
            ]),
          ),
        ),
      );
}

// ── Banner: armada pilihan dari data nyata ───────────────────
class _BannerArmada extends StatefulWidget {
  const _BannerArmada();
  @override
  State<_BannerArmada> createState() => _BannerArmadaState();
}

class _BannerArmadaState extends State<_BannerArmada> {
  int _halaman = 0;

  @override
  Widget build(BuildContext context) {
    final semua = context.select<MobilProvider, List<MobilModel>>((p) => p.mobils);
    final pilihan = semua.where((m) => m.primaryImage.isNotEmpty).take(3).toList();
    if (pilihan.isEmpty) return const SizedBox.shrink();
    final tinggi = MediaQuery.sizeOf(context).height < 560 ? 140.0 : 168.0;

    return SizedBox(
      height: tinggi,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          children: [
            PageView.builder(
              itemCount: pilihan.length,
              onPageChanged: (i) => setState(() => _halaman = i),
              itemBuilder: (_, i) => _SlideBanner(mobil: pilihan[i]),
            ),
            if (pilihan.length > 1)
              Positioned(
                right: 18, bottom: 14,
                child: Row(children: [
                  for (var i = 0; i < pilihan.length; i++)
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      margin: const EdgeInsets.only(left: 4),
                      width: i == _halaman ? 16 : 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: i == _halaman ? Colors.white : Colors.white54,
                        borderRadius: BorderRadius.circular(3),
                      ),
                    ),
                ]),
              ),
          ],
        ),
      ),
    );
  }
}

class _SlideBanner extends StatelessWidget {
  final MobilModel mobil;
  const _SlideBanner({required this.mobil});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    final lebar = MediaQuery.sizeOf(context).width;
    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MobilDetailScreen(mobilId: mobil.id))),
      child: ColoredBox(
        color: AppColors.primary,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Positioned(
              right: 0, top: 0, bottom: 0,
              width: lebar * 0.5,
              child: CachedNetworkImage(
                imageUrl: mobil.primaryImage,
                fit: BoxFit.cover,
                memCacheWidth: 600,
                placeholder: (_, __) => const SizedBox.shrink(),
                errorWidget: (_, __, ___) => const SizedBox.shrink(),
              ),
            ),
            // Scrim: menyatukan sisi kiri foto dengan latar gelap agar teks tetap terbaca.
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  stops: [0.45, 0.72],
                  colors: [AppColors.primary, Color(0x00111111)],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('ARMADA PILIHAN', style: t(fontSize: 10, letterSpacing: 1.4, fontWeight: FontWeight.w700, color: Colors.white70)),
                  const SizedBox(height: 6),
                  SizedBox(
                    width: lebar * 0.42,
                    child: Text(mobil.masterMobil?.nama ?? mobil.merk,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: t(fontSize: 19, height: 1.15, fontWeight: FontWeight.w800, color: Colors.white)),
                  ),
                  const SizedBox(height: 4),
                  Text('Mulai ${rp(mobil.hargaSewa)} / hari',
                      style: t(fontSize: 12, fontWeight: FontWeight.w500, color: Colors.white70)),
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
                    child: Row(mainAxisSize: MainAxisSize.min, children: [
                      Text('Lihat Detail', style: t(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.ink)),
                      const SizedBox(width: 4),
                      const Icon(Icons.arrow_forward_rounded, size: 14, color: AppColors.ink),
                    ]),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Grid kartu mobil (kolom mengikuti lebar) ─────────────────
class _GridMobil extends StatelessWidget {
  final VoidCallback onCoba;
  const _GridMobil({required this.onCoba});

  @override
  Widget build(BuildContext context) {
    return Consumer<MobilProvider>(builder: (_, p, __) {
      if (p.isLoading && p.mobils.isEmpty) return const _GridSkeleton();
      if (p.state == MobilLoadState.error && p.mobils.isEmpty) {
        return ErrorState(message: p.error, onRetry: onCoba);
      }
      if (p.mobils.isEmpty) {
        return const Padding(
          padding: EdgeInsets.symmetric(vertical: 24),
          child: EmptyState(icon: Icons.directions_car_outlined, title: 'Belum ada mobil', subtitle: 'Coba pilih tipe lain.'),
        );
      }
      final items = p.mobils.take(8).toList();
      return LayoutBuilder(builder: (_, c) {
        const gap = 12.0;
        final k = c.maxWidth < 600 ? 1 : 2;
        final w = (c.maxWidth - gap * (k - 1)) / k;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [for (final m in items) SizedBox(width: w, child: MobilCardH(mobil: m))],
        );
      });
    });
  }
}

class _GridSkeleton extends StatelessWidget {
  const _GridSkeleton();

  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
        baseColor: AppColors.surfaceAlt,
        highlightColor: AppColors.bg,
        child: Column(children: [
          for (var i = 0; i < 3; i++)
            Container(
              height: 102,
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
            ),
        ]),
      );
}
