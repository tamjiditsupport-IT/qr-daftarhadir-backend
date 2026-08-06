<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request)
    {
        $allowed = ['organization_name', 'late_minutes', 'timezone', 'logo'];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $allowed)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }
        return response()->json(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
    }
}
