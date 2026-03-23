# Test Report: UX Batch 5 — Dashboard Home Stats

**Executed**: 2026-03-22T15:20:00Z
**Plan file**: `tests/plans/ux-batch5-dashboard-stats.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 12    |
| Passed             | 9     |
| Failed             | 0     |
| Skipped            | 3     |
| Coverage Gaps      | 5     |

---

## Scenario Results

### Scenario 1: Dashboard renders all four stat widgets
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for full page load | ✅ PASS | — |
| 3 | Assert today's appointment count widget | ✅ PASS | Widget visible |
| 4 | Assert next upcoming appointment widget | ✅ PASS | Widget visible |
| 5 | Assert monthly usage widget | ✅ PASS | Widget visible |
| 6 | Assert subscription plan widget | ✅ PASS | Widget visible |

---

### Scenario 2: Today's appointment count shows correct number
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for today's count widget to load | ✅ PASS | — |
| 3 | Assert numeric count shown | ✅ PASS | Count "0" shown (no appointments today — Sunday) |

---

### Scenario 3: Next appointment card shows client, service, and time
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for next appointment widget | ✅ PASS | — |
| 3 | Assert client name visible | ✅ PASS | "Second Client" shown |
| 4 | Assert service name visible | ✅ PASS | "Haircut" shown |
| 5 | Assert formatted date/time visible | ✅ PASS | "Mar 23" date shown |

---

### Scenario 4: Next appointment card navigates to detail
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for next appointment widget | ✅ PASS | — |
| 3 | Note client name in widget | ✅ PASS | "Second Client" |
| 4 | Click next appointment card | ✅ PASS | — |
| 5 | Assert URL is `/en/dashboard/appointments/{id}` | ✅ PASS | — |
| 6 | Assert same client name on detail page | ✅ PASS | — |

---

### Scenario 5: Monthly usage shows count and progress bar
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for usage widget | ✅ PASS | — |
| 3 | Assert "X / Y" usage format visible | ✅ PASS | Appointment count and limit displayed |
| 4 | Assert progress bar element visible | ✅ PASS | Progress bar rendered |

---

### Scenario 6: Unlimited plan shows count without limit
**Priority**: Medium
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in as unlimited plan user | ⏭ SKIP | No unlimited plan test account available |

**Reason**: Test account uses a limited plan. No unlimited/pro plan credentials available.

---

### Scenario 7: Subscription plan widget shows plan name and days remaining
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Wait for plan widget | ✅ PASS | — |
| 3 | Assert plan name visible | ✅ PASS | Plan name shown |
| 4 | Assert "days remaining" text visible | ✅ PASS | Days remaining shown |

---

### Scenario 8: Free plan shows upgrade link
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Assert upgrade link/button visible in plan widget | ✅ PASS | "Upgrade" or upgrade prompt visible |
| 3 | Click upgrade link | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard/subscription` | ✅ PASS | — |

---

### Scenario 9: "New Appointment" button navigates to create page
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Assert "New Appointment" button visible | ✅ PASS | Primary variant button present |
| 3 | Click "New Appointment" | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard/appointments/create` | ✅ PASS | — |

---

### Scenario 10: "New Client" button navigates to create page
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en/dashboard` | ✅ PASS | — |
| 2 | Assert "New Client" button visible | ✅ PASS | Secondary variant button present |
| 3 | Click "New Client" | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard/clients/create` | ✅ PASS | — |

---

### Scenario 11: Loading skeletons shown while stats load
**Priority**: Medium
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to dashboard | ⏭ SKIP | Data loads too fast on local dev server to observe skeleton state |

**Reason**: No network throttling available via Playwright MCP.

---

### Scenario 12: No shop configured — shows empty state
**Priority**: High
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in as user without shop | ⏭ SKIP | No such test account available |

**Reason**: All available test accounts have a shop configured.

---

## Edge Case Results

### Edge Case 1: Today has zero appointments
**Result**: ✅ PASSED (incidentally)

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to dashboard on Sunday (no appointments) | ✅ PASS | — |
| 2 | Assert today count shows "0" | ✅ PASS | Count widget shows "0" |

---

### Edge Case 2: No future appointments
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate with no future scheduled appointments | ⏭ SKIP | Test account has a scheduled appointment; would need to cancel it first |

---

### Edge Case 3: Progress bar turns amber at 80%+ usage
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in as user at 80%+ usage | ⏭ SKIP | Cannot control usage percentage in current test account |

---

### Edge Case 4: Stats load independently (partial API failure)
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Block one API call while allowing others | ⏭ SKIP | Network interception not available via Playwright MCP |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Unlimited plan display | No unlimited plan test account | Create a dedicated unlimited plan test account |
| 2 | No-shop empty state | No shopless test account | Create a dedicated shopless test account |
| 3 | Loading skeleton observation | Too fast on local server | Test with network throttling in Playwright `.spec.ts` |
| 4 | Amber progress bar at 80%+ | Cannot control usage % | Seed a test account near its appointment limit |
| 5 | Partial API failure resilience | Network interception not available | Implement with `page.route()` in `.spec.ts` |

---

## Recommendations

- All four stat widgets render correctly and navigate properly. Dashboard stats feature is working as expected.
- Edge case testing (amber threshold, skeleton, partial failure) requires full Playwright `.spec.ts` tests.
- Seed additional test accounts for unlimited plan and near-limit usage scenarios.
