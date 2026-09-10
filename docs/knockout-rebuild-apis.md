
## API 1: `GET /tournament-types/{id}/knockout-candidates`

### Endpoint

```
GET /api/tournament-types/{tournamentType}/knockout-candidates
```

### Mục đích

Trả về danh sách **ứng viên vào vòng knockout** kèm `team_label` (thân thiện với UI), phục vụ cho FE modal "Ghép cặp thủ công".

**Hành vi:**

- Nếu **vòng bảng chưa hoàn thành** → trả `{pool_completed: false, candidates: []}` (KHÔNG error).
- Nếu **vòng bảng đã hoàn thành 100%** (tất cả trận `round=1` có `status='completed'`) → trả danh sách candidates gồm:
  - **Real candidates**: Nhất/Nhì/Ba các bảng (lấy từ `standings`).
  - **Virtual candidates**: "Nhì tốt nhất #N", "Ba tốt nhất #N" (từ cross-group comparison khi cần).



### Request

| Field | Type | Required | Mô tả |
|-------|------|----------|-------|
| (path) `tournamentType` | int | ✅ | ID của tournament type |

### Response thành công (HTTP 200)

```json
{
    "status": true,
    "message": "Lấy danh sách ứng viên vào vòng sau thành công",
    "data": {
        "pool_completed": true,
        "candidates": [
            {
                "team_id": 1593,
                "team_name": "Đội số 2",
                "team_avatar": null,
                "group_id": 668,
                "group_name": "Bảng A",
                "group_position": 1,
                "candidate_type": "winner",
                "team_label": "Nhất A",
                "is_virtual": false
            },
            {
                "team_id": 1596,
                "team_name": "Đội số 5",
                "team_avatar": null,
                "group_id": 669,
                "group_name": "Bảng B",
                "group_position": 1,
                "candidate_type": "winner",
                "team_label": "Nhất B",
                "is_virtual": false
            },
            {
                "team_id": 1598,
                "team_name": "Đội số 7",
                "team_avatar": null,
                "group_id": 670,
                "group_name": "Bảng C",
                "group_position": 1,
                "candidate_type": "winner",
                "team_label": "Nhất C",
                "is_virtual": false
            },
            {
                "team_id": 1592,
                "team_name": "Đội số 1",
                "team_avatar": null,
                "group_id": 668,
                "group_name": "Bảng A",
                "group_position": 2,
                "candidate_type": "runner_up",
                "team_label": "Nhì tốt nhất #1",
                "is_virtual": true
            }
        ]
    }
}
```

### Response khi vòng bảng chưa hoàn thành (HTTP 200)

```json
{
    "status": true,
    "message": "Lấy danh sách ứng viên vào vòng sau thành công",
    "data": {
        "pool_completed": false,
        "candidates": []
    }
}
```

### Response lỗi (HTTP 500)

```json
{
    "status": false,
    "message": "Có lỗi xảy ra khi lấy danh sách ứng viên: <error message>",
    "data": null
}
```

### Mô tả các trường trong `candidates[]`

| Field | Type | Mô tả |
|-------|------|-------|
| `team_id` | int\|null | ID đội (null nếu không resolve được) |
| `team_name` | string\|null | Tên đội |
| `team_avatar` | string\|null | URL avatar đội (có thể null) |
| `group_id` | int\|null | ID bảng gốc của đội (real) hoặc bảng gốc của Nhì/Ba tốt nhất (virtual) |
| `group_name` | string\|null | Tên bảng (VD: "Bảng A") |
| `group_position` | int | Vị trí trong bảng (1 = Nhất, 2 = Nhì, 3 = Ba) |
| `candidate_type` | string | `winner` \| `runner_up` \| `third_place` \| `rank_N` |
| `team_label` | string | Label thân thiện (VD: "Nhất A", "Nhì tốt nhất #1") |
| `is_virtual` | bool | `true` nếu là virtual (Nhì/Ba tốt nhất), `false` nếu là real |

---

## API 2: `POST /tournament-types/{id}/knockout-rebuild-pairing`

### Endpoint

```
POST /api/tournament-types/{tournamentType}/knockout-rebuild-pairing
```

### Mục đích

Áp dụng **ghép cặp thủ công** từ FE cho vòng knockout. API này chỉ:

- **Reassign** `home_team_id` / `away_team_id` cho các trận `round=2 main bracket`.
- **KHÔNG** động vào `round ≥ 3` (giữ nguyên cấu trúc bracket đã có).
- **KHÔNG** động vào resurrection bracket (`bracket_type='sub'`).
- **KHÔNG** động vào `next_match_id` / `next_position` của matches round=2.
- **KHÔNG** ảnh hưởng logic `pairing_mode` cũ.

