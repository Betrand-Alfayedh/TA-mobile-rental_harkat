import 'package:shared_preferences/shared_preferences.dart';

// ─────────────────────────────────────────────────────────────
//  StorageService — persists auth token & user data locally
// ─────────────────────────────────────────────────────────────

class StorageService {
  static const _keyToken    = 'auth_token';
  static const _keyUserId   = 'user_id';
  static const _keyUserName = 'user_name';
  static const _keyUserEmail= 'user_email';
  static const _keyUserRole = 'user_role';

  // ── Token ───────────────────────────────────────────────────
  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyToken, token);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyToken);
  }

  static Future<void> deleteToken() async {
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

  // ── Clear All ───────────────────────────────────────────────
  static Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}
