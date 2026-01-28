<?php
namespace App\Enums;

enum TournamentStatus: string {
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Finished = 'finished';
}
