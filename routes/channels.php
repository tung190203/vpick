<?php

use App\Models\MiniTournament;
use App\Models\Tournament;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('mini-tournament.{tournamentId}', function ($user, $tournamentId) {
    $tournament = MiniTournament::find($tournamentId);
    if (!$tournament) return false;

    $hasAccess = $tournament?->all_users->pluck('id')->contains($user->id);

    return $hasAccess;
});

Broadcast::channel('tournament.{tournamentId}', function ($user, $tournamentId) {
    $tournament = Tournament::find($tournamentId);
    if (!$tournament) {
        return false;
    }

    $hasAccess = $tournament->all_users->pluck('id')->contains($user->id);
    
    return $hasAccess;
});

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});