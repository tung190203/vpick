<?php

namespace App\Notifications;

use App\Models\MiniParticipant;
use App\Models\MiniParticipantPayment;
use App\Models\MiniTournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    public function __construct(MiniParticipantPayment $payment)
    {
        $this->payment = $payment;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $tournament = $this->payment->miniTournament;
        
        return [
            'payment_id' => $this->payment->id,
            'mini_tournament_id' => $tournament->id,
            'title' => 'Thanh toán được xác nhận',
            'message' => "Thanh toán phí tham gia kèo '{$tournament->name}' của bạn đã được xác nhận.",
            'amount' => $this->payment->amount,
        ];
    }

    public function toBroadcast($notifiable)
    {
        $tournament = $this->payment->miniTournament;
        
        return new BroadcastMessage([
            'payment_id' => $this->payment->id,
            'mini_tournament_id' => $tournament->id,
            'title' => 'Thanh toán được xác nhận',
            'message' => "Thanh toán phí tham gia kèo '{$tournament->name}' của bạn đã được xác nhận.",
            'amount' => $this->payment->amount,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
