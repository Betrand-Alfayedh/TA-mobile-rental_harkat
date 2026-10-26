import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../models/sopir_model.dart';
import '../../providers/sopir_provider.dart';

/// Form laporan kondisi kendaraan. [jenis]: 1 sebelum sewa, 2 sesudah sewa.
///
/// Dipakai sopir (konstruktor biasa, mengirim lewat SopirProvider) dan admin ([LaporanKondisiScreen.generik]
/// dengan [kirim] sendiri).
class LaporanKondisiScreen extends StatefulWidget {
  final String namaUnit;
  final String? platNomor;
  final LaporanKondisiModel? lama;
  final int jenis;
  final int? unitId;

  /// Pengirim laporan; mengembalikan pesan galat atau null bila berhasil. Null → SopirProvider.
  final Future<String?> Function(int kondisi, String? catatan, File? foto)? kirim;

  LaporanKondisiScreen({super.key, required SopirUnitModel unit, required this.jenis})
      : namaUnit = unit.namaLengkap,
        platNomor = unit.platNomor,
        lama = jenis == 1 ? unit.laporanSebelum : unit.laporanSesudah,
        unitId = unit.id,
        kirim = null;

  const LaporanKondisiScreen.generik({
    super.key,
    required this.namaUnit,
    this.platNomor,
    this.lama,
    required this.jenis,
    required this.kirim,
  }) : unitId = null;

  @override
  State<LaporanKondisiScreen> createState() => _LaporanKondisiScreenState();
}

class _LaporanKondisiScreenState extends State<LaporanKondisiScreen> {
  static const _pilihan = {1: 'Baik', 2: 'Lecet / perlu perhatian', 3: 'Rusak'};

  final _catatan = TextEditingController();
  int _kondisi = 1;
  File? _foto;
  bool _mengirim = false;

  LaporanKondisiModel? get _lama => widget.lama;
  String get _judul => widget.jenis == 1 ? 'Kondisi sebelum sewa' : 'Kondisi sesudah sewa';

  @override
  void initState() {
    super.initState();
    final lama = _lama;
    if (lama != null) {
      _kondisi = lama.kondisi;
      _catatan.text = lama.catatan ?? '';
    }
  }

  @override
  void dispose() {
    _catatan.dispose();
    super.dispose();
  }

  Future<void> _ambilFoto(ImageSource sumber) async {
    try {
      // Dikompres di perangkat agar unggahan ringan dan di bawah batas 2 MB server.
      final x = await ImagePicker().pickImage(source: sumber, imageQuality: 80, maxWidth: 1600);
      if (x != null && mounted) setState(() => _foto = File(x.path));
    } catch (_) {
      if (mounted) _snack('Foto tidak dapat diambil.', error: true);
    }
  }

  void _pilihSumber() {
    showModalBottomSheet<void>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(
            leading: const Icon(Icons.photo_camera_rounded),
            title: const Text('Kamera'),
            onTap: () { Navigator.pop(ctx); _ambilFoto(ImageSource.camera); },
          ),
          ListTile(
            leading: const Icon(Icons.photo_library_rounded),
            title: const Text('Galeri'),
            onTap: () { Navigator.pop(ctx); _ambilFoto(ImageSource.gallery); },
          ),
        ]),
      ),
    );
  }

  Future<void> _kirim() async {
    if (_mengirim) return;
    if (_kondisi != 1 && _catatan.text.trim().isEmpty) {
      _snack('Jelaskan kondisi kendaraan pada catatan.', error: true);
      return;
    }
    // Ambil dependensi sebelum await agar tidak memakai context setelah jeda.
    final kirim = widget.kirim ??
        (int kondisi, String? catatan, File? foto) => context.read<SopirProvider>().kirimLaporan(
              unitId: widget.unitId!, jenis: widget.jenis, kondisi: kondisi, catatan: catatan, foto: foto);

    setState(() => _mengirim = true);
    String? galat;
    try {
      galat = await kirim(_kondisi, _catatan.text, _foto);
    } catch (e) {
      galat = 'Terjadi kesalahan: $e';
    }
    if (!mounted) return;
    setState(() => _mengirim = false);
    if (galat != null) {
      _snack(galat, error: true);
      return;
    }
    Navigator.pop(context, true);
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: error ? AppColors.error : AppColors.success,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final submitting = _mengirim;
    final gutter = screenGutter(context);
    final t = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(
            children: [
              ScreenHeader(_judul),
              Expanded(
                child: ListView(
                  padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                  children: [
                    SoftCard(
                      child: Row(children: [
                        Container(
                          width: 44, height: 44,
                          decoration: BoxDecoration(color: AppColors.primarySoft, borderRadius: BorderRadius.circular(14)),
                          child: const Icon(Icons.directions_car_rounded, color: AppColors.primary, size: 22),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(widget.namaUnit, style: t(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                            if (widget.platNomor != null)
                              Text(widget.platNomor!, style: t(fontSize: 12.5, color: AppColors.muted)),
                          ]),
                        ),
                      ]),
                    ),
                    if (_lama != null) ...[
                      const SizedBox(height: 12),
                      const InfoBanner('Laporan sudah pernah dikirim. Mengirim ulang akan memperbaruinya.', tone: BannerTone.warning),
                    ],
                    const SizedBox(height: 22),
                    const SectionLabel('Kondisi kendaraan'),
                    for (final e in _pilihan.entries) ...[
                      ChoiceTile(
                        title: e.value,
                        selected: _kondisi == e.key,
                        onTap: submitting ? () {} : () => setState(() => _kondisi = e.key),
                      ),
                      const SizedBox(height: 10),
                    ],
                    const SizedBox(height: 10),
                    TextField(
                      controller: _catatan,
                      maxLines: 4,
                      maxLength: 500,
                      enabled: !submitting,
                      decoration: const InputDecoration(labelText: 'Catatan', hintText: 'Mis. lecet di bumper kiri depan'),
                    ),
                    const SizedBox(height: 14),
                    const SectionLabel('Foto (opsional)'),
                    if (_foto != null)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(22),
                        child: Image.file(_foto!, height: 200, width: double.infinity, fit: BoxFit.cover),
                      )
                    else if (_lama?.foto != null)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(22),
                        child: Image.network(_lama!.foto!, height: 200, width: double.infinity, fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => const SizedBox(height: 60, child: Center(child: Text('Foto tidak dapat dimuat')))),
                      ),
                    const SizedBox(height: 10),
                    SecondaryButton(
                      onPressed: submitting ? null : _pilihSumber,
                      child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                        const Icon(Icons.add_a_photo_rounded, size: 20, color: AppColors.primary),
                        const SizedBox(width: 10),
                        Text(_foto == null ? 'Ambil / pilih foto' : 'Ganti foto',
                            style: t(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
                      ]),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: BottomActionBar(
        child: PrimaryButton(label: 'Kirim laporan', loading: submitting, onPressed: _kirim),
      ),
    );
  }
}
