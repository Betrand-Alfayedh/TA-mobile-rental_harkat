import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';

/// Lebar konten maksimum agar tampilan tetap rapi di tablet (design.md bagian 7).
class ContentWidth extends StatelessWidget {
  final Widget child;
  final double max;
  const ContentWidth({super.key, required this.child, this.max = 480});

  @override
  Widget build(BuildContext context) =>
      Align(alignment: Alignment.topCenter, heightFactor: 1, child: ConstrainedBox(constraints: BoxConstraints(maxWidth: max), child: child));
}

/// Gutter horizontal layar: 16 dp pada layar sempit, 20 dp selainnya.
double screenGutter(BuildContext context) => MediaQuery.sizeOf(context).width < 360 ? 16 : 20;

/// Tombol utama hijau pekat berbentuk pill; menampilkan indikator saat [loading].
class PrimaryButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final IconData? trailingIcon;
  const PrimaryButton({super.key, required this.label, this.onPressed, this.loading = false, this.trailingIcon});

  @override
  Widget build(BuildContext context) => SizedBox(
        width: double.infinity,
        height: 54,
        child: ElevatedButton(
          onPressed: loading ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
            foregroundColor: AppColors.onPrimary,
            disabledBackgroundColor: AppColors.primary.withOpacity(0.6),
            disabledForegroundColor: AppColors.onPrimary,
            elevation: 0,
            shape: const StadiumBorder(),
          ),
          child: loading
              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.4))
              : Row(mainAxisSize: MainAxisSize.min, mainAxisAlignment: MainAxisAlignment.center, children: [
                  Flexible(
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(label, maxLines: 1, softWrap: false, style: GoogleFonts.plusJakartaSans(fontSize: 14.5, fontWeight: FontWeight.w700)),
                    ),
                  ),
                  if (trailingIcon != null) ...[const SizedBox(width: 8), Icon(trailingIcon, size: 18)],
                ]),
        ),
      );
}

/// Tombol sekunder: latar putih, garis tipis.
class SecondaryButton extends StatelessWidget {
  final VoidCallback? onPressed;
  final Widget child;
  const SecondaryButton({super.key, required this.child, this.onPressed});

  @override
  Widget build(BuildContext context) => SizedBox(
        width: double.infinity,
        height: 54,
        child: OutlinedButton(
          onPressed: onPressed,
          style: OutlinedButton.styleFrom(
            backgroundColor: AppColors.surface,
            foregroundColor: AppColors.ink,
            side: const BorderSide(color: AppColors.line),
            shape: const StadiumBorder(),
          ),
          child: child,
        ),
      );
}

/// Kolom isian dengan label di atas, latar putih, tanpa garis saat diam.
class AppTextField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String? hintText;
  final IconData icon;
  final bool obscure;
  final Widget? suffix;
  final String? Function(String?)? validator;
  final TextInputType keyboardType;
  final TextInputAction? textInputAction;
  final VoidCallback? onSubmitted;
  final bool readOnly;
  final int maxLines;

  const AppTextField({
    super.key,
    required this.controller,
    required this.label,
    required this.icon,
    this.hintText,
    this.obscure = false,
    this.suffix,
    this.validator,
    this.keyboardType = TextInputType.text,
    this.textInputAction,
    this.onSubmitted,
    this.readOnly = false,
    this.maxLines = 1,
  });

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: GoogleFonts.plusJakartaSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.muted)),
          const SizedBox(height: 8),
          TextFormField(
            controller: controller,
            obscureText: obscure,
            readOnly: readOnly,
            maxLines: maxLines,
            keyboardType: keyboardType,
            textInputAction: textInputAction,
            onFieldSubmitted: onSubmitted == null ? null : (_) => onSubmitted!(),
            validator: validator,
            style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w500, color: readOnly ? AppColors.muted : AppColors.ink),
            decoration: InputDecoration(
              hintText: hintText,
              prefixIcon: Icon(icon, size: 20, color: AppColors.hint),
              suffixIcon: suffix,
              fillColor: readOnly ? AppColors.surfaceAlt : null,
              enabledBorder: readOnly
                  ? OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none)
                  : null,
            ),
          ),
        ],
      );
}

/// Satu tab pada [FloatingNavBar].
class NavItem {
  final IconData icon;
  final IconData selectedIcon;
  final String label;

  /// Angka lencana di atas ikon (0 = tanpa lencana).
  final int badge;
  const NavItem(this.icon, this.selectedIcon, this.label, {this.badge = 0});
}

