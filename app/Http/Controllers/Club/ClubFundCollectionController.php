<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\CreateQrCodeRequest;
use App\Http\Requests\Club\StoreFundCollectionRequest;
use App\Http\Requests\Club\UpdateFundCollectionRequest;
use App\Http\Resources\Club\ClubFundCollectionResource;
use App\Http\Resources\Club\ClubFundContributionResource;
use App\Http\Resources\Club\ClubMyFundCollectionResource;
use App\Http\Resources\UserResource;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Services\Club\ClubFundCollectionService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;

class ClubFundCollectionController extends Controller
{
    public function __construct(
        protected ClubFundCollectionService $collectionService
    ) {
    }

    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        $collections = $this->collectionService->getCollections($club, $validated);

        $data = ['collections' => ClubFundCollectionResource::collection($collections)];
        $meta = [
            'current_page' => $collections->currentPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
            'last_page' => $collections->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách đợt thu thành công', 200, $meta);
    }

    public function store(StoreFundCollectionRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $data = $request->validated();

            if ($request->hasFile('qr_image')) {
                $data['qr_code_url'] = app(ImageOptimizationService::class)->optimizeThumbnail(
                    $request->file('qr_image'),
                    'qr_codes',
                    90
                );
                unset($data['qr_image']);
            }

            $collection = $this->collectionService->createCollection($club, $data, $userId);
            $collection->load(['creator', 'club', 'contributions.user', 'assignedMembers']);

            return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Tạo đợt thu thành công', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function show($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)
            ->with(['creator', 'club'])
            ->findOrFail($collectionId);

        $detail = $this->collectionService->getCollectionDetail($collection);

        return ResponseHelper::success([
            'collection' => new ClubFundCollectionResource($detail['collection']),
            'approved_payments' => $detail['approved_payments']->map(function ($item) {
                return [
                    'user' => new UserResource($item['user']),
                    'amount_due' => $item['amount_due'],
                    'amount_paid' => $item['amount_paid'],
                    'payment_status' => $item['payment_status'],
                    'paid_at' => $item['paid_at'],
                    'contribution' => new ClubFundContributionResource($item['contribution']),
                ];
            }),
            'waiting_approval_payments' => $detail['waiting_approval_payments']->map(function ($item) {
                return [
                    'user' => new UserResource($item['user']),
                    'amount_due' => $item['amount_due'],
                    'amount_paid' => $item['amount_paid'],
                    'payment_status' => $item['payment_status'],
                    'paid_at' => $item['paid_at'],
                    'contribution' => new ClubFundContributionResource($item['contribution']),
                ];
            }),
            'no_payment_yet' => $detail['no_payment_yet']->map(function ($item) {
                return [
                    'user' => new UserResource($item['user']),
                    'amount_due' => $item['amount_due'],
                    'amount_paid' => $item['amount_paid'],
                    'payment_status' => $item['payment_status'],
                    'paid_at' => $item['paid_at'],
                    'contribution' => $item['contribution'] ? new ClubFundContributionResource($item['contribution']) : null,
                ];
            }),
            'summary' => $detail['summary'],
        ], 'Lấy thông tin đợt thu thành công');
    }

    public function update(UpdateFundCollectionRequest $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $data = $request->validated();
            if (!empty($data['end_date']) && $collection->start_date && $data['end_date'] < $collection->start_date->format('Y-m-d')) {
                return ResponseHelper::error('Ngày kết thúc phải sau ngày bắt đầu đợt thu', 422);
            }
            $collection = $this->collectionService->updateCollection($collection, $data, $userId);
            $collection->load(['creator', 'club', 'contributions.user']);

            return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Cập nhật đợt thu thành công');
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'active') ? 422 : 403;
            return ResponseHelper::error($e->getMessage(), $statusCode);
        }
    }

    public function destroy($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $this->collectionService->cancelCollection($collection, $userId);
            return ResponseHelper::success('Đợt thu đã được hủy');
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'active') ? 422 : 403;
            return ResponseHelper::error($e->getMessage(), $statusCode);
        }
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

    public function listQrCodes(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $collections = $this->collectionService->getQrCodes($club, $validated);

        $data = ['qr_codes' => ClubFundCollectionResource::collection($collections)];
        $meta = [
            'current_page' => $collections->currentPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
            'last_page' => $collections->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách mã QR thành công', 200, $meta);
    }

    public function createQrCode(CreateQrCodeRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        try {
            $collection = $this->collectionService->createQrCode($club, $request->validated(), $userId);
            $collection->load(['creator', 'club', 'contributions.user']);

            return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Tạo mã QR thành công', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function getMyCollections(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $validated = $request->validate([
            'payment_status' => 'sometimes|in:need_payment,pending,confirmed',
        ]);

        $result = $this->collectionService->getMyCollections($club, $userId);

        if (!empty($validated['payment_status'])) {
            $key = $validated['payment_status'];
            $result = [
                'need_payment' => $key === 'need_payment' ? $result['need_payment'] : collect(),
                'pending' => $key === 'pending' ? $result['pending'] : collect(),
                'confirmed' => $key === 'confirmed' ? $result['confirmed'] : collect(),
            ];
        }

        return ResponseHelper::success([
            'need_payment' => ClubMyFundCollectionResource::collection($result['need_payment']),
            'pending' => ClubMyFundCollectionResource::collection($result['pending']),
            'confirmed' => ClubMyFundCollectionResource::collection($result['confirmed']),
            'summary' => [
                'need_payment_count' => $result['need_payment']->count(),
                'pending_count' => $result['pending']->count(),
                'confirmed_count' => $result['confirmed']->count(),
            ],
        ], 'Lấy danh sách đợt thu của tôi thành công');
    }
}
