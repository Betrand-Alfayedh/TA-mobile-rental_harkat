import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/widgets/app_widgets.dart';
import '../../providers/admin_provider.dart';
import 'admin_home_screen.dart';
import 'armada_list_screen.dart';
import 'lainnya_screen.dart';
import 'pembayaran_list_screen.dart';
import 'pesanan_list_screen.dart';

/// Kerangka aplikasi admin: navigasi bawah, pemuatan awal, dan penyegaran berkala selama aplikasi terlihat.
class AdminShell extends StatefulWidget {
  const AdminShell({super.key});

  @override
  State<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends State<AdminShell> with WidgetsBindingObserver {
  int _index = 0;
  late final AdminProvider _prov;

  @override
  void initState() {
    super.initState();
    _prov = context.read<AdminProvider>();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _muatAwal();
      _prov.startPolling();
    });
  }

  void _muatAwal() {
    _prov.muatDashboard();
    _prov.muatPesanan();
    _prov.muatPembayaran();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _prov.segarkanRingan();
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
    final menunggu = context.select<AdminProvider, int>((p) => p.dashboard.value?.menungguVerifikasi ?? 0);

    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          AdminHomeScreen(pindahTab: (i) => setState(() => _index = i)),
          const PesananListScreen(),
          const PembayaranListScreen(),
          const ArmadaListScreen(),
          const LainnyaScreen(),
        ],
      ),
      bottomNavigationBar: FloatingNavBar(
        index: _index,
        onTap: (i) => setState(() => _index = i),
        items: [
          const NavItem(Icons.dashboard_outlined, Icons.dashboard_rounded, 'Beranda'),
          const NavItem(Icons.receipt_long_outlined, Icons.receipt_long_rounded, 'Pesanan'),
          NavItem(Icons.payments_outlined, Icons.payments_rounded, 'Bayar', badge: menunggu),
          const NavItem(Icons.directions_car_outlined, Icons.directions_car_rounded, 'Armada'),
          const NavItem(Icons.menu_rounded, Icons.menu_open_rounded, 'Lainnya'),
        ],
      ),
    );
  }
}
