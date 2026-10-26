import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/widgets/app_widgets.dart';
import '../../../core/widgets/state_views.dart';
import '../../../providers/muat.dart';

// ─────────────────────────────────────────────────────────────
//  Komponen bersama layar admin
// ─────────────────────────────────────────────────────────────

Color warnaStatusPesanan(int status) => switch (status) {
      1 => AppColors.statusBooked,
      2 => AppColors.statusOngoing,
      3 => AppColors.statusDone,
      _ => AppColors.statusCancel,
    };

String labelStatusPesanan(int status) => switch (status) {
      1 => 'Booked',
      2 => 'Berjalan',
      3 => 'Selesai',
      _ => 'Dibatalkan',
    };

Color warnaStatusPembayaran(int status) => switch (status) {
      1 => AppColors.statusDone,
      2 => AppColors.statusBooked,
      _ => AppColors.muted,
    };

String labelStatusPembayaran(int status) => switch (status) {
      1 => 'Lunas',
      2 => 'Menunggu verifikasi',
      _ => 'Belum dibayar',
    };

Color warnaStatusMobil(int status) => switch (status) {
      1 => AppColors.success,
      3 => AppColors.statusOngoing,
      4 => AppColors.warning,
      _ => AppColors.error,
    };

String labelStatusMobil(int status) => switch (status) {
      1 => 'Tersedia',
      3 => 'Disewa',
      4 => 'Maintenance',
      0 => 'Rusak',
      _ => 'Tidak diketahui',
    };

void tampilkanSnack(BuildContext context, String pesan, {bool galat = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(pesan), backgroundColor: galat ? AppColors.error : AppColors.success));
}

/// Tampilkan hasil sebuah aksi: galat berwarna merah, sukses hijau (dengan info tambahan bila ada).
void tampilkanHasil(BuildContext context, String? galat, String suksesPesan, {String? info}) {
  if (galat != null) {
    tampilkanSnack(context, galat, galat: true);
  } else {
    tampilkanSnack(context, (info != null && info.isNotEmpty) ? '$suksesPesan $info' : suksesPesan);
  }
}

/// Dialog konfirmasi untuk aksi yang tidak dapat dibatalkan.
Future<bool> konfirmasi(
  BuildContext context, {
  required String judul,
  required String isi,
  String tombol = 'Ya',
  Color warna = AppColors.primary,
}) async {
  final ya = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(judul),
      content: Text(isi),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          style: TextButton.styleFrom(foregroundColor: AppColors.muted),
          child: const Text('Batal'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(ctx, true),
          style: FilledButton.styleFrom(backgroundColor: warna, minimumSize: const Size(64, 44)),
          child: Text(tombol),
        ),
      ],
    ),
  );
  return ya == true;
}

/// Dialog satu kolom teks (mis. alasan penolakan). Mengembalikan null bila dibatalkan.
Future<String?> mintaTeks(
  BuildContext context, {
  required String judul,
  required String label,
  String tombol = 'Kirim',
  Color warna = AppColors.primary,
  bool wajib = false,
}) {
  final ctrl = TextEditingController();
  return showDialog<String>(
    context: context,
    builder: (ctx) {
      String? galat;
      return StatefulBuilder(builder: (ctx, setState) {
        return AlertDialog(
          title: Text(judul),
          content: TextField(
            controller: ctrl,
            maxLines: 3,
            maxLength: 200,
            decoration: InputDecoration(labelText: label, errorText: galat),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              style: TextButton.styleFrom(foregroundColor: AppColors.muted),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () {
                if (wajib && ctrl.text.trim().isEmpty) {
                  setState(() => galat = 'Wajib diisi');
                  return;
                }
                Navigator.pop(ctx, ctrl.text.trim());
              },
              style: FilledButton.styleFrom(backgroundColor: warna, minimumSize: const Size(64, 44)),
              child: Text(tombol),
            ),
          ],
        );
      });
    },
  );
}

