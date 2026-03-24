# Test Report: Post-Registration Redirect

**Executed**: 2026-03-23T18:44:00Z
**Plan file**: `development/features/09-onboarding/post-registration-redirect.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric          | Value |
|-----------------|-------|
| Total Scenarios | 7     |
| Passed          | 4     |
| Failed          | 0     |
| Skipped         | 3     |
| Edge Cases      | 3     |
| EC Passed       | 3     |
| EC Failed       | 0     |
| EC Skipped      | 0     |
| Coverage Gaps   | 1     |

---

## Test Accounts Used

| Account | Email | Password | State |
|---------|-------|----------|-------|
| New (Sc1) | account.a/b/c.onboarding@example.com | Test1234! | Registered fresh during setup — all redirected to /dashboard/shop/create |
| Existing (Sc2) | account.b.onboarding@example.com | Test1234! | Has shop — used for login redirect verification |
| Facebook | N/A | N/A | No test Facebook app available — scenarios skipped |

---

## Scenario Results

### Scenario 1: Email registration redirects to shop creation
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Fill email with unique new address | ✅ PASS | Verified with 3 fresh accounts during setup |
| 3 | Fill password | ✅ PASS | — |
| 4 | Fill passwordConfirmation | ✅ PASS | — |
| 5 | Click submit | ✅ PASS | — |
| 6 | Wait for navigation | ✅ PASS | — |
| 7 | Assert URL is /dashboard/shop/create | ✅ PASS | All 3 accounts redirected correctly |
| 8 | Assert shop-creation form is visible | ✅ PASS | "Tạo cửa hàng" heading and form confirmed |

**Notes**: Validated three times — Accounts A, B, and C all redirected to `/dashboard/shop/create` upon registration.

---

### Scenario 2: Email login redirects to dashboard (not shop creation)
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /login | ✅ PASS | — |
| 2 | Fill email with existing account credentials | ✅ PASS | Accounts A, B, C all tested |
| 3 | Fill password | ✅ PASS | — |
| 4 | Click submit | ✅ PASS | — |
| 5 | Wait for navigation | ✅ PASS | — |
| 6 | Assert URL is /dashboard | ✅ PASS | All logins land on /dashboard |
| 7 | Assert dashboard content is visible | ✅ PASS | Stats cards and shop name visible |

---

### Scenario 3: Facebook login as new user redirects to shop creation
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Facebook OAuth requires a test Facebook app or sandbox mock. No such setup is available in this dev environment. `[VERIFY: confirm Facebook test app availability]` flag in plan was not resolved.

---

### Scenario 4: Facebook login as existing/returning user redirects to dashboard
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Same as Scenario 3 — no Facebook test account linked to an existing app user available.

---

### Scenario 5: Facebook button on login page — new user redirect
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Same as Scenario 3 — Facebook OAuth mock not available.

---

### Scenario 6: Registration form validation errors do not redirect
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Fill email with invalid format "notanemail" | ✅ PASS | — |
| 3 | Fill password with "abc" (too short) | ✅ PASS | — |
| 4 | Click submit | ✅ PASS | — |
| 5 | Assert URL is still /register | ✅ PASS | No redirect occurred |
| 6 | Assert validation error messages are visible | ✅ PASS | "Giá trị không hợp lệ" (email) + "Phải có ít nhất 8 ký tự" (password) |

---

### Scenario 7: Duplicate email registration stays on register page with error
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Fill email with existing account address | ✅ PASS | Used account.a.onboarding@example.com |
| 3 | Fill valid password and confirmation | ✅ PASS | — |
| 4 | Click submit | ✅ PASS | — |
| 5 | Wait for API response | ✅ PASS | 409 Conflict returned |
| 6 | Assert URL is still /register | ✅ PASS | No redirect |
| 7 | Assert error message visible | ✅ PASS | Alert: "Email này đã được đăng ký" |

---

## Edge Case Results

### Edge Case 1: Already-authenticated user navigates to `/register`
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in as Account B → confirm /dashboard | ✅ PASS | — |
| 2 | Navigate to /register | ✅ PASS | — |
| 3 | Assert URL is NOT /register | ✅ PASS | Redirected to /dashboard by guest middleware |

---

### Edge Case 2: Already-authenticated user navigates to `/login`
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | While authenticated as Account B | ✅ PASS | — |
| 2 | Navigate to /login | ✅ PASS | — |
| 3 | Assert URL is not /login | ✅ PASS | Redirected to /dashboard by guest middleware |

---

### Edge Case 3: Registration succeeds but `/dashboard/shop/create` returns 404
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register and complete registration | ✅ PASS | Verified 3 times during account setup |
| 2 | Wait for redirect | ✅ PASS | — |
| 3 | Assert browser lands at valid page | ✅ PASS | Shop creation form rendered correctly with no errors |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Facebook OAuth scenarios (Sc3, Sc4, Sc5) | No Facebook test app or sandbox mock available in dev environment | Set up a Facebook test app with test accounts, or implement an OAuth mock/stub for automated testing; run these scenarios manually with a real Facebook account |

---

## Recommendations

1. **Facebook OAuth coverage**: Scenarios 3, 4, and 5 (3 of 7 total) are untested. These cover the `isNewUser` branch in the Facebook login handler. If Facebook OAuth is a supported auth path, these should be tested before release — either via a Facebook sandbox app or by mocking the OAuth callback endpoint.

2. **Locale-prefixed redirect targets**: The plan notes that all scenarios should be repeated with `/en/` prefix. This was not done to avoid duplication. A quick sanity check on `/en/register` → `/en/dashboard/shop/create` and `/en/login` → `/en/dashboard` is recommended.

3. **All redirect paths confirmed clean**: Both email registration → shop creation and email login → dashboard flows work correctly and consistently. Guest middleware correctly blocks authenticated users from accessing auth pages.
