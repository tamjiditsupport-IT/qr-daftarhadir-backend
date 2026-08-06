<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'unit'])->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name',
            'unit_id' => 'nullable|exists:units,id'
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'unit_id' => $request->role === 'admin_instansi' ? $request->unit_id : null
            ]);

            $user->assignRole($request->role);

            \App\Models\AuditLog::create([
                'user_id' => $request->user()->id ?? null,
                'action' => 'Menambahkan User: ' . $user->name . ' (' . $request->role . ')'
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan',
                'data' => $user->load(['roles', 'unit'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Prevent self deletion
        if ($request->user() && $request->user()->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri'
            ], 400);
        }

        $name = $user->name;
        $user->delete();

        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'Menghapus User: ' . $name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
    }

    public function resetPassword(Request $request, string $id)
    {
        $request->validate(['password' => 'required|string|min:6']);
        $user = User::findOrFail($id);
        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['success' => true, 'message' => 'Password berhasil direset']);
    }
}
