<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MiniParticipantPaymentResource;
use App\Http\Resources\MiniTournamentResource;
use App\Models\MiniParticipant;
use App\Models\MiniParticipantPayment;
use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use App\Models\MiniMatch;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MiniTournamentPaymentController extends Controller
{
    /**
     * Lấy chi tiết khoản thu phí của kèo
     * API: GET /api/mini-tournaments/{id}/payments
     */
    public function index(Request $request, $miniTournamentId)
    {
        $miniTournament = MiniTournament::with([
            'competitionLocation',
            'sport',
        ])->findOrFail($miniTournamentId);

        // Load payments với user relationships
        $payments = MiniParticipantPayment::with(['user', 'confirmer'])
            ->where('mini_tournament_id', $miniTournamentId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group payments by status
        $pendingPayments = $payments->filter(fn($p) => $p->status === MiniParticipantPayment::STATUS_PENDING);
        $paidPayments = $payments->filter(fn($p) => $p->status === MiniParticipantPayment::STATUS_PAID);
        $confirmedPayments = $payments->filter(fn($p) => $p->status === MiniParticipantPayment::STATUS_CONFIRMED);
        $rejectedPayments = $payments->filter(fn($p) => $p->status === MiniParticipantPayment::STATUS_REJECTED);

        // Tính tổng tiền
        $participantCount = $miniTournament->participants()->count();
        
        // Tính số tiền mỗi người phải đóng
        $feePerPerson = 0;
        if ($miniTournament->has_fee) {
            if ($miniTournament->auto_split_fee) {
                // Chia tự động: tổng tiền / số người hiện tại
                $feePerPerson = $participantCount > 0 ? round($miniTournament->fee_amount / $participantCount) : 0;
            } else {
                // Tiền cố định mỗi người
                $feePerPerson = $miniTournament->fee_amount;
            }
        }

        $data = [
            'tournament' => new MiniTournamentResource($miniTournament),
            'payment_config' => [
                'has_fee' => $miniTournament->has_fee,
                'auto_split_fee' => $miniTournament->auto_split_fee,
                'fee_amount' => $miniTournament->fee_amount,
                'fee_per_person' => $feePerPerson,
                'fee_description' => $miniTournament->fee_description,
                'qr_code_url' => $miniTournament->qr_code_url,
                'payment_account_id' => $miniTournament->payment_account_id,
            ],
            'summary' => [
                'total_participants' => $participantCount,
                'total_expected' => $feePerPerson * $participantCount,
                'total_collected' => $confirmedPayments->sum('amount'),
                'total_pending' => $pendingPayments->count(),
                'total_awaiting_confirmation' => $paidPayments->count(),
                'total_confirmed' => $confirmedPayments->count(),
                'total_rejected' => $rejectedPayments->count(),
            ],
            'payments' => MiniParticipantPaymentResource::collection($payments),
            'pending_payments' => MiniParticipantPaymentResource::collection($pendingPayments->values()),
            'awaiting_confirmation_payments' => MiniParticipantPaymentResource::collection($paidPayments->values()),
            'confirmed_payments' => MiniParticipantPaymentResource::collection($confirmedPayments->values()),
        ];

        return ResponseHelper::success($data, 'Lấy thông tin thanh toán thành công');
    }

    /**
     * API đóng phí kèo cho thành viên
     * API: POST /api/mini-tournaments/{id}/pay
     * Body: participant_id, receipt_image (bắt buộc), note (không bắt buộc)
     */
    public function pay(Request $request, $miniTournamentId)
    {
        $data = $request->validate([
            'participant_id' => 'required|exists:mini_participants,id',
            'receipt_image' => 'required|string', // base64 hoặc URL
            'note' => 'nullable|string|max:500',
        ]);

        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        // Nếu kèo chia tiền tự động thì chỉ được thanh toán sau khi đã có ít nhất 1 trận hoàn tất
        if ($miniTournament->auto_split_fee) {
            $hasCompletedMatch = MiniMatch::where('mini_tournament_id', $miniTournament->id)
                ->where('status', MiniMatch::STATUS_COMPLETED)
                ->exists();

            if (!$hasCompletedMatch) {
                return ResponseHelper::error(
                    'Kèo đang cài đặt chia tiền tự động, chỉ được thanh toán sau khi trận đấu đã kết thúc',
                    400
                );
            }
        }

        // Kiểm tra kèo có thu phí không
        if (!$miniTournament->has_fee) {
            return ResponseHelper::error('Kèo này không thu phí tham gia', 400);
        }

        $participant = MiniParticipant::where('id', $data['participant_id'])
            ->where('mini_tournament_id', $miniTournamentId)
            ->first();

        if (!$participant) {
            return ResponseHelper::error('Không tìm thấy thành viên', 404);
        }

        $userId = Auth::id();
        if ($participant->user_id !== $userId) {
            return ResponseHelper::error('Bạn không có quyền thanh toán cho thành viên này', 403);
        }

        // Tính số tiền phải đóng
        $participantCount = $miniTournament->participants()->count();
        $feePerPerson = 0;
        
        if ($miniTournament->auto_split_fee) {
            // Chia tự động: tổng tiền / số người
            $feePerPerson = $participantCount > 0 ? round($miniTournament->fee_amount / $participantCount) : 0;
        } else {
            // Tiền cố định mỗi người
            $feePerPerson = $miniTournament->fee_amount;
        }

        // Kiểm tra xem đã có payment record chưa
        $existingPayment = MiniParticipantPayment::where('mini_tournament_id', $miniTournamentId)
            ->where('participant_id', $data['participant_id'])
            ->first();

        DB::beginTransaction();
        try {
            if ($existingPayment) {
                // Cập nhật payment existing
                if (in_array($existingPayment->status, [MiniParticipantPayment::STATUS_CONFIRMED])) {
                    DB::rollBack();
                    return ResponseHelper::error('Thanh toán đã được xác nhận, không thể cập nhật', 400);
                }

                $existingPayment->update([
                    'amount' => $feePerPerson,
                    'status' => MiniParticipantPayment::STATUS_PAID,
                    'receipt_image' => $data['receipt_image'],
                    'note' => $data['note'] ?? null,
                    'paid_at' => now(),
                    'admin_note' => null,
                    'confirmed_at' => null,
                    'confirmed_by' => null,
                ]);

                $payment = $existingPayment;
            } else {
                // Tạo payment mới
                $payment = MiniParticipantPayment::create([
                    'mini_tournament_id' => $miniTournamentId,
                    'participant_id' => $data['participant_id'],
                    'user_id' => $userId,
                    'amount' => $feePerPerson,
                    'status' => MiniParticipantPayment::STATUS_PAID,
                    'receipt_image' => $data['receipt_image'],
                    'note' => $data['note'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            DB::commit();

            return ResponseHelper::success(
                new MiniParticipantPaymentResource($payment->load(['user', 'confirmer'])),
                'Thanh toán thành công, chờ chủ kèo xác nhận',
                200
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Xác nhận hoặc từ chối thanh toán của thành viên
     * API: POST /api/mini-tournaments/{id}/payments/{paymentId}/confirm
     * API: POST /api/mini-tournaments/{id}/payments/{paymentId}/reject
     */
    public function confirm(Request $request, $miniTournamentId, $paymentId)
    {
        return $this->processConfirmation($request, $miniTournamentId, $paymentId, true);
    }

    public function reject(Request $request, $miniTournamentId, $paymentId)
    {
        return $this->processConfirmation($request, $miniTournamentId, $paymentId, false);
    }

    private function processConfirmation(Request $request, $miniTournamentId, $paymentId, bool $isConfirm)
    {
        $data = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        // Kiểm tra quyền organizer
        if (!$miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền xác nhận thanh toán', 403);
        }

        $payment = MiniParticipantPayment::where('id', $paymentId)
            ->where('mini_tournament_id', $miniTournamentId)
            ->first();

        if (!$payment) {
            return ResponseHelper::error('Không tìm thấy thanh toán', 404);
        }

        if ($payment->status !== MiniParticipantPayment::STATUS_PAID) {
            return ResponseHelper::error('Thanh toán đang ở trạng thái không thể xác nhận', 400);
        }

        DB::beginTransaction();
        try {
            $newStatus = $isConfirm ? MiniParticipantPayment::STATUS_CONFIRMED : MiniParticipantPayment::STATUS_REJECTED;
            
            $payment->update([
                'status' => $newStatus,
                'admin_note' => $data['admin_note'] ?? null,
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
            ]);

            // Gửi notification cho thành viên
            $payment->load('user');
            if ($payment->user) {
                if ($isConfirm) {
                    $payment->user->notify(new PaymentConfirmedNotification($payment));
                } else {
                    $payment->user->notify(new PaymentRejectedNotification($payment));
                }
            }

            DB::commit();

            $message = $isConfirm ? 'Xác nhận thanh toán thành công' : 'Từ chối thanh toán thành công';
            
            return ResponseHelper::success(
                new MiniParticipantPaymentResource($payment->load(['user', 'confirmer'])),
                $message
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Nhắc thành viên đóng phí
     * API: POST /api/mini-tournaments/{id}/payments/remind/{participantId}
     */
    public function remind(Request $request, $miniTournamentId, $participantId)
    {
        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        // Kiểm tra quyền organizer
        if (!$miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền nhắc nhở thanh toán', 403);
        }

        // Kiểm tra kèo có thu phí không
        if (!$miniTournament->has_fee) {
            return ResponseHelper::error('Kèo này không thu phí tham gia', 400);
        }

        // Nếu kèo chia tiền tự động thì chỉ được nhắc thanh toán sau khi đã có ít nhất 1 trận hoàn tất
        if ($miniTournament->auto_split_fee) {
            $hasCompletedMatch = MiniMatch::where('mini_tournament_id', $miniTournament->id)
                ->where('status', MiniMatch::STATUS_COMPLETED)
                ->exists();

            if (!$hasCompletedMatch) {
                return ResponseHelper::error(
                    'Kèo đang cài đặt chia tiền tự động, chỉ được nhắc thanh toán sau khi trận đấu đã kết thúc',
                    400
                );
            }
        }

        $participant = MiniParticipant::where('id', $participantId)
            ->where('mini_tournament_id', $miniTournamentId)
            ->first();

        if (!$participant) {
            return ResponseHelper::error('Không tìm thấy thành viên', 404);
        }

        // Kiểm tra xem đã thanh toán chưa
        $payment = MiniParticipantPayment::where('mini_tournament_id', $miniTournamentId)
            ->where('participant_id', $participantId)
            ->where('status', MiniParticipantPayment::STATUS_CONFIRMED)
            ->first();

        if ($payment) {
            return ResponseHelper::error('Thành viên này đã thanh toán rồi', 400);
        }

        // Gửi notification nhắc nhở
        $participant->load('user');
        if ($participant->user) {
            $participant->user->notify(new PaymentReminderNotification($miniTournament, $participant));
        }

        return ResponseHelper::success(null, 'Đã gửi nhắc nhở thanh toán cho thành viên');
    }

    /**
     * Nhắc tất cả thành viên chưa thanh toán
     * API: POST /api/mini-tournaments/{id}/payments/remind-all
     */
    public function remindAll(Request $request, $miniTournamentId)
    {
        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        // Kiểm tra quyền organizer
        if (!$miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền nhắc nhở thanh toán', 403);
        }

        // Kiểm tra kèo có thu phí không
        if (!$miniTournament->has_fee) {
            return ResponseHelper::error('Kèo này không thu phí tham gia', 400);
        }

        // Nếu kèo chia tiền tự động thì chỉ được nhắc thanh toán sau khi đã có ít nhất 1 trận hoàn tất
        if ($miniTournament->auto_split_fee) {
            $hasCompletedMatch = MiniMatch::where('mini_tournament_id', $miniTournament->id)
                ->where('status', MiniMatch::STATUS_COMPLETED)
                ->exists();

            if (!$hasCompletedMatch) {
                return ResponseHelper::error(
                    'Kèo đang cài đặt chia tiền tự động, chỉ được nhắc thanh toán sau khi trận đấu đã kết thúc',
                    400
                );
            }
        }

        // Lấy danh sách thành viên chưa thanh toán
        $participants = MiniParticipant::where('mini_tournament_id', $miniTournamentId)
            ->where('is_confirmed', true)
            ->get();

        $remindedCount = 0;
        
        foreach ($participants as $participant) {
            // Kiểm tra đã thanh toán chưa
            $payment = MiniParticipantPayment::where('mini_tournament_id', $miniTournamentId)
                ->where('participant_id', $participant->id)
                ->where('status', MiniParticipantPayment::STATUS_CONFIRMED)
                ->first();

            if (!$payment && $participant->user) {
                $participant->user->notify(new PaymentReminderNotification($miniTournament, $participant));
                $remindedCount++;
            }
        }

        return ResponseHelper::success([
            'reminded_count' => $remindedCount,
        ], "Đã gửi nhắc nhở cho {$remindedCount} thành viên");
    }

    /**
     * Lấy trạng thái thanh toán của user hiện tại
     * API: GET /api/mini-tournaments/{id}/my-payment
     */
    public function myPayment(Request $request, $miniTournamentId)
    {
        $miniTournament = MiniTournament::findOrFail($miniTournamentId);
        $userId = Auth::id();

        // Tìm participant của user hiện tại
        $participant = MiniParticipant::where('mini_tournament_id', $miniTournamentId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return ResponseHelper::error('Bạn chưa tham gia kèo này', 404);
        }

        // Lấy payment
        $payment = MiniParticipantPayment::with(['confirmer'])
            ->where('mini_tournament_id', $miniTournamentId)
            ->where('participant_id', $participant->id)
            ->first();

        // Tính số tiền phải đóng
        $feePerPerson = 0;
        if ($miniTournament->has_fee) {
            $participantCount = $miniTournament->participants()->count();
            if ($miniTournament->auto_split_fee) {
                $feePerPerson = $participantCount > 0 ? round($miniTournament->fee_amount / $participantCount) : 0;
            } else {
                $feePerPerson = $miniTournament->fee_amount;
            }
        }

        $data = [
            'participant_id' => $participant->id,
            'has_fee' => $miniTournament->has_fee,
            'fee_per_person' => $feePerPerson,
            'qr_code_url' => $miniTournament->qr_code_url,
            'fee_description' => $miniTournament->fee_description,
            'payment' => $payment ? new MiniParticipantPaymentResource($payment) : null,
        ];

        return ResponseHelper::success($data, 'Lấy thông tin thanh toán thành công');
    }
}
