<?php

namespace App\Services\TournamentType;

use Illuminate\Support\Collection;

/**
 * Service xử lý logic pairing teams cho knockout stage
 */
class TeamPairingService
{
    // Pairing modes
    const PAIRING_MODE_SEQUENTIAL = 'sequential';  // Tuần tự: A-B, C-D, E-F, G-H
    const PAIRING_MODE_SYMMETRIC = 'symmetric';    // Đối xứng: A-H, B-G, C-F, D-E
    const PAIRING_MODE_MANUAL = 'manual';

    /**
     * Sắp xếp đội advancing theo mode đã chọn
     */
    public function arrangeAdvancingTeams(
        $advancingByRank,
        ?string $pairingMode = null,
        ?array $manualPairings = null
    ): Collection {
        // Normalize: Trim và lowercase
        $pairingMode = $pairingMode ? strtolower(trim($pairingMode)) : null;

        return match ($pairingMode) {
            self::PAIRING_MODE_SYMMETRIC, 'symmetric' => $this->arrangeSymmetric($advancingByRank),
            self::PAIRING_MODE_MANUAL, 'manual' => $this->arrangeManual($advancingByRank, $manualPairings),
            default => $this->arrangeSequential($advancingByRank),
        };
    }

    /**
     * Pattern: Nhất A vs Nhì B, Nhất B vs Nhì A, Nhất C vs Nhì D, Nhất D vs Nhì C
     * Ví dụ 8 bảng: A-B, B-A, C-D, D-C, E-F, F-E, G-H, H-G
     */
    private function arrangeSequential($advancingByRank): Collection
    {
        $advancing = collect();

        $firstPlaceTeams = $advancingByRank->get(0, collect());
        $secondPlaceTeams = $advancingByRank->get(1, collect());

        $numFirstPlace = $firstPlaceTeams->count();
        $numSecondPlace = $secondPlaceTeams->count();

        // Pattern tuần tự
        for ($i = 0; $i < max($numFirstPlace, $numSecondPlace); $i += 2) {
            // Cặp thứ i: Nhất[i] vs Nhì[i+1]
            if ($i < $numFirstPlace) {
                $advancing->push($firstPlaceTeams->get($i));
            }
            if (($i + 1) < $numSecondPlace) {
                $advancing->push($secondPlaceTeams->get($i + 1));
            }

            // Cặp thứ i+1: Nhất[i+1] vs Nhì[i]
            if (($i + 1) < $numFirstPlace) {
                $advancing->push($firstPlaceTeams->get($i + 1));
            }
            if ($i < $numSecondPlace) {
                $advancing->push($secondPlaceTeams->get($i));
            }
        }

        // Xử lý các hạng còn lại (hạng 3, 4...)
        foreach ($advancingByRank as $rank => $teamsAtRank) {
            if ($rank < 2) continue;
            foreach ($teamsAtRank as $team) {
                $advancing->push($team);
            }
        }

        return $advancing;
    }

    /**
     * Pattern: Nhất A vs Nhì H, Nhất B vs Nhì G, Nhất C vs Nhì F, Nhất D vs Nhì E
     * Ví dụ 8 bảng: A-H, B-G, C-F, D-E, E-D, F-C, G-B, H-A
     * Khi chỉ có 1 đội/bảng (numAdvancing=1): symmetric pair đầu vs cuối → A-D, B-C (4 bảng)
     */
    private function arrangeSymmetric($advancingByRank): Collection
    {
        $firstPlaceTeams = $advancingByRank->get(0, collect());
        $secondPlaceTeams = $advancingByRank->get(1, collect());

        $numFirstPlace = $firstPlaceTeams->count();
        $numSecondPlace = $secondPlaceTeams->count();

        // === KHI CHỈ CÓ 1 ĐỘI/BẢNG (numSecondPlace === 0) ===
        // Symmetric pair đầu vs cuối: [A1, B1, C1, D1] → [A1, D1, B1, C1] → (A-D), (B-C)
        if ($numSecondPlace === 0 && $numFirstPlace > 0) {
            $reordered = collect();
            $numPairs = intdiv($numFirstPlace, 2);
            for ($i = 0; $i < $numPairs; $i++) {
                $reordered->push($firstPlaceTeams->get($i));
                $reordered->push($firstPlaceTeams->get($numFirstPlace - 1 - $i));
            }
            if ($numFirstPlace % 2 === 1) {
                $reordered->push($firstPlaceTeams->get($numPairs));
            }
            return $reordered;
        }

        // === EXISTING LOGIC: Nhất vs Nhì theo pattern đối xứng ===
        $advancing = collect();
        for ($i = 0; $i < max($numFirstPlace, $numSecondPlace); $i++) {
            if ($i < $numFirstPlace) {
                $advancing->push($firstPlaceTeams->get($i));
            }
            $oppositeIndex = $numSecondPlace - 1 - $i;
            if ($oppositeIndex >= 0 && $oppositeIndex < $numSecondPlace) {
                $advancing->push($secondPlaceTeams->get($oppositeIndex));
            }
        }

        foreach ($advancingByRank as $rank => $teamsAtRank) {
            if ($rank < 2) continue;
            foreach ($teamsAtRank as $team) {
                $advancing->push($team);
            }
        }

        return $advancing;
    }

