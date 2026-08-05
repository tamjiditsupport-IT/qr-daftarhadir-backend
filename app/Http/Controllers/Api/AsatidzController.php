<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asatidz;
use App\Models\QrCard;
use Illuminate\Support\Facades\DB;

class AsatidzController extends Controller
{
    public function index()
    {
        $asatidz = Asatidz::with(['units', 'positions', 'qrCard'])->orderBy('name')->get();
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
}
