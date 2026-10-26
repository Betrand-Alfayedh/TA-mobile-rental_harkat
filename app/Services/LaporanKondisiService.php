<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\LaporanKondisi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LaporanKondisiService
{
    /**
     * Simpan laporan kondisi satu unit. Satu laporan per jenis; kirim ulang memperbarui yang lama.
     * Admin boleh melaporkan unit mana pun; sopir hanya unit yang ditugaskan kepadanya.
     *
     * @param array{jenis:int|string, kondisi:int|string, catatan?:?string} $data
     */
    public function simpan(BookingDetail $detail, User $oleh, array $data, ?UploadedFile $foto = null): LaporanKondisi
    {
        $jenis = (int) $data['jenis'];
        $status = (int) $detail->status;

        if ((int) $oleh->role === 3 && (int) $detail->supir_id !== (int) $oleh->supir?->id) {
            throw new \DomainException('Unit ini bukan tugas Anda.');
        }
        if ($status === Booking::STATUS_CANCELED) {
            throw new \DomainException('Pesanan sudah dibatalkan.');
        }
        if ($jenis === LaporanKondisi::SEBELUM && ! in_array($status, [Booking::STATUS_BOOKED, Booking::STATUS_ONGOING], true)) {
            throw new \DomainException('Laporan sebelum sewa hanya dapat dibuat sebelum unit dikembalikan.');
        }
        if ($jenis === LaporanKondisi::SESUDAH && $status === Booking::STATUS_BOOKED) {
            throw new \DomainException('Laporan sesudah sewa baru dapat dibuat setelah unit dijemput.');
        }

        $laporan = LaporanKondisi::firstOrNew(['booking_detail_id' => $detail->id, 'jenis' => $jenis]);
        $fotoLama = $laporan->foto;

        $laporan->fill([
            'kondisi'     => (int) $data['kondisi'],
            'catatan'     => $data['catatan'] ?? null,
            'dibuat_oleh' => $oleh->id,
        ]);

        if ($foto) {
            $laporan->foto = $foto->store('laporan_kondisi', 'public'); // nama acak
        }

        $laporan->save();

        if ($foto && $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $laporan;
    }
}
