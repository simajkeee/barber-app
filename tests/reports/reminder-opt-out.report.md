# Test Report: Reminder Opt-Out

**Executed**: 2026-03-28T08:55:00Z
**Plan file**: `tests/plans/development/features/14-automated-reminders/reminder-opt-out.plan.md`
**Result**: FAILED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 10    |
| Passed             | 6     |
| Failed             | 1     |
| Skipped            | 4     |
| Coverage Gaps      | 2     |

---

## Scenario Results

### Scenario 1: Valid token opt-out returns 200
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Retrieve valid token `3980663f...` for secondclient (reminderOptOut=false) | ✅ PASS | |
| 2 | POST /api/v1/public/reminders/opt-out with valid token | ✅ PASS | No Authorization header |
| 3 | Assert status 200 | ✅ PASS | |
| 4 | Assert response `"message": "You have been unsubscribed from reminders."` | ✅ PASS | Exact match |
| 5 | Assert `Client.reminderOptOut = true` in DB | ✅ PASS | Confirmed via SQL query |

---

### Scenario 2: Invalid token returns 404 with INVALID_OPT_OUT_TOKEN
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST with 64-zero token | ✅ PASS | |
| 2 | Assert status 404 | ✅ PASS | |
| 3 | Assert `"code": "INVALID_OPT_OUT_TOKEN"` | ✅ PASS | |
| 4 | Assert `"message": "Invalid or unknown token."` | ✅ PASS | Exact match |

---

### Scenario 3: Opt-out is idempotent
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Seed: already-opted-out client with valid token | ✅ PASS | secondclient from Scenario 1 |
| 2 | First call with same token | ✅ PASS | 200 |
| 3 | Second call with same token | ✅ PASS | 200 |
| 4 | Assert response contains unsubscribe message | ✅ PASS | `"message": "You have been unsubscribed from reminders."` |

---

### Scenario 4: Missing token field returns 400 VALIDATION_ERROR
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST with empty body `{}` | ✅ PASS | |
| 2 | Assert status 400 | ❌ FAIL | Got 404 |
| 3 | Assert `"code": "VALIDATION_ERROR"` | ❌ FAIL | Got `"INVALID_OPT_OUT_TOKEN"` |
| 4 | Assert details contain `field: "token"` | ❌ FAIL | |

**Failure Detail**:
- **Failed step**: Step 2 — status code assertion
- **Expected**: 400 `VALIDATION_ERROR` with `details[field="token"]`
- **Actual**: 404 `INVALID_OPT_OUT_TOKEN` — `"Invalid or unknown token."`
- **Root cause**: `OptOutRequest::$token` is declared as `string $token = ''` with only `#[Assert\Regex(pattern: '/^[0-9a-f]{64}$/')]`. Symfony's `Regex` constraint skips empty strings when there is no `#[Assert\NotBlank]`. So when the field is absent from the JSON body, it defaults to `''`, which passes validation, and the empty string lookup in `findByToken('')` returns null → 404.
- **Fix**: Add `#[Assert\NotBlank]` to `OptOutRequest::$token`.

---

### Scenario 5: Token too short returns validation error
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST with `"token": "abc123"` (6 chars) | ✅ PASS | |
| 2 | Assert status 400 | ✅ PASS | |
| 3 | Assert `"code": "VALIDATION_ERROR"` | ✅ PASS | `details[field="token", message="Invalid token format."]` |

---

### Scenario 6: Frontend /opt-out page shows loading state
**Priority**: Medium
**Result**: ⏭ SKIPPED

**Reason**: Playwright Chrome browser unavailable (macOS conflict — "Opening in existing browser session").
**Code verification**: `status = ref('loading')` is set when `token` is truthy on component setup. Loading spinner (`animate-spin`) renders conditionally on `status === 'loading'`. Implementation is correct.

---

