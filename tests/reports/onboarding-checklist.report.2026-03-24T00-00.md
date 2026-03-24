# Test Report: Onboarding Checklist Widget

**Executed**: 2026-03-24T00:00:00Z
**Plan file**: `development/features/09-onboarding/onboarding-checklist.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 13    |
| Passed             | 13    |
| Failed             | 0     |
| Skipped            | 0     |
| Edge Cases Total   | 5     |
| Edge Cases Passed  | 3     |
| Edge Cases Skipped | 2     |
| Coverage Gaps      | 2     |

**vs. previous run (2026-03-23)**: Previous run had 1 failed scenario (Sc10 — no-shop empty state) and 1 failed edge case (EC3 — dismiss aria-label). Both are now fixed and passing. Scenario 13 (Vietnamese locale) previously skipped due to 500 on `/vi/dashboard`; this run passed it via client-side locale switch, with the 500 bug noted as a coverage gap.

---

## Test Accounts Used

| Account | Email | Password | State |
|---------|-------|----------|-------|
| A | test-account-a@test.com | Password1! | Shop (Test Shop A) + 1 service added during Sc11, no clients |
| B | test-account-b@test.com | Password1! | Shop (Test Shop B) + 1 service (Haircut) + 1 client (Test Client) |
| C | test-account-c@test.com | Password1! | No shop |

---

## Scenario Results

### Scenario 1: Checklist widget visible for user with shop and incomplete steps
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear `localStorage.removeItem('onboarding_dismissed')` | ✅ PASS | Key absent on fresh login |
| 2 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 3 | Wait for dashboard to fully load | ✅ PASS | Stats cards and shop data visible |
| 4 | Assert onboarding checklist widget is visible | ✅ PASS | Widget rendered |
| 5 | Assert heading "Getting Started" is visible | ✅ PASS | `<h2>Getting Started</h2>` present |
| 6 | Assert subtitle "Complete these steps to start accepting appointments" | ✅ PASS | Present inside widget |
| 7 | Assert exactly 4 step rows | ✅ PASS | 4 `<li>` elements confirmed |

---

### Scenario 2: Step 1 (shop) is always shown as completed when widget is visible
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Create your shop" step row | ✅ PASS | — |
| 4 | Assert filled checkmark icon | ✅ PASS | SVG `fill="currentColor"` checkmark path, class `text-green-500` |
| 5 | Assert `aria-label` contains "completed" | ✅ PASS | `"Create your shop — completed"` |

---

### Scenario 3: Incomplete steps show circle outline icon (not X-in-circle)
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Add your first service" step row | ✅ PASS | — |
| 4 | Assert icon is circle outline SVG | ✅ PASS | `<circle cx="10" cy="10" r="8.25">` with `fill="none" stroke="currentColor"`, class `text-gray-300` |
| 5 | Locate "Add your first client" step row | ✅ PASS | — |
| 6 | Assert icon is circle outline SVG | ✅ PASS | Same circle SVG |
| 7 | Assert `aria-label` contains "not yet completed" on each | ✅ PASS | `"… — not yet completed"` on both |

---

### Scenario 4: Step 3 (schedule) is auto-completed after shop creation
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate "Review your working hours" step row | ✅ PASS | — |
| 4 | Assert filled checkmark icon | ✅ PASS | `text-green-500` filled SVG |
| 5 | Assert `aria-label` contains "completed" | ✅ PASS | `"Review your working hours — completed"` |

---

### Scenario 5: Clicking an incomplete step navigates to its target route
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Click "Add your first service" | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard/shop/services` | ✅ PASS | — |
| 5 | Navigate back to `/en/dashboard` | ✅ PASS | — |
| 6 | Click "Add your first client" | ✅ PASS | — |
| 7 | Assert URL is `/en/dashboard/clients/create` | ✅ PASS | — |

---

