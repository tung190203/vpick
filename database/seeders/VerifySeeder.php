<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Verify;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VerifySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereDoesntHave('verify')->get();

        foreach ($users as $user) {
            $otherUsers = User::where('id', '!=', $user->id)->inRandomOrder()->take(2)->pluck('id')->toArray();

            $verifierId = $otherUsers[0] ?? null;
            $approverId = $otherUsers[1] ?? null;

            Verify::create([
                'user_id' => $user->id,
                'vndupr_score' => rand(10, 500) / 10,
                'certified_file' => null,
                'status' => ['pending','approved','rejected'][rand(0,2)],
                'verifier_id' => $verifierId,
                'approver_id' => $approverId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
