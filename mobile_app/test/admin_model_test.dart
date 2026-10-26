import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/models/admin_model.dart';
import 'package:harkat_rental/models/master_model.dart';
import 'package:harkat_rental/models/mobil_model.dart';

import 'support.dart';

void main() {
  group('BookingAdminModel', () {
    test('pesanan lengkap terbaca: penyewa, pembayaran, unit', () {
      final b = BookingAdminModel.fromJson(bookingJson(5, units: [
        unitJson(11, bersopir: true, sebelum: {'id': 1, 'jenis': 1, 'kondisi': 1, 'kondisi_label': 'Baik'}),
      ]));

      expect(b.id, 5);
      expect(b.penyewaNama, 'Budi Santoso');
      expect(b.penyewaNoHp, '0812');
      expect(b.pembayarans.single.jenisText, 'DP');
      expect(b.units.single.namaLengkap, 'Toyota Avanza');
      expect(b.units.single.laporanSebelum?.kondisiLabel, 'Baik');
      expect(b.totalHarga, 800000);
    });

    test('angka sebagai string/double dan field hilang tidak membuat crash', () {
      final b = BookingAdminModel.fromJson({'id': '7', 'status': 2.0, 'total_harga': '800000', 'uang_muka': 400000.0});
      expect(b.id, 7);
      expect(b.status, 2);
      expect(b.totalHarga, 800000);
      expect(b.penyewaNama, '');
      expect(b.units, isEmpty);

      // daftar berisi elemen yang bukan objek diabaikan
      final c = BookingAdminModel.fromJson({'id': 1, 'details': [1, 'x', null, unitJson(3)], 'pembayarans': 'rusak'});
      expect(c.units, hasLength(1));
      expect(c.pembayarans, isEmpty);
    });

    test('DP lunas dan sisa tagihan', () {
      final belum = BookingAdminModel.fromJson(bookingJson(1, dpStatus: 0));
      expect(belum.dpLunas, isFalse);
      expect(belum.sisaTagihan, 400000);

      final lunas = BookingAdminModel.fromJson(bookingJson(1, dpStatus: 1, pembayarans: [
        pembayaranJson(1, status: 1),
        pembayaranJson(2, jenis: 2, status: 0, jumlah: 450000),
      ]));
      expect(lunas.dpLunas, isTrue);
      expect(lunas.sisaTagihan, 450000);
    });

    test('pesanan tanpa pembayaran: DP dianggap belum lunas, tidak melempar', () {
      final b = BookingAdminModel.fromJson(bookingJson(1, pembayarans: []));
      expect(b.dp, isNull);
      expect(b.dpLunas, isFalse);
    });

    test('hanya Booked yang dapat dibatalkan', () {
      expect(BookingAdminModel.fromJson(bookingJson(1, status: 1)).bisaDibatalkan, isTrue);
      for (final s in [0, 2, 3]) {
        expect(BookingAdminModel.fromJson(bookingJson(1, status: s)).bisaDibatalkan, isFalse, reason: 'status $s');
      }
    });

    test('hitung pembayaran menunggu verifikasi dan unit perlu penugasan', () {
      final b = BookingAdminModel.fromJson(bookingJson(1, pembayarans: [pembayaranJson(1, status: 2)], units: [
        unitJson(1, bersopir: true),                         // perlu ditugaskan
        unitJson(2, bersopir: true, menunggu: true),         // sudah ditawarkan
        unitJson(3, bersopir: true, supirId: 4),             // sudah bersopir
        unitJson(4),                                         // lepas kunci
        unitJson(5, bersopir: true, status: 2),              // sudah berjalan
      ]));

      expect(b.jumlahMenungguVerifikasi, 1);
      expect(b.perluPenugasan, 1);
    });
  });

  group('UnitAdminModel', () {
    test('pakai_supir bernilai 2 (data lama: sudah ditawarkan) dibaca sebagai bersopir', () {
      final u = UnitAdminModel.fromJson({...unitJson(1), 'pakai_supir': 2});
      expect(u.pakaiSupir, isTrue);
    });

    test('aturan laporan kondisi sama dengan server', () {
      bool sebelum(int s) => UnitAdminModel.fromJson(unitJson(1, status: s)).bisaLaporSebelum;
      bool sesudah(int s) => UnitAdminModel.fromJson(unitJson(1, status: s)).bisaLaporSesudah;
      expect([sebelum(1), sebelum(2), sebelum(3), sebelum(0)], [true, true, false, false]);
      expect([sesudah(1), sesudah(2), sesudah(3), sesudah(0)], [false, true, true, false]);
    });

    test('pengembalian dengan denda terbaca', () {
      final u = UnitAdminModel.fromJson(unitJson(1, status: 3, pengembalian: {
        'tanggal_kembali_aktual': '2026-10-10 11:00:00', 'kondisi': 1, 'kondisi_label': 'Baik',
        'denda_flag': true, 'nominal_denda': 100000, 'catatan': null, 'gambar': null,
      }));
      expect(u.pengembalian?.dendaFlag, isTrue);
      expect(u.pengembalian?.nominalDenda, 100000);
    });
  });

  group('PembayaranAdminModel', () {
    test('status dan jenis', () {
      final p = PembayaranAdminModel.fromJson(pembayaranJson(1, status: 2));
      expect(p.menungguVerifikasi, isTrue);
      expect(p.belumBayar, isFalse);
      expect(p.adalahDp, isTrue);
      expect(PembayaranAdminModel.fromJson(pembayaranJson(1, status: 1)).lunas, isTrue);
    });

    test('keterangan (mis. denda) masuk ke judul', () {
      final p = PembayaranAdminModel.fromJson({...pembayaranJson(3, jenis: 2), 'keterangan': 'Denda keterlambatan'});
      expect(p.judul, 'Pelunasan - Denda keterlambatan');
    });
  });

  group('DendaKeterlambatan (pratinjau, aturan sama dengan server)', () {
    final rencana = DateTime(2026, 10, 10, 9, 0);

    test('tepat waktu atau lebih awal: tanpa denda', () {
      expect(DendaKeterlambatan.jam(rencana, rencana), 0);
      expect(DendaKeterlambatan.jam(rencana, rencana.subtract(const Duration(hours: 5))), 0);
    });

    test('telat 1 menit sudah dihitung 1 jam penuh', () {
      expect(DendaKeterlambatan.jam(rencana, rencana.add(const Duration(minutes: 1))), 1);
    });

    test('tepat 60 menit = 1 jam, 61 menit = 2 jam', () {
      expect(DendaKeterlambatan.jam(rencana, rencana.add(const Duration(minutes: 60))), 1);
      expect(DendaKeterlambatan.jam(rencana, rencana.add(const Duration(minutes: 61))), 2);
    });

    test('telat 90 menit = 2 jam = Rp100.000 (sama dengan tes server)', () {
      expect(DendaKeterlambatan.jam(rencana, rencana.add(const Duration(minutes: 90))), 2);
      expect(DendaKeterlambatan.nominal(rencana, rencana.add(const Duration(minutes: 90))), 100000);
    });
  });

  group('DashboardAdminModel & halaman', () {
    test('dashboard terbaca dan total perlu tindakan dijumlahkan', () {
      final d = DashboardAdminModel.fromJson(dashboardJson(menunggu: 2, perluSopir: 1));
      expect(d.menungguVerifikasi, 2);
      expect(d.armadaTersedia, 6);
      expect(d.totalPerluTindakan, 3);   // 2 verifikasi + 1 penugasan + 0 terlambat
    });

    test('dashboard kosong tidak melempar', () {
      final d = DashboardAdminModel.fromJson({});
      expect(d.totalPerluTindakan, 0);
      expect(d.armadaTotal, 0);
    });

    test('meta paginasi dan hasil halaman', () {
      final h = HasilHalaman<int>.fromJson({'data': [{'id': 1}, {'id': 2}], 'meta': meta(page: 2, last: 3, total: 40)}, (j) => j['id'] as int);
      expect(h.items, [1, 2]);
      expect(h.meta.page, 2);
      expect(h.meta.lastPage, 3);
      expect(h.meta.total, 40);
      // meta hilang: bawaan satu halaman
      expect(HasilHalaman<int>.fromJson({'data': []}, (j) => 0).meta.lastPage, 1);
    });
  });

  group('model master', () {
    test('sopir, penyewa, referensi', () {
      final s = SupirAdminModel.fromJson({'id': '3', 'nama': 'Joko', 'aktif': false, 'status': 2, 'status_text': 'Bertugas'});
      expect(s.id, 3);
      expect(s.aktif, isFalse);
      expect(s.bertugas, isTrue);
      // aktif hilang → dianggap aktif
      expect(SupirAdminModel.fromJson({'id': 1}).aktif, isTrue);

      final p = PenyewaModel.fromJson({'id': 1, 'name': 'Budi', 'email': 'b@x.com', 'jumlah_pesanan': '2'});
      expect(p.jumlahPesanan, 2);
      expect(PenyewaModel.fromJson({'id': 1}).jumlahPesanan, isNull);

      final r = ReferensiArmadaModel.fromJson({
        'tipe': [{'id': 1, 'nama_tipe': 'MPV'}],
        'model': [{'id': 5, 'nama': 'Avanza', 'tipe_id': 1}],
      });
      expect(r.tipe.single.nama, 'MPV');
      expect(r.model.single.tipeId, 1);
    });

    test('rekap: utilisasi sebagai angka, string, atau hilang', () {
      RekapModel baca(Object? util) => RekapModel.fromJson({'bulan': '2026-10', 'armada': {'utilisasi_persen': util, 'per_mobil': []}});
      expect(baca(7.5).utilisasiPersen, 7.5);
      expect(baca('12.5').utilisasiPersen, 12.5);
      expect(baca(null).utilisasiPersen, 0);

      final r = RekapModel.fromJson({
        'bulan': '2026-10',
        'pesanan': {'total': 2, 'selesai': 1, 'dibatalkan': 1},
        'pendapatan': {'dp': 400000, 'pelunasan': 500000, 'total': 900000, 'denda_tercatat': 100000},
        'armada': {'total_unit_disewa': 1, 'total_hari_sewa': 2, 'utilisasi_persen': 3.3,
          'per_mobil': [{'mobil_id': 4, 'nama': 'Avanza', 'plat_nomor': 'AB 1 CD', 'jumlah_sewa': 1, 'total_hari': 2}]},
      });
      expect(r.pendapatanTotal, 900000);
      expect(r.perMobil.single.totalHari, 2);
    });

    test('MobilModel kini tahan angka berupa string (harga desimal dari server)', () {
      final m = MobilModel.fromJson({...mobilJson(1), 'harga_sewa': '500000.00', 'tahun': '2023', 'id': '1'});
      expect(m.hargaSewa, 500000);
      expect(m.tahun, 2023);
      expect(m.id, 1);
    });
  });
}
