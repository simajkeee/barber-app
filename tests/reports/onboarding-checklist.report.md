# Test Report: Onboarding Checklist Widget

**Executed**: 2026-03-23T18:44:00Z
**Plan file**: `development/features/09-onboarding/onboarding-checklist.plan.md`
**Result**: FAILED

---

## Summary

| Metric          | Value |
|-----------------|-------|
| Total Scenarios | 13    |
| Passed          | 11    |
| Failed          | 1     |
| Skipped         | 1     |
| Edge Cases      | 5     |
| EC Passed       | 3     |
| EC Failed       | 1     |
| EC Skipped      | 2     |
| Coverage Gaps   | 3     |

---

## Test Accounts Used

| Account | Email | Password | State |
|---------|-------|----------|-------|
| A | account.a.onboarding@example.com | Test1234! | Shop + 1 service (Beard Trim) added during Sc11, no clients |
| B | account.b.onboarding@example.com | Test1234! | Shop + 1 service (Haircut) + 1 client (Test Client) |
| C | account.c.onboarding@example.com | Test1234! | No shop |

---

## Scenario Results

### Scenario 1: Checklist widget visible for user with shop and incomplete steps
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage onboarding_dismissed | ✅ PASS | — |
| 2 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 3 | Wait for dashboard to load | ✅ PASS | — |
| 4 | Assert onboarding checklist widget is visible | ✅ PASS | Heading "Bắt đầu" visible |
| 5 | Assert heading "Bắt đầu" (vi) is visible | ✅ PASS | — |
| 6 | Assert subtitle visible | ✅ PASS | "Hoàn thành các bước sau để bắt đầu nhận lịch hẹn" |
| 7 | Assert exactly 4 step rows rendered | ✅ PASS | 4 listitem elements confirmed |

---

### Scenario 2: Step 1 (shop) is always shown as completed when widget is visible
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Tạo cửa hàng của bạn" step row | ✅ PASS | — |
| 4 | Assert filled checkmark icon (not circle, not X) | ✅ PASS | Green checkmark confirmed via screenshot |
| 5 | Assert aria-label contains "completed" | ✅ PASS | aria-label: "Tạo cửa hàng của bạn — đã hoàn thành" |

---

### Scenario 3: Incomplete steps show circle outline icon (not X-in-circle)
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Thêm dịch vụ đầu tiên" step row | ✅ PASS | — |
| 4 | Assert circle outline icon (not X) | ✅ PASS | Grey circle outline confirmed via screenshot |
| 5 | Locate "Thêm khách hàng đầu tiên" step row | ✅ PASS | — |
| 6 | Assert circle outline icon (not X) | ✅ PASS | Grey circle outline confirmed |
| 7 | Assert aria-label contains "not yet completed" | ✅ PASS | "chưa hoàn thành" present in both |

---

### Scenario 4: Step 3 (schedule) is auto-completed after shop creation
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A (fresh shop) | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Xem lại giờ làm việc" step row | ✅ PASS | — |
| 4 | Assert filled checkmark icon | ✅ PASS | Green checkmark confirmed |
| 5 | Assert aria-label contains "completed" | ✅ PASS | "Xem lại giờ làm việc — đã hoàn thành" |

---

### Scenario 5: Clicking an incomplete step navigates to its target route
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Click "Thêm dịch vụ đầu tiên" step row | ✅ PASS | — |
| 4 | Assert URL is /dashboard/shop/services | ✅ PASS | — |
| 5 | Navigate back to /dashboard | ✅ PASS | — |
| 6 | Click "Thêm khách hàng đầu tiên" step row | ✅ PASS | — |
| 7 | Assert URL is /dashboard/clients/create | ✅ PASS | — |

---

### Scenario 6: Clicking a completed step also navigates to its target route
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Click "Tạo cửa hàng của bạn" step row | ✅ PASS | — |
| 4 | Assert URL is /dashboard/shop/create | ✅ PASS | — |
| 5 | Navigate back to /dashboard | ✅ PASS | — |
| 6 | Click "Xem lại giờ làm việc" step row | ✅ PASS | — |
| 7 | Assert URL is /dashboard/shop/schedule | ✅ PASS | — |

---

### Scenario 7: Dismiss button hides the widget and persists in localStorage
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | Verified null |
| 2 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 3 | Wait for checklist widget | ✅ PASS | — |
| 4 | Click "Ẩn" button | ✅ PASS | — |
| 5 | Assert widget no longer visible | ✅ PASS | Removed from DOM |
| 6 | Assert localStorage.getItem('onboarding_dismissed') === '1' | ✅ PASS | Returned "1" |

---

### Scenario 8: Widget stays hidden after page reload when previously dismissed
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure localStorage dismiss key is '1' | ✅ PASS | Set from Scenario 7 |
| 2 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 3 | Wait for dashboard to load | ✅ PASS | — |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | No "Bắt đầu" heading in DOM |

---

### Scenario 9: Widget auto-hides when all steps are complete
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | — |
| 2 | Navigate to /dashboard as Account B | ✅ PASS | — |
| 3 | Wait for dashboard to fully load | ✅ PASS | — |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | No widget in DOM — all steps complete |

---

### Scenario 10: Widget not shown when user has no shop
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | — |
| 2 | Navigate to /dashboard as Account C (no shop) | ✅ PASS | — |
| 3 | Wait for dashboard to load | ✅ PASS | — |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | Correct — no widget shown |
| 5 | Assert empty-state "create shop" prompt IS visible | ❌ FAIL | Dashboard shows blank stat cards; no "Chưa có cửa hàng" empty state |

