# Test Report: Trial Expiry Command

**Executed**: 2026-03-25T17:50:00Z
**Plan file**: `development/features/23-free-trial-phone-number/trial-expiry-command.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 6     |
| Passed             | 6     |
| Failed             | 0     |
| Skipped            | 0     |
| Edge Cases Total   | 3     |
| Edge Cases Passed  | 3     |
| Edge Cases Skipped | 0     |
| Coverage Gaps      | 2     |

---

## Scenario Results

### Scenario 1: Command runs with no overdue trials
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure no overdue trials in DB | ✅ PASS | No subscriptions had trial_ends_at < now with plan=pro, status=active, endDate=NULL |
| 2 | Run `php bin/console app:subscriptions:expire-trials` | ✅ PASS | Executed via PHP CLI (shared volume — equivalent to docker exec) |
| 3 | Assert exit code 0 | ✅ PASS | — |
| 4 | Assert output contains `"Expired 0 trial(s)"` | ✅ PASS | Full output: `[OK] Expired 0 trial(s). Downgraded to FREE plan.` |
| 5 | Assert output contains `"Downgraded to FREE plan"` | ✅ PASS | — |

---

### Scenario 2: Command expires one overdue trial
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register account + create shop | ✅ PASS | Status 201 |
| 2 | GET /subscription — trigger trial creation | ✅ PASS | isInTrial=true, plan=pro |
| 3 | Assert isInTrial=true, plan=pro | ✅ PASS | — |
| 4 | Backdate trial_ends_at to yesterday via DB | ✅ PASS | Used `date_trunc('second', NOW() AT TIME ZONE 'UTC') - INTERVAL '1 day'` |
| 5 | Run `php bin/console app:subscriptions:expire-trials` | ✅ PASS | — |
| 6 | Assert exit code 0 | ✅ PASS | — |
| 7 | Assert output contains `"Expired 1 trial(s)"` | ✅ PASS | Full output: `[OK] Expired 1 trial(s). Downgraded to FREE plan.` |
| 8 | GET /subscription | ✅ PASS | — |
| 9 | Assert plan = "free" | ✅ PASS | — |
| 10 | Assert trial.isInTrial = false | ✅ PASS | — |
| 11 | Assert trial.trialDaysRemaining = null | ✅ PASS | — |

---

### Scenario 3: Command expires multiple overdue trials
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register 3 accounts, create shops, trigger trials for each | ✅ PASS | All 3 regStatus 201 |
| 2 | Backdate trial_ends_at to yesterday for all 3 via DB | ✅ PASS | 3 rows updated |
| 3 | Run expiry command | ✅ PASS | — |
| 4 | Assert exit code 0 | ✅ PASS | — |
| 5 | Assert output contains `"Expired 3 trial(s)"` | ✅ PASS | Full output: `[OK] Expired 3 trial(s). Downgraded to FREE plan.` |
| 6–7 | GET /subscription for each account — assert plan=free, isInTrial=false | ✅ PASS | All 3 confirmed free |

---

### Scenario 4: Command is idempotent — second run reports 0
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Set up overdue trial and run first time (reuse S2 setup) | ✅ PASS | First run: "Expired 1 trial(s)" |
| 4 | Run command second time | ✅ PASS | — |
| 5 | Assert exit code 0 | ✅ PASS | — |
| 6 | Assert output contains `"Expired 0 trial(s)"` | ✅ PASS | Already downgraded to FREE — no longer matches filter |

---

### Scenario 5: Active trial is NOT affected by the command
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register account + create shop + trigger trial | ✅ PASS | Status 201 |
| 2 | Assert isInTrial=true (trial ends in 30 days) | ✅ PASS | trialDaysRemaining=30 |
| 3 | Run `php bin/console app:subscriptions:expire-trials` | ✅ PASS | Output: "Expired 0 trial(s)" |
| 4 | GET /subscription | ✅ PASS | — |
| 5 | Assert plan still = "pro" | ✅ PASS | — |
| 6 | Assert isInTrial still = true | ✅ PASS | — |
| 7 | Assert trialDaysRemaining still ~29 or 30 | ✅ PASS | Returned 29 |

---

### Scenario 6: trialEndsAt preserved after expiry
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Use S2 account (already expired) | ✅ PASS | — |
| 2 | GET /subscription | ✅ PASS | — |
| 3 | Assert isInTrial = false | ✅ PASS | — |
| 4 | Assert trialEndsAt is non-null past datetime | ✅ PASS | Value: "2026-03-25T00:47:14+07:00" — preserved, not nulled |

---

## Edge Case Results

### Edge Case 1: Paid PRO subscription with past trialEndsAt is NOT expired
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register account + create shop + trigger trial | ✅ PASS | — |
| 2 | Set end_date=future + trial_ends_at=yesterday in DB | ✅ PASS | Simulates paid PRO with expired trial |
| 3 | Run expiry command | ✅ PASS | Output: "Expired 0 trial(s)" |
| 4 | Assert output contains "Expired 0" | ✅ PASS | `endDate IS NULL` guard in `findOverdueTrials()` correctly excludes this |
| 5 | GET /subscription | ✅ PASS | — |
| 6 | Assert plan still = "pro" with non-null endDate | ✅ PASS | plan=pro, endDate="2026-04-25T00:49:16+07:00" |

---

### Edge Case 2: Cancelled subscription with past trialEndsAt is not affected
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register account; set status=cancelled, plan=pro, trial_ends_at=yesterday via DB | ✅ PASS | 1 row updated |
| 2 | Run expiry command | ✅ PASS | Output: "Expired 0 trial(s)" |
| 3 | Assert output contains "Expired 0" | ✅ PASS | `status = active` filter correctly excludes cancelled |
| 4 | Assert subscription status still = cancelled in DB | ✅ PASS | Unchanged |

---

### Edge Case 3: Command with empty database / no matching subscriptions
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure no subscriptions match the expiry criteria | ✅ PASS | All overdue trials already expired in prior scenarios |
| 2 | Run expiry command | ✅ PASS | — |
| 3 | Assert exit code 0 | ✅ PASS | Confirmed via `echo $?` = 0 |
| 4 | Assert output contains "Expired 0 trial(s)" | ✅ PASS | Handles empty result set gracefully |

*Note: Full DB empty state was not tested (shared test DB), but zero-match behavior is functionally identical and validated here.*

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Microseconds in raw SQL break Doctrine parser | `NOW()` in raw PostgreSQL returns microsecond-precision timestamps (e.g. `2026-03-24 17:41:17.070914+00`); Doctrine's `DateTimeTzImmutableType` expects `Y-m-d H:i:sO` without microseconds — command crashes mid-run | Always use `date_trunc('second', NOW() AT TIME ZONE 'UTC')` in test setup SQL. The app's own Doctrine writes don't have this issue (PHP DateTime has no microseconds) |
| 2 | Docker exec not available — used PHP CLI directly | `docker compose` not accessible from within Claude container; used `php bin/console` directly (shared volume means identical result) | No action required for correctness; document that `php bin/console` = `docker compose exec php bin/console` when volumes are shared |

---

## Recommendations

- **Microseconds gotcha in test setup**: When backdating `trial_ends_at` via raw SQL, always truncate to seconds: `date_trunc('second', NOW() AT TIME ZONE 'UTC') - INTERVAL '1 day'`. Using bare `NOW()` generates microseconds that crash Doctrine's `DateTimeTzImmutableType` when the command loads the entity.
- **Command exit code is always 0**: The command uses `SymfonyStyle::success()` which always exits 0. There is no failure exit code — even if 0 records are processed. This is expected behavior.
- **Rate limiter management**: Multiple `cache:pool:clear cache.rate_limiter` calls were needed across this module. Consider adding a `.env.test` override: `RATE_LIMITER_REGISTRATION_LIMIT=100` or disable the rate limiter in test/dev environment.
- **All command output assertions confirmed**: The `[OK]` prefix, "Expired N trial(s)", and "Downgraded to FREE plan" message format are all stable and match the plan's expected output exactly.
