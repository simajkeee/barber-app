# Test Report: Health Check Endpoint

**Executed**: 2026-03-24T18:10:00Z
**Plan file**: `development/features/12-health-check/health-check.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 7     |
| Passed             | 0     |
| Failed             | 0     |
| Skipped            | 7     |
| Edge Cases Total   | 4     |
| Edge Cases Passed  | 0     |
| Edge Cases Failed  | 0     |
| Edge Cases Skipped | 4     |
| Coverage Gaps      | 1     |

---

## Pre-Execution Check

**Smoke check**: `GET http://localhost/health` → **404 Not Found** (`text/html; charset=UTF-8`)

The health check controller has not been implemented. The plan's Prerequisites section explicitly flags this:

> `[NOT YET IMPLEMENTED — validate before running]` — controller and route must be deployed before executing this plan.

**Decision**: All 7 scenarios and 4 edge cases are marked SKIPPED. No test execution was performed.

---

## Scenario Results

### Scenario 1: Healthy database returns 200 with `{"status":"ok"}`
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 2: Response Content-Type is `application/json`
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 3: Endpoint is publicly accessible — no Authorization header required
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 4: Endpoint is not rate-limited under rapid polling
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 5: Wrong HTTP method returns 405 Method Not Allowed
**Priority**: Medium
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 6: Database down — returns 503 with `{"status":"error","detail":"..."}`
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

### Scenario 7: Response body contains no stack trace or credentials on failure
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — `GET /health` returns 404. Controller not yet implemented.

---

## Edge Case Results

### Edge Case 1: HEAD request returns 200 with no body
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — controller not yet implemented.

---

### Edge Case 2: Endpoint path is exactly `/health` — no trailing slash
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — controller not yet implemented.

---

### Edge Case 3: `/health/` with trailing slash returns 404 (not a valid route)
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — controller not yet implemented. Note: `/health/` currently also returns 404 as there is no `/health` route at all.

---

### Edge Case 4: Endpoint is outside the `/api/` prefix — not intercepted by ApiExceptionListener
**Result**: ⏭ SKIPPED

**Reason**: Prerequisite not met — controller not yet implemented.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | All 7 scenarios and 4 edge cases untested | `GET /health` returns `404 Not Found` — the `HealthController` and its route have not been implemented and deployed | Implement the health check feature (see `development/features/12-health-check/`), deploy it, then re-run this plan |

---

## Recommendations

- **Implement the feature first**: All test scenarios are blocked on the controller existing. Implement `HealthController` with a `GET /health` route that runs a `SELECT 1` DBAL probe and returns `{"status":"ok"}` / `{"status":"error","detail":"..."}`.
- **Re-run after implementation**: Once deployed, all 7 scenarios can be executed without modification. The plan is complete and well-structured.
- **Scenario 6 requires Docker access**: Stopping the database container (`docker compose stop db`) to test the 503 path requires shell access. Confirm the test environment permits this before re-running.
- **Rate-limit exclusion (Sc4)**: Confirm the Symfony rate limiter config explicitly excludes `/health` — this should be verified in `config/packages/framework.yaml` alongside the implementation.
