import 'dart:async';
import 'dart:io';
import 'package:flutter/foundation.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/admin_model.dart';
import 'muat.dart';
import 'paging.dart';

/// Keadaan sisi admin untuk alur inti: dashboard, pesanan, pembayaran, dan aksi per pesanan/unit.
/// Aksi mengembalikan [Hasil] agar layar dapat menampilkan galat atau info tambahan.
class AdminProvider extends ChangeNotifier {
  Muat<DashboardAdminModel?> dashboard = const Muat(value: null);
  Halaman<BookingAdminModel> bookings  = const Halaman();
  Halaman<PembayaranAdminModel> pembayaran = const Halaman();
  Muat<BookingAdminModel?> detail = const Muat(value: null);

  // Filter daftar. Pembayaran dimulai dari "menunggu verifikasi" karena itu pekerjaan utama admin.
  int? filterStatusPesanan;
  String kataKunciPesanan = '';
  int? filterStatusPembayaran = 2;

  bool _submitting = false;
  bool get submitting => _submitting;

  // Nomor generasi: respons lama (filter sudah berganti) dibuang agar tidak menimpa hasil terbaru.
  int _genPesanan = 0;
  int _genPembayaran = 0;
  int _genDetail = 0;

  Timer? _timer;
  bool _disposed = false;

  /// Penyegaran berkala ringan (dashboard + pembayaran menunggu) saat layar admin terlihat.
  static const pollingInterval = Duration(seconds: 30);

  void startPolling() {
    _timer?.cancel();
    _timer = Timer.periodic(pollingInterval, (_) => segarkanRingan());
  }

  void stopPolling() {
    _timer?.cancel();
    _timer = null;
  }

  /// Dibuang saat logout / sesi berakhir. Tidak memanggil notifyListeners (dipakai dari ProxyProvider.update).
  void reset() {
    stopPolling();
    dashboard = const Muat(value: null);
    bookings = const Halaman();
    pembayaran = const Halaman();
    detail = const Muat(value: null);
    filterStatusPesanan = null;
    kataKunciPesanan = '';
    filterStatusPembayaran = 2;
    _submitting = false;
    _genPesanan++;
    _genPembayaran++;
    _genDetail++;
  }

  void _notify() {
    if (!_disposed) notifyListeners();
  }

  @override
  void dispose() {
    _disposed = true;
    _timer?.cancel();
    super.dispose();
  }

  // ── Pemuatan ───────────────────────────────────────────────

  Future<void> segarkanRingan() => Future.wait([muatDashboard(silent: true), muatPembayaran(silent: true)]);

  Future<void> muatDashboard({bool silent = false}) async {
    if (!silent) {
      dashboard = dashboard.copy(loading: true, clearError: true);
      _notify();
    }
    try {
      final res = await ApiService.call('GET', ApiConstants.adminDashboard);
      if (res.success && res.data != null) {
        dashboard = dashboard.copy(
            value: DashboardAdminModel.fromJson(res.data!['data'] as Map<String, dynamic>),
            loading: false, loaded: true, clearError: true);
      } else {
        dashboard = silent ? dashboard.copy(loading: false) : dashboard.copy(loading: false, loaded: true, error: res.displayMessage);
      }
    } catch (_) {
      dashboard = silent ? dashboard.copy(loading: false) : dashboard.copy(loading: false, loaded: true, error: 'Data tidak dapat dimuat.');
    }
    _notify();
  }

  Future<void> aturFilterPesanan({int? status, String? kataKunci, bool hapusStatus = false}) {
    if (hapusStatus) filterStatusPesanan = null;
    if (status != null) filterStatusPesanan = status;
    if (kataKunci != null) kataKunciPesanan = kataKunci.trim();
    return muatPesanan();
  }

  Future<void> muatPesanan({bool silent = false}) async {
    final gen = ++_genPesanan;
    if (!silent) {
      bookings = bookings.copy(items: const [], page: 0, loading: true, clearError: true);
      _notify();
    }
    final hasil = await ambilHalaman<BookingAdminModel>(bookings, 1,
        silent: silent, ambil: _ambilPesanan, urai: BookingAdminModel.fromJson);
    if (gen != _genPesanan) return;
    bookings = hasil;
    _notify();
  }

