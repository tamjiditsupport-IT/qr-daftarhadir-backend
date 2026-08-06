<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Approval;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    public function index()
    {
        $approvals = Approval::with(['meeting', 'asatidz', 'requestedBy', 'approver'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        return response()->json([
            'success' => true,
            'data' => $approvals
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'asatidz_id' => 'required|exists:asatidz,id',
            'type' => 'required|in:Sick,Excused',
            'notes' => 'nullable|string'
        ]);

        $approval = Approval::create([
            'meeting_id' => $request->meeting_id,
            'asatidz_id' => $request->asatidz_id,
            'type' => $request->type,
            'status' => 'Pending',
            'notes' => $request->notes,
            'requested_by' => $request->user()->id ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim dan menunggu persetujuan',
            'data' => $approval
        ], 201);
    }

    public function resolve(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'notes' => 'nullable|string'
        ]);

        $approval = Approval::findOrFail($id);

        DB::beginTransaction();
        try {
            $approval->update([
                'status' => $request->status,
                'approved_by' => $request->user()->id ?? null,
                'notes' => $request->notes ?? $approval->notes,
                'approved_at' => Carbon::now()
            ]);

            if ($request->status === 'Approved') {
                $log = AttendanceLog::where('meeting_id', $approval->meeting_id)
                        ->where('asatidz_id', $approval->asatidz_id)
                        ->first();

                if ($log) {
                    $log->update([
                        'status' => $approval->type,
                        'late_duration_minutes' => 0
                    ]);
                } else {
                    AttendanceLog::create([
                        'meeting_id' => $approval->meeting_id,
                        'asatidz_id' => $approval->asatidz_id,
                        'status' => $approval->type,
                        'time' => Carbon::now()->format('H:i:s'),
                        'late_duration_minutes' => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil diproses',
                'data' => $approval
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
