<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MiniMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_tournament_id',
        'round',
        'participant1_id',
        'participant2_id',
        'scheduled_at',
        'referee_id',
        'status',
        'participant_win_id',
    ];

    const PER_PAGE = 10;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DISPUTED = 'disputed';

    public function participant1()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant1_id');
    }
    public function participant2()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant2_id');
    }

    public function participantWin()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant_win_id');
    }
    public function results()
    {
        return $this->hasMany(MiniMatchResult::class, 'mini_match_id');
    }

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class, 'mini_tournament_id');
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'participant1.user',
            'participant1.team.members.user',
            'participant2.user',
            'participant2.team.members.user',
            'results.participant.user',
            'results.participant.team.members.user',
            'participantWin.user',
            'participantWin.team.members.user',
            'miniTournament.competitionLocation',
            'miniTournament.sport',
        ]);
    }

    public function scopeInBounds($query, $minLat, $maxLat, $minLng, $maxLng)
    {
        return $query->whereHas('miniTournament.competitionLocation', function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
            $q->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng]);
        });
    }

    public function scopeFilter($query, $filters)
    {
        return $query
            ->when(
                !empty($filters['sport_id']),
                fn($q) => $q->whereHas(
                    'miniTournament.sport',
                    fn($sq) => $sq->where('sports.id', $filters['sport_id'])
                )
            )
            ->when(
                !empty($filters['location_id']),
                fn($q) => $q->whereHas(
                    'miniTournament.competitionLocation',
                    fn($lq) => $lq->where('location_id', $filters['location_id'])
                )
            )
            ->when(
                !empty($filters['keyword']),
                fn($q) => $q->whereHas('miniTournament.competitionLocation', function ($sub) use ($filters) {
                    $sub->where('name', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('address', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhereHas(
                            'location',
                            fn($lq) => $lq->where('name', 'like', '%' . $filters['keyword'] . '%')
                        );
                })
            )
            ->when(
                !empty($filters['date_from']),
                fn($q) => $q->whereBetween('scheduled_at', [
                    Carbon::parse($filters['date_from'])->startOfDay(),
                    Carbon::parse($filters['date_from'])->endOfDay()
                ])
            )
            ->when(
                !empty($filters['type']) && in_array($filters['type'], ['single', 'double']),
                function ($q) use ($filters) {
                    if ($filters['type'] === 'single') {
                        $q->where(function ($sub) {
                            $sub->whereHas('participant1', fn($p1) => $p1->whereNotNull('user_id')->whereNull('team_id'))
                                ->orWhereHas('participant2', fn($p2) => $p2->whereNotNull('user_id')->whereNull('team_id'));
                        });
                    } else {
                        $q->where(function ($sub) {
                            $sub->whereHas('participant1', fn($p1) => $p1->whereNotNull('team_id')->whereNull('user_id'))
                                ->orWhereHas('participant2', fn($p2) => $p2->whereNotNull('team_id')->whereNull('user_id'));
                        });
                    }
                }
            )
            ->when(
                !empty($filters['rating']),
                function ($q) use ($filters) {
                    $rating = (float) $filters['rating'];
                    $q->whereHas('miniTournament', function ($tq) use ($rating) {
                        $tq->where('min_rating', '<=', $rating)
                            ->where('max_rating', '>=', $rating);
                    });
                }
            )
            ->when(
                !empty($filters['fee']) && in_array($filters['fee'], ['free', 'paid']),
                function ($q) use ($filters) {
                    if ($filters['fee'] === 'free') {
                        $q->whereHas('miniTournament', fn($tq) => $tq->where('fee_amount', 0));
                    } else {
                        $q->whereHas('miniTournament', function ($tq) use ($filters) {
                            $tq->whereBetween('fee_amount', [$filters['min_price'], $filters['max_price']]);
                        });
                    }
                }
            )
            ->when(
                !empty($filters['time_of_day']) && in_array($filters['time_of_day'], ['morning', 'afternoon', 'evening']),
                function ($q) use ($filters) {
                    if ($filters['time_of_day'] === 'morning') {
                        // Trước 11h
                        $q->whereTime('scheduled_at', '<', '11:00:00');
                    } elseif ($filters['time_of_day'] === 'afternoon') {
                        // Từ 11h đến trước 16h
                        $q->whereTime('scheduled_at', '>=', '11:00:00')
                            ->whereTime('scheduled_at', '<', '16:00:00');
                    } elseif ($filters['time_of_day'] === 'evening') {
                        // Từ 16h trở đi
                        $q->whereTime('scheduled_at', '>=', '16:00:00');
                    }
                }
            )
            ->when(
                !empty($filters['slot_status']) && in_array($filters['slot_status'], ['one_slot', 'two_slot', 'full_slot']),
                function ($q) use ($filters) {
                    if ($filters['slot_status'] === 'one_slot') {
                        $q->whereRaw('(CASE WHEN participant1_id IS NULL THEN 1 ELSE 0 END + CASE WHEN participant2_id IS NULL THEN 1 ELSE 0 END) = 1');
                    } elseif ($filters['slot_status'] === 'two_slot') {
                        $q->where(function ($sub) {
                            $sub->whereHas('participant1', fn($p) => $p->whereNotNull('team_id'))
                                ->orWhereHas('participant2', fn($p) => $p->whereNotNull('team_id'));
                        });
                    } elseif ($filters['slot_status'] === 'full_slot') {
                        $q->whereNull('participant1_id')
                            ->whereNull('participant2_id');
                    }
                }
            );
    }

    public function scopeNearBy($query, float $lat, float $lng, float $radiusKm = 5)
    {
        $haversine = "(6371 * acos(cos(radians($lat)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians($lng)) 
                + sin(radians($lat)) 
                * sin(radians(latitude))))";

        return $query->whereHas('miniTournament.competitionLocation', function ($q) use ($haversine, $radiusKm) {
            $q->select('*')
                ->whereRaw("$haversine < ?", [$radiusKm]);
        });
    }
}
