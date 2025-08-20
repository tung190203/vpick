<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::query();
        if ($request->has('name')) {
            $query->search('name', $request->input('name'));
        }

        $locations = $query->get();

        return response()->json([
            'message' => 'List of locations',
            'locations' => LocationResource::collection($locations)
        ]);
    }
}
