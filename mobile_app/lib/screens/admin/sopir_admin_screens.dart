import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../core/widgets/state_views.dart';
import '../../models/master_model.dart';
import '../../providers/admin_master_provider.dart';
import 'widgets/admin_widgets.dart';

/// Daftar sopir: cari, filter status, tambah, dan ubah.
class SopirAdminListScreen extends StatefulWidget {
  const SopirAdminListScreen({super.key});

  @override
  State<SopirAdminListScreen> createState() => _SopirAdminListScreenState();
}

class _SopirAdminListScreenState extends State<SopirAdminListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminMasterProvider>().muatSupir());
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<AdminMasterProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SopirFormScreen())),
        icon: const Icon(Icons.person_add_alt_1_rounded),
        label: const Text('Tambah sopir'),
      ),
      body: SafeArea(bottom: false, child: ContentWidth(max: 720, child: Column(children: [
        const ScreenHeader('Sopir'),
        KotakCari(hint: 'Cari nama atau no. HP', awal: prov.kataKunciSupir, onCari: (q) => prov.aturFilterSupir(kataKunci: q)),
        BarisFilter<int>(
          pilihan: const [('Semua', null), ('Tersedia', 1), ('Bertugas', 2), ('Libur', 0)],
          terpilih: prov.filterStatusSupir,
          onPilih: (s) => prov.aturFilterSupir(status: s, hapusStatus: s == null),
        ),
        Expanded(
          child: DaftarBerhalaman<SupirAdminModel>(
            halaman: prov.supirs,
            onRefresh: prov.muatSupir,
            onLagi: prov.supirLagi,
            bawah: 96,
            empty: const EmptyState(icon: Icons.badge_outlined, title: 'Belum ada sopir', subtitle: 'Ketuk "Tambah sopir" untuk memulai.'),
            itemBuilder: (ctx, s) => _KartuSopir(s),
          ),
        ),
      ]))),
    );
  }
}

class _KartuSopir extends StatelessWidget {
  final SupirAdminModel s;
  const _KartuSopir(this.s);

  @override
  Widget build(BuildContext context) {
    final warna = s.bertugas ? AppColors.statusOngoing : (s.status == 1 ? AppColors.success : AppColors.muted);
    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => SopirFormScreen(sopir: s))),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
        child: Row(children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: AppColors.primarySoft,
            foregroundColor: AppColors.primary,
            backgroundImage: (s.gambar != null && s.gambar!.isNotEmpty) ? NetworkImage(s.gambar!) : null,
            child: (s.gambar == null || s.gambar!.isEmpty) ? const Icon(Icons.person_rounded) : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(s.nama, style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700)),
              if (s.noHp != null) Text(s.noHp!, style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary)),
              const SizedBox(height: 4),
              Wrap(spacing: 6, children: [
                StatusPill(label: s.statusText.isEmpty ? 'Status ${s.status}' : s.statusText, color: warna),
                if (!s.aktif) const StatusPill(label: 'Nonaktif', color: AppColors.error),
              ]),
            ]),
          ),
          if (s.noHp != null && s.noHp!.isNotEmpty)
            IconButton(tooltip: 'Telepon', icon: const Icon(Icons.call_rounded), onPressed: () => launchUrl(Uri(scheme: 'tel', path: s.noHp))),
        ]),
      ),
    );
  }
}

/// Form tambah / ubah sopir. Akun sopir yang dibuat admin langsung aktif dan dapat login.
class SopirFormScreen extends StatefulWidget {
  final SupirAdminModel? sopir;
  const SopirFormScreen({super.key, this.sopir});

  @override
  State<SopirFormScreen> createState() => _SopirFormScreenState();
}

class _SopirFormScreenState extends State<SopirFormScreen> {
  final _form = GlobalKey<FormState>();
  late final _nama = TextEditingController(text: widget.sopir?.nama ?? '');
  late final _email = TextEditingController(text: widget.sopir?.email ?? '');
  late final _password = TextEditingController();
  late final _noHp = TextEditingController(text: widget.sopir?.noHp ?? '');
  late final _alamat = TextEditingController(text: widget.sopir?.alamat ?? '');
  late bool _aktif = widget.sopir?.aktif ?? true;
  File? _gambar;

