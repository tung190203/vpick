<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;
use Google\Service\Docs\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    public function index(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'is_confirmed' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Participant::where('tournament_id', $tournamentId)
            ->with(['user']);

        if (isset($validated['is_confirmed'])) {
            $query->where('is_confirmed', $validated['is_confirmed']);
        }
        $participants = $query->paginate($validated['per_page'] ?? 15);

        return ResponseHelper::success(ParticipantResource::collection($participants));
    }

    public function join(Request $request, $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $user = Auth::user();
        if ($tournament->registration_closed_at < now()) {
            return ResponseHelper::error('Thời gian đăng ký đã kết thúc', 400);
        }
        $userSport = $user->sports()
            ->where('sport_id', $tournament->sport_id)
            ->first();
        if (!$userSport) {
            return ResponseHelper::error('Bạn không đạt yêu cầu tham gia giải đấu này', 400);
        }
        $score = $userSport->scores()
            ->where('score_type', 'vndupr_score')
            ->value('score_value');
        if (is_null($score)) {
            return ResponseHelper::error('Bạn chưa có điểm số cho môn thể thao này.', 422);
        }

        if ($score < $tournament->min_level || $score > $tournament->max_level) {
            return ResponseHelper::error('Điểm số của bạn không nằm trong yêu cầu giải đấu', 422);
        }
        $age = Carbon::parse($user->date_of_birth)->age;
        switch ($tournament->age_group) {
            case Tournament::ALL_AGES:
                break;
            case Tournament::YOUTH:
                if ($age >= 18) {
                    return ResponseHelper::error('Giải đấu chỉ dành cho người dưới 18 tuổi.', 422);
                }
                break;
            case Tournament::ADULT:
                if ($age < 18 || $age > 55) {
                    return ResponseHelper::error('Giải đấu chỉ dành cho người từ 18 đến 55 tuổi.', 422);
                }
                break;
            case Tournament::SENIOR:
                if ($age <= 55) {
                    return ResponseHelper::error('Giải đấu chỉ dành cho người trên 55 tuổi.', 422);
                }
                break;
        }
        if($user->gender != null){
            switch ($tournament->gender_policy) {
                case Tournament::MALE:
                    if ($user->gender != Tournament::MALE) {
                        return ResponseHelper::error('Giải này chỉ dành cho Nam.', 422);
                    }
                    break;
    
                case Tournament::FEMALE:
                    if ($user->gender != Tournament::FEMALE) {
                        return ResponseHelper::error('Giải này chỉ dành cho Nữ.', 422);
                    }
                    break;
    
                case Tournament::MIXED:
                    break;
            }
        }
        if ($tournament->participants()->count() >= $tournament->max_player) {
            return ResponseHelper::error('Số lượng người tham gia đã đạt giới hạn.', 422);
        }

        $exists = Participant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bạn đã tham gia giải này.'], 422);
        }

        $participant = Participant::create([
            'tournament_id' => $tournamentId,
            'user_id' => $user->id,
            'is_confirmed' => $tournament->auto_approve && !$tournament->is_private,
        ]);

        return ResponseHelper::success(ParticipantResource::collection($participant), 201);
    }

    public function suggestUsers(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tournament = Tournament::findOrFail($tournamentId);
        $perPage = $validated['per_page'] ?? 20;

        $midLevel = ($tournament->min_level + $tournament->max_level) / 2;

        $query = User::query()
            // 1. Có môn thể thao phù hợp
            ->whereHas(
                'sports',
                fn($q) =>
                $q->where('sport_id', $tournament->sport_id)
            )
            // 2. Có điểm số trong khoảng
            ->whereHas(
                'vnduprScores',
                fn($q) =>
                $q->where('score_value', '>=', $tournament->min_level)
                    ->where('score_value', '<=', $tournament->max_level)
                    ->where('score_type', 'vndupr_score')
            )
            // 3. Tuổi
            ->tap(fn($q) => $this->filterByAge($q, $tournament->age_group))
            // 4. Giới tính
            ->tap(fn($q) => $this->filterByGender($q, $tournament->gender_policy))
            // 5. Loại trừ người đã tham gia giải
            ->whereDoesntHave(
                'participants',
                fn($q) =>
                $q->where('tournament_id', $tournamentId)
            )
            // 6. Join để lấy level và sort
            ->leftJoin('user_sport', function ($join) use ($tournament) {
                $join->on('users.id', '=', 'user_sport.user_id')
                    ->where('user_sport.sport_id', '=', $tournament->sport_id);
            })
            ->leftJoin('user_sport_scores', function ($join) {
                $join->on('user_sport.id', '=', 'user_sport_scores.user_sport_id')
                    ->where('user_sport_scores.score_type', '=', 'vndupr_score');
            })
            ->select('users.*')
            ->selectRaw('user_sport_scores.score_value as level')
            ->selectRaw('ABS(user_sport_scores.score_value - ?) as level_diff', [$midLevel])
            ->selectRaw('CASE WHEN users.location_id = ? THEN 1 ELSE 0 END as same_location', [$tournament->location_id])
            ->orderByDesc('same_location')
            ->orderBy('level_diff');

        $users = $query->paginate($perPage);

        $suggestions = $users->getCollection()->map(function ($user) use ($tournament) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'avatar' => $user->avatar_url,
                'gender' => $user->gender_text,
                'age' => $user->age_years,
                'level' => $user->level,
                'same_location' => (bool) $user->same_location,
                'match_score' => $this->calculateMatchScore($user, $tournament),
            ];
        });

        return ResponseHelper::success([
            'suggestions' => $suggestions,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ], 'Gợi ý người dùng thành công');
    }

    public function inviteFriends(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tournament = Tournament::findOrFail($tournamentId);
        $perPage = $validated['per_page'] ?? 20;
        $midLevel = ($tournament->min_level + $tournament->max_level) / 2;

        $user = Auth::user();

        // Bắt đầu query từ danh sách bạn bè
        $query = $user->friends()
            // 1. Có môn thể thao phù hợp
            ->whereHas(
                'sports',
                fn($q) =>
                $q->where('sport_id', $tournament->sport_id)
            )
            // 2. Có điểm số phù hợp
            ->whereHas(
                'vnduprScores',
                fn($q) =>
                $q->where('score_value', '>=', $tournament->min_level)
                    ->where('score_value', '<=', $tournament->max_level)
                    ->where('score_type', 'vndupr_score')
            )
            // 3. Tuổi
            ->tap(fn($q) => $this->filterByAge($q, $tournament->age_group))
            // 4. Giới tính
            ->tap(fn($q) => $this->filterByGender($q, $tournament->gender_policy))
            // 5. Loại trừ đã tham gia
            ->whereDoesntHave(
                'participants',
                fn($q) =>
                $q->where('tournament_id', $tournamentId)
            )
            // 6. Join để lấy level, sắp xếp
            ->leftJoin('user_sport', function ($join) use ($tournament) {
                $join->on('users.id', '=', 'user_sport.user_id')
                    ->where('user_sport.sport_id', '=', $tournament->sport_id);
            })
            ->leftJoin('user_sport_scores', function ($join) {
                $join->on('user_sport.id', '=', 'user_sport_scores.user_sport_id')
                    ->where('user_sport_scores.score_type', '=', 'vndupr_score');
            })
            ->select('users.*')
            ->selectRaw('user_sport_scores.score_value as level')
            ->selectRaw('ABS(user_sport_scores.score_value - ?) as level_diff', [$midLevel])
            ->selectRaw('CASE WHEN users.location_id = ? THEN 1 ELSE 0 END as same_location', [$tournament->location_id])
            ->orderByDesc('same_location')
            ->orderBy('level_diff');

        $friends = $query->paginate($perPage);

        $suggestions = $friends->getCollection()->map(function ($user) use ($tournament) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'avatar' => $user->avatar_url,
                'gender' => $user->gender_text,
                'age' => $user->age_years,
                'location_id' => $user->location_id,
                'level' => $user->level,
                'same_location' => (bool) $user->same_location,
                'match_score' => $this->calculateMatchScore($user, $tournament),
            ];
        });

        return ResponseHelper::success([
            'invitations' => $suggestions,
            'meta' => [
                'current_page' => $friends->currentPage(),
                'per_page' => $friends->perPage(),
                'total' => $friends->total(),
                'last_page' => $friends->lastPage(),
            ]
        ], 'Gợi ý bạn bè để mời thành công');
    }

    public function confirm($participantId)
    {
        $participant = Participant::with('tournament')->findOrFail($participantId);
        $tournamentWithStaff = $participant->tournament->load('staff');
        $isOrganizer = $tournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xác nhận người tham gia này', 403);
        }
        if ($participant->is_confirmed) {
            return ResponseHelper::error('Người tham gia đã được xác nhận', 400);
        }
        $participant->is_confirmed = true;
        $participant->save();

        return ResponseHelper::success(new ParticipantResource($participant), 'Xác nhận người tham gia thành công');
    }

    public function acceptInvite($participantId)
    {
        $participant = Participant::with('tournament')->findOrFail($participantId);
        $tournamentWithStaff = $participant->tournament->load('staff');
        $isOrganizer = $tournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xác nhận người tham gia này', 403);
        }
        if ($participant->is_confirmed) {
            return ResponseHelper::error('Người tham gia đã được xác nhận', 400);
        }
        $tournament = Tournament::findOrFail($participant->tournament_id);
        if ($tournament->registration_closed_at < now()) {
            return ResponseHelper::error('Thời gian đăng ký đã kết thúc', 400);
        }
        $participantType = $tournament->participant;
        if ($participantType === 'user') {
            if ($tournament->participants()->count() >= $tournament->max_player) {
                return ResponseHelper::error('Số lượng người tham gia đã đạt giới hạn.', 422);
            }
        } else {
            if ($tournament->participants()->count() >= ($tournament->max_team * $tournament->player_per_team)) {
                return ResponseHelper::error('Số lượng người tham gia đã đạt giới hạn.', 422);
            }
        }
        $participant->is_confirmed = true;
        $participant->save();

        return ResponseHelper::success(new ParticipantResource($participant), 'Xác nhận lời mời tham gia thành công');
    }

    public function invite(Request $request, $tournamentId)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tournament = Tournament::findOrFail($tournamentId);
        $perPage = $validated['per_page'] ?? 20;
        $midLevel = ($tournament->min_level + $tournament->max_level) / 2;

        $user = Auth::user();

        // Bắt đầu query từ toàn bộ user (trừ chính mình)
        $query = User::query()
            ->where('users.id', '!=', $user->id)
            // 1. Có môn thể thao phù hợp
            ->whereHas(
                'sports',
                fn($q) =>
                $q->where('sport_id', $tournament->sport_id)
            )
            // 2. Có điểm số phù hợp
            ->whereHas(
                'vnduprScores',
                fn($q) =>
                $q->where('score_value', '>=', $tournament->min_level)
                    ->where('score_value', '<=', $tournament->max_level)
                    ->where('score_type', 'vndupr_score')
            )
            // 3. Tuổi
            ->tap(fn($q) => $this->filterByAge($q, $tournament->age_group))
            // 4. Giới tính
            ->tap(fn($q) => $this->filterByGender($q, $tournament->gender_policy))
            // 5. Loại trừ đã tham gia
            ->whereDoesntHave(
                'participants',
                fn($q) =>
                $q->where('tournament_id', $tournamentId)
            )
            // 6. Join để lấy level, sắp xếp
            ->leftJoin('user_sport', function ($join) use ($tournament) {
                $join->on('users.id', '=', 'user_sport.user_id')
                    ->where('user_sport.sport_id', '=', $tournament->sport_id);
            })
            ->leftJoin('user_sport_scores', function ($join) {
                $join->on('user_sport.id', '=', 'user_sport_scores.user_sport_id')
                    ->where('user_sport_scores.score_type', '=', 'vndupr_score');
            })
            ->select('users.*')
            ->selectRaw('user_sport_scores.score_value as level')
            ->selectRaw('ABS(user_sport_scores.score_value - ?) as level_diff', [$midLevel])
            ->selectRaw('CASE WHEN users.location_id = ? THEN 1 ELSE 0 END as same_location', [$tournament->location_id])
            ->orderByDesc('same_location')
            ->orderBy('level_diff');

        $users = $query->paginate($perPage);

        $suggestions = $users->getCollection()->map(function ($user) use ($tournament) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'avatar' => $user->avatar_url,
                'gender' => $user->gender_text,
                'age' => $user->age_years,
                'location_id' => $user->location_id,
                'level' => $user->level,
                'same_location' => (bool) $user->same_location,
                'match_score' => $this->calculateMatchScore($user, $tournament),
            ];
        });

        return ResponseHelper::success([
            'invitations' => $suggestions,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ], 'Gợi ý người chơi để mời thành công');
    }

    public function delete($participantId)
    {
        $participant = Participant::with('tournament')->findOrFail($participantId);
        $tournamentWithStaff = $participant->tournament->load('staff');
        $isOrganizer = $tournamentWithStaff->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xoá người tham gia này', 403);
        }
        $participant->delete();

        return ResponseHelper::success(null, 'Xoá người tham gia thành công', 200);
    }

    /**
     * Lọc theo độ tuổi
     */
    private function filterByAge($query, $ageGroup)
    {
        $today = Carbon::today();

        switch ($ageGroup) {
            case Tournament::YOUTH: // Dưới 18
                $minDate = $today->copy()->subYears(18);
                $query->where('date_of_birth', '>', $minDate);
                break;

            case Tournament::ADULT: // 18-55
                $minDate = $today->copy()->subYears(55);
                $maxDate = $today->copy()->subYears(18);
                $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
                break;

            case Tournament::SENIOR: // Trên 55
                $maxDate = $today->copy()->subYears(55);
                $query->where('date_of_birth', '<', $maxDate);
                break;

            case Tournament::ALL_AGES:
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
        if ($genderPolicy === Tournament::MALE) {
            $query->where('gender', Tournament::MALE);
        } elseif ($genderPolicy === Tournament::FEMALE) {
            $query->where('gender', Tournament::FEMALE);
        }
        // MIXED: không lọc

        return $query;
    }

    /**
     * Tính điểm phù hợp
     */
    private function calculateMatchScore($user, $tournament)
    {
        $score = 0;

        // +30 điểm nếu cùng location
        if ($user->location === $tournament->location) {
            $score += 30;
        }

        // +40 điểm dựa trên level (càng gần mid-level càng cao)
        $midLevel = ($tournament->min_level + $tournament->max_level) / 2;
        $levelDiff = abs($user->level - $midLevel);
        $maxDiff = ($tournament->max_level - $tournament->min_level) / 2;

        if ($maxDiff > 0) {
            $levelScore = 40 * (1 - ($levelDiff / $maxDiff));
            $score += max(0, $levelScore);
        }

        // +20 điểm nếu đúng giới tính (với giải MALE hoặc FEMALE)
        if ($tournament->gender_policy !== Tournament::MIXED) {
            if ($user->gender === $tournament->gender_policy) {
                $score += 20;
            }
        } else {
            $score += 10; // Mixed thì cộng ít hơn
        }

        // +10 điểm nếu trong độ tuổi lý tưởng
        $age = Carbon::parse($user->date_of_birth)->age;
        switch ($tournament->age_group) {
            case Tournament::YOUTH:
                if ($age >= 14 && $age <= 17)
                    $score += 10;
                break;
            case Tournament::ADULT:
                if ($age >= 25 && $age <= 40)
                    $score += 10;
                break;
            case Tournament::SENIOR:
                if ($age >= 55 && $age <= 65)
                    $score += 10;
                break;
        }

        return round($score, 2);
    }
}
