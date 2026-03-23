# Test Report: UX Batch 1 — Quick Wins

**Executed**: 2026-03-22T15:00:00Z
**Plan file**: `tests/plans/ux-batch1-quick-wins.plan.md`
**Result**: FAILED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 8     |
| Passed             | 7     |
| Failed             | 2     |
| Skipped            | 1     |
| Coverage Gaps      | 2     |

---

## Scenario Results

### Scenario 1: Logo navigates to dashboard
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 2 | Assert BarberPro logo link is visible | ✅ PASS | Logo link present in sidebar |
| 3 | Click the logo | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard` | ✅ PASS | — |

---

### Scenario 2: Toast shown when redirected from edit page
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to appointment create page | ✅ PASS | — |
| 2 | Verify new appointment form loads | ✅ PASS | — |
| 3 | Fill required fields and save | ✅ PASS | — |
| 4 | Assert success toast visible | ✅ PASS | Toast confirmed |

---

### Scenario 3: Cancel button on shop create page
**Priority**: Medium
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard/shop/create` | ✅ PASS | — |
| 2 | Wait for form to load | ✅ PASS | — |
| 3 | Assert "Cancel" button is visible | ❌ FAIL | Only "Create Your Shop" button exists; no Cancel button |

**Failure Detail**:
- **Failed step**: Step 3 — Assert "Cancel" button is visible
- **Expected**: A "Cancel" or "Back" button present on the shop create form
- **Actual**: Only "Create Your Shop" submit button rendered; no cancel/back action
- **Screenshot**: N/A (screenshot saving unavailable in this environment)

---

### Scenario 4: Appointment row in list is clickable
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/appointments` | ✅ PASS | — |
| 2 | Click "All Appointments" tab | ✅ PASS | — |
| 3 | Assert appointment row links to detail page | ✅ PASS | Row is an `<a>` link |
| 4 | Click first appointment row | ✅ PASS | — |
| 5 | Assert URL is `/en/dashboard/appointments/{id}` | ✅ PASS | — |

---

### Scenario 5: Heading hierarchy on dashboard page
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 2 | Assert h1 exists | ✅ PASS | "Dashboard" h1 present |
| 3 | Assert no skipped heading levels | ✅ PASS | h2 elements exist in stat cards |

---

### Scenario 6: Language switcher is visible
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 2 | Assert language switcher element is visible | ✅ PASS | Switcher in header banner with globe icon |

---

### Scenario 7: Language switcher changes locale
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard` | ✅ PASS | — |
| 2 | Click "Tiếng Việt" language link | ✅ PASS | — |
| 3 | Assert URL prefix changes to `/vi/` | ✅ PASS | — |
| 4 | Assert UI text changes to Vietnamese | ✅ PASS | — |

---

### Scenario 8: Language switcher present on all pages
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Check switcher on appointments page | ✅ PASS | — |
| 2 | Check switcher on clients page | ✅ PASS | — |
| 3 | Check switcher on shop page | ✅ PASS | — |

---

## Edge Case Results

### Edge Case 1: Logo on public booking page navigates correctly
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to public shop page | ✅ PASS | — |
| 2 | Assert logo links to home | ✅ PASS | — |

---

### Edge Case 2: Language switcher accessibility
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Check ARIA labels on language switcher | ⏭ SKIP | No Playwright MCP equivalent for screen reader testing |

---

### Edge Case 3: Daily schedule cards are clickable
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/appointments` | ✅ PASS | — |
| 2 | Assert appointment card body is clickable | ❌ FAIL | Cards are `generic` (div) elements, not NuxtLinks; only "View" button navigates |

**Failure Detail**:
- **Failed step**: Step 2 — Assert appointment card body is a link
- **Expected**: Clicking the card body navigates to appointment detail
- **Actual**: Card body is a non-interactive `generic` element; only the "View" button within it navigates
- **Note**: Behavior may be intentional given multiple action buttons per card (View, Mark Complete, No Show, Cancel)

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Cancel button missing on shop create page | Feature not implemented — `pages/dashboard/shop/create.vue` has no Cancel button | Add a Back/Cancel button to the shop create form |
| 2 | Daily schedule card clickability | Cards use `div` wrappers with multiple action buttons inside, making the whole row non-navigable | Decide whether row-level click should navigate; if yes, implement NuxtLink wrapper |

---

## Recommendations

- Add a Cancel/Back button to the shop create page for navigation consistency with other create forms.
- Clarify the intended interaction for daily schedule appointment cards — if row-click navigation is desired, implement it as a NuxtLink with action buttons stopping propagation.
- Language switcher is in the header (banner role), not the footer — this is correct UX placement.
