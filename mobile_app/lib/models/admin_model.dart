import 'sopir_model.dart';

// ─────────────────────────────────────────────────────────────
//  Model sisi admin: dashboard, pesanan, unit sewa, pembayaran.
//  Parser toleran: angka boleh berupa int, double, atau string.
// ─────────────────────────────────────────────────────────────

Map<String, dynamic>? _map(dynamic v) => v is Map<String, dynamic> ? v : null;
List<Map<String, dynamic>> _list(dynamic v) =>
    v is List ? v.whereType<Map<String, dynamic>>().toList() : const [];

class PembayaranAdminModel {
  final int id;
  final int bookingId;
  final int jenis;                 // 1 DP, 2 pelunasan
  final String jenisText;
  final int jumlah;
  final int? metode;               // 1 tunai, 2 transfer, 3 QRIS
  final String? metodeText;
  final int status;                // 0 belum bayar, 2 menunggu verifikasi, 1 lunas
  final String statusText;
  final String? fotoBukti;
  final String? tanggalPembayaran;
  final String? jatuhTempo;
  final String? catatanAdmin;
  final String? keterangan;
  final String? penyewa;
  final int totalPesanan;
  final String? statusPesanan;

  const PembayaranAdminModel({
    required this.id,
    required this.bookingId,
    required this.jenis,
    required this.jenisText,
    required this.jumlah,
    this.metode,
    this.metodeText,
    required this.status,
    required this.statusText,
    this.fotoBukti,
    this.tanggalPembayaran,
    this.jatuhTempo,
    this.catatanAdmin,
    this.keterangan,
    this.penyewa,
    this.totalPesanan = 0,
    this.statusPesanan,
  });

  factory PembayaranAdminModel.fromJson(Map<String, dynamic> j) => PembayaranAdminModel(
        id:                asInt(j['id']),
        bookingId:         asInt(j['booking_id']),
        jenis:             asInt(j['jenis'], 1),
        jenisText:         j['jenis_text']?.toString() ?? '',
        jumlah:            asInt(j['jumlah']),
        metode:            j['metode_pembayaran'] == null ? null : asInt(j['metode_pembayaran']),
        metodeText:        j['metode_pembayaran_text']?.toString(),
        status:            asInt(j['status_pembayaran']),
        statusText:        j['status_pembayaran_text']?.toString() ?? '',
        fotoBukti:         j['foto_bukti'] as String?,
        tanggalPembayaran: j['tanggal_pembayaran'] as String?,
        jatuhTempo:        j['jatuh_tempo'] as String?,
        catatanAdmin:      j['catatan_admin'] as String?,
        keterangan:        j['keterangan'] as String?,
        penyewa:           j['penyewa'] as String?,
        totalPesanan:      asInt(j['total_pesanan']),
        statusPesanan:     j['status_pesanan']?.toString(),
      );

  bool get belumBayar => status == 0;
  bool get lunas => status == 1;
  bool get menungguVerifikasi => status == 2;
  bool get adalahDp => jenis == 1;

  String get judul => keterangan != null && keterangan!.isNotEmpty ? '$jenisText - $keterangan' : jenisText;
}

class PengembalianAdminModel {
  final String? tanggalKembaliAktual;
  final int kondisi;               // 1 baik, 0 rusak
  final String kondisiLabel;
  final bool dendaFlag;
  final int nominalDenda;
  final String? catatan;
  final String? gambar;

  const PengembalianAdminModel({
    this.tanggalKembaliAktual,
    required this.kondisi,
    required this.kondisiLabel,
    required this.dendaFlag,
    required this.nominalDenda,
    this.catatan,
    this.gambar,
  });

  factory PengembalianAdminModel.fromJson(Map<String, dynamic> j) => PengembalianAdminModel(
        tanggalKembaliAktual: j['tanggal_kembali_aktual']?.toString(),
        kondisi:              asInt(j['kondisi'], 1),
        kondisiLabel:         j['kondisi_label']?.toString() ?? '',
        dendaFlag:            j['denda_flag'] == true || asInt(j['denda_flag']) == 1,
        nominalDenda:         asInt(j['nominal_denda']),
        catatan:              j['catatan'] as String?,
        gambar:               j['gambar'] as String?,
      );
}

/// Satu unit mobil di dalam pesanan.
class UnitAdminModel {
  final int id;
  final int status;                // 0 batal, 1 booked, 2 ongoing, 3 selesai
  final String statusLabel;
  final String? mobilNama;
  final String? mobilMerk;
  final String? platNomor;
  final String? gambar;
  final bool pakaiSupir;
  final int? supirId;
  final String? supirNama;
  final String? supirNoHp;
  final bool menungguSupir;        // ada tawaran terbuka ke sopir
  final String? tanggalSewa;
  final String? tanggalKembali;
  final int lamaSewa;
  final int harga;
  final LaporanKondisiModel? laporanSebelum;
  final LaporanKondisiModel? laporanSesudah;
  final PengembalianAdminModel? pengembalian;

