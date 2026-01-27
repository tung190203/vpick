<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\ClubMember;
use App\Http\Resources\ClubResource;
use Illuminate\Support\Facades\DB;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'perPage' => 'sometimes|integer|min:1|max:200',
        ]);
        $query = Club::withFullRelations()->orderBy('created_at', 'desc');
    
        if (!empty($validated['name'])) {
            $query->search(['name'], $validated['name']);
        }
    
        if (!empty($validated['location'])) {
            $query->search(['location'], $validated['location']);
        }
    
        $perPage = $validated['perPage'] ?? Club::PER_PAGE;
        $clubs = $query->paginate($perPage);
    
        $data = [
            'clubs' => ClubResource::collection($clubs),
        ];
    
        $meta = [
            'current_page' => $clubs->currentPage(),
            'last_page'    => $clubs->lastPage(),
            'per_page'     => $clubs->perPage(),
            'total'        => $clubs->total(),
        ];
    
        return ResponseHelper::success($data, 'Lấy danh sách câu lạc bộ thành công', 200, $meta);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:clubs',
            'location' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
            'created_by' => 'required|exists:users,id',
        ]);

        return DB::transaction(function () use ($request) {
            $logoPath = null;
            if ($request->hasFile('logo_url')) {
                $logoPath = $request->file('logo_url')->store('logos', 'public');
            }
            $club = Club::create([
                'name' => $request->name,
                'location' => $request->location,
                'logo_url' => $logoPath,
                'status' => 'active',
                'created_by' => $request->created_by,
            ]);
            
            // Tạo member với role admin
            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $request->created_by,
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);
            
            $club->load('members.user');

            return ResponseHelper::success(new ClubResource($club), 'Tạo câu lạc bộ thành công');
        });
    }

    public function show($id)
    {
        $club = Club::withFullRelations()->findOrFail($id);

        // Load members với user và vnduprScores
        $members = $club->members()->with(['user.vnduprScores'])->get();
        
        $members = $members->map(function ($member) {
            $user = $member->user;
            $member->user->club_score = $user->vnduprScores->first()?->score_value ?? 0;
            return $member;
        })->sortByDesc(function ($member) {
            return $member->user->club_score ?? 0;
        })->values();

        $members->each(function ($member, $index) {
            $member->rank_in_club = $index + 1;
        });

        $club->setRelation('members', $members);

        return ResponseHelper::success(new ClubResource($club), 'Lấy thông tin câu lạc bộ thành công');
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

        return ResponseHelper::success(new ClubResource($club->refresh()), 'Cập nhật câu lạc bộ thành công');
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);

        ClubMember::whereIn('club_id', $ids)->delete();
        Club::whereIn('id', $ids)->delete();

        return ResponseHelper::success([], 'Xóa câu lạc bộ thành công');
    }

    public function join(Request $request, $id)
    {
        $club = Club::findOrFail($id);
        $userId = auth()->id();

        if ($club->hasMember($userId)) {
            return ResponseHelper::error('Người dùng đã là thành viên của câu lạc bộ này', 409);
        }

        // Tạo join request
        ClubMember::create([
            'club_id' => $club->id,
            'user_id' => $userId,
            'role' => 'member',
            'status' => 'pending',
        ]);

        return ResponseHelper::success(new ClubResource($club->load('members')), 'Tham gia câu lạc bộ thành công');
    }

    public function myClubs(Request $request)
    {
        $userId = auth()->id();
        $clubs = Club::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('status', 'active');
        })->withFullRelations()->get();

        return ResponseHelper::success(ClubResource::collection($clubs), 'Lấy danh sách câu lạc bộ của tôi thành công');
    }
}
