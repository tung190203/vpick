<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreMiniTournamentRequest;
use App\Http\Resources\MiniTournamentResource;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniTournamentController extends Controller
{
    public function store(StoreMiniTournamentRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $miniTournament = MiniTournament::create($data);
        MiniParticipant::create([
            'mini_tournament_id' => $miniTournament->id,
            'type' => 'user',
            'user_id' => Auth::id(),
            'is_confirmed' => true,
        ]);

        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
            $miniTournament->update(['poster' => $posterPath]);
        }
        $miniTournament->loadFullRelations();

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Mini Tournament created successfully', 201);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $query = MiniTournament::withFullRelations();

        if ($request->has('sport_id')) {
            $query->where('sport_id', $validated['sport_id']);
        }

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        $miniTournaments = $query->paginate($validated['per_page'] ?? MiniTournament::PER_PAGE);

        return ResponseHelper::success(MiniTournamentResource::collection($miniTournaments), 'Mini Tournaments retrieved successfully');
    }

    public function show($id)
    {
        $miniTournament = MiniTournament::withFullRelations()->findOrFail($id);

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Mini Tournament retrieved successfully');
    }
    public function update(StoreMiniTournamentRequest $request, $id)
    {
        $miniTournament = MiniTournament::findOrFail($id);
        $data = $request->validated();

        $miniTournament->update($data);

        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
            $miniTournament->update(['poster' => $posterPath]);
        }
        $miniTournament->loadFullRelations();

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Mini Tournament updated successfully');
    }
}
