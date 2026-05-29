<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationStatusNotification extends Notification
{
    use Queueable;

    protected $event;
    protected $status;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($event, $status, $message = null)
    {
        $this->event = $event;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $defaultMessage = "Your registration status for {$this->event->title} has been updated to {$this->status}.";
        $messageText = $this->message ?? $defaultMessage;

        return (new MailMessage)
            ->subject("Event Registration Status: {$this->event->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($messageText)
            ->action('View Event Details', url(env('FRONTEND_URL', 'http://localhost:5173') . "/events/{$this->event->id}"))
            ->line('Thank you for using Eventify!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $defaultMessage = "Your registration status for {$this->event->title} has been updated to {$this->status}.";

        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'status' => $this->status,
            'message' => $this->message ?? $defaultMessage,
        ];
    }
}
