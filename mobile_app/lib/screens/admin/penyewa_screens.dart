import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/master_model.dart';
import '../../providers/admin_master_provider.dart';
import 'widgets/admin_widgets.dart';

/// Data penyewa (pelanggan): cari, tambah, dan ubah.
class PenyewaListScreen extends StatefulWidget {
  const PenyewaListScreen({super.key});

  @override
  State<PenyewaListScreen> createState() => _PenyewaListScreenState();
}

class _PenyewaListScreenState extends State<PenyewaListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminMasterProvider>().muatPenyewa());
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminMasterProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PenyewaFormScreen())),
        icon: const Icon(Icons.person_add_alt_1_rounded),
        label: const Text('Tambah penyewa'),
      ),
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const ScreenHeader('Penyewa'),
        KotakCari(hint: 'Cari nama, email, atau no. HP', awal: prov.kataKunciPenyewa, onCari: prov.aturKataKunciPenyewa),
        Expanded(
          child: DaftarBerhalaman<PenyewaModel>(
            halaman: prov.penyewa,
            onRefresh: prov.muatPenyewa,
            onLagi: prov.penyewaLagi,
            bawah: 96,
            empty: const EmptyState(icon: Icons.people_outline_rounded, title: 'Belum ada penyewa', subtitle: 'Pelanggan yang mendaftar atau dicatat admin tampil di sini.'),
            itemBuilder: (ctx, p) => InkWell(
              borderRadius: BorderRadius.circular(22),
              onTap: () => Navigator.of(ctx).push(MaterialPageRoute(builder: (_) => PenyewaFormScreen(penyewa: p))),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
                child: Row(children: [
                  const CircleAvatar(backgroundColor: AppColors.primarySoft, foregroundColor: AppColors.primary, child: Icon(Icons.person_rounded)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(p.name, style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700)),
                      Text(p.email, style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary)),
                      if (p.noHp != null) Text(p.noHp!, style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary)),
                    ]),
                  ),
                  if (p.jumlahPesanan != null)
                    StatusPill(label: '${p.jumlahPesanan} pesanan', color: AppColors.info),
                ]),
              ),
            ),
          ),
        ),
      ]))),
    );
  }
}

/// Form tambah / ubah penyewa. Penyewa yang dicatat admin tidak mendapat kata sandi yang diketahui.
class PenyewaFormScreen extends StatefulWidget {
  final PenyewaModel? penyewa;
  const PenyewaFormScreen({super.key, this.penyewa});

  @override
  State<PenyewaFormScreen> createState() => _PenyewaFormScreenState();
}

class _PenyewaFormScreenState extends State<PenyewaFormScreen> {
  final _form = GlobalKey<FormState>();
  late final _nama = TextEditingController(text: widget.penyewa?.name ?? '');
  late final _email = TextEditingController(text: widget.penyewa?.email ?? '');
  late final _noHp = TextEditingController(text: widget.penyewa?.noHp ?? '');
  late final _alamat = TextEditingController(text: widget.penyewa?.alamat ?? '');
  late final _kota = TextEditingController(text: widget.penyewa?.asalKota ?? '');

  bool get _ubah => widget.penyewa != null;

  @override
  void dispose() {
    for (final c in [_nama, _email, _noHp, _alamat, _kota]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _simpan() async {
    if (!(_form.currentState?.validate() ?? false)) return;
    final prov = context.read<AdminMasterProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);

    final h = await prov.simpanPenyewa(widget.penyewa?.id, {
      'name': _nama.text.trim(),
      'email': _email.text.trim().toLowerCase(),
      'no_hp': _noHp.text.trim(),
      'alamat': _alamat.text.trim(),
      'asal_kota': _kota.text.trim(),
    });
    if (h.galat == null && nav.mounted) nav.pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(h.galat ?? (_ubah ? 'Data penyewa diperbarui.' : 'Penyewa ditambahkan.')),
        backgroundColor: h.galat == null ? AppColors.success : AppColors.error,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final submitting = context.select<AdminMasterProvider, bool>((p) => p.submitting);
    String? wajib(String? v, String nama) => (v ?? '').trim().isEmpty ? '$nama wajib diisi' : null;

    return HalamanForm(
      judul: _ubah ? 'Ubah penyewa' : 'Tambah penyewa',
      formKey: _form,
      aksi: PrimaryButton(
        label: _ubah ? 'Simpan perubahan' : 'Tambah penyewa',
        loading: submitting,
        onPressed: _simpan,
      ),
      children: [
          TextFormField(controller: _nama, enabled: !submitting, decoration: const InputDecoration(labelText: 'Nama'), validator: (v) => wajib(v, 'Nama')),
          const SizedBox(height: 14),
          TextFormField(
            controller: _email,
            enabled: !submitting,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(labelText: 'Email (invoice dikirim ke sini)'),
            validator: (v) => RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch((v ?? '').trim()) ? null : 'Email tidak valid',
          ),
          const SizedBox(height: 14),
          TextFormField(controller: _noHp, enabled: !submitting, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'No. HP'), validator: (v) => wajib(v, 'No. HP')),
          const SizedBox(height: 14),
          TextFormField(controller: _alamat, enabled: !submitting, maxLines: 2, decoration: const InputDecoration(labelText: 'Alamat'), validator: (v) => wajib(v, 'Alamat')),
          const SizedBox(height: 14),
          TextFormField(controller: _kota, enabled: !submitting, decoration: const InputDecoration(labelText: 'Asal kota'), validator: (v) => wajib(v, 'Asal kota')),
      ],
    );
  }
}
