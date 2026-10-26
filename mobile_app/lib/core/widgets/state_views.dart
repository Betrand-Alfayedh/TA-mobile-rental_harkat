import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import 'app_widgets.dart';

/// Keadaan kosong: ikon dalam lingkaran lembut, judul, dan keterangan singkat.
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  const EmptyState({super.key, required this.icon, required this.title, this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80, height: 80,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.primarySoft),
              child: Icon(icon, size: 36, color: AppColors.primary),
            ),
            const SizedBox(height: 18),
            Text(title,
                textAlign: TextAlign.center,
                style: GoogleFonts.plusJakartaSans(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.ink)),
            if (subtitle != null) ...[
              const SizedBox(height: 6),
              Text(subtitle!,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.plusJakartaSans(fontSize: 13, height: 1.45, color: AppColors.muted)),
            ],
          ],
        ),
      ),
    );
  }
}

/// Keadaan galat dengan tombol coba lagi.
class ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const ErrorState({super.key, required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80, height: 80,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.errorSoft),
              child: const Icon(Icons.cloud_off_rounded, size: 36, color: AppColors.error),
            ),
            const SizedBox(height: 18),
            Text(message,
                textAlign: TextAlign.center,
                style: GoogleFonts.plusJakartaSans(fontSize: 14, height: 1.45, color: AppColors.muted)),
            const SizedBox(height: 18),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Coba lagi'),
            ),
          ],
        ),
      ),
    );
  }
}

/// Lencana status: teks berwarna di atas latar pucat dari warna yang sama (tidak hanya warna: ada teks).
class StatusPill extends StatelessWidget {
  final String label;
  final Color color;
  const StatusPill({super.key, required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.13),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(label,
          style: GoogleFonts.plusJakartaSans(fontSize: 11.5, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

/// Menampilkan daftar dengan keadaan loading / galat / kosong / isi, plus tarik-untuk-segarkan.
class DaftarMuat<T> extends StatelessWidget {
  final bool loading;
  final bool loaded;
  final String? error;
  final List<T> items;
  final Future<void> Function() onRefresh;
  final Widget Function(BuildContext, T) itemBuilder;
  final Widget empty;

  const DaftarMuat({
    super.key,
    required this.loading,
    required this.loaded,
    required this.error,
    required this.items,
    required this.onRefresh,
    required this.itemBuilder,
    required this.empty,
  });

  @override
  Widget build(BuildContext context) {
    if (!loaded && loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null && items.isEmpty) {
      return ErrorState(message: error!, onRetry: onRefresh);
    }
    if (items.isEmpty) {
      // ListView agar tarik-untuk-segarkan tetap bisa dipakai pada keadaan kosong.
      return RefreshIndicator(
        onRefresh: onRefresh,
        child: LayoutBuilder(
          builder: (_, c) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [SizedBox(height: c.maxHeight, child: empty)],
          ),
        ),
      );
    }
    final gutter = screenGutter(context);
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ContentWidth(
        max: 720,
        child: ListView.separated(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 14),
          itemBuilder: (ctx, i) => itemBuilder(ctx, items[i]),
        ),
      ),
    );
  }
}
