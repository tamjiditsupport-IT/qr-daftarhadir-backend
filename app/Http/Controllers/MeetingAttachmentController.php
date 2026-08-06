<?php

namespace App\Http\Controllers;

use App\Models\MeetingAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MeetingAttachmentController extends Controller
{
    public function store(Request $request, $meetingId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('meeting_attachments', 'public');

        $attachment = MeetingAttachment::create([
            'meeting_id' => $meetingId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diunggah',
            'data' => $attachment
        ]);
    }

    public function destroy($id)
    {
        $attachment = MeetingAttachment::findOrFail($id);
        
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus'
        ]);
    }

    public function download($id)
    {
        $attachment = MeetingAttachment::findOrFail($id);
        
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
