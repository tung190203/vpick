<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubFundCollectionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClubFundCollectionController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => ['sometimes', Rule::enum(ClubFundCollectionStatus::class)],
        ]);

        $query = $club->fundCollections()->with(['creator', 'contributions.user']);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $collections = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = [
            'collections' => ClubFundCollectionResource::collection($collections),
        ];
        $meta = [
            'current_page' => $collections->currentPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
            'last_page' => $collections->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách đợt thu thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo đợt thu', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|max:3',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'qr_code_url' => 'nullable|string',
        ]);

        $collection = ClubFundCollection::create([
            'club_id' => $club->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_amount' => $validated['target_amount'],
            'collected_amount' => 0,
            'currency' => $validated['currency'] ?? 'VND',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => ClubFundCollectionStatus::Pending,
            'qr_code_url' => $validated['qr_code_url'] ?? null,
            'created_by' => $userId,
        ]);

        $collection->load(['creator', 'club', 'contributions.user']);

        return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Tạo đợt thu thành công', 201);
    }

    public function show($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)
            ->with(['creator', 'club', 'contributions.user'])
            ->findOrFail($collectionId);

        return ResponseHelper::success(
            new ClubFundCollectionResource($collection),
            'Lấy thông tin đợt thu thành công'
        );
    }

    public function update(Request $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        $club = $collection->club;
        if (!$club->canManageFinance($userId) || $collection->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền cập nhật đợt thu này', 403);
        }

        if (!in_array($collection->status, ['pending', 'active'])) {
            return ResponseHelper::error('Chỉ có thể cập nhật đợt thu đang pending hoặc active', 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'sometimes|numeric|min:0.01',
            'end_date' => 'nullable|date|after:start_date',
            'qr_code_url' => 'nullable|string',
        ]);

        $collection->update($validated);
        $collection->load(['creator', 'club', 'contributions.user']);

        return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Cập nhật đợt thu thành công');
    }

    public function destroy($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        $club = $collection->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền hủy đợt thu', 403);
        }

        if (!in_array($collection->status, [ClubFundCollectionStatus::Pending, ClubFundCollectionStatus::Active])) {
            return ResponseHelper::error('Chỉ có thể hủy đợt thu đang pending hoặc active', 422);
        }

        $collection->update(['status' => ClubFundCollectionStatus::Cancelled]);

        return ResponseHelper::success('Đợt thu đã được hủy');
    }

    public function getQrCode($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);

        if (!$collection->qr_code_url) {
            return ResponseHelper::error('Đợt thu chưa có mã QR', 404);
        }

        return ResponseHelper::success([
            'qr_code_url' => $collection->qr_code_url,
            'qr_code_data' => null,
        ], 'Lấy mã QR thành công');
    }
}
