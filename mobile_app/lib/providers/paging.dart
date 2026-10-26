import '../core/services/api_service.dart';
import '../models/admin_model.dart';
import 'muat.dart';

/// Hasil sebuah aksi: [galat] berisi pesan bila gagal, [info] pesan tambahan bila berhasil
/// (mis. "invoice gagal dikirim").
class Hasil {
  final String? galat;
  final String? info;
  const Hasil({this.galat, this.info});

  bool get ok => galat == null;
  static const sukses = Hasil();
}

/// Hitung keadaan baru sebuah daftar berhalaman dari satu panggilan API.
///
/// Mode [silent] (penyegaran latar belakang) tidak menghapus data lama saat gagal.
/// Respons rusak tidak melempar dan tidak membuat layar macet di loading.
Future<Halaman<T>> ambilHalaman<T>(
  Halaman<T> sekarang,
  int page, {
  required bool silent,
  required Future<ApiResponse<Map<String, dynamic>>> Function(int page) ambil,
  required T Function(Map<String, dynamic>) urai,
}) async {
  try {
    final res = await ambil(page);
    if (res.success && res.data != null) {
      final h = HasilHalaman<T>.fromJson(res.data!, urai);
      return sekarang.copy(
        items:       page <= 1 ? h.items : [...sekarang.items, ...h.items],
        page:        h.meta.page,
        lastPage:    h.meta.lastPage,
        total:       h.meta.total,
        loading:     false,
        loadingMore: false,
        loaded:      true,
        clearError:  true,
      );
    }
    return silent
        ? sekarang.copy(loading: false, loadingMore: false)
        : sekarang.copy(loading: false, loadingMore: false, loaded: true, error: res.displayMessage);
  } catch (_) {
    return silent
        ? sekarang.copy(loading: false, loadingMore: false)
        : sekarang.copy(loading: false, loadingMore: false, loaded: true, error: 'Data tidak dapat dimuat.');
  }
}