  Future<void> pesananLagi() async {
    if (!bookings.hasMore || bookings.loadingMore || bookings.loading) return;
    final gen = _genPesanan;
    bookings = bookings.copy(loadingMore: true, clearError: true);
    _notify();
    final hasil = await ambilHalaman<BookingAdminModel>(bookings, bookings.page + 1,
        silent: false, ambil: _ambilPesanan, urai: BookingAdminModel.fromJson);
    if (gen != _genPesanan) return;
    bookings = hasil;
    _notify();
  }

  Future<ApiResponse<Map<String, dynamic>>> _ambilPesanan(int page) => ApiService.call('GET', ApiConstants.adminBookings, query: {
        'page': '$page',
        'per_page': '15',
        if (filterStatusPesanan != null) 'status': '$filterStatusPesanan',
        if (kataKunciPesanan.isNotEmpty) 'q': kataKunciPesanan,
      });

  Future<void> aturFilterPembayaran({int? status, bool semua = false}) {
    filterStatusPembayaran = semua ? null : status;
    return muatPembayaran();
  }

  Future<void> muatPembayaran({bool silent = false}) async {
    final gen = ++_genPembayaran;
    if (!silent) {
      pembayaran = pembayaran.copy(items: const [], page: 0, loading: true, clearError: true);
      _notify();
    }
    final hasil = await ambilHalaman<PembayaranAdminModel>(pembayaran, 1,
        silent: silent, ambil: _ambilPembayaran, urai: PembayaranAdminModel.fromJson);
    if (gen != _genPembayaran) return;
    pembayaran = hasil;
    _notify();
  }

  Future<void> pembayaranLagi() async {
    if (!pembayaran.hasMore || pembayaran.loadingMore || pembayaran.loading) return;
    final gen = _genPembayaran;
    pembayaran = pembayaran.copy(loadingMore: true, clearError: true);
    _notify();
    final hasil = await ambilHalaman<PembayaranAdminModel>(pembayaran, pembayaran.page + 1,
        silent: false, ambil: _ambilPembayaran, urai: PembayaranAdminModel.fromJson);
    if (gen != _genPembayaran) return;
    pembayaran = hasil;
    _notify();
  }

  Future<ApiResponse<Map<String, dynamic>>> _ambilPembayaran(int page) => ApiService.call('GET', ApiConstants.adminPembayaran, query: {
        'page': '$page',
        'per_page': '15',
        if (filterStatusPembayaran != null) 'status': '$filterStatusPembayaran',
      });

  /// Muat satu pesanan untuk layar detail. Pesanan lain yang sedang dibuka tidak tercampur (generasi).
  Future<void> muatDetail(int id, {bool silent = false}) async {
    final gen = ++_genDetail;
    if (!silent) {
      detail = const Muat<BookingAdminModel?>(value: null, loading: true);
      _notify();
    }
    try {
      final res = await ApiService.call('GET', ApiConstants.adminBooking(id));
      if (gen != _genDetail) return;
      if (res.success && res.data != null) {
        detail = Muat<BookingAdminModel?>(
            value: BookingAdminModel.fromJson(res.data!['data'] as Map<String, dynamic>), loaded: true);
      } else if (!silent) {
        detail = Muat<BookingAdminModel?>(value: null, loaded: true, error: res.displayMessage);
      }
    } catch (_) {
      if (gen != _genDetail) return;
      if (!silent) detail = const Muat<BookingAdminModel?>(value: null, loaded: true, error: 'Data tidak dapat dimuat.');
    }
    _notify();
  }

  // ── Aksi ───────────────────────────────────────────────────

  /// Jalankan satu aksi: kunci ganda ditolak, galat jaringan ditangkap, dan [sesudah] hanya jika berhasil.
  /// [bookingId] menentukan pesanan yang disegarkan; null = hanya daftar dan dashboard.
  Future<Hasil> _aksi(
    Future<ApiResponse<Map<String, dynamic>>> Function() panggil, {
    int? bookingId,
    String Function(Map<String, dynamic> data)? info,
  }) async {
    if (_submitting) return const Hasil(galat: 'Mohon tunggu, permintaan sebelumnya masih diproses.');
    _submitting = true;
    _notify();
    try {
      final res = await panggil();
      if (!res.success) {
        return Hasil(galat: res.displayMessage.isNotEmpty ? res.displayMessage : 'Permintaan gagal.');
      }
      final pesan = info?.call((res.data?['data'] is Map<String, dynamic>) ? res.data!['data'] as Map<String, dynamic> : const {});
      await _segarkanSetelahAksi(bookingId);
      return Hasil(info: pesan);
    } catch (e) {
      return Hasil(galat: 'Terjadi kesalahan: $e');
    } finally {
      _submitting = false;
      _notify();
    }
  }

