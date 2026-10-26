import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/sopir_model.dart';
import '../../providers/sopir_provider.dart';
import 'widgets/unit_card.dart';

/// Riwayat tugas yang sudah selesai.
class RiwayatSopirScreen extends StatelessWidget {
  const RiwayatSopirScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SopirProvider>();
    final s = prov.riwayat;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const TabHeader('Riwayat Tugas'),
          Expanded(
              child: DaftarMuat<SopirUnitModel>(
            loading: s.loading,
            loaded: s.loaded,
            error: s.error,
            items: s.value,
            onRefresh: prov.muatRiwayat,
            empty: const EmptyState(
              icon: Icons.history_rounded,
              title: 'Belum ada riwayat',
              subtitle: 'Tugas yang sudah selesai akan tercatat di sini.',
            ),
            itemBuilder: (_, u) => UnitCard(unit: u),
          )),
        ]),
      ),
    );
  }
}
