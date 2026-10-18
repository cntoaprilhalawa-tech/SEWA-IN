<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    public function __construct(
        private string $title,
        private string $body,
        private ?string $url = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
