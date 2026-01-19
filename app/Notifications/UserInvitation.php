<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $invitationUrl,
        public string $inviterName
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You\'ve been invited to join ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->inviterName . ' has invited you to join ' . config('app.name') . '.')
            ->line('To accept this invitation and set up your account, please click the button below:')
            ->action('Accept Invitation', $this->invitationUrl)
            ->line('This invitation will expire in 48 hours.')
            ->line('If you did not expect to receive an invitation, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_url' => $this->invitationUrl,
            'inviter_name' => $this->inviterName,
        ];
    }
}
