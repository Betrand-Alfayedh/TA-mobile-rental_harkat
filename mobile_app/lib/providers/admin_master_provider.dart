import 'dart:io';
import 'package:flutter/foundation.dart';
import '../core/constants/api_constants.dart';
import '../core/services/api_service.dart';
import '../models/master_model.dart';
import '../models/mobil_model.dart';
import 'muat.dart';
import 'paging.dart';

/// Data master untuk admin: armada, sopir, penyewa, dan rekap bulanan.
class AdminMasterProvider extends ChangeNotifier {
  Halaman<MobilModel> mobils = const Halaman();
  Halaman<SupirAdminModel> supirs = const Halaman();
  Halaman<PenyewaModel> penyewa = const Halaman();
  Muat<ReferensiArmadaModel> referensi = const Muat(value: ReferensiArmadaModel());
  Muat<RekapModel?> rekap = const Muat(value: null);

  // Filter
  int? filterStatusMobil;
  String kataKunciMobil = '';
  int? filterStatusSupir;
  String kataKunciSupir = '';
  String kataKunciPenyewa = '';
  String bulanRekap = _bulanIni();

  bool _submitting = false;
  bool get submitting => _submitting;

  int _genMobil = 0, _genSupir = 0, _genPenyewa = 0, _genRekap = 0;
  bool _disposed = false;

  static String _bulanIni() {
    final n = DateTime.now();
    return '${n.year}-${n.month.toString().padLeft(2, '0')}';
  }

  /// Dibuang saat logout / sesi berakhir. Tidak memanggil notifyListeners (dipakai dari ProxyProvider.update).
  void reset() {
    mobils = const Halaman();
    supirs = const Halaman();
    penyewa = const Halaman();
    referensi = const Muat(value: ReferensiArmadaModel());
    rekap = const Muat(value: null);
    filterStatusMobil = null;
    kataKunciMobil = '';
    filterStatusSupir = null;
    kataKunciSupir = '';
    kataKunciPenyewa = '';
    bulanRekap = _bulanIni();
    _submitting = false;
    _genMobil++; _genSupir++; _genPenyewa++; _genRekap++;
  }

  void _notify() {
    if (!_disposed) notifyListeners();
  }

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  // ── Armada ─────────────────────────────────────────────────

  Future<void> aturFilterMobil({int? status, bool hapusStatus = false, String? kataKunci}) {
    if (hapusStatus) filterStatusMobil = null;
    if (status != null) filterStatusMobil = status;
    if (kataKunci != null) kataKunciMobil = kataKunci.trim();
    return muatMobil();
  }

  Future<void> muatMobil({bool silent = false}) async {
    final gen = ++_genMobil;
    if (!silent) {
      mobils = mobils.copy(items: const [], page: 0, loading: true, clearError: true);
      _notify();
    }
    final h = await ambilHalaman<MobilModel>(mobils, 1, silent: silent, ambil: _ambilMobil, urai: MobilModel.fromJson);
    if (gen != _genMobil) return;
    mobils = h;
    _notify();
  }

  Future<void> mobilLagi() async {
    if (!mobils.hasMore || mobils.loadingMore || mobils.loading) return;
    final gen = _genMobil;
    mobils = mobils.copy(loadingMore: true, clearError: true);
    _notify();
    final h = await ambilHalaman<MobilModel>(mobils, mobils.page + 1, silent: false, ambil: _ambilMobil, urai: MobilModel.fromJson);
    if (gen != _genMobil) return;
    mobils = h;
    _notify();
  }

  Future<ApiResponse<Map<String, dynamic>>> _ambilMobil(int page) => ApiService.call('GET', ApiConstants.adminMobils, query: {
        'page': '$page',
        'per_page': '15',
        if (filterStatusMobil != null) 'status': '$filterStatusMobil',
        if (kataKunciMobil.isNotEmpty) 'q': kataKunciMobil,
      });