  const UnitAdminModel({
    required this.id,
    required this.status,
    required this.statusLabel,
    this.mobilNama,
    this.mobilMerk,
    this.platNomor,
    this.gambar,
    required this.pakaiSupir,
    this.supirId,
    this.supirNama,
    this.supirNoHp,
    this.menungguSupir = false,
    this.tanggalSewa,
    this.tanggalKembali,
    this.lamaSewa = 0,
    this.harga = 0,
    this.laporanSebelum,
    this.laporanSesudah,
    this.pengembalian,
  });

  factory UnitAdminModel.fromJson(Map<String, dynamic> j) {
    final mobil   = _map(j['mobil']);
    final master  = _map(mobil?['master_mobil']);
    final supir   = _map(j['supir']);
    final sebelum = _map(j['laporan_sebelum']);
    final sesudah = _map(j['laporan_sesudah']);
    final kembali = _map(j['pengembalian']);
    return UnitAdminModel(
      id:             asInt(j['id']),
      status:         asInt(j['status'], 1),
      statusLabel:    j['status_label']?.toString() ?? '',
      mobilNama:      master?['nama'] as String?,
      mobilMerk:      mobil?['merk'] as String?,
      platNomor:      mobil?['plat_nomor'] as String?,
      gambar:         mobil?['gambar'] as String?,
      pakaiSupir:     j['pakai_supir'] == true || asInt(j['pakai_supir']) > 0,
      supirId:        j['supir_id'] == null ? null : asInt(j['supir_id']),
      supirNama:      supir?['nama'] as String?,
      supirNoHp:      supir?['no_hp'] as String?,
      menungguSupir:  j['menunggu_supir'] == true,
      tanggalSewa:    j['tanggal_sewa']?.toString(),
      tanggalKembali: j['tanggal_kembali']?.toString(),
      lamaSewa:       asInt(j['lama_sewa']),
      harga:          asInt(j['harga']),
      laporanSebelum: sebelum != null ? LaporanKondisiModel.fromJson(sebelum) : null,
      laporanSesudah: sesudah != null ? LaporanKondisiModel.fromJson(sesudah) : null,
      pengembalian:   kembali != null ? PengembalianAdminModel.fromJson(kembali) : null,
    );
  }

  String get namaLengkap => [mobilMerk, mobilNama].where((e) => e != null && e.isNotEmpty).join(' ');

  /// Unit bersopir yang belum punya sopir dan belum ada tawaran terbuka → admin perlu menugaskan.
  bool get perluPenugasan => pakaiSupir && supirId == null && !menungguSupir && status == 1;

  bool get bisaLaporSebelum => status == 1 || status == 2;
  bool get bisaLaporSesudah => status == 2 || status == 3;
}

class BookingAdminModel {
  final int id;
  final int status;                // 0 batal, 1 booked, 2 ongoing, 3 selesai
  final String statusLabel;
  final String? tanggalBooking;
  final String? asalKotaLabel;
  final String? namaKota;
  final String? jaminanLabel;
  final int totalHarga;
  final int uangMuka;
  final int penyewaId;
  final String penyewaNama;
  final String? penyewaEmail;
  final String? penyewaNoHp;
  final String? penyewaAlamat;
  final List<PembayaranAdminModel> pembayarans;
  final List<UnitAdminModel> units;

  const BookingAdminModel({
    required this.id,
    required this.status,
    required this.statusLabel,
    this.tanggalBooking,
    this.asalKotaLabel,
    this.namaKota,
    this.jaminanLabel,
    required this.totalHarga,
    required this.uangMuka,
    this.penyewaId = 0,
    this.penyewaNama = '',
    this.penyewaEmail,
    this.penyewaNoHp,
    this.penyewaAlamat,
    this.pembayarans = const [],
    this.units = const [],
  });

  factory BookingAdminModel.fromJson(Map<String, dynamic> j) {
    final p = _map(j['penyewa']);
    return BookingAdminModel(
      id:             asInt(j['id']),
      status:         asInt(j['status'], 1),
      statusLabel:    j['status_label']?.toString() ?? '',
      tanggalBooking: (j['tanggal_booking_format'] ?? j['tanggal_booking'])?.toString(),
      asalKotaLabel:  j['asal_kota_label']?.toString(),
      namaKota:       j['nama_kota'] as String?,
      jaminanLabel:   j['jaminan_label']?.toString(),
      totalHarga:     asInt(j['total_harga']),
      uangMuka:       asInt(j['uang_muka']),
      penyewaId:      asInt(p?['id']),
      penyewaNama:    p?['name']?.toString() ?? '',
      penyewaEmail:   p?['email'] as String?,
      penyewaNoHp:    p?['no_hp'] as String?,
      penyewaAlamat:  p?['alamat'] as String?,
      pembayarans:    _list(j['pembayarans']).map(PembayaranAdminModel.fromJson).toList(),
      units:          _list(j['details']).map(UnitAdminModel.fromJson).toList(),
    );
  }

