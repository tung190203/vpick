<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClubMember;

class Club extends Model
{
    use HasFactory;

    protected $table    = 'clubs';

    protected $perPage  = 10;

    public function member()
    {
        return $this->belongsToMany(User::class, 'club_members', 'club_id', 'user_id');
    }

    public function getAllClub($request = null)
    {
        $clubs  = Club::orderBy('created_at');
                        if ($request != null) {
                            $clubs = $clubs->where('name', 'LIKE', '%'.$request->name.'%')
                            ->orwhere('location', 'LIKE', '%'.$request->location.'%');
                        }
                        $clubs = $clubs->paginate(isset($request) ? $request->perPage : 10);
        return $clubs;
    }

    public function createClub($request)
    {
        $club               = new Club();
        $club->name         = $request->name;
        $club->location     = $request->location;
        $club->logo_url     = $request->logo_url;
        $club->created_by   = $request->created_by;
        $club->save();
        return $club;
    }

    public function getClubById($id)
    {
        $club = Club::findorFail($id);
        $club->member;
        return $club;
    }

    public function updateClub($request, $id)
    {
        $club               = Club::find($id);
        $club->name         = $request->name;
        $club->location     = $request->location;
        $club->logo_url     = $request->logo_url;
        $club->created_by   = $request->created_by;
        $club->save();
        return $club;
    }

    public function deleteClub($request)
    {
        $club               = Club::whereIn('id', $request->list)->delete();
        $member             = ClubMember::whereIn('club_id', $request->list)->delete();
        return true;
    }
}
