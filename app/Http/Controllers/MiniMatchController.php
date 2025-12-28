<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MiniMatchResource;
use App\Models\MiniMatch;
use App\Models\MiniMatchResult;
use App\Models\MiniParticipant;
use App\Models\MiniTeam;
use App\Models\MiniTeamMember;
use App\Models\MiniTournament;
use App\Models\User;
use App\Models\VnduprHistory;
use App\Notifications\MiniMatchCreatedNotification;
use App\Notifications\MiniMatchResultConfirmedNotification;
use App\Notifications\MiniMatchUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MiniMatchController extends Controller
{
    private const VALIDATION_RULE = 'sometimes';
    /**
     * Lấy danh sách trận đấu trong mini tournament (theo vòng, thời gian, lọc theo người chơi)
     */
    public function index(Request $request, $miniTournamentId)
    {
        $request->validate([
            'filter' => 'nullable|string|in:matches,my_matches,leaderboard',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $miniTournament = MiniTournament::findOrFail($miniTournamentId);
        $filter = $request->input('filter', 'matches');
        $perPage = $request->input('per_page', MiniMatch::PER_PAGE);
    
        $query = MiniMatch::withFullRelations()
            ->where('mini_tournament_id', $miniTournament->id)
            ->orderBy('round')
            ->orderBy('scheduled_at');
    
        if ($filter === 'my_matches') {
            $userId = Auth::id();
    
            $query->where(function ($q) use ($userId) {
                $q->whereHas('team1.members.user', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                })->orWhereHas('team2.members.user', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                });
            });
        }
    
        // Paginate
        $matches = $query->paginate($perPage);
    
        $data = [
            'matches' => MiniMatchResource::collection($matches),
        ];
    
        $meta = [
            'current_page' => $matches->currentPage(),
            'last_page'    => $matches->lastPage(),
            'per_page'     => $matches->perPage(),
            'total'        => $matches->total(),
        ];
    
        return ResponseHelper::success($data, 'Lấy danh sách trận đấu thành công', 200, $meta);
    }
    /**
     * Lấy thông tin chi tiết trận đấu
     */
    public function show($matchId)
    {
        $match = MiniMatch::withFullRelations()->findOrFail($matchId);

        return ResponseHelper::success(new MiniMatchResource($match), 'Lấy thông tin trận đấu thành công');
    }
    public function store(Request $request, $miniTournamentId)
    {
        $miniTournament = MiniTournament::findOrFail($miniTournamentId);
    
        if(!$miniTournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền tạo trận đấu', 403);
        }
    
        $data = $request->validate([
            'team1' => 'required|array|min:1',
            'team2' => 'required|array|min:1',
            'team1.*' => 'exists:users,id',
            'team2.*' => 'exists:users,id',
            'team1_name' => 'nullable|string|max:255',
            'team2_name' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date',
            'round' => 'nullable|string',
            'referee' => 'nullable|exists:referees,id',
            'yard_number' => 'nullable|string|max:50',
            'name_of_match' => 'nullable|string|max:255',
        ]);

        $team1Count = count($data['team1']);
        $team2Count = count($data['team2']);

        if ($team1Count !== $team2Count) {
            return ResponseHelper::error('Số lượng người chơi của 2 đội phải bằng nhau',422);
        }

        switch ($miniTournament->match_type) {
            case MiniTournament::MATCH_TYPE_SINGLE:
                if ($team1Count !== 1) {
                    return ResponseHelper::error('Kèo này chỉ cho phép tạo trận 1v1', 422);
                }
                break;
            case MiniTournament::MATCH_TYPE_DOUBLE:
                if ($team1Count !== 2) {
                    return ResponseHelper::error('Kèo này chỉ cho phép tạo trận 2v2', 422);
                }
                break;
            default:
                if (!in_array($team1Count, [1, 2])) {
                    return ResponseHelper::error('Chỉ cho phép tạo trận 1v1 hoặc 2v2', 422);
                }
                break;
        }
    
        $allUserIds = array_unique(array_merge($data['team1'], $data['team2']));
    
        $validParticipants = MiniParticipant::where('mini_tournament_id', $miniTournament->id)
            ->where('is_confirmed', true)
            ->whereIn('user_id', $allUserIds)
            ->pluck('user_id')
            ->toArray();
    
        if (count($validParticipants) !== count($allUserIds)) {
            return ResponseHelper::error(
                'Có người chơi chưa tham gia hoặc chưa được duyệt trong kèo',
                422
            );
        }
    
        DB::beginTransaction();
    
        try {
            $team1 = MiniTeam::create([
                'mini_tournament_id' => $miniTournament->id,
                'name' => $data['team1_name'] ?? 'Team 1',
            ]);
    
            foreach ($data['team1'] as $userId) {
                $team1->members()->create(['user_id' => $userId]);
            }
    
            $team2 = MiniTeam::create([
                'mini_tournament_id' => $miniTournament->id,
                'name' => $data['team2_name'] ?? 'Team 2',
            ]);
    
            foreach ($data['team2'] as $userId) {
                $team2->members()->create(['user_id' => $userId]);
            }
            $matchCount = MiniMatch::where('mini_tournament_id', $miniTournament->id)->count();
            $defaultMatchName = 'Trận đấu số ' . ($matchCount + 1);
    
            $match = MiniMatch::create([
                'mini_tournament_id' => $miniTournament->id,
                'team1_id' => $team1->id,
                'team2_id' => $team2->id,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => MiniMatch::STATUS_PENDING,
                'round' => $data['round'] ?? null,
                'yard_number' => $data['yard_number'] ?? null,
                'name_of_match' => $data['name_of_match'] ?? $defaultMatchName
            ]);
    
            DB::commit();
    
            return ResponseHelper::success(new MiniMatchResource($match->loadFullRelations()), 'Tạo trận đấu thành công', 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage());
        }
    }
    /**
     * Cập nhật thông tin trận đấu trong kèo đấu
     */

     public function update(Request $request, $matchId)
     {
         $match = MiniMatch::withFullRelations()->findOrFail($matchId);
     
         $miniTournament = $match->miniTournament;
     
         if (!$miniTournament->hasOrganizer(Auth::id())) {
             return ResponseHelper::error('Bạn không có quyền sửa trận đấu', 403);
         }
     
         $data = $request->validate([
             'team1' => 'sometimes|array|min:1',
             'team2' => 'sometimes|array|min:1',
             'team1.*' => 'exists:users,id',
             'team2.*' => 'exists:users,id',
             'team1_name' => 'nullable|string|max:255',
             'team2_name' => 'nullable|string|max:255',
             'scheduled_at' => 'nullable|date',
             'round' => 'nullable|string',
             'yard_number' => 'nullable|string|max:50',
             'name_of_match' => 'nullable|string|max:255',
         ]);
     
         // ---- CHECK MATCH TYPE ----
         $team1Count = isset($data['team1']) ? count($data['team1']) : $match->team1->members->count();
     
         $team2Count = isset($data['team2']) ? count($data['team2']) : $match->team2->members->count();
     
         if ($team1Count !== $team2Count) {
             return ResponseHelper::error('Số lượng người chơi của 2 đội phải bằng nhau', 422);
         }
     
         switch ($miniTournament->match_type) {
             case MiniTournament::MATCH_TYPE_SINGLE:
                 if ($team1Count !== 1) {
                     return ResponseHelper::error('Kèo này chỉ cho phép tạo trận 1v1', 422);
                 }
                 break;
     
             case MiniTournament::MATCH_TYPE_DOUBLE:
                 if ($team1Count !== 2) {
                     return ResponseHelper::error('Kèo này chỉ cho phép tạo trận 2v2', 422);
                 }
                 break;
     
             default:
                 if (!in_array($team1Count, [1, 2])) {
                     return ResponseHelper::error('Chỉ cho phép tạo trận 1v1 hoặc 2v2', 422);
                 }
         }
     
         DB::beginTransaction();
     
         try {
             // ---- UPDATE TEAM 1 ----
             if (isset($data['team1'])) {
                 $this->syncTeamMembers($match->team1, $data['team1']);
             }
     
             if (!empty($data['team1_name'])) {
                 $match->team1->update(['name' => $data['team1_name']]);
             }
     
             // ---- UPDATE TEAM 2 ----
             if (isset($data['team2'])) {
                 $this->syncTeamMembers($match->team2, $data['team2']);
             }
     
             if (!empty($data['team2_name'])) {
                 $match->team2->update(['name' => $data['team2_name']]);
             }
     
             // ---- UPDATE MATCH INFO ----
             $match->update([
                 'scheduled_at' => $data['scheduled_at'] ?? $match->scheduled_at,
                 'round' => $data['round'] ?? $match->round,
                 'yard_number' => $data['yard_number'] ?? $match->yard_number,
                 'name_of_match' => $data['name_of_match'] ?? $match->name_of_match,
             ]);
     
             DB::commit();
     
             return ResponseHelper::success(
                 new MiniMatchResource($match->loadFullRelations()),
                 'Cập nhật trận đấu thành công'
             );
     
         } catch (\Throwable $e) {
             DB::rollBack();
             return ResponseHelper::error($e->getMessage());
         }
     }

     protected function syncTeamMembers(MiniTeam $team, array $userIds)
    {
        $team->members()->delete();

        foreach ($userIds as $userId) {
            $team->members()->create(['user_id' => $userId]);
        }
    }     

    /**
     * Thêm hoặc cập nhật kết quả 1 hiệp (set)
     */
    public function addSetResult(Request $request, $matchId)
    {
        $validated = $request->validate([
            'set_number' => 'required|integer|min:1',
            'results' => 'required|array|min:2|max:2',
            'results.*.team_id' => 'required|exists:mini_teams,id',
            'results.*.score' => 'required|integer|min:0',
        ]);
    
        $match = MiniMatch::withFullRelations()->findOrFail($matchId);
        $tournament = $match->miniTournament->load('staff');
    
        if (!$tournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Người dùng không có quyền thêm kết quả trận đấu trong kèo đấu này',403);
        }

        if (!$match->isEditable()) {
            return ResponseHelper::error('Trận đấu này đã được xác nhận kết quả', 400);
        }
    
        // =======================
        // 1. Kiểm tra set_number
        // =======================
        if (!empty($tournament->set_number) && $validated['set_number'] > $tournament->set_number) {
            return ResponseHelper::error(
                "Trận đấu không được vượt quá {$tournament->set_number} set",
                400
            );
        }
    
        // =======================
        // 2. Lấy luật thi đấu
        // =======================
        $pointsToWinSet   = $tournament->games_per_set;
        $pointsDifference = $tournament->points_difference;
        $maxPoints        = $tournament->max_points;
    
        if ($pointsToWinSet === null || $pointsDifference === null || $maxPoints === null) {
            return ResponseHelper::error(
                'Kèo đấu chưa thiết lập đủ luật thi đấu',
                400
            );
        }
    
        // =======================
        // 3. Validate team hợp lệ
        // =======================
        $teamIds = [$match->team1_id, $match->team2_id];
        $inputResults = collect($validated['results']);
    
        if ($inputResults->count() !== 2) {
            return ResponseHelper::error(
                'Cần cung cấp điểm số cho cả hai đội',
                400
            );
        }
    
        $teamA = $inputResults->firstWhere('team_id', $teamIds[0]);
        $teamB = $inputResults->firstWhere('team_id', $teamIds[1]);
    
        if (!$teamA || !$teamB) {
            return ResponseHelper::error(
                'Team không hợp lệ hoặc không thuộc trận đấu này',
                400
            );
        }
    
        $A = (int) $teamA['score'];
        $B = (int) $teamB['score'];
    
        if ($A < 0 || $B < 0) {
            return ResponseHelper::error('Điểm số không hợp lệ', 400);
        }
    
        // =======================
        // 4. Logic xác định thắng set
        // =======================
        $winnerTeamId = null;
        $isSetCompleted = false;
        $scoreDiff = abs($A - $B);
        $isPointsToWinReached = ($A >= $pointsToWinSet || $B >= $pointsToWinSet);
        $isMaxPointsReached = ($A == $maxPoints || $B == $maxPoints);
    
        if ($pointsToWinSet == $maxPoints) {
            // Ví dụ: 11-2-11
            if ($isMaxPointsReached) {
                $isSetCompleted = true;
                $winnerTeamId = $A > $B ? $teamIds[0] : $teamIds[1];
            }
        } else {
            // Ví dụ: 11-2-15
            if ($isPointsToWinReached && $scoreDiff >= $pointsDifference) {
                $isSetCompleted = true;
                $winnerTeamId = $A > $B ? $teamIds[0] : $teamIds[1];
            } elseif ($isMaxPointsReached) {
                if ($A == $B) {
                    return ResponseHelper::error(
                        "Điểm số hòa tại điểm tối đa $maxPoints",
                        400
                    );
                }
                $isSetCompleted = true;
                $winnerTeamId = $A > $B ? $teamIds[0] : $teamIds[1];
            }
        }
    
        if (!$isSetCompleted || !$winnerTeamId) {
            return ResponseHelper::error(
                "Set {$validated['set_number']} chưa thỏa mãn luật thắng",
                400
            );
        }
    
        // =======================
        // 5. Anti-cheat điểm
        // =======================
        $winningScore = max($A, $B);
        $losingScore  = min($A, $B);
    
        if ($pointsToWinSet == $maxPoints) {
            if (!($winningScore == $maxPoints && $losingScore < $maxPoints)) {
                return ResponseHelper::error('Điểm số không hợp lệ', 400);
            }
        } else {
            if ($winningScore < $maxPoints) {
                if (
                    $winningScore < $pointsToWinSet ||
                    ($winningScore - $losingScore) < $pointsDifference
                ) {
                    return ResponseHelper::error('Điểm số không hợp lệ', 400);
                }
            } else {
                if ($winningScore != $maxPoints || $winningScore <= $losingScore) {
                    return ResponseHelper::error('Điểm số không hợp lệ', 400);
                }
            }
        }
    
        // =======================
        // 6. Ghi DB
        // =======================
        DB::transaction(function () use ($match, $validated, $winnerTeamId) {
    
            MiniMatchResult::where('mini_match_id', $match->id)
                ->where('set_number', $validated['set_number'])
                ->delete();

            foreach ($validated['results'] as $res) {
                MiniMatchResult::create([
                    'mini_match_id' => $match->id,
                    'team_id' => $res['team_id'],
                    'score' => $res['score'],
                    'set_number' => $validated['set_number'],
                    'won_set' => $res['team_id'] == $winnerTeamId,
                ]);
            }
    
            $match->update([
                'team1_confirm' => false,
                'team2_confirm' => false,
            ]);
        });
    
        $match = MiniMatch::withFullRelations()->findOrFail($matchId);
    
        return ResponseHelper::success(
            new MiniMatchResource($match),
            'Thành công'
        );
    }

    /**
     * Xóa kết quả 1 hiệp
     */
    public function deleteSetResult($matchId, $setNumber)
    {
        $match = MiniMatch::with('miniTournament')->findOrFail($matchId);
        $tournament = $match->miniTournament->load('staff');
        if (!$tournament->hasOrganizer(Auth::id())) {
            return ResponseHelper::error('Người dùng không có quyền xoá kết quả trận đấu trong kèo đấu này',403);
        }

        if (!$match->isEditable()) {
            return ResponseHelper::error('Trận đấu đã được xác nhận không thể xoá kết quả', 400);
        }

        MiniMatchResult::where('mini_match_id', $match->id)
            ->where('set_number', $setNumber)
            ->delete();

        return ResponseHelper::success(null, 'Kết quả hiệp đã được xóa');
    }

    /**
     * Xóa trận đấu
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return ResponseHelper::error('Danh sách trận đấu không hợp lệ', 400);
        }

        $matches = MiniMatch::with('miniTournament')
            ->whereIn('id', $ids)
            ->get();

        if ($matches->isEmpty()) {
            return ResponseHelper::error('Không tìm thấy trận đấu nào', 404);
        }

        foreach ($matches as $match) {
            $tournament = $match->miniTournament->load('staff');
            if (!$tournament->hasOrganizer(Auth::id())) {
                return ResponseHelper::error('Người dùng không có quyền xoá trận đấu này',403);
            }
            if (!$match->isEditable()) {
                return ResponseHelper::error("Không thể xóa trận đấu đã xác nhận kết quả", 400);
            }
        }

        MiniMatchResult::whereIn('mini_match_id', $ids)->delete();
        MiniMatch::whereIn('id', $ids)->delete();

        return ResponseHelper::success(null, 'Xoá thành công');
    }

    /**
     * Tạo QR code để xác nhận kết quả trận đấu
     */

    public function generateQr($matchId)
    {
        $match = MiniMatch::with('miniTournament')->findOrFail($matchId);
        $url = url("/api/mini-matches/confirm-result/{$match->id}");

        return ResponseHelper::success(['qr_url' => $url], 'Thành công');
    }
    /**
     * Xác nhận kết quả trận đấu (thông qua QR code)
     */

     public function confirmResult($matchId)
     {
         // 1. Load data với đầy đủ quan hệ (tận dụng scope đã có)
         $match = MiniMatch::withFullRelations()->findOrFail($matchId);
     
         if (!$match->isEditable()) {
             return ResponseHelper::error('Kết quả trận đấu đã được xác nhận trước đó', 400);
         }
     
         $tournament = $match->miniTournament;
         $sportId = $tournament->sport_id;
         $currentUserId = Auth::id();
         $isOrganizer = $tournament->hasOrganizer($currentUserId);
     
         // 2. Kiểm tra quyền xác nhận
         $userTeam = null;
         if (!$isOrganizer) {
             if ($match->team1->members->contains('user_id', $currentUserId)) {
                 $userTeam = $match->team1;
             } elseif ($match->team2->members->contains('user_id', $currentUserId)) {
                 $userTeam = $match->team2;
             }
     
             if (!$userTeam) {
                 return ResponseHelper::error('Bạn không có quyền xác nhận kết quả trận đấu này', 403);
             }
         }
     
         // 3. Thực hiện xác nhận và tính toán trong Transaction
         return DB::transaction(function () use ($match, $isOrganizer, $userTeam, $sportId) {
             // --- CẬP NHẬT TRẠNG THÁI XÁC NHẬN ---
             if ($isOrganizer) {
                 $match->team1_confirm = true;
                 $match->team2_confirm = true;
             } else {
                 if ($userTeam->id === $match->team1_id) $match->team1_confirm = true;
                 if ($userTeam->id === $match->team2_id) $match->team2_confirm = true;
             }
     
             // --- CHỈ TÍNH TOÁN KHI CẢ 2 ĐÃ CONFIRM ---
             if ($match->team1_confirm && $match->team2_confirm) {
                 $this->processMatchCompletion($match, $sportId);
             }
     
             $match->save();
     
             return ResponseHelper::success(
                 new MiniMatchResource($match->refresh()),
                 'Xác nhận kết quả thành công'
             );
         });
     }
     
     /**
      * Logic xử lý khi trận đấu hoàn tất (Tính winner, Elo/VNDUPR)
      */
     private function processMatchCompletion($match, $sportId)
     {
         // A. Xác định đội thắng
         $wins = $match->results->where('won_set', true)->groupBy('team_id')->map->count();
         $maxWins = $wins->max();
         $winnerTeams = $wins->filter(fn($c) => $c === $maxWins)->keys();
         
         $match->team_win_id = $winnerTeams->count() === 1 ? $winnerTeams->first() : null;
         $match->status = MiniMatch::STATUS_COMPLETED;
     
         foreach ($match->results as $r) {
             $r->update(['status' => MiniMatchResult::STATUS_APPROVED]);
         }
     
         // B. Tính toán S (Actual Score) & R (Average Rating)
         $scores = $match->results->groupBy('team_id')->map->sum('score');
         $t1Score = $scores->get($match->team1_id, 0);
         $t2Score = $scores->get($match->team2_id, 0);
         $totalScore = $t1Score + $t2Score;
     
         $S_t1 = $totalScore > 0 ? $t1Score / $totalScore : 0;
         $S_t2 = $totalScore > 0 ? $t2Score / $totalScore : 0;
     
         // Tận dụng dữ liệu đã load trong relation để tính Rating trung bình (Tránh Query DB)
         $calcAvgRating = function ($team) use ($sportId) {
             $ratings = $team->members->map(function ($member) use ($sportId) {
                 $userSport = $member->user->sports->where('sport_id', $sportId)->first();
                 if (!$userSport) return 0;
                 $scoreRecord = $userSport->scores->where('score_type', 'vndupr_score')->first();
                 return $scoreRecord ? (float)$scoreRecord->score_value : 0;
             });
             return $ratings->count() > 0 ? $ratings->avg() : 0;
         };
     
         $R_t1 = $calcAvgRating($match->team1);
         $R_t2 = $calcAvgRating($match->team2);
     
         $E_t1 = 1 / (1 + pow(10, ($R_t2 - $R_t1)));
         $E_t2 = 1 / (1 + pow(10, ($R_t1 - $R_t2)));
     
         // C. Cập nhật điểm cho từng Player
         $teamData = [
             ['team' => $match->team1, 'S' => $S_t1, 'E' => $E_t1],
             ['team' => $match->team2, 'S' => $S_t2, 'E' => $E_t2],
         ];
     
         $W = 0.2;
     
         foreach ($teamData as $data) {
             foreach ($data['team']->members as $member) {
                 $user = $member->user;
                 
                 // 1. Cập nhật số trận
                 $user->increment('total_matches');
     
                 // 2. Lấy R_old từ relation (Không dùng DB::table)
                 $userSport = $user->sports->where('sport_id', $sportId)->first();
                 $R_old = 0;
                 if ($userSport) {
                     $scoreRecord = $userSport->scores->where('score_type', 'vndupr_score')->first();
                     $R_old = $scoreRecord ? (float)$scoreRecord->score_value : 0;
                 }
     
                 // 3. Tính K & Turbo (Giữ nguyên logic gốc)
                 $history = VnduprHistory::where('user_id', $user->id)->latest('id')->take(15)->get()->reverse();
                 
                 $K = ($user->total_matches <= 10) ? 1 : (($user->total_matches <= 50) ? 0.6 : 0.3);
                 
                 if ($history->count() >= 2) {
                     if (($history->first()->score_before - $history->last()->score_after) > 0.5) {
                         $K = 1;
                     }
                 }
     
                 // 4. Tính R_new
                 $R_new = $R_old + ($W * $K * ($data['S'] - $data['E']));
     
                 // 5. Lưu History & Update Score
                 VnduprHistory::create([
                     'user_id' => $user->id,
                     'mini_match_id' => $match->id,
                     'score_before' => $R_old,
                     'score_after' => $R_new,
                 ]);
     
                 if ($userSport) {
                     DB::table('user_sport_scores')->updateOrInsert(
                         ['user_sport_id' => $userSport->id, 'score_type' => 'vndupr_score'],
                         ['score_value' => $R_new, 'updated_at' => now()]
                     );
                 }
             }
         }
     }

    /**
     * Trình lọc trận đấu (theo địa điểm, môn thể thao, từ khóa, thời gian, vị trí)
     */
    public function listMiniMatch(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'sometimes',
            'lng' => 'sometimes',
            'radius' => 'sometimes|numeric|min:1',
            'minLat' => self::VALIDATION_RULE,
            'maxLat' => self::VALIDATION_RULE,
            'minLng' => self::VALIDATION_RULE,
            'maxLng' => self::VALIDATION_RULE,
            'per_page' => 'sometimes|integer|min:1|max:200',
            'is_map' => 'sometimes|boolean',
            'date_from' => 'sometimes|date',
            'location_id' => 'sometimes|integer|exists:locations,id',
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'keyword' => 'sometimes|string|max:255',
            'rating' => 'sometimes',
            'rating.*' => 'integer',
            'time_of_day' => 'sometimes|array',
            'time_of_day.*' => 'in:morning,afternoon,evening',
            'slot_status' => 'sometimes|array',
            'slot_status.*' => 'in:one_slot,two_slot,full_slot',
            'type' => 'sometimes|array',
            'type.*' => 'in:single,double',
            'fee' => 'sometimes|array',
            'fee.*' => 'in:free,paid',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0',
        ]);
    
        $query = MiniMatch::withFullRelations()->filter($validated);
    
        $hasFilter = collect([
            'sport_id', 'location_id', 'date_from', 'keyword',
            'lat', 'lng', 'radius', 'type', 'rating', 'fee',
            'min_price', 'max_price', 'time_of_day', 'slot_status'
        ])->some(fn($key) => $request->filled($key));
    
        if (!$hasFilter && (!empty($validated['minLat']) || !empty($validated['maxLat']) || !empty($validated['minLng']) || !empty($validated['maxLng']))) {
            $query->inBounds(
                $validated['minLat'],
                $validated['maxLat'],
                $validated['minLng'],
                $validated['maxLng']
            );
        }
    
        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            $query->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
        }

        $isMap = filter_var($validated['is_map'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
        if ($isMap) {
            $matches = $query->get();
            $paginationMeta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $matches->count(),
                'total' => $matches->count(),
            ];
        } else {
            $matches = $query->paginate($validated['per_page'] ?? MiniMatch::PER_PAGE);
            $paginationMeta = [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
            ];
        }
    
        return ResponseHelper::success(
            ['matches' => MiniMatchResource::collection($matches)],
            'Lấy danh sách Mini Match thành công',
            200,
            $paginationMeta
        );
    }    

    private function getParticipantRating($participant, $sportId)
    {
        if ($participant->type !== 'user') {
            return 0; // team chưa có rating
        }

        // Lấy user_sport
        $userSport = DB::table('user_sport')
            ->where('user_id', $participant->user_id)
            ->where('sport_id', $sportId)
            ->first();

        if (!$userSport) {
            return 0;
        }

        // Lấy score_value = vndupr_score
        return DB::table('user_sport_scores')
            ->where('user_sport_id', $userSport->id)
            ->where('score_type', 'vndupr_score')
            ->value('score_value') ?? 0;
    }
}
