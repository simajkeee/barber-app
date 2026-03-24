# Test Report: Post-Registration Redirect

**Executed**: 2026-03-24T00:00:00Z
**Plan file**: `development/features/09-onboarding/post-registration-redirect.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 7     |
| Passed             | 4     |
| Failed             | 0     |
| Skipped            | 3     |
| Edge Cases Total   | 3     |
| Edge Cases Passed  | 3     |
| Edge Cases Skipped | 0     |
| Coverage Gaps      | 1     |

**vs. previous run (2026-03-23)**: Same result — 4 passed, 3 skipped (Facebook). All edge cases pass. No regressions.

---

## Test Accounts Used

| Account | Email | Password | State |
|---------|-------|----------|-------|
| A (new during Sc1) | test-account-a@test.com | Password1! | Registered fresh — redirected to `/en/dashboard/shop/create` |
| A (existing for Sc2) | test-account-a@test.com | Password1! | Has shop — login lands on `/en/dashboard` |
| Facebook | N/A | N/A | No test Facebook app — scenarios 3, 4, 5 skipped |

---

## Scenario Results

### Scenario 1: Email registration redirects to shop creation
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/register` | ✅ PASS | — |
| 2 | Fill email with unique new address | ✅ PASS | `test-account-a@test.com` (and B, C during setup) |
| 3 | Fill password with valid password | ✅ PASS | `Password1!` |
| 4 | Fill first name + last name | ✅ PASS | — |
| 5 | Click "Create Account" | ✅ PASS | — |
| 6 | Wait for navigation | ✅ PASS | — |
| 7 | Assert URL is `/en/dashboard/shop/create` | ✅ PASS | All 3 new accounts redirected correctly |
| 8 | Assert shop-creation form is visible | ✅ PASS | "Create Your Shop" heading and form fields visible |

---

### Scenario 2: Email login redirects to dashboard (not shop creation)
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/login` | ✅ PASS | — |
| 2 | Fill email with existing account credentials | ✅ PASS | `test-account-a@test.com` |
| 3 | Fill password | ✅ PASS | — |
| 4 | Click "Log In" | ✅ PASS | — |
| 5 | Wait for navigation | ✅ PASS | — |
| 6 | Assert URL is `/en/dashboard` | ✅ PASS | — |
| 7 | Assert dashboard content is visible | ✅ PASS | Stats cards and user name visible |

---

### Scenario 3: Facebook login as new user redirects to shop creation
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: No Facebook test app or sandbox mock available in dev environment. `[VERIFY]` flag in plan was not resolved.

---

### Scenario 4: Facebook login as existing/returning user redirects to dashboard
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Same as Scenario 3.

---

### Scenario 5: Facebook button on login page — new user redirect
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Same as Scenario 3.

---

### Scenario 6: Registration form validation errors do not redirect
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/register` | ✅ PASS | — |
| 2 | Fill email with `notanemail` | ✅ PASS | — |
| 3 | Fill password with `abc` (too short) | ✅ PASS | — |
| 4 | Click submit | ✅ PASS | — |
| 5 | Assert URL is still `/en/register` | ✅ PASS | No redirect |
| 6 | Assert validation error messages visible | ✅ PASS | `"Invalid value"` (email) + `"Must be at least 8 characters"` (password) |

---

### Scenario 7: Duplicate email registration stays on register page with error
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/register` | ✅ PASS | — |
| 2 | Fill email with `test-account-a@test.com` (already registered) | ✅ PASS | — |
| 3 | Fill valid password + first/last name | ✅ PASS | — |
| 4 | Click submit | ✅ PASS | — |
| 5 | Wait for API response | ✅ PASS | 409 Conflict from backend |
| 6 | Assert URL is still `/en/register` | ✅ PASS | No redirect |
| 7 | Assert error message visible | ✅ PASS | Alert: `"This email is already registered"` |

---

## Edge Case Results

### Edge Case 1: Already-authenticated user navigates to `/register`
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in as Account A → confirm `/en/dashboard` | ✅ PASS | — |
| 2 | Navigate to `/en/register` | ✅ PASS | — |
| 3 | Assert URL is NOT `/en/register` | ✅ PASS | Redirected to `/en/dashboard` by guest middleware |

---

### Edge Case 2: Already-authenticated user navigates to `/login`
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | While authenticated as Account A | ✅ PASS | — |
| 2 | Navigate to `/en/login` | ✅ PASS | — |
| 3 | Assert URL is not `/en/login` | ✅ PASS | Redirected to `/en/dashboard` by guest middleware |

---

### Edge Case 3: Registration succeeds and `/dashboard/shop/create` renders correctly
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/register` and complete registration | ✅ PASS | Verified 3× during account setup |
| 2 | Wait for redirect | ✅ PASS | — |
| 3 | Assert browser lands at valid page with no 404 | ✅ PASS | Shop creation form rendered correctly |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Facebook OAuth scenarios (Sc3, Sc4, Sc5) | No Facebook test app or sandbox mock in dev environment | Set up a Facebook test app with test users, or implement an OAuth mock for automated testing; run these 3 scenarios manually with a real Facebook account before release |

---

## Recommendations

- **Facebook OAuth** (3 of 7 scenarios untested): The `isNewUser` branch in the Facebook handler remains unverified by automated tests. These should be tested before a production release, either with a Facebook sandbox app or by mocking the OAuth callback.
- **All non-Facebook flows confirmed clean**: Email registration → shop creation and email login → dashboard both work correctly and consistently across multiple accounts. Guest middleware reliably blocks authenticated users from auth pages.
- No regressions vs. previous run.
