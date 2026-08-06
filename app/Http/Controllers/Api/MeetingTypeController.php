<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingType;

class MeetingTypeController extends Controller
{
    public function index()
    {
        $types = MeetingType::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $type = MeetingType::create(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'Tipe Rapat berhasil ditambahkan', 'data' => $type], 201);
    }

    public function update(Request $request, string $id)
    {
        $type = MeetingType::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255']);
        $type->update(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'Tipe Rapat berhasil diperbarui', 'data' => $type]);
    }

    public function destroy(string $id)
    {
        $type = MeetingType::findOrFail($id);
        if ($type->meetings()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Tipe Rapat tidak dapat dihapus karena sudah digunakan pada Data Rapat'], 400);
        }
        $type->delete();
        return response()->json(['success' => true, 'message' => 'Tipe Rapat berhasil dihapus']);
    }
}
