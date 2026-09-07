<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\SponsorResource;
use App\Models\Sponsor;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    /**
     * Lấy danh sách nhãn hàng tài trợ đang active
     * GET /api/sponsors
     */
    public function index(Request $request)
    {
        $sponsors = Sponsor::active()->get();

        return ResponseHelper::success(
            SponsorResource::collection($sponsors),
            'Lấy danh sách nhãn hàng tài trợ thành công'
        );
    }
}
