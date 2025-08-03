<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function list (Request $request)
    {
        $query = Tournament::with(['club', 'createdBy', 'matches', 'participants']);
        if ($request->has('keyword')) {
            $query->where('name', 'like', '%' . $request->input('keyword') . '%');
        }
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->input('end_date'));
        }
        if ($request->filled('status')) {
            if( $request->input('status') === Tournament::STATUS_UPCOMING) {
                $query->where('start_date', '>', now());
            } elseif ($request->input('status') === Tournament::STATUS_ONGOING) {
                $query->where('start_date', '<=', now())
                      ->where('end_date', '>=', now());
            } elseif ($request->input('status') === Tournament::STATUS_FINISHED) {
                $query->where('end_date', '<', now());
            }
        }

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
        $tournament = Tournament::with(['club', 'createdBy'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => new TournamentResource($tournament),
        ]);
    }
}
