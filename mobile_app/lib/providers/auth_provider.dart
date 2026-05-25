import 'package:flutter/foundation.dart';
import 'package:google_sign_in/google_sign_in.dart';
import '../core/services/api_service.dart';
import '../core/services/storage_service.dart';
import '../models/user_model.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthProvider extends ChangeNotifier {
  AuthStatus _status = AuthStatus.initial;
  UserModel? _user;
  String _error = '';

  AuthStatus get status => _status;
  UserModel?  get user   => _user;
  String      get error  => _error;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  // ── Boot: restore session from local storage ───────────────
  Future<void> init() async {
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
      _user   = UserModel.fromJson(res.data!['data'] as Map<String, dynamic>);
      _status = AuthStatus.authenticated;
    } else {
      // Token expired or invalid — clear it
      await StorageService.clearAll();
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  // ── Login ──────────────────────────────────────────────────
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    _setLoading();

    final res = await ApiService.login(email: email, password: password);

    if (res.success && res.data != null) {
      await _persistSession(res.data!);
      return true;
    }

    _error  = _extractError(res);
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  // ── Logout ─────────────────────────────────────────────────
  Future<void> logout() async {
    await ApiService.logout();
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
      _error  = _extractError(res);
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
    final res = await ApiService.updateProfile(data);
    if (res.success && res.data != null) {
      _user = UserModel.fromJson(res.data!['data'] as Map<String, dynamic>);
      notifyListeners();
      return true;
    }
    _error = _extractError(res);
    notifyListeners();
    return false;
  }

  // ── Refresh me ─────────────────────────────────────────────
  Future<void> refreshMe() async {
    final res = await ApiService.getMe();
    if (res.success && res.data != null) {
      _user = UserModel.fromJson(res.data!['data'] as Map<String, dynamic>);
      notifyListeners();
    }
  }

  // ── Helpers ────────────────────────────────────────────────
  void _setLoading() {
    _status = AuthStatus.loading;
    _error  = '';
    notifyListeners();
  }

  Future<void> _persistSession(Map<String, dynamic> body) async {
    final data  = body['data'] as Map<String, dynamic>;
    final token = data['token'] as String;
    _user       = UserModel.fromJson(data['user'] as Map<String, dynamic>);

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

  String _extractError(ApiResponse<Map<String, dynamic>> res) {
    if (res.errors != null) {
      return res.errors!.values.first is List
          ? (res.errors!.values.first as List).first.toString()
          : res.errors!.values.first.toString();
    }
    return res.message.isNotEmpty ? res.message : 'Terjadi kesalahan.';
  }
}
