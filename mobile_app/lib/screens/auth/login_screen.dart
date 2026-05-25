import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../home/home_screen.dart';

// ─── Brand colors matching the web (green-600 / green-700) ───
const _kGreen600 = Color(0xFF16A34A);
const _kGreen700 = Color(0xFF15803D);
const _kGreen50  = Color(0xFFF0FDF4);

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey   = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl  = TextEditingController();
  bool _obscure    = true;
  late AnimationController _fadeCtrl;
  late Animation<double>   _fadeAnim;

  @override
  void initState() {
    super.initState();
    _fadeCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 700));
    _fadeAnim = CurvedAnimation(parent: _fadeCtrl, curve: Curves.easeIn);
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
    MaterialPageRoute(builder: (_) => const HomeScreen()), (_) => false);

  void _showError(String msg) => ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(msg, style: GoogleFonts.outfit(fontSize: 13)),
      backgroundColor: AppColors.error,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ),
  );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: FadeTransition(
        opacity: _fadeAnim,
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 52),

                // ── Logo (matches web: circular with border) ───
                Center(
                  child: Container(
                    width: 84, height: 84,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      border: Border.all(color: _kGreen600, width: 2.5),
                      boxShadow: [BoxShadow(color: _kGreen600.withOpacity(0.25), blurRadius: 20, spreadRadius: 2)],
                    ),
                    child: ClipOval(
                      child: Padding(
                        padding: const EdgeInsets.all(12.0),
                        child: Image.asset('assets/images/favicon.png', fit: BoxFit.contain),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // ── Title (matches web: green-600) ─────────────
                Center(child: Text('Customer Login',
                  style: GoogleFonts.outfit(fontSize: 26, fontWeight: FontWeight.w800, color: _kGreen600))),
                const SizedBox(height: 4),
                Center(child: Text('Masuk ke akun Harkat Rental Anda',
                  style: GoogleFonts.outfit(fontSize: 13, color: AppColors.textSecondary))),
                const SizedBox(height: 40),

                // ── Card (matches web: white/95 bg, ring) ──────
                Container(
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: _kGreen600.withOpacity(0.15)),
                    boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.15), blurRadius: 24, offset: const Offset(0, 8))],
                  ),
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      // ── Email/Password Form ─────────────────
                      Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            _InputField(
                              controller: _emailCtrl,
                              label: 'Email',
                              emoji: '📧',
                              keyboardType: TextInputType.emailAddress,
                              validator: (v) => (v == null || !v.contains('@')) ? 'Email tidak valid' : null,
                            ),
                            const SizedBox(height: 14),
                            _InputField(
                              controller: _passCtrl,
                              label: 'Password',
                              emoji: '🔑',
                              obscure: _obscure,
                              suffix: IconButton(
                                icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                    color: AppColors.textSecondary, size: 20),
                                onPressed: () => setState(() => _obscure = !_obscure),
                              ),
                              validator: (v) => (v == null || v.length < 6) ? 'Password minimal 6 karakter' : null,
                            ),
                            const SizedBox(height: 8),

                            // Lupa password
                            Align(
                              alignment: Alignment.centerRight,
                              child: TextButton(
                                onPressed: () {},
                                style: TextButton.styleFrom(padding: EdgeInsets.zero, tapTargetSize: MaterialTapTargetSize.shrinkWrap),
                                child: Text('Lupa password?',
                                    style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
                              ),
                            ),
                            const SizedBox(height: 16),

                            // ── Login Button (green-600) ────────
                            Consumer<AuthProvider>(builder: (_, auth, __) =>
                              SizedBox(
                                width: double.infinity, height: 50,
                                child: ElevatedButton(
                                  onPressed: auth.status == AuthStatus.loading ? null : _submit,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: _kGreen600,
                                    foregroundColor: Colors.white,
                                    disabledBackgroundColor: _kGreen600.withOpacity(0.5),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                    elevation: 3,
                                    shadowColor: _kGreen600.withOpacity(0.4),
                                  ),
                                  child: auth.status == AuthStatus.loading
                                      ? const SizedBox(width: 22, height: 22,
                                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5))
                                      : Text('Login', style: GoogleFonts.outfit(fontSize: 15, fontWeight: FontWeight.w700)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 20),

                      // ── Divider ────────────────────────────────
                      Row(children: [
                        const Expanded(child: Divider(color: Color(0xFF334155))),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          child: Text('atau', style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary)),
                        ),
                        const Expanded(child: Divider(color: Color(0xFF334155))),
                      ]),

                      const SizedBox(height: 16),

                      // ── Google Login Button (matches web style) ─
                      Consumer<AuthProvider>(builder: (_, auth, __) =>
                        SizedBox(
                          width: double.infinity, height: 50,
                          child: OutlinedButton(
                            onPressed: auth.status == AuthStatus.loading ? null : _googleLogin,
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF1E293B),
                              backgroundColor: Colors.white,
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: auth.status == AuthStatus.loading
                                ? const SizedBox(width: 22, height: 22,
                                    child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.grey))
                                : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                                    // Google logo SVG paths
                                    SizedBox(width: 20, height: 20, child: CustomPaint(painter: _GoogleLogoPainter())),
                                    const SizedBox(width: 10),
                                    Text('Login via Google',
                                      style: GoogleFonts.outfit(fontSize: 14, fontWeight: FontWeight.w600, color: const Color(0xFF374151))),
                                  ]),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 32),

                // ── Info note ─────────────────────────────────
                Center(
                  child: Text(
                    'Akun dibuat otomatis saat login pertama via Google',
                    style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textHint),
                    textAlign: TextAlign.center,
                  ),
                ),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  Reusable styled input field
