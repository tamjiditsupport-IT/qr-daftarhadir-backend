<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }
}
