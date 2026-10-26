import 'sopir_model.dart';

// ─────────────────────────────────────────────────────────────
//  Model data master untuk admin: sopir, penyewa, referensi armada, rekap.
// ─────────────────────────────────────────────────────────────

Map<String, dynamic>? _map(dynamic v) => v is Map<String, dynamic> ? v : null;
List<Map<String, dynamic>> _list(dynamic v) =>
    v is List ? v.whereType<Map<String, dynamic>>().toList() : const [];

class SupirAdminModel {
  final int id;
  final String nama;
  final String? email;
  final String? noHp;
  final String? alamat;
  final bool aktif;
  final int status;              // 0 libur, 1 tersedia, 2 bertugas
  final String statusText;
  final String? gambar;

  const SupirAdminModel({
    required this.id,
    required this.nama,
    this.email,
    this.noHp,
    this.alamat,
    required this.aktif,
    required this.status,
    required this.statusText,
    this.gambar,
  });

  factory SupirAdminModel.fromJson(Map<String, dynamic> j) => SupirAdminModel(
        id:         asInt(j['id']),
        nama:       j['nama']?.toString() ?? '',
        email:      j['email'] as String?,
        noHp:       j['no_hp'] as String?,
        alamat:     j['alamat'] as String?,
        aktif:      j['aktif'] != false,
        status:     asInt(j['status']),
        statusText: j['status_text']?.toString() ?? '',
        gambar:     j['gambar'] as String?,
      );

  bool get bertugas => status == 2;
}

class PenyewaModel {
  final int id;
  final String name;
  final String email;
  final String? noHp;
  final String? alamat;
  final String? asalKota;
  final int? jumlahPesanan;

  const PenyewaModel({
    required this.id,
    required this.name,
    required this.email,
    this.noHp,
    this.alamat,
    this.asalKota,
    this.jumlahPesanan,
  });

  factory PenyewaModel.fromJson(Map<String, dynamic> j) => PenyewaModel(
        id:            asInt(j['id']),
        name:          j['name']?.toString() ?? '',
        email:         j['email']?.toString() ?? '',
        noHp:          j['no_hp'] as String?,
        alamat:        j['alamat'] as String?,
        asalKota:      j['asal_kota'] as String?,
        jumlahPesanan: j['jumlah_pesanan'] == null ? null : asInt(j['jumlah_pesanan']),
      );
}

/// Pilihan untuk form armada: tipe dan model mobil.
class ReferensiArmadaModel {
  final List<({int id, String nama})> tipe;
  final List<({int id, String nama, int tipeId})> model;

  const ReferensiArmadaModel({this.tipe = const [], this.model = const []});

  factory ReferensiArmadaModel.fromJson(Map<String, dynamic> j) => ReferensiArmadaModel(
        tipe:  _list(j['tipe']).map((e) => (id: asInt(e['id']), nama: e['nama_tipe']?.toString() ?? '')).toList(),
        model: _list(j['model']).map((e) => (id: asInt(e['id']), nama: e['nama']?.toString() ?? '', tipeId: asInt(e['tipe_id']))).toList(),
      );
}

class RekapMobilModel {
  final int mobilId;
  final String? nama;
  final String? platNomor;
  final int jumlahSewa;
  final int totalHari;

  const RekapMobilModel({required this.mobilId, this.nama, this.platNomor, required this.jumlahSewa, required this.totalHari});

  factory RekapMobilModel.fromJson(Map<String, dynamic> j) => RekapMobilModel(
        mobilId:    asInt(j['mobil_id']),
        nama:       j['nama'] as String?,
        platNomor:  j['plat_nomor'] as String?,
        jumlahSewa: asInt(j['jumlah_sewa']),
        totalHari:  asInt(j['total_hari']),
      );
}

class RekapModel {
  final String bulan;
  final int pesananTotal, booked, berjalan, selesai, dibatalkan;
  final int dp, pelunasan, pendapatanTotal, dendaTercatat;
  final int unitDisewa, hariSewa;
  final double utilisasiPersen;
  final List<RekapMobilModel> perMobil;

  const RekapModel({
    required this.bulan,
    this.pesananTotal = 0, this.booked = 0, this.berjalan = 0, this.selesai = 0, this.dibatalkan = 0,
    this.dp = 0, this.pelunasan = 0, this.pendapatanTotal = 0, this.dendaTercatat = 0,
    this.unitDisewa = 0, this.hariSewa = 0, this.utilisasiPersen = 0,
    this.perMobil = const [],
  });

  factory RekapModel.fromJson(Map<String, dynamic> j) {
    final p = _map(j['pesanan']) ?? const {};
    final k = _map(j['pendapatan']) ?? const {};
    final a = _map(j['armada']) ?? const {};
    final util = a['utilisasi_persen'];
    return RekapModel(
      bulan:           j['bulan']?.toString() ?? '',
      pesananTotal:    asInt(p['total']),
      booked:          asInt(p['booked']),
      berjalan:        asInt(p['berjalan']),
      selesai:         asInt(p['selesai']),
      dibatalkan:      asInt(p['dibatalkan']),
      dp:              asInt(k['dp']),
      pelunasan:       asInt(k['pelunasan']),
      pendapatanTotal: asInt(k['total']),
      dendaTercatat:   asInt(k['denda_tercatat']),
      unitDisewa:      asInt(a['total_unit_disewa']),
      hariSewa:        asInt(a['total_hari_sewa']),
      utilisasiPersen: util is num ? util.toDouble() : double.tryParse('$util') ?? 0,
      perMobil:        _list(a['per_mobil']).map(RekapMobilModel.fromJson).toList(),
    );
  }
}
