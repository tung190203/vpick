<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = Facility::all();

        return ResponseHelper::success(FacilityResource::collection($facilities), 'Lấy danh sách tiện ích thành công');
    }
}
