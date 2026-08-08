<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreUserRequest;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'unit'])->orderBy('name')->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(StoreUserRequest $request)
    {

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
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
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
