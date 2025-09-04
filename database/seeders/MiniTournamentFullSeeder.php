<?php

namespace Database\Seeders;

use App\Models\MiniMatch;
use App\Models\MiniMatchResult;
use App\Models\MiniParticipant;
use App\Models\MiniTeam;
use App\Models\MiniTeamMember;
use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MiniTournamentFullSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // 1. Tạo 1 Mini Tournament
        $tournament = MiniTournament::create([
            'name' => 'Mini Summer Cup 2025',
            'sport_id' => 1, // chọn sport sample
            'starts_at' => now()->addDays(3),
            'match_type' => 3, // single match example
            'status' => 2,
        ]);

        MiniTournamentStaff::create([
            'mini_tournament_id' => $tournament->id,
            'user_id' => $users->first()->id,
            'role' => MiniTournamentStaff::ROLE_ORGANIZER,
        ]);

        // 2. Tạo Teams (2 teams cho tournament)
        $teams = [];
        for ($i = 1; $i <= 2; $i++) {
            $team = MiniTeam::create([
                'name' => 'Mini Team ' . $i,
                'mini_tournament_id' => $tournament->id,
            ]);

            // 3. Gán member cho team
            $members = $users->random(2);
            foreach ($members as $member) {
                MiniTeamMember::firstOrCreate([
                    'mini_team_id' => $team->id,
                    'user_id' => $member->id,
                ]);
            }

            $teams[] = $team;
        }

        // 4. Tạo participants
        $participants = [];

        // a) User participants
        $userParticipants = $users->take(4);
        foreach ($userParticipants as $user) {
            $p = MiniParticipant::create([
                'mini_tournament_id' => $tournament->id,
                'type' => 'user',
                'user_id' => $user->id,
                'is_confirmed' => true,
            ]);
            $participants[] = $p;
        }

        // b) Team participants
        foreach ($teams as $team) {
            $p = MiniParticipant::create([
                'mini_tournament_id' => $tournament->id,
                'type' => 'team',
                'team_id' => $team->id,
                'is_confirmed' => true,
            ]);
            $participants[] = $p;
        }

        // 5. Tạo 2 matches giữa participants
        for ($i = 0; $i < 2; $i++) {
            $p1 = $participants[array_rand($participants)];
            $p2 = $participants[array_rand($participants)];

            while ($p2->id === $p1->id) {
                $p2 = $participants[array_rand($participants)];
            }

            $match = MiniMatch::create([
                'mini_tournament_id' => $tournament->id,
                'participant1_id' => $p1->id,
                'participant2_id' => $p2->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 7)),
                'status' => 'pending',
            ]);

            // 6. Tạo kết quả cho mỗi participant
            MiniMatchResult::create([
                'mini_match_id' => $match->id,
                'participant_id' => $p1->id,
                'score' => rand(0, 21),
                'won_set' => false,
                'set_number' => 1,
                'status' => 'pending',
            ]);

            MiniMatchResult::create([
                'mini_match_id' => $match->id,
                'participant_id' => $p2->id,
                'score' => rand(0, 21),
                'won_set' => false,
                'set_number' => 1,
                'status' => 'pending',
            ]);
        }
    }
}
