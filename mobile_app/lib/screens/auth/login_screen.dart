import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/widgets/app_widgets.dart';
import '../../providers/auth_provider.dart';
import '../role_shell.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _formKey   = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl  = TextEditingController();
  bool _obscure    = true;
  late AnimationController _fadeCtrl;
  late Animation<double>   _fadeAnim;
  late Animation<Offset>   _slideAnim;

  @override
  void initState() {
    super.initState();
    _fadeCtrl  = AnimationController(vsync: this, duration: const Duration(milliseconds: 600));
    _fadeAnim  = CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(begin: const Offset(0, 0.03), end: Offset.zero)
        .animate(CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeOutCubic));
    _fadeCtrl.forward();
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    _fadeCtrl.dispose();
    super.dispose();
  }

  // ── Email/Password Login ───────────────────────────────────
  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final auth = context.read<AuthProvider>();
    final ok   = await auth.login(
      email:    _emailCtrl.text.trim(),
      password: _passCtrl.text,
    );
    if (!mounted) return;
    if (ok) {
      _goHome();
    } else {
      _showError(auth.error);
    }
  }

  // ── Google Login ───────────────────────────────────────────
  Future<void> _googleLogin() async {
    final auth = context.read<AuthProvider>();
    final ok   = await auth.loginWithGoogle();
    if (!mounted) return;
    if (ok) {
      _goHome();
    } else if (auth.error.isNotEmpty) {
      _showError(auth.error);
    }
  }

  void _goHome() => Navigator.of(context).pushAndRemoveUntil(
    MaterialPageRoute(builder: (_) => shellFor(context.read<AuthProvider>().user)), (_) => false);

  void _showError(String msg) => ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(msg),
      backgroundColor: AppColors.error,
      margin: const EdgeInsets.all(16),
    ),
  );

  @override
  Widget build(BuildContext context) {
    final gutter = screenGutter(context);
    final text   = GoogleFonts.plusJakartaSans;

    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: FadeTransition(
          opacity: _fadeAnim,
          child: SlideTransition(
            position: _slideAnim,
            child: Center(
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(gutter + 4, 24, gutter + 4, 32),
                child: ContentWidth(
                  max: 440,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ── Foto hero (disembunyikan di layar pendek agar form tetap terlihat) ──
                      if (MediaQuery.sizeOf(context).height >= 700) ...[
                      ClipRRect(
                        borderRadius: BorderRadius.circular(24),
                        child: AspectRatio(
                          aspectRatio: 16 / 10,
                          child: Stack(fit: StackFit.expand, children: [
                            Image.asset('assets/images/hero_mobil.jpg', fit: BoxFit.cover, cacheWidth: 900),
                            Positioned(
                              left: 14, top: 14,
                              child: Container(
                                width: 44, height: 44,
                                decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.white),
                                child: ClipOval(child: Image.asset('assets/images/favicon.png', fit: BoxFit.cover)),
                              ),
                            ),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 28),
                      ],

                      // ── Judul dua warna ─────────────────────
                      Text('Masuk ke',
                          style: text(fontSize: 32, height: 1.1, fontWeight: FontWeight.w800, color: AppColors.ink)),
                      Text('Harkat Rental',
                          style: text(fontSize: 32, height: 1.1, fontWeight: FontWeight.w800, color: AppColors.ink)),
                      const SizedBox(height: 10),
                      Text('Sewa mobil di Yogyakarta, langsung dari aplikasi.',
                          style: text(fontSize: 14, height: 1.45, fontWeight: FontWeight.w500, color: AppColors.muted)),
                      const SizedBox(height: 32),

                      // ── Form ────────────────────────────────
                      Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            AppTextField(
                              controller: _emailCtrl,
                              label: 'Email',
                              hintText: 'nama@email.com',
                              icon: Icons.mail_outline_rounded,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              validator: (v) => (v == null || !v.contains('@')) ? 'Email tidak valid' : null,
                            ),
                            const SizedBox(height: 16),
                            AppTextField(
                              controller: _passCtrl,
                              label: 'Password',
                              hintText: 'Minimal 6 karakter',
                              icon: Icons.lock_outline_rounded,
                              obscure: _obscure,
                              textInputAction: TextInputAction.done,
                              onSubmitted: _submit,
                              suffix: IconButton(
                                tooltip: _obscure ? 'Tampilkan password' : 'Sembunyikan password',
                                icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                    color: AppColors.hint, size: 20),
                                onPressed: () => setState(() => _obscure = !_obscure),
                              ),
                              validator: (v) => (v == null || v.length < 6) ? 'Password minimal 6 karakter' : null,
                            ),
                            const SizedBox(height: 24),
                            Selector<AuthProvider, bool>(
                              selector: (_, a) => a.status == AuthStatus.loading,
                              builder: (_, loading, __) => PrimaryButton(
                                label: 'Masuk',
                                trailingIcon: Icons.arrow_forward_rounded,
                                loading: loading,
                                onPressed: _submit,
                              ),
                            ),
                          ],
                        ),
                      ),

                      // ── Pemisah ─────────────────────────────
                      const SizedBox(height: 24),
                      Row(children: [
                        const Expanded(child: Divider()),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          child: Text('atau', style: text(fontSize: 12, fontWeight: FontWeight.w500, color: AppColors.muted)),
                        ),
                        const Expanded(child: Divider()),
                      ]),
                      const SizedBox(height: 24),

                      // ── Google ──────────────────────────────
                      Selector<AuthProvider, bool>(
                        selector: (_, a) => a.status == AuthStatus.loading,
                        builder: (_, loading, __) => SecondaryButton(
                          onPressed: loading ? null : _googleLogin,
                          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                            SizedBox(width: 20, height: 20, child: CustomPaint(painter: _GoogleLogoPainter())),
                            const SizedBox(width: 10),
                            Text('Lanjutkan dengan Google',
                                style: text(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 28),

                      Center(
                        child: TextButton(
                          onPressed: () => Navigator.of(context).pushAndRemoveUntil(
                              MaterialPageRoute(builder: (_) => shellFor(null)), (_) => false),
                          child: Text('Lihat katalog tanpa masuk',
                              style: text(fontSize: 13, fontWeight: FontWeight.w600)),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Center(
                        child: Text(
                          'Akun pelanggan dibuat otomatis saat login pertama dengan Google.',
                          style: text(fontSize: 12, height: 1.45, color: AppColors.muted),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  Logo "G" Google dilukis dengan empat warna
// ─────────────────────────────────────────────────────────────
class _GoogleLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final r = size.width / 2;
    final paint = Paint()..style = PaintingStyle.stroke..strokeWidth = size.width * 0.18;
    final rect = Rect.fromCircle(center: Offset(r, r), radius: r * 0.82);
    paint.color = const Color(0xFF4285F4);
    canvas.drawArc(rect, -0.3, 2.0, false, paint);
    paint.color = const Color(0xFF34A853);
    canvas.drawArc(rect, 1.7, 1.0, false, paint);
    paint.color = const Color(0xFFFBBC05);
    canvas.drawArc(rect, 2.7, 0.8, false, paint);
    paint.color = const Color(0xFFEA4335);
    canvas.drawArc(rect, 3.5, 1.0, false, paint);
    final barPaint = Paint()..color = const Color(0xFF4285F4)..style = PaintingStyle.fill;
    canvas.drawRect(Rect.fromLTWH(r, r - size.height * 0.12, r * 0.82, size.height * 0.24), barPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
