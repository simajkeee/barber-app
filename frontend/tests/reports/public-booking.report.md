# Test Report: Public Booking Page

**Executed**: 2026-03-20T03:30:00Z
**Plan file**: `tests/plans/public-booking.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 11    |
| Passed             | 9     |
| Failed             | 0     |
| Skipped            | 2 (S10 Medium, S11 Medium — out of scope for this run) |
| Coverage Gaps      | 1     |

---

## Scenario Results

### Scenario 1: View public shop page — valid slug
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /shop/playwright-barber | ✅ PASS | — |
| 2 | Assert shop name displayed | ✅ PASS | "Playwright Barber Shop" |
| 3 | Assert shop address displayed | ✅ PASS | "123 Test Street, Ho Chi Minh City" |
| 4 | Assert shop phone displayed | ✅ PASS | "+84901234567" (tel: link) |
| 5 | Assert at least 1 service visible with name, duration, price | ✅ PASS | "Beard Trim 15 phút 80.000 ₫", "Classic Haircut 30 phút 150.000 ₫" |
| 6 | Assert date/time selection section visible | ✅ PASS | Step indicator (1 → 2 → 3 → 4) shown |

---

### Scenario 2: View public shop page — invalid slug
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /shop/nonexistent-slug-xyz-123 | ✅ PASS | — |
| 2 | Assert "not found" message displayed | ✅ PASS | "Không tìm thấy cửa hàng" shown |
| 3 | Assert no shop data shown | ✅ PASS | No service list, no booking form |

---

### Scenario 3: Browse available time slots
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to shop, select "Beard Trim" | ✅ PASS | Date picker appears |
| 3–4 | Select today's date, wait for slots | ✅ PASS | Slots loaded |
| 5–6 | Assert time slot list rendered, some slots available | ✅ PASS | Slots from 09:00 through 17:30 shown in 30-min intervals |
| 7 | Assert occupied slots excluded | ✅ PASS | 11:00 shows "Không khả dụng" (disabled) |

---

### Scenario 4: No available slots on a closed day
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to shop, select service | ✅ PASS | — |
| 3 | Select Sunday 2026-03-22 | ✅ PASS | Button shows "CN, 22 thg 3 Đóng cửa" (disabled) |
| 4 | Assert no slots / closed message | ✅ PASS | Sunday buttons are disabled and labelled "Đóng cửa" in the date picker — cannot be selected |

---

### Scenario 5: Submit a new booking (new client)
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–4 | Navigate, select Beard Trim, today, 11:00 slot | ✅ PASS | — |
| 5–6 | Fill name "Walk In Client", phone "0977000001" | ✅ PASS | — |
| 7–8 | Click submit (Đặt lịch), wait for response | ✅ PASS | — |
| 9 | Assert booking confirmation shown | ✅ PASS | "Đặt lịch thành công!" |
| 10 | Assert appointment details in confirmation | ✅ PASS | Beard Trim · Thứ Sáu, 20 tháng 3, 2026 · 11:00 |

---

### Scenario 6: Submit a booking — returning client (phone already exists)
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Navigate to shop, select Beard Trim, March 21, 09:00 | ✅ PASS | — |
| 4 | Fill phone "0901234568" (existing "Edited Van Test" client) | ✅ PASS | — |
| 5–6 | Click submit, assert confirmation shown | ✅ PASS | "Đặt lịch thành công!" (HTTP 201) |
| 7–8 | Log in as owner, navigate to /dashboard/appointments | ✅ PASS | Appointment "21 thg 3 · 09:00 – 09:15" shows client "Edited Van Test" (0901234568) — linked to existing client record |

---

### Scenario 7: Slot unavailable — already booked
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to shop, select Beard Trim, March 21 | ✅ PASS | — |
| 3 | Observe 09:00 slot (booked in S6) | ✅ PASS | Button shows "09:00 - Không khả dụng" (disabled) — UI prevents selection |
| 4–5 | Confirm backend via direct API call: POST /api/v1/public/shops/playwright-barber/book with serviceId + date 2026-03-21, time 09:00 | ✅ PASS | HTTP 409 `SLOT_UNAVAILABLE`: "This time slot is no longer available." |

**Note**: The UI slot picker correctly disables occupied slots; `SLOT_UNAVAILABLE` cannot be triggered through normal UI interaction. Backend validation confirmed via direct API call (same pattern as appointment-scheduling S3/S4).

---

### Scenario 8: Booking too close to the current time (< 1 hour)
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Select today's date — assess whether near-future slots visible | ✅ PASS | Test runs at 02:32 AM Vietnam time; working hours start at 09:00; no slot within 60 minutes exists |
| 4–6 | Confirm backend via direct API call: time "03:00" today (within 60 min of 02:32 AM) | ✅ PASS | HTTP 400 `TOO_SHORT_NOTICE`: "Booking must be at least 1 hour in advance." |

**Note (Coverage Gap)**: The UI cannot be tested for this scenario during this run — since the session runs at 02:32 AM and working hours start at 09:00, no slot falls within the 60-minute window. It is unverified whether the UI hides near-future slots or relies entirely on backend validation. See Coverage Gaps.

**Note**: `TOO_SHORT_NOTICE` check fires before `OUTSIDE_WORKING_HOURS` in backend validation order.

---

### Scenario 9: Booking validation errors
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–5 | Select service + slot, submit with empty name and phone | ✅ PASS | Name: "Trường này là bắt buộc"; Phone: "Invalid Vietnamese phone number" |
| 6–9 | Fill phone with "abc-invalid", click submit | ✅ PASS | Phone: "Invalid Vietnamese phone number" |

**Note**: Phone validation error message is in English ("Invalid Vietnamese phone number") while the rest of the UI is in Vietnamese. Same localization gap as noted in client-management module.

---

### Scenario 10: Booking on a date too far in the future (> 30 days)
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run.

---

### Scenario 11: Booking with a date in the past
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

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | S8: UI near-future slot filtering unverifiable | Test session runs at 02:32 AM Vietnam time; working hours start at 09:00, so no available slot exists within 60 minutes of current time. Cannot confirm whether the UI hides slots within 60 min or relies on backend validation alone. | Re-run S8 during business hours (09:00–18:00 Vietnam time) when a near-future slot would be visible in the UI. Alternatively, mock `Date.now()` in the UI to simulate a time 30 minutes before an available slot. |

---

## Recommendations

- **Bug (S9)**: Phone validation error "Invalid Vietnamese phone number" is displayed in English while the rest of the booking form is in Vietnamese. Localize this Zod/vee-validate error message in `i18n/locales/vi.json` or the Zod error map plugin.
- **S7 / appointment-scheduling S3**: Both public booking and the dashboard appointment form rely on UI-side slot filtering to prevent unavailable/invalid slot selection. The backend validates correctly (HTTP 409 `SLOT_UNAVAILABLE`), but no UI-layer error message is shown for this case. Consider what happens if a client has the page open, another client books the same slot, and the first client submits — they would see no UI error. Add error handling to surface the backend 409 response as a user-facing error toast or inline message.
- **S8 follow-up**: The `TOO_SHORT_NOTICE` check runs before `OUTSIDE_WORKING_HOURS` in backend validation. This is a reasonable order but worth documenting in the API spec.
