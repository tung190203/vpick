<?php

namespace App\Actions\Tournament;

use App\Models\TournamentType;
use App\Services\TournamentType\MatchGeneratorService;
use Illuminate\Support\Collection;

/**
 * Action chuyên xử lý generation Round Robin format
 */
class GenerateRoundRobinAction
{
    public function __construct(
        private MatchGeneratorService $matchGenerator
    ) {}

    /**
     * Generate matches cho Round Robin format
     */
    public function execute(TournamentType $type, Collection $teams, int $numLegs): void
    {
        if ($teams->count() < 2) {
            return;
        }

        $this->matchGenerator->generateRoundRobin($type, $teams, $numLegs);
    }
}
