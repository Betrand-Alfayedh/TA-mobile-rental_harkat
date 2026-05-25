import 'mobil_model.dart';

class PembayaranModel {
  final int id;
  final int bookingId;
  final int jenis;
  final String? jenisText;
  final int jumlah;
  final int? metodePembayaran;
  final String? metodePembayaranText;
  final int? statusPembayaran;
  final String? statusPembayaranText;
  final String? fotoBukti;
  final String? tanggalPembayaran;
  final String? jatuhTempo;
  final String? catatanAdmin;
  final String? keterangan;

  const PembayaranModel({
    required this.id,
    required this.bookingId,
    required this.jenis,
    this.jenisText,
    required this.jumlah,
    this.metodePembayaran,
    this.metodePembayaranText,
    this.statusPembayaran,
    this.statusPembayaranText,
    this.fotoBukti,
    this.tanggalPembayaran,
    this.jatuhTempo,
    this.catatanAdmin,
    this.keterangan,
  });

  factory PembayaranModel.fromJson(Map<String, dynamic> json) =>
      PembayaranModel(
        id:                    json['id'] as int,
        bookingId:             json['booking_id'] as int,
        jenis:                 json['jenis'] as int? ?? 1,
        jenisText:             json['jenis_text'] as String?,
        jumlah:                json['jumlah'] as int? ?? 0,
        metodePembayaran:      json['metode_pembayaran'] as int?,
        metodePembayaranText:  json['metode_pembayaran_text'] as String?,
        statusPembayaran:      json['status_pembayaran'] as int?,
        statusPembayaranText:  json['status_pembayaran_text'] as String?,
        fotoBukti:             json['foto_bukti'] as String?,
        tanggalPembayaran:     json['tanggal_pembayaran'] as String?,
        jatuhTempo:            json['jatuh_tempo'] as String?,
        catatanAdmin:          json['catatan_admin'] as String?,
        keterangan:            json['keterangan'] as String?,
      );

  bool get sudahBayar       => statusPembayaran == 1;
  bool get menungguVerifik  => statusPembayaran == 2;
  bool get belumBayar       => statusPembayaran == 0;
}

class BookingDetailModel {
  final int id;
  final MobilModel? mobil;
  final bool pakaiSupir;
  final String tanggalSewa;
  final String tanggalKembali;
  final int lamaSewa;
  final int harga;
  final int status;
  final String? statusLabel;
  final String? tanggalMulaiFormat;
  final String? tanggalSelesaiFormat;

  const BookingDetailModel({
    required this.id,
    this.mobil,
    required this.pakaiSupir,
    required this.tanggalSewa,
    required this.tanggalKembali,
    required this.lamaSewa,
    required this.harga,
    required this.status,
    this.statusLabel,
    this.tanggalMulaiFormat,
    this.tanggalSelesaiFormat,
  });

  factory BookingDetailModel.fromJson(Map<String, dynamic> json) =>
      BookingDetailModel(
        id:                  json['id'] as int,
        mobil:               json['mobil'] != null
            ? MobilModel.fromJson(json['mobil'] as Map<String, dynamic>)
            : null,
        pakaiSupir:          (json['pakai_supir'] as bool?) ?? false,
        tanggalSewa:         json['tanggal_sewa'] as String? ?? '',
        tanggalKembali:      json['tanggal_kembali'] as String? ?? '',
        lamaSewa:            json['lama_sewa'] as int? ?? 0,
        harga:               json['harga'] as int? ?? 0,
        status:              json['status'] as int? ?? 1,
        statusLabel:         json['status_label'] as String?,
        tanggalMulaiFormat:  json['tanggal_mulai_format'] as String?,
        tanggalSelesaiFormat: json['tanggal_selesai_format'] as String?,
      );
}

class BookingModel {
  final int id;
  final int userId;
  final String tanggalBooking;
  final String? tanggalBookingFormat;
  final int asalKota;
  final String? asalKotaLabel;
  final String? namaKota;
  final int jaminan;
  final String? jaminanLabel;
  final int uangMuka;
  final String? uangMukaRp;
  final int totalHarga;
  final String? totalHargaRp;
  final int status;
  final String? statusLabel;
  final List<BookingDetailModel> details;
  final PembayaranModel? pembayaranDp;
  final PembayaranModel? pelunasan;

  const BookingModel({
    required this.id,
    required this.userId,
    required this.tanggalBooking,
    this.tanggalBookingFormat,
    required this.asalKota,
    this.asalKotaLabel,
    this.namaKota,
    required this.jaminan,
    this.jaminanLabel,
    required this.uangMuka,
    this.uangMukaRp,
    required this.totalHarga,
    this.totalHargaRp,
    required this.status,
    this.statusLabel,
    this.details = const [],
    this.pembayaranDp,
    this.pelunasan,
  });

  factory BookingModel.fromJson(Map<String, dynamic> json) => BookingModel(
        id:                  json['id'] as int,
        userId:              json['user_id'] as int,
        tanggalBooking:      json['tanggal_booking'] as String? ?? '',
        tanggalBookingFormat: json['tanggal_booking_format'] as String?,
        asalKota:            json['asal_kota'] as int? ?? 1,
        asalKotaLabel:       json['asal_kota_label'] as String?,
        namaKota:            json['nama_kota'] as String?,
        jaminan:             json['jaminan'] as int? ?? 1,
        jaminanLabel:        json['jaminan_label'] as String?,
        uangMuka:            json['uang_muka'] as int? ?? 0,
        uangMukaRp:          json['uang_muka_rp'] as String?,
        totalHarga:          json['total_harga'] as int? ?? 0,
        totalHargaRp:        json['total_harga_rp'] as String?,
        status:              json['status'] as int? ?? 1,
        statusLabel:         json['status_label'] as String?,
        details:             (json['details'] as List<dynamic>?)
                ?.map((e) => BookingDetailModel.fromJson(e as Map<String, dynamic>))
                .toList() ??
            [],
        pembayaranDp: json['pembayaran_dp'] != null &&
                (json['pembayaran_dp'] as Map).isNotEmpty
            ? PembayaranModel.fromJson(
                json['pembayaran_dp'] as Map<String, dynamic>)
            : null,
        pelunasan: json['pelunasan'] != null &&
                (json['pelunasan'] as Map).isNotEmpty
            ? PembayaranModel.fromJson(
                json['pelunasan'] as Map<String, dynamic>)
            : null,
      );

  bool get isBooked  => status == 1;
  bool get isOngoing => status == 2;
  bool get isDone    => status == 3;
  bool get isCanceled=> status == 0;
}
