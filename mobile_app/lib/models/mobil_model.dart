class TipeMobilModel {
  final int id;
  final String namaTipe;

  const TipeMobilModel({required this.id, required this.namaTipe});

  factory TipeMobilModel.fromJson(Map<String, dynamic> json) => TipeMobilModel(
        id:       json['id'] as int,
        namaTipe: json['nama_tipe'] as String? ?? '',
      );
}

class MasterMobilModel {
  final int id;
  final String nama;
  final TipeMobilModel? tipe;

  const MasterMobilModel({required this.id, required this.nama, this.tipe});

  factory MasterMobilModel.fromJson(Map<String, dynamic> json) =>
      MasterMobilModel(
        id:   json['id'] as int,
        nama: json['nama'] as String? ?? '',
        tipe: json['tipe'] != null
            ? TipeMobilModel.fromJson(json['tipe'] as Map<String, dynamic>)
            : null,
      );
}

class MobilModel {
  final int id;
  final String platNomor;
  final String merk;
  final int tahun;
  final int hargaSewa;
  final int hargaAllIn;
  final int status;
  final String? statusText;
  final String? gambar;
  final MasterMobilModel? masterMobil;
  final List<String> images;

  const MobilModel({
    required this.id,
    required this.platNomor,
    required this.merk,
    required this.tahun,
    required this.hargaSewa,
    required this.hargaAllIn,
    required this.status,
    this.statusText,
    this.gambar,
    this.masterMobil,
    this.images = const [],
  });

  factory MobilModel.fromJson(Map<String, dynamic> json) => MobilModel(
        id:          json['id'] as int,
        platNomor:   json['plat_nomor'] as String? ?? '',
        merk:        json['merk'] as String? ?? '',
        tahun:       json['tahun'] as int? ?? 0,
        hargaSewa:   json['harga_sewa'] as int? ?? 0,
        hargaAllIn:  json['harga_all_in'] as int? ?? 0,
        status:      json['status'] as int? ?? 1,
        statusText:  json['status_text'] as String?,
        gambar:      json['gambar'] as String?,
        masterMobil: json['master_mobil'] != null
            ? MasterMobilModel.fromJson(
                json['master_mobil'] as Map<String, dynamic>)
            : null,
        images: (json['images'] as List<dynamic>?)
                ?.map((e) => e.toString())
                .toList() ??
            [],
      );

  bool get isAvailable => status == 1;

  String get namaLengkap =>
      masterMobil != null ? '${masterMobil!.nama} ($merk)' : merk;

  String? get tipeNama => masterMobil?.tipe?.namaTipe;

  String get primaryImage {
    if (images.isNotEmpty) return images.first;
    if (gambar != null && gambar!.isNotEmpty) return gambar!;
    return '';
  }
}
