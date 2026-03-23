# Test Plan: UX Batch 8 — Reminder Templates Per Locale

## Overview
Reminder templates are now locale-aware. The backend stores a separate template per
`(shop, locale)` pair — defaulting to Vietnamese for all new shops and seeding an English
template on first access. The frontend `Reminders Settings` page shows a locale-tab switcher
(VI / EN) to edit each locale's template independently, and the reminder summary card shows
"Template language: Vietnamese / English" so the user knows which locale they're viewing.
This batch required both backend (new `locale` column, migration, updated API) and frontend
(locale-tab UI) changes.

## Scope
- **In scope**: Locale tab switching on the settings page, correct template loading per locale,
  saving a template for a specific locale, locale label in the settings summary card, API
  `?locale=` query param behavior, default templates on first access.
- **Out of scope**: Reminder sending logic, client reminder list, `Mark Reminded` action,
  Zalo/SMS integrations.

## Prerequisites
- Application running at `BASE_URL`
- Database migration `Version20260322000000` has been run
- Logged in as shop owner
- A shop with no previously saved reminder settings (for default template tests) — `[VERIFY: may need a fresh test shop]`

---

## Test Scenarios

### Scenario 1: Reminder settings page shows VI and EN locale tabs
**Actor**: Authenticated shop owner
**Goal**: The settings page displays two tabs — Vietnamese (VI) and English (EN)
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Wait for page to load
3. Assert a tab or button with "Vietnamese" or "VI" label is visible
4. Assert a tab or button with "English" or "EN" label is visible

**Expected Result**: Both locale tabs are present.

---

### Scenario 2: Default locale tab matches the user's current UI language
**Actor**: Authenticated shop owner using English UI
**Goal**: When the UI is in English, the EN locale tab is active by default
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Wait for page to load
3. Assert the "English" / "EN" tab is the currently active tab (highlighted/selected state)
4. Assert the settings form is loaded and visible

**Expected Result**: Active tab defaults to EN when app is in English locale.

---

### Scenario 3: Default locale tab is VI when UI is in Vietnamese
**Actor**: Authenticated shop owner using Vietnamese UI
**Goal**: When the UI is in Vietnamese, the VI tab is active by default
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/vi/dashboard/reminders/settings`
2. Wait for page to load
3. Assert the "Vietnamese" / "VI" tab is the currently active tab
4. Assert the settings form is loaded and visible

**Expected Result**: Active tab defaults to VI when app is in Vietnamese locale.

---

### Scenario 4: Switching locale tabs loads the correct template
**Actor**: Authenticated shop owner
**Goal**: Clicking the EN tab loads the English template; clicking VI loads the Vietnamese template
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Wait for the EN tab to load with its template text
3. Note the message template text shown for EN
4. Click the "Vietnamese" / "VI" tab
5. Wait for the VI template to load
6. Assert the template text has changed (different from EN template, or in Vietnamese)
7. Click back to "English" / "EN" tab
8. Assert the EN template text is restored (cached — no additional API call if not changed)

**Expected Result**: Each tab shows the correct locale-specific template.

---

### Scenario 5: Saving EN template only updates EN locale
**Actor**: Authenticated shop owner
**Goal**: Editing and saving the English template does not affect the Vietnamese template
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Ensure the EN tab is active
3. Note the current VI template text (switch to VI tab, read, switch back to EN)
4. Edit the days-since-last-visit field to "21"
5. Edit the message template field with an English-specific message
6. Click "Save"
7. Assert a success toast appears with "Settings saved" message
8. Navigate back to settings
9. Click the VI tab
10. Assert the VI template text is unchanged (same as noted in step 3)

**Expected Result**: EN template is saved; VI template is unaffected.

---

### Scenario 6: Saving VI template only updates VI locale
**Actor**: Authenticated shop owner
**Goal**: Editing and saving the Vietnamese template does not affect the English template
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Click the "Vietnamese" / "VI" tab
3. Note the current EN template text (switch to EN tab, read, switch back to VI)
4. Edit the message template with a Vietnamese-specific message
5. Click "Save"
6. Assert success toast appears
7. Navigate back to settings
8. Click the EN tab
9. Assert the EN template is unchanged

**Expected Result**: VI template is saved; EN template is unaffected.

---

### Scenario 7: Default English template is auto-created on first EN tab access
**Actor**: Authenticated shop owner on a fresh shop
**Goal**: Accessing the EN tab for the first time shows a pre-populated default English template
**Priority**: High

**Steps**:
1. `[VERIFY: use a shop that has never had settings saved — may need a fresh test shop]`
2. Navigate to `BASE_URL/en/dashboard/reminders/settings`
3. Click the "English" / "EN" tab
4. Wait for template to load
5. Assert the message template field is NOT empty
6. Assert the template contains English text (not Vietnamese)

**Expected Result**: Default English template is seeded on first access — no blank template.

---

### Scenario 8: Settings summary card shows "Template language" label
**Actor**: Authenticated shop owner
**Goal**: The reminder settings summary card on the reminders index page shows which locale the template is for
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders`
2. Wait for page to load
3. Assert the settings summary card is visible
4. Assert it contains text indicating the template locale (e.g., "Template language: English" or "Vietnamese")

