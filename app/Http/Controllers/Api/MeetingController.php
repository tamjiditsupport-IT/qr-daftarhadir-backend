<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Asatidz;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::with(['type', 'creator'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $meetings
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'meeting_type_id' => 'required|exists:meeting_types,id',
            'start_time' => 'required|date',
            'late_minutes' => 'required|integer',
            'unit_ids' => 'nullable|array',
            'unit_ids.*' => 'exists:units,id'
        ]);

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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $meeting = Meeting::with([
            'type', 
            'creator',
            'participants.asatidz',
            'attendanceLogs'
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

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil diakhiri',
            'data' => $meeting
        ]);
    }
}
