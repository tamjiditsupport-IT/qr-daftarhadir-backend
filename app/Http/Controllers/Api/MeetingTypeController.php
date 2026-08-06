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
}