**Expected Result**: Locale label is visible on the summary card.

---

### Scenario 9: API request includes correct locale query param
**Actor**: Automated verification
**Goal**: When the EN tab is active, the GET request for settings includes `?locale=en`
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Open browser network monitor
3. Wait for the initial settings request to complete
4. Assert the request to `/api/v1/reminders/settings` includes `locale=en` query parameter
5. Click the VI tab
6. Assert a new request to `/api/v1/reminders/settings` includes `locale=vi` query parameter

**Expected Result**: Correct `?locale=` param is sent for each tab.
**Notes**: Use `browser_network_requests` to inspect requests.

---

### Scenario 10: Locale cache prevents duplicate API calls on tab re-switch
**Actor**: Automated verification
**Goal**: Switching back to a previously loaded tab does not make another API request
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/reminders/settings`
2. Wait for EN tab to load (note: 1 request made)
3. Click the VI tab — wait for it to load (1 more request — total 2)
4. Click the EN tab again
5. Assert NO new request to `/api/v1/reminders/settings?locale=en` is made (cache hit)

**Expected Result**: Second visit to EN tab uses cached data — no redundant API call.
**Notes**: Use `browser_network_requests` to count requests.

---

## Edge Cases & Negative Tests

### Edge Case 1: Switching tabs while a save is in progress
**Scenario**: User clicks Save on EN tab, then quickly clicks VI tab.
**Steps**:
1. Navigate to settings, ensure EN tab is active
2. Edit a field
3. Click Save (do not wait for completion)
4. Immediately click the VI tab
5. Assert the save completes (or fails gracefully) without errors
**Expected Result**: No race condition — save completes for EN, VI loads correctly.

### Edge Case 2: Save fails — error toast shown, locale cache not corrupted
**Scenario**: The save API call fails (network error).
**Steps**:
1. Navigate to settings
2. `[SIMULATE: block PUT /api/v1/reminders/settings]`
3. Edit the template and click Save
4. Assert error toast is shown
5. Assert the form is still visible with the unsaved changes
6. Restore network connectivity, click Save again
7. Assert success toast appears
**Expected Result**: Failed save shows error toast; retry works correctly.

### Edge Case 3: Long message template text
**Scenario**: User saves a very long message template.
**Steps**:
1. Navigate to settings (EN tab)
2. Clear the message template field
3. Fill message template with 500 characters of text
4. Click Save
5. Assert success or appropriate validation error
**Expected Result**: Either saves successfully or shows a max-length validation error.

### Edge Case 4: Migration not run — locale column missing
**Scenario**: If the database migration has not been run, the backend should not crash — it should surface an appropriate error.
**Steps**:
1. `[REQUIRES: rollback migration on test environment]`
2. Navigate to settings
3. Assert page shows an error state (not a 500 blank screen)
**Expected Result**: Graceful error if migration is missing. `[NOTE: migration MUST be run in production before this feature is deployed]`

---

## Data Requirements
- Database migration `Version20260322000000` applied (adds `locale` column to `reminder_settings`)
- A fresh shop with no reminder settings saved (Scenario 7)
- A shop with existing settings saved for both locales (Scenarios 5, 6)
- One shop with at least one client past the reminder threshold (for settings summary card, Scenario 8)

## Coverage Gaps
- The reminder cards on `/dashboard/reminders` (index) — verifying they use the user's locale template for message copy
- Edge case where user's `UserLocale` preference differs from the tab they save (should be independent)
- Concurrent updates from two browser tabs saving different locales simultaneously
