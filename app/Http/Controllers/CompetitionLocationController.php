<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\CompetitionLocationResource;
use App\Http\Resources\FacilityResource;
use App\Models\CompetitionLocation;
use App\Models\CompetitionLocationYard;
use App\Models\Facility;
use Illuminate\Http\Request;

class CompetitionLocationController extends Controller
{
    private const VALIDATION_RULE = 'sometimes';
    /**
     * Lọc và lấy danh sách địa điểm thi đấu (trình lọc sân bóng)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'sometimes',
            'lng' => 'sometimes',
            'radius' => 'sometimes|numeric|min:1',
            'minLat' => self::VALIDATION_RULE,
            'maxLat' => self::VALIDATION_RULE,
            'minLng' => self::VALIDATION_RULE,
            'maxLng' => self::VALIDATION_RULE,
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'location_id' => 'sometimes|integer|exists:locations,id',
            'number_of_yards' => 'sometimes|array',
            'number_of_yards.*' => 'integer|min:1',
            'keyword' => 'sometimes|string|max:255',
            'is_followed' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'yard_type' => 'nullable|array',
            'yard_type.*' => 'integer|in:' . implode(',', CompetitionLocationYard::YARD_TYPE),
            'facility_id' => 'nullable|array',
            'facility_id.*' => 'integer|exists:facilities,id',
        ]);
    
        $query = CompetitionLocation::withFullRelations()->filter($validated);
    
        $hasFilter = collect(['sport_id', 'location_id', 'is_followed', 'keyword', 'lat', 'lng', 'radius', 'number_of_yards', 'yard_type', 'facility_id'])
            ->some(fn($key) => $request->filled($key));
    
        if (!$hasFilter && (!empty($validated['minLat']) || !empty($validated['maxLat']) || !empty($validated['minLng']) || !empty($validated['maxLng']))) {
            $query->inBounds(
                $validated['minLat'],
                $validated['maxLat'],
                $validated['minLng'],
                $validated['maxLng']
            );
        }

        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            $query->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
        }
    
        $locations = $query->paginate($validated['per_page'] ?? CompetitionLocation::PER_PAGE);

        $yardTypes = CompetitionLocationYard::select('yard_type')
            ->distinct()
            ->get()
            ->map(fn($yard) => [
                'id' => $yard->yard_type,
                'name' => $yard->yard_type_name,
            ]);

        $data = [
            'competition_locations' => CompetitionLocationResource::collection($locations),
            'yard_types' => $yardTypes,
            'facilities' => FacilityResource::collection(Facility::all()),
        ];
    
        return ResponseHelper::success(
            // CompetitionLocationResource::collection($locations),
            $data,
            'Lấy danh sách địa điểm thi đấu thành công',
        );
    }    
}
