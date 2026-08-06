<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingApproved extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $meeting;
    public $status; // 'approved' or 'rejected'

    public function __construct($meeting, $status)
    {
        $this->meeting = $meeting;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $statusText = $this->status === 'approved' ? 'Disetujui' : 'Ditolak';
        return [
            'meeting_id' => $this->meeting->id,
            'title' => 'Rapat ' . $statusText,
            'message' => 'Pengajuan rapat "' . $this->meeting->title . '" telah ' . strtolower($statusText) . ' oleh Yayasan.'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
