<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\QrCard;
use App\Models\MeetingParticipant;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'qr_code' => 'required|string',
        ]);

        $meeting = Meeting::find($request->meeting_id);
        if ($meeting->status !== 'running') {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak sedang berlangsung',
                'error_code' => 'MEETING_NOT_RUNNING'
            ], 400);
        }

        $qrCard = QrCard::where('qr_code', $request->qr_code)->with('asatidz')->first();
        if (!$qrCard) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak ditemukan',
                'error_code' => 'QR_NOT_FOUND'
            ], 404);
        }
        
        $asatidz = $qrCard->asatidz;

        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
                        ->where('asatidz_id', $asatidz->id)
                        ->first();
        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Asatidz tidak diundang ke rapat ini',
                'error_code' => 'NOT_INVITED'
            ], 400);
        }

        $existingLog = AttendanceLog::where('meeting_id', $meeting->id)
                        ->where('asatidz_id', $asatidz->id)
                        ->first();
        if ($existingLog) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah Hadir',
                'error_code' => 'ALREADY_PRESENT'
            ], 422);
        }

        $now = Carbon::now();
        $startTime = Carbon::parse($meeting->start_time);
        $lateToleratedTime = $startTime->copy()->addMinutes($meeting->late_minutes);
        
        $status = 'Present';
        $lateDurationMinutes = 0;
        
        if ($now->greaterThan($lateToleratedTime)) {
            $status = 'Late';
            $lateDurationMinutes = $now->diffInMinutes($startTime);
        }

        $participant->update([
            'attendance_status' => $status,
        ]);

        $log = AttendanceLog::create([
            'meeting_id' => $meeting->id,
            'asatidz_id' => $asatidz->id,
            'status' => $status,
            'time' => $now->format('H:i:s'),
            'late_duration_minutes' => $lateDurationMinutes,
        ]);

        broadcast(new \App\Events\AttendanceScanned($participant))->toOthers();

        return response()->json([
            'success' => true,
            'status' => $status,
            'time' => $now->format('H:i:s'),
            'late_duration_minutes' => $lateDurationMinutes,
            'name' => $asatidz->name
        ]);
    }

    public function manual(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'asatidz_id' => 'required|exists:asatidz,id',
            'status' => 'required|in:Present,Late,Absent,Sick,Excused'
        ]);

        $meeting = Meeting::findOrFail($request->meeting_id);
        
        $log = AttendanceLog::where('meeting_id', $meeting->id)
                ->where('asatidz_id', $request->asatidz_id)
                ->first();

        if ($log) {
            $log->update([
                'status' => $request->status,
                'late_duration_minutes' => 0
            ]);
        } else {
            AttendanceLog::create([
                'meeting_id' => $meeting->id,
                'asatidz_id' => $request->asatidz_id,
                'status' => $request->status,
                'time' => Carbon::now()->format('H:i:s'),
                'late_duration_minutes' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil diperbarui',
        ]);
    }
}
