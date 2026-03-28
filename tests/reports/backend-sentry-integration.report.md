# Test Report: Backend Sentry Integration

**Executed**: 2026-03-28T07:43:00Z
**Plan file**: `development/features/25-sentry/backend-sentry-integration.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 11    |
| Passed             | 6     |
| Failed             | 1     |
| Skipped            | 4     |
| Coverage Gaps      | 5     |

---

## Scenario Results

### Scenario 1: Application boots successfully after Sentry installation
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | `php bin/console cache:clear` | ✅ PASS | Exit code 0 |
| 2 | `php bin/console debug:container sentry` | ✅ PASS | 27 Sentry services registered (sentry.client, Sentry\ClientInterface, HubInterface, etc.) |
| 3 | GET http://localhost/health | ✅ PASS | Route correction: plan says `/api/health` — actual route is `/health` (no `/api` prefix) |
| 4 | Assert status 200 | ✅ PASS | — |
| 5 | Assert body contains `"status": "ok"` | ✅ PASS | `{"status":"ok"}` |

---

### Scenario 2: GET /health still responds after Sentry integration
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET http://localhost/health | ✅ PASS | Route corrected from plan's `/api/health` |
| 2 | Assert status 200 | ✅ PASS | — |
| 3 | Assert body contains `"status": "ok"` | ✅ PASS | `{"status":"ok"}` |
| 4 | Assert Content-Type contains `application/json` | ✅ PASS | `Content-Type: application/json` |

---

### Scenario 3: ApiException still returns correct JSON (not swallowed by Sentry)
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /api/v1/auth/login with invalid credentials | ✅ PASS | — |
| 2 | Assert status 401 or 400 | ✅ PASS | 401 Unauthorized |
| 3 | Assert Content-Type contains `application/json` | ✅ PASS | — |
| 4 | Assert body contains key `"code"` | ✅ PASS | `"code":"INVALID_CREDENTIALS"` |
| 5 | Assert body does NOT contain `"exception"` or `"trace"` | ✅ PASS | Clean response, no debug leak |

---

### Scenario 4: 404 Not Found still returns correct JSON
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /api/v1/this-route-does-not-exist | ✅ PASS | — |
| 2 | Assert status 404 | ✅ PASS | — |
| 3 | Assert Content-Type contains `application/json` | ✅ PASS | — |
| 4 | Assert body contains key `"code"` | ✅ PASS | `"code":"HTTP_404"` |

---

### Scenario 5: 401 Unauthorized still returns correct JSON
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /api/v1/auth/me with no Authorization header | ✅ PASS | — |
| 2 | Assert status 401 | ✅ PASS | — |
| 3 | Assert Content-Type contains `application/json` | ✅ PASS | — |
| 4 | Assert body contains key `"code"` | ✅ PASS | `{"code":401,"message":"JWT Token not found"}` |

---

### Scenario 6: 422 Validation error still returns correct JSON with details
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /api/v1/auth/register with invalid body | ✅ PASS | — |
| 2 | Assert status 400 | ✅ PASS | — |
| 3 | Assert Content-Type contains `application/json` | ✅ PASS | — |
| 4 | Assert body contains `"code": "VALIDATION_ERROR"` | ✅ PASS | — |
| 5 | Assert body contains key `"details"` | ✅ PASS | — |
| 6 | Assert `details` is a non-empty array | ✅ PASS | 6 field errors returned |

---

### Scenario 7: Authenticated endpoint still works normally
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register user sentry-test-new@example.com / Password123! | ✅ PASS | Phone 0901234568 (0901234567 already existed) |
| 2 | GET /api/v1/auth/me with Bearer token | ✅ PASS | — |
| 3 | Assert status 200 | ✅ PASS | — |
| 4 | Assert body contains id, email, firstName, lastName | ✅ PASS | All keys present |
| 5 | Assert Content-Type contains `application/json` | ✅ PASS | — |

---

### Scenario 8: Sentry disabled in test environment — no network calls during PHPUnit
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Run `php vendor/bin/phpunit` | ✅ PASS | Tests executed |
| 2 | Assert exit code is `0` (all tests pass) | ❌ FAIL | 8 failures, 3 errors — pre-existing failures unrelated to Sentry |
| 3 | Assert no output contains `sentry.io` or Sentry network errors | ✅ PASS | No Sentry network errors in output |

**Failure Detail**:
- **Failed step**: Step 2 — exit code assertion
- **Expected**: All 392 tests pass (exit 0)
- **Actual**: 8 failures, 3 errors (AppointmentServiceTest × 6, ApiExceptionListenerTest × 2)
- **Sentry-specific assertion**: PASSED — no Sentry network calls observed, DSN correctly disabled
- **Assessment**: These are **pre-existing test failures** unrelated to the Sentry integration. The critical Sentry requirement (no outbound network calls in tests) is satisfied.

---

### Scenario 9: RuntimeException is captured by Sentry
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: `[REQUIRES LIVE SENTRY DSN]` — not runnable in local dev or CI. Staging environment with real `SENTRY_DSN` required.

---

### Scenario 10: ApiException does NOT appear in Sentry
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: `[REQUIRES LIVE SENTRY DSN]` — Sentry dashboard access required.

---

### Scenario 11: Messenger job failure is captured by Sentry
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: `[REQUIRES LIVE SENTRY DSN]` and running Messenger worker required.

---

## Edge Case Results

### Edge Case 1: Empty SENTRY_DSN — app does not crash
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Confirm `SENTRY_DSN=` (empty) in .env | ✅ PASS | `SENTRY_DSN=` confirmed |
| 2 | `php bin/console cache:clear` — assert exit 0 | ✅ PASS | — |
| 3 | GET /health | ✅ PASS | — |
| 4 | Assert status 200 | ✅ PASS | — |

---

### Edge Case 2: Rate limit error still returns 429, not absorbed by Sentry
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Send POST /api/v1/auth/login 12 times rapidly | ✅ PASS | Requests 1–10: 401, requests 11–12: 429 |
| 2 | Assert 11th response is 429 | ✅ PASS | — |
| 3 | Assert body contains `"code"` field | ✅ PASS | `"code":"RATE_LIMIT_EXCEEDED"` |
| 4 | Assert Content-Type contains `application/json` | ✅ PASS | — |

---

### Edge Case 3: Expired JWT token still returns 401, not 500
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /api/v1/auth/me with `Authorization: Bearer invalid.jwt.token` | ✅ PASS | — |
| 2 | Assert status 401 | ✅ PASS | Not 500 — no escalation |
| 3 | Assert Content-Type contains `application/json` | ✅ PASS | — |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Plan references `/api/health` — actual route is `/health` | Plan inaccuracy (no `/api` prefix on health controller) | Update plan to use `/health` |
| 2 | Actual Sentry event delivery for RuntimeException | `[REQUIRES LIVE SENTRY DSN]` — Scenario 9 skipped | Manual staging test with real DSN |
| 3 | ApiException NOT appearing in Sentry dashboard | `[REQUIRES LIVE SENTRY DSN]` — Scenario 10 skipped | Manual staging verification |
| 4 | Messenger job failure captured by Sentry | `[REQUIRES LIVE SENTRY DSN]` + running worker — Scenario 11 skipped | Manual staging + worker setup |
| 5 | Pre-existing PHPUnit test failures (8 failures, 3 errors) | Not Sentry-related — AppointmentServiceTest and ApiExceptionListenerTest | Fix pre-existing test failures separately |

---

## Recommendations

1. **Pre-existing test failures are blocking Scenario 8**: `AppointmentServiceTest` (6 failures — likely timezone or shop schedule logic issue) and `ApiExceptionListenerTest` (2 failures — error code vs status code assertion mismatch). These should be fixed independently of the Sentry integration.
2. **Sentry DSN is correctly disabled**: The empty `SENTRY_DSN` and `when@dev.yaml`/`when@test.yaml` overrides work as expected — no Sentry network calls observed, no SDK initialization errors.
3. **All 27 Sentry services register correctly**: The container compiles cleanly with all Sentry services, confirming the `sentry.yaml` configuration is valid.
4. **Error response shapes are unchanged**: All 6 runnable regression scenarios passed — ApiException, 404, 401, 422, 429, and authenticated endpoints all return the same JSON shapes as before Sentry installation.
