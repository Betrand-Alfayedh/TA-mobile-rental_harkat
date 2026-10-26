import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/sopir_provider.dart';
import '../../core/widgets/app_widgets.dart';
import '../profile/profile_screen.dart';
import 'riwayat_sopir_screen.dart';
import 'sopir_home_screen.dart';
import 'tawaran_screen.dart';
import 'tugas_screen.dart';

/// Kerangka aplikasi sopir: navigasi bawah + penyegaran tawaran berkala selama aplikasi terlihat.
class SopirShell extends StatefulWidget {
  const SopirShell({super.key});

  @override
  State<SopirShell> createState() => _SopirShellState();
}

class _SopirShellState extends State<SopirShell> with WidgetsBindingObserver {
  int _index = 0;
  late final SopirProvider _prov;

  @override
  void initState() {
    super.initState();
    _prov = context.read<SopirProvider>();
    WidgetsBinding.instance.addObserver(this);
    // Muat semuanya sekali; setelah itu polling ringan hanya untuk tawaran dan dashboard.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _prov.refreshAll();
      _prov.startPolling();
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _prov.refreshAll();
      _prov.startPolling();
    } else if (state == AppLifecycleState.paused) {
      _prov.stopPolling();   // hemat baterai dan data saat di latar belakang
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _prov.stopPolling();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final jumlahTawaran = context.select<SopirProvider, int>((p) => p.tawaran.value.length);

    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          SopirHomeScreen(onLihatTawaran: () => setState(() => _index = 1)),
          const TawaranScreen(),
          const TugasScreen(),
          const RiwayatSopirScreen(),
          const ProfileScreen(),
        ],
      ),
      bottomNavigationBar: FloatingNavBar(
        index: _index,
        onTap: (i) => setState(() => _index = i),
        items: [
          const NavItem(Icons.home_outlined, Icons.home_rounded, 'Beranda'),
          NavItem(Icons.notifications_none_rounded, Icons.notifications_rounded, 'Tawaran', badge: jumlahTawaran),
          const NavItem(Icons.directions_car_outlined, Icons.directions_car_rounded, 'Tugas'),
          const NavItem(Icons.history_rounded, Icons.history_rounded, 'Riwayat'),
          const NavItem(Icons.person_outline_rounded, Icons.person_rounded, 'Profil'),
        ],
      ),
    );
  }
}
