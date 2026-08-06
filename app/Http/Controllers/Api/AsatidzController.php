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

    public function destroy(Request $request, $id)
    {
        $asatidz = Asatidz::find($id);
        if (!$asatidz) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $name = $asatidz->name;
        $asatidz->delete();

        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'Menghapus Asatidz: ' . $name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data asatidz berhasil dihapus'
        ]);
    }
}
