<?php

namespace App\Http\Controllers;

use App\Events\MiniTournamentMessageSent;
use App\Events\TournamentMessageSent;
use App\Helpers\ResponseHelper;
use App\Http\Resources\MessageResource;
use App\Models\DeviceToken;
use App\Models\MiniTournament;
use App\Models\MiniTournamentMessage;
use App\Models\Tournament;
use App\Models\TournamentMessage;
use App\Models\User;
use App\Notifications\MiniTournamentMessageNotification;
use App\Notifications\TournamentMessageNotification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SendMessageController extends Controller
{
    public function storeMessageMiniTour(Request $request, $tournamentId)
    {
        $request->validate([
            'type' => 'required|in:text,image,voice,emoji,file',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $tournament = MiniTournament::findOrFail($tournamentId);

        // Lấy danh sách user thực sự thuộc tournament (user trực tiếp + team members)
        $allUserIds = $tournament->all_users->pluck('id');

        // Kiểm tra quyền gửi message
        if (!$allUserIds->contains(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền gửi tin nhắn vào giải đấu này', 403);
        }

        $content = $request->input('content', null);
        $meta = null;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat', 'public');
            $content = $path;
            $meta = [
                'filename' => $request->file('file')->getClientOriginalName(),
                'size' => $request->file('file')->getSize(),
            ];
        }

        $message = MiniTournamentMessage::create([
            'mini_tournament_id' => $tournamentId,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'content' => $content,
            'meta' => $meta,
        ])->load('user');
        // Gửi thông báo đến tất cả user trong tournament, trừ người gửi
        foreach ($allUserIds as $userId) {
            if ($userId != Auth::id()) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new MiniTournamentMessageNotification($message));
                }
            }
        }
        broadcast(new MiniTournamentMessageSent(new MessageResource($message->load('user'))))->toOthers();

        $recipientIds = $allUserIds
            ->filter(fn ($id) => $id != Auth::id())
            ->values()
            ->toArray();

        if (!empty($recipientIds)) {
            $this->pushToUsers(
                $recipientIds,
                $tournament->name,
                Auth::user()->full_name . ': ' . $this->formatChatPreview($message),
                [
                    'type' => 'MINI_TOURNAMENT_CHAT_MESSAGE',
                    'mini_tournament_id' => $tournament->id,
                    'message_id' => $message->id,
                ]
            );
        }

        return ResponseHelper::success(new MessageResource($message->load('user')), 'Gửi tin nhắn thành công');
    }

    public function getMessagesMiniTour(Request $request, $tournamentId)
    {
        $validate = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:200'
        ]);
        $tournament = MiniTournament::withFullRelations()->findOrFail($tournamentId);
        $allStaffIds = $tournament->staff->pluck('id');
        $allUserIds = $tournament->all_users->pluck('id');

        $allIds = array_merge($allStaffIds->toArray(), $allUserIds->toArray());

        // Kiểm tra quyền xem message
        if (!in_array(Auth::id(), $allIds)) {
            return ResponseHelper::error('Bạn không có quyền xem tin nhắn trong giải đấu này', 403);
        }

        $messages = MiniTournamentMessage::where('mini_tournament_id', $tournamentId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate($validate['per_page'] ?? MiniTournamentMessage::PER_PAGE);

        $meta = [
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ];

        return ResponseHelper::success(MessageResource::collection($messages), 'Lấy tin nhắn thành công', 200, $meta);
    }

    public function storeMessageTour(Request $request, $tournamentId)
    {
        $request->validate([
            'type' => 'required|in:text,image,voice,emoji,file',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);
        $tournament = Tournament::findOrFail($tournamentId);

        $allUserIds = $tournament->all_users->pluck('id');

        if (!$allUserIds->contains(Auth::id())) {
            return ResponseHelper::error('Bạn không có quyền gửi tin nhắn vào giải đấu này', 403);
        }
        $content = $request->input('content', null);
        $meta = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat', 'public');
            $content = $path;
            $meta = [
                'filename' => $request->file('file')->getClientOriginalName(),
                'size' => $request->file('file')->getSize(),
            ];
        }
        $message = TournamentMessage::create([
            'tournament_id' => $tournamentId,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'content' => $content,
            'meta' => $meta,
        ])->load('user');

        foreach ($allUserIds as $userId) {
            if ($userId != Auth::id()) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new TournamentMessageNotification($message));
                }
            }
        }

        broadcast(new TournamentMessageSent(new MessageResource($message->load('user'))))->toOthers();

        $recipientIds = $allUserIds
            ->filter(fn ($id) => $id != Auth::id())
            ->values()
            ->toArray();

        if (!empty($recipientIds)) {
            $this->pushToUsers(
                $recipientIds,
                $tournament->name,
                Auth::user()->full_name . ': ' . $this->formatChatPreview($message),
                [
                    'type' => 'TOURNAMENT_CHAT_MESSAGE',
                    'tournament_id' => $tournament->id,
                    'message_id' => $message->id,
                ]
            );
        }

        return ResponseHelper::success(new MessageResource($message->load('user')), 'Gửi tin nhắn thành công');
    }

    public function getMessagesTour(Request $request, $tournamentId)
    {
        $validate = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:200'
        ]);
        $tournament = Tournament::withFullRelations()->findOrFail($tournamentId);
        $allStaffIds = $tournament->staff->pluck('id');
        $allUserIds = $tournament->all_users->pluck('id');

        $allIds = array_merge($allStaffIds->toArray(), $allUserIds->toArray());

        if (!in_array(Auth::id(), $allIds)) {
            return ResponseHelper::error('Bạn không có quyền xem tin nhắn trong giải đấu này', 403);
        }

        $messages = TournamentMessage::where('tournament_id', $tournamentId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($validate['per_page'] ?? TournamentMessage::PER_PAGE);

        $meta = [
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ];

        return ResponseHelper::success(
            MessageResource::collection($messages->reverse()->values()), 
            'Lấy tin nhắn thành công', 
            200, 
            $meta
        );
    }

    private function pushToUsers(array $userIds, string $title, string $body, array $data = [])
    {
        $devices = DeviceToken::whereIn('user_id', $userIds)
            ->where('is_enabled', true)
            ->get();

        if ($devices->isEmpty()) return;

        $firebase = app(FirebaseService::class);

        foreach ($devices as $device) {
            try {
                $firebase->sendToUser(
                    $device->token,
                    $title,
                    $body,
                    $data
                );
            } catch (\Throwable $e) {
                $device->update(['is_enabled' => false]);
            }
        }
    }

    private function formatChatPreview($message): string
    {
        return match ($message->type) {
            'text'  => Str::limit($message->content, 80),
            'image' => 'Hình ảnh',
            'voice' => 'Tin nhắn thoại',
            'file'  => 'Tệp đính kèm',
            'emoji' => $message->content,
            default => 'Tin nhắn mới',
        };
    }
}
