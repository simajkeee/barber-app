# Test Report: Appointment Scheduling

**Executed**: 2026-03-20T02:00:00Z
**Plan file**: `tests/plans/appointment-scheduling.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 14    |
| Passed             | 11    |
| Failed             | 0     |
| Skipped            | 2 (S11 Medium, S14 Medium — out of scope for this run) |
| Coverage Gaps      | 2     |

---

## Scenario Results

### Scenario 1: View daily schedule (empty day)
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard/appointments | ✅ PASS | — |
| 2–3 | Select date with no appointments, assert daily schedule visible | ✅ PASS | March 19 selected, "Hôm nay không có lịch hẹn" shown |
| 4 | Assert working hours displayed | ✅ PASS | "Giờ làm việc: 09:00 – 18:00" |
| 5 | Assert empty state shown | ✅ PASS | "Tận hưởng ngày tự do!" |

---

### Scenario 2: Create a new appointment
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to /dashboard/appointments/create, assert form visible | ✅ PASS | — |
| 3 | Select client "Edited Van Test" | ✅ PASS | Autocomplete search used |
| 4 | Select service "Beard Trim (15min)" | ✅ PASS | — |
| 5–6 | Select date 2026-03-21, select slot 11:00 | ✅ PASS | Slots loaded after date selection |
| 7–8 | Click Lưu, wait for navigation | ✅ PASS | — |
| 9 | Assert success feedback | ✅ PASS | Toast "Đã đặt lịch" |
| 10 | Assert appointment on daily schedule | ✅ PASS | "21 thg 3 · 11:00 – 11:15" shown on schedule |

**Note**: Before S2 could be executed, the playwright user had no subscription record (`SUBSCRIPTION_NOT_FOUND`) despite hitting `APPOINTMENT_LIMIT_REACHED`. Backend `canCreateAppointment()` returns `false` when no subscription record exists (subscription required for any appointment creation). A FREE subscription was seeded directly in the DB to unblock the test.

---

### Scenario 3: Create appointment with time conflict
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Note existing appointment at 10:00 on 2026-03-21 | ✅ PASS | Seeded via direct API call |
| 2–5 | Attempt to create appointment at same time | ✅ PASS | UI removes occupied slots from slot picker |
| 6–7 | Submit conflicting time via direct API call | ✅ PASS | HTTP 409 `APPOINTMENT_OVERLAP` returned |

**Note**: The UI prevents selection of occupied slots, so APPOINTMENT_OVERLAP cannot be triggered via normal UI interaction. Backend validation confirmed via direct API call. No error toast display is possible through the UI for this error code.

---

### Scenario 4: Create appointment outside working hours
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–5 | Attempt to book 07:00 on an open day | ✅ PASS | UI only shows slots within working hours (09:00–18:00) |
| 6–7 | Submit 07:00 via direct API call | ✅ PASS | HTTP 422 `OUTSIDE_WORKING_HOURS` returned |

**Note**: Same as S3 — UI filters out-of-hours slots; validation confirmed at API level.

---

### Scenario 5: Create appointment on a closed day
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–4 | Select Sunday 2026-03-22 in date picker | ✅ PASS | — |
| 5 | Assert no available slots | ✅ PASS | "Không có khung giờ trống trong ngày này" shown |
| 6 | Confirm API: HTTP 422 `SHOP_CLOSED` | ✅ PASS | Direct API call confirmed |

**Note (UX)**: Selecting a closed day shows both "Không có khung giờ trống trong ngày này" (correct) AND an error alert "Tải khung giờ trống thất bại" (confusing — the available-slots endpoint returns an error instead of an empty list for closed days). Consider returning HTTP 200 with empty slots array on closed days.

---

### Scenario 6: View appointment detail
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to appointments, click "Xem" on an existing appointment | ✅ PASS | — |
| 3 | Assert URL changes to /dashboard/appointments/{id} | ✅ PASS | `019d0787-b154-7655-93bc-dc12f252069c` |
| 4 | Assert client name visible | ✅ PASS | "Edited Van Test" |
| 5 | Assert service name and duration | ✅ PASS | "Beard Trim", "15 phút · 80.000 ₫" |
| 6 | Assert start/end times | ✅ PASS | "21 thg 3 · 10:00 – 10:15" |
| 7 | Assert status | ✅ PASS | "Đã đặt" (Scheduled) |

---

### Scenario 7: Edit an appointment — reschedule
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to edit page, assert fields pre-filled | ✅ PASS | Client, service, date pre-populated |
| 3 | Change date to 2026-03-24, select 14:00 | ✅ PASS | Slots loaded for new date |
| 4–6 | Click Lưu, assert success | ✅ PASS | Toast "Đã cập nhật lịch hẹn" |
| 7–8 | Assert appointment shows new time | ✅ PASS | "24 thg 3 · 14:00 – 14:15" on detail page |

**Note (UX)**: When editing on the same day, the available-slots API does not exclude the appointment being edited (`excludeId` not passed), so the current slot appears occupied and no slots are shown. Changing to a different date works correctly. Consider passing `excludeAppointmentId` to the available-slots call in the edit form.

---

### Scenario 8: Attempt to edit a completed appointment
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to /dashboard/appointments/{id}/edit for COMPLETED appointment | ✅ PASS | URL redirected to detail page |
| — | Assert edit blocked | ✅ PASS | Toast "Lịch hẹn này không thể chỉnh sửa"; only "Quay lại" button shown |

---

### Scenario 9: Mark appointment as completed
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to SCHEDULED appointment detail, click "Hoàn thành" | ✅ PASS | — |
| 3–4 | Confirmation dialog appears, click "Xác nhận" | ✅ PASS | "Điều này sẽ cập nhật số lượt ghé của khách hàng." |
| 5–6 | Assert status shows "Hoàn thành" | ✅ PASS | Toast "Đã đánh dấu hoàn thành" |
| 7–8 | Navigate to client detail, assert visit count incremented | ✅ PASS | "Tổng lượt ghé: 1", "Lần ghé cuối: 21 thg 3, 2026" |

---

### Scenario 10: Cancel an appointment
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Click "Hủy lịch" on SCHEDULED appointment | ✅ PASS | Confirmation dialog shown |
| 3–4 | Confirm, wait for response | ✅ PASS | — |
| 5 | Assert status shows "Đã hủy" | ✅ PASS | Toast "Đã hủy lịch hẹn" |
| 6–7 | Navigate to create, select same date — assert slot free | ✅ PASS | 14:00 appears in slots for 2026-03-24 |

---

### Scenario 11: Mark appointment as no-show
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run.

---

### Scenario 12: Invalid status transition (COMPLETED → CANCELLED)
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to COMPLETED appointment detail | ✅ PASS | — |
| — | Assert no status-change controls available | ✅ PASS | Only "Quay lại" button shown; "Hoàn thành", "Không đến", "Hủy lịch" absent |

---

### Scenario 13: View available slots
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Navigate to create, select service and open date | ✅ PASS | — |
| 4–5 | Assert slot list rendered; occupied slots absent | ✅ PASS | 10:00 and 11:00 absent when both booked on 2026-03-21 |
| 6 | Assert past times excluded (not applicable — future date used) | ✅ PASS | All shown slots are future |

---

### Scenario 14: Revenue summary
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run.

---

## Edge Case Results

### Edge Cases 1–4
**Result**: ⏭ SKIPPED
**Reason**: Edge cases excluded from Critical + High priority run.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Playwright user had no subscription record | Backend `canCreateAppointment()` returns `false` when `subscriptions` table has no row for the shop — no subscription = no appointments, regardless of plan. Test setup seeded a FREE subscription via SQL to unblock tests. | Add subscription seeding to the playwright test shop setup script: `INSERT INTO subscriptions ... plan='free', status='active'`. |
| 2 | S3/S4 error display in UI not verifiable | UI slot picker prevents selecting occupied/out-of-hours slots, so the APPOINTMENT_OVERLAP and OUTSIDE_WORKING_HOURS error toasts cannot be triggered through normal UI interaction. Backend validation confirmed via direct API call. | Consider adding a test that manipulates the form submission directly (e.g., via Playwright `page.route()` intercept or custom API test). |

---

## Recommendations

- **Bug (S5 UX)**: Selecting a closed day in the date picker shows an error alert "Tải khung giờ trống thất bại" alongside the correct "no slots" message. The available-slots endpoint should return HTTP 200 with an empty array and a `closed: true` flag for closed days rather than an error, allowing the UI to show only the friendly closed-day message.
- **UX (S7 edit)**: The edit form's slot picker does not pass the current appointment ID to the available-slots API to exclude it from conflict detection. When editing an appointment and keeping the same day, no slots appear (since the current appointment's slot looks occupied). Pass `excludeAppointmentId` to `GET /api/v1/appointments/available-slots` in edit mode.
- **Infrastructure**: The playwright test shop requires a `subscriptions` record for appointment creation to work. This should be seeded as part of initial test data setup, not discovered during test execution.
- **S14 prerequisite**: Revenue summary requires completed appointments — now satisfied (one COMPLETED appointment exists).
