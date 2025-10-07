<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $type;
    protected string $identifier;

    public function __construct(string $type, string $identifier)
    {
        $this->type = $type;
        $this->identifier = $identifier;
    }

    public function via($notifiable)
    {
        return $this->type === 'email' ? ['mail'] : ['database'];
        // Với 'phone' bạn có thể thay 'database' bằng service SMS thực tế (Twilio, ViettelSMS, v.v.)
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $otp = rand(100000, 999999);

        DB::table('verification_codes')->updateOrInsert(
            ['type' => $this->type, 'identifier' => $this->identifier],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Dữ liệu truyền qua view
        $data = [
            'otp' => $otp,
            'type' => $this->type,
        ];

        // Dùng custom view
        return (new MailMessage)
            ->subject('Mã xác minh tài khoản của bạn')
            ->view('emails.verify', $data);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'identifier' => $this->identifier,
        ];
    }
}
