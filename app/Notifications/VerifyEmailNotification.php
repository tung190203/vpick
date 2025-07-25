<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

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
        $token = encrypt(['email' => $notifiable->email, 'expires_at' => now()->addMinutes(60)]);
        $url = config('app.frontend_url') . "/verify-email?token=" . urlencode($token);
    
        return (new MailMessage)
            ->subject('Xác minh email của bạn')
            ->line('Nhấn vào nút bên dưới để xác minh email.')
            ->action('Xác minh email', $url)
            ->line('Nếu bạn không đăng ký, hãy bỏ qua email này.');
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
