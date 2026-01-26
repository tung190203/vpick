<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\SportResource;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;

class SportController extends Controller
{
    protected $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Danh sách môn thể thao
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:200',
            'name' => 'sometimes|string|max:255',
            'is_map' => 'sometimes|boolean', // 👈 thêm cờ is_map
        ]);
    
        $query = Sport::query();
    
        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        if (!empty($validated['is_map']) && $validated['is_map'] === true) {
            $sports = $query->orderBy('name', 'asc')->get();
    
            $data = [
                'sports' => SportResource::collection($sports),
            ];
    
            // Meta đơn giản vì không phân trang
            $meta = [
                'current_page' => 1,
                'last_page'    => 1,
                'per_page'     => $sports->count(),
                'total'        => $sports->count(),
            ];
        } else {
            $sports = $query
                ->orderBy('name', 'asc')
                ->paginate($validated['per_page'] ?? Sport::PER_PAGE);
    
            $data = [
                'sports' => SportResource::collection($sports),
            ];
    
            $meta = [
                'current_page' => $sports->currentPage(),
                'last_page'    => $sports->lastPage(),
                'per_page'     => $sports->perPage(),
                'total'        => $sports->total(),
            ];
        }
    
        return ResponseHelper::success($data, 'Lấy danh sách môn thể thao thành công', 200, $meta);
    }  
    
    public function update(Request $request, $sportid) 
    {
        $request->validate([
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'name' => 'sometimes|string|max:255'
        ]);
        if(!User::isAdmin(auth()->user()->id)) {
            return ResponseHelper::error('Bạn không có quyền cập nhật môn thể thao', 403);
        }
        $sport = Sport::findOrFail($sportid);
        if ($request->filled('name')) {
            $sport->name = $request->name;
        }
        if ($request->hasFile('icon')) {
            $this->imageService->deleteOldImage($sport->icon);
            if ($request->file('icon')->getClientOriginalExtension() === 'svg') {
        
                $filename = time() . '_' . uniqid() . '.svg';
                $path = 'sports/icons/' . $filename;
        
                Storage::disk('public')->put(
                    $path,
                    file_get_contents($request->file('icon'))
                );
        
                $sport->icon = asset('storage/' . $path);
            } 
            else {
                $path = $this->imageService->optimize(
                    file: $request->file('icon'),
                    path: 'sports/icons',
                    maxWidth: 512,
                    quality: 85
                );
                $sport->icon = asset('storage/' . $path);
            }
        }        
    
        $sport->save();
    
        return ResponseHelper::success(
            new SportResource($sport),
            'Cập nhật thành công'
        );
    }    
}
