<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\FollowResource;
use App\Http\Resources\UserListResource;
use App\Models\CompetitionLocation;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\FollowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * Danh sách theo dõi của người dùng
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $follows = Follow::where('user_id', $userId)
            ->with('followable')
            ->get()
            ->groupBy(fn($f) => strtolower(class_basename($f->followable_type)));

        $result = [];

        foreach ($follows as $shortType => $items) {
            $result[$shortType] = FollowResource::collection($items);
        }

        return ResponseHelper::success($result, 'Lấy danh sách theo dõi thành công');
    }
    /**
     * Theo dõi một đối tượng
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'followable_type' => 'required|string',
            'followable_id' => 'required|integer',
        ]);

        $follow = $this->follow(Auth::id(), $validated['followable_type'], $validated['followable_id']);

        if (!$follow) {
            return ResponseHelper::error('Theo dõi thất bại', 400);
        }

        if(!$follow->wasRecentlyCreated) {
            return ResponseHelper::error('Bạn đã theo dõi rồi', 400);
        }

        $followable = $follow->followable;

        // Gửi notification cho chủ thể được follow
        if (method_exists($followable, 'notify')) {
            $followable->notify(new FollowNotification(Auth::user(), $followable));
        }

        return ResponseHelper::success(
            new FollowResource($follow->load('followable')),
            'Theo dõi thành công'
        );
    }
    protected function follow(int $userId, string $type, int $id): ?Follow
    {
        $map = [
            'competition' => CompetitionLocation::class,
            'user' => User::class,
        ];

        $model = $map[strtolower($type)] ?? null;
        if (!$model) {
            return null;
        }

        if (!$model::whereKey($id)->exists()) {
            return null;
        }

        // Tạo hoặc lấy follow
        return Follow::firstOrCreate([
            'user_id' => $userId,
            'followable_id' => $id,
            'followable_type' => $model,
        ]);
    }
    /**
     * Hủy theo dõi một đối tượng
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'followable_type' => 'required|string',
            'followable_id'   => 'required|integer',
        ]);

        $deleted = $this->unfollow(Auth::id(), $validated['followable_type'], $validated['followable_id']);

        if (!$deleted) {
            return ResponseHelper::error('Hủy theo dõi thất bại hoặc đối tượng không tồn tại', 400);
        }

        return ResponseHelper::success([], 'Hủy theo dõi thành công');
    }
    
    protected function unfollow(int $userId, string $type, int $id): bool
    {
        $map = [
            'competition' => CompetitionLocation::class,
            'user'        => User::class,
        ];

        $model = $map[strtolower($type)] ?? null;
        if (!$model) {
            return false;
        }

        return Follow::where('user_id', $userId)
            ->where('followable_id', $id)
            ->where('followable_type', $model)
            ->delete() > 0;
    }

    /**
 * Lấy danh sách bạn bè (mutual follows)
 */
public function getFriends(Request $request)
{
    $validated = $request->validate([
        'per_page' => 'sometimes|integer|min:1|max:100',
    ]);

    $userId = Auth::id();
    $perPage = $validated['per_page'] ?? 15;

    // Lấy danh sách user mà current user đang follow
    $following = Follow::where('user_id', $userId)
        ->where('followable_type', User::class)
        ->pluck('followable_id')
        ->toArray();

    // Lấy danh sách user đang follow current user
    $followers = Follow::where('followable_id', $userId)
        ->where('followable_type', User::class)
        ->pluck('user_id')
        ->toArray();

    // Tìm giao = bạn bè
    $friendIds = array_intersect($following, $followers);

    // Lấy thông tin user
    $friends = User::whereIn('id', $friendIds)->paginate($perPage);

    return ResponseHelper::success(
        UserListResource::collection($friends),
        'Lấy danh sách bạn bè thành công'
    );
}
}
