<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function permissions()
    {
        $permissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $permissions]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate(['permissions' => 'array']);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }
        
        return response()->json(['success' => true, 'message' => 'Role permissions diperbarui']);
    }
}
