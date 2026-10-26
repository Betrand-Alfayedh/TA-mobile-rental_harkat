import 'dart:async';
import 'dart:io';
import 'package:flutter/foundation.dart';
import '../core/services/api_service.dart';
import '../models/sopir_model.dart';
import 'muat.dart';

/// Keadaan sisi sopir: dashboard, tawaran, tugas aktif, riwayat, dan aksi (terima/tolak/status/laporan).
/// Aksi mengembalikan pesan galat (String) atau null bila berhasil, agar layar bisa menampilkannya.
class SopirProvider extends ChangeNotifier {
  Muat<SopirDashboardModel?> dashboard = const Muat(value: null);
  Muat<List<TawaranModel>>   tawaran   = const Muat(value: []);
  Muat<List<SopirUnitModel>> tugas     = const Muat(value: []);
  Muat<List<SopirUnitModel>> riwayat   = const Muat(value: []);

  bool _submitting = false;
  bool get submitting => _submitting;

  Timer? _timer;
  bool _disposed = false;

  /// Polling tawaran + dashboard saat layar sopir terlihat (tanpa soket realtime).
  static const pollingInterval = Duration(seconds: 30);

  void startPolling() {
    _timer?.cancel();
    _timer = Timer.periodic(pollingInterval, (_) => muatTawaranBaru());
  }

  void stopPolling() {
    _timer?.cancel();
    _timer = null;
  }

  /// Dipanggil saat logout / sesi berakhir agar data pengguna sebelumnya tidak terbawa.
  /// Tidak memanggil notifyListeners: dipakai dari ProxyProvider.update (saat build), dan layar sopir
  /// sudah dilepas ketika logout.
  void reset() {
    stopPolling();
    dashboard = const Muat(value: null);
    tawaran   = const Muat(value: []);
    tugas     = const Muat(value: []);
    riwayat   = const Muat(value: []);
    _submitting = false;
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

  Future<void> refreshAll() => Future.wait([muatDashboard(), muatTawaran(), muatTugas(), muatRiwayat()]);

  Future<void> muatTawaranBaru() => Future.wait([muatDashboard(silent: true), muatTawaran(silent: true)]);

  Future<void> muatDashboard({bool silent = false}) async {
    if (!silent) _tandaiLoading(() => dashboard = dashboard.copy(loading: true, clearError: true));
    dashboard = await _ambil<SopirDashboardModel?>(dashboard, silent, ApiService.getSopirDashboard,
        (d) => SopirDashboardModel.fromJson(d['data'] as Map<String, dynamic>));
    _notify();
  }

  Future<void> muatTawaran({bool silent = false}) async {
    if (!silent) _tandaiLoading(() => tawaran = tawaran.copy(loading: true, clearError: true));
    tawaran = await _ambil<List<TawaranModel>>(tawaran, silent, ApiService.getTawaran,
        (d) => (d['data'] as List).map((e) => TawaranModel.fromJson(e as Map<String, dynamic>)).toList());
    _notify();
  }

  Future<void> muatTugas({bool silent = false}) async {
    if (!silent) _tandaiLoading(() => tugas = tugas.copy(loading: true, clearError: true));
    tugas = await _ambil<List<SopirUnitModel>>(tugas, silent, ApiService.getSopirTugas, _uraiUnit);
    _notify();
  }

  Future<void> muatRiwayat({bool silent = false}) async {
    if (!silent) _tandaiLoading(() => riwayat = riwayat.copy(loading: true, clearError: true));
    riwayat = await _ambil<List<SopirUnitModel>>(riwayat, silent, ApiService.getSopirRiwayat, _uraiUnit);
    _notify();
  }

  static List<SopirUnitModel> _uraiUnit(Map<String, dynamic> d) =>
      (d['data'] as List).map((e) => SopirUnitModel.fromJson(e as Map<String, dynamic>)).toList();

  void _tandaiLoading(void Function() ubah) {
    ubah();
    _notify();
  }

  /// Hitung keadaan baru dari satu panggilan API. Mode [silent] (polling) tidak menghapus data lama
  /// saat gagal. Respons rusak tidak melempar dan tidak membuat layar macet di loading.
  Future<Muat<T>> _ambil<T>(
    Muat<T> sekarang,
    bool silent,
    Future<ApiResponse<Map<String, dynamic>>> Function() panggil,
    T Function(Map<String, dynamic>) urai,
  ) async {
    try {
      final res = await panggil();
      if (res.success && res.data != null) {
        return sekarang.copy(value: urai(res.data!), loading: false, loaded: true, clearError: true);
      }
      return silent
          ? sekarang.copy(loading: false)
          : sekarang.copy(loading: false, loaded: true, error: res.displayMessage);
    } catch (_) {
      return silent
          ? sekarang.copy(loading: false)
          : sekarang.copy(loading: false, loaded: true, error: 'Data tidak dapat dimuat.');
    }
  }

  // ── Aksi ───────────────────────────────────────────────────

  Future<String?> _aksi(Future<ApiResponse<Map<String, dynamic>>> Function() panggil, {Future<void> Function()? sesudah}) async {
    if (_submitting) return 'Mohon tunggu, permintaan sebelumnya masih diproses.';
    _submitting = true;
    _notify();
    try {
      final res = await panggil();
      if (!res.success) {
        // 409 = tugas sudah diambil sopir lain: segarkan agar tawarannya hilang dari daftar.
        if (res.statusCode == 409) await refreshAll();
        return res.displayMessage.isNotEmpty ? res.displayMessage : 'Permintaan gagal.';
      }
      if (sesudah != null) await sesudah();
      return null;
    } catch (e) {
      return 'Terjadi kesalahan: $e';
    } finally {
      _submitting = false;
      _notify();
    }
  }

  Future<String?> terima(TawaranModel t) =>
      _aksi(() => ApiService.terimaTawaran(t.id), sesudah: refreshAll);

  Future<String?> tolak(TawaranModel t) =>
      _aksi(() => ApiService.tolakTawaran(t.id), sesudah: () => Future.wait([muatTawaran(), muatDashboard()]));

  Future<String?> ubahStatus(bool tersedia) =>
      _aksi(() => ApiService.setSopirStatus(tersedia), sesudah: muatDashboard);

  Future<String?> kirimLaporan({
    required int unitId,
    required int jenis,
    required int kondisi,
    String? catatan,
    File? foto,
  }) =>
      _aksi(
        () => ApiService.kirimLaporanKondisi(unitId: unitId, jenis: jenis, kondisi: kondisi, catatan: catatan, foto: foto),
        sesudah: () => Future.wait([muatTugas(silent: true), muatRiwayat(silent: true)]),
      );
}