**Failure Detail**:
- **Failed step**: 5 — Assert existing empty-state component is visible
- **Expected**: "Chưa có cửa hàng" heading and "Tạo cửa hàng" button visible
- **Actual**: Regular dashboard layout with blank stat cards rendered; no-shop empty state absent on normal login (was visible immediately after registration but not on subsequent logins)
- **Screenshot**: see page-2026-03-23T18-40-54-446Z.png in Playwright MCP output

---

### Scenario 11: Checklist updates after completing a step and returning to dashboard
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Dashboard as Account A; confirm service step is incomplete | ✅ PASS | "chưa hoàn thành" |
| 2 | Click service step → navigate to /dashboard/shop/services | ✅ PASS | — |
| 3 | Create new service ("Beard Trim", 80,000 VND) | ✅ PASS | Toast "Đã thêm dịch vụ" shown |
| 4 | Navigate back to /dashboard | ✅ PASS | — |
| 5 | Wait for dashboard to reload | ✅ PASS | — |
| 6 | Locate "Thêm dịch vụ đầu tiên" step row | ✅ PASS | — |
| 7 | Assert step shows filled checkmark | ✅ PASS | Green checkmark confirmed |
| 8 | Assert aria-label contains "completed" | ✅ PASS | "Thêm dịch vụ đầu tiên — đã hoàn thành" |

---

### Scenario 12: Progress text / subtitle is shown in checklist
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Assert subtitle "Hoàn thành các bước sau..." is visible | ✅ PASS | — |
| 4 | Assert no "X of 4 steps completed" pattern | ✅ PASS | Old progress key absent |

---

### Scenario 13: Vietnamese locale renders correct i18n labels
**Priority**: Medium
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to BASE_URL/vi/dashboard | ❌ FAIL | 500 error: "Page not found: /vi/dashboard" |
| 2–6 | Verify Vietnamese strings | ⏭ SKIPPED | Redirected back to /dashboard |

**Notes**: The route `/vi/dashboard` is invalid — the app uses no-prefix strategy for the default Vietnamese locale (`/dashboard`) and prefix only for English (`/en/dashboard`). All Vietnamese strings were verified in Scenarios 1–12 on `/dashboard`: heading "Bắt đầu", subtitle, all four step labels in Vietnamese, and dismiss button "Ẩn" are all correct. Coverage gap filed for plan URL error.

---

## Edge Case Results

### Edge Case 1: Services API fails — service step shown as incomplete
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Intercept services endpoint to return 500 | ⏭ SKIPPED | No Playwright MCP network interception available |

---

### Edge Case 2: Clients API fails — client step shown as incomplete
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Intercept clients endpoint to return 500 | ⏭ SKIPPED | No Playwright MCP network interception available |

---

### Edge Case 3: Dismiss button has correct aria-label
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate dismiss button element | ✅ PASS | Found button "Ẩn" |
| 4 | Assert aria-label is "Dismiss onboarding checklist" | ❌ FAIL | aria-label is "Ẩn" (just button text) |

**Failure Detail**:
- **Failed step**: 4
- **Expected**: aria-label = "Dismiss onboarding checklist" (or Vietnamese equivalent describing the action)
- **Actual**: aria-label = "Ẩn" — screen readers only hear "Ẩn" with no context about what is being dismissed

---

### Edge Case 4: Checklist list has role="list"
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate `<ul>` wrapping step rows | ✅ PASS | — |
| 4 | Assert role="list" | ✅ PASS | Accessibility tree shows `list` role |

---

### Edge Case 5: No redirect loop if user dismissed and returns
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Dismiss checklist as Account A | ✅ PASS | Widget hidden, localStorage = '1' |
| 2 | Log out | ✅ PASS | — |
| 3 | Log back in as Account A | ✅ PASS | — |
| 4 | Navigate to /dashboard | ✅ PASS | — |
| 5 | Assert checklist widget is NOT visible | ✅ PASS | localStorage persisted across session |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Network interception for API failure scenarios (EC1, EC2) | Playwright MCP does not support `page.route()` interception | Test via unit/integration tests or use a backend test mode that can simulate 500 errors |
| 2 | `/vi/dashboard` route returns 500 error | App uses no-prefix strategy for default locale (vi); `/vi/` prefix is invalid | Update plan to use `/dashboard` for Vietnamese and `/en/dashboard` for English; also investigate/fix the 500 error on `/vi/` prefix in case users land there |
| 3 | No-shop empty state not rendered on login (Scenario 10) | "Chưa có cửa hàng" empty state renders after registration redirect but not on subsequent logins | Bug — dashboard page should show empty state for no-shop users regardless of how they arrive |

---

## Recommendations

1. **Bug — no-shop empty state missing on login**: When a user with no shop logs in and navigates to `/dashboard`, the page shows blank stat cards instead of the "Chưa có cửa hàng" empty state with the "Tạo cửa hàng" CTA. This is a regression — the state renders correctly right after registration. Investigate whether the shop store is not initialising correctly on login vs. registration flow.

2. **Bug — dismiss button aria-label not descriptive**: `aria-label="Ẩn"` gives screen reader users no context. Should be `aria-label="Ẩn danh sách kiểm tra"` (vi) or `"Dismiss onboarding checklist"` (en).

3. **Plan fix — locale URL strategy**: The plan references `/vi/dashboard` which is not a valid route. The correct approach is `/dashboard` (vi, default, no prefix) and `/en/dashboard` (en). Update the plan and re-run Scenario 13 against `/dashboard` to formally close it.

4. **API error fallback (EC1, EC2)**: These scenarios should be covered by unit tests on the `useOnboarding` composable that mock the API responses.