### Scenario 7: Frontend /opt-out page shows success state
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: Playwright Chrome browser unavailable.
**Code verification** (`frontend/pages/opt-out.vue`):
- On `onMounted`, `processOptOut()` calls POST; on success sets `status = 'success'`
- Success text: `t('reminders.optOut.success')` = `"Bạn đã hủy đăng ký nhận email nhắc hẹn."` ✅ (matches plan)
- Homepage link: `<NuxtLink :to="localePath('/')">` ✅

---

### Scenario 8: Frontend /opt-out page shows error state for invalid token
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Playwright Chrome browser unavailable.
**Code verification**: On `$fetch` error, `status = 'error'`. Error text: `t('reminders.optOut.error')` = `"Link không hợp lệ hoặc đã hết hạn."` ✅ (matches plan).

---

### Scenario 9: Frontend /opt-out page with no token query param
**Priority**: Medium
**Result**: ⏭ SKIPPED

**Reason**: Playwright Chrome browser unavailable.
**Code verification**: `const token = typeof rawToken === 'string' ? rawToken : ''`. Empty token → `status = ref('error')` immediately (token is falsy). No JS crash. Error state shown without API call. ✅

---

### Scenario 10: Opt-out endpoint accessible without JWT
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST with valid token, NO Authorization header | ✅ PASS | |
| 2 | Assert status 200 | ✅ PASS | |

---

## Edge Case Results

### Edge Case 1: Rate limiting — 429 after limit exceeded
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Send 11 requests in rapid succession | ✅ PASS | Used 64-zero token (valid format) |
| 2 | Assert 429 returned when limit exceeded | ✅ PASS | Hit at request 5 (bucket partially consumed from earlier tests) |
| 3 | Assert response has code field | ✅ PASS | `"code": "RATE_LIMIT_EXCEEDED"` |

**Note**: Rate limit triggered earlier than request 11 because the `publicBookingLimiter` bucket (shared across all public endpoints) was partially consumed by earlier test requests in the same session.

---

### Edge Case 2: 64-char non-hex characters
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST with 64 uppercase-Z token | ✅ PASS | |
| 2 | Assert status 400 or 404 | ✅ PASS | Got 400 |
| 3 | Assert `VALIDATION_ERROR` | ✅ PASS | Regex `/^[0-9a-f]{64}$/` rejects non-hex chars before DB query |

---

### Edge Case 3: Empty string token
**Result**: ⏭ SKIPPED

**Reason**: Rate limit exceeded from Edge Case 1 testing; 429 returned before reaching the controller.
**Expected behavior** (based on code analysis): Same bug as Scenario 4 — Regex constraint skips empty strings, returning 404 `INVALID_OPT_OUT_TOKEN` instead of 400 `VALIDATION_ERROR`. Fix is the same: add `#[Assert\NotBlank]`.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Playwright browser conflict blocked 4 frontend scenarios | Chrome on host macOS hijacks the Playwright MCP browser launch ("Opening in existing browser session"). | Close Chrome on host before running browser tests, or configure Playwright to use a different browser profile. Frontend behavior verified via code inspection — implementation is correct. |
| 2 | Edge Case 3 rate-limited | The `publicBookingLimiter` bucket was consumed by Edge Case 1 and earlier test requests. | Run Edge Case 3 in a fresh session with a reset rate-limiter, or use a different IP. |

---

## Recommendations

- **Bug**: `OptOutRequest::$token` is missing `#[Assert\NotBlank]`. An empty body `{}` or `{"token":""}` passes validation and falls through to a DB lookup that returns 404 instead of the correct 400 validation error. Add `#[Assert\NotBlank]` to fix Scenario 4 and Edge Case 3.
- The idempotent behavior (second call with already-opted-out token returns 200) is correct and user-friendly.
- Rate limiting reuses the `publicBookingLimiter` from the booking endpoint — this is correct behavior but means booking activity can deplete the opt-out request budget. Consider whether a separate limiter is warranted for the opt-out path.
- Frontend opt-out page correctly handles all three states (loading/success/error) and the no-token case. SSR concern from the plan: the API call is made on `onMounted` (client-side), so there is no SSR concern with the loading state.
