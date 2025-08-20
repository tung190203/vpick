<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\ClubMember;
use App\Http\Resources\ClubResource;
use Illuminate\Support\Facades\DB;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        $query = Club::with('members')
            ->orderBy('created_at')
            ->when($request->filled('name'), fn($q) => $q->search(['name'], $request->name))
            ->when($request->filled('location'), fn($q) => $q->search(['location'], $request->location));

        $clubs = $query->paginate($request->get('perPage', Club::PER_PAGE));

        return response()->json([
            'message' => 'Get list club successfully',
            'data' => ClubResource::collection($clubs),
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|unique:clubs',
            'location'   => 'nullable|string',
            'logo_url'   => 'nullable|image|max:2048',
            'created_by' => 'required|exists:users,id',
        ]);
    
        return DB::transaction(function () use ($request) {
            $logoPath = null;
            if ($request->hasFile('logo_url')) {
                $logoPath = $request->file('logo_url')->store('logos', 'public');
            }
            $club = Club::create([
                'name'       => $request->name,
                'location'   => $request->location,
                'logo_url'   => $logoPath,
                'created_by' => $request->created_by,
            ]);
            $club->members()->attach($request->created_by, ['is_manager' => true]);
            $club->load('members');
    
            return response()->json([
                'message' => 'Create club successfully',
                'data'    => new ClubResource($club),
            ]);
        });
    }

    public function show($id)
    {
        $club = Club::with('members')->findOrFail($id);

        return response()->json([
            'message' => 'Get club successfully',
            'data' => new ClubResource($club),
        ]);
    }

    public function update(Request $request, $id)
    {
        $club = Club::findOrFail($id);
    
        $request->validate([
            'name' => "required|unique:clubs,name,{$id}",
            'location' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
        ]);
    
        $logoPath = $club->logo_url;
        if ($request->hasFile('logo_url')) {
            $logoPath = $request->file('logo_url')->store('logos', 'public');
        }
    
        $club->update([
            'name' => $request->name ?? $club->name,
            'location' => $request->location ?? $club->location,
            'logo_url' => $logoPath ?? $club->logo_url,
            'created_by' => $request->created_by ?? $club->created_by,
        ]);
    
        return response()->json([
            'message' => 'Update club successfully',
            'data' => new ClubResource($club->refresh()),
        ]);
    }    

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);

        ClubMember::whereIn('club_id', $ids)->delete();
        Club::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Delete club successfully',
            'data' => [],
        ]);
    }

    public function join(Request $request, $id)
    {
        $club = Club::findOrFail($id);
        $userId = auth()->id();

        if ($club->members()->where('user_id', $userId)->exists()) {
            return response()->json([
                'message' => 'Người dùng đã là thành viên của câu lạc bộ này',
            ], 409);
        }

        $club->members()->attach($userId);

        return response()->json([
            'message' => 'Tham gia câu lạc bộ thành công',
            'data' => new ClubResource($club->load('members')),
        ]);
    }
}
