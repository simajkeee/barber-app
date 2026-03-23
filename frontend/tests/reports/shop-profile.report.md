# Test Report: Shop Profile Management

**Executed**: 2026-03-20T00:30:00Z
**Plan file**: `tests/plans/shop-profile.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 12    |
| Passed             | 10    |
| Failed             | 0     |
| Skipped            | 2 (S6 Medium priority; S12 Medium priority — out of scope for this run) |
| Coverage Gaps      | 0     |

---

## Scenario Results

### Scenario 1: Create a new shop
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register new user (shoptest@barberpro.com) with no shop | ✅ PASS | Dashboard shows "Chưa có cửa hàng" empty state |
| 2 | Navigate to /dashboard/shop/create | ✅ PASS | — |
| 3 | Assert create-shop form visible | ✅ PASS | — |
| 4–6 | Fill name, address, phone | ✅ PASS | "Test Barber Shop", "123 Test Street", "0901234567" |
| 7 | Click submit | ✅ PASS | — |
| 8–9 | Assert redirect to /dashboard/shop | ✅ PASS | — |
| 10 | Assert shop name visible | ✅ PASS | "Test Barber Shop" displayed, auto-slug `test-barber-shop-28bf` generated, default 7-day schedule (09:00–19:00, Sunday closed) created |

---

### Scenario 2: Attempt to create a second shop
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard/shop/create with existing shop | ✅ PASS | Form renders (no redirect) |
| 2–3 | Fill and submit second shop form | ✅ PASS | — |
| 4 | Assert error shown | ✅ PASS | "Bạn đã có cửa hàng" alert (HTTP 409) |

---

### Scenario 3: View shop profile
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard/shop | ✅ PASS | — |
| 2–4 | Assert name, address, phone visible | ✅ PASS | All fields rendered correctly |
| 5 | Assert working schedule section present | ✅ PASS | 7-day schedule displayed |

---

### Scenario 4: Edit shop basic details
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Click "Chỉnh sửa", assert form pre-filled | ✅ PASS | — |
| 3–4 | Update name to "Updated Shop Name", address to "456 New Address" | ✅ PASS | — |
| 5 | Click save | ✅ PASS | — |
| 6 | Assert success toast | ✅ PASS | "Cập nhật cửa hàng thành công" |
| 7 | Assert updated name visible | ✅ PASS | — |

---

### Scenario 5: Edit shop slug — valid slug
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Open edit form, update slug | ✅ PASS | Changed to `my-test-shop-20260320` |
| 4 | Click save | ✅ PASS | — |
| 5 | Assert no error, new slug displayed | ✅ PASS | "Cập nhật cửa hàng thành công" |

---

### Scenario 6: Edit shop slug — duplicate slug
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run. Requires a second shop with a known slug seeded separately.

---

### Scenario 7: Update working schedule
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to /dashboard/shop/schedule | ✅ PASS | 7-day schedule grid visible |
| 3–4 | Set Monday open to 08:00, close to 21:00 | ✅ PASS | — |
| 5 | Sunday already closed — no toggle needed | ✅ PASS | — |
| 6 | Click save | ✅ PASS | — |
| 7 | Assert success toast | ✅ PASS | "Cập nhật lịch thành công" |
| 8–10 | Reload /dashboard/shop — Monday shows 08:00–21:00, Sunday closed | ✅ PASS | Persisted correctly |

---

### Scenario 8: Working schedule — close time before open time
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Set Monday open to 18:00, close to 09:00 | ✅ PASS | — |
| 4 | Click save | ✅ PASS | — |
| 5 | Assert validation error | ✅ PASS | "Giờ mở cửa phải trước giờ đóng cửa" shown inline for Monday |

---

### Scenario 9: Create a new service
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard/shop/services | ✅ PASS | Empty state shown |
| 2 | Click "Thêm dịch vụ" | ✅ PASS | Dialog opens |
| 3–5 | Fill name "Haircut", duration 30, price 100000 | ✅ PASS | — |
| 6 | Click save | ✅ PASS | — |
| 7 | Assert "Haircut" in services list | ✅ PASS | "30 phút · 100.000 ₫" shown, "Đã thêm dịch vụ" toast |

---

### Scenario 10: Edit an existing service
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Click "Chỉnh sửa" on Haircut | ✅ PASS | Edit dialog opens pre-filled |
| 3 | Update price to 150000 | ✅ PASS | — |
| 4 | Click save | ✅ PASS | — |
| 5 | Assert updated price visible | ✅ PASS | "150.000 ₫", "Đã cập nhật dịch vụ" toast |

---

### Scenario 11: Deactivate (soft delete) a service
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Click "Ẩn" on Haircut service | ✅ PASS | Confirmation dialog shown |
| 3 | Confirm deactivation | ✅ PASS | — |
| 4 | Assert service no longer in active list | ✅ PASS | Empty state shown |
| 5 | Toggle "Hiện dịch vụ ẩn" | ✅ PASS | Haircut shows as "Đã ẩn" with "Kích hoạt" button |

---

### Scenario 12: Create service with invalid data
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run.

---

## Edge Case Results

### Edge Cases 1–3
**Result**: ⏭ SKIPPED
**Reason**: Edge cases excluded from Critical + High priority run.

---

## Coverage Gaps Encountered

No coverage gaps encountered during this run.

---

## Recommendations

- **Note (S2)**: The `/dashboard/shop/create` page still renders the form even when the user already has a shop. The error only surfaces after submission. A better UX would redirect to `/dashboard/shop` immediately. The backend correctly prevents creation (HTTP 409), but the frontend could be more proactive.
- **S6 prerequisite**: Duplicate slug test requires a second shop seeded via API. Consider adding this to the test data setup script.
