<?php

namespace App\Http\Controllers\Club;

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
                'admin' => $club->members()->where('role', 'admin')->count(),
                'manager' => $club->members()->where('role', 'manager')->count(),
                'treasurer' => $club->members()->where('role', 'treasurer')->count(),
                'secretary' => $club->members()->where('role', 'secretary')->count(),
                'member' => $club->members()->where('role', 'member')->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', 'pending')->count(),
                'active' => $club->members()->where('status', 'active')->count(),
                'inactive' => $club->members()->where('status', 'inactive')->count(),
                'suspended' => $club->members()->where('status', 'suspended')->count(),
            ],
        ];

        $mainWallet = $club->mainWallet;
        $financialStats = [
            'total_wallets' => $club->wallets()->count(),
            'main_wallet_balance' => $mainWallet ? $mainWallet->balance : 0,
            'pending_transactions' => $mainWallet ? $mainWallet->transactions()->where('status', 'pending')->count() : 0,
            'active_collections' => $club->fundCollections()->where('status', 'active')->count(),
        ];

        $activityStats = [
            'total' => $club->activities()->count(),
            'scheduled' => $club->activities()->where('status', 'scheduled')->count(),
            'ongoing' => $club->activities()->where('status', 'ongoing')->count(),
            'completed' => $club->activities()->where('status', 'completed')->count(),
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
