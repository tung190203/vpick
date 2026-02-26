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
use Illuminate\Support\Facades\Cache;

class ClubActivityController extends Controller
{
    private const ACTIVITY_COLLECTED_SUM = 'activityFeeTransactions as collected_amount';

    private const ACTIVITIES_CACHE_TTL = 60; // seconds

    public function __construct(
        protected ClubActivityService $activityService
    ) {
    }

    public function index(GetActivitiesRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();
        $filters = $request->validated();

        $version = (int) Cache::get('club_activities_version:' . $clubId, 0);
        $cacheKey = 'club_activities:' . $clubId . ':' . $version . ':' . md5(json_encode($filters) . ':' . ($userId ?? 'guest'));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        $activities = $this->activityService->getActivities($club, $filters, $userId);

        $data = ['activities' => ClubActivityListResource::collection($activities)];
        $meta = [
            'current_page' => $activities->currentPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ];

        $response = ResponseHelper::success($data, 'Lấy danh sách hoạt động thành công', 200, $meta);
        $responseData = $response->getData(true);
        Cache::put($cacheKey, $responseData, self::ACTIVITIES_CACHE_TTL);

        return $response;
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

            Cache::increment('club_activities_version:' . $clubId);

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
                'participants',
                'participants.user' => User::FULL_RELATIONS,
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

            Cache::increment('club_activities_version:' . $clubId);

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
            Cache::increment('club_activities_version:' . $clubId);
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

            Cache::increment('club_activities_version:' . $clubId);

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

            Cache::increment('club_activities_version:' . $clubId);

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
