<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TournamentResource;
use App\Models\Participant;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'location' => 'nullable|string',
            'level' => 'required|in:local,provincial,national',
            'description' => 'nullable|string',
            'tournament_types' => 'required|array',
            'tournament_types.*.type' => 'required|in:single,double,mixed',
            'tournament_types.*.description' => 'nullable|string',
            'tournament_types.*.groups' => 'array',
            'tournament_types.*.groups.*.name' => 'required|string',
            'tournament_types.*.groups.*.matches' => 'array',
            'club_id' => 'nullable|exists:clubs,id',
        ]);

        $tournament = null;

        DB::transaction(function () use ($validated, &$tournament) {
            // 1. Tạo giải
            $tournament = Tournament::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'location' => $validated['location'] ?? null,
                'level' => $validated['level'],
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
                'club_id' => $validated['club_id'] ?? null,
            ]);

            // 2. Tạo thể thức
            foreach ($validated['tournament_types'] as $typeData) {
                $type = $tournament->tournamentTypes()->create([
                    'type' => $typeData['type'],
                    'description' => $typeData['description'] ?? null,
                ]);

                // 3. Tạo bảng
                if (!empty($typeData['groups'])) {
                    foreach ($typeData['groups'] as $groupData) {
                        $group = $type->groups()->create([
                            'name' => $groupData['name'],
                        ]);

                        // 4. Nếu có matches -> tạo participants + matches
                        if (!empty($groupData['matches'])) {
                            foreach ($groupData['matches'] as $matchData) {

                                // Xử lý participant1
                                if (in_array($type->type, ['double', 'mixed'])) {
                                    // Team participant
                                    $team1 = Team::create([
                                        'name' => $matchData['participant1']['name'],
                                        'tournament_type_id' => $type->id,
                                    ]);
                                    $team1->members()->attach($matchData['participant1']['members']);

                                    $p1 = Participant::create([
                                        'tournament_type_id' => $type->id,
                                        'type' => 'team',
                                        'team_id' => $team1->id,
                                        'is_confirmed' => true,
                                    ]);
                                } else {
                                    // User participant
                                    $p1 = Participant::firstOrCreate([
                                        'user_id' => $matchData['participant1_id'],
                                        'tournament_type_id' => $type->id,
                                        'type' => 'user',
                                    ], [
                                        'is_confirmed' => true,
                                    ]);
                                }

                                // Xử lý participant2
                                if (in_array($type->type, ['double', 'mixed'])) {
                                    $team2 = Team::create([
                                        'name' => $matchData['participant2']['name'],
                                        'tournament_type_id' => $type->id,
                                    ]);
                                    $team2->members()->attach($matchData['participant2']['members']);

                                    $p2 = Participant::create([
                                        'tournament_type_id' => $type->id,
                                        'type' => 'team',
                                        'team_id' => $team2->id,
                                        'is_confirmed' => true,
                                    ]);
                                } else {
                                    $p2 = Participant::firstOrCreate([
                                        'user_id' => $matchData['participant2_id'],
                                        'tournament_type_id' => $type->id,
                                        'type' => 'user',
                                    ], [
                                        'is_confirmed' => true,
                                    ]);
                                }

                                // 5. Tạo match
                                $group->matches()->create([
                                    'participant1_id' => $p1->id,
                                    'participant2_id' => $p2->id,
                                    'scheduled_at' => $matchData['scheduled_at'] ?? null,
                                    'status' => 'pending',
                                ]);
                            }
                        }
                    }
                }
            }
        });

        if ($tournament) {
            $tournament = Tournament::withFullRelations()->find($tournament->id);
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

        if ($request->has('status')) {
            $query->filterByStatus($request->status);
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
        $tournament = Tournament::with('tournamentTypes.groups.matches')->find($id);
    
        if (!$tournament) {
            return ResponseHelper::error('Giải đấu không tồn tại', 404);
        }
    
        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'location' => 'nullable|string',
            'level' => 'required|in:local,provincial,national',
            'description' => 'nullable|string',
            'club_id' => 'nullable|exists:clubs,id',
            'tournament_types' => 'array',
            'tournament_types.*.id' => 'nullable|exists:tournament_types,id',
            'tournament_types.*.type' => 'required|in:single,double,mixed',
            'tournament_types.*.description' => 'nullable|string',
            'tournament_types.*.groups' => 'array',
            'tournament_types.*.groups.*.id' => 'nullable|exists:groups,id',
            'tournament_types.*.groups.*.name' => 'required|string',
            'tournament_types.*.groups.*.matches' => 'array',
            'tournament_types.*.groups.*.matches.*.id' => 'nullable|exists:matches,id',
        ]);
    
        DB::transaction(function () use (&$tournament, $validated) {

            $tournament->update([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'location' => $validated['location'] ?? null,
                'level' => $validated['level'],
                'description' => $validated['description'] ?? null,
                'club_id' => $validated['club_id'] ?? null,
            ]);
    
            if (!empty($validated['tournament_types'])) {
                $typeIds = [];
                foreach ($validated['tournament_types'] as $typeData) {

                    $type = !empty($typeData['id'])
                        ? $tournament->tournamentTypes()->find($typeData['id'])
                        : $tournament->tournamentTypes()->make();
    
                    $type->fill([
                        'type' => $typeData['type'],
                        'description' => $typeData['description'] ?? null,
                    ]);
                    $tournament->tournamentTypes()->save($type);
                    $typeIds[] = $type->id;

                    if (!empty($typeData['groups'])) {
                        $groupIds = [];
                        foreach ($typeData['groups'] as $groupData) {
                            $group = !empty($groupData['id'])
                                ? $type->groups()->find($groupData['id'])
                                : $type->groups()->make();
    
                            $group->fill([
                                'name' => $groupData['name'],
                            ]);
                            $type->groups()->save($group);
                            $groupIds[] = $group->id;

                            if (!empty($groupData['matches'])) {
                                $matchIds = [];
                                foreach ($groupData['matches'] as $matchData) {
                                    $match = !empty($matchData['id'])
                                        ? $group->matches()->find($matchData['id'])
                                        : $group->matches()->make();
    
                                    $match->fill([
                                        'participant1_id' => $matchData['participant1_id'],
                                        'participant2_id' => $matchData['participant2_id'],
                                        'scheduled_at' => $matchData['scheduled_at'] ?? null,
                                        'status' => $matchData['status'] ?? 'pending',
                                    ]);
                                    $group->matches()->save($match);
                                    $matchIds[] = $match->id;
                                }
    
                                $group->matches()->whereNotIn('id', $matchIds)->delete();
                            }
                        }
                    
                        $type->groups()->whereNotIn('id', $groupIds)->delete();
                    }
                }

                $tournament->tournamentTypes()->whereNotIn('id', $typeIds)->delete();
            }
        });
    
        $tournament->load('club', 'createdBy', 'tournamentTypes.groups.matches');    

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
