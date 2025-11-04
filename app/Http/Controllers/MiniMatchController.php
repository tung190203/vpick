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
use App\Notifications\MiniMatchCreatedNotification;
use App\Notifications\MiniMatchResultConfirmedNotification;
use App\Notifications\MiniMatchUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'per_page' => 'nullable|integer|min:1|max:100',
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
                $q->whereHas('participant1', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->orWhereHas('team.members', fn($m) => $m->where('user_id', $userId));
                })->orWhereHas('participant2', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)
                        ->orWhereHas('team.members', fn($m) => $m->where('user_id', $userId));
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
     * Tạo trận đấu mới
     * participants nếu là int => user
     * truyền mảng lên là team
     */
    public function store(Request $request, $miniTournamentId)
    {
        $validated = $request->validate([
            'round' => 'nullable|string',
            'participant1_id' => 'sometimes',
            'participant2_id' => 'sometimes',
            'scheduled_at' => 'nullable|date',
            'referee' => 'nullable|exists:referees,id',
            'team1_name' => 'nullable|string|max:255',
            'team2_name' => 'nullable|string|max:255',
            'yard_number' => 'nullable|string|max:50',
            'name_of_match' => 'nullable|string|max:255',
        ]);

        $miniTournament = MiniTournament::with('staff')->findOrFail($miniTournamentId);
        $isOrganizer = $miniTournament->hasOrganizer(Auth::id());

        if (!$isOrganizer) {
            return ResponseHelper::error('Người dùng không có quyền tạo trận đấu trong giải đấu này', 403);
        }

        // xử lý participant (có thể là id hoặc array user_id[])
        $p1 = $this->resolveParticipant(
            $validated['participant1_id'] ?? null,
            $miniTournament->id,
            null,
            $validated['team1_name'] ?? null
        );

        $p2 = $this->resolveParticipant(
            $validated['participant2_id'] ?? null,
            $miniTournament->id,
            null,
            $validated['team2_name'] ?? null
        );

        // check trùng nhau
        if ($p1 && $p2) {
            if ($p1->id === $p2->id) {
                return ResponseHelper::error('Người chơi không được trùng nhau', 400);
            }

            $matches = MiniMatch::where('mini_tournament_id', $miniTournament->id)
                ->where(function ($query) use ($p1, $p2) {
                    $query->where(function ($q) use ($p1, $p2) {
                        $q->where('participant1_id', $p1->id)->where('participant2_id', $p2->id);
                    })->orWhere(function ($q) use ($p1, $p2) {
                        $q->where('participant1_id', $p2->id)->where('participant2_id', $p1->id);
                    });
                })
                ->first();

            if ($matches) {
                return ResponseHelper::error('Trận đấu giữa hai người chơi này đã tồn tại', 400);
            }
        }

        $matchCount = MiniMatch::where('mini_tournament_id', $miniTournament->id)->count();
        $defaultMatchName = 'Trận đấu số ' . ($matchCount + 1);
        // tạo trận đấu
        $match = MiniMatch::create([
            'mini_tournament_id' => $miniTournament->id,
            'participant1_id' => $p1?->id,
            'participant2_id' => $p2?->id,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'referee_id' => $validated['referee'] ?? null,
            'status' => MiniMatch::STATUS_PENDING,
            'round' => $validated['round'] ?? null,
            'yard_number' => $validated['yard_number'] ?? null,
            'name_of_match' => $validated['name_of_match'] ?? $defaultMatchName,
        ]);

        $match = MiniMatch::withFullRelations()->findOrFail($match->id);

        $participants = [$p1, $p2];

        foreach ($participants as $participant) {
            if (!$participant) continue;

            if ($participant->type === 'user' && $participant->user) {
                $participant->user->notify(new MiniMatchCreatedNotification($match));
            }

            if ($participant->type === 'team' && $participant->team) {
                // Gửi notification cho tất cả thành viên team
                foreach ($participant->team->members as $member) {
                    $member->notify(new MiniMatchCreatedNotification($match));
                }
            }
        }

        return ResponseHelper::success(new MiniMatchResource($match), 'Tạo trận đấu thành công');
    }

    /**
     * Giải quyết participant từ input (chỉ nhận user_id hoặc mảng user_id[])
     * - Nếu input là số -> coi là user đơn (participant type = user)
     * - Nếu input là mảng có 1 phần tử -> cũng coi là user đơn
     * - Nếu input là mảng >1 -> coi là team (participant type = team)
     *
     * Trả về MiniParticipant hoặc null
     */
    protected function resolveParticipant($input, $miniTournamentId, $currentParticipant = null, $teamName = null)
    {
        if (empty($input)) {
            return null;
        }

        // Nếu input là số (user đơn)
        if (!is_array($input)) {
            return MiniParticipant::firstOrCreate(
                [
                    'mini_tournament_id' => $miniTournamentId,
                    'type' => 'user',
                    'user_id' => (int) $input,
                ],
                ['is_confirmed' => true]
            );
        }

        // Nếu input là array -> LUÔN coi là team
        $userIds = collect($input)->map(fn($i) => (int) $i)->unique()->sort()->values()->all();

        // Nếu currentParticipant là team -> update members + tên team
        if ($currentParticipant && $currentParticipant->type === 'team') {
            $team = $currentParticipant->team;

            foreach ($userIds as $uid) {
                MiniTeamMember::firstOrCreate(['mini_team_id' => $team->id, 'user_id' => $uid]);
            }
            // Update tên team nếu có truyền vào
            if (!empty($teamName)) {
                $team->update(['name' => $teamName]);
            }
            return $currentParticipant;
        }

        // Tìm team có đúng danh sách userIds
        $existingTeam = MiniTeam::where('mini_tournament_id', $miniTournamentId)
            ->whereHas('members', function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds);
            }, '=', count($userIds))
            ->whereDoesntHave('members', function ($q) use ($userIds) {
                $q->whereNotIn('user_id', $userIds);
            })
            ->first();

        if ($existingTeam) {
            // Nếu có team rồi nhưng user truyền name -> update name
            if ($teamName) {
                $existingTeam->update(['name' => $teamName]);
            }
        } else {
            // Nếu chưa có team -> tạo mới
            $existingTeam = MiniTeam::create([
                'mini_tournament_id' => $miniTournamentId,
                'name' => $teamName ?: 'Team ' . Str::random(5),
            ]);
            foreach ($userIds as $uid) {
                $existingTeam->members()->create(['user_id' => $uid]);
            }
        }

        return MiniParticipant::firstOrCreate(
            [
                'mini_tournament_id' => $miniTournamentId,
                'type' => 'team',
                'team_id' => $existingTeam->id,
            ],
            ['is_confirmed' => true]
        );
    }




    /**
     * Cập nhật thông tin trận đấu trong kèo đấu
     */

    public function update(Request $request, $matchId)
    {
        $validated = $request->validate([
            'round' => 'nullable|string',
            'participant1_id' => 'sometimes',
            'participant2_id' => 'sometimes',
            'team1_name' => 'nullable|string|max:255',
            'team2_name' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date',
            'referee' => 'nullable|exists:referees,id',
            'yard_number' => 'nullable|string|max:50',
            'name_of_match' => 'nullable|string|max:255',
        ]);

        $match = MiniMatch::with(['miniTournament', 'participant1', 'participant2'])->findOrFail($matchId);
        $miniTournament = $match->miniTournament->load('staff');
        $isOrganizer = $miniTournament->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Người dùng không có quyền sửa trận đấu trong giải đấu này', 403);
        }

        // Xử lý participant 1
        $p1 = array_key_exists('participant1_id', $validated)
            ? $this->resolveParticipant(
                $validated['participant1_id'],
                $miniTournament->id,
                $match->participant1,
                $validated['team1_name'] ?? null
            )
            : $match->participant1;

        // Xử lý participant 2
        $p2 = array_key_exists('participant2_id', $validated)
            ? $this->resolveParticipant(
                $validated['participant2_id'],
                $miniTournament->id,
                $match->participant2,
                $validated['team2_name'] ?? null
            )
            : $match->participant2;

        if ($p1 && $p2 && $p1->id === $p2->id) {
            return ResponseHelper::error('Người chơi không được trùng nhau', 400);
        }

        $exists = MiniMatch::where('mini_tournament_id', $miniTournament->id)
            ->where(function ($query) use ($p1, $p2) {
                $query->where(function ($q) use ($p1, $p2) {
                    $q->where('participant1_id', $p1->id)->where('participant2_id', $p2->id);
                })->orWhere(function ($q) use ($p1, $p2) {
                    $q->where('participant1_id', $p2->id)->where('participant2_id', $p1->id);
                });
            })
            ->where('id', '!=', $match->id)
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Trận đấu giữa hai người chơi này đã tồn tại', 400);
        }

        $match->update([
            'participant1_id' => $p1?->id,
            'participant2_id' => $p2?->id,
            'scheduled_at' => $validated['scheduled_at'] ?? $match->scheduled_at,
            'referee_id' => $validated['referee'] ?? $match->referee_id,
            'round' => $validated['round'] ?? $match->round,
            'yard_number' => $validated['yard_number'] ?? $match->yard_number,
            'name_of_match' => $validated['name_of_match'] ?? $match->name_of_match,
        ]);

        $match = MiniMatch::withFullRelations()->findOrFail($match->id);

        $participants = [$p1, $p2];

        foreach ($participants as $participant) {
            if (!$participant) continue;

            if ($participant->type === 'user' && $participant->user) {
                $participant->user->notify(new MiniMatchUpdatedNotification($match));
            }

            if ($participant->type === 'team' && $participant->team) {
                foreach ($participant->team->members as $member) {
                    $member->notify(new MiniMatchUpdatedNotification($match));
                }
            }
        }

        return ResponseHelper::success(new MiniMatchResource($match), 'Cập nhật trận đấu thành công');
    }

    /**
     * Thêm hoặc cập nhật kết quả 1 hiệp (set)
     */
    public function addSetResult(Request $request, $matchId)
    {
        $validated = $request->validate([
            'set_number' => 'required|integer|min:1',
            'results' => 'required|array|min:2',
            'results.*.participant_id' => 'required|exists:mini_participants,id',
            'results.*.score' => 'required|integer|min:0',
        ]);

        $match = MiniMatch::with('miniTournament')->findOrFail($matchId);
        $tournament = $match->miniTournament->load('staff');
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Người dùng không có quyền thêm kết quả trận đấu trong giải đấu này', 403);
        }

        if (!empty($tournament->set_number) && $validated['set_number'] > $tournament->set_number) {
            return ResponseHelper::error("Trận đấu không được vượt quá {$tournament->set_number} set", 400);
        }

        if (!empty($tournament->games_per_set)) {
            foreach ($validated['results'] as $res) {
                if ($res['score'] > $tournament->games_per_set) {
                    return ResponseHelper::error("Điểm số không được vượt quá {$tournament->games_per_set} trong một set", 400);
                }
            }
        }

        // đảm bảo participant hợp lệ
        $participantIds = [$match->participant1_id, $match->participant2_id];
        foreach ($validated['results'] as $res) {
            if (!in_array($res['participant_id'], $participantIds)) {
                return ResponseHelper::error('Người chơi không hợp lệ', 400);
            }
        }

        // xóa nếu đã tồn tại set_number (update lại)
        MiniMatchResult::where('mini_match_id', $match->id)
            ->where('set_number', $validated['set_number'])
            ->delete();

        // lưu kết quả mới
        foreach ($validated['results'] as $res) {
            MiniMatchResult::create([
                'mini_match_id' => $match->id,
                'participant_id' => $res['participant_id'],
                'score' => $res['score'],
                'set_number' => $validated['set_number'],
            ]);
        }

        // xác định người thắng set
        $setResults = MiniMatchResult::where('mini_match_id', $match->id)
            ->where('set_number', $validated['set_number'])
            ->get();

        $maxScore = $setResults->max('score');
        foreach ($setResults as $r) {
            $r->won_set = $r->score === $maxScore;
            $r->save();
        }

        $match = MiniMatch::withFullRelations()->findOrFail($matchId);

        return ResponseHelper::success(new MiniMatchResource($match), 'Thành công');
    }

    /**
     * Xóa kết quả 1 hiệp
     */
    public function deleteSetResult($matchId, $setNumber)
    {
        $match = MiniMatch::with('miniTournament')->findOrFail($matchId);
        $tournament = $match->miniTournament->load('staff');
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Người dùng không có quyền xóa kết quả trận đấu trong giải đấu này', 403);
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
            $isOrganizer = $tournament->hasOrganizer(Auth::id());
            if (!$isOrganizer) {
                return ResponseHelper::error("Bạn không có quyền xóa trận đấu", 403);
            }
            if ($match->status === MiniMatch::STATUS_COMPLETED) {
                return ResponseHelper::error("Không thể xóa trận đấu đã xác nhận kết quả", 400);
            }
        }

        MiniMatchResult::whereIn('mini_match_id', $ids)->delete();
        MiniMatch::whereIn('id', $ids)->delete();

        return ResponseHelper::success(null, 'Các trận đấu đã được xóa');
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
        $match = MiniMatch::with('results.participant.user')
            ->findOrFail($matchId);
        $tournament = $match->miniTournament->load('staff');
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        $participantIds = [$match->participant1_id, $match->participant2_id];

        $userParticipant = MiniParticipant::whereIn('id', $participantIds)
            ->where(function ($q) {
                $q->where(fn($sub) => $sub->where('type', 'user')->where('user_id', Auth::id()))
                    ->orWhereHas('team.members', fn($sub) => $sub->where('user_id', Auth::id()));
            })
            ->first();

        if (!$userParticipant && !$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền xác nhận kết quả trận đấu này', 403);
        }

        if ($match->status === MiniMatch::STATUS_COMPLETED) {
            return ResponseHelper::error('Kết quả trận đấu đã được xác nhận trước đó', 400);
        }

        if ($userParticipant && $userParticipant->id == $match->participant1_id) {
            $match->participant1_confirm = true;
        } elseif ($userParticipant && $userParticipant->id == $match->participant2_id) {
            $match->participant2_confirm = true;
        } elseif ($isOrganizer) {
            $match->participant1_confirm = true;
            $match->participant2_confirm = true;
        }        

        if ($match->participant1_confirm && $match->participant2_confirm) {
            $wins = $match->results->groupBy('participant_id')->map(function ($results) {
                return $results->where('won_set', true)->count();
            });
            $winners = $wins->filter(fn($count) => $count === $wins->max())->keys();
            $match->participant_win_id = $winners->count() === 1 ? $winners->first() : null;
            $match->status = MiniMatch::STATUS_COMPLETED;
        }

        $match->save();

        // Xác định đối thủ cần nhận noti
        $recipientUserIds = collect();

        if ($userParticipant) {
            $opponentParticipant = $userParticipant->id == $match->participant1_id
                ? $match->participant2
                : $match->participant1;

            if ($opponentParticipant->type === 'user') {
                $recipientUserIds->push($opponentParticipant->user_id);
            } elseif ($opponentParticipant->type === 'team') {
                $recipientUserIds = $recipientUserIds->merge($opponentParticipant->team->members->pluck('user_id'));
            }
        }

        if ($isOrganizer) {
            foreach ([$match->participant1, $match->participant2] as $participant) {
                if ($participant->type === 'user') {
                    $recipientUserIds->push($participant->user_id);
                } elseif ($participant->type === 'team') {
                    $recipientUserIds = $recipientUserIds->merge($participant->team->members->pluck('user_id'));
                }
            }
        }

        // Loại bỏ chính user vừa xác nhận
        $recipientUserIds = $recipientUserIds->unique()->reject(fn($id) => $id == Auth::id());

        foreach ($recipientUserIds as $uid) {
            $user = User::find($uid);
            if ($user) {
                $user->notify(new MiniMatchResultConfirmedNotification($match));
            }
        }

        return ResponseHelper::success(new MiniMatchResource($match->fresh('results.participant.user')), 'Xác nhận kết quả thành công');
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
            'per_page' => 'sometimes|integer|min:1|max:100',
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
}
