import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/mobil_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/mobil_provider.dart';

class MobilListScreen extends StatefulWidget {
  /// Tipe yang sudah dipilih di beranda (kosong = semua).
  final String initialType;
  const MobilListScreen({super.key, this.initialType = ''});
  @override
  State<MobilListScreen> createState() => _MobilListScreenState();
}

class _MobilListScreenState extends State<MobilListScreen> {
  final _searchCtrl  = TextEditingController();
  final _scrollCtrl  = ScrollController();
  String _activeType = '';

  @override
  void initState() {
    super.initState();
    _activeType = widget.initialType;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<MobilProvider>();
      p.loadTipes();
      // Mulai dari filter layar ini (bukan sisa filter beranda).
      p.applyFilter(search: '', type: _activeType);
    });
    _scrollCtrl.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >= _scrollCtrl.position.maxScrollExtent - 240) {
      context.read<MobilProvider>().loadMore();
    }
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _applyFilter() => context.read<MobilProvider>().applyFilter(
        search: _searchCtrl.text.trim(),
        type:   _activeType,
      );

  @override
  Widget build(BuildContext context) {
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 920,
          child: Column(
            children: [
              // ── Judul ─────────────────────────────────────
              Padding(
                padding: EdgeInsets.fromLTRB(gutter - 4, 8, gutter, 0),
                child: Row(children: [
                  const BackCircleButton(),
                  const SizedBox(width: 8),
                  Text('Katalog Mobil', style: t(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.ink)),
                ]),
              ),

              // ── Pencarian ─────────────────────────────────
              Padding(
                padding: EdgeInsets.fromLTRB(gutter, 14, gutter, 12),
                child: Container(
                  decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.line)),
                  child: TextField(
                    controller: _searchCtrl,
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => _applyFilter(),
                    onChanged: (_) => setState(() {}),
                    style: t(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.ink),
                    decoration: InputDecoration(
                      hintText: 'Cari mobil, merek, atau tipe…',
                      hintStyle: t(fontSize: 13.5, color: AppColors.muted),
                      prefixIcon: const Icon(Icons.search_rounded, color: AppColors.muted, size: 21),
                      suffixIcon: _searchCtrl.text.isNotEmpty
                          ? IconButton(
                              tooltip: 'Hapus pencarian',
                              icon: const Icon(Icons.close_rounded, color: AppColors.muted, size: 20),
                              onPressed: () { _searchCtrl.clear(); setState(() {}); _applyFilter(); },
                            )
                          : null,
                      filled: false,
                      contentPadding: const EdgeInsets.symmetric(vertical: 16),
                      border: InputBorder.none,
                      enabledBorder: InputBorder.none,
                      focusedBorder: InputBorder.none,
                    ),
                  ),
                ),
              ),

              // ── Chip tipe ─────────────────────────────────
              TipeChips(
                aktif: _activeType,
                onPilih: (v) { setState(() => _activeType = v); _applyFilter(); },
              ),
              const SizedBox(height: 12),

              // ── Daftar ────────────────────────────────────
              Expanded(
                child: Consumer<MobilProvider>(builder: (_, prov, __) {
                  if (prov.isLoading && prov.mobils.isEmpty) {
                    return const Center(child: CircularProgressIndicator(color: AppColors.primary));
                  }
                  if (prov.state == MobilLoadState.error && prov.mobils.isEmpty) {
                    return ErrorState(message: prov.error, onRetry: () => prov.loadMobils(reset: true));
                  }
                  if (prov.mobils.isEmpty) {
                    return const EmptyState(
                      icon: Icons.search_off_rounded,
                      title: 'Mobil tidak ditemukan',
                      subtitle: 'Coba kata kunci atau tipe lain.',
                    );
                  }
                  return LayoutBuilder(builder: (context, c) {
                    const gap = 12.0;
                    final k = c.maxWidth < 600 ? 1 : 2;
                    return RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: () => prov.loadMobils(reset: true),
                      child: CustomScrollView(
                        controller: _scrollCtrl,
                        physics: const AlwaysScrollableScrollPhysics(),
                        slivers: [
                          SliverPadding(
                            padding: EdgeInsets.fromLTRB(gutter, 0, gutter, 12),
                            sliver: SliverGrid(
                              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: k,
                                crossAxisSpacing: gap,
                                mainAxisSpacing: gap,
                                mainAxisExtent: 102 + MediaQuery.textScalerOf(context).scale(14) - 14,
                              ),
                              delegate: SliverChildBuilderDelegate(
                                (_, i) => MobilCardH(mobil: prov.mobils[i]),
                                childCount: prov.mobils.length,
                              ),
                            ),
                          ),
                          if (prov.hasMore)
                            const SliverToBoxAdapter(
                              child: Padding(
                                padding: EdgeInsets.symmetric(vertical: 16),
                                child: Center(child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4))),
                              ),
                            ),
                          const SliverToBoxAdapter(child: SizedBox(height: 24)),
                        ],
                      ),
                    );
                  });
                }),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