  bool get _ubah => widget.sopir != null;

  @override
  void dispose() {
    for (final c in [_nama, _email, _password, _noHp, _alamat]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _pilihGambar() async {
    try {
      final x = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80, maxWidth: 1200);
      if (x != null && mounted) setState(() => _gambar = File(x.path));
    } catch (_) {
      if (mounted) tampilkanSnack(context, 'Foto tidak dapat dipilih.', galat: true);
    }
  }

  Future<void> _simpan() async {
    if (!(_form.currentState?.validate() ?? false)) return;
    final prov = context.read<AdminMasterProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);

    if (_ubah && !_aktif && (widget.sopir?.aktif ?? true)) {
      final ya = await konfirmasi(context,
          judul: 'Nonaktifkan sopir?',
          isi: '${_nama.text.trim()} tidak dapat login lagi dan sesinya diakhiri.',
          tombol: 'Nonaktifkan',
          warna: AppColors.error);
      if (!ya) return;
    }

    final data = <String, dynamic>{
      'name': _nama.text.trim(),
      'email': _email.text.trim().toLowerCase(),
      'no_hp': _noHp.text.trim(),
      'alamat': _alamat.text.trim(),
      if (_password.text.isNotEmpty) 'password': _password.text,
      if (_ubah) 'aktif': _aktif,
    };
    final h = await prov.simpanSupir(widget.sopir?.id, data, gambar: _gambar);
    if (h.galat == null && nav.mounted) nav.pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(h.galat ?? (_ubah ? 'Data sopir diperbarui.' : 'Sopir ditambahkan.')),
        backgroundColor: h.galat == null ? AppColors.success : AppColors.error,
      ));
  }

  @override
  Widget build(BuildContext context) {
    final submitting = context.select<AdminMasterProvider, bool>((p) => p.submitting);

    return HalamanForm(
      judul: _ubah ? 'Ubah sopir' : 'Tambah sopir',
      formKey: _form,
      aksi: PrimaryButton(
        label: _ubah ? 'Simpan perubahan' : 'Tambah sopir',
        loading: submitting,
        onPressed: _simpan,
      ),
      children: [
          TextFormField(
            controller: _nama,
            enabled: !submitting,
            decoration: const InputDecoration(labelText: 'Nama'),
            validator: (v) => (v ?? '').trim().isEmpty ? 'Nama wajib diisi' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _email,
            enabled: !submitting,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(labelText: 'Email (untuk login)'),
            validator: (v) => RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch((v ?? '').trim()) ? null : 'Email tidak valid',
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _password,
            enabled: !submitting,
            obscureText: true,
            decoration: InputDecoration(labelText: _ubah ? 'Kata sandi baru (kosongkan bila tidak diubah)' : 'Kata sandi'),
            validator: (v) {
              final t = v ?? '';
              if (!_ubah && t.length < 8) return 'Minimal 8 karakter';
              if (_ubah && t.isNotEmpty && t.length < 8) return 'Minimal 8 karakter';
              return null;
            },
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _noHp,
            enabled: !submitting,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(labelText: 'No. HP'),
            validator: (v) => (v ?? '').trim().isEmpty ? 'No. HP wajib diisi' : null,
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _alamat,
            enabled: !submitting,
            maxLines: 2,
            decoration: const InputDecoration(labelText: 'Alamat'),
            validator: (v) => (v ?? '').trim().isEmpty ? 'Alamat wajib diisi' : null,
          ),
          if (_ubah) ...[
            const SizedBox(height: 14),
            SoftCard(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              child: SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Akun aktif'),
                subtitle: const Text('Nonaktif = tidak dapat login'),
                value: _aktif,
                onChanged: submitting ? null : (v) => setState(() => _aktif = v),
              ),
            ),
          ] else ...[
            const SizedBox(height: 14),
            if (_gambar != null) ...[
              ClipRRect(borderRadius: BorderRadius.circular(22), child: Image.file(_gambar!, height: 140, width: double.infinity, fit: BoxFit.cover)),
              const SizedBox(height: 10),
            ],
            TombolFoto(label: _gambar == null ? 'Foto (opsional)' : 'Ganti foto', onPressed: submitting ? null : _pilihGambar),
          ],
      ],
    );
  }
}
