<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MiniTournamentResource;
use App\Models\MiniTournament;
use App\Models\Tournament;
use Illuminate\Http\Request;

class MapController extends Controller
{
    private const VALIDATION_RULE = 'sometimes';
    public function  getMatch(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'sometimes',
            'lng' => 'sometimes',
            'radius' => 'sometimes|numeric|min:1',
            'minLat' => self::VALIDATION_RULE,
            'maxLat' => self::VALIDATION_RULE,
            'minLng' => self::VALIDATION_RULE,
            'maxLng' => self::VALIDATION_RULE,
            'mini_tournament_per_page' => 'sometimes|integer|min:1|max:200',
            'tournament_per_page' => 'sometimes|integer|min:1|max:200',
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
        $miniTournamentQuery = MiniTournament::withFullRelations()->filter($validated);
        $tournamentQuery = Tournament::withFullRelations()->filter($validated);
        $hasMiniTournamentFilter = collect([
            'sport_id',
            'location_id',
            'date_from',
            'keyword',
            'lat',
            'lng',
            'radius',
            'type',
            'rating',
            'fee',
            'min_price',
            'max_price',
            'time_of_day',
            'slot_status',
        ])->some(fn($key) =>$request->filled($key));
        $hasTournamentFilter = collect([
            'sport_id',
            'location_id',
            'date_from',
            'keyword',
            'lat',
            'lng',
            'radius',
            'rating',
            'fee',
            'min_price',
            'max_price',
            'time_of_day',
            'slot_status',
        ])->some(fn($key) =>$request->filled($key));
        if(!$hasMiniTournamentFilter && (!empty($validated['minLat']) || !empty($validated['maxLat']) || !empty($validated['minLng']) || !empty($validated['maxLng']))){
           $miniTournamentQuery->inBounds(
                $validated['minLat'] ?? null,
                $validated['maxLat'] ?? null,
                $validated['minLng'] ?? null,
                $validated['maxLng'] ?? null,
            );
        }
        if(!$hasTournamentFilter && (!empty($validated['minLat']) || !empty($validated['maxLat']) || !empty($validated['minLng']) || !empty($validated['maxLng']))){
            $tournamentQuery->inBounds(
                 $validated['minLat'] ?? null,
                 $validated['maxLat'] ?? null,
                 $validated['minLng'] ?? null,
                 $validated['maxLng'] ?? null,
             );
         }
        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            $miniTournamentQuery->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
            $tournamentQuery->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
        }
        $isMap = filter_var($validated['is_map'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if($isMap) {
            $miniTournament = $miniTournamentQuery->get();
            $miniTournamentMeta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $miniTournament->count(),
                'total' => $miniTournament->count(),
            ];
            $miniTournamentData = [
                'data' => MiniTournamentResource::collection($miniTournament),
                'meta' => $miniTournamentMeta,
            ];
            $tournament = $tournamentQuery->get();
            $tournamentMeta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $tournament->count(),
                'total' => $tournament->count(),
            ];
            $tournamentData = [
                'data' => MiniTournamentResource::collection($tournament),
                'meta' => $tournamentMeta,
            ];
        } else {
            $miniTournament = $miniTournamentQuery->paginate($validated['mini_tournament_per_page'] ?? MiniTournament::PER_PAGE);
            $miniTournamentMeta = [
                'current_page' => $miniTournament->currentPage(),
                'last_page' => $miniTournament->lastPage(),
                'per_page' => $miniTournament->perPage(),
                'total' => $miniTournament->total(),
            ];
            $miniTournamentData = [
                'data' => MiniTournamentResource::collection($miniTournament->items()),
                'meta' => $miniTournamentMeta,
            ];
            $tournament = $tournamentQuery->paginate($validated['tournament_per_page'] ?? Tournament::PER_PAGE);
            $tournamentMeta = [
                'current_page' => $tournament->currentPage(),
                'last_page' => $tournament->lastPage(),
                'per_page' => $tournament->perPage(),
                'total' => $tournament->total(),
            ];
            $tournamentData = [
                'data' => MiniTournamentResource::collection($tournament->items()),
                'meta' => $tournamentMeta,
            ];
        }
        $data = [
            'mini_tournaments' => $miniTournamentData,
            'tournaments' => $tournamentData,
        ];

        return ResponseHelper::success($data, 'Lấy dữ liệu thành công', 200);
    }
}
