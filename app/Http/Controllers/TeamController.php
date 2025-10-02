<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\ListTeamResource;
use App\Http\Resources\TeamResource;
use App\Models\Participant;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function listTeams($tournamentId)
    {
        $teams = Team::where('tournament_id', $tournamentId)->with('members')->get();

        return ResponseHelper::success(ListTeamResource::collection($teams), 'Lấy danh sách đội thành công');
    }

    public function createTeam(Request $request, $tournamentId)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255',
            ],
            [
                'name.required' => 'Vui lòng nhập tên đội',
                'name.string' => 'Tên đội phải là chuỗi ký tự',
                'name.max' => 'Tên đội không được vượt quá 255 ký tự',
            ]
        );

        $tournament = Tournament::findOrFail($tournamentId);
        if ($tournament->max_team && $tournament->teams()->count() >= $tournament->max_team) {
            return ResponseHelper::error('Đã đạt số lượng đội tối đa cho giải đấu', 400);
        }

        $team = Team::create([
            'name' => $request->name,
            'tournament_id' => $tournamentId,
        ]);


        return ResponseHelper::success(new TeamResource($team->load('members')), 'Tạo đội thành công');
    }

    public function addMember(Request $request, $teamId)
    {
        $request->validate(
            [
                'user_id' => 'required|exists:participants,user_id',
            ],
            [
                'user_id.exists' => 'Người dùng chưa tham gia giải đấu',
                'user_id.required' => 'Vui lòng chọn người dùng',
            ]
        );

        $team = Team::findOrFail($teamId);
        $tournament = $team->tournament;
        $participant = Participant::where('user_id', $request->user_id)
            ->where('tournament_id', $tournament->id)
            ->where('is_confirmed', true)
            ->first();

        if (!$participant) {
            return ResponseHelper::error("Người dùng chưa được xác nhận tham gia giải đấu", 422);
        }
        $currentCount = $team->members()->count();
        $maxPlayers = $tournament->player_per_team;

        if ($maxPlayers && $currentCount >= $maxPlayers) {
            return ResponseHelper::error("Đội đã đủ số lượng tối đa {$maxPlayers} thành viên", 422);
        }

        // tránh thêm trùng
        if ($team->members()->where('user_id', $request->user_id)->exists()) {
            return ResponseHelper::error("Người dùng đã nằm trong đội", 422);
        }

        $team->members()->attach($request->user_id);

        return ResponseHelper::success($team->load('members'), 'Thêm thành viên vào đội thành công');
    }

    public function autoAssignTeams($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $participants = Participant::where('tournament_id', $tournamentId)
            ->where('is_confirmed', true)
            ->get()
            ->shuffle(); // random danh sách người

        if ($participants->isEmpty()) {
            return ResponseHelper::error('Không có người tham gia nào đã được xác nhận để phân đội', 400);
        }

        $maxPlayers = $tournament->player_per_team ?? 1;
        $maxTeams = $tournament->max_team ?? 1;

        // Xóa các team cũ trước khi phân lại
        Team::where('tournament_id', $tournamentId)->delete();

        // Tạo đủ số team theo max_team
        $teams = [];
        for ($i = 0; $i < $maxTeams; $i++) {
            $teams[] = Team::create([
                'name' => 'Đội ' . ($i + 1),
                'tournament_id' => $tournamentId,
            ]);
        }

        // Gán người lần lượt từ Đội 1 → Đội 2 → ...
        $teamIndex = 0;
        $teamMemberCount = array_fill(0, $maxTeams, 0);

        foreach ($participants as $participant) {
            $teams[$teamIndex]->members()->attach($participant->user_id);
            $teamMemberCount[$teamIndex]++;

            // nếu đội hiện tại đã full thì chuyển sang đội tiếp theo
            if ($teamMemberCount[$teamIndex] >= $maxPlayers) {
                $teamIndex++;
                if ($teamIndex >= $maxTeams) {
                    break; // hết đội
                }
            }
        }

        // load lại danh sách teams + members nhưng chỉ lấy user có participant.confirmed
        $teamsWithMembers = Team::where('tournament_id', $tournamentId)
            ->with([
                'members' => function ($q) use ($tournamentId) {
                    $q->whereIn('users.id', function ($sub) use ($tournamentId) {
                        $sub->select('user_id')
                            ->from('participants')
                            ->where('tournament_id', $tournamentId)
                            ->where('is_confirmed', true);
                    });
                }
            ])
            ->get();

        return ResponseHelper::success(
            ListTeamResource::collection($teamsWithMembers),
            'Phân đội tự động thành công'
        );
    }

    public function removeMember(Request $request, $teamId)
    {
        $request->validate(
            [
                'user_id' => 'required|exists:participants,user_id',
            ],
            [
                'user_id.exists' => 'Người dùng chưa tham gia giải đấu',
                'user_id.required' => 'Vui lòng chọn người dùng',
            ]
        );

        $team = Team::findOrFail($teamId);
        $team->members()->detach($request->user_id);

        return ResponseHelper::success($team->load('members'), 'Xóa thành viên khỏi đội thành công');
    }
}