  Future<void> muatReferensi() async {
    if (referensi.loaded) return;
    try {
      final res = await ApiService.call('GET', ApiConstants.adminReferensi);
      if (res.success && res.data != null) {
        referensi = Muat(value: ReferensiArmadaModel.fromJson(res.data!['data'] as Map<String, dynamic>), loaded: true);
        _notify();
      }
    } catch (_) {/* form tetap bisa dicoba lagi saat dibuka berikutnya */}
  }

  /// Simpan mobil. [id] null = tambah. Gambar baru dikirim terpisah untuk mobil yang sudah ada.
  Future<Hasil> simpanMobil(int? id, Map<String, dynamic> data, {File? gambar}) => _aksi(() async {
        if (id == null) {
          final res = gambar == null
              ? await ApiService.call('POST', ApiConstants.adminMobils, body: data)
              : await ApiService.multipart(ApiConstants.adminMobils,
                  fields: data.map((k, v) => MapEntry(k, '$v')), files: {'gambar': gambar});
          return res;
        }
        final res = await ApiService.call('PUT', ApiConstants.adminMobil(id), body: data);
        if (!res.success || gambar == null) return res;
        return ApiService.multipart(ApiConstants.adminMobilGambar(id), files: {'gambar': gambar});
      }, segarkan: [() => muatMobil(silent: true)]);

  /// [status]: 1 tersedia, 4 maintenance, 0 rusak.
  Future<Hasil> ubahStatusMobil(int id, int status) => _aksi(
        () => ApiService.call('PATCH', ApiConstants.adminMobilStatus(id), body: {'status': status}),
        segarkan: [() => muatMobil(silent: true)],
      );

  Future<Hasil> hapusMobil(int id) => _aksi(
        () => ApiService.call('DELETE', ApiConstants.adminMobil(id)),
        segarkan: [() => muatMobil(silent: true)],
      );

  // ── Sopir ──────────────────────────────────────────────────

  Future<void> aturFilterSupir({int? status, bool hapusStatus = false, String? kataKunci}) {
    if (hapusStatus) filterStatusSupir = null;
    if (status != null) filterStatusSupir = status;
    if (kataKunci != null) kataKunciSupir = kataKunci.trim();
    return muatSupir();
  }

  Future<void> muatSupir({bool silent = false}) async {
    final gen = ++_genSupir;
    if (!silent) {
      supirs = supirs.copy(items: const [], page: 0, loading: true, clearError: true);
      _notify();
    }
    final h = await ambilHalaman<SupirAdminModel>(supirs, 1, silent: silent, ambil: _ambilSupir, urai: SupirAdminModel.fromJson);
    if (gen != _genSupir) return;
    supirs = h;
    _notify();
  }

  Future<void> supirLagi() async {
    if (!supirs.hasMore || supirs.loadingMore || supirs.loading) return;
    final gen = _genSupir;
    supirs = supirs.copy(loadingMore: true, clearError: true);
    _notify();
    final h = await ambilHalaman<SupirAdminModel>(supirs, supirs.page + 1, silent: false, ambil: _ambilSupir, urai: SupirAdminModel.fromJson);
    if (gen != _genSupir) return;
    supirs = h;
    _notify();
  }

  Future<ApiResponse<Map<String, dynamic>>> _ambilSupir(int page) => ApiService.call('GET', ApiConstants.adminSupirs, query: {
        'page': '$page',
        'per_page': '15',
        if (filterStatusSupir != null) 'status': '$filterStatusSupir',
        if (kataKunciSupir.isNotEmpty) 'q': kataKunciSupir,
      });

  Future<Hasil> simpanSupir(int? id, Map<String, dynamic> data, {File? gambar}) => _aksi(() {
        if (id != null) return ApiService.call('PUT', ApiConstants.adminSupir(id), body: data);
        return gambar == null
            ? ApiService.call('POST', ApiConstants.adminSupirs, body: data)
            : ApiService.multipart(ApiConstants.adminSupirs,
                fields: data.map((k, v) => MapEntry(k, '$v')), files: {'gambar': gambar});
      }, segarkan: [() => muatSupir(silent: true)]);

