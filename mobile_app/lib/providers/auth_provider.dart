import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:google_sign_in/google_sign_in.dart';
import '../core/services/api_service.dart';
import '../core/services/storage_service.dart';
import '../models/user_model.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthProvider extends ChangeNotifier {
  AuthProvider() {
    // Token dicabut/kedaluwarsa (mis. login di HP lain) → logout otomatis.
    ApiService.onUnauthorized = _handleUnauthorized;
  }

  AuthStatus _status = AuthStatus.initial;
  UserModel? _user;
  String _error = '';
  bool _sessionExpired = false;

  AuthStatus get status => _status;
  UserModel?  get user   => _user;
  String      get error  => _error;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  /// True saat sesi baru saja dimatikan server; UI mengarahkan ke login lalu memanggil [ackSessionExpired].
  bool get sessionExpired => _sessionExpired;
  void ackSessionExpired() => _sessionExpired = false;

  void _handleUnauthorized() {
    // Saat init/login/logout status bukan authenticated: ditangani alurnya sendiri.
    if (_status != AuthStatus.authenticated) return;
    StorageService.clearAll();
    _user           = null;
    _status         = AuthStatus.unauthenticated;
    _sessionExpired = true;
    notifyListeners();
  }

  // ── Boot: restore session from local storage ───────────────
  Future<void> init() async {
    try {
      final token = await StorageService.getToken();
      if (token == null || token.isEmpty) {
        _status = AuthStatus.unauthenticated;
        notifyListeners();
        return;
      }

      _status = AuthStatus.loading;
      notifyListeners();

      final res = await ApiService.getMe();
      if (res.success && res.data != null) {
        await _setUser(UserModel.fromJson(res.data!['data'] as Map<String, dynamic>));
        _status = AuthStatus.authenticated;
      } else if (res.statusCode == 401) {
        // Hanya token yang benar-benar ditolak server yang menghapus sesi.
        await StorageService.clearAll();
        _status = AuthStatus.unauthenticated;
      } else {
        await _restoreOffline();
      }
    } catch (_) {
      await _restoreOffline();
    }
    notifyListeners();
  }

  /// Server tidak terjangkau / error: jangan mengeluarkan user. Pakai profil tersimpan.
  Future<void> _restoreOffline() async {
    try {
      final raw = await StorageService.getUserJson();
      if (raw != null) {
        _user   = UserModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
        _status = AuthStatus.authenticated;
        return;
      }
    } catch (_) {/* data lokal rusak → anggap belum login */}
    _status = AuthStatus.unauthenticated;
  }

  // ── Login ──────────────────────────────────────────────────
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    _setLoading();
    try {
      final res = await ApiService.login(email: email, password: password);

      if (res.success && res.data != null) {
        await _persistSession(res.data!);
        return true;
      }
      _error = res.displayMessage.isNotEmpty ? res.displayMessage : 'Terjadi kesalahan.';
    } catch (e) {
      _error = 'Login gagal: $e';
    }
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  // ── Logout ─────────────────────────────────────────────────
  Future<void> logout() async {
    await ApiService.logout();
    // Tanpa ini akun Google terakhir tetap terpilih otomatis di login berikutnya.
    try {
      await _googleSignIn.signOut();
    } catch (_) {/* tidak login via Google atau layanan tidak tersedia */}
    await StorageService.clearAll();
    _user   = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  // ── Google Sign-In ─────────────────────────────────────────
  // NOTE: Untuk mendapatkan idToken, kita wajib memasukkan Web Client ID
  // (bukan Android Client ID) ke parameter serverClientId.
  static final _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    serverClientId: '765088231031-tertibfm0gkpd9v6q1umar36vj5irbsh.apps.googleusercontent.com',
  );

  Future<bool> loginWithGoogle() async {
    _setLoading();
    try {
      final account = await _googleSignIn.signIn();
      if (account == null) {
        // User cancelled
        _status = AuthStatus.unauthenticated;
        notifyListeners();
        return false;
      }
      final auth    = await account.authentication;
      final idToken = auth.idToken;
      if (idToken == null) {
        _error  = 'Tidak bisa mendapatkan token Google.';
        _status = AuthStatus.unauthenticated;
        notifyListeners();
        return false;
      }
      final res = await ApiService.loginWithGoogle(idToken: idToken);
      if (res.success && res.data != null) {
        await _persistSession(res.data!);
        return true;
      }
      _error  = res.displayMessage.isNotEmpty ? res.displayMessage : 'Terjadi kesalahan.';
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return false;
    } catch (e) {
      _error  = 'Login Google gagal: ${e.toString()}';
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return false;
    }
  }

  // ── Update Profile ─────────────────────────────────────────
  Future<bool> updateProfile(Map<String, dynamic> data) async {
    try {
      final res = await ApiService.updateProfile(data);
      if (res.success && res.data != null) {
        await _setUser(UserModel.fromJson(res.data!['data'] as Map<String, dynamic>));
        notifyListeners();
        return true;
      }
      _error = res.displayMessage.isNotEmpty ? res.displayMessage : 'Terjadi kesalahan.';
    } catch (e) {
      _error = 'Gagal menyimpan profil: $e';
    }
    notifyListeners();
    return false;
  }

  // ── Refresh me ─────────────────────────────────────────────
  Future<void> refreshMe() async {
    try {
      final res = await ApiService.getMe();
      if (res.success && res.data != null) {
        await _setUser(UserModel.fromJson(res.data!['data'] as Map<String, dynamic>));
        notifyListeners();
      }
    } catch (_) {/* biarkan data lama */}
  }

  // ── Helpers ────────────────────────────────────────────────
  void _setLoading() {
    _status = AuthStatus.loading;
    _error  = '';
    notifyListeners();
  }

  /// Simpan user di memori + cache lokal (untuk pemulihan sesi saat offline).
  Future<void> _setUser(UserModel u) async {
    _user = u;
    await StorageService.saveUserJson(jsonEncode(u.toJson()));
  }

  Future<void> _persistSession(Map<String, dynamic> body) async {
    final data  = body['data'] as Map<String, dynamic>;
    final token = data['token'] as String;
    await _setUser(UserModel.fromJson(data['user'] as Map<String, dynamic>));

    await StorageService.saveToken(token);
    await StorageService.saveUser(
      id:    _user!.id,
      name:  _user!.name,
      email: _user!.email,
      role:  _user!.role,
    );

    _status = AuthStatus.authenticated;
    notifyListeners();
  }
}
