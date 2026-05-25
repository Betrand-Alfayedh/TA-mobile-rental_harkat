import 'package:intl/intl.dart';

// ─────────────────────────────────────────────────────────────
//  Utility formatters
// ─────────────────────────────────────────────────────────────

class Formatters {
  static final _rupiah = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  /// Format integer to Rupiah string, e.g. "Rp 150.000"
  static String toRupiah(dynamic value) {
    if (value == null) return '-';
    final num = value is int ? value : int.tryParse(value.toString()) ?? 0;
    return _rupiah.format(num);
  }

  /// Format ISO date string to "Senin, 10 Juli 2025"
  static String toReadableDate(String? iso) {
    if (iso == null || iso.isEmpty) return '-';
    try {
      final dt = DateTime.parse(iso);
      return DateFormat('EEEE, d MMMM yyyy', 'id_ID').format(dt);
    } catch (_) {
      return iso;
    }
  }

  /// Format ISO datetime string to "10 Jul 2025 • 08:00"
  static String toShortDateTime(String? iso) {
    if (iso == null || iso.isEmpty) return '-';
    try {
      final dt = DateTime.parse(iso);
      return DateFormat('d MMM yyyy • HH:mm', 'id_ID').format(dt);
    } catch (_) {
      return iso;
    }
  }

  /// Duration label: "3 hari"
  static String lamaSewa(int days) => '$days hari';

  /// Status color mapping
  static String statusLabel(int status) {
    switch (status) {
      case 0:
        return 'Canceled';
      case 1:
        return 'Booked';
      case 2:
        return 'On Going';
      case 3:
        return 'Selesai';
      default:
        return 'Unknown';
    }
  }

  /// Payment status label
  static String paymentStatusLabel(int? status) {
    switch (status) {
      case 0:
        return 'Belum Bayar';
      case 1:
        return 'Sudah Bayar';
      case 2:
        return 'Menunggu Verifikasi';
      default:
        return '-';
    }
  }
}
