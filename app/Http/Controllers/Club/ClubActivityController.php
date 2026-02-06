<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\CancelActivityRequest;
use App\Http\Requests\Club\GetActivitiesRequest;
use App\Http\Requests\Club\StoreActivityRequest;
use App\Http\Requests\Club\UpdateActivityRequest;
use App\Http\Resources\Club\ClubActivityListResource;
use App\Http\Resources\Club\ClubActivityResource;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\User;
use App\Services\Club\ClubActivityService;

class ClubActivityController extends Controller
{
    private const ACTIVITY_COLLECTED_SUM = 'activityFeeTransactions as collected_amount';

    public function __construct(
        protected ClubActivityService $activityService
    ) {
    }

    public function index(GetActivitiesRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $activities = $this->activityService->getActivities($club, $request->validated(), $userId);

        $data = ['activities' => ClubActivityListResource::collection($activities)];
        $meta = [
            'current_page' => $activities->currentPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách hoạt động thành công', 200, $meta);
    }

    public function store(StoreActivityRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $activity = $this->activityService->createActivity($club, $request->validated(), $userId);

            $activity->load([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ]);
            $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

            return ResponseHelper::success(new ClubActivityResource($activity), 'Tạo hoạt động thành công', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function show($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)
            ->with([
                'creator' => User::FULL_RELATIONS,
                'club',
                'participants' => function ($query) {
                    $query->where('status', 'accepted')
                        ->with('user', User::FULL_RELATIONS);
                },
                'miniTournament'
            ])
            ->withSum(self::ACTIVITY_COLLECTED_SUM, 'amount')
            ->findOrFail($activityId);

        return ResponseHelper::success(new ClubActivityResource($activity), 'Lấy thông tin hoạt động thành công');
    }

    public function update(UpdateActivityRequest $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $activity = $this->activityService->updateActivity($activity, $request->validated(), $userId);

            $activity->load([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ]);
            $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

            return ResponseHelper::success(new ClubActivityResource($activity), 'Cập nhật hoạt động thành công');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function destroy($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $this->activityService->deleteActivity($activity, $userId);
            return ResponseHelper::success('Xóa hoạt động thành công');
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'scheduled') ? 422 : 403;
            return ResponseHelper::error($e->getMessage(), $statusCode);
        }
    }

    public function complete($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $activity = $this->activityService->completeActivity($activity, $userId);

            $activity->load([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ]);
            $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

            return ResponseHelper::success(new ClubActivityResource($activity), 'Hoạt động đã được đánh dấu hoàn thành');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function cancel(CancelActivityRequest $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $activity = $this->activityService->cancelActivity(
                $activity,
                $userId,
                $request->input('cancellation_reason'),
                $request->input('cancel_transactions')
            );

            $activity->load([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ]);
            $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

            return ResponseHelper::success(new ClubActivityResource($activity), 'Sự kiện đã được hủy');
        } catch (\Exception $e) {
            $statusCode = 403;
            if (str_contains($e->getMessage(), 'scheduled')) {
                $statusCode = 422;
            } elseif (str_contains($e->getMessage(), 'ví chính')) {
                $statusCode = 404;
            }
            return ResponseHelper::error($e->getMessage(), $statusCode);
        }
    }
}
