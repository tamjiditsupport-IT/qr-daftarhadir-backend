<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends Controller
{
    public function tree()
    {
        $units = Unit::with('children')->whereNull('parent_id')->get();
        // Since we want infinite depth, we should eager load deeply or just use a helper
        // A simple approach for moderate depth:
        $units = Unit::with('children.children.children')->whereNull('parent_id')->get();
        
        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    public function index()
    {
        $units = Unit::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:units,id'
        ]);

        $unit = Unit::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit berhasil ditambahkan',
            'data' => $unit
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:units,id'
        ]);

        // Prevent setting parent to itself
        if ($request->parent_id == $unit->id) {
            return response()->json([
                'success' => false,
                'message' => 'Induk unit tidak valid'
            ], 400);
        }

        $unit->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit berhasil diperbarui',
            'data' => $unit
        ]);
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);

        // Check if it has children
        if ($unit->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak dapat dihapus karena memiliki sub-unit'
            ], 400);
        }

        // Check if it has asatidz
        if ($unit->asatidz()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak dapat dihapus karena masih ada Asatidz yang terdaftar'
            ], 400);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit berhasil dihapus'
        ]);
    }
}
