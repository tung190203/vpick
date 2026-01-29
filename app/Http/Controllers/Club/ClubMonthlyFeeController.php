<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubMonthlyFeeResource;
use App\Models\Club\Club;
use App\Models\Club\ClubMonthlyFee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubMonthlyFeeController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        
        $validated = $request->validate([
            'is_active' => 'sometimes|boolean',
        ]);

        $query = $club->monthlyFees();

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        $fees = $query->get();

        return ResponseHelper::success(ClubMonthlyFeeResource::collection($fees), 'Lấy danh sách cấu hình phí thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo cấu hình phí', 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|max:3',
            'due_day' => 'required|integer|min:1|max:31',
            'is_active' => 'sometimes|boolean',
        ]);

        $fee = ClubMonthlyFee::create([
            'club_id' => $club->id,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'VND',
            'due_day' => $validated['due_day'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return ResponseHelper::success(new ClubMonthlyFeeResource($fee), 'Tạo cấu hình phí thành công', 201);
    }

    public function show($clubId, $feeId)
    {
        $fee = ClubMonthlyFee::where('club_id', $clubId)->findOrFail($feeId);
        return ResponseHelper::success(new ClubMonthlyFeeResource($fee), 'Lấy thông tin cấu hình phí thành công');
    }

    public function update(Request $request, $clubId, $feeId)
    {
        $fee = ClubMonthlyFee::where('club_id', $clubId)->findOrFail($feeId);
        $userId = auth()->id();

        $club = $fee->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền cập nhật', 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'currency' => 'sometimes|string|max:3',
            'due_day' => 'sometimes|integer|min:1|max:31',
            'is_active' => 'sometimes|boolean',
        ]);

        $fee->update($validated);
        return ResponseHelper::success(new ClubMonthlyFeeResource($fee->fresh()), 'Cập nhật cấu hình phí thành công');
    }

    public function destroy($clubId, $feeId)
    {
        $fee = ClubMonthlyFee::where('club_id', $clubId)->findOrFail($feeId);
        $userId = auth()->id();

        $club = $fee->club;
        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xóa', 403);
        }

        if ($fee->payments()->exists()) {
            return ResponseHelper::error('Không thể xóa vì có payments', 422);
        }

        $fee->delete();
        return ResponseHelper::success('Xóa cấu hình phí thành công');
    }
}
