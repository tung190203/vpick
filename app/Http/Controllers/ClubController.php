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
    public function index(Request $request)
    {
        $clubs      = $this->club->getAllClub($request->all());
        return $this->club->response('Get list club successfully', $clubs);
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
        $club   = $this->club->createClub($request->all());
        return $this->club->response('Create club successfully', $club, 'store');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $club  = $this->club->getClubById($id);
        return $this->club->response('Get club successfully', $club, 'edit');
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
        $club   = $this->club->updateClub($request->all(), $id);
        return $this->club->response('Update club successfully', $club, 'update');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $club   = $this->club->deleteClub($request->all());
        return $this->club->response('Delete club successfully', [], 'delete');
    }
}
