<?php

namespace App\Notifications;

use App\Models\MiniParticipantPayment;
use App\Models\MiniTournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification implements ShouldQueue
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
            'title' => 'Thanh toán bị từ chối',
            'message' => "Thanh toán phí tham gia kèo '{$tournament->name}' của bạn đã bị từ chối. " . 
                ($this->payment->admin_note ? "Lý do: {$this->payment->admin_note}" : "Vui lòng thanh toán lại."),
            'amount' => $this->payment->amount,
        ];
    }

    public function toBroadcast($notifiable)
    {
        $tournament = $this->payment->miniTournament;
        
        return new BroadcastMessage([
            'payment_id' => $this->payment->id,
            'mini_tournament_id' => $tournament->id,
            'title' => 'Thanh toán bị từ chối',
            'message' => "Thanh toán phí tham gia kèo '{$tournament->name}' của bạn đã bị từ chối. " . 
                ($this->payment->admin_note ? "Lý do: {$this->payment->admin_note}" : "Vui lòng thanh toán lại."),
            'amount' => $this->payment->amount,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
