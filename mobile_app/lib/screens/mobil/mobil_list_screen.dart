import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../providers/mobil_provider.dart';
import '../../models/mobil_model.dart';
import 'mobil_detail_screen.dart';

class MobilListScreen extends StatefulWidget {
  const MobilListScreen({super.key});
  @override
  State<MobilListScreen> createState() => _MobilListScreenState();
}

class _MobilListScreenState extends State<MobilListScreen> {
  final _searchCtrl   = TextEditingController();
  final _scrollCtrl   = ScrollController();
  String _activeType  = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<MobilProvider>();
      p.loadTipes();
      p.loadMobils(reset: true);
    });
    _scrollCtrl.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >= _scrollCtrl.position.maxScrollExtent - 200) {
      context.read<MobilProvider>().loadMore();
    }
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _applyFilter() {
    context.read<MobilProvider>().applyFilter(
      search: _searchCtrl.text.trim(),
      type:   _activeType,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: AppColors.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('Katalog Mobil', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
      ),
      body: Column(
        children: [
          // ── Search Bar ──────────────────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: TextField(
              controller: _searchCtrl,
              style: GoogleFonts.outfit(color: AppColors.textPrimary),
              onSubmitted: (_) => _applyFilter(),
              decoration: InputDecoration(
                hintText:     'Cari merek atau nama mobil...',
                hintStyle:    GoogleFonts.outfit(color: AppColors.textHint, fontSize: 14),
                prefixIcon:   const Icon(Icons.search, color: AppColors.textSecondary),
                suffixIcon:   _searchCtrl.text.isNotEmpty
                    ? IconButton(icon: const Icon(Icons.close, color: AppColors.textSecondary), onPressed: () { _searchCtrl.clear(); _applyFilter(); })
                    : null,
                filled:       true,
                fillColor:    AppColors.surface,
                contentPadding: const EdgeInsets.symmetric(vertical: 14),
                border:       OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.border)),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.primaryLight, width: 2)),
              ),
            ),
          ),

          // ── Type Filter Chips ───────────────────────────────
          Consumer<MobilProvider>(
            builder: (_, prov, __) {
              final tipes = prov.tipes;
              if (tipes.isEmpty) return const SizedBox.shrink();
              return SizedBox(
                height: 44,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: tipes.length + 1,
                  separatorBuilder: (_, __) => const SizedBox(width: 8),
                  itemBuilder: (_, i) {
                    if (i == 0) {
                      return FilterChip(
                        label: Text('Semua', style: GoogleFonts.outfit(fontSize: 12, color: _activeType.isEmpty ? Colors.white : AppColors.textSecondary, fontWeight: FontWeight.w600)),
                        selected: _activeType.isEmpty,
                        onSelected: (_) { setState(() => _activeType = ''); _applyFilter(); },
                        selectedColor: AppColors.primary,
                        backgroundColor: AppColors.surface,
                        checkmarkColor: Colors.white,
                        side: const BorderSide(color: AppColors.border),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      );
                    }
                    final tipe = tipes[i - 1];
                    final sel  = _activeType == tipe.namaTipe;
                    return FilterChip(
                      label: Text(tipe.namaTipe, style: GoogleFonts.outfit(fontSize: 12, color: sel ? Colors.white : AppColors.textSecondary, fontWeight: FontWeight.w600)),
                      selected: sel,
                      onSelected: (_) { setState(() => _activeType = sel ? '' : tipe.namaTipe); _applyFilter(); },
                      selectedColor: AppColors.primary,
                      backgroundColor: AppColors.surface,
                      checkmarkColor: Colors.white,
                      side: const BorderSide(color: AppColors.border),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                    );
                  },
                ),
              );
            },
          ),

          const SizedBox(height: 8),

          // ── Car List ────────────────────────────────────────
          Expanded(
            child: Consumer<MobilProvider>(
              builder: (_, prov, __) {
                if (prov.isLoading && prov.mobils.isEmpty) {
                  return const Center(child: CircularProgressIndicator(color: AppColors.accent));
                }
                if (prov.state == MobilLoadState.error) {
                  return Center(child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.wifi_off_rounded, size: 56, color: AppColors.textHint),
                      const SizedBox(height: 12),
                      Text(prov.error, style: GoogleFonts.outfit(color: AppColors.textSecondary)),
                      const SizedBox(height: 16),
                      ElevatedButton(onPressed: () => prov.loadMobils(reset: true), child: const Text('Coba Lagi')),
                    ],
                  ));
                }
                if (prov.mobils.isEmpty) {
                  return Center(child: Text('Tidak ada mobil ditemukan', style: GoogleFonts.outfit(color: AppColors.textSecondary)));
                }
                return GridView.builder(
                  controller: _scrollCtrl,
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2, crossAxisSpacing: 12, mainAxisSpacing: 12, childAspectRatio: 0.75),
                  itemCount: prov.mobils.length + (prov.hasMore ? 1 : 0),
                  itemBuilder: (_, i) {
                    if (i == prov.mobils.length) {
                      return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator(color: AppColors.accent, strokeWidth: 2)));
                    }
                    return _MobilCard(mobil: prov.mobils[i]);
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _MobilCard extends StatelessWidget {
  final MobilModel mobil;
  const _MobilCard({required this.mobil});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MobilDetailScreen(mobilId: mobil.id))),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 8, offset: const Offset(0, 3))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            ClipRRect(
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              child: Stack(
                children: [
                  SizedBox(
                    height: 110, width: double.infinity,
                    child: mobil.primaryImage.isNotEmpty
                        ? CachedNetworkImage(imageUrl: mobil.primaryImage, fit: BoxFit.cover,
                            placeholder: (_, __) => Container(color: AppColors.surfaceLight),
                            errorWidget: (_, __, ___) => Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, color: AppColors.border, size: 40)))
                        : Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car, color: AppColors.border, size: 40)),
                  ),
                  if (!mobil.isAvailable)
                    Positioned.fill(child: Container(
                      color: Colors.black54,
                      child: Center(child: Text('Tidak Tersedia', style: GoogleFonts.outfit(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700))),
                    )),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(mobil.merk, style: GoogleFonts.outfit(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary), maxLines: 1, overflow: TextOverflow.ellipsis),
                    if (mobil.tipeNama != null) ...[
                      const SizedBox(height: 2),
                      Text(mobil.tipeNama!, style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
                    ],
                    const Spacer(),
                    Row(
                      children: [
                        const Icon(Icons.calendar_today_outlined, size: 10, color: AppColors.textSecondary),
                        const SizedBox(width: 4),
                        Text(mobil.tahun.toString(), style: GoogleFonts.outfit(fontSize: 10, color: AppColors.textSecondary)),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(Formatters.toRupiah(mobil.hargaSewa), style: GoogleFonts.outfit(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.accent)),
                    Text('/hari', style: GoogleFonts.outfit(fontSize: 9, color: AppColors.textHint)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
