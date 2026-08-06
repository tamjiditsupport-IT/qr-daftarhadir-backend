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
    public function index(Request $request)
    {
        $query = Approval::with(['meeting', 'asatidz', 'requestedBy', 'approver'])
                    ->orderBy('created_at', 'desc');
                    
        $user = $request->user();
        if ($user && $user->unit_id) {
            $unitIds = $user->unit->getAllChildIds();
            $query->whereHas('asatidz.units', function($q) use ($unitIds) {
                $q->whereIn('units.id', $unitIds);
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $query->get()
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
                $participant = \App\Models\MeetingParticipant::where('meeting_id', $approval->meeting_id)
                                ->where('asatidz_id', $approval->asatidz_id)->first();
                                
                if ($approval->type === 'Attendance') {
                    $meeting = \App\Models\Meeting::find($approval->meeting_id);
                    $startTime = Carbon::parse($meeting->start_time);
                    $lateToleratedTime = $startTime->copy()->addMinutes($meeting->late_minutes);
                    $scanTime = Carbon::parse($approval->created_at);
                    
                    $status = 'Present';
                    $lateDurationMinutes = 0;
                    if ($scanTime->greaterThan($lateToleratedTime)) {
                        $status = 'Late';
                        $lateDurationMinutes = $scanTime->diffInMinutes($startTime);
                    }
                    
                    if ($participant) $participant->update(['attendance_status' => $status]);
                    
                    AttendanceLog::create([
                        'meeting_id' => $approval->meeting_id,
                        'asatidz_id' => $approval->asatidz_id,
                        'status' => $status,
                        'time' => $scanTime->format('H:i:s'),
                        'late_duration_minutes' => $lateDurationMinutes,
                    ]);
                    
                    if ($participant) broadcast(new \App\Events\AttendanceScanned($participant))->toOthers();
                } else {
                    if ($participant) $participant->update(['attendance_status' => $approval->type]);
                    
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
            } else if ($request->status === 'Rejected' && $approval->type === 'Attendance') {
                $participant = \App\Models\MeetingParticipant::where('meeting_id', $approval->meeting_id)
                                ->where('asatidz_id', $approval->asatidz_id)->first();
                if ($participant) $participant->update(['attendance_status' => 'Absent']);
                if ($participant) broadcast(new \App\Events\AttendanceScanned($participant))->toOthers();
            }

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Meresolusi Persetujuan (' . $request->status . '): ' . $approval->type . ' untuk Asatidz ID ' . $approval->asatidz_id
            ]);

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
