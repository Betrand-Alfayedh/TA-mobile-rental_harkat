<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $status
 * @property string $nama_mobil
 * @property string $plat_nomor
 * @property string $merk
 * @property int $tahun
 * @property int $harga_sewa
 * @property int $harga_all_in
 * @property string $gambar
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $status_badge_class
 * @property-read mixed $status_text
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereGambar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereHargaAllIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereHargaSewa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereMerk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereNamaMobil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil wherePlatNomor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mobil whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaksi> $transaksis
 * @property-read int|null $transaksis_count
 * @mixin \Eloquent
 */
	class Mobil extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama
 * @property string $email
 * @property string $no_hp
 * @property string|null $alamat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereNoTelp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pelanggan whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaksi> $transaksis
 * @property-read int|null $transaksis_count
 * @mixin \Eloquent
 */
	class Pelanggan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama
 * @property string $no_hp
 * @property string $alamat
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $gambar
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereGambar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereNoHp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaksi> $transaksis
 * @property-read int|null $transaksis_count
 * @mixin \Eloquent
 */
	class Supir extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pelanggan_id
 * @property int $mobil_id
 * @property int|null $supir_id
 * @property string $tanggal_sewa
 * @property string $tanggal_kembali
 * @property int|null $lama_sewa
 * @property int|null $total_harga
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mobil $mobil
 * @property-read \App\Models\Pelanggan $pelanggan
 * @property-read \App\Models\Supir|null $supir
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereLamaSewa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereMobilId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi wherePelangganId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereSupirId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereTanggalKembali($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereTanggalSewa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereTotalHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaksi whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class Transaksi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $role
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class User extends \Eloquent {}
}

