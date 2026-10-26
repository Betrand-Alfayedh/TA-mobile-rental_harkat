/// Pemeriksaan bukti pembayaran sebelum diunggah. Aturan sama dengan server
/// (`bukti_bayar`: jpg/jpeg/png, maksimal 5 MB).
class BuktiValidator {
  static const maksBytes = 5 * 1024 * 1024;
  static const ekstensi = {'jpg', 'jpeg', 'png'};

  /// Mengembalikan pesan galat, atau null bila berkas boleh diunggah.
  static String? periksa({required String path, required int bytes}) {
    final titik = path.lastIndexOf('.');
    final ext = titik < 0 ? '' : path.substring(titik + 1).toLowerCase();
    if (!ekstensi.contains(ext)) {
      return 'Format foto harus JPG atau PNG.';
    }
    if (bytes <= 0) {
      return 'Foto kosong atau rusak. Pilih foto lain.';
    }
    if (bytes > maksBytes) {
      return 'Ukuran foto maksimal 5 MB.';
    }
    return null;
  }
}
