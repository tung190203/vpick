<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubActivityResource;
use App\Http\Resources\Club\ClubNotificationResource;
use App\Http\Resources\ListClubResource;
use App\Models\Club\Club;
use App\Services\Club\ClubDashboardService;

class ClubDashboardController extends Controller
{
    public function __construct(
        protected ClubDashboardService $dashboardService
    ) {
    }

    public function index($clubId)
    {
        $club = Club::with([
            'members',
            'wallets',
            'fundCollections',
            'activities',
            'notifications',
        ])->findOrFail($clubId);

        $userId = auth()->id();
        $dashboardData = $this->dashboardService->getDashboardData($club, $userId);

        return ResponseHelper::success([
            'club' => new ListClubResource($club),
            'statistics' => $dashboardData['statistics'],
            'recent_activities' => ClubActivityResource::collection($dashboardData['recent_activities']),
            'recent_notifications' => ClubNotificationResource::collection($dashboardData['recent_notifications']),
        ], 'Lấy dashboard thành công');
    }
}
