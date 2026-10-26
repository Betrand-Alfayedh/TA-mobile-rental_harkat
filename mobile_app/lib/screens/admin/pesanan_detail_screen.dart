import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/admin_model.dart';
import '../../models/sopir_model.dart';
import '../../providers/admin_provider.dart';
import '../sopir/laporan_kondisi_screen.dart';
import 'pembayaran_sheet.dart';
import 'pengembalian_form_screen.dart';
import 'widgets/admin_widgets.dart';

/// Rincian satu pesanan beserta semua aksi admin: verifikasi pembayaran, penugasan sopir, jemput,
/// laporan kondisi, pengembalian, dan pembatalan.
class PesananDetailScreen extends StatefulWidget {
  final int bookingId;
  const PesananDetailScreen({super.key, required this.bookingId});

  @override
  State<PesananDetailScreen> createState() => _PesananDetailScreenState();
}

class _PesananDetailScreenState extends State<PesananDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminProvider>().muatDetail(widget.bookingId));
  }

  Future<void> _muat() => context.read<AdminProvider>().muatDetail(widget.bookingId);

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminProvider>();
    final d = prov.detail;
    final b = d.value;

    final gutter = screenGutter(context);
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        ScreenHeader('Pesanan #${widget.bookingId}'),
        Expanded(child: Builder(builder: (_) {
        if (b == null || b.id != widget.bookingId) {
          if (d.error != null) return ErrorState(message: d.error!, onRetry: _muat);
          return const Center(child: CircularProgressIndicator());
        }
        return RefreshIndicator(
          onRefresh: _muat,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 32),
            children: [
              _ringkasan(b),
              const SizedBox(height: 12),
              _penyewa(context, b),
              const SizedBox(height: 12),
              _pembayaran(context, b),
              const SizedBox(height: 12),
              for (final u in b.units) ...[_unit(context, prov, b, u), const SizedBox(height: 12)],
              if (b.bisaDibatalkan)
                OutlinedButton.icon(
                  onPressed: prov.submitting ? null : () => _batalkan(context, b),
                  style: OutlinedButton.styleFrom(foregroundColor: AppColors.error, backgroundColor: AppColors.errorSoft, side: BorderSide.none),
                  icon: const Icon(Icons.cancel_outlined),
                  label: const Text('Batalkan pesanan'),
                ),
            ],
          ),
        );
      })),
      ]))),
    );
  }

  Widget _ringkasan(BookingAdminModel b) => BagianKartu(
        judul: 'Ringkasan',
        aksi: StatusPill(label: labelStatusPesanan(b.status), color: warnaStatusPesanan(b.status)),
        child: Column(children: [
          BarisInfo('Dipesan', b.tanggalBooking ?? '-'),
          BarisInfo('Total', Formatters.toRupiah(b.totalHarga), tebal: true),
          BarisInfo('Uang muka', Formatters.toRupiah(b.uangMuka)),
          BarisInfo('Sisa tagihan', Formatters.toRupiah(b.sisaTagihan)),
          if (b.asalKotaLabel != null) BarisInfo('Asal kota', b.namaKota != null && b.namaKota!.isNotEmpty ? b.namaKota! : b.asalKotaLabel!),
          if (b.jaminanLabel != null) BarisInfo('Jaminan', b.jaminanLabel!),
        ]),
      );

  Widget _penyewa(BuildContext context, BookingAdminModel b) => BagianKartu(
        judul: 'Penyewa',
        aksi: (b.penyewaNoHp != null && b.penyewaNoHp!.isNotEmpty)
            ? IconButton(
                tooltip: 'Telepon',
                icon: const Icon(Icons.call_rounded),
                onPressed: () => launchUrl(Uri(scheme: 'tel', path: b.penyewaNoHp)),
              )
            : null,
        child: Column(children: [
          BarisInfo('Nama', b.penyewaNama, tebal: true),
          if (b.penyewaNoHp != null) BarisInfo('No. HP', b.penyewaNoHp!),
          if (b.penyewaEmail != null) BarisInfo('Email', b.penyewaEmail!),
          if (b.penyewaAlamat != null) BarisInfo('Alamat', b.penyewaAlamat!),
        ]),
      );

  Widget _pembayaran(BuildContext context, BookingAdminModel b) => BagianKartu(
        judul: 'Pembayaran',
        child: b.pembayarans.isEmpty
            ? Text('Belum ada tagihan.', style: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary))
            : Column(children: [
                for (final p in b.pembayarans)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    onTap: () => bukaPembayaran(context, p),
                    title: Text(p.judul, style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600)),
                    subtitle: Text(Formatters.toRupiah(p.jumlah)),
                    trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                      StatusPill(label: labelStatusPembayaran(p.status), color: warnaStatusPembayaran(p.status)),
                      const Icon(Icons.chevron_right_rounded),
                    ]),
                  ),
              ]),
      );

  Widget _unit(BuildContext context, AdminProvider prov, BookingAdminModel b, UnitAdminModel u) {
    final jemputBisa = b.status == 1 && u.status == 1;
    return BagianKartu(
      judul: u.namaLengkap.isEmpty ? 'Mobil' : u.namaLengkap,
      aksi: StatusPill(label: labelStatusPesanan(u.status), color: warnaStatusPesanan(u.status)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (u.platNomor != null) BarisInfo('Plat', u.platNomor!),
        BarisInfo('Mulai', Formatters.toShortDateTime(u.tanggalSewa)),
        BarisInfo('Kembali', Formatters.toShortDateTime(u.tanggalKembali)),
        BarisInfo('Lama', Formatters.lamaSewa(u.lamaSewa)),
        BarisInfo('Harga', Formatters.toRupiah(u.harga)),
        BarisInfo('Sopir', _teksSopir(u)),
        _laporan('Sebelum sewa', u.laporanSebelum),
        _laporan('Sesudah sewa', u.laporanSesudah),
        if (u.pengembalian != null) ...[
          const Divider(height: 20, color: AppColors.border),
          BarisInfo('Dikembalikan', Formatters.toShortDateTime(u.pengembalian!.tanggalKembaliAktual)),
          BarisInfo('Kondisi', u.pengembalian!.kondisiLabel),
          if (u.pengembalian!.dendaFlag)
            BarisInfo('Denda', Formatters.toRupiah(u.pengembalian!.nominalDenda), warnaNilai: AppColors.warning, tebal: true),
        ],
        const SizedBox(height: 8),
        Wrap(spacing: 8, runSpacing: 8, children: [
          if (u.perluPenugasan)
            ElevatedButton.icon(
              onPressed: prov.submitting ? null : () => _tugaskan(context, b, u),
              icon: const Icon(Icons.person_add_alt_1_rounded, size: 18),
              label: const Text('Tugaskan sopir'),
            ),
          if (u.status == 1)
            ElevatedButton.icon(
              // Jemput hanya bila pesanan Booked dan DP lunas; alasannya ditampilkan agar tidak membingungkan.
              onPressed: (prov.submitting || !jemputBisa || !b.dpLunas) ? null : () => _jemput(context, b, u),
              icon: const Icon(Icons.key_rounded, size: 18),
              label: Text(b.dpLunas ? 'Konfirmasi jemput' : 'Jemput (DP belum lunas)'),
            ),
          if (u.bisaLaporSebelum)
            OutlinedButton(onPressed: () => _laporan_(context, b, u, 1), child: Text(u.laporanSebelum == null ? 'Lapor sebelum' : 'Ubah laporan sebelum')),
          if (u.bisaLaporSesudah)
            OutlinedButton(onPressed: () => _laporan_(context, b, u, 2), child: Text(u.laporanSesudah == null ? 'Lapor sesudah' : 'Ubah laporan sesudah')),
          if (u.status == 2)
            ElevatedButton.icon(
              onPressed: prov.submitting ? null : () => _kembali(context, b, u),
              icon: const Icon(Icons.assignment_return_rounded, size: 18),
              label: const Text('Catat pengembalian'),
            ),
        ]),
      ]),
    );
  }

  String _teksSopir(UnitAdminModel u) {
    if (!u.pakaiSupir) return 'Tanpa sopir (lepas kunci)';
    if (u.supirNama != null) return u.supirNama! + (u.supirNoHp != null ? ' (${u.supirNoHp})' : '');
    return u.menungguSupir ? 'Menunggu jawaban sopir...' : 'Belum ditugaskan';
  }

  Widget _laporan(String label, LaporanKondisiModel? l) {
    final warna = switch (l?.kondisi) { 1 => AppColors.success, 2 => AppColors.warning, 3 => AppColors.error, _ => AppColors.textHint };
    return BarisInfo(label, l?.kondisiLabel ?? 'belum dilaporkan', warnaNilai: warna);
  }

  // ── Aksi ───────────────────────────────────────────────────

  Future<void> _tugaskan(BuildContext context, BookingAdminModel b, UnitAdminModel u) async {
    final prov = context.read<AdminProvider>();
    final ya = await konfirmasi(context,
        judul: 'Tugaskan sopir?', isi: '${u.namaLengkap} akan ditawarkan ke semua sopir yang tersedia.', tombol: 'Kirim tawaran');
    if (!ya || !context.mounted) return;
    final h = await prov.tugaskan(b.id, u);
    if (context.mounted) tampilkanHasil(context, h.galat, h.info ?? 'Tugas ditawarkan.');
  }

  Future<void> _jemput(BuildContext context, BookingAdminModel b, UnitAdminModel u) async {
    final prov = context.read<AdminProvider>();
    final ya = await konfirmasi(context,
        judul: 'Konfirmasi jemput?',
        isi: '${u.namaLengkap} diserahkan kepada ${b.penyewaNama}. Status mobil menjadi disewa.',
        tombol: 'Konfirmasi',
        warna: AppColors.success);
    if (!ya || !context.mounted) return;
    final h = await prov.jemput(b.id, u);
    if (context.mounted) tampilkanHasil(context, h.galat, 'Unit dijemput.');
  }

  Future<void> _kembali(BuildContext context, BookingAdminModel b, UnitAdminModel u) async {
    await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => PengembalianFormScreen(bookingId: b.id, unit: u)),
    );
  }

  Future<void> _laporan_(BuildContext context, BookingAdminModel b, UnitAdminModel u, int jenis) async {
    final prov = context.read<AdminProvider>();
    final terkirim = await Navigator.of(context).push<bool>(MaterialPageRoute(
      builder: (_) => LaporanKondisiScreen.generik(
        namaUnit: u.namaLengkap,
        platNomor: u.platNomor,
        lama: jenis == 1 ? u.laporanSebelum : u.laporanSesudah,
        jenis: jenis,
        kirim: (kondisi, catatan, foto) async =>
            (await prov.kirimLaporan(b.id, u, jenis: jenis, kondisi: kondisi, catatan: catatan, foto: foto)).galat,
      ),
    ));
    if (terkirim == true && context.mounted) tampilkanSnack(context, 'Laporan kondisi tersimpan.');
  }

  Future<void> _batalkan(BuildContext context, BookingAdminModel b) async {
    final prov = context.read<AdminProvider>();
    final alasan = await mintaTeks(context,
        judul: 'Batalkan pesanan #${b.id}?',
        label: 'Alasan (opsional)',
        tombol: 'Batalkan pesanan',
        warna: AppColors.error);
    if (alasan == null || !context.mounted) return;
    final h = await prov.batalkan(b, catatan: alasan);
    if (context.mounted) tampilkanHasil(context, h.galat, 'Pesanan dibatalkan.');
  }
}
