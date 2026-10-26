import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/mobil_model.dart';
import '../../providers/admin_master_provider.dart';
import 'armada_form_screen.dart';
import 'widgets/admin_widgets.dart';

/// Armada: cari, filter status, ubah status, ubah data, tambah, dan hapus (hanya yang belum pernah disewa).
class ArmadaListScreen extends StatefulWidget {
  const ArmadaListScreen({super.key});

  @override
  State<ArmadaListScreen> createState() => _ArmadaListScreenState();
}

class _ArmadaListScreenState extends State<ArmadaListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<AdminMasterProvider>();
      if (!p.mobils.loaded) p.muatMobil();
    });
  }

  Future<void> _ubahStatus(MobilModel m, int status, String nama) async {
    final prov = context.read<AdminMasterProvider>();
    final ya = await konfirmasi(context, judul: 'Ubah status?', isi: '${m.platNomor} menjadi "$nama".', tombol: 'Ubah');
    if (!ya || !mounted) return;
    final h = await prov.ubahStatusMobil(m.id, status);
    if (mounted) tampilkanHasil(context, h.galat, 'Status mobil diperbarui.');
  }

  Future<void> _hapus(MobilModel m) async {
    final prov = context.read<AdminMasterProvider>();
    final ya = await konfirmasi(context,
        judul: 'Hapus mobil?',
        isi: '${m.platNomor} akan dihapus permanen. Hanya mobil yang belum pernah disewa yang dapat dihapus.',
        tombol: 'Hapus',
        warna: AppColors.error);
    if (!ya || !mounted) return;
    final h = await prov.hapusMobil(m.id);
    if (mounted) tampilkanHasil(context, h.galat, 'Mobil dihapus.');
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminMasterProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ArmadaFormScreen())),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Tambah mobil'),
      ),
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const TabHeader('Armada'),
        KotakCari(hint: 'Cari plat, merk, atau model', awal: prov.kataKunciMobil, onCari: (q) => prov.aturFilterMobil(kataKunci: q)),
        BarisFilter<int>(
          pilihan: const [('Semua', null), ('Tersedia', 1), ('Disewa', 3), ('Maintenance', 4), ('Rusak', 0)],
          terpilih: prov.filterStatusMobil,
          onPilih: (s) => prov.aturFilterMobil(status: s, hapusStatus: s == null),
        ),
        Expanded(
          child: DaftarBerhalaman<MobilModel>(
            halaman: prov.mobils,
            onRefresh: prov.muatMobil,
            onLagi: prov.mobilLagi,
            bawah: 96,
            empty: const EmptyState(icon: Icons.directions_car_outlined, title: 'Belum ada mobil', subtitle: 'Ketuk "Tambah mobil" untuk memulai.'),
            itemBuilder: (ctx, m) => _KartuMobil(
              m,
              onUbah: () => Navigator.of(ctx).push(MaterialPageRoute(builder: (_) => ArmadaFormScreen(mobil: m))),
              onStatus: (s, nama) => _ubahStatus(m, s, nama),
              onHapus: () => _hapus(m),
            ),
          ),
        ),
      ]))),
    );
  }
}

class _KartuMobil extends StatelessWidget {
  final MobilModel m;
  final VoidCallback onUbah;
  final void Function(int status, String nama) onStatus;
  final VoidCallback onHapus;
  const _KartuMobil(this.m, {required this.onUbah, required this.onStatus, required this.onHapus});

  @override
  Widget build(BuildContext context) {
    final dikunci = m.status == 3; // disewa: status hanya berubah lewat pengembalian
    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: onUbah,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
        child: Row(children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: SizedBox(
              width: 84,
              height: 64,
              child: (m.gambar != null && m.gambar!.isNotEmpty)
                  ? Image.network(m.gambar!, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _kosong())
                  : _kosong(),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(m.masterMobil != null ? '${m.masterMobil!.nama} (${m.merk})' : m.merk,
                  maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w700)),
              Text('${m.platNomor} • ${m.tahun}', style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary)),
              Text('${Formatters.toRupiah(m.hargaSewa)} / hari', style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.ink, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              StatusPill(label: labelStatusMobil(m.status), color: warnaStatusMobil(m.status)),
            ]),
          ),
          PopupMenuButton<String>(
            tooltip: 'Menu mobil',
            onSelected: (v) {
              switch (v) {
                case 'ubah': onUbah();
                case 'tersedia': onStatus(1, 'Tersedia');
                case 'maintenance': onStatus(4, 'Maintenance');
                case 'rusak': onStatus(0, 'Rusak');
                case 'hapus': onHapus();
              }
            },
            itemBuilder: (_) => [
              const PopupMenuItem(value: 'ubah', child: Text('Ubah data')),
              if (!dikunci && m.status != 1) const PopupMenuItem(value: 'tersedia', child: Text('Tandai tersedia')),
              if (!dikunci && m.status != 4) const PopupMenuItem(value: 'maintenance', child: Text('Tandai maintenance')),
              if (!dikunci && m.status != 0) const PopupMenuItem(value: 'rusak', child: Text('Tandai rusak')),
              const PopupMenuItem(value: 'hapus', child: Text('Hapus')),
            ],
          ),
        ]),
      ),
    );
  }

  Widget _kosong() => Container(color: AppColors.surfaceLight, child: const Icon(Icons.directions_car_rounded, color: AppColors.textHint));
}
