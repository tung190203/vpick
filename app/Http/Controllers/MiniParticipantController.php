<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use App\Http\Resources\MiniParticipantResource;
use App\Models\MiniTournamentStaff;
use App\Models\SuperAdminDraft;
use App\Models\User;
use App\Notifications\MiniTournamentCreatorInvitationNotification;
use App\Notifications\MiniTournamentJoinConfirmedNotification;
use App\Notifications\MiniTournamentJoinRequestNotification;
use App\Notifications\MiniTournamentRemovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $isSuperAdmin = SuperAdminDraft::where('user_id', Auth::id())->exists();

        $participant = $miniTournament->participants()->create([
            'user_id' => $validated['user_id'],
            'is_confirmed' => $isSuperAdmin,
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

    public function deleteStaff($staffId)
    {
        $tournamentStaff = DB::table('mini_tournament_staff')->where('id', $staffId)->first();
        if (!$tournamentStaff) {
            return ResponseHelper::error('Nhân viên không tồn tại', 404);
        }
        $tournament = MiniTournament::with('staff')->findOrFail($tournamentStaff->mini_tournament_id);
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xoá nhân viên này', 403);
        }
        if( $tournamentStaff->role === 'organizer') {
            return ResponseHelper::error('Không thể xoá nhân viên với vai trò tổ chức', 400);
        }
        if ($tournamentStaff->user_id === Auth::id()) {
            return ResponseHelper::error('Bạn không thể tự xoá chính mình', 400);
        }
        DB::table('mini_tournament_staff')->where('id', $staffId)->delete();

        return ResponseHelper::success(null, 'Xoá nhân viên thành công', 200);
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

    public function getCandidates(Request $request, $tournamentId)
    {
        $miniTournament = MiniTournament::withFullRelations()->findOrFail($tournamentId);
        $user = Auth::user();
    
        $validated = $request->validate([
            'scope' => 'required|in:club,friends,area,all',
            'club_id' => 'required_if:scope,club|exists:clubs,id',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:200',
            'lat' => 'required_if:scope,area|numeric',
            'lng' => 'required_if:scope,area|numeric',
            'radius' => 'required_if:scope,area|numeric|min:0.1|max:200',
        ]);
    
        $perPage = $validated['per_page'] ?? 20;
        $scope = $validated['scope'];
    
        // 🧮 Tính mid level cho sorting (nếu mini tournament có min/max level)
        $midLevel = null;
        if (isset($miniTournament->min_level) && isset($miniTournament->max_level) 
            && $miniTournament->min_level !== null && $miniTournament->max_level !== null) {
            $midLevel = (float)(($miniTournament->min_level + $miniTournament->max_level) / 2);
        }
    
        // 🎯 Tùy theo phạm vi (scope)
        switch ($scope) {
            case 'club':
                $query = User::withFullRelations()
                    ->whereHas('clubs', fn($q) => $q->where('clubs.id', $validated['club_id']));
                break;

            case 'friends':
                $query = User::withFullRelations()
                    ->whereExists(function ($q) use ($user) {
                        $q->select(DB::raw(1))
                            ->from('follows as f1')
                            ->whereColumn('f1.followable_id', 'users.id')
                            ->where('f1.user_id', $user->id)
                            ->where('f1.followable_type', User::class);
                    })
                    ->whereExists(function ($q) use ($user) {
                        $q->select(DB::raw(1))
                            ->from('follows as f2')
                            ->whereColumn('f2.user_id', 'users.id')
                            ->where('f2.followable_id', $user->id)
                            ->where('f2.followable_type', User::class);
                    });
                break;

            case 'area':
                $lat = $validated['lat'];
                $lng = $validated['lng'];
                $radius = $validated['radius'];

                $haversine = "(6371 * acos(
                        cos(radians(?))
                        * cos(radians(users.latitude))
                        * cos(radians(users.longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(users.latitude))
                    ))";

                $query = User::withFullRelations()
                    ->whereNotNull('users.latitude')
                    ->whereNotNull('users.longitude')
                    ->whereRaw("$haversine <= ?", [
                        $lat,
                        $lng,
                        $lat,
                        $radius
                    ])
                    ->orderByRaw("$haversine asc", [
                        $lat,
                        $lng,
                        $lat
                    ]);
                break;
                
            case 'all':
                $query = User::withFullRelations();
                break;
        }
    
        // 🔐 Visibility filter (trừ scope 'all')
        if ($scope !== 'all') {
            $query->whereIn('users.visibility', [
                User::VISIBILITY_PUBLIC,
                User::VISIBILITY_FRIEND_ONLY
            ]);
        } else {
            $query->whereIn('users.visibility', [User::VISIBILITY_PUBLIC]);
        }
    
        // ⚽ Filter theo setting của giải (chỉ áp dụng khi scope !== 'all')
        if ($scope !== 'all') {
            // 1. Có môn thể thao phù hợp (nếu mini tournament có sport_id)
            if (isset($miniTournament->sport_id)) {
                $query->whereHas('sports', function ($q) use ($miniTournament) {
                    $q->where('sport_id', $miniTournament->sport_id);
                });
            }
    
            // 2. Tuổi (nếu mini tournament có age_group)
            if (isset($miniTournament->age_group)) {
                $query->tap(fn ($q) => $this->filterByAge($q, $miniTournament->age_group));
            }
    
            // 3. Giới tính (nếu mini tournament có gender_policy)
            if (isset($miniTournament->gender_policy)) {
                $query->tap(fn ($q) => $this->filterByGender($q, $miniTournament->gender_policy));
            }
        }
    
        // 4. Loại trừ người có ĐỒNG THỜI trong cả participant VÀ staff (áp dụng cho tất cả scope)
        $participantUserIds = $miniTournament->participants->pluck('user_id')->toArray();
        $staffUserIds = $miniTournament->miniTournamentStaffs->pluck('user_id')->toArray();
        
        // Lấy những user có trong CẢ 2 mảng (giao của 2 tập hợp)
        $excludedUserIds = array_intersect($participantUserIds, $staffUserIds);
        
        // Loại trừ những user có trong cả 2 bảng
        if (!empty($excludedUserIds)) {
            $query->whereNotIn('users.id', $excludedUserIds);
        }
    
        // 5. Join để lấy level + filter level (chỉ khi scope !== 'all' và có sport_id)
        if ($scope !== 'all' && isset($miniTournament->sport_id)) {
            $query->leftJoin('user_sport', function ($join) use ($miniTournament) {
                $join->on('users.id', '=', 'user_sport.user_id')
                    ->where('user_sport.sport_id', $miniTournament->sport_id);
            })
            ->leftJoin('user_sport_scores', function ($join) {
                $join->on('user_sport.id', '=', 'user_sport_scores.user_sport_id')
                    ->where('user_sport_scores.score_type', 'vndupr_score');
            });
    
            // 6. Filter level
            if (isset($miniTournament->min_level)) {
                $query->where('user_sport_scores.score_value', '>=', $miniTournament->min_level);
            }
            if (isset($miniTournament->max_level)) {
                $query->where('user_sport_scores.score_value', '<=', $miniTournament->max_level);
            }
        }
    
        // 7. Select + Sort
        if ($scope !== 'all') {
            $query->select('users.*');
            
            if (isset($miniTournament->sport_id)) {
                $query->selectRaw('user_sport_scores.score_value as level');
                
                if ($midLevel !== null) {
                    $query->selectRaw(
                        'ABS(user_sport_scores.score_value - ?) as level_diff',
                        [$midLevel]
                    );
                }
            }
            
            if (isset($miniTournament->location_id)) {
                $query->selectRaw(
                    'CASE WHEN users.location_id = ? THEN 1 ELSE 0 END as same_location',
                    [$miniTournament->location_id]
                )
                ->orderByDesc('same_location');
            }
            
            if ($midLevel !== null) {
                $query->orderBy('level_diff');
            }
        } else {
            $query->select('users.*');
        }
    
        // 🔍 Tìm kiếm tên người dùng (áp dụng cho tất cả scope)
        if (!empty($validated['search'])) {
            $query->where('users.full_name', 'like', '%' . $validated['search'] . '%');
        }
    
        // 🧮 Phân trang
        $paginated = $query->paginate($perPage);
        $candidates = $paginated->getCollection()->map(function ($u) use ($user, $excludedUserIds) {
            return [
                'id' => $u->id,
                'name' => $u->full_name,
                'visibility' => $u->visibility,
                'age_group' => $u->age_group,
                'avatar_url' => $u->avatar_url,
                'thumbnail' => $u->thumbnail,
                'gender' => $u->gender,
                'gender_text' => $u->gender_text,
                'play_times' => [],
        
                'sports' => $u->sports->map(function ($userSport) {
                    $scores = $userSport->scores
                        ->pluck('score_value', 'score_type')
                        ->toArray();
        
                    return [
                        'sport_id' => $userSport->sport_id,
                        'sport_icon' => $userSport->sport?->icon,
                        'sport_name' => $userSport->sport?->name,
                        'scores' => [
                            'personal_score' => $scores['personal_score'] ?? '0.000',
                            'dupr_score'     => $scores['dupr_score'] ?? '0.000',
                            'vndupr_score'   => $scores['vndupr_score'] ?? '0.000',
                        ],
                        'total_matches'     => $userSport->total_matches ?? 0,
                        'total_tournaments' => $userSport->total_tournaments ?? 0,
                        'total_prizes'      => $userSport->total_prizes ?? 0,
                    ];
                }),
                'is_friend' => $user->isFriendWith($u),
                'is_mini_participant' => in_array($u->id, $excludedUserIds),
            ];
        });
    
        return ResponseHelper::success([
            'result' => $candidates,
        ], 'Danh sách ứng viên', 200, [
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'per_page'     => $paginated->perPage(),
            'total'        => $paginated->total(),
        ]);
    }
    
    /**
     * Lọc theo độ tuổi
     */
    private function filterByAge($query, $ageGroup)
    {
        $today = Carbon::today();
    
        switch ($ageGroup) {
            case MiniTournament::YOUTH: // Dưới 18
                $minDate = $today->copy()->subYears(18);
                $query->where('date_of_birth', '>', $minDate);
                break;
    
            case MiniTournament::ADULT: // 18-55
                $minDate = $today->copy()->subYears(55);
                $maxDate = $today->copy()->subYears(18);
                $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
                break;
    
            case MiniTournament::SENIOR: // Trên 55
                $maxDate = $today->copy()->subYears(55);
                $query->where('date_of_birth', '<', $maxDate);
                break;
    
            case MiniTournament::ALL_AGES:
            default:
                // Không lọc
                break;
        }
    
        return $query;
    }
    
    /**
     * Lọc theo giới tính
     */
    private function filterByGender($query, $genderPolicy)
    {
        if ($genderPolicy === MiniTournament::MALE) {
            $query->where('gender', MiniTournament::MALE);
        } elseif ($genderPolicy === MiniTournament::FEMALE) {
            $query->where('gender', MiniTournament::FEMALE);
        }
        // MIXED: không lọc
    
        return $query;
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
