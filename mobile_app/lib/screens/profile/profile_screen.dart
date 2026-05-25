import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../auth/login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});
  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _editing = false;
  final _nameCtrl   = TextEditingController();
  final _noHpCtrl   = TextEditingController();
  final _alamatCtrl = TextEditingController();
  final _kotaCtrl   = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _prefill());
  }

  void _prefill() {
    final user = context.read<AuthProvider>().user;
    if (user == null) return;
    _nameCtrl.text   = user.name;
    _noHpCtrl.text   = user.noHp   ?? '';
    _alamatCtrl.text = user.alamat  ?? '';
    _kotaCtrl.text   = user.asalKota ?? '';
  }

  Future<void> _save() async {
    final auth = context.read<AuthProvider>();
    final ok   = await auth.updateProfile({
      'name':      _nameCtrl.text.trim(),
      'no_hp':     _noHpCtrl.text.trim(),
      'alamat':    _alamatCtrl.text.trim(),
      'asal_kota': _kotaCtrl.text.trim(),
    });
    if (!mounted) return;
    if (ok) {
      setState(() => _editing = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('Profil berhasil diperbarui'),
        backgroundColor: AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(auth.error),
        backgroundColor: AppColors.error,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ));
    }
  }

  Future<void> _logout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: AppColors.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('Konfirmasi Logout', style: GoogleFonts.outfit(color: AppColors.textPrimary, fontWeight: FontWeight.w700)),
        content: Text('Apakah Anda yakin ingin keluar?', style: GoogleFonts.outfit(color: AppColors.textSecondary)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text('Batal', style: GoogleFonts.outfit(color: AppColors.textSecondary))),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            child: Text('Logout', style: GoogleFonts.outfit(color: Colors.white, fontWeight: FontWeight.w700)),
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
    for (final c in [_nameCtrl, _noHpCtrl, _alamatCtrl, _kotaCtrl]) c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: Text('Profil Saya', style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        actions: [
          TextButton(
            onPressed: () {
              if (_editing) {
                _save();
              } else {
                setState(() => _editing = true);
              }
            },
            child: Text(_editing ? 'Simpan' : 'Edit', style: GoogleFonts.outfit(color: AppColors.accent, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // ── Avatar ──────────────────────────────────────
          Center(
            child: Stack(
              children: [
                Container(
                  width: 90, height: 90,
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: AppColors.primary.withOpacity(0.4), blurRadius: 20, spreadRadius: 2)],
                  ),
                  child: Center(child: Text(
                    user?.name.isNotEmpty == true ? user!.name[0].toUpperCase() : 'U',
                    style: GoogleFonts.outfit(fontSize: 36, fontWeight: FontWeight.w800, color: Colors.white),
                  )),
                ),
                if (_editing)
                  Positioned(bottom: 0, right: 0, child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: const BoxDecoration(color: AppColors.accent, shape: BoxShape.circle),
                    child: const Icon(Icons.camera_alt_rounded, size: 14, color: Colors.white),
                  )),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Center(child: Text(user?.name ?? '', style: GoogleFonts.outfit(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.textPrimary))),
          Center(child: Text(user?.email ?? '', style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary))),
          if (!(user?.isProfileComplete ?? true)) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(color: AppColors.warning.withOpacity(0.1), borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.warning.withOpacity(0.4))),
              child: Row(children: [
                const Icon(Icons.warning_amber_rounded, color: AppColors.warning, size: 16),
                const SizedBox(width: 8),
                Expanded(child: Text('Lengkapi profil Anda untuk bisa melakukan booking', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.warning))),
              ]),
            ),
          ],
          const SizedBox(height: 28),

          // ── Fields ──────────────────────────────────────
          _ProfileField(icon: Icons.person_outline,       label: 'Nama Lengkap', ctrl: _nameCtrl,   editing: _editing),
          const SizedBox(height: 14),
          _ProfileField(icon: Icons.email_outlined,       label: 'Email',        ctrl: TextEditingController(text: user?.email ?? ''), editing: false, readonly: true),
          const SizedBox(height: 14),
          _ProfileField(icon: Icons.phone_outlined,       label: 'No. HP',       ctrl: _noHpCtrl,   editing: _editing, keyboardType: TextInputType.phone),
          const SizedBox(height: 14),
          _ProfileField(icon: Icons.location_city_outlined, label: 'Asal Kota', ctrl: _kotaCtrl,   editing: _editing),
          const SizedBox(height: 14),
          _ProfileField(icon: Icons.home_outlined,        label: 'Alamat',       ctrl: _alamatCtrl, editing: _editing, maxLines: 3),
          const SizedBox(height: 32),

          // ── Logout ──────────────────────────────────────
          SizedBox(
            width: double.infinity, height: 52,
            child: OutlinedButton.icon(
              onPressed: _logout,
              icon: const Icon(Icons.logout_rounded, color: AppColors.error),
              label: Text('Keluar dari Akun', style: GoogleFonts.outfit(color: AppColors.error, fontWeight: FontWeight.w700, fontSize: 15)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: AppColors.error),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }
}

class _ProfileField extends StatelessWidget {
  final IconData icon;
  final String label;
  final TextEditingController ctrl;
  final bool editing;
  final bool readonly;
  final TextInputType keyboardType;
  final int maxLines;

  const _ProfileField({
    required this.icon,
    required this.label,
    required this.ctrl,
    required this.editing,
    this.readonly     = false,
    this.keyboardType = TextInputType.text,
    this.maxLines     = 1,
  });

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: GoogleFonts.outfit(fontSize: 11, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
      const SizedBox(height: 6),
      TextFormField(
        controller:   ctrl,
        readOnly:     readonly || !editing,
        maxLines:     maxLines,
        keyboardType: keyboardType,
        style:        GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14, fontWeight: FontWeight.w500),
        decoration: InputDecoration(
          prefixIcon: Icon(icon, color: editing && !readonly ? AppColors.primaryLight : AppColors.textHint, size: 18),
          filled:     true,
          fillColor:  editing && !readonly ? AppColors.surface : AppColors.background,
          contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
          border:     OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: editing && !readonly ? AppColors.border : Colors.transparent)),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.primaryLight, width: 2)),
        ),
      ),
    ],
  );
}
