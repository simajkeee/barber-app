# Test Plan: Smart Reminders

## Overview
Covers the reminder workflow: viewing today's list of clients who are overdue for a visit,
copying the pre-filled reminder message, marking clients as reminded, and configuring the
reminder threshold and message template. This is a read-heavy daily-use feature.
Actors: authenticated shop owner.

## Scope
- **In scope**: View today's reminders list, mark client as reminded, view reminder settings,
  update reminder threshold and message template.
- **Out of scope**: Automated Zalo/SMS sending (post-MVP — only manual copy-to-clipboard in MVP),
  reminder history tracking.

## Prerequisites
- Application running at `BASE_URL`
- Logged-in user with an existing shop
- At least 2 clients with `lastVisitAt` set more than 30 days ago (to appear on the reminder list)
- At least 1 client with `lastVisitAt` within the last 30 days (should NOT appear)
- At least 1 client with `lastRemindedAt` set within the last 7 days (should NOT appear — in cooldown)

## Test Scenarios

### Scenario 1: View today's reminder list
**Actor**: Authenticated shop owner
**Goal**: See the list of clients who haven't visited in the configured number of days
**Priority**: Critical

**Steps**:
1. Log in and navigate to `/dashboard/reminders`
2. Assert the reminders page is visible
3. Assert a list of reminder candidates is displayed
4. Assert each item shows: client name, phone number, days since last visit, and the pre-filled reminder message
5. Assert clients who visited within the threshold are NOT shown
6. Assert clients in the 7-day cooldown are NOT shown

**Expected Result**: GET `/api/v1/reminders/today` returns the correct filtered list.
Placeholders in the message template are resolved (e.g., `{client_name}` replaced with
the actual client name).

---

### Scenario 2: View reminder list — empty state
**Actor**: Authenticated shop owner
**Goal**: See a meaningful empty state when no clients need reminding today
**Priority**: High

**Steps**:
1. Log in as a user whose shop has no qualifying reminder candidates (all clients visited recently, or no clients at all)
2. Navigate to `/dashboard/reminders`
3. Assert the page renders without error
4. Assert an empty state message is displayed (e.g., "No clients to remind today")

**Expected Result**: Empty state handled gracefully. No crash or missing UI elements.

---

### Scenario 3: Reminder message content
**Actor**: Authenticated shop owner
**Goal**: Verify that the message shown contains the client's actual name and correct day count
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/reminders`
2. Identify a client reminder entry with a known `daysSinceVisit` value
3. Assert the message text contains the client's first and last name
4. Assert the message text contains the number of days since the last visit
5. Assert the message text contains the shop name

**Expected Result**: All placeholders (`{client_name}`, `{days_since_visit}`, `{shop_name}`)
are resolved in the `message` field returned by the API.

---

### Scenario 4: Copy reminder message to clipboard
**Actor**: Authenticated shop owner
**Goal**: Copy the pre-filled reminder message with one click
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/reminders`
2. Find the "Copy" button next to a reminder entry
3. Click the "Copy" button
4. Assert a success toast or visual feedback is shown (e.g., "Copied!")

**Expected Result**: Clipboard API triggered. Feedback confirms the copy action.
**Notes**: Browser clipboard permission may be required in Playwright — use `context.grantPermissions(['clipboard-read', 'clipboard-write'])` if needed. [VERIFY: confirm button exists with a copy/clipboard action]

---

### Scenario 5: Mark client as reminded
**Actor**: Authenticated shop owner
**Goal**: Mark a client as reminded so they leave the list for 7 days
**Priority**: Critical

**Steps**:
1. Navigate to `/dashboard/reminders`
2. Note the total count of clients shown
3. Find a client entry and click "Mark as reminded" or equivalent button
4. Wait for response
5. Assert success feedback is shown
6. Assert the marked client is removed from the visible list
7. Assert the list count has decreased by 1

**Expected Result**: POST `/api/v1/reminders/{clientId}/mark-reminded` called. Client
`lastRemindedAt` updated to now. Client disappears from today's list (7-day cooldown begins).

