<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function list(Request $request)
    {
        $query = Tournament::withFullRelations();
        // Tìm kiếm theo keyword
        $query->when($request->filled('keyword'), fn($q) =>
            $q->search($request->input('keyword'))
        ); 
        // Lọc theo ngày
        $query->filterByDate(
            $request->input('start_date'),
            $request->input('end_date')
        );

        $query->when($request->filled('status'), fn($q) =>
         $q->filterByStatus(
            $request->input('status')
         )
        );
    
        // Phân trang
        $tournaments = $query->orderBy('start_date', 'asc')->paginate(9);

        return response()->json([
            'success' => true,
            'data' => TournamentResource::collection($tournaments),
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
        ]);
    }

    public function showTournament(Request $request, $id)
    {
        $tournament = Tournament::withFullRelations()->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function joinTournament(Request $request, $id)
    {
        $tournament = Tournament::findOrFail($id);
        $user = $request->user();

        // Kiểm tra nếu người dùng đã tham gia
        if ($tournament->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already joined this tournament.',
            ], 400);
        }

        $tournament->participants()->create(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'You have successfully joined the tournament.',
        ]);
    }
}
