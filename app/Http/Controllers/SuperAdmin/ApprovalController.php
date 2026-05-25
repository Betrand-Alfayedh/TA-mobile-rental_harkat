<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
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
        $user = Auth::user();
        $approval = Approval::with('approvable')->findOrFail($id);

        $approval->update([
            'status' => 1,
            'approved_by' => $user->id,
        ]);

        // 🔥 update status_approval di model terkait (Supir/Mobil)
        if ($approval->approvable) {
            $approval->approvable->update(['status_approval' => 1]);
        }
        $approval->approvable->update([
            'status_approval' => 1, // approved
        ]);

        return back()->with('success', 'Data berhasil di-approve!');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $approval = Approval::with('approvable')->findOrFail($id);

        // update status approval di tabel approvals
        $approval->update([
            'status' => 0,
            'approved_by' => $user->id,
            'note' => $request->note,
        ]);

        $model = $approval->approvable;

        if (!empty($approval->old_data) && is_array($approval->old_data)) {
            // rollback semua field lama
            foreach ($approval->old_data as $key => $value) {
                // pastikan field ada di model (fillable)
                if (in_array($key, $model->getFillable())) {
                    $model->$key = $value;
                }
            }
            $model->status_approval = 1; // rollback sukses → status normal
            $model->save();
        } else {
            // data baru yang di-reject
            $model->status_approval = 0;
            $model->save();
        }

        return back()->with('success', 'Data berhasil di-reject dan dikembalikan ke versi sebelumnya!');
    }

    // ApprovalController.php
    public function show($id)
    {
        $approval = Approval::with('approvable', 'requester', 'approver')->findOrFail($id);
        // bikin diff buat view (hanya field yang berubah)
        $old = $approval->old_data ?? [];
        $new = $approval->new_data ?? [];
        $diff = [];
        foreach ($new as $k => $v) {
            $ov = $old[$k] ?? null;
            if ($ov !== $v) {
                $diff[$k] = ['old' => $ov, 'new' => $v];
            }
        }
        return view('superadmin.approvals.show', compact('approval', 'diff'));
    }
}
