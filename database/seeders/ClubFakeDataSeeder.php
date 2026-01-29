<?php

namespace Database\Seeders;

use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubActivityStatus;
use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMonthlyFeePaymentStatus;
use App\Enums\ClubNotificationPriority;
use App\Enums\ClubNotificationStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\ClubWalletType;
use App\Enums\PaymentMethod;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubExpense;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use App\Models\Club\ClubMonthlyFee;
use App\Models\Club\ClubMonthlyFeePayment;
use App\Models\Club\ClubNotification;
use App\Models\Club\ClubNotificationRecipient;
use App\Models\Club\ClubNotificationType;
use App\Models\Club\ClubProfile;
use App\Models\Club\ClubWallet;
use App\Models\Club\ClubWalletTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClubFakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNotificationTypes();
        $clubs = Club::with('activeMembers.user')->get();
        $users = User::limit(50)->get();

        if ($clubs->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($clubs as $club) {
            $members = $club->activeMembers->pluck('user')->filter();
            $memberIds = $members->pluck('id')->toArray();
            $creatorId = $memberIds[0] ?? $users->first()->id;

            $wallet = $this->ensureMainWallet($club);
            if ($wallet) {
                $this->seedWalletTransactions($club, $wallet, $creatorId, $memberIds);
                $this->seedFundCollections($club, $wallet, $creatorId, $memberIds);
                $this->seedMonthlyFeesAndPayments($club, $wallet, $creatorId, $memberIds);
                $this->seedExpenses($club, $wallet, $creatorId);
            }

            $this->seedNotifications($club, $creatorId, $memberIds);
            $this->seedActivities($club, $creatorId, $memberIds);
            $this->seedProfiles($club);
        }
    }

    protected function seedNotificationTypes(): void
    {
        if (ClubNotificationType::exists()) {
            return;
        }
        $this->call(ClubNotificationTypeSeeder::class);
    }

    protected function ensureMainWallet(Club $club): ?ClubWallet
    {
        return ClubWallet::firstOrCreate(
            [
                'club_id' => $club->id,
                'type' => ClubWalletType::Main,
            ],
            ['currency' => 'VND']
        );
    }

    protected function seedWalletTransactions(Club $club, ClubWallet $wallet, int $creatorId, array $memberIds): void
    {
        for ($i = 0; $i < rand(3, 8); $i++) {
            $direction = $i % 3 === 0 ? ClubWalletTransactionDirection::Out : ClubWalletTransactionDirection::In;
            ClubWalletTransaction::create([
                'club_wallet_id' => $wallet->id,
                'direction' => $direction,
                'amount' => rand(50, 500) * 1000,
                'source_type' => $direction === ClubWalletTransactionDirection::In
                    ? ClubWalletTransactionSourceType::MonthlyFee
                    : ClubWalletTransactionSourceType::Expense,
                'source_id' => null,
                'payment_method' => PaymentMethod::BankTransfer,
                'status' => ClubWalletTransactionStatus::Confirmed,
                'description' => $direction === ClubWalletTransactionDirection::In ? 'Thu phí tháng' : 'Chi mua dụng cụ',
                'created_by' => $creatorId,
                'confirmed_by' => $creatorId,
                'confirmed_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }

    protected function seedFundCollections(Club $club, ClubWallet $wallet, int $creatorId, array $memberIds): void
    {
        $statuses = [ClubFundCollectionStatus::Pending, ClubFundCollectionStatus::Active, ClubFundCollectionStatus::Completed];
        for ($i = 0; $i < rand(1, 3); $i++) {
            $target = rand(5, 50) * 100000;
            $collection = ClubFundCollection::create([
                'club_id' => $club->id,
                'title' => 'Đợt thu quỹ ' . ($i + 1),
                'description' => 'Thu quỹ hoạt động CLB',
                'target_amount' => $target,
                'collected_amount' => 0,
                'currency' => 'VND',
                'start_date' => now()->subDays(rand(10, 60)),
                'end_date' => now()->addDays(rand(10, 90)),
                'status' => $statuses[$i % 3],
                'created_by' => $creatorId,
            ]);

            if ($collection->status !== ClubFundCollectionStatus::Pending && !empty($memberIds)) {
                $amount = (int) ($target * 0.2 / max(1, count($memberIds)));
                foreach (array_slice($memberIds, 0, rand(2, 4)) as $uid) {
                    $contrib = ClubFundContribution::create([
                        'club_fund_collection_id' => $collection->id,
                        'user_id' => $uid,
                        'amount' => $amount,
                        'status' => ClubFundContributionStatus::Confirmed,
                    ]);
                    $tx = $wallet->transactions()->create([
                        'direction' => ClubWalletTransactionDirection::In,
                        'amount' => $amount,
                        'source_type' => ClubWalletTransactionSourceType::FundCollection,
                        'source_id' => $contrib->id,
                        'payment_method' => PaymentMethod::BankTransfer,
                        'status' => ClubWalletTransactionStatus::Confirmed,
                        'created_by' => $uid,
                        'confirmed_by' => $creatorId,
                        'confirmed_at' => now(),
                    ]);
                    $contrib->update(['wallet_transaction_id' => $tx->id]);
                }
                $collection->update(['collected_amount' => $collection->contributions()->sum('amount')]);
            }
        }
    }

    protected function seedMonthlyFeesAndPayments(Club $club, ClubWallet $wallet, int $creatorId, array $memberIds): void
    {
        $fee = ClubMonthlyFee::firstOrCreate(
            ['club_id' => $club->id, 'due_day' => 15],
            ['amount' => 100000, 'currency' => 'VND', 'is_active' => true]
        );

        foreach (array_slice($memberIds, 0, rand(2, 5)) as $uid) {
            $period = now()->subMonths(rand(0, 3))->startOfMonth();
            ClubMonthlyFeePayment::firstOrCreate(
                [
                    'club_id' => $club->id,
                    'user_id' => $uid,
                    'period' => $period,
                ],
                [
                    'club_monthly_fee_id' => $fee->id,
                    'amount' => $fee->amount,
                    'status' => rand(0, 10) > 2 ? ClubMonthlyFeePaymentStatus::Paid : ClubMonthlyFeePaymentStatus::Pending,
                    'paid_at' => rand(0, 10) > 2 ? now()->subDays(rand(1, 30)) : null,
                ]
            );
        }
    }

    protected function seedExpenses(Club $club, ClubWallet $wallet, int $creatorId): void
    {
        $titles = ['Mua bóng', 'Thuê sân', 'Nước uống', 'Dụng cụ tập'];
        for ($i = 0; $i < rand(2, 5); $i++) {
            $amount = rand(2, 20) * 100000;
            $spentAt = now()->subDays(rand(1, 90));
            $expense = ClubExpense::create([
                'club_id' => $club->id,
                'title' => $titles[array_rand($titles)],
                'amount' => $amount,
                'spent_by' => $creatorId,
                'spent_at' => $spentAt,
                'note' => 'Chi phí hoạt động',
            ]);
            $tx = $wallet->transactions()->create([
                'direction' => ClubWalletTransactionDirection::Out,
                'amount' => $amount,
                'source_type' => ClubWalletTransactionSourceType::Expense,
                'source_id' => $expense->id,
                'payment_method' => PaymentMethod::Cash,
                'status' => ClubWalletTransactionStatus::Confirmed,
                'created_by' => $creatorId,
                'confirmed_by' => $creatorId,
                'confirmed_at' => $spentAt,
            ]);
            $expense->update(['wallet_transaction_id' => $tx->id]);
        }
    }

    protected function seedNotifications(Club $club, int $creatorId, array $memberIds): void
    {
        $typeIds = ClubNotificationType::where('is_active', true)->pluck('id');
        if ($typeIds->isEmpty()) {
            return;
        }
        for ($i = 0; $i < rand(2, 5); $i++) {
            $notif = ClubNotification::create([
                'club_id' => $club->id,
                'club_notification_type_id' => $typeIds->random(),
                'title' => 'Thông báo CLB ' . ($i + 1),
                'content' => 'Nội dung thông báo mẫu cho CLB.',
                'priority' => ClubNotificationPriority::Normal,
                'status' => $i === 0 ? ClubNotificationStatus::Draft : ClubNotificationStatus::Sent,
                'is_pinned' => $i === 1,
                'created_by' => $creatorId,
                'sent_at' => $i === 0 ? null : now()->subDays(rand(1, 30)),
            ]);
            foreach (array_slice($memberIds, 0, rand(2, 5)) as $uid) {
                ClubNotificationRecipient::create([
                    'club_notification_id' => $notif->id,
                    'user_id' => $uid,
                    'is_read' => (bool) rand(0, 1),
                    'read_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                ]);
            }
        }
    }

    protected function seedActivities(Club $club, int $creatorId, array $memberIds): void
    {
        $types = ['meeting', 'practice', 'match', 'event'];
        for ($i = 0; $i < rand(2, 4); $i++) {
            $start = now()->addDays(rand(-30, 60))->setHour(18)->setMinute(0)->setSecond(0);
            $activity = ClubActivity::create([
                'club_id' => $club->id,
                'title' => 'Hoạt động ' . $types[array_rand($types)] . ' ' . ($i + 1),
                'description' => 'Mô tả hoạt động.',
                'type' => $types[array_rand($types)],
                'start_time' => $start,
                'end_time' => $start->copy()->addHours(2),
                'location' => 'Sân tập CLB',
                'reminder_minutes' => 15,
                'fee_amount' => rand(0, 1) ? 50000 : 0,
                'status' => $start->isPast() ? ClubActivityStatus::Completed : ClubActivityStatus::Scheduled,
                'created_by' => $creatorId,
            ]);
            foreach (array_slice($memberIds, 0, rand(2, 5)) as $uid) {
                ClubActivityParticipant::create([
                    'club_activity_id' => $activity->id,
                    'user_id' => $uid,
                    'status' => rand(0, 10) > 2 ? ClubActivityParticipantStatus::Accepted : ClubActivityParticipantStatus::Invited,
                ]);
            }
        }
    }

    protected function seedProfiles(Club $club): void
    {
        if (ClubProfile::where('club_id', $club->id)->exists()) {
            return;
        }
        ClubProfile::create([
            'club_id' => $club->id,
            'description' => 'Câu lạc bộ ' . $club->name . ' - mô tả giới thiệu.',
            'phone' => '0' . rand(900000000, 999999999),
            'email' => 'club' . $club->id . '@example.com',
            'address' => $club->address ?? 'Hà Nội',
        ]);
    }
}
