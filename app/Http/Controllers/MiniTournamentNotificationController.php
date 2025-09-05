<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\MiniTournament;
use App\Models\MiniTournamentUserNotification;

class MiniTournamentNotificationController extends Controller
{
    /**
     * Đăng ký nhắc nhở giải đấu nhỏ.
     *
     * @param int $miniTournamentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe($miniTournamentId)
    {
        $tournament = MiniTournament::findOrFail($miniTournamentId);

        $record = MiniTournamentUserNotification::firstOrCreate([
            'user_id' => auth()->id(),
            'mini_tournament_id' => $tournament->id,
        ]);
        if (!$record->wasRecentlyCreated) {
            return ResponseHelper::error('Bạn đã đăng ký nhắc nhở cho giải đấu này rồi', 400);
        }
        $data = $this->responseData($miniTournamentId);

        return ResponseHelper::success($data, 'Đăng ký nhắc nhở thành công');
    }
    /**
     * Hủy đăng ký nhắc nhở giải đấu nhỏ.
     *
     * @param int $miniTournamentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribe($miniTournamentId)
    {
        MiniTournamentUserNotification::where('user_id', auth()->id())
            ->where('mini_tournament_id', $miniTournamentId)
            ->delete();

        $data = $this->responseData($miniTournamentId);

        return ResponseHelper::success($data, 'Hủy đăng ký nhắc nhở thành công');
    }

    private function responseData($miniTournamentId)
    {
        $subscribed = MiniTournamentUserNotification::where('user_id', auth()->id())
            ->where('mini_tournament_id', $miniTournamentId)
            ->exists();

        return [
            'mini_tournament_id' => $miniTournamentId,
            'subscribed' => $subscribed,
        ];
    }
}
