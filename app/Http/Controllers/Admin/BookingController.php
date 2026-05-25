<?php

namespace App\Http\Controllers\Admin;

use App\Events\JobAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksiRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;
use App\Models\User;
use App\Notifications\JobAssignedEmail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['details'])->latest()->get();
        return view('admin.transaksi.index', compact('bookings'));
    }

    public function create()
    {
        $pelanggans = User::where('role', 4)->get();
        $mobils = Mobil::where('status', 1)->where('status_approval', 1)->get();
        if ($mobils->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada mobil tersedia.');
        }
        $supirs = Supir::all();

        return view('admin.transaksi.create', compact('pelanggans', 'mobils', 'supirs'));
    }

    public function store(TransaksiRequest $request): RedirectResponse
    {
        // ✅ Validasi input
        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:users,id',
            'mobils.*.mobil_id'        => 'required|exists:mobils,id',
            'mobils.*.tanggal_sewa'    => 'required|date',
            'mobils.*.tanggal_kembali' => 'required|date|after_or_equal:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'     => 'required|in:0,1',
            'asal_kota'                => 'required|in:1,2',
            'nama_kota'                => 'required_if:asal_kota,2|nullable|string',
            'jaminan'                  => 'required|in:1,2',
        ], [
            'mobils.*.mobil_id.required'        => 'Mobil harus dipilih.',
            'mobils.*.tanggal_sewa.required'    => 'Tanggal sewa harus diisi.',
            'mobils.*.tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal sewa.',
            'asal_kota.required'                => 'Asal Kota harus diisi.',
            'nama_kota.required_if'             => 'Nama kota harus diisi jika asal kota = luar kota.',
            'jaminan.required'                  => 'Jaminan harus diisi.',
        ]);

        // ✅ Buat booking baru
        $booking = Booking::create([
            'user_id'        => $request->pelanggan_id,
            'tanggal_booking' => now(),
            'total_harga'    => 0,
            'uang_muka'    => 0,
            'status'         => 1, // booked
            'asal_kota'      => $request->asal_kota,
            'nama_kota'      => $request->nama_kota,
            'jaminan'        => $request->jaminan,
        ]);

        $totalBooking = 0;
        $tanggalSewaTerdekat = null;

        // ✅ Simpan detail booking
        foreach ($validated['mobils'] as $item) {
            $mobil          = Mobil::findOrFail($item['mobil_id']);
            $tanggalSewa    = Carbon::parse($item['tanggal_sewa']);
            $tanggalKembali = Carbon::parse($item['tanggal_kembali']);

            // hitung selisih jam -> dibagi 24 -> ceil (dibulatkan ke atas)
            $jam = $tanggalSewa->diffInHours($tanggalKembali);
            $lama = max(1, ceil($jam / 24));


            $harga = $item['pakai_supir']
                ? $mobil->harga_all_in * $lama
                : $mobil->harga_sewa * $lama;


            BookingDetail::create([
                'booking_id'      => $booking->id,
                'mobil_id'        => $mobil->id,
                'pakai_supir'     => $item['pakai_supir'],
                'supir_id'        => null,
                'tanggal_sewa'    => $tanggalSewa,
                'tanggal_kembali' => $tanggalKembali,
                'lama_sewa'       => $lama,
                'harga'           => $harga,
            ]);

            // update mobil jadi booked
            foreach ($validated['mobils'] as $item) {
                $mobil = Mobil::findOrFail($item['mobil_id']);
                $mobil->update(['status' => 2]);
                \Log::info("Mobil {$mobil->id} status updated to {$mobil->status}");
            }
            $totalBooking += $harga;

            // simpan tanggal sewa paling awal
            $tanggalSewaTerdekat = is_null($tanggalSewaTerdekat) || $tanggalSewa->lt($tanggalSewaTerdekat)
                ? $tanggalSewa
                : $tanggalSewaTerdekat;
        }

        // ✅ Update total booking & uang muka
        $booking->update([
            'total_harga' => $totalBooking,
            'uang_muka'   => $totalBooking / 2, // override biar fix 50%
        ]);

        // ✅ Hitung jatuh tempo DP
        $tanggalBooking = Carbon::parse($booking->tanggal_booking);

        // default jatuh tempo = 3 hari setelah booking
        $jatuhTempoDefault = $tanggalBooking->copy()->addDays(4);

        // jatuh tempo final = minimal dari (jatuh tempo default, tanggal sewa - 2 jam)
        $jatuhTempo = $jatuhTempoDefault->min($tanggalSewaTerdekat->copy()->subHours(2));

        // ✅ Generate pembayaran DP
        $pembayaranDp = Pembayaran::create([
            'booking_id'  => $booking->id,
            'jenis'       => 1, // DP
            'jumlah'      => $booking->uang_muka,
            'status'      => 0, // belum dibayar
            'jatuh_tempo' => $jatuhTempo,
        ]);
        return redirect()->route('admin.booking.index')->with('success', 'Transaksi berhasil dibuat.');
    }

    public function show(Booking $booking)
    {
        return view('admin.transaksi.show', compact('transaksi'));
    }

    public function konfirmasiJemput(BookingDetail $detail)
    {
        $mobil = $detail->mobil;
        $booking = $detail->booking;

        if ($mobil->status !== Mobil::STATUS_DIBOOKING) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobil tidak dalam status dibooking.'
                ], 400);
            }
            return back()->with('error', 'Mobil tidak dalam status dibooking.');
        }

        $mobil->status = Mobil::STATUS_DISEWA;
        $mobil->save();
        $booking->status = Booking::STATUS_ONGOING;
        $booking->save();

        $customerName = $booking->user->name ?? '-';
        $mobilName = $mobil->masterMobil->nama ?? '-';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Mobil $mobilName berhasil dijemput oleh $customerName!",
                'mobil_status_text' => $mobil->status_text,
                'mobil_status_badge_class' => $mobil->status_badge_class
            ]);
        }

        return back()->with('successSwal', "Mobil $mobilName berhasil dijemput oleh $customerName!");
    }


    public function edit(Booking $booking)
    {
        $pelanggans = User::where('role', 4);
        $mobils = Mobil::where('status', 1)->where('status_approval', 1)->get();
        if ($mobils->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada mobil tersedia.');
        }
        $supirs = Supir::all();

        return view('admin.transaksi.edit', compact('booking', 'pelanggans', 'mobils', 'supirs'));
    }

    public function update(TransaksiRequest $request, Booking $booking): RedirectResponse
    {
        $data = $this->prepareTransaksiData($request->validated());

        // Step 1: Cek apakah mobil atau supir sebelumnya dipakai
        $oldMobilId = $booking->mobil_id;
        $oldSupirId = $booking->supir_id;

        $booking->update($data);

        // Step 3: Kalau status transaksi jadi
        if ($data['status'] === 2) {
            // Mobil dan supir jadi tersedia lagi
            Mobil::where('id', $booking->mobil_id)->update(['status' => 3]);
            if ($booking->supir_id) {
                Supir::where('id', $booking->supir_id)->update(['status' => 0]);
            }
        } else {
            // Kalau mobil diganti, balikin mobil lama jadi tersedia
            if ($oldMobilId !== $data['mobil_id']) {
                Mobil::where('id', $oldMobilId)->update(['status' => 1]);
            }

            // Mobil baru jadi tidak tersedia
            Mobil::where('id', $data['mobil_id'])->update(['status' => 3]);

            // Supir juga dicek
            if ($oldSupirId !== $data['supir_id']) {
                if ($oldSupirId) {
                    Supir::where('id', $oldSupirId)->update(['status' => 1]);
                }
            }

            if (!empty($data['supir_id'])) {
                Supir::where('id', $data['supir_id'])->update(['status' => 0]);
            }
        }

        return redirect()->route('admin.booking.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function assignJobSupir(Request $request, BookingDetail $bookingDtl)
    {
        try {
            // Ambil semua supir available
            $availableSupirs = Supir::where('status', 1)->get();
            if ($availableSupirs->isEmpty()) {
                return back()->with('error', 'Tidak ada supir yang available.');
            }

            // Mulai transaksi DB
            DB::transaction(function () use ($availableSupirs, $bookingDtl) {
                foreach ($availableSupirs as $supir) {
                    $offer = JobOffer::firstOrCreate([
                        'booking_detail_id' => $bookingDtl->id,
                        'supir_id' => $supir->id,
                    ]);
                    Log::info("Job offer created/exists:", $offer->toArray());
                    // Cek kalau supir punya user_id
                    if ($supir->user_id) {
                        event(new JobAssigned($offer));
                    }
                    // Kirim email
                    if ($supir->user && $supir->user->email) {
                        $supir->user->notify(new JobAssignedEmail($bookingDtl));
                    }
                }
            });
            $bookingDtl->update(['pakai_supir' => 2]);
            return back()->with('success', 'Job dikirim ke semua supir available.');
        } catch (\Exception $e) {
            Log::error('AssignJob error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal assign job: ' . $e->getMessage());
        }
    }

    /**
     * Prepare data for create or update transaksi
     */
    private function prepareTransaksiData(array $data): array
    {
        $tanggalSewa = Carbon::parse($data['tanggal_sewa']);
        $tanggalKembali = Carbon::parse($data['tanggal_kembali']);
        $lamaSewa = $tanggalSewa->diffInDays($tanggalKembali) + 1;

        $mobil = Mobil::findOrFail($data['mobil_id']);
        $hargaPerHari = $mobil->getHargaPerHari(isset($data['supir_id']));
        $totalHarga = $hargaPerHari * $lamaSewa;

        return array_merge($data, [
            'lama_sewa' => $lamaSewa,
            'total_harga' => $totalHarga,
        ]);
    }
}
