<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TournamentResource;
use App\Http\Controllers\TournamentTypeController;
use App\Models\Matches;
use App\Models\Tournament;
use App\Models\TournamentStaff;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentController extends Controller
{
    protected $imageService;
    protected $tournamentTypeController;

    public function __construct(
        ImageOptimizationService $imageService,
        TournamentTypeController $tournamentTypeController
    ) {
        $this->imageService = $imageService;
        $this->tournamentTypeController = $tournamentTypeController;
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poster' => 'nullable|image|max:350',
            'sport_id' => 'required|exists:sports,id',
            'name' => 'required|string',
            'competition_location_id' => 'nullable|exists:competition_locations,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'registration_open_at' => 'nullable|date',
            'registration_closed_at' => 'nullable|date',
            'early_registration_deadline' => 'nullable|date',
            'duration' => 'nullable|integer',
            'enable_dupr' => 'nullable|boolean',
            'enable_vndupr' => 'nullable|boolean',
            'min_level' => 'nullable',
            'max_level' => 'nullable',
            'age_group' => 'nullable|in:' . implode(',', Tournament::AGES),
            'gender_policy' => 'nullable|in:' . implode(',', Tournament::GENDER),
            'participant' => 'nullable|in:team,user',
            'max_team' => 'nullable|integer|required_if:participant,team',
            'player_per_team' => 'nullable|integer|required_if:participant,team',
            'max_player' => 'nullable|integer|required_if:participant,user',
            'fee' => 'nullable|in:free,pair',
            'standard_fee_amount' => 'nullable|numeric|required_if:fee,pair',
            'is_private' => 'nullable|boolean',
            'auto_approve' => 'nullable|boolean',
            'description' => 'nullable|string',
            'club_id' => 'nullable|exists:clubs,id',
        ]);

        $tournament = null;

        DB::transaction(function () use ($validated, &$tournament, $request) {
            if ($request->hasFile('poster')) {
                $path = $request->file('poster')->store('tournaments/posters', 'public');
                $validated['poster'] = $path;
            }
            $tournament = Tournament::create([
                ...$validated,
                'created_by' => auth()->id(),
            ]);

            TournamentStaff::create([
                'tournament_id' => $tournament->id,
                'user_id' => auth()->id(),
                'role' => TournamentStaff::ROLE_ORGANIZER,
            ]);
        });

        if ($tournament) {
            $tournament = Tournament::withBasicRelations()->find($tournament->id);
        } else {
            return ResponseHelper::error('Tạo giải đấu thất bại', 500);
        }

        return ResponseHelper::success(new TournamentResource($tournament), 'Tạo giải đấu thành công');
    }

    public function index(Request $request)
    {
        $query = Tournament::withFullRelations();

        if ($request->has('keyword')) {
            $query->search($request->keyword);
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $query->filterByDate($request->start_date, $request->end_date);
        }

        $tournaments = $query->paginate(Tournament::PER_PAGE);

        $data = [
            'tournaments' => TournamentResource::collection($tournaments),
        ];

        $meta = [
            'current_page' => $tournaments->currentPage(),
            'last_page' => $tournaments->lastPage(),
            'per_page' => $tournaments->perPage(),
            'total' => $tournaments->total(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách giải đấu thành công', 200, $meta);
    }

    public function show($id)
    {
        $tournament = Tournament::withFullRelations()->find($id);

        if (!$tournament) {
            return ResponseHelper::error('Giải đấu không tồn tại', 404);
        }

        return ResponseHelper::success(new TournamentResource($tournament), 'Lấy chi tiết giải đấu thành công');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'poster' => 'nullable|image|max:5120',
            'remove_poster' => 'nullable|boolean', // Thêm field này
            'sport_id' => 'nullable|exists:sports,id',
            'name' => 'nullable|string',
            'competition_location_id' => 'nullable|exists:competition_locations,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'registration_open_at' => 'nullable|date',
            'registration_closed_at' => 'nullable|date',
            'early_registration_deadline' => 'nullable|date',
            'duration' => 'nullable|integer',
            'enable_dupr' => 'nullable|boolean',
            'enable_vndupr' => 'nullable|boolean',
            'min_level' => 'nullable',
            'max_level' => 'nullable',
            'age_group' => 'nullable|in:' . implode(',', Tournament::AGES),
            'gender_policy' => 'nullable|in:' . implode(',', Tournament::GENDER),
            'participant' => 'nullable|in:team,user',
            'max_team' => 'nullable|integer|required_if:participant,team',
            'player_per_team' => 'nullable|integer|required_if:participant,team',
            'max_player' => 'nullable|integer|required_if:participant,user',
            'fee' => 'nullable|in:free,pair',
            'standard_fee_amount' => 'nullable|numeric|required_if:fee,pair',
            'is_private' => 'nullable|boolean',
            'auto_approve' => 'nullable|boolean',
            'description' => 'nullable|string',
            'club_id' => 'nullable|exists:clubs,id',
            'is_public_branch' => 'nullable|boolean',
            'is_own_score' => 'nullable|boolean',
            'status' => 'nullable|in:' . implode(',', Tournament::STATUS),
        ]);

        $tournament = Tournament::findOrFail($id);
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền thay đổi giải đấu', 400);
        }

        DB::transaction(function () use ($validated, $tournament, $request) {
            if ($request->hasFile('poster')) {
                $this->imageService->deleteOldImage($tournament->poster);
                $path = $this->imageService->optimize(
                    $validated['poster'],
                    'tournaments/posters'
                );
                $validated['poster'] = $path;
            } elseif ($request->has('remove_poster') && $request->input('remove_poster')) {
                $this->imageService->deleteOldImage($tournament->poster);
                $validated['poster'] = null;
            } else {
                unset($validated['poster']);
            }
            unset($validated['remove_poster']);
            $tournament->fill($validated);
            $tournament->save();
        });

        $tournament = Tournament::withBasicRelations()->find($tournament->id);

        return ResponseHelper::success(new TournamentResource($tournament), 'Cập nhật giải đấu thành công');
    }

    public function destroy(Request $request)
    {
        $tournament = Tournament::find($request->id);

        if (!$tournament) {
            return ResponseHelper::error('Giải đấu không tồn tại', 404);
        }

        $hasCompletedMatch = Matches::whereHas('tournamentType', function ($q) use ($tournament) {
            $q->where('tournament_id', $tournament->id);
        })
        ->where('status', Matches::STATUS_COMPLETED)
        ->exists();

        if ($hasCompletedMatch) {
            return ResponseHelper::error(
                'Không thể huỷ bỏ giải. Đã có trận đấu hoàn thành thuộc giải này.',
                400
            );
        }

        DB::transaction(function () use ($tournament) {
            $tournament->delete();
        });

        return ResponseHelper::success(null, 'Xoá giải đấu thành công');
    }

    /**
     * Lấy bracket cho tournament với cấu trúc mới
     * Trả về: poolStage, leftSide, rightSide, finalMatch, thirdPlaceMatch
     *
     * Route: GET /api/tournaments/{id}/bracket
     * Alias: GET /api/tournament-detail/{id}/bracket (backward compatible)
     *
     * @param int $id Tournament ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBracket($id)
    {
        try {
            $tournament = Tournament::with('tournamentTypes')->find($id);

            if (!$tournament) {
                return ResponseHelper::error('Giải đấu không tồn tại', 404);
            }

            $tournamentType = $tournament->tournamentTypes->first();

            if (!$tournamentType) {
                return ResponseHelper::error('Giải đấu chưa có tournament type', 404);
            }

            // Sử dụng getBracketNew cho format Mixed, getBracket cho các format khác
            if ($tournamentType->format === \App\Models\TournamentType::FORMAT_MIXED) {
                return $this->tournamentTypeController->getBracketNew($tournamentType);
            } else {
                return $this->tournamentTypeController->getBracket($tournamentType);
            }
        } catch (\Throwable $e) {
            Log::error('Error in getBracket', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Lỗi khi lấy bracket: ' . $e->getMessage(), 500);
        }
    }
}
