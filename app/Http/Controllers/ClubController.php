<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Club;

class ClubController extends Controller
{
    public function __construct(Club $club)
    {
        $this->club = $club;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clubs  = $this->club->getAllClub();
        return response()->json([
            'message'   => 'Get list club successfully',
            'clubs'      => $clubs,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|unique:clubs',
            'location'  => 'required'
        ]);
        $club   = $this->club->createClub($request);
        return response()->json([
            'message'   => 'Create club successfully',
            'clubs'      => $club,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $club  = $this->club->getClubById($id);
        return response()->json([
            'message'   => 'Get club successfully',
            'clubs'      => $club,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'      => 'required|unique:clubs',
            'location'  => 'required'
        ]);
        $club   = $this->club->updateClub($request, $id);
        return response()->json([
            'message'   => 'Update club successfully',
            'clubs'      => $club,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $club   = $this->club->deleteClub($request);
        return response()->json([
            'message'   => 'Delete club successfully',
        ]);
    }
}
