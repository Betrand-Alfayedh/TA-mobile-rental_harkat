<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\Mobil;
use App\Models\Supir;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alur approval:
 *  - Pengajuan BARU (old_data = null): data sudah dibuat dengan status_approval=2 → approve=1, reject=0.
 *  - Pengajuan PERUBAHAN (old_data terisi): data live TIDAK diubah sampai disetujui. Approve menerapkan
 *    new_data; reject cukup membuang usulan (dan file baru yang tidak terpakai).
 */
class ApprovalController extends Controller
{
    private const MOBIL_FIELDS = ['master_mobil_id', 'plat_nomor', 'merk', 'tahun', 'harga_sewa', 'harga_all_in', 'gambar'];

    public function index(Request $request)
    {
        $status = $request->get('status', 2); // default pending
        $approvals = Approval::with(['approvable', 'requester', 'approver'])
            ->where('status', $status)
            ->latest()
            ->get();

        return view('superadmin.approvals.index', compact('approvals', 'status'));
    }

    public function approve($id)
    {
        $approval = Approval::findOrFail($id);
        if ($error = $this->cannotProcess($approval)) {
            return back()->with('error', $error);
        }

        DB::transaction(function () use ($approval) {
            $model = $approval->approvable;

            if ($approval->old_data !== null) {
                $this->applyChanges($model, $approval);
            }

            $model->update(['status_approval' => 1]);
            $approval->update(['status' => 1, 'approved_by' => Auth::id()]);
        });

        return back()->with('success', 'Data berhasil di-approve!');
    }

    public function reject(Request $request, $id)
    {
        $approval = Approval::findOrFail($id);
        if ($error = $this->cannotProcess($approval)) {
            return back()->with('error', $error);
        }

        DB::transaction(function () use ($approval, $request) {
            $model = $approval->approvable;

            if ($approval->old_data === null) {
                $model->update(['status_approval' => 0]); // pengajuan baru ditolak
            } else {
                $this->discardNewFiles($model, $approval); // usulan perubahan dibuang, data live tetap
            }

            $approval->update([
                'status'      => 0,
                'approved_by' => Auth::id(),
                'note'        => $request->note,
            ]);
        });

        return back()->with('success', 'Pengajuan ditolak. Data tetap seperti sebelumnya.');
    }

    /** Pesan error bila pengajuan tidak bisa diproses, atau null. */
    private function cannotProcess(Approval $approval): ?string
    {
        if ((int) $approval->status !== 2) {
            return 'Pengajuan ini sudah diproses sebelumnya.';
        }
        if (! $approval->approvable) {
            return 'Data terkait sudah dihapus, pengajuan tidak dapat diproses.';
        }
        return null;
    }

    private function applyChanges(Model $model, Approval $approval): void
    {
        $new = $approval->new_data ?? [];

        if ($model instanceof Mobil) {
            $fields    = Arr::only($new, self::MOBIL_FIELDS);
            $oldGambar = $model->gambar;
            $model->update($fields);

            if (isset($fields['gambar']) && $oldGambar && $oldGambar !== $fields['gambar']) {
                @unlink(public_path('gambar_mobil/' . basename($oldGambar)));
            }
        } elseif ($model instanceof Supir) {
            $user = $model->user;
            $user->fill(Arr::only($new, ['name', 'email', 'no_hp', 'alamat']));
            if (! empty($new['password'])) {
                $user->password = $new['password']; // sudah berupa hash dari form admin
            }
            $user->save();

            $oldGambar = $model->gambar;
            $model->update(Arr::only($new, ['status', 'gambar']));
            if (isset($new['gambar']) && $oldGambar && $oldGambar !== $new['gambar']) {
                Storage::disk('public')->delete($oldGambar);
            }
        }
    }

    /** Hapus file hasil upload usulan yang ditolak (jika berbeda dari file yang sedang dipakai). */
    private function discardNewFiles(Model $model, Approval $approval): void
    {
        $newGambar = $approval->new_data['gambar'] ?? null;
        if (! $newGambar || $newGambar === $model->gambar) {
            return;
        }

        if ($model instanceof Mobil) {
            @unlink(public_path('gambar_mobil/' . basename($newGambar)));
        } elseif ($model instanceof Supir) {
            Storage::disk('public')->delete($newGambar);
        }
    }
}
