import 'package:flutter/foundation.dart';
import '../core/services/api_service.dart';
import '../models/mobil_model.dart';
import '../models/sopir_model.dart' show asInt;

enum MobilLoadState { initial, loading, loaded, error }

class MobilProvider extends ChangeNotifier {
  // ── Car list state ─────────────────────────────────────────
  List<MobilModel>    _mobils     = [];
  List<TipeMobilModel> _tipes     = [];
  List<MobilModel>    _available  = [];
  MobilLoadState      _state      = MobilLoadState.initial;
  MobilLoadState      _availState = MobilLoadState.initial;
  String              _error      = '';

  // Pagination
  int  _currentPage = 1;
  int  _lastPage    = 1;
  bool _hasMore     = true;

  int _gen = 0;

  // Filters
  String _search = '';
  String _type   = '';

  // Getters
  List<MobilModel>    get mobils    => _mobils;
  List<TipeMobilModel> get tipes    => _tipes;
  List<MobilModel>    get available => _available;
  MobilLoadState      get state     => _state;
  MobilLoadState      get availState => _availState;
  String              get error     => _error;
  bool                get hasMore   => _hasMore;
  String              get search    => _search;
  String              get type      => _type;
  bool get isLoading  => _state == MobilLoadState.loading;

  // ── Load car types ─────────────────────────────────────────
  Future<void> loadTipes() async {
    if (_tipes.isNotEmpty) return;
    try {
      final res = await ApiService.getTipeMobils();
      if (res.success && res.data != null) {
        final list = res.data!['data'] as List<dynamic>;
        _tipes = list.map((e) => TipeMobilModel.fromJson(e as Map<String, dynamic>)).toList();
        notifyListeners();
      }
    } catch (_) {
      // filter tipe bersifat opsional: abaikan
    }
  }

  // ── Load first page ────────────────────────────────────────
  Future<void> loadMobils({bool reset = false}) async {
    if (reset) {
      _currentPage = 1;
      _hasMore     = true;
      _mobils      = [];
    }
    if (!_hasMore && !reset) return;

    // Nomor permintaan: jawaban lama (filter sudah berganti) tidak boleh menimpa hasil terbaru
    // atau menambah halaman yang salah ke daftar.
    final gen = ++_gen;
    _state = MobilLoadState.loading;
    notifyListeners();

    try {
      final res = await ApiService.getMobils(
        search:  _search,
        type:    _type,
        page:    _currentPage,
        perPage: 10,
      );

      if (gen != _gen) return;   // sudah ada permintaan yang lebih baru
      if (res.success && res.data != null) {
        final list = res.data!['data'] as List<dynamic>;
        final meta = res.data!['meta'] as Map<String, dynamic>?;
        final fetched = list.map((e) => MobilModel.fromJson(e as Map<String, dynamic>)).toList();

        if (reset) {
          _mobils = fetched;
        } else {
          _mobils.addAll(fetched);
        }

        _lastPage    = asInt(meta?['last_page'], 1);
        _hasMore     = _currentPage < _lastPage;
        _currentPage++;
        _state       = MobilLoadState.loaded;
      } else {
        _gagal(res.message);
      }
    } catch (e) {
      if (gen != _gen) return;
      // Respons tak terduga: jangan biarkan spinner katalog berputar selamanya.
      _gagal('Data mobil tidak dapat dibaca: $e');
    }
    notifyListeners();
  }

  /// Galat memuat. Bila daftar sudah berisi (gagal "muat lagi"), daftar dipertahankan dan hanya
  /// halaman berikutnya yang dicoba ulang saat digulir; layar galat penuh hanya untuk daftar kosong.
  void _gagal(String pesan) {
    _error = pesan;
    _state = _mobils.isEmpty ? MobilLoadState.error : MobilLoadState.loaded;
  }

  /// Dibuang saat logout / sesi berakhir agar data pengguna sebelumnya tidak terbawa.
  void reset() {
    _gen++;
    _mobils = [];
    _tipes = [];
    _available = [];
    _state = MobilLoadState.initial;
    _availState = MobilLoadState.initial;
    _error = '';
    _search = '';
    _type = '';
    _currentPage = 1;
    _lastPage = 1;
    _hasMore = true;
  }

  // ── Load more (infinite scroll) ────────────────────────────
  Future<void> loadMore() async {
    if (!_hasMore || _state == MobilLoadState.loading) return;
    await loadMobils();
  }

  // ── Apply search & type filter ─────────────────────────────
  Future<void> applyFilter({String? search, String? type}) async {
    _search = search ?? _search;
    _type   = type   ?? _type;
    await loadMobils(reset: true);
  }

  Future<void> clearFilter() async {
    _search = '';
    _type   = '';
    await loadMobils(reset: true);
  }

  // ── Single car detail ──────────────────────────────────────
  Future<MobilModel?> getMobilDetail(int id) async {
    try {
      final res = await ApiService.getMobilDetail(id);
      if (res.success && res.data != null) {
        return MobilModel.fromJson(res.data!['data'] as Map<String, dynamic>);
      }
    } catch (_) {
      // dianggap gagal memuat
    }
    return null;
  }

  // ── Available cars between dates ───────────────────────────
  Future<void> loadAvailable({
    required String tanggalSewa,
    required String tanggalKembali,
  }) async {
    _availState = MobilLoadState.loading;
    _available  = [];
    notifyListeners();

    try {
      final res = await ApiService.getAvailableMobils(
        tanggalSewa:    tanggalSewa,
        tanggalKembali: tanggalKembali,
      );

      if (res.success && res.data != null) {
        final list = res.data!['data'] as List<dynamic>;
        _available  = list.map((e) => MobilModel.fromJson(e as Map<String, dynamic>)).toList();
        _availState = MobilLoadState.loaded;
      } else {
        _error      = res.message;
        _availState = MobilLoadState.error;
      }
    } catch (e) {
      _error      = 'Data mobil tidak dapat dibaca: $e';
      _availState = MobilLoadState.error;
    }
    notifyListeners();
  }
}
