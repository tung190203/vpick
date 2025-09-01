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
        $sports = Sport::all();

        return ResponseHelper::success(SportResource::collection($sports), 'Lấy danh sách môn thể thao thành công');
    }
}
