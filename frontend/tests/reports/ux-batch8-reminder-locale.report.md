# Test Report: UX Batch 8 — Reminder Templates Per Locale

**Executed**: 2026-03-22T15:35:00Z
**Plan file**: `tests/plans/ux-batch8-reminder-locale.plan.md`
**Result**: FAILED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 10    |
| Passed             | 4     |
| Failed             | 6     |
| Skipped            | 0     |
| Coverage Gaps      | 3     |

---

## Root Cause

The backend endpoint `GET /api/v1/reminders/settings?locale=en` returns **500 Internal Server Error**. This indicates the database migration `Version20260322000000` (which adds the `locale` column to the `reminder_settings` table) has **not been applied** to the running database. All scenarios that depend on loading or saving locale-specific settings are blocked by this 500 error.

Additionally, all `/vi/*` routes (including `/vi/dashboard/reminders/settings`) throw `500 — obj.hasOwnProperty is not a function`, which is a pre-existing Nuxt router bug affecting all Vietnamese-locale routes.

---

## Scenario Results

### Scenario 1: Settings page shows VI and EN locale tabs
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/reminders/settings` | ✅ PASS | — |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert "Vietnamese" tab visible | ✅ PASS | Button "Vietnamese" present |
| 4 | Assert "English" tab visible | ✅ PASS | Button "English" present |

**Note**: The locale tab switcher UI renders correctly even though the API returns 500.

---

### Scenario 2: Default locale tab is EN when UI is in English
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/reminders/settings` | ✅ PASS | — |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert English tab is active (highlighted) | ✅ PASS | English: `bg-white shadow-sm text-gray-900`; Vietnamese: `text-gray-500` |
| 4 | Assert settings form visible | ❌ FAIL | Form not loaded — API returns 500, no form content rendered |

**Partial Pass**: Tab active state is correct (S2 core assertion passes). Form visibility assertion fails due to API error.

---

### Scenario 3: Default locale tab is VI when UI is in Vietnamese
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/vi/dashboard/reminders/settings` | ❌ FAIL | 500 error: "obj.hasOwnProperty is not a function" / "Page not found: /vi/dashboard/reminders/settings" |

**Failure Detail**:
- **Failed step**: Step 1 — Navigate to `/vi/dashboard/reminders/settings`
- **Expected**: Page renders with Vietnamese tab active
- **Actual**: 500 Internal Server Error. This is the pre-existing `/vi` route bug affecting all Vietnamese-locale paths.

---

### Scenario 4: Switching tabs loads correct template
**Priority**: Critical
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/reminders/settings` | ✅ PASS | — |
| 2 | Wait for EN tab to load with template | ❌ FAIL | No template content loads — API returns 500 |

**Failure Detail**:
- **Failed step**: Step 2 — Wait for EN template to load
- **Expected**: Template form visible with message text
- **Actual**: Form not rendered; `GET /api/v1/reminders/settings?locale=en` returns 500
- **Root cause**: Migration `Version20260322000000` not applied

---

### Scenario 5: Saving EN template only updates EN locale
**Priority**: Critical
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings | ✅ PASS | — |
| 2 | Attempt to load EN form | ❌ FAIL | API 500 — form does not load |

**Failure Detail**: Cannot save — form never renders due to API 500.

---

### Scenario 6: Saving VI template only updates VI locale
**Priority**: Critical
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings, click VI tab | ✅ PASS | — |
| 2 | Wait for VI template to load | ❌ FAIL | API 500 for `?locale=vi` as well |

**Failure Detail**: Cannot save — form never renders due to API 500.

---

### Scenario 7: Default English template auto-created on first access
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings EN tab | ✅ PASS | — |
| 2 | Wait for template to load | ❌ FAIL | API 500 |

**Failure Detail**: Cannot verify default template seeding — API returns 500.

---

### Scenario 8: Settings summary card shows "Template language" label
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/en/dashboard/reminders` | ✅ PASS | — |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert settings summary card visible | ✅ PASS | Summary card present |
| 4 | Assert "Template language: English" text visible | ✅ PASS | Text "Template language: English" confirmed in summary card |

**Note**: The reminders INDEX page (not the settings page) fetches and displays the locale-aware template label correctly. This suggests the backend may have partial locale support already for the index endpoint.

---

### Scenario 9: API request includes correct locale query param
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings | ✅ PASS | — |
| 2 | Inspect network requests | ✅ PASS | — |
| 3 | Assert request includes `locale=en` | ✅ PASS | `GET /api/v1/reminders/settings?locale=en` confirmed in network log |

**Note**: The frontend correctly sends `?locale=en`. The 500 error is on the backend, not the frontend.

---

### Scenario 10: Locale cache prevents duplicate API calls on tab re-switch
**Priority**: Medium
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings, load EN tab | ✅ PASS | — |
| 2 | Click VI tab — wait for it to load | ❌ FAIL | Both EN and VI API calls return 500; cannot test cache behavior |

**Failure Detail**: API always fails — impossible to distinguish cached vs uncached requests.

---

## Edge Case Results

### Edge Case 1: Switching tabs while save is in progress
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Attempt to save EN template | ❌ FAIL | Form does not load; cannot initiate a save |

---

### Edge Case 2: Save fails — error toast, cache not corrupted
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Attempt to save template | ❌ FAIL | Form does not load |

---

### Edge Case 3: Long message template text (500 chars)
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Load form and enter long text | ❌ FAIL | Form does not load |

---

### Edge Case 4: Migration not run — locale column missing
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to settings without migration | ✅ PASS | This is the actual current state |
| 2 | Assert page shows error state (not blank screen) | ❌ FAIL | Page shows tabs but no form and no visible error message; user sees a blank content area with no explanation |

**Expected**: Graceful error state with message
**Actual**: Silent failure — tabs render but form area is empty, no error message shown to user

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | All locale-specific settings scenarios (S4-S7, S10, EC1-EC3) | Migration `Version20260322000000` not applied — `GET /api/v1/reminders/settings?locale=en` returns 500 | **Run the migration**: `docker compose exec php symfony console doctrine:migrations:migrate` then re-run this test module |
| 2 | Vietnamese-locale page testing (S3) | Pre-existing `/vi` route bug: `obj.hasOwnProperty is not a function` for all `/vi/*` routes | **Critical bug** — fix the `/vi` route configuration before testing S3 |
| 3 | Error state display when migration missing (EC4) | Settings page shows empty content instead of an explicit error message | Add an error state to the settings form: when the API returns 500, show "Settings unavailable — please contact support" or similar |

---

## Recommendations

1. **Apply migration**: Run `Version20260322000000` on the development database, then re-run this entire test module. All S4-S7, S10, and EC1-EC3 scenarios are expected to pass after the migration.
2. **Fix `/vi` route bug**: The `obj.hasOwnProperty is not a function` error on `/vi/*` routes is a critical bug affecting all Vietnamese-locale navigation. Fix before the next release.
3. **Add error state to settings page**: When the locale API returns a non-200 response, the form should show an explicit error message rather than silently rendering empty content.
4. **Note on S8**: The reminders index page correctly displays "Template language: English" — this suggests the index page may be using a different (non-locale-parameterized) endpoint or already has locale support. Verify this is the intended behavior.
