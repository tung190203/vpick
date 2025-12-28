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
     * - Filter theo is_confirmed (0/1)
     * - Hỗ trợ phân trang
     */
    public function index(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'is_confirmed' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = MiniParticipant::where('mini_tournament_id', $tournamentId)->withFullRelations();

        if ($request->filled('is_confirmed')) {
            $query->where('is_confirmed', $validated['is_confirmed']);
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
    public function join($tournamentId)
    {
        $miniTournament = MiniTournament::with('staff')->findOrFail($tournamentId);

        $this->checkMaxPlayers($miniTournament);

        $exists = MiniParticipant::where('mini_tournament_id', $tournamentId)
            ->where('user_id', Auth::id())
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Bạn đã tham gia kèo đấu này rồi.', 400);
        }

        $participant = MiniParticipant::create([
            'mini_tournament_id' => $tournamentId,
            'user_id' => Auth::id(),
            'is_confirmed' => $miniTournament->auto_approve && !$miniTournament->is_private,
        ]);

        if (!$participant->is_confirmed) {
            $this->notifyOrganizersJoinRequest($miniTournament, $participant);
        }

        return ResponseHelper::success(
            new MiniParticipantResource($participant->loadFullRelations()),
            'Tham gia kèo thành công',
            201
        );
    }

    /**
     * Organizer mời user
     */
    public function invite(Request $request, $tournamentId)
    {
        $miniTournament = MiniTournament::with('staff', 'participants')->findOrFail($tournamentId);

        if (!$miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền mời người tham gia.', 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->checkMaxPlayers($miniTournament);

        $exists = $miniTournament->participants()
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($exists) {
            return ResponseHelper::error('User này đã được mời hoặc đã tham gia.', 400);
        }

        $participant = $miniTournament->participants()->create([
            'user_id' => $validated['user_id'],
            'is_confirmed' => false,
            'invited_by' => Auth::id(),
        ]);

        User::find($validated['user_id'])
            ->notify(new MiniTournamentCreatorInvitationNotification($participant));

        return ResponseHelper::success(
            new MiniParticipantResource($participant->loadFullRelations()),
            'Đã gửi lời mời',
            201
        );
    }

    /**
     * Organizer duyệt user
     */
    public function confirm($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);

        if (!$participant->miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Không có quyền duyệt', 403);
        }

        if ($participant->is_confirmed) {
            return ResponseHelper::success(
                new MiniParticipantResource($participant),
                'User đã được duyệt trước đó'
            );
        }

        $this->checkMaxPlayers($participant->miniTournament);

        $participant->update(['is_confirmed' => true]);

        $participant->user->notify(
            new MiniTournamentJoinConfirmedNotification($participant)
        );

        return ResponseHelper::success(
            new MiniParticipantResource($participant->loadFullRelations()),
            'Duyệt thành công'
        );
    }

    /**
     * User accept lời mời
     */
    public function acceptInvite($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);

        if ($participant->user_id !== Auth::id()) {
            return ResponseHelper::error('Không có quyền', 403);
        }

        if ($participant->is_confirmed) {
            return ResponseHelper::success(
                new MiniParticipantResource($participant),
                'Bạn đã chấp nhận trước đó'
            );
        }

        $this->checkMaxPlayers($participant->miniTournament);

        $participant->update(['is_confirmed' => true]);

        return ResponseHelper::success(
            new MiniParticipantResource($participant->loadFullRelations()),
            'Chấp nhận lời mời thành công'
        );
    }

    /**
     * User từ chối lời mời
     */
    public function declineInvite($participantId)
    {
        $participant = MiniParticipant::findOrFail($participantId);

        if ($participant->user_id !== Auth::id()) {
            return ResponseHelper::error('Không có quyền', 403);
        }

        $participant->delete();

        return ResponseHelper::success(null, 'Đã từ chối lời mời');
    }

    /**
     * Organizer xóa participant
     */
    public function delete($participantId)
    {
        $participant = MiniParticipant::with('miniTournament')->findOrFail($participantId);

        if (!$participant->miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Không có quyền', 403);
        }

        $participant->delete();

        $participant->user?->notify(
            new MiniTournamentRemovedNotification($participant)
        );

        return ResponseHelper::success(null, 'Đã xóa người tham gia');
    }

    /**
     * =====================
     * Helpers
     * =====================
     */
    private function checkMaxPlayers(MiniTournament $miniTournament)
    {
        if (!$miniTournament->max_players) {
            return;
        }

        $confirmed = $miniTournament->participants()
            ->where('is_confirmed', true)
            ->count();

        if ($confirmed >= $miniTournament->max_players) {
            abort(ResponseHelper::error('Kèo đã đủ số lượng người chơi.', 400));
        }
    }

    private function notifyOrganizersJoinRequest(MiniTournament $tournament, MiniParticipant $participant)
    {
        $organizers = $tournament->staff()
            ->wherePivot('role', MiniTournamentStaff::ROLE_ORGANIZER)
            ->get();

        foreach ($organizers as $organizer) {
            if ($organizer->id !== Auth::id()) {
                $organizer->notify(
                    new MiniTournamentJoinRequestNotification($participant)
                );
            }
        }
    }
}
