<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Asatidz;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function meetings(Request $request)
    {
        $meetings = Meeting::with(['type', 'participants', 'attendanceLogs'])
            ->whereIn('status', ['finished', 'running'])
            ->orderByDesc('start_time')
            ->get()
            ->map(function ($m) {
                $total = $m->participants->count();
                $present = $m->attendanceLogs->where('status', 'Present')->count();
                $late = $m->attendanceLogs->where('status', 'Late')->count();
                $absent = $total - $present - $late - $m->attendanceLogs->whereIn('status', ['Sick', 'Excused'])->count();
                $percentage = $total > 0 ? round((($present + $late) / $total) * 100) : 0;
                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'date' => $m->start_time,
                    'type' => $m->type?->name,
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => max(0, $absent),
                    'percentage' => $percentage,
                ];
            });

        return response()->json(['success' => true, 'data' => $meetings]);
    }

    public function units(Request $request)
    {
        $units = Unit::withCount('asatidz')->get()->map(function ($unit) {
            $participants = MeetingParticipant::where(function ($q) use ($unit) {
                $q->whereHas('asatidz', fn($aq) => $aq->where('unit_id', $unit->id));
            })->get();

            $total = $participants->count();
            $present = $participants->whereIn('attendance_status', ['Present', 'Late'])->count();
            $absent = $participants->where('attendance_status', 'Absent')->count();

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'total_asatidz' => $unit->asatidz_count,
                'total_meetings' => $participants->groupBy('meeting_id')->count(),
                'total_present' => $present,
                'avg_percentage' => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        });

        return response()->json(['success' => true, 'data' => $units]);
    }

    public function asatidz(Request $request)
    {
        $asatidz = Asatidz::with(['participants'])->get()->map(function ($a) {
            $total = $a->participants->count();
            $present = $a->participants->where('attendance_status', 'Present')->count();
            $late = $a->participants->where('attendance_status', 'Late')->count();
            $absent = $a->participants->whereIn('attendance_status', ['Absent', null])->count();

            return [
                'id' => $a->id,
                'name' => $a->name,
                'id_asatidz' => $a->id_asatidz,
                'total_meetings' => $total,
                'total_present' => $present + $late,
                'total_absent' => $absent,
                'percentage' => $total > 0 ? round((($present + $late) / $total) * 100) : 0,
            ];
        })->sortByDesc('percentage')->values();

        return response()->json(['success' => true, 'data' => $asatidz]);
    }

    public function monthly(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $meetings = Meeting::with(['participants', 'attendanceLogs'])
            ->whereYear('start_time', $year)
            ->whereMonth('start_time', $month)
            ->whereIn('status', ['finished', 'running'])
            ->get();

        $data = $meetings->map(function ($m) {
            $total = $m->participants->count();
            $present = $m->attendanceLogs->whereIn('status', ['Present', 'Late'])->count();
            $absent = $total - $present;
            return [
                'id' => $m->id,
                'period' => $m->title,
                'total_meetings' => 1,
                'total_present' => $present,
                'total_absent' => max(0, $absent),
                'percentage' => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function yearly(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $meetings = Meeting::with(['participants', 'attendanceLogs'])
                ->whereYear('start_time', $year)
                ->whereMonth('start_time', $m)
                ->whereIn('status', ['finished', 'running'])
                ->get();

            $totalParticipants = 0;
            $totalPresent = 0;
            $totalAbsent = 0;

            foreach ($meetings as $meeting) {
                $t = $meeting->participants->count();
                $p = $meeting->attendanceLogs->whereIn('status', ['Present', 'Late'])->count();
                $totalParticipants += $t;
                $totalPresent += $p;
                $totalAbsent += max(0, $t - $p);
            }

            $rows[] = [
                'period' => ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$m-1] . ' ' . $year,
                'total_meetings' => $meetings->count(),
                'total_present' => $totalPresent,
                'total_absent' => $totalAbsent,
                'percentage' => $totalParticipants > 0 ? round(($totalPresent / $totalParticipants) * 100) : 0,
            ];
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function export(Request $request)
    {
        $asatidz = Asatidz::with(['units', 'positions'])->orderBy('name')->get();
        $path = storage_path('app/export_asatidz_' . time() . '.xlsx');
        
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($path);
        
        foreach ($asatidz as $a) {
            $writer->addRow([
                'ID Asatidz' => $a->id_asatidz,
                'Nama' => $a->name,
                'Phone' => $a->phone,
                'Unit' => $a->units->pluck('name')->implode(', '),
                'Jabatan' => $a->positions->pluck('name')->implode(', '),
            ]);
        }
        
        return response()->download($path, 'Data_Asatidz.xlsx')->deleteFileAfterSend(true);
    }
}