  // ── Penyewa ────────────────────────────────────────────────

  Future<void> aturKataKunciPenyewa(String q) {
    kataKunciPenyewa = q.trim();
    return muatPenyewa();
  }

  Future<void> muatPenyewa({bool silent = false}) async {
    final gen = ++_genPenyewa;
    if (!silent) {
      penyewa = penyewa.copy(items: const [], page: 0, loading: true, clearError: true);
      _notify();
    }
    final h = await ambilHalaman<PenyewaModel>(penyewa, 1, silent: silent, ambil: _ambilPenyewa, urai: PenyewaModel.fromJson);
    if (gen != _genPenyewa) return;
    penyewa = h;
    _notify();
  }

  Future<void> penyewaLagi() async {
    if (!penyewa.hasMore || penyewa.loadingMore || penyewa.loading) return;
    final gen = _genPenyewa;
    penyewa = penyewa.copy(loadingMore: true, clearError: true);
    _notify();
    final h = await ambilHalaman<PenyewaModel>(penyewa, penyewa.page + 1, silent: false, ambil: _ambilPenyewa, urai: PenyewaModel.fromJson);
    if (gen != _genPenyewa) return;
    penyewa = h;
    _notify();
  }

  Future<ApiResponse<Map<String, dynamic>>> _ambilPenyewa(int page) => ApiService.call('GET', ApiConstants.adminPenyewa, query: {
        'page': '$page',
        'per_page': '15',
        if (kataKunciPenyewa.isNotEmpty) 'q': kataKunciPenyewa,
      });

  Future<Hasil> simpanPenyewa(int? id, Map<String, dynamic> data) => _aksi(
        () => id == null
            ? ApiService.call('POST', ApiConstants.adminPenyewa, body: data)
            : ApiService.call('PUT', ApiConstants.adminPenyewaItem(id), body: data),
        segarkan: [() => muatPenyewa(silent: true)],
      );

  // ── Rekap ──────────────────────────────────────────────────

  Future<void> muatRekap([String? bulan]) async {
    if (bulan != null) bulanRekap = bulan;
    final gen = ++_genRekap;
    rekap = rekap.copy(loading: true, clearError: true);
    _notify();
    try {
      final res = await ApiService.call('GET', ApiConstants.adminRekap, query: {'bulan': bulanRekap});
      if (gen != _genRekap) return;
      if (res.success && res.data != null) {
        rekap = Muat<RekapModel?>(value: RekapModel.fromJson(res.data!['data'] as Map<String, dynamic>), loaded: true);
      } else {
        rekap = rekap.copy(loading: false, loaded: true, error: res.displayMessage);
      }
    } catch (_) {
      if (gen != _genRekap) return;
      rekap = rekap.copy(loading: false, loaded: true, error: 'Data tidak dapat dimuat.');
    }
    _notify();
  }

  // ── Umum ───────────────────────────────────────────────────

  Future<Hasil> _aksi(
    Future<ApiResponse<Map<String, dynamic>>> Function() panggil, {
    List<Future<void> Function()> segarkan = const [],
  }) async {
    if (_submitting) return const Hasil(galat: 'Mohon tunggu, permintaan sebelumnya masih diproses.');
    _submitting = true;
    _notify();
    try {
      final res = await panggil();
      if (!res.success) {
        return Hasil(galat: res.displayMessage.isNotEmpty ? res.displayMessage : 'Permintaan gagal.');
      }
      await Future.wait(segarkan.map((f) => f()));
      return Hasil.sukses;
    } catch (e) {
      return Hasil(galat: 'Terjadi kesalahan: $e');
    } finally {
      _submitting = false;
      _notify();
    }
  }
}