---

### Scenario 6: View reminder settings
**Actor**: Authenticated shop owner
**Goal**: See the current reminder configuration for the shop
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/reminders/settings`
2. Assert the settings form is visible
3. Assert "Days since last visit" field shows a value (default: 30)
4. Assert the "Message template" textarea shows the default template text

**Expected Result**: Settings loaded from GET `/api/v1/reminders/settings`. Default values
visible for a shop without custom settings.

---

### Scenario 7: Update reminder threshold
**Actor**: Authenticated shop owner
**Goal**: Change the reminder threshold from 30 days to 14 days
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/reminders/settings`
2. Clear the "Days since last visit" field and fill with `14`
3. Click save
4. Wait for response
5. Assert success feedback is shown
6. Navigate to `/dashboard/reminders`
7. Assert that clients who visited 15–29 days ago now appear in the list

**Expected Result**: Settings updated via PUT `/api/v1/reminders/settings`. The reminder
list immediately reflects the new threshold.

---

### Scenario 8: Update message template
**Actor**: Authenticated shop owner
**Goal**: Customize the reminder message template
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/reminders/settings`
2. Clear the "Message template" textarea
3. Fill with `Hi {client_name}, it has been {days_since_visit} days. Book again at {shop_name}!`
4. Click save
5. Wait for response
6. Assert success feedback
7. Navigate to `/dashboard/reminders`
8. Assert that at least one reminder message now uses the new template format (contains "Hi" instead of "Chào")

**Expected Result**: Template saved and applied to all future reminder messages. Server-side
placeholder resolution uses the new template.

---

### Scenario 9: Invalid reminder settings
**Actor**: Authenticated shop owner
**Goal**: See validation errors for out-of-range settings values
**Priority**: Medium

**Steps**:
1. Navigate to `/dashboard/reminders/settings`
2. Clear "Days since last visit" and fill with `0`
3. Click save
4. Assert a validation error is displayed (minimum is 1)
5. Clear the field and fill with `366`
6. Click save
7. Assert a validation error is displayed (maximum is 365)

**Expected Result**: HTTP 400 `VALIDATION_ERROR`. Field-level errors shown. Settings not saved.

## Edge Cases & Negative Tests

### Edge Case 1: Client at exactly the threshold boundary
**Scenario**: Client's `lastVisitAt` is exactly `daysSinceLastVisit` days ago
**Steps**:
1. Set reminder threshold to 30 days
2. Ensure a client has `lastVisitAt` exactly 30 days ago
3. Navigate to `/dashboard/reminders`
**Expected Result**: The client appears in today's list (boundary is inclusive — `<=` condition).

### Edge Case 2: Client reminded exactly 7 days ago
**Scenario**: Client's `lastRemindedAt` is exactly 7 days ago — cooldown should have expired
**Steps**:
1. Ensure a client has `lastRemindedAt` set to exactly 7 days ago
2. Navigate to `/dashboard/reminders`
**Expected Result**: Client reappears in the list (cooldown condition is `<= today - 7 days`).

### Edge Case 3: Message template with no placeholders
**Scenario**: A template with only static text — no `{client_name}` etc.
**Steps**:
1. Set template to `Reminder: please visit us again soon!`
2. Save and navigate to `/dashboard/reminders`
**Expected Result**: Messages show the static text without errors. No placeholder tokens visible.

## Data Requirements
- At least 2 clients with `lastVisitAt` > 30 days ago (qualifying candidates)
- At least 1 client with `lastVisitAt` < 30 days ago (should not appear)
- At least 1 client with `lastRemindedAt` < 7 days ago (in cooldown, should not appear)
- Shop must have a name (used in the default message template)

## Coverage Gaps
- Automated Zalo/SMS sending — post-MVP, not implemented
- Reminder history log — not persisted in MVP (only `lastRemindedAt` on client)
- Concurrent mark-reminded calls — idempotent, but not testable with single Playwright session
- Pagination of the reminder list (if > 50 clients qualify)
