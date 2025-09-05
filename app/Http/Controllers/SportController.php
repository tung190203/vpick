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
            'per_page' => 'sometimes|integer|min:1|max:100',
            'name' => 'sometimes|string|max:255',
        ]);
        $query = Sport::query();
        if ($validated['name'] ?? false) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }
        $sports = $query->orderBy('name', 'asc')
            ->take($validated['per_page'] ?? Sport::PER_PAGE)
            ->get();

        return ResponseHelper::success(SportResource::collection($sports), 'Lấy danh sách môn thể thao thành công');
    }
}
