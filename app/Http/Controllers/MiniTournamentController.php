<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreMiniTournamentRequest;
use App\Http\Resources\MiniTournamentResource;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use App\Models\User;
use App\Notifications\MiniTournamentInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiniTournamentController extends Controller
{
    /**
     * tạo mini tournament
     */
    public function store(StoreMiniTournamentRequest $request)
    {
        $data = $request->safe()->except(['invite_user']);

        $miniTournament = MiniTournament::create($data);
        $miniTournament->staff()->attach(Auth::id(), ['role' => MiniTournamentStaff::ROLE_ORGANIZER]);

        if ($data['role_type'] !== MiniTournament::ROLE_ORGANIZER) {
            MiniParticipant::create([
                'mini_tournament_id' => $miniTournament->id,
                'type' => 'user',
                'user_id' => Auth::id(),
                'is_confirmed' => true,
            ]);
        }

        if( $request->has('invite_user') ) {
            $inviteUsers = $request->input('invite_user', []);
            foreach ($inviteUsers as $userId) {
                MiniParticipant::create([
                    'mini_tournament_id' => $miniTournament->id,
                    'type' => 'user',
                    'user_id' => $userId,
                    'is_confirmed' => true,
                ]);
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new MiniTournamentInvitationNotification($miniTournament));
                }
            }  
        }

        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
            $miniTournament->update(['poster' => $posterPath]);
        }
        $miniTournament->loadFullRelations();

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Mini Tournament created successfully', 201);
    }
    /**
     * danh sách mini tournament
     */
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

        $userId = auth()->id();
        $query->where(function ($q) use ($userId) {
            $q->where('is_private', 0)
                ->orWhereHas('participants', fn($sub) => $sub->where('user_id', $userId));
        });
        $miniTournaments = $query->paginate($validated['per_page'] ?? MiniTournament::PER_PAGE);

        return ResponseHelper::success(MiniTournamentResource::collection($miniTournaments), 'Mini Tournaments retrieved successfully');
    }
    /**
     * chi tiết mini tournament
     */
    public function show($id)
    {
        $miniTournament = MiniTournament::withFullRelations()->findOrFail($id);

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Mini Tournament retrieved successfully');
    }
    /**
     * cập nhật mini tournament
     */
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
