<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\Asatidz;
use App\Models\Unit;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => ['meetings' => [], 'asatidz' => [], 'units' => []]]);
        }

        // Search Meetings
        $meetings = Meeting::where('title', 'like', "%{$q}%")
            ->orWhere('meeting_code', 'like', "%{$q}%")
            ->take(5)
            ->get();

        // Search Asatidz
        $asatidz = Asatidz::where('name', 'like', "%{$q}%")
            ->orWhere('niy', 'like', "%{$q}%")
            ->take(5)
            ->get();

        // Search Units
        $units = Unit::where('name', 'like', "%{$q}%")
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'meetings' => $meetings,
                'asatidz' => $asatidz,
                'units' => $units
            ]
        ]);
    }
}
