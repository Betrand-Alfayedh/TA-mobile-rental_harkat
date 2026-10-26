import 'package:flutter/material.dart';
import '../models/user_model.dart';
import 'admin/admin_shell.dart';
import 'home/home_screen.dart';
import 'sopir/sopir_shell.dart';

/// Layar utama sesuai peran: admin (2), sopir (3), pelanggan (4, juga untuk peran tak dikenal).
/// Server tetap memeriksa peran di setiap endpoint; ini hanya menentukan tampilan.
Widget shellFor(UserModel? user) {
  if (user == null) return const HomeScreen();
  if (user.isAdmin) return const AdminShell();
  if (user.isSopir) return const SopirShell();
  return const HomeScreen();
}
