// ─────────────────────────────────────────────────────────────
//  Keadaan data untuk layar: sedang dimuat / galat / berisi.
// ─────────────────────────────────────────────────────────────

/// Keadaan satu data (dashboard, detail, dsb.).
class Muat<T> {
  final bool loading;
  final bool loaded;
  final String? error;
  final T value;
  const Muat({required this.value, this.loading = false, this.loaded = false, this.error});

  Muat<T> copy({T? value, bool? loading, bool? loaded, String? error, bool clearError = false}) => Muat<T>(
        value:   value ?? this.value,
        loading: loading ?? this.loading,
        loaded:  loaded ?? this.loaded,
        error:   clearError ? null : (error ?? this.error),
      );
}

/// Daftar berhalaman (paginasi dari API): isi, halaman, dan keadaan muat pertama / muat lanjutan.
class Halaman<T> {
  final List<T> items;
  final int page;
  final int lastPage;
  final int total;
  final bool loading;       // memuat halaman pertama
  final bool loadingMore;   // memuat halaman berikutnya
  final bool loaded;
  final String? error;

  const Halaman({
    this.items = const [],
    this.page = 0,
    this.lastPage = 1,
    this.total = 0,
    this.loading = false,
    this.loadingMore = false,
    this.loaded = false,
    this.error,
  });

  bool get hasMore => page < lastPage;

  Halaman<T> copy({
    List<T>? items,
    int? page,
    int? lastPage,
    int? total,
    bool? loading,
    bool? loadingMore,
    bool? loaded,
    String? error,
    bool clearError = false,
  }) =>
      Halaman<T>(
        items:       items ?? this.items,
        page:        page ?? this.page,
        lastPage:    lastPage ?? this.lastPage,
        total:       total ?? this.total,
        loading:     loading ?? this.loading,
        loadingMore: loadingMore ?? this.loadingMore,
        loaded:      loaded ?? this.loaded,
        error:       clearError ? null : (error ?? this.error),
      );
}
