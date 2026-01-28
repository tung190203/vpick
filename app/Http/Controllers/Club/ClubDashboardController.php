<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubWalletTransactionStatus;
use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Http\Controllers\Controller;

class ClubDashboardController extends Controller
{
    public function index($clubId)
    {
        $club = Club::with([
            'members.user',
            'wallets',
            'fundCollections',
            'activities',
            'notifications',
        ])->findOrFail($clubId);

        $memberStats = [
            'total' => $club->members()->count(),
            'by_role' => [
                'admin' => $club->members()->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $club->members()->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $club->members()->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $club->members()->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $club->members()->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
        ];

        $mainWallet = $club->mainWallet;
        $financialStats = [
            'total_wallets' => $club->wallets()->count(),
            'main_wallet_balance' => $mainWallet ? $mainWallet->balance : 0,
            'pending_transactions' => $mainWallet ? $mainWallet->transactions()->where('status', ClubWalletTransactionStatus::Pending)->count() : 0,
            'active_collections' => $club->fundCollections()->where('status', ClubFundCollectionStatus::Active)->count(),
        ];

        $activityStats = [
            'total' => $club->activities()->count(),
            'scheduled' => $club->activities()->where('status', ClubActivityStatus::Scheduled)->count(),
            'ongoing' => $club->activities()->where('status', ClubActivityStatus::Ongoing)->count(),
            'completed' => $club->activities()->where('status', ClubActivityStatus::Completed)->count(),
        ];

        $notificationStats = [
            'total' => $club->notifications()->count(),
            'pinned' => $club->notifications()->where('is_pinned', true)->count(),
            'unread' => $club->notifications()
                ->whereHas('recipients', function ($q) {
                    $q->where('user_id', auth()->id())
                      ->where('is_read', false);
                })
                ->count(),
        ];

        $recentActivities = $club->activities()
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        $recentNotifications = $club->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return ResponseHelper::success([
            'club' => $club,
            'statistics' => [
                'members' => $memberStats,
                'financial' => $financialStats,
                'activities' => $activityStats,
                'notifications' => $notificationStats,
            ],
            'recent_activities' => $recentActivities,
            'recent_notifications' => $recentNotifications,
        ], 'Lấy dashboard thành công');
    }
}
