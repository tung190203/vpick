<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\CompetitionLocationYard;
use Illuminate\Http\Request;

class CompetitionLocationYardController extends Controller
{
    public function index(Request $request)
    {
        $competitionLocationYards = CompetitionLocationYard::select('yard_type')
            ->distinct()
            ->get()
            ->map(fn($yard) => [
                'yard_type' => $yard->yard_type,
                'name' => $yard->yard_type_name,
            ]);

        return ResponseHelper::success($competitionLocationYards, 'Lấy loại sân thành công');
    }
}
