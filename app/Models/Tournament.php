<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'location',
        'club_id',
        'created_by',
        'level',
        'description',
    ];

    protected $appends = ['status'];

    const STATUS_UPCOMING = 'upcoming';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';

    const PER_PAGE = 10;

    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tournamentTypes()
    {
        return $this->hasMany(TournamentType::class, 'tournament_id');
    }

    public function groups()
    {
        return $this->hasManyThrough(Group::class, TournamentType::class);
    }

    public function participants()
    {
        return $this->hasManyThrough(Participant::class, TournamentType::class);
    }

    public function matches()
    {
        return $this->hasManyThrough(Matches::class, Group::class);
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'createdBy',
            'club',
            'tournamentTypes.groups.matches.participant1.user',
            'tournamentTypes.groups.matches.participant1.team.members',
            'tournamentTypes.groups.matches.participant2.user',
            'tournamentTypes.groups.matches.participant2.team.members',
        ]);
    }
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
    public function scopeFinished($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', '%' . $keyword . '%');
    }

    public function scopeFilterByDate($query, $startDate = null, $endDate = null)
    {
        return $query
            ->when($startDate, fn($q) => $q->whereDate('start_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('end_date', '<=', $endDate));
    }

    public function scopeFilterByStatus($query, $status)
    {
        $query = match ($status) {
            self::STATUS_UPCOMING => $query->upcoming(),
            self::STATUS_ONGOING => $query->ongoing(),
            self::STATUS_FINISHED => $query->finished(),
            default => $query,
        };

        return $query;
    }

    public function getStatusAttribute()
    {
        if ($this->end_date && $this->end_date < now()) {
            return self::STATUS_FINISHED;
        }

        if ($this->start_date && $this->start_date > now()) {
            return self::STATUS_UPCOMING;
        }

        if ($this->start_date && $this->end_date && $this->start_date <= now() && $this->end_date >= now()) {
            return self::STATUS_ONGOING;
        }

        return null;
    }
}
