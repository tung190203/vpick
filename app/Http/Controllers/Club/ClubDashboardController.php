<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubActivityStatus;
use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Enums\ClubWalletTransactionStatus;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubActivityResource;
use App\Http\Resources\Club\ClubNotificationResource;
use App\Http\Resources\ListClubResource;
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

        $joined = fn () => $club->members()->where('membership_status', ClubMembershipStatus::Joined);
        $memberStats = [
            'total' => $joined()->where('status', ClubMemberStatus::Active)->count(),
            'by_role' => [
                'admin' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => $club->members()->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => $club->members()->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => $club->members()->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => $club->members()->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => $club->members()->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
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
            ->with(['creator', 'participants.user'])
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        $recentNotifications = $club->notifications()
            ->with(['type', 'creator', 'recipients.user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return ResponseHelper::success([
            'club' => new ListClubResource($club),
            'statistics' => [
                'members' => $memberStats,
                'financial' => $financialStats,
                'activities' => $activityStats,
                'notifications' => $notificationStats,
            ],
            'recent_activities' => ClubActivityResource::collection($recentActivities),
            'recent_notifications' => ClubNotificationResource::collection($recentNotifications),
        ], 'Lấy dashboard thành công');
    }
}
