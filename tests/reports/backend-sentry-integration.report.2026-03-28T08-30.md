# Test Report: Backend Sentry Integration (Live DSN Run)

**Executed**: 2026-03-28T08:30:00Z
**Plan file**: `development/features/25-sentry/backend-sentry-integration.plan.md`
**Result**: PARTIAL

> **Note**: This is the live-DSN re-run. The previous run (no DSN) is in `backend-sentry-integration.report.md`.
> Scenarios 1–8 and Edge Cases 1–3 were already verified in the previous run — all passed except Scenario 8
> (pre-existing test failures unrelated to Sentry). This report covers the live-DSN scenarios only.

---

## Summary (this run — live DSN scenarios only)

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 3 (live DSN only) |
| Passed             | 2     |
| Failed             | 0     |
| Skipped            | 1     |
| Coverage Gaps      | 2     |

---

## Environment Changes Applied

| Change | Detail |
|--------|--------|
| Added `SENTRY_DSN` to `/app/.env` | Mapped from `SENTRY_BACKEND_DSN` (user set wrong variable name) |
| Temporarily disabled `when@dev.yaml` override | Commented out `sentry: dsn: ~` to allow live DSN in dev env |
| Restored after testing | `when@dev.yaml` override re-enabled after verification |

---

## Scenario 9 (proxy): Backend DSN connectivity verified via `sentry:test`
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Temporarily disable `when@dev.yaml` DSN override | ✅ PASS | Commented out `sentry: dsn: ~` |
| 2 | Run `php bin/console cache:clear` | ✅ PASS | Exit 0 |
| 3 | Confirm `debug:config sentry` shows real DSN (not null) | ✅ PASS | `dsn: '%env(SENTRY_DSN)%'` resolved |
| 4 | Run `php bin/console sentry:test` | ✅ PASS | "DSN correctly configured in the current client" |
| 5 | Assert event sent successfully | ✅ PASS | "Message sent successfully with ID `54443a59c6c04089b34a33b58d7bdf17`" |
| 6 | Verify event in Sentry dashboard | ⏭ MANUAL | Cannot access dashboard from container — user must verify |

**Notes**: The `sentry:test` command is the official Sentry SDK verification tool. A successful event ID confirms the DSN is valid, the network is reachable, and events are being delivered. Check the backend Sentry project dashboard for the event with ID `54443a59c6c04089b34a33b58d7bdf17`.

---

## Scenario 10 (proxy): ApiException filtering — error responses unchanged with live DSN
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /health with live DSN active | ✅ PASS | 200 `{"status":"ok"}` |
| 2 | GET /api/v1/auth/me (no auth) — 401 ApiException | ✅ PASS | `{"code":401,"message":"JWT Token not found"}` |
| 3 | GET /api/v1/nonexistent — 404 HttpException | ✅ PASS | `{"code":"HTTP_404",...}` |
| 4 | POST /api/v1/auth/login bad creds — 401 INVALID_CREDENTIALS | ✅ PASS | `{"code":"INVALID_CREDENTIALS",...}` |
| 5 | Verify these do NOT appear in Sentry dashboard | ⏭ MANUAL | Cannot access dashboard from container — user must verify |

**Notes**: All business error responses are intact with live DSN. The `ignored_exceptions` list in `sentry.yaml` should filter `ApiException`, `HttpException`, `AuthenticationException`, `RateLimitExceededException`, and `JWTDecodeFailureException`. Dashboard verification is required to confirm no spurious events were created.

---

## Scenario 11: Messenger job failure captured by Sentry
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Requires a running Symfony Messenger worker + a handler that fails + live DSN. Not testable in this environment without additional setup (running worker, failing message).

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Sentry dashboard verification (event 54443a59c6c04089b34a33b58d7bdf17 appears) | Cannot access sentry.io from container | Open Sentry → backend project → verify event appears |
| 2 | Confirm ApiException events do NOT appear in dashboard after business error hits | Cannot access sentry.io from container | After hitting 401/404 endpoints above, verify no new events in Sentry dashboard |

---

## Configuration Issue Found & Fixed

The user set `SENTRY_BACKEND_DSN` and `SENTRY_FRONTEND_DSN` in `.env`, but the app reads:
- Backend: `SENTRY_DSN` (from `config/packages/sentry.yaml`)
- Frontend: `NUXT_PUBLIC_SENTRY_DSN` (from `frontend/.env`)

**Fix applied**: Added `SENTRY_DSN` to `.env` pointing to the same value as `SENTRY_BACKEND_DSN`. Updated `frontend/.env` `NUXT_PUBLIC_SENTRY_DSN` with the frontend DSN value.

## Recommendations

1. **Rename env vars for consistency**: Replace `SENTRY_BACKEND_DSN` / `SENTRY_FRONTEND_DSN` with the actual variable names the app reads (`SENTRY_DSN` and `NUXT_PUBLIC_SENTRY_DSN`). The aliased names add confusion.
2. **Manual dashboard steps** (do these now while the test event is fresh):
   - Open Sentry → backend project → confirm event `54443a59c6c04089b34a33b58d7bdf17` appears with a stack trace
   - Confirm no events were created by the 401/404 API calls made during testing
3. **Frontend dev server restart required**: `NUXT_PUBLIC_SENTRY_DSN` change in `frontend/.env` requires `npm run dev` restart to take effect — Nuxt bakes the DSN into the bundle at startup.