/// Bar navigasi bawah mengambang: kapsul putih, tab aktif berupa lingkaran hijau (design.md 5.6).
/// Dipasang sebagai `bottomNavigationBar` agar Scaffold menyisakan ruangnya.
class FloatingNavBar extends StatelessWidget {
  final List<NavItem> items;
  final int index;
  final ValueChanged<int> onTap;
  const FloatingNavBar({super.key, required this.items, required this.index, required this.onTap});

  @override
  Widget build(BuildContext context) => SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
          child: ContentWidth(
            max: 560,
            child: Container(
              height: 76,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(28),
                boxShadow: AppColors.floatShadow,
              ),
              child: Row(
                children: [
                  for (var i = 0; i < items.length; i++)
                    Expanded(
                      child: Semantics(
                        button: true,
                        selected: i == index,
                        label: items[i].label,
                        child: InkResponse(
                          onTap: () => onTap(i),
                          radius: 40,
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              AnimatedContainer(
                                duration: const Duration(milliseconds: 220),
                                curve: Curves.easeOutCubic,
                                width: 46,
                                height: 46,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: i == index ? AppColors.primary : Colors.transparent,
                                ),
                                child: Badge(
                                  isLabelVisible: items[i].badge > 0,
                                  label: Text('${items[i].badge}'),
                                  backgroundColor: AppColors.error,
                                  offset: const Offset(-12, 0),
                                  child: Icon(
                                    i == index ? items[i].selectedIcon : items[i].icon,
                                    size: 24,
                                    color: i == index ? Colors.white : AppColors.hint,
                                  ),
                                ),
                              ),
                              Text(
                                items[i].label,
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 11,
                                  fontWeight: i == index ? FontWeight.w700 : FontWeight.w500,
                                  color: i == index ? AppColors.primary : AppColors.hint,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      );
}

/// Tombol kembali bulat (area sentuh 48 dp).
class BackCircleButton extends StatelessWidget {
  final Color background;
  const BackCircleButton({super.key, this.background = AppColors.surface});

  @override
  Widget build(BuildContext context) => Semantics(
        button: true,
        label: 'Kembali',
        child: GestureDetector(
          onTap: () => Navigator.maybePop(context),
          behavior: HitTestBehavior.opaque,
          child: SizedBox(
            width: 48,
            height: 48,
            child: Center(
              child: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(shape: BoxShape.circle, color: background, border: Border.all(color: AppColors.line), boxShadow: AppColors.floatShadow),
                child: const Icon(Icons.arrow_back_rounded, size: 20, color: AppColors.ink),
              ),
            ),
          ),
        ),
      );
}

/// Judul layar sederhana: tombol kembali bulat + judul besar.
class ScreenHeader extends StatelessWidget {
  final String title;
  const ScreenHeader(this.title, {super.key});

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(screenGutter(context) - 4, 8, screenGutter(context), 8),
        child: Row(children: [
          const BackCircleButton(),
          const SizedBox(width: 8),
          Expanded(
            child: Text(title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.ink)),
          ),
        ]),
      );
}

/// Kartu putih berlengan lembut untuk mengelompokkan isi form.
class SoftCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets padding;
  final Color? color;
  const SoftCard({super.key, required this.child, this.padding = const EdgeInsets.all(16), this.color});

  @override
  Widget build(BuildContext context) => Container(
        width: double.infinity,
        padding: padding,
        decoration: BoxDecoration(color: color ?? AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: color == null ? AppColors.softShadow : null),
        child: child,
      );
}

/// Judul seksi kecil di atas kelompok isian.
class SectionLabel extends StatelessWidget {
  final String text;
  const SectionLabel(this.text, {super.key});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Text(text, style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink)),
      );
}

/// Pilihan tunggal berbentuk kartu: terpilih = garis hijau + latar hijau pucat + tanda centang.
class ChoiceTile extends StatelessWidget {
  final String title;
  final String? subtitle;
  final IconData? icon;
  final bool selected;
  final VoidCallback onTap;

  /// Susunan ringkas (ikon di atas, label di bawah) untuk 3 pilihan sebaris.
  final bool compact;
  const ChoiceTile({super.key, required this.title, required this.selected, required this.onTap, this.subtitle, this.icon, this.compact = false});