    /**
     * Sắp xếp theo danh sách thủ công
     * $manualPairings format (FE convention mới):
     * [
     *   ['group_id' => 631, 'rank' => 1, 'position' => 0],  // Nhất bảng 631 ở slot 0 (pair 0, home)
     *   ['group_id' => 635, 'rank' => 2, 'position' => 0],  // Nhì bảng 635 ở slot 0 (pair 0, away)
     *   ['group_id' => 636, 'rank' => 1, 'position' => 1],  // Nhất bảng 636 ở slot 1 (pair 0, home) ...
     *   ...
     *   ['group_id' => 0,   'rank' => 2, 'position' => 6],  // Virtual "Nhì tốt nhất" #1 ở slot 6
     *   ['group_id' => 0,   'rank' => 2, 'position' => 7],  // Virtual "Nhì tốt nhất" #2 ở slot 7
     * ]
     *
     * Convention:
     *   - position = slot index (0-based), mỗi slot = 1 cặp = 2 entries (rank=1 và rank=2)
     *   - group_id = 0 + rank = 2 (1-based) → virtual "Nhì tốt nhất" #N
     *   - group_id = 0 + rank = 3 (1-based) → virtual "Ba tốt nhất" #N
     *     (BE tự resolve team cụ thể sau khi pool stage kết thúc)
     *   - Thứ tự virtual được xác định theo position (1, 2, 3, ... tương ứng #1, #2, #3)
     */
    private function arrangeManual($advancingByRank, ?array $manualPairings): Collection
    {
        if (empty($manualPairings)) {
            return $this->arrangeSequential($advancingByRank);
        }

        // ✅ Detect convention: old vs new
        // Old: position = slotIndex (0,0, 1,1, 2,2, ...) max < numSlots
        // New: position = slotIndex*2 + subIndex (0,1, 2,3, ...) max >= numSlots
        $maxPos = 0;
        foreach ($manualPairings as $p) {
            $maxPos = max($maxPos, (int)($p['position'] ?? 0));
        }
        $numSlots = count($manualPairings) > 0
            ? max(1, (int)ceil(sqrt(2 * count($manualPairings))))
            : 1;
        $isOldConvention = $maxPos < $numSlots && $maxPos > 0;

        // ✅ Normalize to new convention: position = slotIndex*2 + subIndex
        // Old: (position, rank=1) → (position*2, rank=1), (position, rank=2) → (position*2+1, rank=2)
        // New: already in correct format, no change needed
        foreach ($manualPairings as &$p) {
            $pos = (int)($p['position'] ?? 0);
            $rank = (int)($p['rank'] ?? 1);
            if ($isOldConvention) {
                $p['position'] = $pos * 2 + ($rank - 1);
            }
            // rank stays as-is (1 = Nhất, 2 = Nhì, 3 = Ba)
        }
        unset($p); // break reference

        // ✅ Bước 1: Sắp xếp FE pairings theo (position, rank) — Nhất (rank=1) trước Nhì (rank=2) trong cùng slot
        usort($manualPairings, function ($a, $b) {
            $posCmp = ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            if ($posCmp !== 0) return $posCmp;
            return ($a['rank'] ?? 1) <=> ($b['rank'] ?? 1);
        });

        // ✅ Bước 2: Map real groups theo (group_id, rank) → entry
        $teamMap = [];
        foreach ($advancingByRank as $rank => $teamsAtRank) {
            foreach ($teamsAtRank as $team) {
                if (($team->_from_group ?? null) !== null) {
                    $key = "{$team->_from_group}_{$rank}";
                    $teamMap[$key] = $team;
                }
            }
        }

        // ✅ Bước 3: Map virtual "Nhì/Ba tốt nhất" entries theo _virtual_index, phân theo rank
        $virtualByRank = [1 => [], 2 => []]; // rank=1 (0-based) = Nhì, rank=2 (0-based) = Ba
        foreach ($advancingByRank as $rank => $teamsAtRank) {
            foreach ($teamsAtRank as $team) {
                if (($team->_from_group ?? null) === null) {
                    $vIdx = (int) ($team->_virtual_index ?? 0);
                    $virtualByRank[$rank][$vIdx] = $team;
                }
            }
        }
        $virtualCursor = [1 => 0, 2 => 0]; // Đếm số virtual đã dùng theo rank

        // ✅ Bước 4: Build $advancing từ manual_pairings
        // Mỗi entry có position → knockoutIndex, basePosition = position % 2 (home/away)
        $advancing = collect();
        foreach ($manualPairings as $pairing) {
            $groupId = $pairing['group_id'] ?? null;
            $rankInput = (int)($pairing['rank'] ?? 1);
            $position = (int)($pairing['position'] ?? 0);
            $rankIndex = $rankInput - 1; // 0-based: 0 = Nhất, 1 = Nhì, 2 = Ba

            // Virtual "Nhì/Ba tốt nhất" (group_id = 0, rank = 2 hoặc 3)
            if ((int) $groupId === 0 && $rankIndex >= 1) {
                $virtualCursor[$rankIndex]++;
                if (isset($virtualByRank[$rankIndex][$virtualCursor[$rankIndex]])) {
                    $advancing->push($virtualByRank[$rankIndex][$virtualCursor[$rankIndex]]);
                }
                continue;
            }

            // Real entry
            $key = "{$groupId}_{$rankIndex}";
            if (isset($teamMap[$key])) {
                $advancing->push($teamMap[$key]);
            }
        }

        return $advancing;
    }
}
