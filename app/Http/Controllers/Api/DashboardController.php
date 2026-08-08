<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asatidz;
use App\Models\Meeting;
use App\Models\Unit;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAsatidz = Asatidz::count();
        $totalMeetings = Meeting::whereMonth('start_time', date('m'))->count();
        $totalUnits = Unit::count();
        
        $recentMeetings = Meeting::with('type')->orderBy('created_at', 'desc')->limit(5)->get();

        // Calculate attendance stats for current month
        $currentMonthMeetings = Meeting::whereMonth('start_time', date('m'))->pluck('id');
        
        $stats = [
            'present' => \App\Models\MeetingParticipant::whereIn('meeting_id', $currentMonthMeetings)->where('attendance_status', 'Present')->count(),
            'permit' => \App\Models\MeetingParticipant::whereIn('meeting_id', $currentMonthMeetings)->where('attendance_status', 'Permit')->count(),
            'sick' => \App\Models\MeetingParticipant::whereIn('meeting_id', $currentMonthMeetings)->where('attendance_status', 'Sick')->count(),
            'late' => \App\Models\MeetingParticipant::whereIn('meeting_id', $currentMonthMeetings)->where('attendance_status', 'Late')->count(),
            'absent' => \App\Models\MeetingParticipant::whereIn('meeting_id', $currentMonthMeetings)->where('attendance_status', 'Absent')->count(),
        ];
        
        $totalExpected = array_sum($stats);
        $totalAttended = $stats['present'] + $stats['late'];
        $percentage = $totalExpected > 0 ? round(($totalAttended / $totalExpected) * 100, 1) : 0;

        // Chart data (last 7 meetings)
        $chartMeetings = Meeting::withCount([
            'participants as present_count' => function ($query) {
                $query->whereIn('attendance_status', ['Present', 'Late']);
            },
            'participants as absent_count' => function ($query) {
                $query->whereIn('attendance_status', ['Absent', 'Sick', 'Permit']);
            }
        ])->orderBy('start_time', 'asc')->limit(7)->get();

        $chartData = [];
        foreach ($chartMeetings as $m) {
            $chartData[] = [
                'name' => mb_substr($m->title, 0, 15) . (mb_strlen($m->title) > 15 ? '...' : ''),
                'Hadir' => $m->present_count ?? 0,
                'Tidak Hadir' => $m->absent_count ?? 0
            ];
        }

        // Recent Scans
        $recentScans = \App\Models\MeetingParticipant::with(['asatidz', 'meeting'])
                        ->whereIn('attendance_status', ['Present', 'Late'])
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_asatidz' => $totalAsatidz,
                'total_meetings' => $totalMeetings,
                'total_units' => $totalUnits,
                'recent_meetings' => $recentMeetings,
                'stats' => $stats,
                'percentage' => $percentage,
                'chart_data' => $chartData,
                'recent_scans' => $recentScans
            ]
        ]);
    }
}
