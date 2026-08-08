<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Asatidz;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreMeetingRequest;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $query = Meeting::with(['type', 'creator'])->orderBy('created_at', 'desc');
        
        if ($request->query('all')) {
            $meetings = $query->get();
        } else {
            $meetings = $query->paginate(20);
        }
        
        return response()->json([
            'success' => true,
            'data' => $meetings
        ]);
    }

    public function store(StoreMeetingRequest $request)
    {
        DB::beginTransaction();
        try {
            $meeting = Meeting::create([
                'title' => $request->title,
                'meeting_type_id' => $request->meeting_type_id,
                'start_time' => $request->start_time,
                'late_minutes' => $request->late_minutes,
                'status' => 'scheduled',
                'created_by' => $request->user()->id ?? null
            ]);

            // Get asatidz snapshot based on units
            if ($request->has('unit_ids') && count($request->unit_ids) > 0) {
                $asatidzIds = DB::table('asatidz_units')
                                ->whereIn('unit_id', $request->unit_ids)
                                ->pluck('asatidz_id')
                                ->unique();

                $participants = [];
                foreach ($asatidzIds as $id) {
                    $participants[] = [
                        'meeting_id' => $meeting->id,
                        'asatidz_id' => $id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (count($participants) > 0) {
                    MeetingParticipant::insert($participants);
                }
            }

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Membuat rapat baru: ' . $meeting->title
            ]);

            // Notify super admins
            $superAdmins = \App\Models\User::whereHas('roles', function($q){
                $q->where('name', 'super_admin')->orWhere('name', 'admin_yayasan');
            })->get();

            foreach($superAdmins as $admin) {
                if ($admin->id !== ($request->user()->id ?? null)) {
                    $admin->notify(new \App\Notifications\MeetingRequested($meeting));
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Rapat berhasil dibuat',
                'data' => $meeting
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat rapat',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show($id)
    {
        $meeting = Meeting::with([
            'type', 
            'creator',
            'participants.asatidz',
            'attendanceLogs',
            'attachments',
            'attachments.uploader',
            'histories',
            'histories.user'
        ])->find($id);

        if (!$meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $meeting
        ]);
    }

    public function start(Request $request, $id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan'
            ], 404);
        }

        if ($meeting->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak bisa dimulai karena statusnya ' . $meeting->status
            ], 400);
        }

        $meeting->status = 'running';
        $meeting->save();

        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'Memulai rapat: ' . $meeting->title
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil dimulai',
            'data' => $meeting
        ]);
    }

    public function finish(Request $request, $id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan'
            ], 404);
        }

        if ($meeting->status !== 'running') {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak bisa diakhiri karena statusnya ' . $meeting->status
            ], 400);
        }

        $meeting->status = 'finished';
        $meeting->save();

        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'Mengakhiri rapat: ' . $meeting->title
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil diakhiri',
            'data' => $meeting
        ]);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:5120'
        ]);

        $file = $request->file('file');
        
        DB::beginTransaction();
        try {
            $extension = $file->getClientOriginalExtension() ?: 'xlsx';
            $rows = \Spatie\SimpleExcel\SimpleExcelReader::create($file->getRealPath(), $extension)->getRows();
            
            $rows->each(function(array $row) use ($request) {
                if (empty($row['title']) || empty($row['start_time'])) return;
                
                $meeting = Meeting::create([
                    'title' => $row['title'],
                    'meeting_type_id' => $row['meeting_type_id'] ?: 1,
                    'start_time' => $row['start_time'],
                    'late_minutes' => $row['late_minutes'] ?: 15,
                    'status' => 'scheduled',
                    'created_by' => $request->user()->id ?? null
                ]);

                if (!empty($row['unit_ids'])) {
                    $unitIds = array_map('trim', explode(',', (string)$row['unit_ids']));
                    
                    $asatidzIds = DB::table('asatidz_units')
                                    ->whereIn('unit_id', $unitIds)
                                    ->pluck('asatidz_id')
                                    ->unique();

                    $participants = [];
                    foreach ($asatidzIds as $id) {
                        $participants[] = [
                            'meeting_id' => $meeting->id,
                            'asatidz_id' => $id,
                            'attendance_status' => 'Absent',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    
                    if (count($participants) > 0) {
                        MeetingParticipant::insert($participants);
                    }
                }
            });

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Import Data Meetings Excel'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data meetings berhasil diimpor']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => app()->isLocal() ? 'Gagal mengimpor data: ' . $e->getMessage() : 'Gagal mengimpor data'], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/template_import_meetings_' . time() . '.xlsx');
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($path);
        
        $writer->addRow([
            'title' => 'Rapat Awal Tahun',
            'meeting_type_id' => '1',
            'start_time' => '2026-09-01 08:00:00',
            'late_minutes' => '15',
            'unit_ids' => '1,2,3'
        ]);
        
        return response()->download($path, 'Template_Import_Meetings.xlsx')->deleteFileAfterSend(true);
    }
}
