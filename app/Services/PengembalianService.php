<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengembalianService
{
    /**
     * Catat pengembalian satu unit: hitung denda (per jam yang sudah dimulai), tagihkan,
     * selesaikan unit (booking Done bila semua unit kembali), atur status mobil, bebaskan sopir.
     *
     * @param array{tanggal_kembali_aktual:string, kondisi:int|string, catatan?:?string} $data  kondisi: 1 baik, 0 rusak
     */
    public function catat(int $detailId, array $data, ?UploadedFile $gambar = null, ?User $oleh = null): Pengembalian
    {
        $path = null;

        try {
            return DB::transaction(function () use ($detailId, $data, $gambar, $oleh, &$path) {
                $detail = BookingDetail::with(['mobil', 'booking'])->lockForUpdate()->findOrFail($detailId);

                if ($detail->pengembalian()->exists()) {
                    throw new \DomainException('Unit mobil ini sudah dikembalikan sebelumnya.');
                }
                // Hanya unit yang sudah dijemput (ongoing) yang bisa dikembalikan.
                if ((int) $detail->status !== Booking::STATUS_ONGOING) {
                    throw new \DomainException('Unit ini belum dijemput (atau booking sudah dibatalkan), pengembalian tidak dapat dicatat.');
                }

                $rencana = Carbon::parse($detail->tanggal_kembali);
                $aktual  = Carbon::parse($data['tanggal_kembali_aktual']);
                if ($aktual->lt(Carbon::parse($detail->tanggal_sewa))) {
                    throw new \DomainException('Tanggal kembali aktual tidak boleh sebelum tanggal sewa.');
                }

                $pengembalian = new Pengembalian([
                    'booking_detail_id'      => $detail->id,
                    'tanggal_kembali_aktual' => $aktual,
                    'kondisi'                => $data['kondisi'],
                    'catatan'                => $data['catatan'] ?? null,
                    'denda_flag'             => 0,
                    'nominal_denda'          => 0,
                    'dicatat_oleh'           => $oleh?->id,
                ]);

                if ($gambar) {
                    $path = $gambar->store('pengembalian', 'public'); // nama acak
                    $pengembalian->gambar = $path;
                }

                // Denda per jam keterlambatan (jam yang sudah dimulai dihitung penuh)
                if ($aktual->gt($rencana)) {
                    $jam = (int) ceil($rencana->diffInMinutes($aktual) / 60);
                    $pengembalian->denda_flag    = 1;
                    $pengembalian->nominal_denda = $jam * Pengembalian::DENDA_PER_JAM;
                }

                $pengembalian->save();

                if ($pengembalian->denda_flag) {
                    $this->tagihkanDenda($detail->booking, $pengembalian->nominal_denda);
                }

                $detail->update(['status' => Booking::STATUS_DONE]); // observer menyesuaikan status booking

                // Rusak → tidak boleh disewa; baik → maintenance (dicek/dibersihkan) sebelum tersedia lagi
                $detail->mobil?->update([
                    'status' => (int) $data['kondisi'] === 1 ? Mobil::STATUS_MAINTENANCE : Mobil::STATUS_RUSAK,
                ]);

                $detail->bebaskanSupir();

                return $pengembalian;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path); // jangan tinggalkan file yatim
            }
            throw $e;
        }
    }

    /** Tambahkan denda ke pelunasan yang belum dibayar; jika sudah lunas/belum ada, buat tagihan denda terpisah. */
    private function tagihkanDenda(Booking $booking, int $nominal): void
    {
        $pelunasan = Pembayaran::where('booking_id', $booking->id)->where('jenis', 2)
            ->where('status_pembayaran', '!=', 1)->first();

        if ($pelunasan) {
            $pelunasan->increment('jumlah', $nominal);
            return;
        }

        Pembayaran::create([
            'booking_id'        => $booking->id,
            'jenis'             => 2, // pelunasan
            'jumlah'            => $nominal,
            'status_pembayaran' => 0,
            'keterangan'        => 'Denda keterlambatan',
            'jatuh_tempo'       => now()->addDays(3),
        ]);
    }
}
