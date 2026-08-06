<?php

namespace App\Observers;

use App\Models\Meeting;
use App\Models\MeetingHistory;

class MeetingObserver
{
    private function logHistory(Meeting $meeting, string $action, string $description = null)
    {
        MeetingHistory::create([
            'meeting_id' => $meeting->id,
            'user_id' => auth()->id() ?? $meeting->created_by,
            'action' => $action,
            'description' => $description
        ]);
    }

    public function created(Meeting $meeting): void
    {
        $this->logHistory($meeting, 'Dibuat', 'Rapat dijadwalkan pada ' . $meeting->start_time->format('d M Y H:i'));
    }

    public function updated(Meeting $meeting): void
    {
        if ($meeting->isDirty('status')) {
            $oldStatus = $meeting->getOriginal('status');
            $newStatus = $meeting->status;
            
            $statusMap = [
                'scheduled' => 'Dijadwalkan',
                'ongoing' => 'Dimulai',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan'
            ];
            
            $statusText = $statusMap[$newStatus] ?? $newStatus;
            
            if ($newStatus === 'ongoing') {
                $this->logHistory($meeting, 'Dimulai', 'Rapat resmi dimulai.');
            } elseif ($newStatus === 'completed') {
                $this->logHistory($meeting, 'Selesai', 'Rapat telah diselesaikan.');
            } else {
                $this->logHistory($meeting, 'Status Berubah', "Status diubah dari $oldStatus menjadi $newStatus");
            }
        }
    }

    public function deleted(Meeting $meeting): void
    {
        //
    }

    public function restored(Meeting $meeting): void
    {
        //
    }

    public function forceDeleted(Meeting $meeting): void
    {
        //
    }
}
