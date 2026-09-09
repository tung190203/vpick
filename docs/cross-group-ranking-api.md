## API 1: Lấy Danh sách Ứng viên So sánh

### Endpoint

```
GET /api/tournament-types/{tournamentType}/cross-group-comparison
```

### Mục đích

Trả về danh sách xếp hạng tất cả đội Nhì và Ba từ mọi bảng, kèm thống kê so sánh sau khi loại các trận gặp đội cuối bảng.

### Schema Response

```json
{
    "status": true,
    "message": "string",
    "data": {
        "enabled": true,
        "applied": true,
        "comparison_rule": {
            "minimum_group_size": 4,
            "description": "string"
        },
        "qualification": {
            "number_of_groups": 6,
            "knockout_slots": 8,
            "additional_slots": 2,
            "runner_up_candidates": 6,
            "third_place_candidates": 0
        },
        "candidates": [
            {
                "rank": 1,
                "team": {
                    "id": "123",
                    "name": "Đoàn Trần - Radio"
                },
                "group": {
                    "id": "5",
                    "name": "Bảng B",
                    "team_count": 4
                },
                "group_position": 2,
                "candidate_type": "runner_up",
                "matches": {
                    "original": 3,
                    "counted": 3,
                    "excluded": 0
                },
                "statistics": {
                    "wins": 2,
                    "losses": 1,
                    "win_rate": 66.67,
                    "points_for": 35,
                    "points_against": 30,
                    "point_diff": 5,
                    "average_point_difference": 1.67
                },
                "status": "qualified",
                "pending_draw": false,
                "has_excluded_matches": false
            }
        ]
    }
}
```

### Các trường Response

| Trường | Kiểu | Mô tả |
|--------|------|--------|
| `enabled` | boolean | Tính năng có được bật trong cấu hình giải đấu |
| `applied` | boolean | Quy tắc có thực sự được áp dụng (bảng không đều + format=Mixed) |
| `comparison_rule.minimum_group_size` | integer | Số đội của bảng nhỏ nhất |
| `qualification.additional_slots` | integer | Số suất cần lấy thêm (knockout_slots - number_of_groups) |
| `candidates[].rank` | integer | Xếp hạng so sánh (1 = tốt nhất) |
| `candidates[].candidate_type` | string | `runner_up` (Nhì) hoặc `third_place` (Ba) |
| `candidates[].matches.excluded` | integer | Số trận bị loại khỏi tính toán |
| `candidates[].status` | string | `qualified` (đi tiếp), `not_qualified` (không đi tiếp), hoặc `not_applicable` |
| `candidates[].pending_draw` | boolean | True nếu bằng nhau trên mọi tiêu chí xếp hạng |
| `candidates[].has_excluded_matches` | boolean | True nếu có trận bị loại |

### Khi không áp dụng

```json
{
    "status": true,
    "message": "...",
    "data": {
        "enabled": true,
        "applied": false,
        "comparison_rule": {
            "minimum_group_size": null,
            "description": null
        },
        "qualification": {
            "number_of_groups": 0,
            "knockout_slots": 0,
            "additional_slots": 0,
            "runner_up_candidates": 0,
            "third_place_candidates": 0
        },
        "candidates": []
    }
}
```

---

## API 2: Lấy Chi tiết Trận của Đội

### Endpoint

```
GET /api/tournament-types/{tournamentType}/cross-group-comparison/{team}/matches
```

### Mục đích

Trả về danh sách đầy đủ các trận vòng bảng của một đội ứng viên cụ thể, có đánh dấu rõ trận nào được tính và trận nào bị loại.

### Schema Response

```json
{
    "status": true,
    "message": "string",
    "data": {
        "team": {
            "id": "123",
            "name": "Duy Nguyễn - Hải Nguyên"
        },
        "group": {
            "id": "3",
            "name": "Bảng A",
            "team_count": 5
        },
        "group_position": 2,
        "candidate_type": "runner_up",
        "comparison": {
            "minimum_group_size": 4,
            "original_matches": 4,
            "counted_matches": 3,
            "excluded_matches": 1,
            "wins": 2,
            "losses": 1,
            "win_rate": 66.67,
            "points_for": 34,
            "points_against": 30,
            "point_diff": 4,
            "average_point_difference": 1.33
        },
        "matches": [
            {
                "id": "456",
                "opponent": {
                    "id": "789",
                    "name": "Huy CAP"
                },
                "opponent_group_position": 4,
                "score": "9 - 11",
                "home_score": 9,
                "away_score": 11,
                "result": "loss",
                "included": true,
                "exclusion_reason": null
            },
            {
                "id": "457",
                "opponent": {
                    "id": "790",
                    "name": "Châu Bùi - datclinic"
                },
                "opponent_group_position": 5,
                "score": "11 - 2",
                "home_score": 11,
                "away_score": 2,
                "result": "win",
                "included": false,
                "exclusion_reason": "Đối thủ xếp hạng 5 trong bảng"
            }
        ]
    }
}
```

### Các trường Response

| Trường | Kiểu | Mô tả |
|--------|------|--------|
| `matches[].included` | boolean | True = được tính trong so sánh, False = bị loại |
| `matches[].exclusion_reason` | string\|null | Lý do bị loại bằng tiếng Việt (ví dụ: "Đối thủ xếp hạng 5 trong bảng") |
| `matches[].result` | string | `win` (thắng), `loss` (thua), hoặc `draw` (hòa) |
| `matches[].opponent_group_position` | integer\|null | Thứ hạng cuối cùng của đối thủ trong bảng |

---