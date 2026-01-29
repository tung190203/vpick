<?php

namespace Database\Seeders;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ClubMemberSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $clubs = Club::all();

        if ($users->isEmpty() || $clubs->isEmpty()) {
            return;
        }

        $roles = [ClubMemberRole::Member, ClubMemberRole::Member, ClubMemberRole::Member, ClubMemberRole::Secretary, ClubMemberRole::Treasurer, ClubMemberRole::Manager, ClubMemberRole::Admin];

        foreach ($clubs as $club) {
            $members = $users->shuffle()->take(rand(3, min(6, $users->count())));
            $roleIndex = 0;

            foreach ($members as $user) {
                $role = $roles[$roleIndex % count($roles)];
                $roleIndex++;
                $status = ClubMemberStatus::Active;
                $joinedAt = now()->subDays(rand(1, 365));

                $attrs = [
                    'user_id' => $user->id,
                    'club_id' => $club->id,
                ];
                $values = [
                    'role' => $role,
                    'status' => $status,
                    'joined_at' => $joinedAt,
                    'is_manager' => in_array($role, [ClubMemberRole::Admin, ClubMemberRole::Manager]),
                ];

                if (Schema::hasColumn('club_members', 'invited_by')) {
                    $values['invited_by'] = null; // user request hoặc seed không cần
                }

                ClubMember::firstOrCreate($attrs, $values);
            }
        }
    }
}