  PembayaranAdminModel? get dp => pembayarans.where((p) => p.jenis == 1).firstOrNull;
  bool get dpLunas => dp?.lunas ?? false;

  /// Pesanan hanya dapat dibatalkan selama masih Booked.
  bool get bisaDibatalkan => status == 1;

  int get jumlahMenungguVerifikasi => pembayarans.where((p) => p.menungguVerifikasi).length;
  int get sisaTagihan => pembayarans.where((p) => !p.lunas).fold(0, (a, p) => a + p.jumlah);

  /// Unit yang perlu ditugaskan sopirnya (untuk lencana "perlu tindakan").
  int get perluPenugasan => units.where((u) => u.perluPenugasan).length;
}

class DashboardAdminModel {
  final int masukHariIni, booked, berjalan, selesai, dibatalkan;
  final int menungguVerifikasi, perluPenugasan, jemputHariIni, kembaliHariIni, terlambat;
  final int armadaTotal, armadaTersedia, armadaDisewa, armadaMaintenance, armadaRusak;
  final int sopirTersedia, sopirBertugas, sopirLibur;

  const DashboardAdminModel({
    this.masukHariIni = 0, this.booked = 0, this.berjalan = 0, this.selesai = 0, this.dibatalkan = 0,
    this.menungguVerifikasi = 0, this.perluPenugasan = 0, this.jemputHariIni = 0, this.kembaliHariIni = 0, this.terlambat = 0,
    this.armadaTotal = 0, this.armadaTersedia = 0, this.armadaDisewa = 0, this.armadaMaintenance = 0, this.armadaRusak = 0,
    this.sopirTersedia = 0, this.sopirBertugas = 0, this.sopirLibur = 0,
  });

  factory DashboardAdminModel.fromJson(Map<String, dynamic> j) {
    final p = _map(j['pesanan']) ?? const {};
    final t = _map(j['perlu_tindakan']) ?? const {};
    final a = _map(j['armada']) ?? const {};
    final s = _map(j['sopir']) ?? const {};
    return DashboardAdminModel(
      masukHariIni:       asInt(p['masuk_hari_ini']),
      booked:             asInt(p['booked']),
      berjalan:           asInt(p['berjalan']),
      selesai:            asInt(p['selesai']),
      dibatalkan:         asInt(p['dibatalkan']),
      menungguVerifikasi: asInt(t['pembayaran_menunggu_verifikasi']),
      perluPenugasan:     asInt(t['perlu_penugasan_sopir']),
      jemputHariIni:      asInt(t['jemput_hari_ini']),
      kembaliHariIni:     asInt(t['kembali_hari_ini']),
      terlambat:          asInt(t['terlambat_kembali']),
      armadaTotal:        asInt(a['total']),
      armadaTersedia:     asInt(a['tersedia']),
      armadaDisewa:       asInt(a['disewa']),
      armadaMaintenance:  asInt(a['maintenance']),
      armadaRusak:        asInt(a['rusak']),
      sopirTersedia:      asInt(s['tersedia']),
      sopirBertugas:      asInt(s['bertugas']),
      sopirLibur:         asInt(s['libur']),
    );
  }

  /// Jumlah hal yang butuh perhatian admin sekarang.
  int get totalPerluTindakan => menungguVerifikasi + perluPenugasan + terlambat;
}

/// Meta paginasi dari API: `{page, per_page, total, last_page}`.
class MetaHalaman {
  final int page, lastPage, total;
  const MetaHalaman({this.page = 1, this.lastPage = 1, this.total = 0});

  factory MetaHalaman.fromJson(Map<String, dynamic>? j) => MetaHalaman(
        page:     asInt(j?['page'], 1),
        lastPage: asInt(j?['last_page'], 1),
        total:    asInt(j?['total']),
      );
}

/// Hasil penguraian respons berhalaman.
class HasilHalaman<T> {
  final List<T> items;
  final MetaHalaman meta;
  const HasilHalaman(this.items, this.meta);

  factory HasilHalaman.fromJson(Map<String, dynamic> body, T Function(Map<String, dynamic>) parse) =>
      HasilHalaman(_list(body['data']).map(parse).toList(), MetaHalaman.fromJson(_map(body['meta'])));
}

/// Denda keterlambatan: Rp50.000 per jam yang sudah dimulai (sama dengan aturan server).
class DendaKeterlambatan {
  static const perJam = 50000;

  /// [rencana] jam kembali yang dijanjikan, [aktual] jam kembali sebenarnya.
  static int jam(DateTime rencana, DateTime aktual) {
    if (!aktual.isAfter(rencana)) return 0;
    return (aktual.difference(rencana).inMinutes / 60).ceil();
  }

  static int nominal(DateTime rencana, DateTime aktual) => jam(rencana, aktual) * perJam;
}