/// Kartu bagian dengan judul.
class BagianKartu extends StatelessWidget {
  final String judul;
  final Widget child;
  final Widget? aksi;
  const BagianKartu({super.key, required this.judul, required this.child, this.aksi});

  @override
  Widget build(BuildContext context) {
    // Material transparan di dalam dekorasi: ListTile/InkWell di dalamnya tetap punya permukaan untuk efek sentuh.
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(22), boxShadow: AppColors.softShadow),
      child: Material(
        type: MaterialType.transparency,
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Expanded(
                  child: Text(judul, style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink)),
                ),
                if (aksi != null) aksi!,
              ]),
              const SizedBox(height: 12),
              child,
            ],
          ),
        ),
      ),
    );
  }
}

class BarisInfo extends StatelessWidget {
  final String label;
  final String nilai;
  final Color? warnaNilai;
  final bool tebal;
  const BarisInfo(this.label, this.nilai, {super.key, this.warnaNilai, this.tebal = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 108, child: Text(label, style: GoogleFonts.plusJakartaSans(fontSize: 12.5, color: AppColors.muted))),
          Expanded(
            child: Text(nilai,
                style: GoogleFonts.plusJakartaSans(
                    fontSize: 13, fontWeight: tebal ? FontWeight.w800 : FontWeight.w600, color: warnaNilai ?? AppColors.ink)),
          ),
        ],
      ),
    );
  }
}

/// Baris pilihan filter berbentuk pill yang dapat digeser horizontal.
class BarisFilter<T> extends StatelessWidget {
  final List<(String, T?)> pilihan;
  final T? terpilih;
  final void Function(T?) onPilih;
  const BarisFilter({super.key, required this.pilihan, required this.terpilih, required this.onPilih});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 48,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: screenGutter(context), vertical: 4),
        itemCount: pilihan.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) {
          final (label, nilai) = pilihan[i];
          final on = terpilih == nilai;
          return Semantics(
            button: true,
            selected: on,
            child: GestureDetector(
              onTap: () => onPilih(nilai),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(horizontal: 18),
                alignment: Alignment.center,
                decoration: BoxDecoration(color: on ? AppColors.primary : AppColors.surfaceAlt, borderRadius: BorderRadius.circular(20)),
                child: Text(label,
                    style: GoogleFonts.plusJakartaSans(fontSize: 12.5, fontWeight: FontWeight.w600, color: on ? Colors.white : AppColors.ink)),
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Kotak pencarian berbentuk pill; pencarian dijalankan saat ketuk "cari" di papan ketik.
class KotakCari extends StatefulWidget {
  final String hint;
  final String awal;
  final void Function(String) onCari;
  const KotakCari({super.key, required this.hint, required this.onCari, this.awal = ''});

  @override
  State<KotakCari> createState() => _KotakCariState();
}

class _KotakCariState extends State<KotakCari> {
  late final TextEditingController _ctrl = TextEditingController(text: widget.awal);

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final g = screenGutter(context);
    return Padding(
      padding: EdgeInsets.fromLTRB(g, 8, g, 10),
      child: Container(
        decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(26), boxShadow: AppColors.softShadow),
        child: TextField(
          controller: _ctrl,
          textInputAction: TextInputAction.search,
          onSubmitted: widget.onCari,
          style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.ink),
          decoration: InputDecoration(
            hintText: widget.hint,
            hintStyle: GoogleFonts.plusJakartaSans(fontSize: 13.5, color: AppColors.hint),
            prefixIcon: const Icon(Icons.search_rounded, color: AppColors.ink, size: 20),
            suffixIcon: IconButton(
              icon: const Icon(Icons.close_rounded, size: 20, color: AppColors.muted),
              tooltip: 'Hapus pencarian',
              onPressed: () {
                _ctrl.clear();
                widget.onCari('');
              },
            ),
            filled: false,
            contentPadding: const EdgeInsets.symmetric(vertical: 16),
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
          ),
        ),
      ),
    );
  }
}

