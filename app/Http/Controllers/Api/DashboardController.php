<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asatidz;
use App\Models\Meeting;
use App\Models\Unit;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAsatidz = Asatidz::count();
        $totalMeetings = Meeting::whereMonth('start_time', date('m'))->count();
        $totalUnits = Unit::count();
        
        $recentMeetings = Meeting::with('type')->orderBy('created_at', 'desc')->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_asatidz' => $totalAsatidz,
                'total_meetings' => $totalMeetings,
                'total_units' => $totalUnits,
                'recent_meetings' => $recentMeetings
            ]
        ]);
    }
}
