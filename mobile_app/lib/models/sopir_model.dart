// ─────────────────────────────────────────────────────────────
//  Model sisi sopir: dashboard, tawaran, tugas (unit sewa), laporan kondisi.
//  Parser toleran: angka boleh datang sebagai int, double, atau string.
// ─────────────────────────────────────────────────────────────

int asInt(dynamic v, [int fallback = 0]) {
  if (v is int) return v;
  if (v is num) return v.toInt();
  final s = v?.toString() ?? '';
  // Kolom desimal dari MySQL datang sebagai string ("500000.00"): tetap terbaca sebagai angka.
  return int.tryParse(s) ?? double.tryParse(s)?.toInt() ?? fallback;
}

Map<String, dynamic>? _map(dynamic v) => v is Map<String, dynamic> ? v : null;

class LaporanKondisiModel {
  final int id;
  final int jenis;          // 1 sebelum sewa, 2 sesudah sewa
  final int kondisi;        // 1 baik, 2 lecet, 3 rusak
  final String kondisiLabel;
  final String? catatan;
  final String? foto;
  final String? dibuatPada;

  const LaporanKondisiModel({
    required this.id,
    required this.jenis,
    required this.kondisi,
    required this.kondisiLabel,
    this.catatan,
    this.foto,
    this.dibuatPada,
  });

  factory LaporanKondisiModel.fromJson(Map<String, dynamic> j) => LaporanKondisiModel(
        id:           asInt(j['id']),
        jenis:        asInt(j['jenis'], 1),
        kondisi:      asInt(j['kondisi'], 1),
        kondisiLabel: j['kondisi_label']?.toString() ?? '-',
        catatan:      j['catatan'] as String?,
        foto:         j['foto'] as String?,
        dibuatPada:   j['dibuat_pada'] as String?,
      );
}

/// Satu unit sewa yang ditugaskan (atau ditawarkan) ke sopir. Tidak memuat data keuangan.
class SopirUnitModel {
  final int id;
  final int status;         // 1 booked, 2 ongoing, 3 selesai, 0 batal
  final String statusLabel;
  final String? mobilNama;
  final String? mobilMerk;
  final String? platNomor;
  final String? gambar;
  final String? tanggalSewa;
  final String? tanggalKembali;
  final int lamaSewa;
  final String? penyewaNama;
  final String? penyewaNoHp;
  final String? asalKota;
  final LaporanKondisiModel? laporanSebelum;
  final LaporanKondisiModel? laporanSesudah;

  const SopirUnitModel({
    required this.id,
    required this.status,
    required this.statusLabel,
    this.mobilNama,
    this.mobilMerk,
    this.platNomor,
    this.gambar,
    this.tanggalSewa,
    this.tanggalKembali,
    this.lamaSewa = 0,
    this.penyewaNama,
    this.penyewaNoHp,
    this.asalKota,
    this.laporanSebelum,
    this.laporanSesudah,
  });

  factory SopirUnitModel.fromJson(Map<String, dynamic> j) {
    final mobil   = _map(j['mobil']);
    final penyewa = _map(j['penyewa']);
    final sebelum = _map(j['laporan_sebelum']);
    final sesudah = _map(j['laporan_sesudah']);
    return SopirUnitModel(
      id:             asInt(j['id']),
      status:         asInt(j['status'], 1),
      statusLabel:    j['status_label']?.toString() ?? '',
      mobilNama:      mobil?['nama'] as String?,
      mobilMerk:      mobil?['merk'] as String?,
      platNomor:      mobil?['plat_nomor'] as String?,
      gambar:         mobil?['gambar'] as String?,
      tanggalSewa:    j['tanggal_sewa']?.toString(),
      tanggalKembali: j['tanggal_kembali']?.toString(),
      lamaSewa:       asInt(j['lama_sewa']),
      penyewaNama:    penyewa?['nama'] as String?,
      penyewaNoHp:    penyewa?['no_hp'] as String?,
      asalKota:       j['asal_kota']?.toString(),
      laporanSebelum: sebelum != null ? LaporanKondisiModel.fromJson(sebelum) : null,
      laporanSesudah: sesudah != null ? LaporanKondisiModel.fromJson(sesudah) : null,
    );
  }

  String get namaLengkap => [mobilMerk, mobilNama].where((e) => e != null && e.isNotEmpty).join(' ');

  /// Laporan "sebelum" boleh dibuat selama unit belum selesai; "sesudah" setelah dijemput (ongoing).
  bool get bisaLaporSebelum => status == 1 || status == 2;
  bool get bisaLaporSesudah => status == 2 || status == 3;
}

class TawaranModel {
  final int id;
  final int status;
  final String? ditawarkanPada;
  final SopirUnitModel unit;

  const TawaranModel({
    required this.id,
    required this.status,
    this.ditawarkanPada,
    required this.unit,
  });

  factory TawaranModel.fromJson(Map<String, dynamic> j) => TawaranModel(
        id:             asInt(j['id']),
        status:         asInt(j['status']),
        ditawarkanPada: j['ditawarkan_pada'] as String?,
        unit:           SopirUnitModel.fromJson(_map(j['unit']) ?? const {}),
      );
}

class SopirDashboardModel {
  final String nama;
  final int status;         // 0 libur, 1 tersedia, 2 bertugas
  final String statusText;
  final int tugasAktif;
  final int tawaranBaru;
  final int tugasSelesai;

  const SopirDashboardModel({
    required this.nama,
    required this.status,
    required this.statusText,
    required this.tugasAktif,
    required this.tawaranBaru,
    required this.tugasSelesai,
  });

  factory SopirDashboardModel.fromJson(Map<String, dynamic> j) {
    final s = _map(j['sopir']) ?? const {};
    return SopirDashboardModel(
      nama:         s['nama']?.toString() ?? '',
      status:       asInt(s['status']),
      statusText:   s['status_text']?.toString() ?? '',
      tugasAktif:   asInt(j['tugas_aktif']),
      tawaranBaru:  asInt(j['tawaran_baru']),
      tugasSelesai: asInt(j['tugas_selesai']),
    );
  }

  bool get bertugas => status == 2;
  bool get tersedia => status == 1;
}
