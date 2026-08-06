<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingRequested extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $meeting;

    public function __construct($meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'title' => 'Persetujuan Rapat Baru',
            'message' => 'Rapat "' . $this->meeting->title . '" membutuhkan persetujuan Anda.'
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
            'meeting_id' => $this->meeting->id,
            'title' => 'Persetujuan Rapat Baru',
            'message' => 'Rapat "' . $this->meeting->title . '" membutuhkan persetujuan Anda.'
        ];
    }
}