  @override
  Widget build(BuildContext context) {
    final t = GoogleFonts.plusJakartaSans;
    return Semantics(
      button: true,
      selected: selected,
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          constraints: const BoxConstraints(minHeight: 56),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: selected ? AppColors.primarySoft : AppColors.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: selected ? AppColors.primary : AppColors.line, width: selected ? 1.5 : 1),
          ),
          child: compact
              ? Column(mainAxisSize: MainAxisSize.min, children: [
                  if (icon != null) Icon(icon, size: 22, color: selected ? AppColors.primary : AppColors.muted),
                  const SizedBox(height: 6),
                  Text(title, textAlign: TextAlign.center, maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: t(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.ink)),
                ])
              : Row(children: [
            if (icon != null) ...[Icon(icon, size: 20, color: selected ? AppColors.primary : AppColors.muted), const SizedBox(width: 12)],
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: t(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
                if (subtitle != null) Text(subtitle!, style: t(fontSize: 12, color: AppColors.muted)),
              ]),
            ),
            Icon(selected ? Icons.check_circle_rounded : Icons.circle_outlined, size: 22, color: selected ? AppColors.primary : AppColors.hint),
          ]),
        ),
      ),
    );
  }
}

/// Bar aksi tetap di bawah layar form (putih, sudut atas melengkung).
class BottomActionBar extends StatelessWidget {
  final Widget child;
  const BottomActionBar({super.key, required this.child});

  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          boxShadow: AppColors.floatShadow,
        ),
        child: SafeArea(
          top: false,
          child: ContentWidth(max: 720, child: Padding(padding: const EdgeInsets.fromLTRB(24, 16, 24, 16), child: child)),
        ),
      );
}

/// Judul layar tab (tanpa tombol kembali) dengan aksi opsional di kanan.
class TabHeader extends StatelessWidget {
  final String title;
  final Widget? trailing;
  const TabHeader(this.title, {super.key, this.trailing});

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(screenGutter(context), 14, screenGutter(context) - 8, 8),
        child: Row(children: [
          Expanded(
            child: Text(title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(fontSize: 26, fontWeight: FontWeight.w800, color: AppColors.ink)),
          ),
          if (trailing != null) trailing!,
        ]),
      );
}

/// Tombol ikon bulat putih (area sentuh 48 dp), mis. untuk menyegarkan.
class RoundIconButton extends StatelessWidget {
  final IconData icon;
  final String tooltip;
  final VoidCallback onPressed;
  const RoundIconButton({super.key, required this.icon, required this.tooltip, required this.onPressed});

  @override
  Widget build(BuildContext context) => Tooltip(
        message: tooltip,
        child: GestureDetector(
          onTap: onPressed,
          behavior: HitTestBehavior.opaque,
          child: SizedBox(
            width: 48,
            height: 48,
            child: Center(
              child: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.surface, boxShadow: AppColors.softShadow),
                child: Icon(icon, size: 20, color: AppColors.ink),
              ),
            ),
          ),
        ),
      );
}

/// Kotak informasi berwarna lembut dengan ikon (info, peringatan, atau galat).
enum BannerTone { info, warning, error, success }

class InfoBanner extends StatelessWidget {
  final String text;
  final BannerTone tone;
  const InfoBanner(this.text, {super.key, this.tone = BannerTone.info});

  @override
  Widget build(BuildContext context) {
    final (fg, bg, icon) = switch (tone) {
      BannerTone.info    => (AppColors.info, AppColors.infoSoft, Icons.info_outline_rounded),
      BannerTone.warning => (AppColors.warning, AppColors.warningSoft, Icons.warning_amber_rounded),
      BannerTone.error   => (AppColors.error, AppColors.errorSoft, Icons.error_outline_rounded),
      BannerTone.success => (AppColors.success, AppColors.successSoft, Icons.check_circle_outline_rounded),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(16)),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, size: 18, color: fg),
        const SizedBox(width: 10),
        Expanded(child: Text(text, style: GoogleFonts.plusJakartaSans(fontSize: 12.5, height: 1.4, fontWeight: FontWeight.w600, color: fg))),
      ]),
    );
  }
}

/// Warna lencana status pesanan: teks + latar lembut (design.md bagian 2).
({Color fg, Color bg}) bookingStatusStyle(int status) => switch (status) {
      1 => (fg: AppColors.warning, bg: AppColors.warningSoft),
      2 => (fg: AppColors.info, bg: AppColors.infoSoft),
      3 => (fg: AppColors.success, bg: AppColors.successSoft),
      _ => (fg: AppColors.error, bg: AppColors.errorSoft),
    };