  Future<void> _segarkanSetelahAksi(int? bookingId) => Future.wait([
        if (bookingId != null) muatDetail(bookingId, silent: true),
        muatPesanan(silent: true),
        muatPembayaran(silent: true),
        muatDashboard(silent: true),
      ]);

  static String? _infoInvoice(Map<String, dynamic> d) =>
      d['invoice_terkirim'] == false ? 'Pembayaran tercatat lunas, tetapi invoice gagal dikirim ke email pelanggan.' : null;

  Future<Hasil> verifikasi(PembayaranAdminModel p) => _aksi(
        () => ApiService.call('POST', ApiConstants.pembayaranVerifikasi(p.id)),
        bookingId: p.bookingId,
        info: (d) => _infoInvoice(d) ?? '',
      );

  Future<Hasil> tolak(PembayaranAdminModel p, {String? catatan}) => _aksi(
        () => ApiService.call('POST', ApiConstants.pembayaranTolak(p.id),
            body: {if (catatan != null && catatan.trim().isNotEmpty) 'catatan_admin': catatan.trim()}),
        bookingId: p.bookingId,
      );

  /// [metode]: 1 tunai, 2 transfer, 3 QRIS.
  Future<Hasil> tandaiLunas(PembayaranAdminModel p, int metode) => _aksi(
        () => ApiService.call('POST', ApiConstants.pembayaranLunas(p.id), body: {'metode_pembayaran': metode}),
        bookingId: p.bookingId,
        info: (d) => _infoInvoice(d) ?? '',
      );

  Future<Hasil> batalkan(BookingAdminModel b, {String? catatan}) => _aksi(
        () => ApiService.call('POST', ApiConstants.adminBookingBatal(b.id),
            body: {if (catatan != null && catatan.trim().isNotEmpty) 'catatan': catatan.trim()}),
        bookingId: b.id,
      );

  Future<Hasil> tugaskan(int bookingId, UnitAdminModel u) => _aksi(
        () => ApiService.call('POST', ApiConstants.detailPenugasan(u.id)),
        bookingId: bookingId,
        info: (d) => 'Tugas ditawarkan ke ${d['jumlah_sopir'] ?? 0} sopir.',
      );

  Future<Hasil> jemput(int bookingId, UnitAdminModel u) => _aksi(
        () => ApiService.call('PATCH', ApiConstants.detailJemput(u.id)),
        bookingId: bookingId,
      );

  /// [tanggalAktual] format `yyyy-MM-dd HH:mm:ss`; [kondisi]: 1 baik, 0 rusak.
  Future<Hasil> catatPengembalian(
    int bookingId,
    UnitAdminModel u, {
    required String tanggalAktual,
    required int kondisi,
    String? catatan,
    File? foto,
  }) =>
      _aksi(
        () => ApiService.multipart(ApiConstants.detailPengembalian(u.id), fields: {
          'tanggal_kembali_aktual': tanggalAktual,
          'kondisi': '$kondisi',
          if (catatan != null && catatan.trim().isNotEmpty) 'catatan': catatan.trim(),
        }, files: {
          if (foto != null) 'gambar': foto,
        }),
        bookingId: bookingId,
        info: (d) {
          final denda = (d['nominal_denda'] is num) ? (d['nominal_denda'] as num).toInt() : 0;
          final jam = (d['jam_terlambat'] is num) ? (d['jam_terlambat'] as num).toInt() : 0;
          return denda > 0 ? 'Terlambat $jam jam, denda tertagih.' : '';
        },
      );

  /// Laporan kondisi oleh admin. [jenis]: 1 sebelum, 2 sesudah; [kondisi]: 1 baik, 2 lecet, 3 rusak.
  Future<Hasil> kirimLaporan(
    int bookingId,
    UnitAdminModel u, {
    required int jenis,
    required int kondisi,
    String? catatan,
    File? foto,
  }) =>
      _aksi(
        () => ApiService.multipart(ApiConstants.detailLaporan(u.id), fields: {
          'jenis': '$jenis',
          'kondisi': '$kondisi',
          if (catatan != null && catatan.trim().isNotEmpty) 'catatan': catatan.trim(),
        }, files: {
          if (foto != null) 'foto': foto,
        }),
        bookingId: bookingId,
      );
}
