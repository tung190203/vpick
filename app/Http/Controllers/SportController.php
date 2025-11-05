<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\SportResource;
use App\Models\Sport;
use Illuminate\Http\Request;

class SportController extends Controller
{
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
}
