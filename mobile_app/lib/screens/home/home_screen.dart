import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/auth_provider.dart';
import '../../providers/mobil_provider.dart';
import '../../providers/booking_provider.dart';
import '../../models/mobil_model.dart';
import '../auth/login_screen.dart';
import '../mobil/mobil_list_screen.dart';
import '../mobil/mobil_detail_screen.dart';
import '../booking/booking_list_screen.dart';
import '../riwayat/riwayat_screen.dart';
import '../profile/profile_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _navIndex = 0;

  final List<Widget> _pages = const [
    _HomeTab(),
    BookingListScreen(),
    RiwayatScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: IndexedStack(index: _navIndex, children: _pages),
      bottomNavigationBar: _buildNavBar(),
    );
  }

  Widget _buildNavBar() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: const Border(top: BorderSide(color: AppColors.border, width: 0.5)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.3), blurRadius: 20, offset: const Offset(0, -4))],
      ),
      child: NavigationBar(
        selectedIndex: _navIndex,
        onDestinationSelected: (i) => setState(() => _navIndex = i),
        backgroundColor: Colors.transparent,
        indicatorColor: AppColors.primary.withOpacity(0.3),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded, color: AppColors.accent), label: 'Beranda'),
          NavigationDestination(icon: Icon(Icons.bookmark_outline), selectedIcon: Icon(Icons.bookmark_rounded, color: AppColors.accent), label: 'Booking'),
          NavigationDestination(icon: Icon(Icons.history_outlined), selectedIcon: Icon(Icons.history_rounded, color: AppColors.accent), label: 'Riwayat'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person_rounded, color: AppColors.accent), label: 'Profil'),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  Home Tab Content
// ─────────────────────────────────────────────────────────────
class _HomeTab extends StatefulWidget {
  const _HomeTab();
  @override
  State<_HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<_HomeTab> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MobilProvider>().loadMobils(reset: true);
      context.read<MobilProvider>().loadTipes();
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    return SafeArea(
      child: CustomScrollView(
        slivers: [
          // ── App Bar / Hero ──────────────────────────────────
          SliverToBoxAdapter(child: _HeroHeader(userName: user?.name ?? 'Pengguna')),

          // ── Promo Banner ────────────────────────────────────
          SliverToBoxAdapter(child: _PromoBanner()),

          // ── Quick Stats ─────────────────────────────────────
          SliverToBoxAdapter(child: _QuickStats()),

          // ── Section Title ───────────────────────────────────
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Mobil Tersedia', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                  GestureDetector(
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MobilListScreen())),
                    child: Text('Lihat Semua', style: GoogleFonts.outfit(fontSize: 13, color: AppColors.accent, fontWeight: FontWeight.w600)),
                  ),
                ],
              ),
            ),
          ),

          // ── Car Grid ────────────────────────────────────────
          const SliverToBoxAdapter(child: _CarGrid()),
          const SliverToBoxAdapter(child: SizedBox(height: 24)),
        ],
      ),
    );
  }
}

