import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../models/mobil_model.dart';
import '../../providers/admin_master_provider.dart';
import '../../core/widgets/app_widgets.dart';
import 'widgets/admin_widgets.dart';

/// Form tambah / ubah mobil. [mobil] null = tambah. Aturan sama dengan server (plat, tahun, harga).
class ArmadaFormScreen extends StatefulWidget {
  final MobilModel? mobil;
  const ArmadaFormScreen({super.key, this.mobil});

  @override
  State<ArmadaFormScreen> createState() => _ArmadaFormScreenState();
}

class _ArmadaFormScreenState extends State<ArmadaFormScreen> {
  static final _regexPlat = RegExp(r'^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$');

  final _form = GlobalKey<FormState>();
  late final _plat = TextEditingController(text: widget.mobil?.platNomor ?? '');
  late final _merk = TextEditingController(text: widget.mobil?.merk ?? '');
  late final _tahun = TextEditingController(text: widget.mobil != null ? '${widget.mobil!.tahun}' : '');
  late final _harga = TextEditingController(text: widget.mobil != null ? '${widget.mobil!.hargaSewa}' : '');
  late final _hargaAllIn = TextEditingController(text: (widget.mobil?.hargaAllIn ?? 0) > 0 ? '${widget.mobil!.hargaAllIn}' : '');
  int? _modelId;
  File? _gambar;

  bool get _ubah => widget.mobil != null;

  @override
  void initState() {
    super.initState();
    _modelId = widget.mobil?.masterMobil?.id;
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminMasterProvider>().muatReferensi());
  }

  @override
  void dispose() {
    for (final c in [_plat, _merk, _tahun, _harga, _hargaAllIn]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _pilihGambar() async {
    try {
      final x = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85, maxWidth: 1800);
      if (x != null && mounted) setState(() => _gambar = File(x.path));
    } catch (_) {
      if (mounted) tampilkanSnack(context, 'Gambar tidak dapat dipilih.', galat: true);
    }
  }

  Future<void> _simpan() async {
    if (!(_form.currentState?.validate() ?? false)) return;
    final prov = context.read<AdminMasterProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);

    final data = <String, dynamic>{
      'master_mobil_id': _modelId,
      'plat_nomor': _plat.text.trim().toUpperCase(),
      'merk': _merk.text.trim(),
      'tahun': int.parse(_tahun.text.trim()),
      'harga_sewa': int.parse(_harga.text.trim()),
      if (_hargaAllIn.text.trim().isNotEmpty) 'harga_all_in': int.parse(_hargaAllIn.text.trim()),
    };
    final h = await prov.simpanMobil(widget.mobil?.id, data, gambar: _gambar);
    if (h.galat == null && nav.mounted) nav.pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(h.galat ?? (_ubah ? 'Mobil diperbarui.' : 'Mobil ditambahkan.')),
        backgroundColor: h.galat == null ? Colors.green.shade700 : Colors.red.shade700,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminMasterProvider>();
    final model = prov.referensi.value.model;
    final submitting = prov.submitting;

    return HalamanForm(
      judul: _ubah ? 'Ubah mobil' : 'Tambah mobil',
      formKey: _form,
      aksi: PrimaryButton(
        label: _ubah ? 'Simpan perubahan' : 'Tambah mobil',
        loading: submitting,
        onPressed: _simpan,
      ),
      children: [
            DropdownButtonFormField<int>(
              // Kunci berubah saat daftar model selesai dimuat agar nilai awal dipasang ulang.
              key: ValueKey('model-${model.length}'),
              initialValue: model.any((m) => m.id == _modelId) ? _modelId : null,
              decoration: const InputDecoration(labelText: 'Model mobil'),
              items: [for (final m in model) DropdownMenuItem(value: m.id, child: Text(m.nama))],
              onChanged: submitting ? null : (v) => setState(() => _modelId = v),
              validator: (v) => v == null ? 'Pilih model mobil' : null,
              hint: Text(prov.referensi.loaded ? 'Pilih model' : 'Memuat...'),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _plat,
              enabled: !submitting,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(labelText: 'Plat nomor', hintText: 'AB 1234 CD'),
              validator: (v) => _regexPlat.hasMatch((v ?? '').trim().toUpperCase()) ? null : 'Format: AB 1234 CD (dipisah spasi)',
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _merk,
              enabled: !submitting,
              decoration: const InputDecoration(labelText: 'Merk'),
              validator: (v) => (v ?? '').trim().isEmpty ? 'Merk wajib diisi' : null,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _tahun,
              enabled: !submitting,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(4)],
              decoration: const InputDecoration(labelText: 'Tahun'),
              validator: (v) {
                final t = int.tryParse((v ?? '').trim());
                if (t == null || t < 1900 || t > DateTime.now().year) return 'Tahun 1900 - ${DateTime.now().year}';
                return null;
              },
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _harga,
              enabled: !submitting,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(labelText: 'Harga sewa per hari (Rp)', prefixText: 'Rp '),
              validator: (v) => int.tryParse((v ?? '').trim()) == null ? 'Isi harga dengan angka' : null,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _hargaAllIn,
              enabled: !submitting,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(labelText: 'Harga dengan sopir per hari (Rp)', prefixText: 'Rp '),
            ),
            const SizedBox(height: 16),
            if (_gambar != null)
              ClipRRect(borderRadius: BorderRadius.circular(22), child: Image.file(_gambar!, height: 180, width: double.infinity, fit: BoxFit.cover))
            else if (widget.mobil?.gambar != null && widget.mobil!.gambar!.isNotEmpty)
              ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: Image.network(widget.mobil!.gambar!, height: 180, width: double.infinity, fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const SizedBox(height: 60, child: Center(child: Text('Gambar tidak dapat dimuat')))),
              ),
            const SizedBox(height: 10),
            TombolFoto(label: _gambar == null ? 'Pilih gambar (opsional)' : 'Ganti gambar', onPressed: submitting ? null : _pilihGambar),
      ],
    );
  }
}
