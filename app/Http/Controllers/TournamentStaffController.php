<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Tournament;
use App\Models\TournamentStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentStaffController extends Controller
{
    public function addStaff(Request $request, $tournamentId)
    {
        $validatedData = $request->validate([
            'staff_id' => 'required|integer|exists:users,id',
        ]);
    
        $tournament = Tournament::findOrFail($tournamentId);
        $isOrganizer = $tournament->hasOrganizer(Auth::id());

        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền thêm người tổ chức', 403);
        }
        $staffId = $validatedData['staff_id'];
        if ($tournament->staff()->where('user_id', $staffId)->exists()) {
            return response()->json([
                'message' => 'Người dùng này đã là người tổ chức của giải đấu.'
            ], 409);
        }

        $tournament->staff()->attach($staffId, [
            'role' => TournamentStaff::ROLE_ORGANIZER,
            'is_invite_by_organizer' => true
        ]);
    
        return response()->json(['message' => 'Thêm người tổ chức thành công'], 201);
    }
}