class _HeroHeader extends StatelessWidget {
  final String userName;
  const _HeroHeader({required this.userName});

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
    decoration: const BoxDecoration(gradient: AppColors.heroGradient),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Halo, ${userName.split(' ').first}! 👋',
                      style: GoogleFonts.outfit(fontSize: 22, fontWeight: FontWeight.w800, color: Colors.white)),
                  const SizedBox(height: 4),
                  Text('Mau sewa mobil hari ini?',
                      style: GoogleFonts.outfit(fontSize: 13, color: Colors.white70)),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: Colors.white12, borderRadius: BorderRadius.circular(14)),
              child: const Icon(Icons.directions_car_rounded, color: AppColors.accent, size: 28),
            ),
          ],
        ),
        const SizedBox(height: 20),
        // Search bar
        GestureDetector(
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MobilListScreen())),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(color: Colors.white12, borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white24)),
            child: Row(
              children: [
                const Icon(Icons.search_rounded, color: Colors.white54, size: 20),
                const SizedBox(width: 10),
                Text('Cari mobil impianmu...', style: GoogleFonts.outfit(color: Colors.white54, fontSize: 14)),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}

class _PromoBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.fromLTRB(20, 20, 20, 0),
    height: 140,
    decoration: BoxDecoration(
      gradient: const LinearGradient(colors: [Color(0xFFD97706), Color(0xFFF59E0B), Color(0xFFFCD34D)]),
      borderRadius: BorderRadius.circular(20),
      boxShadow: [BoxShadow(color: AppColors.accent.withOpacity(0.3), blurRadius: 20, offset: const Offset(0, 8))],
    ),
    child: Stack(
      children: [
        Positioned(right: -10, bottom: -10,
          child: Icon(Icons.directions_car, size: 140, color: Colors.white.withOpacity(0.15))),
        Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(20)),
                child: Text('PROMO SPESIAL', style: GoogleFonts.outfit(fontSize: 10, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: 1.5))),
              const SizedBox(height: 8),
              Text('Sewa Lebih Hemat,\nBepergian Lebih Nyaman!',
                style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white, height: 1.3)),
            ],
          ),
        ),
      ],
    ),
  );
}

class _QuickStats extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
      child: Row(
        children: [
          _StatCard(icon: Icons.verified_outlined,     label: 'Armada',  value: '50+',   color: AppColors.primaryLight),
          const SizedBox(width: 12),
          _StatCard(icon: Icons.star_outline_rounded,  label: 'Rating',  value: '4.9★',  color: AppColors.accent),
          const SizedBox(width: 12),
          _StatCard(icon: Icons.support_agent_outlined, label: '24/7',   value: 'Support', color: AppColors.success),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label, value;
  final Color color;
  const _StatCard({required this.icon, required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) => Expanded(
    child: Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border)),
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 6),
          Text(value, style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
          Text(label, style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
        ],
      ),
    ),
  );
}

class _CarGrid extends StatelessWidget {
  const _CarGrid();

  @override
  Widget build(BuildContext context) {
    return Consumer<MobilProvider>(
      builder: (_, provider, __) {
        if (provider.isLoading && provider.mobils.isEmpty) {
          return const Padding(
            padding: EdgeInsets.all(40),
            child: Center(child: CircularProgressIndicator(color: AppColors.accent)),
          );
        }
        if (provider.mobils.isEmpty) {
          return Padding(
            padding: const EdgeInsets.all(40),
            child: Center(child: Text('Tidak ada mobil tersedia', style: GoogleFonts.outfit(color: AppColors.textSecondary))),
          );
        }
        final items = provider.mobils.take(6).toList();
        return Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: items.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2, crossAxisSpacing: 12, mainAxisSpacing: 12, childAspectRatio: 0.78),
            itemBuilder: (_, i) => _CarCard(mobil: items[i]),
          ),
        );
      },
    );
  }
}

class _CarCard extends StatelessWidget {
  final MobilModel mobil;
  const _CarCard({required this.mobil});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MobilDetailScreen(mobilId: mobil.id))),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            ClipRRect(
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              child: SizedBox(
                height: 110, width: double.infinity,
                child: mobil.primaryImage.isNotEmpty
                    ? CachedNetworkImage(imageUrl: mobil.primaryImage, fit: BoxFit.cover,
                        placeholder: (_, __) => Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, color: AppColors.border, size: 40)),
                        errorWidget: (_, __, ___) => Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, color: AppColors.border, size: 40)))
                    : Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, color: AppColors.border, size: 40)),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(mobil.merk, style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  if (mobil.tipeNama != null)
                    Text(mobil.tipeNama!, style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
                  const SizedBox(height: 6),
                  Text(Formatters.toRupiah(mobil.hargaSewa), style: GoogleFonts.outfit(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.accent)),
                  Text('/hari', style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
