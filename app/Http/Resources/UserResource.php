<?php

namespace App\Http\Resources;

use App\Models\Sport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sport = Sport::where('slug', 'pickleball')->first();
        if($sport) {
            $sportId = $sport->id;
            $userScore = $this->vnduprScoresBySport($sportId)->max('score_value') ?? 0;
            $vnRank = User::query()
                ->select(DB::raw('COUNT(DISTINCT users.id) + 1 as rank'))
                ->join('user_sport', 'users.id', '=', 'user_sport.user_id')
                ->join('user_sport_scores', 'user_sport.id', '=', 'user_sport_scores.user_sport_id')
                ->where('user_sport.sport_id', $sportId)
                ->where('user_sport_scores.score_type', 'vndupr_score')
                ->where('user_sport_scores.score_value', '>', $userScore)
                ->value('rank');
        } else {
            $vnRank = null;
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'visibility' => $this->visibility,
            'avatar_url' => $this->avatar_url,
            'thumbnail' => $this->thumbnail,
            'location_id' => $this->location_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'about' => $this->about,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'is_profile_completed' => $this->is_profile_completed,
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,
            'date_of_birth' => Carbon::parse($this->date_of_birth)->format('d-m-Y'),
            'age_years' => $this->age_years,
            'age_group' => $this->age_group,
            'play_times' => UserPlayTimeResource::collection($this->whenLoaded('playTimes')),
            'sports' => UserSportResource::collection($this->whenLoaded('sports')),
            'clubs' => ClubResource::collection($this->whenLoaded('clubs')),
            'is_followed' => $request->user() ? $request->user()->isFollowing($this->resource) : false,
            'vn_rank' => $vnRank
        ];
    }
}
