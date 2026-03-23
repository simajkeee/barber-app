# Test Report: Auth & Setup

**Executed**: 2026-03-20T00:00:00Z
**Plan file**: `tests/plans/auth.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 11    |
| Passed             | 10    |
| Failed             | 1     |
| Skipped            | 3 (edge cases — Medium/Low priority, out of scope for this run) |
| Coverage Gaps      | 1     |

---

## Scenario Results

### Scenario 1: Register a new account
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Assert registration form visible | ✅ PASS | — |
| 3 | Fill name, email, password fields | ✅ PASS | — |
| 4 | Click register button | ✅ PASS | — |
| 5 | Assert redirect to /dashboard | ✅ PASS | — |

---

### Scenario 2: Register with duplicate email
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Fill form with existing email | ✅ PASS | — |
| 3 | Click register | ✅ PASS | — |
| 4 | Assert error message for duplicate email | ✅ PASS | Error shown inline |

---

### Scenario 3: Register with invalid data
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /register | ✅ PASS | — |
| 2 | Submit empty form | ✅ PASS | — |
| 3 | Assert field validation errors | ✅ PASS | Required fields highlighted |

---

### Scenario 4: Successful login
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /login | ✅ PASS | — |
| 2 | Fill email and password | ✅ PASS | playwright@barberpro.com / Password123! |
| 3 | Click login | ✅ PASS | — |
| 4 | Assert redirect to /dashboard | ✅ PASS | Dashboard rendered with shop name |

---

### Scenario 5: Login with wrong password
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /login | ✅ PASS | — |
| 2 | Fill with wrong password | ✅ PASS | — |
| 3 | Click login | ✅ PASS | — |
| 4 | Assert error message | ✅ PASS | Invalid credentials error shown |

---

### Scenario 6: Authenticated route redirect for guests
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure unauthenticated state | ✅ PASS | — |
| 2 | Navigate to /dashboard | ✅ PASS | — |
| 3 | Assert redirect to /login | ✅ PASS | auth middleware working |

---

### Scenario 7: Guest route redirect for authenticated users
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Log in successfully | ✅ PASS | — |
| 2 | Navigate to /login | ✅ PASS | — |
| 3 | Assert redirect to /dashboard | ✅ PASS | guest middleware working |

---

### Scenario 8: Forgot password — success state
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /forgot-password | ✅ PASS | — |
| 2 | Fill with known email | ✅ PASS | playwright@barberpro.com |
| 3 | Click submit | ✅ PASS | — |
| 4 | Assert success message visible | ✅ PASS | "Nếu tài khoản với email này tồn tại, liên kết đặt lại mật khẩu đã được gửi." |

---

### Scenario 9: Forgot password — unknown email shows same message
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /forgot-password | ✅ PASS | — |
| 2 | Fill with nonexistent email | ✅ PASS | — |
| 3 | Submit and assert same success message | ✅ PASS | No user enumeration — identical response |

---

### Scenario 10: Reset password with valid token
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Extract token from MailHog email | ✅ PASS | Messenger queue consumed via `php bin/console messenger:consume` |
| 2 | Navigate to /reset-password?token={token} | ✅ PASS | — |
| 3 | Assert reset form visible | ✅ PASS | — |
| 4 | Fill new password and submit | ✅ PASS | — |
| 5 | Assert redirect to /login with success alert | ✅ PASS | "Mật khẩu của bạn đã được đặt lại thành công." |

---

### Scenario 11: Reset password with invalid token
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /reset-password?token=invalid-token-xyz-000 | ✅ PASS | Form loads |
| 2 | Fill new password and submit | ✅ PASS | — |
| 3 | Assert error message displayed | ❌ FAIL | No error shown — form stays blank |

**Failure Detail**:
- **Failed step**: Step 3 — assert error message displayed
- **Expected**: An error alert indicating the token is invalid or expired (HTTP 400 `INVALID_RESET_TOKEN`)
- **Actual**: API returned HTTP error (confirmed in console: `Failed to load resource: the server responded with a status of 400`), but no error message is rendered in the UI. The form stays on the page silently.
- **Screenshot**: `tests/reports/screenshots/auth/scenario-11-step-3.png`

---

## Edge Case Results

### Edge Case 1–3
**Result**: ⏭ SKIPPED
**Reason**: Medium/Low priority — excluded from this run (Critical + High only).

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Messenger worker not running in Docker stack | Forgot-password emails are queued async via Symfony Messenger but no `messenger:consume` worker container exists. Emails will not be delivered until the queue is manually consumed. | Add a `messenger_worker` container to `docker-compose.yml`, or run `php bin/console messenger:consume async` in the background. |

---

## Recommendations

- **Bug (S11)**: Reset-password page silently swallows the API error when the token is invalid. The `useAuth` composable's error from `resetPassword()` is not being displayed in the form. Fix: surface `error` from the composable in the template (similar to how login errors are shown).
- **Infrastructure**: MailHog (host-side) is the active mail catcher, not Mailpit (Docker container). The `.env` `MAILER_DSN=smtp://host.docker.internal:1025` routes emails to the host MailHog. This works but is inconsistent with the Docker-first setup. Consider updating `MAILER_DSN` to point to the `mailer` service.
- **S10 prerequisite**: Requires `php bin/console messenger:consume async --limit=5` before checking MailHog. Automate this in test setup or add a Messenger worker container.