// ─────────────────────────────────────────────────────────────
class _InputField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String emoji;
  final bool obscure;
  final Widget? suffix;
  final String? Function(String?)? validator;
  final TextInputType keyboardType;

  const _InputField({
    required this.controller,
    required this.label,
    required this.emoji,
    this.obscure       = false,
    this.suffix,
    this.validator,
    this.keyboardType  = TextInputType.text,
  });

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: GoogleFonts.outfit(fontSize: 12, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
      const SizedBox(height: 6),
      TextFormField(
        controller:   controller,
        obscureText:  obscure,
        keyboardType: keyboardType,
        style:        GoogleFonts.outfit(color: AppColors.textPrimary, fontSize: 14),
        validator:    validator,
        decoration: InputDecoration(
          prefixIcon:  Padding(padding: const EdgeInsets.only(left: 12, right: 8), child: Text(emoji, style: const TextStyle(fontSize: 16))),
          prefixIconConstraints: const BoxConstraints(minWidth: 40, minHeight: 40),
          suffixIcon:  suffix,
          filled:      true,
          fillColor:   AppColors.background,
          contentPadding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
          border:      OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFF334155))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: _kGreen600, width: 2)),
          errorBorder:   OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.error)),
          focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.error, width: 2)),
        ),
      ),
    ],
  );
}

// ─────────────────────────────────────────────────────────────
//  Google G logo painted with 4 colors (matching web SVG)
// ─────────────────────────────────────────────────────────────
class _GoogleLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final r = size.width / 2;
    // Blue arc
    final paint = Paint()..style = PaintingStyle.stroke..strokeWidth = size.width * 0.18;
    paint.color = const Color(0xFF4285F4);
    canvas.drawArc(Rect.fromCircle(center: Offset(r, r), radius: r * 0.82),
        -0.3, 2.0, false, paint);
    paint.color = const Color(0xFF34A853);
    canvas.drawArc(Rect.fromCircle(center: Offset(r, r), radius: r * 0.82),
        1.7, 1.0, false, paint);
    paint.color = const Color(0xFFFBBC05);
    canvas.drawArc(Rect.fromCircle(center: Offset(r, r), radius: r * 0.82),
        2.7, 0.8, false, paint);
    paint.color = const Color(0xFFEA4335);
    canvas.drawArc(Rect.fromCircle(center: Offset(r, r), radius: r * 0.82),
        3.5, 1.0, false, paint);
    // Horizontal bar of G
    final barPaint = Paint()..color = const Color(0xFF4285F4)..style = PaintingStyle.fill;
    canvas.drawRect(Rect.fromLTWH(r, r - size.height * 0.12, r * 0.82, size.height * 0.24), barPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
