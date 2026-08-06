<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function exportExcel($id)
    {
        $meeting = Meeting::findOrFail($id);
        $participants = MeetingParticipant::with('asatidz')->where('meeting_id', $id)->get();

        $filename = "daftar_hadir_{$id}.csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'ID Asatidz', 'Nama Asatidz', 'Status Kehadiran', 'Waktu'];

        $callback = function() use($participants, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $rowCounter = 1;
            foreach ($participants as $p) {
                fputcsv($file, [
                    $rowCounter++,
                    $p->asatidz->id_asatidz ?? '-',
                    $p->asatidz->name ?? '-',
                    $p->attendance_status,
                    $p->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf($id)
    {
        $meeting = Meeting::with('type', 'unit')->findOrFail($id);
        $participants = MeetingParticipant::with('asatidz')->where('meeting_id', $id)->get();

        $pdf = Pdf::loadView('exports.meeting_pdf', compact('meeting', 'participants'));
        
        return $pdf->download("daftar_hadir_{$id}.pdf");
    }
}
