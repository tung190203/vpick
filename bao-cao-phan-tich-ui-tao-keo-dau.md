# BÁO CÁO PHÂN TÍCH UI "TẠO KÈO ĐẤU" - UPDATE V3
## Figma vs Backend | 12/03/2026

---

## 1. CẬP NHẬT THEO YÊU CẦU SẾP

### 1.1. Tách `match_type` → `play_mode` + `format`

| UI Figma | Backend Mới |
|----------|-------------|
| **CHẾ ĐỘ CHƠI** | `play_mode` |
| - Vui vẻ (giao hữu, không tính điểm) | `play_mode = 1` |
| - Thi đấu (có tính điểm) | `play_mode = 2` |
| - Luyện tập | `play_mode = 3` |
| **THỂ THỨC** | `format` |
| - Đánh đơn | `format = 1` |
| - Đánh đôi | `format = 2` |
| - Đánh đôi nam | `format = 3` |
| - Đánh đôi nữ | `format = 4` |
| - Hỗn hợp (Mixed) | `format = 5` |

---

### 1.2. Cơ chế lặp lại - LÀM GIỐNG CLUB

**Cơ chế hiện tại của Club (tham khảo):**

| Thành phần | Mô tả |
|------------|-------|
| `recurring_schedule` | JSON lưu lịch lặp |
| `recurrence_series_id` | UUID để nhóm các occurrence |
| `recurrence_series_cancelled_at` | Timestamp hủy cả series |

**Cấu trúc `recurring_schedule` (giống Club):**
```json
{
  "period": "weekly",
  "week_days": [3, 5, 7],
  "repeat_until": "2026-12-31"
}
```

---

### 1.3. Bảng lưu kèo mẫu

| Chức năng | API | Mô tả |
|------------|-----|--------|
| Save | `POST /api/mini-tournament-templates` | Lưu cài đặt thành template |
| Load | `GET /api/mini-tournament-templates` | Danh sách template |
| Delete | `DELETE /api/mini-tournament-templates/{id}` | Xóa template |
| **Edit** | ❌ Không có | Theo yêu cầu |

---

### 1.4. Phần Phí tham gia - Theo UI mới

| UI Figma | Backend Mới |
|----------|-------------|
| **Phí tham gia** (toggle) | `enable_fee_collection` (boolean) |
| **Chia tiền sân tự động** | `auto_split_court_fee` (boolean) |
| - ON: `fee_amount` = TỔNG BILL | `auto_split_court_fee = true` |
| - OFF: `fee_amount` = TIỀN/NGƯỜI | `auto_split_court_fee = false` |
| **Thu vào tài khoản QR** | `payment_account_id` (FK -> club_wallets) |
| **Thời điểm thu** | `payment_schedule` |
| - Thu trước khi đấu | `payment_schedule = 'before'` |
| - Thu trong khi đấu | `payment_schedule = 'during'` |
| - Thu sau khi đấu | `payment_schedule = 'after'` |
| **Remind** | Auto notification các công đoạn |

---

## 2. CHECK AUTO_APPROVE - TÙNG ĐÃ LÀM XONG ✅

### Logic hiện tại (đã có):

**File:** `MiniParticipantController.php` - method `join()`

```php
// Line 82: Logic auto_approve đã có
'is_confirmed' => $miniTournament->auto_approve && !$miniTournament->is_private,
```

| Điều kiện | Kết quả |
|------------|---------|
| `auto_approve = true` VÀ `is_private = false` | ✅ `is_confirmed = true` (auto duyệt) |
| `auto_approve = true` VÀ `is_private = true` | `is_confirmed = false` (chờ duyệt) |
| `auto_approve = false` | `is_confirmed = false` (chờ duyệt) |

**Status: ✅ TÙNG ĐÃ LÀM XONG**

---

## 3. LOGIC NỘP TIỀN MỚI ĐƯỢC DUYỆT - CHƯA CÓ ❌

### Yêu cầu sếp:
> "nộp tiền là được duyệt" - tức là khi user nộp tiền xong thì mới được duyệt

### Logic cần thêm:

| Bước | Mô tả |
|------|-------|
| 1 | User đăng ký → `is_confirmed = false` (chờ) |
| 2 | User nộp tiền (qua QR) → `payment_status = 'confirmed'` |
| 3 | System tự động set `is_confirmed = true` |

### Cần thêm:

| Trường | Mô tả |
|---------|-------|
| `payment_status` | pending / confirmed / refunded |
| **Logic** | Khi payment confirmed → auto update `is_confirmed = true` |

---

## 4. TỔNG HỢP CÁC TRƯỜNG CẦN THÊM

### Thêm mới vào MiniTournament:

| Trường | Loại | Mô tả |
|---------|------|-------|
| `play_mode` | Enum(1,2,3) | Vui vẻ/Thi đấu/Luyện tập |
| `format` | Enum(1,2,3,4,5) | Đơn/Đôi nam/Đôi nữ/Mixed |
| `recurring_schedule` | JSON | Lịch lặp |
| `recurrence_series_id` | UUID | Nhóm series |
| `recurrence_series_cancelled_at` | Timestamp | Hủy series |
| `auto_split_court_fee` | Boolean | Chia tiền tự động |
| `payment_account_id` | BigInt | Tài khoản QR |
| `payment_schedule` | Enum | Thời điểm thu |
| `enable_fee_collection` | Boolean | Bật/tắt thu phí |

### Thêm mới vào MiniParticipant:

| Trường | Loại | Mô tả |
|---------|------|-------|
| `payment_status` | Enum | pending/confirmed/refunded |
| `payment_confirmed_at` | Timestamp | Thời điểm xác nhận |

---

## 5. STATUS TỔNG

| STT | Hạng mục | Status |
|-----|-----------|--------|
| 1 | Tách match_type → play_mode + format | [ ] Chưa làm |
| 2 | Cơ chế lặp (giống Club) | [ ] Chưa làm |
| 3 | Bảng template | [ ] Chưa làm |
| 4 | Phí tham gia + QR payment | [ ] Chưa làm |
| 5 | **Auto_approve (đăng ký = duyệt)** | [x] ✅ **ĐÃ XONG** |
| 6 | **Nộp tiền = duyệt** | [ ] ❌ **CHƯA CÓ** |
