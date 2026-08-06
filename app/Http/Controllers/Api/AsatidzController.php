<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asatidz;
use App\Models\QrCard;
use Illuminate\Support\Facades\DB;

class AsatidzController extends Controller
{
    public function index(Request $request)
    {
        $query = Asatidz::with(['units', 'positions', 'qrCard'])->orderBy('name');
        
        $user = $request->user();
        if ($user && $user->unit_id) {
            $unitIds = $user->unit->getAllChildIds();
            $query->whereHas('units', function($q) use ($unitIds) {
                $q->whereIn('units.id', $unitIds);
            });
        }
        
        $asatidz = $query->get();
        return response()->json([
            'success' => true,
            'data' => $asatidz
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_asatidz' => 'required|string|unique:asatidz,id_asatidz',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'unit_ids' => 'nullable|array',
            'unit_ids.*' => 'exists:units,id',
            'position_ids' => 'nullable|array',
            'position_ids.*' => 'exists:positions,id',
        ]);

        DB::beginTransaction();
        try {
            $asatidz = Asatidz::create([
                'id_asatidz' => $request->id_asatidz,
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            if ($request->has('unit_ids')) {
                $asatidz->units()->sync($request->unit_ids);
            }

            if ($request->has('position_ids')) {
                $asatidz->positions()->sync($request->position_ids);
            }

            // Create QR Card
            QrCard::create([
                'asatidz_id' => $asatidz->id,
                'qr_code' => $request->id_asatidz // typically using ID as QR, per PRD
            ]);

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Menambahkan Asatidz: ' . $asatidz->name
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data asatidz berhasil ditambahkan',
                'data' => $asatidz->load(['units', 'positions', 'qrCard'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data asatidz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $asatidz = Asatidz::with(['units', 'positions', 'qrCard'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $asatidz]);
    }

    public function history($id)
    {
        $asatidz = Asatidz::findOrFail($id);
        $history = \App\Models\MeetingParticipant::with(['meeting.type', 'attendanceLog'])
            ->where('asatidz_id', $asatidz->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'meeting_id' => $p->meeting_id,
                'meeting' => $p->meeting ? [
                    'title' => $p->meeting->title,
                    'start_time' => $p->meeting->start_time,
                    'type' => $p->meeting->type,
                ] : null,
                'attendance_status' => $p->attendance_status,
                'attendance_log' => $p->attendanceLog ? [
                    'time' => $p->attendanceLog->time,
                    'status' => $p->attendanceLog->status,
                ] : null,
            ]);

        return response()->json(['success' => true, 'data' => $history]);
    }

    public function update(Request $request, $id)
    {
        $asatidz = Asatidz::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'unit_ids' => 'nullable|array',
            'position_ids' => 'nullable|array',
        ]);

        $asatidz->update($request->only(['name', 'phone']));

        if ($request->has('unit_ids')) {
            $asatidz->units()->sync($request->unit_ids);
        }
        if ($request->has('position_ids')) {
            $asatidz->positions()->sync($request->position_ids);
        }

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui', 'data' => $asatidz->load(['units', 'positions'])]);
    }

    public function destroy(Request $request, $id)
    {
        $asatidz = Asatidz::find($id);
        if (!$asatidz) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $name = $asatidz->name;
        $asatidz->delete();

        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'Menghapus Asatidz: ' . $name
        ]);

        return response()->json(['success' => true, 'message' => 'Data asatidz berhasil dihapus']);
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
            
            $rows->each(function(array $row) {
                // Ignore rows without ID or Name
                if (empty($row['id_asatidz']) || empty($row['name'])) return;
                
                $asatidz = Asatidz::updateOrCreate(
                    ['id_asatidz' => $row['id_asatidz']],
                    [
                        'name' => $row['name'],
                        'phone' => $row['phone'] ?? null
                    ]
                );

                \App\Models\QrCard::firstOrCreate(
                    ['asatidz_id' => $asatidz->id],
                    ['qr_code' => $asatidz->id_asatidz]
                );
            });

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Import Data Asatidz Excel'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data asatidz berhasil diimpor']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mengimpor data: ' . $e->getMessage()], 500);
        }
    }
}
