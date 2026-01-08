<?php

namespace App\Http\Controllers;

use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use Illuminate\Http\Request;

class MiniTournamentStaffController extends Controller
{
    public function addStaff(Request $request, $tournamentId)
    {
        $validatedData = $request->validate([
            'staff_id' => 'required|integer|exists:users,id',
        ]);
    
        $tournament = MiniTournament::findOrFail($tournamentId);
        $staffId = $validatedData['staff_id'];
        if ($tournament->staff()->where('user_id', $staffId)->exists()) {
            return response()->json([
                'message' => 'Người dùng này đã là người tổ chức của giải đấu.'
            ], 409);
        }

        $tournament->staff()->attach($staffId, [
            'role' => MiniTournamentStaff::ROLE_ORGANIZER,
            'is_invite_by_organizer' => true
        ]);
    
        return response()->json(['message' => 'Thêm người tổ chức thành công'], 201);
    }
}
