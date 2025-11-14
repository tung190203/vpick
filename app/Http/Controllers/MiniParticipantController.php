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
            'per_page' => 'nullable|integer|min:1|max:200',
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

        $data = [
            'participants' => MiniParticipantResource::collection($participants),
        ];

        $meta = [
            'current_page' => $participants->currentPage(),
            'last_page' => $participants->lastPage(),
            'per_page' => $participants->perPage(),
            'total' => $participants->total(),
        ];

        return ResponseHelper::success($data, 'Danh sách người tham gia mini tournament.', 200, $meta);
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

        if (!$participant->is_confirmed) {
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

        if ($participant->is_confirmed) {
            return ResponseHelper::success(new MiniParticipantResource($participant), 'Bạn đã chấp nhận lời mời trước đó.', 200);
        }

        $miniTournament = $participant->miniTournament;
        if ($miniTournament && $miniTournament->max_players) {
            $confirmedCount = $miniTournament->participants()->where('is_confirmed', true)->count();
            if ($confirmedCount >= $miniTournament->max_players) {
                return ResponseHelper::error('Giải đấu đã đạt số lượng người chơi tối đa.', 400);
            }
        }

        $participant->is_confirmed = true;
        $participant->save();

        return ResponseHelper::success(new MiniParticipantResource($participant), 'Bạn đã chấp nhận lời mời thành công.', 200);
    }

    public function declineInvite($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);
        $participant->delete();

        return ResponseHelper::success(null, 'Bạn đã từ chối lời mời thành công.', 200);
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
            'is_confirmed' => false
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
     * Lấy danh sách ứng viên để mời vào giải đấu.
     * - scope: club, friends, area
     * - Nếu club -> cần truyền thêm club_id
     */
    public function getCandidates(Request $request, $tournamentId)
    {
        $miniTournament = MiniTournament::with('participants')->findOrFail($tournamentId);
        $user = Auth::user();
    
        $validated = $request->validate([
            'scope' => 'required|in:club,friends,area',
            'club_id' => 'required_if:scope,club|exists:clubs,id',
            'per_page' => 'sometimes|integer|min:1|max:200',
        ]);
    
        $perPage = $validated['per_page'] ?? 20;
        $scope = $validated['scope'];
    
        switch ($scope) {
            case 'club':
                $query = User::whereHas('clubs', fn($q) => $q->where('clubs.id', $validated['club_id']));
                break;
    
            case 'friends':
                $query = $user->friends();
                break;
    
            case 'area':
                $query = User::where('location_id', $user->location_id)
                    ->where('id', '!=', $user->id);
                break;

            default:
                $query = User::query()->whereRaw('0 = 1');
        }
    
        // Phân trang
        $paginated = $query->paginate($perPage);
    
        $participantUserIds = $miniTournament->participants->pluck('user_id')->toArray();
    
        $candidates = $paginated->getCollection()->map(function ($u) use ($user, $participantUserIds) {
            $visibility = 'open';
            if ($u->invite_mode === 'friend_only') {
                $visibility = 'friend_only';
            } elseif ($u->invite_mode === 'private') {
                $visibility = 'private';
            }
    
            return [
                'id' => $u->id,
                'name' => $u->full_name,
                'gender' => $u->gender,
                'age_group' => $u->age_group,
                'avatar' => $u->avatar_url,
                'visibility' => $visibility,
                'is_friend' => $user->isFriendWith($u),
                'is_participant' => in_array($u->id, $participantUserIds),
            ];
        });
    
        $data = [
            'result' => $candidates,
        ];
    
        $meta = [
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'per_page'     => $paginated->perPage(),
            'total'        => $paginated->total(),
        ];
    
        return ResponseHelper::success($data, 'Danh sách ứng viên', 200, $meta);
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
