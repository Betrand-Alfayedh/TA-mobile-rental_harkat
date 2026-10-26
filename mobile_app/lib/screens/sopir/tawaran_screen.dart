import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/sopir_model.dart';
import '../../providers/sopir_provider.dart';
import 'widgets/unit_card.dart';

/// Tawaran tugas dari admin: sopir dapat menerima atau menolak.
class TawaranScreen extends StatelessWidget {
  const TawaranScreen({super.key});

  Future<bool> _konfirmasi(BuildContext context, String judul, String isi,
      String tombol, Color warna) async {
    final ya = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(judul),
        content: Text(isi),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            style: TextButton.styleFrom(foregroundColor: AppColors.muted),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(
                backgroundColor: warna, minimumSize: const Size(64, 44)),
            child: Text(tombol),
          ),
        ],
      ),
    );
    return ya == true;
  }

  Future<void> _terima(BuildContext context, TawaranModel t) async {
    final prov = context.read<SopirProvider>();
    final ok = await _konfirmasi(
        context,
        'Terima tugas?',
        '${t.unit.namaLengkap}\n${t.unit.tanggalSewa == null ? '' : 'Mulai ${t.unit.tanggalSewa}'}\n\nStatus Anda akan berubah menjadi "Bertugas".',
        'Terima',
        AppColors.primary);
    if (!ok || !context.mounted) return;
    final galat = await prov.terima(t);
    if (!context.mounted) return;
    _snack(context, galat ?? 'Tugas diterima.', error: galat != null);
  }

  Future<void> _tolak(BuildContext context, TawaranModel t) async {
    final prov = context.read<SopirProvider>();
    final ok = await _konfirmasi(
        context,
        'Tolak tugas?',
        'Admin akan menawarkan tugas ini ke sopir lain.',
        'Tolak',
        AppColors.error);
    if (!ok || !context.mounted) return;
    final galat = await prov.tolak(t);
    if (!context.mounted) return;
    _snack(context, galat ?? 'Tawaran ditolak.', error: galat != null);
  }

  void _snack(BuildContext context, String msg, {required bool error}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: error ? AppColors.error : AppColors.success,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SopirProvider>();
    final s = prov.tawaran;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const TabHeader('Tawaran Tugas'),
          Expanded(
              child: DaftarMuat<TawaranModel>(
            loading: s.loading,
            loaded: s.loaded,
            error: s.error,
            items: s.value,
            onRefresh: prov.muatTawaran,
            empty: const EmptyState(
              icon: Icons.inbox_rounded,
              title: 'Belum ada tawaran',
              subtitle:
                  'Tawaran baru dari admin muncul di sini. Tarik ke bawah untuk menyegarkan.',
            ),
            itemBuilder: (ctx, t) => UnitCard(
              unit: t.unit,
              tampilkanStatus: false,
              aksi: Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: prov.submitting ? null : () => _tolak(ctx, t),
                      child: const Text('Tolak'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: prov.submitting ? null : () => _terima(ctx, t),
                      child: const Text('Terima'),
                    ),
                  ),
                ],
              ),
            ),
          )),
        ]),
      ),
    );
  }
}
