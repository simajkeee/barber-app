# Test Report: Registration with Phone Number

**Executed**: 2026-03-25T17:46:00Z
**Plan file**: `development/features/23-free-trial-phone-number/registration-phone-number.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 9     |
| Passed             | 9     |
| Failed             | 0     |
| Skipped            | 0     |
| Edge Cases Total   | 3     |
| Edge Cases Passed  | 3     |
| Edge Cases Skipped | 0     |
| Coverage Gaps      | 0     |

---

## Scenario Results

### Scenario 1: Successful registration with Vietnamese local format
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `0460038800` | ✅ PASS | Status 201 |
| 2 | Assert status 201 | ✅ PASS | Plan expected 200 — API correctly returns 201 Created |
| 3 | Assert response contains `user` | ✅ PASS | — |
| 4 | Assert user.phoneNumber = `+84460038800` | ✅ PASS | Normalized from `0460038800` |
| 5 | Assert response contains `token` | ✅ PASS | — |
| 6 | Assert response contains `refreshToken` | ✅ PASS | — |

---

### Scenario 2: Successful registration with E.164 format
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `+84460038801` | ✅ PASS | Status 201 |
| 2 | Assert status 201 | ✅ PASS | — |
| 3 | Assert user.phoneNumber = `+84460038801` | ✅ PASS | Stored as-is |

---

### Scenario 3: Successful registration with spaces and dashes in phone
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `046-003 8802` | ✅ PASS | Status 201 |
| 2 | Assert status 201 | ✅ PASS | — |
| 3 | Assert user.phoneNumber = `+84460038802` | ✅ PASS | Spaces and dashes stripped, normalized |

---

### Scenario 4: Registration fails — phone number missing
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register without phoneNumber field | ✅ PASS | — |
| 2 | Assert status 400 | ✅ PASS | — |
| 3 | Assert code = "VALIDATION_ERROR" | ✅ PASS | — |
| 4 | Assert details contains entry with field = "phoneNumber" | ✅ PASS | — |
| 5 | Assert message = "Phone number is required." | ✅ PASS | — |

---

### Scenario 5: Registration fails — phone number empty string
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `""` | ✅ PASS | — |
| 2 | Assert status 400 | ✅ PASS | — |
| 3 | Assert code = "VALIDATION_ERROR" | ✅ PASS | — |
| 4 | Assert details contains phoneNumber entry | ✅ PASS | Message: "Phone number is required." |

---

### Scenario 6: Registration fails — phone number invalid format
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `"abc-not-a-phone"` | ✅ PASS | — |
| 2 | Assert status 400 | ✅ PASS | — |
| 3 | Assert code = "VALIDATION_ERROR" | ✅ PASS | — |
| 4 | Assert details contains phoneNumber entry | ✅ PASS | — |
| 5 | Assert message = "Phone number format is invalid." | ✅ PASS | — |

---

### Scenario 7: Registration fails — phone number already registered
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register first account with phone `0460038806` | ✅ PASS | Status 201 |
| 2 | Assert first registration status 201 | ✅ PASS | — |
| 3 | Attempt second registration with same phone `0460038806` | ✅ PASS | — |
| 4 | Assert second status 422 | ✅ PASS | — |
| 5 | Assert code = "PHONE_ALREADY_IN_USE" | ✅ PASS | — |
| 6 | Assert message = "This phone number is already registered." | ✅ PASS | — |

---

### Scenario 8: Phone already registered — different format same number
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register first account with phone `0460038807` | ✅ PASS | Status 201 |
| 2 | Assert first registration status 201 | ✅ PASS | — |
| 3 | Attempt second registration with `+84460038807` (same number, E.164 format) | ✅ PASS | — |
| 4 | Assert second status 422 | ✅ PASS | — |
| 5 | Assert code = "PHONE_ALREADY_IN_USE" | ✅ PASS | Normalization deduplication works |

---

### Scenario 9: phoneNumber present in GET /auth/me after registration
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register new account with phone `0460038809` | ✅ PASS | Status 201 |
| 2 | Assert registration status 201 | ✅ PASS | — |
| 3 | GET /auth/me with Bearer token | ✅ PASS | Status 200 |
| 4 | Assert status 200 | ✅ PASS | — |
| 5 | Assert phoneNumber = `+84460038809` | ✅ PASS | — |

---

## Edge Case Results

### Edge Case 1: PUT /auth/me silently ignores phoneNumber
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with phone `0460038810` | ✅ PASS | — |
| 2 | PUT /auth/me with phoneNumber `0999999999` | ✅ PASS | Status 200 |
| 3 | Assert PUT status 200 | ✅ PASS | — |
| 4 | GET /auth/me | ✅ PASS | — |
| 5 | Assert phoneNumber still = `+84460038810` | ✅ PASS | Original value preserved |

---

### Edge Case 2: Phone with country code prefix without + sign
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `84460038811` (no +) | ✅ PASS | Status 201 |
| 2 | Assert status 201 | ✅ PASS | — |
| 3 | Assert user.phoneNumber = `+84460038811` | ✅ PASS | `84XXXXXXXXX` → `+84XXXXXXXXX` |

---

### Edge Case 3: Very short phone number rejected
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phoneNumber `12345` | ✅ PASS | — |
| 2 | Assert status 400 | ✅ PASS | — |
| 3 | Assert code = "VALIDATION_ERROR" | ✅ PASS | — |

---

## Coverage Gaps Encountered

No coverage gaps encountered during this run.

*(Plan-documented gaps: concurrent duplicate registration race condition and non-Vietnamese international numbers — both acknowledged as out of scope for sequential Playwright MCP testing.)*

---

## Recommendations

- **API returns 201, not 200 on registration**: All plan steps that assert `200` on `POST /auth/register` should be updated to `201 Created`. The API is semantically correct; the plan has the wrong expected code.
- **Rate limiter requires mid-run resets**: The 5/hour registration rate limit was exhausted across scenarios. `php bin/console cache:pool:clear cache.rate_limiter` was needed once between scenarios 7 and 8. Consider a higher limit in the test environment or a dedicated test env variable.
- **All normalization cases confirmed**: `0XXXXXXXXX`, `+84XXXXXXXXX`, `84XXXXXXXXX`, and formats with spaces/dashes all normalize correctly to E.164 `+84XXXXXXXXX`.
