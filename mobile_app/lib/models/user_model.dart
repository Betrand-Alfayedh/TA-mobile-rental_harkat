class UserModel {
  final int id;
  final String name;
  final String email;
  final String? noHp;
  final String? alamat;
  final String? asalKota;
  final int role;
  final String? roleName;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.noHp,
    this.alamat,
    this.asalKota,
    required this.role,
    this.roleName,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
        id:        json['id'] as int,
        name:      json['name'] as String? ?? '',
        email:     json['email'] as String? ?? '',
        noHp:      json['no_hp'] as String?,
        alamat:    json['alamat'] as String?,
        asalKota:  json['asal_kota'] as String?,
        role:      json['role'] as int? ?? 4,
        roleName:  json['role_name'] as String?,
      );

  Map<String, dynamic> toJson() => {
        'id':        id,
        'name':      name,
        'email':     email,
        'no_hp':     noHp,
        'alamat':    alamat,
        'asal_kota': asalKota,
        'role':      role,
        'role_name': roleName,
      };

  UserModel copyWith({
    String? name,
    String? noHp,
    String? alamat,
    String? asalKota,
  }) =>
      UserModel(
        id:       id,
        name:     name ?? this.name,
        email:    email,
        noHp:     noHp ?? this.noHp,
        alamat:   alamat ?? this.alamat,
        asalKota: asalKota ?? this.asalKota,
        role:     role,
        roleName: roleName,
      );

  bool get isProfileComplete =>
      (alamat?.isNotEmpty ?? false) &&
      (noHp?.isNotEmpty ?? false) &&
      (asalKota?.isNotEmpty ?? false);
}
