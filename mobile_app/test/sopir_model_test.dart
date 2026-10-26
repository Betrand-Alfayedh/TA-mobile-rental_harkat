import 'package:flutter_test/flutter_test.dart';
import 'package:harkat_rental/models/sopir_model.dart';
import 'package:harkat_rental/models/user_model.dart';
import 'package:harkat_rental/screens/admin/admin_shell.dart';
import 'package:harkat_rental/screens/home/home_screen.dart';
import 'package:harkat_rental/screens/role_shell.dart';
import 'package:harkat_rental/screens/sopir/sopir_shell.dart';

const _unit = {
  'id': 7,
  'status': 2,
  'status_label': 'On Going',
  'mobil': {'id': 3, 'nama': 'Avanza', 'merk': 'Toyota', 'plat_nomor': 'AB 1234 CD', 'gambar': null},
  'tanggal_sewa': '2026-10-08 09:00:00',
  'tanggal_kembali': '2026-10-10 09:00:00',
  'lama_sewa': 2,
  'penyewa': {'nama': 'Budi', 'no_hp': '0812'},
  'asal_kota': 'Yogyakarta',
  'laporan_sebelum': {'id': 1, 'jenis': 1, 'kondisi': 1, 'kondisi_label': 'Baik', 'catatan': 'Mulus', 'foto': null},
  'laporan_sesudah': null,
};

UserModel user(int role) => UserModel(id: 1, name: 'X', email: 'x@x.com', role: role);

void main() {
  group('parser toleran', () {
    test('angka sebagai string atau double tidak membuat crash', () {
      final u = SopirUnitModel.fromJson({..._unit, 'id': '7', 'status': 2.0, 'lama_sewa': '2'});
      expect(u.id, 7);
      expect(u.status, 2);
      expect(u.lamaSewa, 2);
    });

    test('unit lengkap terbaca, termasuk laporan sebelum dan sesudah', () {
      final u = SopirUnitModel.fromJson(_unit);
      expect(u.namaLengkap, 'Toyota Avanza');
      expect(u.penyewaNama, 'Budi');
      expect(u.laporanSebelum?.kondisiLabel, 'Baik');
      expect(u.laporanSesudah, isNull);
    });

    test('field hilang atau null memakai nilai bawaan, tidak melempar', () {
      final u = SopirUnitModel.fromJson({'id': 1});
      expect(u.status, 1);
      expect(u.namaLengkap, '');
      expect(u.penyewaNama, isNull);

      final t = TawaranModel.fromJson({'id': 5});
      expect(t.unit.id, 0);

      final d = SopirDashboardModel.fromJson({});
      expect(d.status, 0);
      expect(d.tugasAktif, 0);
    });

    test('dashboard: bertugas dan tersedia', () {
      final d = SopirDashboardModel.fromJson({
        'sopir': {'nama': 'Joko', 'status': 2, 'status_text': 'Bertugas'},
        'tugas_aktif': 1, 'tawaran_baru': '3', 'tugas_selesai': 4,
      });
      expect(d.bertugas, isTrue);
      expect(d.tersedia, isFalse);
      expect(d.tawaranBaru, 3);
    });
  });

  group('aturan laporan kondisi di klien', () {
    SopirUnitModel dgnStatus(int s) => SopirUnitModel.fromJson({..._unit, 'status': s});

    test('sebelum: saat booked atau ongoing; sesudah: saat ongoing atau selesai', () {
      expect((dgnStatus(1).bisaLaporSebelum, dgnStatus(1).bisaLaporSesudah), (true, false));
      expect((dgnStatus(2).bisaLaporSebelum, dgnStatus(2).bisaLaporSesudah), (true, true));
      expect((dgnStatus(3).bisaLaporSebelum, dgnStatus(3).bisaLaporSesudah), (false, true));
      expect((dgnStatus(0).bisaLaporSebelum, dgnStatus(0).bisaLaporSesudah), (false, false));
    });
  });

  group('pemilihan layar per peran', () {
    test('admin, sopir, dan pelanggan mendapat layar masing-masing', () {
      expect(shellFor(user(2)), isA<AdminShell>());
      expect(shellFor(user(3)), isA<SopirShell>());
      expect(shellFor(user(4)), isA<HomeScreen>());
    });

    test('peran tak dikenal atau belum ada pengguna jatuh ke layar pelanggan', () {
      expect(shellFor(user(1)), isA<HomeScreen>());
      expect(shellFor(null), isA<HomeScreen>());
    });

    test('getter peran pada UserModel', () {
      expect(user(2).isAdmin, isTrue);
      expect(user(3).isSopir, isTrue);
      expect(user(4).isCustomer, isTrue);
      expect(user(3).isAdmin, isFalse);
    });
  });
}
