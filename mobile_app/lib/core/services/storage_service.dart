import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

// ─────────────────────────────────────────────────────────────
//  StorageService — token di penyimpanan aman (Android Keystore),
//  data pengguna non-sensitif di SharedPreferences.
// ─────────────────────────────────────────────────────────────

class StorageService {
  static const _keyToken    = 'auth_token';
  static const _keyUserId   = 'user_id';
  static const _keyUserName = 'user_name';
  static const _keyUserEmail= 'user_email';
  static const _keyUserRole = 'user_role';
  static const _keyUserJson = 'user_json';

  static const _secure = FlutterSecureStorage();

  // ── Token ───────────────────────────────────────────────────
  static Future<void> saveToken(String token) async {
    await _secure.write(key: _keyToken, value: token);
    // Pastikan tidak ada salinan lama (versi sebelum penyimpanan aman) yang tertinggal.
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyToken);
  }

  static Future<String?> getToken() async {
    try {
      final secure = await _secure.read(key: _keyToken);
      if (secure != null && secure.isNotEmpty) return secure;

      // Migrasi satu kali: pindahkan token lama dari SharedPreferences ke penyimpanan aman.
      final prefs  = await SharedPreferences.getInstance();
      final legacy = prefs.getString(_keyToken);
      if (legacy != null && legacy.isNotEmpty) {
        await _secure.write(key: _keyToken, value: legacy);
        await prefs.remove(_keyToken);
        return legacy;
      }
      return null;
    } catch (_) {
      // Keystore rusak (mis. setelah restore perangkat): anggap belum login, jangan crash.
      return null;
    }
  }

  static Future<void> deleteToken() async {
    try {
      await _secure.delete(key: _keyToken);
    } catch (_) {/* tidak ada yang perlu dihapus */}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyToken);
  }

  // ── User Info ───────────────────────────────────────────────
  static Future<void> saveUser({
    required int id,
    required String name,
    required String email,
    required int role,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_keyUserId, id);
    await prefs.setString(_keyUserName, name);
    await prefs.setString(_keyUserEmail, email);
    await prefs.setInt(_keyUserRole, role);
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt(_keyUserId);
    if (id == null) return null;
    return {
      'id':    id,
      'name':  prefs.getString(_keyUserName) ?? '',
      'email': prefs.getString(_keyUserEmail) ?? '',
      'role':  prefs.getInt(_keyUserRole) ?? 4,
    };
  }

  /// Profil lengkap (JSON) untuk memulihkan sesi saat server tidak terjangkau.
  static Future<void> saveUserJson(String json) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyUserJson, json);
  }

  static Future<String?> getUserJson() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyUserJson);
  }

  // ── Clear All ───────────────────────────────────────────────
  static Future<void> clearAll() async {
    await deleteToken();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}
