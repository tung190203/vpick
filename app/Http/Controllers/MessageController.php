<?php

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PrivateMessageResource;
use App\Models\Message;
use App\Models\User;
use App\Notifications\PrivateMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $attachmentUrl = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $attachmentUrl = Storage::url($path);

            $mimeType = $request->file('attachment')->getClientMimeType();
            $attachmentType = explode('/', $mimeType)[0];
        }

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
        ]);

        $receiver = User::find($request->receiver_id);
        $receiver->notify(new PrivateMessageNotification($message));

        broadcast(new MessageSent($message))->toOthers();

        return ResponseHelper::success('Gửi tin nhắn thành công', new PrivateMessageResource($message));
    }

    public function markConversationAsRead($senderId)
    {
        $receiverId = auth()->id();

        $count = Message::where('receiver_id', $receiverId)
            ->where('sender_id', $senderId)
            ->whereNull('read_at')
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    
        if ($count > 0) {
            broadcast(new ConversationRead($receiverId, $senderId, now()))->toOthers();
        }
    
        return ResponseHelper::success('Đánh dấu cuộc trò chuyện đã đọc', ['updated_count' => $count]);
    }

    public function getConversation(Request $request, $userId)
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    
        $perPage = $validated['per_page'] ?? Message::PER_PAGE;
    
        $messages = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', auth()->id())
                      ->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', auth()->id());
            })
            ->with('sender:id,full_name,avatar_url')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    
        $data = [
            'messages' => PrivateMessageResource::collection($messages),
        ];
    
        $meta = [
            'current_page' => $messages->currentPage(),
            'last_page'    => $messages->lastPage(),
            'per_page'     => $messages->perPage(),
            'total'        => $messages->total(),
        ];
    
        return ResponseHelper::success($data, 'Lấy cuộc trò chuyện thành công', 200, $meta);
    }
}
