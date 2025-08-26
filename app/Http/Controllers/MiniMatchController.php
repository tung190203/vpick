<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MiniMatchResource;
use App\Models\MiniMatch;
use App\Models\MiniMatchResult;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniMatchController extends Controller
{
    /**
     * Lấy danh sách trận đấu trong mini tournament (theo vòng, thời gian, lọc theo người chơi)
     */
    public function index(Request $request, $miniTournamentId)
    {
        $request->validate([
            'only_my_matches' => 'nullable|boolean',
            'per_page' => 'nullable|integer',
        ]);

        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        $query = MiniMatch::withFullRelations()
            ->where('mini_tournament_id', $miniTournament->id)
            ->orderBy('round')
            ->orderBy('scheduled_at');

        if ($request->boolean('only_my_matches')) {
            $query->where(function ($q) {
                $q->whereHas('participant1', function ($sub) {
                    $sub->where('user_id', Auth::id());
                })->orWhereHas('participant2', function ($sub) {
                    $sub->where('user_id', Auth::id());
                });
            });
        }

        $matches = $query->paginate($request->input('per_page', MiniMatch::PER_PAGE));

        return ResponseHelper::success(MiniMatchResource::collection($matches));
    }

    /**
     * Tạo trận đấu mới
     */
    public function store(Request $request, $miniTournamentId)
    {
        $validated = $request->validate([
            'round' => 'nullable|string',
            'participant1_id' => 'required|exists:mini_participants,id',
            'participant2_id' => 'required|exists:mini_participants,id',
            'scheduled_at' => 'nullable|date',
            'referee' => 'nullable|exists:referees,id',
        ]);

        $miniTournament = MiniTournament::findOrFail($miniTournamentId);

        if ((int) $miniTournament->created_by !== (int) Auth::id()) {
            return ResponseHelper::error('Người dùng không có quyền tạo trận đấu trong giải đấu này', 403);
        }

        // check participants
        $p1 = MiniParticipant::where('mini_tournament_id', $miniTournament->id)
            ->where('is_confirmed', true)
            ->findOrFail($validated['participant1_id']);

        $p2 = MiniParticipant::where('mini_tournament_id', $miniTournament->id)
            ->where('is_confirmed', true)
            ->findOrFail($validated['participant2_id']);

        if ($p1->id === $p2->id) {
            return ResponseHelper::error('Người chơi không được trùng nhau', 400);
        }

        // check trùng trận
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

        // tạo trận
        $match = MiniMatch::create([
            'mini_tournament_id' => $miniTournament->id,
            'participant1_id' => $p1->id,
            'participant2_id' => $p2->id,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'referee_id' => $validated['referee'] ?? null,
            'status' => MiniMatch::STATUS_PENDING,
            'round' => $validated['round'] ?? null,
        ]);

        $match = MiniMatch::withFullRelations()->findOrFail($match->id);

        return ResponseHelper::success(new MiniMatchResource($match), 'Tạo trận đấu thành công');
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
        $tournament = $match->miniTournament;

        // chỉ organizer mới được thêm/cập nhật set
        if ((int) $tournament->created_by !== (int) Auth::id()) {
            return ResponseHelper::error('Người dùng không có quyền thực thi', 403);
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
        $tournament = $match->miniTournament;

        if ((int) $tournament->created_by !== (int) Auth::id()) {
            return ResponseHelper::error('Người dùng không có quyền thực thi', 403);
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
            $tournament = $match->miniTournament;
            if ((int) $tournament->created_by !== (int) Auth::id()) {
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

        $participantIds = [$match->participant1_id, $match->participant2_id];

        $userParticipant = MiniParticipant::whereIn('id', $participantIds)
            ->where(function ($q) {
                $q->where(fn($sub) => $sub->where('type', 'user')->where('user_id', Auth::id()))
                    ->orWhereHas('team.members', fn($sub) => $sub->where('user_id', Auth::id()));
            })
            ->first();

        if (!$userParticipant) {
            return ResponseHelper::error('Bạn không có quyền xác nhận kết quả trận đấu này', 403);
        }

        if ($match->status === MiniMatch::STATUS_COMPLETED) {
            return ResponseHelper::error('Kết quả trận đấu đã được xác nhận trước đó', 400);
        }

        if ($userParticipant->id == $match->participant1_id) {
            $match->participant1_confirm = true;
        } elseif ($userParticipant->id == $match->participant2_id) {
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

        return ResponseHelper::success(new MiniMatchResource($match->fresh('results.participant.user')), 'Xác nhận kết quả thành công');
    }
}
