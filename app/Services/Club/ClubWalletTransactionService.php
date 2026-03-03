<?php

namespace App\Services\Club;

use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWallet;
use App\Models\Club\ClubWalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClubWalletTransactionService
{
    public function getTransactions(Club $club, array $filters): LengthAwarePaginator
    {
        $query = ClubWalletTransaction::whereHas('wallet', function ($q) use ($club) {
            $q->where('club_id', $club->id);
        })->with(['wallet', 'creator', 'confirmer'])
            ->where(function ($q) {
            $q->where('included_in_club_fund', true)->orWhereNull('included_in_club_fund');
        });

        if (!empty($filters['wallet_id'])) {
            $query->where('club_wallet_id', $filters['wallet_id']);
        }

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['source_types'])) {
            $query->whereIn('source_type', $filters['source_types']);
        }

        if (!empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', '%' . $term . '%')
                    ->orWhere('reference_code', 'like', '%' . $term . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getMyTransactions(Club $club, int $userId, array $filters): LengthAwarePaginator
    {
        $query = ClubWalletTransaction::whereHas('wallet', function ($q) use ($club) {
            $q->where('club_id', $club->id);
        })
            ->where('created_by', $userId)
            ->where(function ($q) {
                $q->where('included_in_club_fund', true)->orWhereNull('included_in_club_fund');
            })
            ->with(['wallet', 'creator', 'confirmer']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', '%' . $term . '%')
                    ->orWhere('reference_code', 'like', '%' . $term . '%');
            });
        }

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['source_types'])) {
            $query->whereIn('source_type', $filters['source_types']);
        }

        if (!empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createTransaction(ClubWallet $wallet, array $data, int $creatorId): ClubWalletTransaction
    {
        return ClubWalletTransaction::create([
            'club_wallet_id' => $wallet->id,
            'direction' => $data['direction'],
            'amount' => $data['amount'],
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'payment_method' => $data['payment_method'],
            'status' => ClubWalletTransactionStatus::Pending,
            'reference_code' => $data['reference_code'] ?? null,
            'description' => $data['description'] ?? null,
            'included_in_club_fund' => $data['included_in_club_fund'] ?? true,
            'created_by' => $creatorId,
        ]);
    }

    /**
     * Tạo nhiều giao dịch thu (In) cho nhiều participant trong 1 request.
     * - fixed: amount = số tiền mỗi người
     * - equal: amount = tổng tiền, chia đều cho count(participant_ids)
     *
     * @return Collection<int, ClubWalletTransaction>
     */
    public function createBatchTransactions(
        ClubWallet $wallet,
        ClubActivity $activity,
        array $participantIds,
        string $collectionType,
        float $amount,
        array $baseData
    ): Collection {
        $participants = ClubActivityParticipant::whereIn('id', $participantIds)
            ->where('club_activity_id', $activity->id)
            ->whereIn('status', [ClubActivityParticipantStatus::Accepted, ClubActivityParticipantStatus::Attended])
            ->get();

        if ($participants->count() !== count($participantIds)) {
            throw new \InvalidArgumentException('Một hoặc nhiều participant không hợp lệ (chỉ chấp nhận accepted/attended)');
        }

        $amountPerPerson = $collectionType === 'equal'
            ? round($amount / count($participantIds), 2)
            : $amount;

        $transactions = collect();
        foreach ($participants as $participant) {
            $tx = $this->createTransaction($wallet, array_merge($baseData, [
                'direction' => ClubWalletTransactionDirection::In,
                'amount' => $amountPerPerson,
                'source_type' => ClubWalletTransactionSourceType::Activity,
                'source_id' => $activity->id,
            ]), $participant->user_id);
            ClubActivityParticipant::where('id', $participant->id)->update(['wallet_transaction_id' => $tx->id]);
            $transactions->push($tx);
        }

        return $transactions;
    }

    public function updateTransaction(ClubWalletTransaction $transaction, array $data): ClubWalletTransaction
    {
        if ($transaction->status !== ClubWalletTransactionStatus::Pending) {
            throw new \Exception('Chỉ có thể cập nhật giao dịch đang pending');
        }

        $transaction->update($data);
        return $transaction;
    }

    public function confirmTransaction(ClubWalletTransaction $transaction, int $confirmerId): ClubWalletTransaction
    {
        if ($transaction->status !== ClubWalletTransactionStatus::Pending) {
            throw new \Exception('Chỉ có thể xác nhận giao dịch đang pending');
        }

        $transaction->confirm($confirmerId);
        return $transaction;
    }

    public function rejectTransaction(ClubWalletTransaction $transaction, int $rejecterId): ClubWalletTransaction
    {
        if ($transaction->status !== ClubWalletTransactionStatus::Pending) {
            throw new \Exception('Chỉ có thể từ chối giao dịch đang pending');
        }

        $transaction->reject($rejecterId);
        return $transaction;
    }
}
