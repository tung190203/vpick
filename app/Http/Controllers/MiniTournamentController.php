<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreMiniTournamentRequest;
use App\Http\Requests\UpdateMiniTournamentRequest;
use App\Http\Resources\ListMiniTournamentResource;
use App\Http\Resources\MiniTournamentResource;
use App\Models\MiniMatch;
use App\Models\MiniParticipant;
use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use App\Models\User;
use App\Notifications\MiniTournamentInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MiniTournamentController extends Controller
{
    /**
     * tạo mini tournament
     */
    public function store(StoreMiniTournamentRequest $request)
    {
        $data = $request->safe()->except(['invite_user', 'role_type']);

        $miniTournament = MiniTournament::create($data);
        $miniTournament->staff()->attach(Auth::id(), ['role' => MiniTournamentStaff::ROLE_ORGANIZER]);

        // Add creator as participant if role_type is 'participant'
        $roleType = $request->input('role_type', 'participant');
        if ($roleType !== 'organizer') {
            MiniParticipant::create([
                'mini_tournament_id' => $miniTournament->id,
                'user_id' => Auth::id(),
                'is_confirmed' => true,
            ]);
        }

        if( $request->has('invite_user') ) {
            $inviteUsers = $request->input('invite_user', []);
            foreach ($inviteUsers as $userId) {
                MiniParticipant::create([
                    'mini_tournament_id' => $miniTournament->id,
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

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Tạo kèo đấu thành công', 201);
    }
    /**
     * danh sách mini tournament
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:200',
            'keyword'  => 'nullable|string'
        ]);
        $nowVN = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
        $query = MiniTournament::withFullRelations();

        if ($request->has('sport_id')) {
            $query->where('sport_id', $validated['sport_id']);
        }

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        // 🔥 keyword search (tên kèo + tên sân + địa chỉ sân)
        if (!empty($validated['keyword'])) {
            $kw = trim($validated['keyword']);

            $query->where(function ($q) use ($kw) {
                $q->where('mini_tournaments.name', 'LIKE', "%{$kw}%")
                  ->orWhereHas('competitionLocation', function ($loc) use ($kw) {
                      $loc->where('competition_locations.name', 'LIKE', "%{$kw}%")
                          ->orWhere('competition_locations.address', 'LIKE', "%{$kw}%");
                  });
            });
        }

        $query->whereDate('start_time', '>=', $nowVN);
        $userId = auth()->id();
        $query->where(function ($q) use ($userId) {
            $q->where('is_private', 0)
                ->orWhereHas('participants', fn($sub) => $sub->where('user_id', $userId));
        });

        $miniTournaments = $query->paginate($validated['per_page'] ?? MiniTournament::PER_PAGE);

        $data = [
            'mini_tournaments' => ListMiniTournamentResource::collection($miniTournaments),
        ];

        $meta = [
            'current_page' => $miniTournaments->currentPage(),
            'last_page' => $miniTournaments->lastPage(),
            'per_page' => $miniTournaments->perPage(),
            'total' => $miniTournaments->total(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách kèo đấu thành công', 200, $meta);
    }
    /**
     * chi tiết mini tournament
     */
    public function show($id)
    {
        $miniTournament = MiniTournament::withFullRelations()->findOrFail($id);

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Lấy thông tin chi tiết kèo đấu thành công');
    }
    /**
     * cập nhật mini tournament
     */
    public function update(UpdateMiniTournamentRequest $request, $id)
    {
        $miniTournament = MiniTournament::findOrFail($id);
        $data = $request->validated();
        // Remove 'role_type' from data before updating tournament
        $data = collect($data)->except('role_type')->toArray();

        $isOrganizer = $miniTournament->hasOrganizer(Auth::id());

        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền cập nhật kèo đấu', 403);
        }

        $miniTournament->update($data);

        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
            $miniTournament->update(['poster' => $posterPath]);
        }

        // Handle role_type change for tournament creator
        if ($request->has('role_type')) {
            $roleType = $request->input('role_type');
            if ($roleType === 'organizer') {
                // Remove creator as participant if switching to organizer-only
                $miniTournament->participants()->where('user_id', Auth::id())->delete();
            } else {
                // Add creator as participant if not already
                $existingParticipant = $miniTournament->participants()->where('user_id', Auth::id())->first();
                if (!$existingParticipant) {
                    MiniParticipant::create([
                        'mini_tournament_id' => $miniTournament->id,
                        'user_id' => Auth::id(),
                        'is_confirmed' => true,
                    ]);
                }
            }
        }
        $miniTournament->loadFullRelations();

        return ResponseHelper::success(new MiniTournamentResource($miniTournament), 'Cập nhật thông tin kèo đấu thành công');
    }

    public function destroy(Request $request, $id)
    {
        $miniTournament = MiniTournament::find($id);
        $isOrganizer = $miniTournament->hasOrganizer(Auth::id());

        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền huỷ kèo đấu', 403);
        }

        if(!$miniTournament) {
            return ResponseHelper::error('Kèo đấu không tồn tại', 404);
        }

        $hasCompletedMatch = MiniMatch::where('mini_tournament_id', $miniTournament->id)->where('status', MiniMatch::STATUS_COMPLETED)->exists();

        if($hasCompletedMatch) {
            return ResponseHelper::error('Không thể huỷ bỏ kèo đã có trận đấu được xác nhận',404);
        }

        DB::transaction(function () use ($miniTournament) {
            $miniTournament->delete();
        });

        return ResponseHelper::success(null, 'Xoá kèo đấu thành công');
    }
}
