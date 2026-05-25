import 'package:flutter/foundation.dart';
import '../core/services/api_service.dart';
import '../models/mobil_model.dart';

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
    final res = await ApiService.getTipeMobils();
    if (res.success && res.data != null) {
      final list = res.data!['data'] as List<dynamic>;
      _tipes = list.map((e) => TipeMobilModel.fromJson(e as Map<String, dynamic>)).toList();
      notifyListeners();
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

    _state = MobilLoadState.loading;
    notifyListeners();

    final res = await ApiService.getMobils(
      search:  _search,
      type:    _type,
      page:    _currentPage,
      perPage: 10,
    );

    if (res.success && res.data != null) {
      final list = res.data!['data'] as List<dynamic>;
      final meta = res.data!['meta'] as Map<String, dynamic>?;
      final fetched = list.map((e) => MobilModel.fromJson(e as Map<String, dynamic>)).toList();

      if (reset) {
        _mobils = fetched;
      } else {
        _mobils.addAll(fetched);
      }

      _lastPage    = meta?['last_page'] as int? ?? 1;
      _hasMore     = _currentPage < _lastPage;
      _currentPage++;
      _state       = MobilLoadState.loaded;
    } else {
      _error = res.message;
      _state = MobilLoadState.error;
    }
    notifyListeners();
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
    final res = await ApiService.getMobilDetail(id);
    if (res.success && res.data != null) {
      return MobilModel.fromJson(res.data!['data'] as Map<String, dynamic>);
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
    notifyListeners();
  }
}
