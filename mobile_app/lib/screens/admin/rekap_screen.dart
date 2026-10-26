import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../providers/admin_master_provider.dart';
import 'widgets/admin_widgets.dart';

/// Rekap operasional per bulan: pesanan, pendapatan (pembayaran lunas), dan pemakaian armada.
class RekapScreen extends StatefulWidget {
  const RekapScreen({super.key});

  @override
  State<RekapScreen> createState() => _RekapScreenState();
}

class _RekapScreenState extends State<RekapScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminMasterProvider>().muatRekap());
  }

  /// Geser bulan [selisih] (−1 / +1) dari bulan yang sedang ditampilkan.
  String _geser(String bulan, int selisih) {
    final p = bulan.split('-');
    final d = DateTime(int.parse(p[0]), int.parse(p[1]) + selisih);
    return '${d.year}-${d.month.toString().padLeft(2, '0')}';
  }

  String _label(String bulan) {
    final p = bulan.split('-');
    return DateFormat('MMMM yyyy', 'id_ID').format(DateTime(int.parse(p[0]), int.parse(p[1])));
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminMasterProvider>();
    final r = prov.rekap;
    final bulanIni = DateTime.now();
    final nilai = r.value;
    final sudahBulanIni = prov.bulanRekap == '${bulanIni.year}-${bulanIni.month.toString().padLeft(2, '0')}';

    final gutter = screenGutter(context);
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const ScreenHeader('Rekap bulanan'),
        Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            IconButton(tooltip: 'Bulan sebelumnya', icon: const Icon(Icons.chevron_left_rounded), onPressed: () => prov.muatRekap(_geser(prov.bulanRekap, -1))),
            SizedBox(width: 170, child: Text(_label(prov.bulanRekap), textAlign: TextAlign.center, style: GoogleFonts.plusJakartaSans(fontSize: 17, fontWeight: FontWeight.w700))),
            IconButton(
              tooltip: 'Bulan berikutnya',
              icon: const Icon(Icons.chevron_right_rounded),
              onPressed: sudahBulanIni ? null : () => prov.muatRekap(_geser(prov.bulanRekap, 1)),
            ),
          ]),
        ),
        Expanded(
          child: Builder(builder: (_) {
            if (!r.loaded && r.loading) return const Center(child: CircularProgressIndicator());
            if (nilai == null) return ErrorState(message: r.error ?? 'Data belum tersedia.', onRetry: prov.muatRekap);
            return RefreshIndicator(
              onRefresh: prov.muatRekap,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 32),
                children: [
                  BagianKartu(judul: 'Pesanan (${nilai.pesananTotal})', child: Column(children: [
                    BarisInfo('Booked', '${nilai.booked}'),
                    BarisInfo('Berjalan', '${nilai.berjalan}'),
                    BarisInfo('Selesai', '${nilai.selesai}'),
                    BarisInfo('Dibatalkan', '${nilai.dibatalkan}'),
                  ])),
                  const SizedBox(height: 12),
                  BagianKartu(judul: 'Pendapatan (pembayaran lunas)', child: Column(children: [
                    BarisInfo('DP', Formatters.toRupiah(nilai.dp)),
                    BarisInfo('Pelunasan', Formatters.toRupiah(nilai.pelunasan)),
                    BarisInfo('Total', Formatters.toRupiah(nilai.pendapatanTotal), tebal: true),
                    BarisInfo('Denda tercatat', Formatters.toRupiah(nilai.dendaTercatat)),
                  ])),
                  const SizedBox(height: 12),
                  BagianKartu(judul: 'Pemakaian armada', child: Column(children: [
                    BarisInfo('Unit disewa', '${nilai.unitDisewa}'),
                    BarisInfo('Total hari sewa', '${nilai.hariSewa}'),
                    BarisInfo('Utilisasi', '${nilai.utilisasiPersen.toStringAsFixed(1)}%'),
                  ])),
                  const SizedBox(height: 12),
                  BagianKartu(
                    judul: 'Per mobil',
                    child: nilai.perMobil.isEmpty
                        ? Text('Belum ada sewa pada bulan ini.', style: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary))
                        : Column(children: [
                            for (final m in nilai.perMobil)
                              ListTile(
                                contentPadding: EdgeInsets.zero,
                                title: Text(m.nama ?? 'Mobil #${m.mobilId}'),
                                subtitle: Text(m.platNomor ?? ''),
                                trailing: Text('${m.jumlahSewa}x • ${m.totalHari} hari', style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600)),
                              ),
                          ]),
                  ),
                ],
              ),
            );
          }),
        ),
      ]))),
    );
  }
}
