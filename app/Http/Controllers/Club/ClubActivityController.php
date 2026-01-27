<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubActivityController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:meeting,practice,match,tournament,event,other',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = $club->activities()->with(['creator', 'participants.user']);

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('start_time', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('start_time', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $activities = $query->orderBy('start_time', 'desc')->paginate($perPage);

        return ResponseHelper::success([
            'data' => $activities->items(),
            'current_page' => $activities->currentPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ], 'Lấy danh sách hoạt động thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền tạo hoạt động', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:meeting,practice,match,tournament,event,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'mini_tournament_id' => 'nullable|exists:mini_tournaments,id',
            'is_recurring' => 'sometimes|boolean',
            'recurring_schedule' => 'nullable|string',
            'reminder_minutes' => 'sometimes|integer|min:0',
        ]);

        $activity = ClubActivity::create([
            'club_id' => $club->id,
            'mini_tournament_id' => $validated['mini_tournament_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurring_schedule' => $validated['recurring_schedule'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'reminder_minutes' => $validated['reminder_minutes'] ?? 15,
            'status' => 'scheduled',
            'created_by' => $userId,
        ]);

        $activity->load(['creator', 'club']);

        return ResponseHelper::success($activity, 'Tạo hoạt động thành công', 201);
    }

    public function show($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)
            ->with(['creator', 'club', 'participants.user', 'miniTournament'])
            ->findOrFail($activityId);

        return ResponseHelper::success($activity, 'Lấy thông tin hoạt động thành công');
    }

    public function update(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary']) || $activity->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền cập nhật hoạt động này', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:meeting,practice,match,tournament,event,other',
            'start_time' => 'sometimes|date',
            'end_time' => 'nullable|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'is_recurring' => 'sometimes|boolean',
            'recurring_schedule' => 'nullable|string',
            'reminder_minutes' => 'sometimes|integer|min:0',
        ]);

        $activity->update($validated);
        $activity->load(['creator', 'club']);

        return ResponseHelper::success($activity, 'Cập nhật hoạt động thành công');
    }

    public function destroy($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary']) || $activity->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền xóa hoạt động này', 403);
        }

        if (!$activity->canBeCancelled()) {
            return ResponseHelper::error('Chỉ có thể xóa hoạt động đang scheduled', 422);
        }

        $activity->delete();

        return ResponseHelper::success([], 'Xóa hoạt động thành công');
    }

    public function complete($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền đánh dấu hoàn thành', 403);
        }

        $activity->markAsCompleted();

        return ResponseHelper::success($activity, 'Hoạt động đã được đánh dấu hoàn thành');
    }
}
