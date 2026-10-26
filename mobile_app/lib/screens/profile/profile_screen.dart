import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../providers/auth_provider.dart';
import '../auth/login_screen.dart';

class ProfileScreen extends StatefulWidget {
  /// Dibuka langsung dalam mode edit (mis. dari dialog "Lengkapi Profil").
  final bool startEditing;
  const ProfileScreen({super.key, this.startEditing = false});
  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _editing = false;
  final _nameCtrl   = TextEditingController();
  final _emailCtrl  = TextEditingController();
  final _noHpCtrl   = TextEditingController();
  final _alamatCtrl = TextEditingController();
  final _kotaCtrl   = TextEditingController();

  @override
  void initState() {
    super.initState();
    _editing = widget.startEditing;
    WidgetsBinding.instance.addPostFrameCallback((_) => _prefill());
  }

  void _prefill() {
    final user = context.read<AuthProvider>().user;
    if (user == null) return;
    _nameCtrl.text   = user.name;
    _emailCtrl.text  = user.email;
    _noHpCtrl.text   = user.noHp   ?? '';
    _alamatCtrl.text = user.alamat  ?? '';
    _kotaCtrl.text   = user.asalKota ?? '';
  }

  Future<void> _save() async {
    final auth = context.read<AuthProvider>();

    if (_nameCtrl.text.trim().isEmpty) {
      _snack('Nama tidak boleh kosong', error: true);
      return;
    }

    // Kirim hanya field yang terisi: field kosong akan dibaca server sebagai null dan ditolak (422).
    final data = <String, dynamic>{
      'name':      _nameCtrl.text.trim(),
      'no_hp':     _noHpCtrl.text.trim(),
      'alamat':    _alamatCtrl.text.trim(),
      'asal_kota': _kotaCtrl.text.trim(),
    }..removeWhere((_, v) => v is String && v.isEmpty);

    final ok = await auth.updateProfile(data);
    if (!mounted) return;
    if (ok) {
      setState(() => _editing = false);
      _snack('Profil berhasil diperbarui');
    } else {
      _snack(auth.error, error: true);
    }
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: error ? AppColors.error : AppColors.success,
    ));
  }

  Future<void> _logout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Konfirmasi Logout'),
        content: const Text('Apakah Anda yakin ingin keluar?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(foregroundColor: AppColors.muted),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: AppColors.error, minimumSize: const Size(64, 44)),
            child: const Text('Logout'),
          ),
        ],
      ),
    );
    if (confirmed == true && mounted) {
      await context.read<AuthProvider>().logout();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
      }
    }
  }

  @override
  void dispose() {
    for (final c in [_nameCtrl, _emailCtrl, _noHpCtrl, _alamatCtrl, _kotaCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user   = context.watch<AuthProvider>().user;
    final gutter = screenGutter(context);
    final t      = GoogleFonts.plusJakartaSans;
    final bisaKembali = Navigator.of(context).canPop();

    final aksi = TextButton(
      onPressed: () => _editing ? _save() : setState(() => _editing = true),
      style: TextButton.styleFrom(minimumSize: const Size(56, 48)),
      child: Text(_editing ? 'Simpan' : 'Edit', style: t(fontSize: 14, fontWeight: FontWeight.w700)),
    );

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(
          max: 720,
          child: Column(
            children: [
              // Dibuka dari dialog "Lengkapi Profil" → ada tombol kembali; sebagai tab → judul biasa.
              if (bisaKembali)
                Padding(
                  padding: EdgeInsets.fromLTRB(gutter - 4, 8, gutter - 8, 8),
                  child: Row(children: [
                    const BackCircleButton(),
                    const SizedBox(width: 8),
                    Expanded(child: Text('Profil Saya', style: t(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.ink))),
                    aksi,
                  ]),
                )
              else
                TabHeader('Profil Saya', trailing: aksi),
              Expanded(
                child: ListView(
                  padding: EdgeInsets.fromLTRB(gutter, 8, gutter, 24),
                  children: [
                    // ── Identitas ────────────────────────────
                    Center(
                      child: Container(
                        width: 96, height: 96,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                          boxShadow: AppColors.floatShadow,
                        ),
                        child: Text(
                          user?.name.isNotEmpty == true ? user!.name[0].toUpperCase() : 'U',
                          style: t(fontSize: 38, fontWeight: FontWeight.w800, color: Colors.white),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Center(child: Text(user?.name ?? '', textAlign: TextAlign.center, style: t(fontSize: 21, fontWeight: FontWeight.w800, color: AppColors.ink))),
                    const SizedBox(height: 2),
                    Center(child: Text(user?.email ?? '', style: t(fontSize: 13, color: AppColors.muted))),
                    if (!(user?.isProfileComplete ?? true)) ...[
                      const SizedBox(height: 16),
                      const InfoBanner('Lengkapi profil Anda untuk bisa melakukan booking', tone: BannerTone.warning),
                    ],
                    const SizedBox(height: 24),

                    // ── Data diri ────────────────────────────
                    SoftCard(
                      padding: const EdgeInsets.all(18),
                      child: Column(children: [
                        AppTextField(controller: _nameCtrl, label: 'Nama Lengkap', icon: Icons.person_outline_rounded, readOnly: !_editing),
                        const SizedBox(height: 16),
                        AppTextField(controller: _emailCtrl, label: 'Email', icon: Icons.mail_outline_rounded, readOnly: true),
                        const SizedBox(height: 16),
                        AppTextField(controller: _noHpCtrl, label: 'No. HP', icon: Icons.phone_outlined, readOnly: !_editing, keyboardType: TextInputType.phone),
                        const SizedBox(height: 16),
                        AppTextField(controller: _kotaCtrl, label: 'Asal Kota', icon: Icons.location_city_outlined, readOnly: !_editing),
                        const SizedBox(height: 16),
                        AppTextField(controller: _alamatCtrl, label: 'Alamat', icon: Icons.home_outlined, readOnly: !_editing, maxLines: 3),
                      ]),
                    ),
                    const SizedBox(height: 20),

                    // ── Keluar ───────────────────────────────
                    SizedBox(
                      width: double.infinity,
                      height: 54,
                      child: OutlinedButton.icon(
                        onPressed: _logout,
                        icon: const Icon(Icons.logout_rounded, color: AppColors.error, size: 20),
                        label: Text('Keluar dari Akun', style: t(color: AppColors.error, fontWeight: FontWeight.w700, fontSize: 14.5)),
                        style: OutlinedButton.styleFrom(
                          backgroundColor: AppColors.errorSoft,
                          side: BorderSide.none,
                          shape: const StadiumBorder(),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
