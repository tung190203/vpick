<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use App\Http\Resources\MiniParticipantResource;
use App\Models\MiniTournamentStaff;
use App\Models\User;
use App\Notifications\MiniTournamentCreatorInvitationNotification;
use App\Notifications\MiniTournamentJoinConfirmedNotification;
use App\Notifications\MiniTournamentJoinRequestNotification;
use App\Notifications\MiniTournamentRemovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniParticipantController extends Controller
{
    /**
     * Danh sách người tham gia 1 mini tournament.
     * - Filter theo is_confirmed (0/1), type (user|team)
     * - Hỗ trợ phân trang
     */
    public function index(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'is_confirmed' => 'nullable|boolean',
            'type' => 'nullable|in:user,team',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = MiniParticipant::query()
            ->where('mini_tournament_id', $tournamentId)
            ->with(['user', 'team']);

        if ($request->filled('is_confirmed')) {
            $query->where('is_confirmed', $validated['is_confirmed']);
        }

        if ($request->filled('type')) {
            $query->where('type', $validated['type']);
        }

        $participants = $query->paginate($validated['per_page'] ?? MiniParticipant::PER_PAGE);

        return ResponseHelper::success(MiniParticipantResource::collection($participants));
    }

    /**
     * Người dùng (hoặc team) tự JOIN vào mini tournament.
     * - Check max_players
     * - Nếu auto_approve = true -> is_confirmed = true
     * - Nếu auto_approve = false hoặc is_private = true -> chờ duyệt
     */
    public function join(Request $request, $tournamentId)
    {
        $miniTournament = MiniTournament::findOrFail($tournamentId);

        // Check số lượng slot
        if ($miniTournament->max_players && $miniTournament->participants()->count() >= $miniTournament->max_players) {
            return ResponseHelper::error('Đã đạt số lượng người chơi tối đa.', 400);
        }

        // Cho phép join bằng user hoặc team
        $validated = $request->validate([
            'type' => 'nullable|in:user,team',
            'team_id' => 'nullable|exists:mini_teams,id',
        ]);

        $type = $validated['type'] ?? 'user';

        // Kiểm tra đã join chưa
        $exists = MiniParticipant::where('mini_tournament_id', $miniTournament->id)
            ->where(function ($q) use ($type, $validated) {
                if ($type === 'user') {
                    $q->where('user_id', Auth::id());
                } else {
                    $q->where('team_id', $validated['team_id'] ?? null);
                }
            })
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Bạn đã tham gia giải đấu này rồi.', 400);
        }

        // Tạo participant mới
        $participant = MiniParticipant::create([
            'mini_tournament_id' => $miniTournament->id,
            'type' => $type,
            'user_id' => $type === 'user' ? Auth::id() : null,
            'team_id' => $type === 'team' ? ($validated['team_id'] ?? null) : null,
            'is_confirmed' => $miniTournament->auto_approve && !$miniTournament->is_private,
        ]);

        if (! $participant->is_confirmed) {
            $organizers = $miniTournament->staff()
                ->wherePivot('role', MiniTournamentStaff::ROLE_ORGANIZER)
                ->get();
        
            foreach ($organizers as $organizer) {
                if ($organizer->id !== Auth::id()) {
                    $organizer->notify(new MiniTournamentJoinRequestNotification($participant));
                }
            }
        }        

        return ResponseHelper::success(new MiniParticipantResource($participant), 'Tham gia giải đấu thành công.', 201);
    }

    /**
     * Chủ giải duyệt một participant.
     * - Chỉ creator mới được duyệt
     * - Set is_confirmed = true
     */
    public function confirm($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);
        $miniTournamentWithStaff = $participant->miniTournament->load('staff');
        $isOrganizer = $miniTournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền duyệt người tham gia này.', 403);
        }

        // Nếu đã confirm trước đó
        if ($participant->is_confirmed) {
            return ResponseHelper::success(new MiniParticipantResource($participant), 'Người tham gia đã được duyệt trước đó.', 200);
        }

        $participant->is_confirmed = true;
        $participant->save();

        $participant->user->notify(new MiniTournamentJoinConfirmedNotification($participant));

        return ResponseHelper::success(new MiniParticipantResource($participant), 'Người tham gia đã được duyệt thành công.', 200);
    }

    /**
     * Người được mời vào giải -> bấm chấp nhận lời mời.
     * - Chỉ user đó mới accept
     * - Check max_players trước khi confirm
     */
    public function acceptInvite($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);

        $miniTournamentWithStaff = $participant->miniTournament->load('staff');
        $isOrganizer = $miniTournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền duyệt người tham gia này.', 403);
        }

        if ($participant->is_confirmed) {
            return ResponseHelper::success(new MiniParticipantResource($participant), 'Bạn đã chấp nhận lời mời trước đó.', 200);
        }

        $miniTournament = MiniTournament::findOrFail($participant->mini_tournament_id);

        if ($miniTournament->max_players && $miniTournament->participants()->where('is_confirmed', true)->count() >= $miniTournament->max_players) {
            return ResponseHelper::error('Giải đấu đã đạt số lượng người chơi tối đa.', 400);
        }

        $participant->is_confirmed = true;
        $participant->save();

        return ResponseHelper::success(new MiniParticipantResource($participant), 'Bạn đã chấp nhận lời mời thành công.', 200);
    }

    /**
     * Creator mời user hoặc team vào giải đấu.
     * - Chỉ creator mới có quyền mời
     * - Check max_players
     * - Nếu auto_approve = true và is_private = false -> is_confirmed = true
     */
    public function invite(Request $request, $tournamentId)
    {
        $miniTournament = MiniTournament::with(['staff', 'participants'])->findOrFail($tournamentId);
        $userId = Auth::id();

        $isOrganizer = $miniTournament->hasOrganizer($userId);
        $canAddFriends = $miniTournament->allow_participant_add_friends
            && $miniTournament->participants()
                ->where('user_id', $userId)
                ->where('is_confirmed', true)
                ->exists();

        if (!$isOrganizer && !$canAddFriends) {
            return ResponseHelper::error('Bạn không có quyền mời người tham gia.', 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:user,team',
            'user_id' => 'required_if:type,user|exists:users,id',
            'team_id' => 'required_if:type,team|exists:teams,id',
        ]);

        $type = $validated['type'];
        $userIdToInvite = $validated['user_id'] ?? null;
        $teamIdToInvite = $validated['team_id'] ?? null;

        $exists = $miniTournament->participants()
            ->when($type === 'user', fn($q) => $q->where('user_id', $userIdToInvite))
            ->when($type === 'team', fn($q) => $q->where('team_id', $teamIdToInvite))
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Người tham gia này đã được mời hoặc tham gia rồi.', 400);
        }

        if (
            $miniTournament->max_players
            && $miniTournament->participants()->where('is_confirmed', true)->count() >= $miniTournament->max_players
        ) {
            return ResponseHelper::error('Đã đạt số lượng người chơi tối đa.', 400);
        }

        $participant = $miniTournament->participants()->create([
            'type' => $type,
            'user_id' => $userIdToInvite,
            'team_id' => $teamIdToInvite,
            'is_confirmed' => $miniTournament->auto_approve && !$miniTournament->is_private,
        ]);

        if ($type === 'user' && $userIdToInvite) {
            $user = User::find($userIdToInvite);
            if ($user) {
                $user->notify(new MiniTournamentCreatorInvitationNotification($participant));
            }
        }

        return ResponseHelper::success(new MiniParticipantResource($participant), 'Người tham gia đã được mời thành công.', 201);
    }
    /**
     * Xóa một participant khỏi mini tournament.
     * - Chỉ creator mới có quyền xóa
     */

    public function delete($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);

        $miniTournamentWithStaff = $participant->miniTournament->load('staff');
        $isOrganizer = $miniTournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xóa người tham gia này.', 403);
        }

        $participant->delete();

        if ($participant->type === 'user' && $participant->user_id) {
            $user = User::find($participant->user_id);
            if ($user) {
                $user->notify(new MiniTournamentRemovedNotification($participant));
            }
        }

        return ResponseHelper::success(null, 'Người tham gia đã được xóa thành công.', 200);
    }
}
