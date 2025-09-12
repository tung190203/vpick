<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $query = Location::query();
        if($validated['name'] ?? false) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        $locations = $query->orderBy('name', 'asc')
            ->paginate($validated['per_page'] ?? Location::PER_PAGE);

        return ResponseHelper::success(LocationResource::collection($locations), 'Lấy danh sách địa điểm thành công', 200, [
            'current_page' => $locations->currentPage(),
            'last_page' => $locations->lastPage(),
            'per_page' => $locations->perPage(),
            'total' => $locations->total(),
        ]);
    }
}
