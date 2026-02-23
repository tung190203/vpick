<?php

namespace App\Services\Club;

use App\Enums\ClubReportReasonType;
use App\Enums\ClubReportStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClubReportService
{
    public function createReport(Club $club, int $userId): ClubReport
    {
        return ClubReport::create([
            'club_id' => $club->id,
            'user_id' => $userId,
            'reason_type' => ClubReportReasonType::Other,
            'description' => null,
            'status' => ClubReportStatus::Pending,
        ]);
    }

    public function getReports(Club $club, array $filters): LengthAwarePaginator
    {
        $query = $club->reports()->with(['reporter', 'reviewer']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['reason_type'])) {
            $query->where('reason_type', $filters['reason_type']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function hasUserReportedClub(Club $club, int $userId): bool
    {
        return $club->reports()
            ->where('user_id', $userId)
            ->where('status', ClubReportStatus::Pending)
            ->exists();
    }
}
