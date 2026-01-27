<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Club;
use App\Models\ClubMonthlyFeePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClubMonthlyFeePaymentController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'sometimes|in:pending,paid,failed',
            'period' => 'sometimes|date',
        ]);

        $query = ClubMonthlyFeePayment::where('club_id', $clubId)
            ->with(['user', 'monthlyFee', 'walletTransaction']);

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['period'])) {
            $query->where('period', $validated['period']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $payments = $query->orderBy('period', 'desc')->paginate($perPage);

        return ResponseHelper::success([
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
            'last_page' => $payments->lastPage(),
        ], 'Lấy danh sách thanh toán thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $validated = $request->validate([
            'club_monthly_fee_id' => 'required|exists:club_monthly_fees,id',
            'period' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,qr_code,other',
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Kiểm tra fee thuộc club
        $fee = $club->monthlyFees()->findOrFail($validated['club_monthly_fee_id']);

        // Kiểm tra đã thanh toán chưa
        $exists = ClubMonthlyFeePayment::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('period', $validated['period'])
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Đã thanh toán phí cho tháng này', 409);
        }

        return DB::transaction(function () use ($club, $validated, $userId) {
            // Tạo payment
            $payment = ClubMonthlyFeePayment::create([
                'club_id' => $club->id,
                'club_monthly_fee_id' => $validated['club_monthly_fee_id'],
                'user_id' => $userId,
                'period' => $validated['period'],
                'amount' => $validated['amount'],
                'status' => 'pending',
            ]);

            // Tạo wallet transaction
            $mainWallet = $club->mainWallet;
            if ($mainWallet) {
                $transaction = $mainWallet->transactions()->create([
                    'direction' => 'in',
                    'amount' => $validated['amount'],
                    'source_type' => 'monthly_fee',
                    'source_id' => $payment->id,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'pending',
                    'reference_code' => $validated['reference_code'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'created_by' => $userId,
                ]);

                $payment->update(['wallet_transaction_id' => $transaction->id]);
            }

            $payment->load(['user', 'monthlyFee', 'walletTransaction']);

            return ResponseHelper::success($payment, 'Thanh toán phí thành công', 201);
        });
    }

    public function show($clubId, $paymentId)
    {
        $payment = ClubMonthlyFeePayment::where('club_id', $clubId)
            ->with(['user', 'monthlyFee', 'walletTransaction', 'club'])
            ->findOrFail($paymentId);

        return ResponseHelper::success($payment, 'Lấy thông tin thanh toán thành công');
    }

    public function getMemberPayments(Request $request, $clubId, $memberId)
    {
        $club = Club::findOrFail($clubId);
        
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:pending,paid,failed',
        ]);

        $query = ClubMonthlyFeePayment::where('club_id', $clubId)
            ->where('user_id', $memberId)
            ->with(['monthlyFee', 'walletTransaction']);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['page'] ?? 15;
        $payments = $query->orderBy('period', 'desc')->paginate($perPage);

        return ResponseHelper::success([
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
            'last_page' => $payments->lastPage(),
        ], 'Lấy lịch sử thanh toán thành công');
    }

    public function getStatistics(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'period' => 'sometimes|date',
        ]);

        $query = ClubMonthlyFeePayment::where('club_id', $clubId);

        if (!empty($validated['period'])) {
            $query->where('period', $validated['period']);
        }

        $stats = [
            'total_payments' => $query->count(),
            'paid_count' => (clone $query)->where('status', 'paid')->count(),
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'failed_count' => (clone $query)->where('status', 'failed')->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'paid_amount' => (clone $query)->where('status', 'paid')->sum('amount'),
        ];

        return ResponseHelper::success($stats, 'Lấy thống kê thanh toán thành công');
    }
}
