<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use App\Models\TournamentStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TournamentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poster' => 'nullable|image|max:350',
            'sport_id' => 'required|exists:sports,id',
            'name' => 'required|string',
            'location' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'registration_open_at' => 'nullable|date',
            'registration_closed_at' => 'nullable|date',
            'early_registration_deadline' => 'nullable|date',
            'duration' => 'nullable|integer',
            'min_level' => 'nullable|integer',
            'max_level' => 'nullable|integer',
            'age_group' => 'nullable|in:' . implode(',', Tournament::AGES),
            'gender_policy' => 'nullable|in:' . implode(',', Tournament::GENDER),
            'participant' => 'nullable|in:team,user',
            'max_team' => 'nullable|integer|required_if:participant,team',
            'min_player_per_team' => 'nullable|integer|required_if:participant,team',
            'max_player_per_team' => 'nullable|integer|required_if:participant,team',
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
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
                'last_page' => $tournaments->lastPage(),
            ],
        ];

        return ResponseHelper::success($data, 'Lấy danh sách giải đấu thành công');
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
            'poster' => 'nullable|image|max:350',
            'sport_id' => 'sometime|exists:sports,id',
            'name' => 'sometimes|required|string',
            'location' => 'sometimes|nullable|string',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'registration_open_at' => 'sometimes|nullable|date',
            'registration_closed_at' => 'sometimes|nullable|date',
            'early_registration_deadline' => 'sometimes|nullable|date',
            'duration' => 'sometimes|nullable|integer',
            'min_level' => 'sometimes|nullable|integer',
            'max_level' => 'sometimes|nullable|integer',
            'age_group' => 'sometimes|nullable|in:' . implode(',', Tournament::AGES),
            'gender_policy' => 'sometimes|nullable|in:' . implode(',', Tournament::GENDER),
            'participant' => 'sometimes|nullable|in:team,user',
            'max_team' => 'sometimes|nullable|integer|required_if:participant,team',
            'min_player_per_team' => 'sometimes|nullable|integer|required_if:participant,team',
            'max_player_per_team' => 'sometimes|nullable|integer|required_if:participant,team',
            'max_player' => 'sometimes|nullable|integer|required_if:participant,user',
            'fee' => 'sometimes|nullable|in:free,pair',
            'standard_fee_amount' => 'sometimes|nullable|numeric|required_if:fee,pair',
            'is_private' => 'sometimes|nullable|boolean',
            'auto_approve' => 'sometimes|nullable|boolean',
            'description' => 'sometimes|nullable|string',
            'club_id' => 'sometimes|nullable|exists:clubs,id',
        ]);
    
        $tournament = Tournament::findOrFail($id);
    
        DB::transaction(function () use ($validated, $tournament, $request) {
            if ($request->hasFile('poster')) {
                if ($tournament->poster && Storage::disk('public')->exists($tournament->poster)) {
                    Storage::disk('public')->delete($tournament->poster);
                }
                $path = $request->file('poster')->store('tournaments/posters', 'public');
                $validated['poster'] = $path;
            }
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

        DB::transaction(function () use ($tournament) {
            $tournament->tournamentTypes()->each(function ($type) {
                $type->groups()->each(function ($group) {
                    $group->matches()->delete();
                    $group->delete();
                });
                $type->delete();
            });
            $tournament->delete();
        });

        return ResponseHelper::success(null, 'Xoá giải đấu thành công');
    }
}
