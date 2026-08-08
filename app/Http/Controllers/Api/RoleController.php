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

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);
        $role = Role::create(['name' => $request->name]);
        return response()->json(['success' => true, 'message' => 'Role berhasil ditambahkan', 'data' => $role]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'permissions' => 'nullable|array',
            'name' => 'nullable|string|unique:roles,name,'.$id
        ]);
        
        if ($request->has('name') && $request->name !== '') {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }
        
        return response()->json(['success' => true, 'message' => 'Role diperbarui']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if (in_array($role->name, ['super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Role ini tidak dapat dihapus'], 400);
        }
        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role berhasil dihapus']);
    }
}
