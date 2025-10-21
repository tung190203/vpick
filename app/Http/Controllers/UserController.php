<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\ClubResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    private const VALIDATION_RULE = 'sometimes';
    public function index(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'sometimes',
            'lng' => 'sometimes',
            'radius' => 'sometimes|numeric|min:1',
            'minLat' => self::VALIDATION_RULE,
            'maxLat' => self::VALIDATION_RULE,
            'minLng' => self::VALIDATION_RULE,
            'maxLng' => self::VALIDATION_RULE,
            'keyword' => 'nullable|string|max:255',
            'sport_id' => 'sometimes|exists:sports,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'location_id' => 'sometimes|exists:locations,id',
            'favourite_player' => 'nullable|boolean',
            'is_connected' => 'nullable|boolean',
            'gender' => 'nullable|in:' . implode(',', User::GENDER),
            'time_of_day' => 'sometimes|array',
            'time_of_day.*' => 'in:' . implode(',', User::PLAY_TIME_OPTIONS),
            'rating' => 'sometimes|array',
            'rating.*' => 'sometimes',
            'online_recently' => 'nullable|boolean',
            'online_before_minutes' => 'sometimes|integer|min:1',
            'recent_matches' => 'sometimes|array',
            'recent_matches.*' => 'sometimes|in:' . implode(',', User::RECENT_MATCHES_OPTIONS),
            'same_club_id' => 'sometimes|array',
            'same_club_id.*' => 'exists:clubs,id',
            'verify_profile' => 'nullable|boolean',
            'achievement' => 'sometimes',
        ]);

        $query = User::withFullRelations()->filter($validated)->visibleFor(auth()->user());

        $hasFilter = collect([
            'sport_id',
            'keyword',
            'lat',
            'lng',
            'radius',
            'location_id',
            'favourite_player',
            'is_connected',
            'gender',
            'time_of_day',
            'rating',
            'online_recently',
            'online_before_minutes',
            'recent_matches',
            'same_club_id',
            'verify_profile',
            'achievement',
        ])->some(fn($key) => $request->filled($key));

        if (!$hasFilter && (!empty($validated['minLat']) || !empty($validated['maxLat']) || !empty($validated['minLng']) || !empty($validated['maxLng']))) {
            $query->inBounds(
                $validated['minLat'],
                $validated['maxLat'],
                $validated['minLng'],
                $validated['maxLng']
            );
        }

        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            $query->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
        }

        // Paginate
        $paginated = $query->paginate($validated['per_page'] ?? User::PER_PAGE);

        // Xử lý recent_matches sau khi paginate
        if (!empty($validated['recent_matches']) && is_array($validated['recent_matches'])) {
            // Lấy collection từ paginator
            $collection = $paginated->getCollection();

            // Tính total_matches_count
            $collection->transform(function ($user) {
                $user->total_matches_count = ($user->matches_count ?? 0) + ($user->mini_matches_count ?? 0);
                return $user;
            });

            // Filter theo low/medium/high
            $collection = $collection->filter(function ($user) use ($validated) {
                foreach ($validated['recent_matches'] as $opt) {
                    if ($opt === 'high' && $user->total_matches_count > 12)
                        return true;
                    if ($opt === 'medium' && $user->total_matches_count >= 5 && $user->total_matches_count <= 12)
                        return true;
                    if ($opt === 'low' && $user->total_matches_count <= 4)
                        return true;
                }
                return false;
            })->values();

            // Tạo lại LengthAwarePaginator
            $paginated = new LengthAwarePaginator(
                $collection,
                $paginated->total(),       // giữ tổng số item
                $paginated->perPage(),     // giữ per page
                $paginated->currentPage(), // giữ trang hiện tại
                ['path' => $request->url(), 'query' => $request->query()] // giữ query string
            );
        }

        $data = [
            'users' => UserResource::collection($paginated),
            'clubs' => ClubResource::collection(auth()->user()->clubs()->get()),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách người dùng thành công');
    }
    public function show($id)
    {
        $user = User::withFullRelations()->find($id);
        if (!$user) {
            return ResponseHelper::error('Người dùng không tồn tại', 404);
        }
        return ResponseHelper::success(new UserResource($user), 'Lấy thông tin người dùng thành công');
    }
    public function update(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'location_id' => 'nullable|exists:locations,id',
            'about' => 'nullable|string|max:300',
            'password' => 'nullable|string|min:8',
            'is_profile_completed' => 'nullable|boolean',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|numeric|in:' . implode(',', User::GENDER),
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'sport_ids' => 'nullable|array',
            'sport_ids.*' => 'exists:sports,id',
            'score_value' => 'nullable|array',
            'score_value.*' => 'integer|min:0',
            'visibility' => 'nullable|in:open,friend-only',
            'self_score' => 'nullable|string|max:255',
        ]);
        $user = User::findOrFail(auth()->id());
        $data = collect($validated)->except(['avatar_url', 'password', 'is_profile_completed', 'score_value', 'sport_ids'])->toArray();

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }
        if ($request->hasFile('avatar_url')) {
            if ($user->avatar_url) {
                $oldPath = str_replace(asset('storage/') . '/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('avatar_url')->store('avatars', 'public');
            $data['avatar_url'] = asset('storage/' . $path);
        }

        if ($request->hasFile('thumbnail')) {
            if ($user->thumbnail) {
                $oldPath = str_replace(asset('storage/') . '/', '', $user->thumbnail);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = asset('storage/' . $path);
        }

        if (!empty($validated['is_profile_completed']) && !$user->is_profile_completed) {
            $data['is_profile_completed'] = true;
        }

        $user->update($data);

        if (isset($validated['sport_ids'])) {
            $user->sports()->each(function ($userSport) {
                $userSport->scores()->delete();
                $userSport->delete();
            });
            if (!empty($validated['sport_ids'])) {
                foreach ($validated['sport_ids'] as $index => $sportId) {
                    $userSport = $user->sports()->create([
                        'sport_id' => $sportId,
                        'tier' => null
                    ]);

                    if (!empty($validated['score_value'][$index])) {
                        $userSport->scores()->create([
                            'score_type' => 'personal_score',
                            'score_value' => $validated['score_value'][$index]
                        ]);
                    }
                }
            }
        }

        $data = [
            'user' => UserResource::make($user->fresh()->loadFullRelations()),
        ];

        return ResponseHelper::success($data, 'Cập nhật thông tin người dùng thành công');
    }

    public function searchLocation(Request $request, GeocodingService $geocoder)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $results = $geocoder->search($validated['query']);

        return ResponseHelper::success($results, 'Tìm kiếm địa điểm thành công');
    }

    public function detailGooglePlace(Request $request, GeocodingService $geocoder)
    {
        $validated = $request->validate([
            'place_id' => 'required|string|max:255',
        ]);

        $result = $geocoder->getGooglePlaceDetail($validated['place_id']);

        return ResponseHelper::success($result, 'Lấy chi tiết địa điểm thành công');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return ResponseHelper::error('Người dùng không tồn tại', 404);
        }
        if ($user->id !== auth()->id()) {
            return ResponseHelper::error('Bạn không có quyền xóa người dùng này', 403);
        }

        // Xóa ảnh đại diện khỏi storage
        if ($user->avatar_url) {
            $oldPath = str_replace(asset('storage/') . '/', '', $user->avatar_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $user->follows()->delete();
        $user->followings()->delete();
        $user->referee()->delete();
        $user->playTimes()->delete();
        $user->badges()->delete();
        $user->sport()->delete();
        $user->sports()->delete();
        $user->vnduprScores()->delete();
        $user->clubs()->detach();
        $user->participants()->delete();
        $user->miniParticipants()->delete();

        $user->delete();

        return ResponseHelper::success(null, 'Xóa người dùng thành công');
    }
}