### Điều kiện tiên quyết

1. `format = FORMAT_MIXED` (= 1).
2. **Vòng bảng đã hoàn thành 100%** (tất cả trận `round=1` có `status='completed'`).
3. **Không có trận knockout đã khóa** ở `round ≥ 2` (chưa có trận nào `status='completed'` với `results.confirmed = true`).
4. Round=2 main bracket phải đã được tạo trước đó.

### Validation Rules

| Field | Rule | Mô tả |
|-------|------|-------|
| `manual_pairings` | `required\|array\|min:1` | Mảng các entry, không được rỗng |
| `manual_pairings.*.group_id` | `required` | ID bảng (int hoặc string convertible) |
| `manual_pairings.*.rank` | `required\|integer\|min:1\|max:10` | Hạng trong bảng (1 = Nhất, 2 = Nhì, 3 = Ba) |
| `manual_pairings.*.position` | `required\|integer\|min:0` | Vị trí slot (xem convention bên dưới) |

**Sanity check:**
- Số entries phải là số chẵn (mỗi cặp = 2 entries: nhất + nhì).
- Không có team_id nào xuất hiện quá 1 lần trong manual_pairings (mỗi team chỉ được vào 1 slot).

### Convention của `position`

Có 2 convention, server tự detect:

**Convention mới** (khuyến nghị):

```json
[
    {"group_id": 668, "rank": 1, "position": 0},
    {"group_id": 669, "rank": 1, "position": 1},
    {"group_id": 670, "rank": 1, "position": 2},
    {"group_id": 0,   "rank": 2, "position": 3}
]
```

→ Position = slotIndex × 2 + subIndex (nhất và nhì khác position).

### Quy tắc resolve team_id

| `group_id` | `rank` | Resolve từ |
|------------|--------|-----------|
| > 0 | 1 | Real group → Nhất bảng (từ standings) |
| > 0 | 2 | Real group → Nhì bảng (từ standings) |
| > 0 | 3 | Real group → Ba bảng (từ standings) |
| = 0 | 2 | Virtual → "Nhì tốt nhất" (resolve từ cross-group comparison, lấy theo thứ tự xuất hiện) |
| = 0 | 3 | Virtual → "Ba tốt nhất" (resolve từ cross-group comparison, lấy theo thứ tự xuất hiện) |

### Request

```json
{
    "manual_pairings": [
        {"group_id": 668, "rank": 1, "position": 0},
        {"group_id": 0,   "rank": 2, "position": 1},
        {"group_id": 669, "rank": 1, "position": 2},
        {"group_id": 670, "rank": 1, "position": 3}
    ]
}
```

### Response thành công (HTTP 200)

```json
{
    "status": true,
    "message": "Rebuild pairing thành công. Đã gán lại 4 cặp đấu (virtual resolved: 1).",
    "data": {
        "tournament_type": {
            // TournamentTypeResource — full payload của tournament type đã refresh
        },
        "rebuild_result": {
            "reassigned_pairs": 4,
            "virtual_resolved": 1
        }
    }
}
```

| Field trong `rebuild_result` | Mô tả |
|------------------------------|-------|
| `reassigned_pairs` | Tổng số entries đã được gán vào round=2 main |
| `virtual_resolved` | Số entries là virtual (group_id=0) đã resolve được team_id |

### Response lỗi

**HTTP 422** — Validation input:

```json
{
    "status": false,
    "message": "Số lượng manual_pairings phải là số chẵn (mỗi cặp = 2 entries: nhất + nhì).",
    "data": null
}
```

**HTTP 422** — Team trùng:

```json
{
    "status": false,
    "message": "Team ID 1593 (Đội số 2) được gán vào nhiều hơn 1 slot trong vòng 2. Mỗi đội chỉ có thể xuất hiện 1 lần. Hãy kiểm tra lại ghép cặp.",
    "data": null
}
```

**HTTP 400** — Vòng bảng chưa hoàn thành hoặc đã có locked matches:

```json
{
    "status": false,
    "message": "Vòng bảng chưa hoàn thành. Không thể rebuild pairing.",
    "data": null
}
```

```json
{
    "status": false,
    "message": "Đã có trận knockout hoàn thành và có kết quả được xác nhận. Không thể rebuild.",
    "data": null
}
```

**HTTP 500** — Lỗi hệ thống:

```json
{
    "status": false,
    "message": "Có lỗi xảy ra khi rebuild pairing: <error message>",
    "data": null
}
```