/// Daftar berhalaman: memuat halaman berikutnya saat hampir sampai di ujung, dengan keadaan
/// loading / galat / kosong dan tarik-untuk-segarkan.
class DaftarBerhalaman<T> extends StatefulWidget {
  final Halaman<T> halaman;
  final Future<void> Function() onRefresh;
  final Future<void> Function() onLagi;
  final Widget Function(BuildContext, T) itemBuilder;
  final Widget empty;
  final double bawah;

  const DaftarBerhalaman({
    super.key,
    required this.halaman,
    required this.onRefresh,
    required this.onLagi,
    required this.itemBuilder,
    required this.empty,
    this.bawah = 24,
  });

  @override
  State<DaftarBerhalaman<T>> createState() => _DaftarBerhalamanState<T>();
}

class _DaftarBerhalamanState<T> extends State<DaftarBerhalaman<T>> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 240) widget.onLagi();
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final h = widget.halaman;
    if (!h.loaded && h.loading) return const Center(child: CircularProgressIndicator());
    if (h.error != null && h.items.isEmpty) return ErrorState(message: h.error!, onRetry: widget.onRefresh);
    if (h.items.isEmpty) {
      return RefreshIndicator(
        onRefresh: widget.onRefresh,
        child: LayoutBuilder(
          builder: (_, c) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [SizedBox(height: c.maxHeight, child: widget.empty)],
          ),
        ),
      );
    }
    final g = screenGutter(context);
    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      child: ListView.separated(
        controller: _scroll,
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(g, 4, g, widget.bawah),
        itemCount: h.items.length + 1,
        separatorBuilder: (_, __) => const SizedBox(height: 14),
        itemBuilder: (ctx, i) {
          if (i < h.items.length) return widget.itemBuilder(ctx, h.items[i]);
          // Kaki daftar: indikator muat lanjutan, ajakan coba lagi, atau penutup.
          if (h.loadingMore) {
            return const Padding(padding: EdgeInsets.all(12), child: Center(child: CircularProgressIndicator(strokeWidth: 2.5)));
          }
          if (h.error != null) {
            return TextButton(onPressed: widget.onLagi, child: const Text('Gagal memuat lanjutan. Ketuk untuk coba lagi.'));
          }
          return Padding(
            padding: const EdgeInsets.all(8),
            child: Center(
              child: Text(h.hasMore ? '' : '${h.total} data',
                  style: GoogleFonts.plusJakartaSans(fontSize: 11.5, color: AppColors.hint)),
            ),
          );
        },
      ),
    );
  }
}


/// Kerangka halaman form admin: judul dengan tombol kembali, isi yang bisa digulir, dan aksi utama di bar bawah.
/// Bila [formKey] diberikan, isi dibungkus [Form].
class HalamanForm extends StatelessWidget {
  final String judul;
  final List<Widget> children;
  final Widget aksi;
  final GlobalKey<FormState>? formKey;
  const HalamanForm({super.key, required this.judul, required this.children, required this.aksi, this.formKey});

  @override
  Widget build(BuildContext context) {
    final g = screenGutter(context);
    Widget isi = ListView(padding: EdgeInsets.fromLTRB(g, 8, g, 24), children: children);
    if (formKey != null) isi = Form(key: formKey, child: isi);
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        bottom: false,
        child: ContentWidth(max: 720, child: Column(children: [ScreenHeader(judul), Expanded(child: isi)])),
      ),
      bottomNavigationBar: BottomActionBar(child: aksi),
    );
  }
}

/// Tombol ikon + teks bergaya sekunder untuk memilih foto atau gambar.
class TombolFoto extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  const TombolFoto({super.key, required this.label, required this.onPressed});

  @override
  Widget build(BuildContext context) => SecondaryButton(
        onPressed: onPressed,
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          const Icon(Icons.add_a_photo_rounded, size: 20, color: AppColors.primary),
          const SizedBox(width: 10),
          Text(label, style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.ink)),
        ]),
      );
}
