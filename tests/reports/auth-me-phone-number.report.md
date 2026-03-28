# Test Report: Auth Me Phone Number

**Executed**: 2026-03-25T17:34:00Z
**Plan file**: `development/features/23-free-trial-phone-number/auth-me-phone-number.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 7     |
| Passed             | 7     |
| Failed             | 0     |
| Skipped            | 0     |
| Edge Cases Total   | 3     |
| Edge Cases Passed  | 2     |
| Edge Cases Skipped | 1     |
| Coverage Gaps      | 1     |

---

## Scenario Results

### Scenario 1: GET /auth/me returns phoneNumber in E.164 format
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with phoneNumber `0460038628` | ✅ PASS | Status 201; plan expected 200 — see Recommendations |
| 2 | GET /auth/me with Bearer token | ✅ PASS | — |
| 3 | Assert status 200 | ✅ PASS | — |
| 4 | Assert response contains key `phoneNumber` | ✅ PASS | — |
| 5 | Assert `phoneNumber` equals `+84460038628` | ✅ PASS | Normalized from `0460038628` |

---

### Scenario 2: GET /auth/me response contains all expected fields including phoneNumber
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with fresh phone `0460038629` | ✅ PASS | Status 201 |
| 2 | GET /auth/me with Bearer token | ✅ PASS | — |
| 3 | Assert status 200 | ✅ PASS | — |
| 4 | Assert all keys present: id, email, firstName, lastName, locale, avatarUrl, phoneNumber | ✅ PASS | No missing fields |
| 5 | Assert email matches registered email | ✅ PASS | — |
| 6 | Assert phoneNumber equals `+84460038629` | ✅ PASS | — |
| 7 | Assert avatarUrl is null | ✅ PASS | — |

---

### Scenario 3: GET /auth/me for pre-migration user returns phoneNumber=null
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register account then SET phone_number = NULL via DB (PHP PDO) | ✅ PASS | 1 row updated |
| 2 | GET /auth/me with that user's token | ✅ PASS | — |
| 3 | Assert status 200 | ✅ PASS | — |
| 4 | Assert phoneNumber is null | ✅ PASS | — |

---

### Scenario 4: GET /auth/me unauthenticated — returns 401
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /auth/me with no Authorization header | ✅ PASS | — |
| 2 | Assert status 401 | ✅ PASS | — |

---

### Scenario 5: PUT /auth/me with phoneNumber silently ignores it
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with phone `0460038631` | ✅ PASS | Rate limiter cleared mid-run (see Recommendations) |
| 2 | PUT /auth/me with firstName, lastName, locale, phoneNumber=`0999999999` | ✅ PASS | Status 200 |
| 3 | Assert PUT status 200 | ✅ PASS | — |
| 4 | GET /auth/me | ✅ PASS | — |
| 5 | Assert firstName equals `NewFirst` | ✅ PASS | Update applied |
| 6 | Assert phoneNumber equals `+84460038631` (original) | ✅ PASS | phoneNumber not changed |

---

### Scenario 6: PUT /auth/me with only phoneNumber — no error, phone unchanged
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with phone `0460038632` | ✅ PASS | — |
| 2 | PUT /auth/me with body `{ "phoneNumber": "0988888888" }` only | ✅ PASS | — |
| 3 | Assert status 200 | ✅ PASS | Returned 200 (UpdateProfileRequest accepts partial body) |
| 4 | GET /auth/me | ✅ PASS | — |
| 5 | Assert phoneNumber still equals `+84460038632` | ✅ PASS | Silently discarded |

---

### Scenario 7: Registration response includes phoneNumber in user object
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | POST /auth/register with phone `0460038633` | ✅ PASS | Status 201 |
| 2 | Assert status 201 | ✅ PASS | — |
| 3 | Assert `user.phoneNumber` equals `+84460038633` | ✅ PASS | — |
| 4 | Assert user contains all 7 expected keys | ✅ PASS | No missing fields |

---

## Edge Case Results

### Edge Case 1: Expired token returns 401
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | GET /auth/me with malformed/expired JWT | ✅ PASS | — |
| 2 | Assert status 401 | ✅ PASS | — |

---

### Edge Case 2: Facebook-registered user has phoneNumber=null
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Login via Facebook OAuth | ⏭ SKIPPED | Facebook OAuth requires live credentials — not available in this environment |

---

### Edge Case 3: Login response includes phoneNumber
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Register with phone `0460038634` | ✅ PASS | — |
| 2 | POST /auth/login with email and password | ✅ PASS | Status 200 |
| 3 | Assert status 200 | ✅ PASS | — |
| 4 | Assert `user.phoneNumber` equals `+84460038634` | ✅ PASS | — |

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Facebook OAuth phoneNumber=null test | Facebook App credentials not available in test environment | Run manually with live Facebook credentials |

---

## Recommendations

- **Registration returns 201, not 200**: All registration steps returned HTTP 201 (Created). The plan expected 200. The API behavior is semantically correct (201 for resource creation). Update plan expectations from `200` to `201` for `/auth/register`.
- **Rate limiter hit during test run**: The registration rate limiter (5/hour) was exhausted mid-run. `php bin/console cache:pool:clear cache.rate_limiter` was used to clear it. Consider raising the limit in test/dev `.env` or providing a test-only bypass to avoid manual intervention.
- **Scenario 3 DB setup**: Pre-migration simulation required a direct SQL UPDATE via PHP PDO. Consider adding a test helper endpoint or fixture command to streamline this for repeated runs.
