<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Matches;
use Illuminate\Http\Request;

class MatchesController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'participant1_id' => 'required|exists:participants,id',
            'participant2_id' => 'required|exists:participants,id|different:participant1_id',
            'scheduled_at' => 'nullable|date',
            'referee_id' => 'nullable|exists:users,id',
            // nếu trong giải đấu có nhóm, thì group_id là bắt buộc
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $match = Matches::create([
            'group_id' => $validated['group_id'] ?? null,
            'participant1_id' => $validated['participant1_id'],
            'participant2_id' => $validated['participant2_id'],
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'referee_id' => $validated['referee_id'] ?? null,
            'status' => 'pending',
        ]);

        return ResponseHelper::success('Tạo trận đấu thành công', $match);
    }

}
