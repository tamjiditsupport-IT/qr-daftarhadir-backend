<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $positions = \App\Models\Position::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $positions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $position = \App\Models\Position::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jabatan berhasil ditambahkan',
            'data' => $position
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $position = \App\Models\Position::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $position->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jabatan berhasil diperbarui',
            'data' => $position
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $position = \App\Models\Position::findOrFail($id);

        if ($position->asatidz()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Jabatan tidak dapat dihapus karena masih digunakan oleh Asatidz'
            ], 400);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jabatan berhasil dihapus'
        ]);
    }
}
