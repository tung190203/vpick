<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubActivityParticipantController extends Controller
{
    public function index(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);

        $validated = $request->validate([
            'status' => 'sometimes|in:invited,accepted,declined,attended,absent',
        ]);

        $query = $activity->participants()->with('user');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $participants = $query->get();

        return ResponseHelper::success([
            'data' => $participants,
            'total' => $participants->count(),
            'read_count' => $participants->where('status', 'accepted')->count(),
            'unread_count' => $participants->where('status', 'invited')->count(),
        ], 'Lấy danh sách người tham gia thành công');
    }

    public function store(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền thêm người tham gia', 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'sometimes|in:invited,accepted',
        ]);

        if ($activity->participants()->where('user_id', $validated['user_id'])->exists()) {
            return ResponseHelper::error('Người này đã tham gia hoạt động', 409);
        }

        $participant = ClubActivityParticipant::create([
            'club_activity_id' => $activity->id,
            'user_id' => $validated['user_id'],
            'status' => $validated['status'] ?? 'invited',
        ]);

        $participant->load('user');

        return ResponseHelper::success($participant, 'Thêm người tham gia thành công', 201);
    }

    public function invite(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền mời', 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $invited = [];
        foreach ($validated['user_ids'] as $userId) {
            if (!$activity->participants()->where('user_id', $userId)->exists()) {
                $participant = ClubActivityParticipant::create([
                    'club_activity_id' => $activity->id,
                    'user_id' => $userId,
                    'status' => 'invited',
                ]);
                $participant->load('user');
                $invited[] = $participant;
            }
        }

        return ResponseHelper::success([
            'invited_count' => count($invited),
            'data' => $invited,
        ], 'Đã mời thành công');
    }

    public function update(Request $request, $clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->findOrFail($participantId);

        $validated = $request->validate([
            'status' => 'required|in:invited,accepted,declined,attended,absent',
        ]);

        $participant->update(['status' => $validated['status']]);
        $participant->load('user');

        return ResponseHelper::success($participant, 'Cập nhật trạng thái thành công');
    }

    public function destroy($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->findOrFail($participantId);

        $userId = auth()->id();
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền xóa', 403);
        }

        $participant->delete();

        return ResponseHelper::success([], 'Xóa người tham gia thành công');
    }
}
