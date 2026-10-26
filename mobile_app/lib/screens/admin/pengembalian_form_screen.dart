import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../models/admin_model.dart';
import '../../providers/admin_provider.dart';
import '../../core/widgets/app_widgets.dart';
import 'widgets/admin_widgets.dart';

/// Catat pengembalian satu unit: waktu aktual, kondisi, foto. Denda keterlambatan ditampilkan sebagai pratinjau
/// (angka akhir dihitung server dengan aturan yang sama).
class PengembalianFormScreen extends StatefulWidget {
  final int bookingId;
  final UnitAdminModel unit;
  const PengembalianFormScreen({super.key, required this.bookingId, required this.unit});

  @override
  State<PengembalianFormScreen> createState() => _PengembalianFormScreenState();
}

class _PengembalianFormScreenState extends State<PengembalianFormScreen> {
  final _catatan = TextEditingController();
  late DateTime _aktual = DateTime.now();
  int _kondisi = 1;   // 1 baik, 0 rusak
  File? _foto;

  DateTime? get _rencana => DateTime.tryParse(widget.unit.tanggalKembali ?? '');

  @override
  void dispose() {
    _catatan.dispose();
    super.dispose();
  }

  Future<void> _pilihWaktu() async {
    final tgl = await showDatePicker(
      context: context,
      initialDate: _aktual,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );
    if (tgl == null || !mounted) return;
    final jam = await showTimePicker(context: context, initialTime: TimeOfDay.fromDateTime(_aktual));
    if (jam == null || !mounted) return;
    setState(() => _aktual = DateTime(tgl.year, tgl.month, tgl.day, jam.hour, jam.minute));
  }

  Future<void> _ambilFoto(ImageSource sumber) async {
    try {
      final x = await ImagePicker().pickImage(source: sumber, imageQuality: 80, maxWidth: 1600);
      if (x != null && mounted) setState(() => _foto = File(x.path));
    } catch (_) {
      if (mounted) tampilkanSnack(context, 'Foto tidak dapat diambil.', galat: true);
    }
  }

  void _pilihSumber() {
    showModalBottomSheet<void>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(leading: const Icon(Icons.photo_camera_rounded, color: AppColors.primary), title: const Text('Kamera'),
              onTap: () { Navigator.pop(ctx); _ambilFoto(ImageSource.camera); }),
          ListTile(leading: const Icon(Icons.photo_library_rounded, color: AppColors.primary), title: const Text('Galeri'),
              onTap: () { Navigator.pop(ctx); _ambilFoto(ImageSource.gallery); }),
          const SizedBox(height: 12),
        ]),
      ),
    );
  }

  Future<void> _simpan() async {
    final prov = context.read<AdminProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final jam = _rencana == null ? 0 : DendaKeterlambatan.jam(_rencana!, _aktual);

    final ya = await konfirmasi(context,
        judul: 'Catat pengembalian?',
        isi: '${widget.unit.namaLengkap}\nKembali: ${DateFormat('d MMM yyyy HH:mm', 'id_ID').format(_aktual)}\n'
            'Kondisi: ${_kondisi == 1 ? 'baik' : 'rusak'}'
            '${jam > 0 ? '\nDenda: ${Formatters.toRupiah(jam * DendaKeterlambatan.perJam)} ($jam jam)' : ''}\n\n'
            'Tindakan ini tidak dapat dibatalkan.',
        tombol: 'Catat');
    if (!ya) return;

    final h = await prov.catatPengembalian(
      widget.bookingId,
      widget.unit,
      tanggalAktual: DateFormat('yyyy-MM-dd HH:mm:ss').format(_aktual),
      kondisi: _kondisi,
      catatan: _catatan.text,
      foto: _foto,
    );
    if (h.galat == null && nav.mounted) nav.pop(true);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(h.galat ?? ('Pengembalian dicatat.${(h.info ?? '').isNotEmpty ? ' ${h.info}' : ''}')),
        backgroundColor: h.galat == null ? AppColors.success : AppColors.error,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final submitting = context.select<AdminProvider, bool>((a) => a.submitting);
    final rencana = _rencana;
    final jam = rencana == null ? 0 : DendaKeterlambatan.jam(rencana, _aktual);

    final t = GoogleFonts.plusJakartaSans;

    return HalamanForm(
      judul: 'Catat pengembalian',
      aksi: PrimaryButton(label: 'Catat pengembalian', loading: submitting, onPressed: _simpan),
      children: [
        SoftCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(color: AppColors.primarySoft, borderRadius: BorderRadius.circular(14)),
                child: const Icon(Icons.directions_car_rounded, color: AppColors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(widget.unit.namaLengkap, style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                  if (widget.unit.platNomor != null) Text(widget.unit.platNomor!, style: t(fontSize: 12.5, color: AppColors.muted)),
                ]),
              ),
            ]),
            const SizedBox(height: 14),
            BarisInfo('Jadwal kembali', Formatters.toShortDateTime(widget.unit.tanggalKembali)),
          ]),
        ),
        const SizedBox(height: 20),
        const SectionLabel('Waktu kembali aktual'),
        Semantics(
          button: true,
          child: GestureDetector(
            onTap: submitting ? null : _pilihWaktu,
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: AppColors.primary, width: 1.5),
              ),
              child: Row(children: [
                const Icon(Icons.schedule_rounded, size: 20, color: AppColors.primary),
                const SizedBox(width: 12),
                Expanded(child: Text('Kembali: ${DateFormat('d MMM yyyy HH:mm', 'id_ID').format(_aktual)}', style: t(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.ink))),
                const Icon(Icons.edit_calendar_outlined, size: 18, color: AppColors.muted),
              ]),
            ),
          ),
        ),
        const SizedBox(height: 10),
        InfoBanner(
          jam > 0
              ? 'Terlambat $jam jam. Denda ${Formatters.toRupiah(jam * DendaKeterlambatan.perJam)} akan ditagihkan.'
              : 'Tepat waktu, tidak ada denda.',
          tone: jam > 0 ? BannerTone.warning : BannerTone.success,
        ),
        const SizedBox(height: 22),
        const SectionLabel('Kondisi kendaraan'),
        Row(children: [
          Expanded(child: ChoiceTile(title: 'Baik', icon: Icons.check_circle_outline_rounded, selected: _kondisi == 1, onTap: submitting ? () {} : () => setState(() => _kondisi = 1))),
          const SizedBox(width: 12),
          Expanded(child: ChoiceTile(title: 'Rusak', icon: Icons.build_circle_outlined, selected: _kondisi == 0, onTap: submitting ? () {} : () => setState(() => _kondisi = 0))),
        ]),
        if (_kondisi == 0) ...[
          const SizedBox(height: 10),
          const InfoBanner('Mobil akan ditandai rusak dan tidak dapat disewa sampai statusnya diubah.', tone: BannerTone.warning),
        ],
        const SizedBox(height: 20),
        TextField(
          controller: _catatan,
          enabled: !submitting,
          maxLines: 3,
          maxLength: 500,
          decoration: const InputDecoration(labelText: 'Catatan (opsional)'),
        ),
        const SizedBox(height: 10),
        if (_foto != null) ...[
          ClipRRect(
            borderRadius: BorderRadius.circular(22),
            child: Image.file(_foto!, height: 180, width: double.infinity, fit: BoxFit.cover),
          ),
          const SizedBox(height: 10),
        ],
        TombolFoto(label: _foto == null ? 'Foto kondisi (opsional)' : 'Ganti foto', onPressed: submitting ? null : _pilihSumber),
      ],
    );
  }
}
