<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\Participant;
use App\Models\Referee;
use App\Models\User;
use App\Models\MatchResult;
use Carbon\Carbon;

class TournamentFullSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(10)->get(); // 10 users
        $referees = Referee::take(3)->get(); // 3 referees

        $tournament = Tournament::create([
            'name' => 'Summer Cup 2025',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(15),
            'location' => 'Hanoi',
            'level' => 'local',
            'description' => 'Full test tournament seeder',
            'created_by' => $users->first()->id,
        ]);

        $types = [
            ['type' => 'single', 'description' => 'Single matches'],
            ['type' => 'double', 'description' => 'Double matches'],
            ['type' => 'mixed',  'description' => 'Mixed matches'],
        ];

        foreach ($types as $typeData) {
            $type = $tournament->tournamentTypes()->create($typeData);

            $groupNames = ['A','B','C','D'];
            foreach ($groupNames as $gName) {
                $group = $type->groups()->create(['name' => $gName]);

                for ($i = 0; $i < 2; $i++) {

                    if ($type->type === 'single') {
                        $p1User = $users->random();
                        $p2User = $users->random();
                        while ($p2User->id === $p1User->id) {
                            $p2User = $users->random();
                        }

                        $p1 = Participant::firstOrCreate([
                            'tournament_type_id' => $type->id,
                            'user_id' => $p1User->id,
                            'type' => 'user',
                        ], ['is_confirmed' => true]);

                        $p2 = Participant::firstOrCreate([
                            'tournament_type_id' => $type->id,
                            'user_id' => $p2User->id,
                            'type' => 'user',
                        ], ['is_confirmed' => true]);
                    } else {
                        $team1 = Team::create([
                            'name' => 'Team ' . $group->name . '1_' . $i,
                            'tournament_type_id' => $type->id,
                        ]);
                        $team1Members = $users->random(2);
                        $team1->members()->attach($team1Members->pluck('id')->toArray());

                        $p1 = Participant::create([
                            'tournament_type_id' => $type->id,
                            'team_id' => $team1->id,
                            'type' => 'team',
                            'is_confirmed' => true,
                        ]);

                        $team2 = Team::create([
                            'name' => 'Team ' . $group->name . '2_' . $i,
                            'tournament_type_id' => $type->id,
                        ]);
                        $team2Members = $users->random(2);
                        $team2->members()->attach($team2Members->pluck('id')->toArray());

                        $p2 = Participant::create([
                            'tournament_type_id' => $type->id,
                            'team_id' => $team2->id,
                            'type' => 'team',
                            'is_confirmed' => true,
                        ]);
                    }

                    // Tạo match
                    $match = $group->matches()->create([
                        'participant1_id' => $p1->id,
                        'participant2_id' => $p2->id,
                        'referee_id' => $referees->random()->id,
                        'status' => 'pending',
                        'scheduled_at' => Carbon::now()->addDays(rand(1,10)),
                        'round' => 'Round ' . ($i + 1),
                    ]);

                    // Tạo match_results cho mỗi participant
                    MatchResult::create([
                        'match_id' => $match->id,
                        'participant_id' => $p1->id,
                        'won_match' => rand(0,1) ? true : false,
                        'confirmed' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    MatchResult::create([
                        'match_id' => $match->id,
                        'participant_id' => $p2->id,
                        'won_match' => rand(0,1) ? true : false,
                        'confirmed' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
