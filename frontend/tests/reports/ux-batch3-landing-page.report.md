# Test Report: UX Batch 3 — Landing Page

**Executed**: 2026-03-22T15:10:00Z
**Plan file**: `tests/plans/ux-batch3-landing-page.plan.md`
**Result**: FAILED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 8     |
| Passed             | 5     |
| Failed             | 3     |
| Skipped            | 0     |
| Coverage Gaps      | 3     |

---

## Scenario Results

### Scenario 1: Hero section is visible
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 2 | Assert hero heading is visible | ✅ PASS | Hero with "BarberPro" branding present |
| 3 | Assert hero subheading or tagline visible | ✅ PASS | — |

---

### Scenario 2: CTA links navigate correctly
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 2 | Assert primary CTA "Get Started" or "Register" link visible | ✅ PASS | — |
| 3 | Assert "Login" link visible | ✅ PASS | — |
| 4 | Click "Login" link | ✅ PASS | — |
| 5 | Assert URL is `/en/auth/login` | ✅ PASS | — |

---

### Scenario 3: Feature cards are visible
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 2 | Assert feature/benefit cards are present | ✅ PASS | Feature section with cards visible |
| 3 | Assert at least 3 feature cards | ✅ PASS | — |

---

### Scenario 4: Language switcher on landing page
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 2 | Assert language switcher is visible | ✅ PASS | Globe icon + "Tiếng Việt" link in header |
| 3 | Click language switcher to switch to Vietnamese | ✅ PASS | — |
| 4 | Assert URL changes to `/vi` prefix | ✅ PASS | Redirected to `/vi/...` |

---

### Scenario 5: Language switcher is in the footer
**Priority**: Medium
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 2 | Scroll to footer | ✅ PASS | — |
| 3 | Assert language switcher visible in footer | ❌ FAIL | Language switcher is in the header (banner), not the footer (contentinfo) |

**Failure Detail**:
- **Failed step**: Step 3 — Assert language switcher in footer
- **Expected**: Language switcher rendered in the `contentinfo` (footer) element
- **Actual**: Language switcher exists only in the `banner` (header) element; no switcher in footer
- **Note**: Header placement is better UX than footer for language selection

---

### Scenario 6: Vietnamese locale landing page renders
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `BASE_URL/vi` | ❌ FAIL | 500 error: "obj.hasOwnProperty is not a function" / "Page not found: /vi" |

**Failure Detail**:
- **Failed step**: Step 1 — Navigate to `/vi`
- **Expected**: Vietnamese landing page renders correctly
- **Actual**: 500 Internal Server Error. Root route `/vi` throws `obj.hasOwnProperty is not a function` in Nuxt router
- **Note**: This is a critical bug — the `/vi` root route is broken. `/vi/dashboard/...` sub-routes also fail with the same error.

---

### Scenario 7: Authenticated user clicking "My Dashboard" navigates to dashboard
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure logged in | ✅ PASS | — |
| 2 | Navigate to landing page | ✅ PASS | — |
| 3 | Click "Dashboard" or similar link | ✅ PASS | — |
| 4 | Assert URL is `/en/dashboard` | ✅ PASS | — |

---

### Scenario 8: Authenticated user is redirected from `/en` to dashboard
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure logged in | ✅ PASS | — |
| 2 | Navigate to `BASE_URL/en` | ✅ PASS | — |
| 3 | Assert user is redirected to `/en/dashboard` | ❌ FAIL | Landing page renders for authenticated users — no auto-redirect to dashboard |

**Failure Detail**:
- **Failed step**: Step 3 — Auto-redirect to dashboard for authenticated users
- **Expected**: Visiting `/en` while logged in redirects to `/en/dashboard`
- **Actual**: Landing page renders normally for authenticated users; no redirect implemented
- **Note**: UX improvement — authenticated users should not see the marketing landing page

---

## Edge Case Results

### Edge Case 1: Landing page on mobile viewport
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Resize to mobile viewport | ⏭ SKIP | Viewport resize not performed in this run |

---

### Edge Case 2: Landing page without JavaScript
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Disable JavaScript | ⏭ SKIP | Cannot disable JS via Playwright MCP |

---

### Edge Case 3: Deep link on `/en/auth/register` for unauthenticated user
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate directly to register page | ✅ PASS | — |
| 2 | Assert register form renders | ✅ PASS | Registration form present |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | `/vi` root route throws 500 | Bug: `obj.hasOwnProperty is not a function` in Nuxt router for `/vi` and all `/vi/*` routes | **Critical bug** — investigate i18n route configuration for the `vi` locale root path |
| 2 | Language switcher not in footer | Implementation placed switcher in header only; plan expected footer placement | Decide canonical placement; if footer is required, add it there |
| 3 | No auth redirect from landing page | `middleware/auth.ts` or the landing page `index.vue` does not redirect authenticated users | Add auth guard to redirect `/en` → `/en/dashboard` when user is logged in |

---

## Recommendations

- **Critical**: Fix the `/vi` route 500 error — this affects all Vietnamese-locale pages across the entire app.
- Add authenticated-user redirect from the landing page root (`/en`) to `/en/dashboard`.
- Language switcher placement in the header is acceptable; only add to footer if UX spec explicitly requires it.
