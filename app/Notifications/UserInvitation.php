<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     *
     * @var array<int>
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $invitationUrl,
        public string $inviterName,
        public int $inviterId,
        public int $notifiableId,
        public string $notifiableEmail
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
            'inviter_name' => $this->inviterName,
            'inviter_id' => $this->inviterId,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User invitation email failed', [
            'notifiable_id' => $this->notifiableId,
            'notifiable_email' => $this->notifiableEmail,
            'inviter_name' => $this->inviterName,
            'inviter_id' => $this->inviterId,
            'exception_class' => $exception::class,
            'exception' => $exception->getMessage(),
        ]);
    }
}