### Scenario 6: Clicking a completed step also navigates to its target route
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Click "Create your shop" (completed) | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard/shop/create` | ✅ PASS | — |
| 5 | Navigate back to `/en/dashboard` | ✅ PASS | — |
| 6 | Click "Review your working hours" (completed) | ✅ PASS | — |
| 7 | Assert URL is `/en/dashboard/shop/schedule` | ✅ PASS | — |

---

### Scenario 7: Dismiss button hides the widget and persists in localStorage
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | `removeItem('onboarding_dismissed')` |
| 2 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 3 | Wait for checklist widget | ✅ PASS | — |
| 4 | Click "Dismiss" button | ✅ PASS | — |
| 5 | Assert checklist widget is no longer visible | ✅ PASS | Widget absent from snapshot |
| 6 | Assert `localStorage.getItem('onboarding_dismissed')` === `'1'` | ✅ PASS | Returned `"1"` |

---

### Scenario 8: Widget stays hidden after page reload when previously dismissed
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure dismiss key is `'1'` (from Sc7) | ✅ PASS | — |
| 2 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 3 | Wait for dashboard to fully load | ✅ PASS | Stats visible |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | No "Getting Started" in snapshot |

---

### Scenario 9: Widget auto-hides when all steps are complete
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | — |
| 2 | Log in as Account B (all steps complete) | ✅ PASS | — |
| 3 | Wait for dashboard to fully load | ✅ PASS | — |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | No widget; dashboard shows stats only |

---

### Scenario 10: Widget not shown when user has no shop
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear localStorage dismiss key | ✅ PASS | — |
| 2 | Log in as Account C (no shop) | ✅ PASS | — |
| 3 | Wait for dashboard to load | ✅ PASS | — |
| 4 | Assert checklist widget is NOT visible | ✅ PASS | No widget in snapshot |
| 5 | Assert empty-state "create shop" prompt IS visible | ✅ PASS | `"No shop yet"` heading + `"Create Your Shop"` button visible |

**Note**: This was a FAIL in the previous run (2026-03-23). Now passing — the no-shop empty state renders correctly on login.

---

### Scenario 11: Checklist updates after completing a step and returning to dashboard
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Confirm "Add your first service" is incomplete | ✅ PASS | `"not yet completed"` in aria-label |
| 2 | Click step → navigate to `/en/dashboard/shop/services` | ✅ PASS | — |
| 3 | Create new service ("Classic Cut", 100,000 VND) | ✅ PASS | Toast "Service added" shown |
| 4 | Navigate back to `/en/dashboard` | ✅ PASS | — |
| 5 | Wait for dashboard to fully reload | ✅ PASS | — |
| 6 | Locate "Add your first service" step row | ✅ PASS | — |
| 7 | Assert filled checkmark icon | ✅ PASS | `text-green-500` SVG |
| 8 | Assert `aria-label` contains "completed" | ✅ PASS | `"Add your first service — completed"` |

---

### Scenario 12: Progress text / subtitle is shown in checklist
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Assert subtitle text present | ✅ PASS | "Complete these steps to start accepting appointments" |
| 4 | Assert no "X of 4 steps completed" pattern | ✅ PASS | Old progress key absent |

---

### Scenario 13: Vietnamese locale renders correct i18n labels
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Switch to VI locale via locale switcher on `/en/dashboard` | ✅ PASS | Direct `/vi/dashboard` navigation causes 500 — see Coverage Gaps |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Assert heading "Bắt đầu" | ✅ PASS | — |
| 4 | Assert subtitle "Hoàn thành các bước sau để bắt đầu nhận lịch hẹn" | ✅ PASS | — |
| 5 | Assert 4 step labels in Vietnamese | ✅ PASS | "Tạo cửa hàng của bạn", "Thêm dịch vụ đầu tiên", "Xem lại giờ làm việc", "Thêm khách hàng đầu tiên" |
| 6 | Assert dismiss button text "Ẩn" | ✅ PASS | Button shows "Ẩn" |

---

## Edge Case Results

### Edge Case 1: Services API fails — service step shown as incomplete
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Intercept services endpoint to return 500 | ⏭ SKIP | Playwright MCP has no `page.route()` equivalent |

---

### Edge Case 2: Clients API fails — client step shown as incomplete
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Intercept clients endpoint to return 500 | ⏭ SKIP | Same reason as EC1 |

---

### Edge Case 3: Dismiss button has correct aria-label
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate dismiss button | ✅ PASS | — |
| 4 | Assert `aria-label` is "Dismiss onboarding checklist" | ✅ PASS | Accessibility snapshot: `button "Dismiss onboarding checklist"` |

**Note**: This was a FAIL in the previous run (aria-label was just "Ẩn"). Now fixed.

---

### Edge Case 4: Checklist list has role="list"
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` as Account A | ✅ PASS | — |
| 2 | Wait for checklist widget | ✅ PASS | — |
| 3 | Locate `<ul>` wrapping step rows | ✅ PASS | — |
| 4 | Assert `role="list"` | ✅ PASS | `querySelector('[role="list"]')` → `{ tag: "UL", role: "list" }` |

---

### Edge Case 5: No redirect loop if user dismissed and returns
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Dismiss checklist as Account A | ✅ PASS | localStorage = `'1'` |
| 2 | Log out | ✅ PASS | — |
| 3 | Log back in as Account A | ✅ PASS | — |
| 4 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 5 | Assert checklist NOT visible | ✅ PASS | localStorage persists across session |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Network interception for API failure (EC1, EC2) | Playwright MCP does not support `page.route()` interception | Cover with Vitest unit tests mocking composable API calls |
| 2 | Direct navigation to `/vi/dashboard` throws 500: `obj.hasOwnProperty is not a function` | Bug in SSR locale handling when `vi` prefix is used directly | **Bug**: Investigate Nuxt i18n plugin initialization on server-side for `/vi/` prefix routes; client-side locale switching works correctly |

---

## Recommendations

- **Bug open**: Direct `/vi/dashboard` navigation crashes with 500. Client-side locale switching works fine. Investigate i18n SSR initialization — likely a plugin or middleware ordering issue with `obj.hasOwnProperty`.
- **EC1 & EC2**: Cover API error fallback at the unit test level (`useOnboarding` composable).
- All previously failing items (Sc10 empty state, EC3 dismiss aria-label) are now resolved.
