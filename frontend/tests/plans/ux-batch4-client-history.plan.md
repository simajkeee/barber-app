# Test Plan: UX Batch 4 — Client Appointment History

## Overview
The client detail page (`/dashboard/clients/[id]`) now includes an "Appointment History"
section below the client info card. It fetches all appointments for the client using the
existing `GET /api/v1/appointments?clientId={id}` endpoint, displays them sorted newest-first,
and handles loading, empty, and error states gracefully. Each appointment row links to the
appointment detail page. No backend changes were required.

## Scope
- **In scope**: Appointment history section rendering, loading/empty/error states, sort order,
  appointment row content (date, service, status badge), navigation from history row to
  appointment detail.
- **Out of scope**: Client info card, edit client flow, delete client flow, appointment actions
  (complete/cancel/etc.).

## Prerequisites
- Application running at `BASE_URL`
- Logged in as shop owner
- At least one client with 2+ appointments in various statuses (scheduled, completed, cancelled)
- At least one client with zero appointments (for empty state test)

---

## Test Scenarios

### Scenario 1: Appointment history section is visible on client detail page
**Actor**: Authenticated shop owner
**Goal**: The "Appointment History" section title is rendered below the client info card
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-appointments}`
2. Wait for page to load
3. Scroll down past the client info card
4. Assert element with text "Appointment History" is visible

**Expected Result**: The history section heading is present.

---

### Scenario 2: Loading skeleton is shown while history is fetching
**Actor**: Authenticated shop owner
**Goal**: Three skeleton rows appear briefly while appointment history loads
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-appointments}`
2. Immediately assert `.animate-pulse` skeleton rows are visible in the history section
3. Wait for all promises to resolve
4. Assert skeleton rows are gone

**Expected Result**: Loading skeleton is shown during fetch, then replaced by real data.
**Notes**: Fast networks may make this hard to catch — use browser network throttling if needed.

---

### Scenario 3: Appointments display with correct data — date, service, status
**Actor**: Authenticated shop owner
**Goal**: Each appointment row shows the time badge, service name, and status badge
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-appointments}`
2. Wait for appointment history to load
3. Assert at least one appointment row is visible
4. Assert the first row contains a date/time element (time badge)
5. Assert the first row contains a service name
6. Assert the first row contains a status badge

**Expected Result**: All three data elements are present on each appointment row.

---

### Scenario 4: Appointments are sorted newest first
**Actor**: Authenticated shop owner
**Goal**: The most recent appointment appears at the top of the list
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-multiple-appointments}`
2. Wait for appointment history to load
3. Note the date of the first appointment row
4. Note the date of the second appointment row
5. Assert the first row's date is more recent (later) than the second row's date

**Expected Result**: History is sorted descending by appointment date (newest first).
**Notes**: `[VERIFY: use browser_evaluate to extract and compare date text from rows if needed]`

---

### Scenario 5: Clicking an appointment row navigates to appointment detail
**Actor**: Authenticated shop owner
**Goal**: Each appointment row is a link to the full appointment detail page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-appointments}`
2. Wait for appointment history to load
3. Click the first appointment row in the history section
4. Assert URL is `/en/dashboard/appointments/{appointment-id}`

**Expected Result**: User is taken to the appointment detail page for that appointment.

---

### Scenario 6: Empty state shown when client has no appointments
**Actor**: Authenticated shop owner
**Goal**: A clear "No appointments yet" message is shown for clients with no history
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-zero-appointments}`
2. Wait for page to load
3. Assert "No appointments yet" text is visible in the history section
4. Assert no appointment rows are visible

**Expected Result**: Empty state message renders; no rows or skeleton.

---

### Scenario 7: Error state shown with Retry button when API fails
**Actor**: Authenticated shop owner
**Goal**: If the appointments fetch fails, an error message and Retry button appear
**Priority**: High

**Steps**:
1. `[SIMULATE: block /api/v1/appointments network request via devtools]`
2. Navigate to `BASE_URL/en/dashboard/clients/{client-id}`
3. Wait for the history section to show an error state
4. Assert an error message is visible in the history section
5. Assert a "Retry" button is visible
6. Restore network connectivity
7. Click "Retry"
8. Wait for appointment history to load successfully

**Expected Result**: Error state renders with retry option; retry fetches and renders data.

---

### Scenario 8: Multiple statuses display correct badge colors/labels
**Actor**: Authenticated shop owner
**Goal**: A client with appointments of different statuses shows the correct status badge for each
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id-with-mixed-status-appointments}`
2. Wait for appointment history to load
3. Assert a row with "Completed" status badge is visible
4. Assert a row with "Scheduled" status badge is visible
5. Assert a row with "Cancelled" status badge is visible (if applicable)

**Expected Result**: Each appointment shows its correct status.

---

## Edge Cases & Negative Tests

### Edge Case 1: Client with a large number of appointments
**Scenario**: A client with many appointments (>20) — list renders without pagination issues.
**Steps**:
1. Navigate to a client detail page for a client with 20+ appointments
2. Wait for history to load
3. Assert at least 20 appointment rows are visible (up to the fetch limit of 50)
**Expected Result**: All fetched appointments render correctly. No crash or blank section.
**Notes**: Fetch limit is 50 — confirm with spec.

### Edge Case 2: Client with only cancelled appointments
**Scenario**: All appointments are in terminal cancelled status — empty-like but has data.
**Steps**:
1. Navigate to a client with only cancelled appointments
2. Wait for history to load
3. Assert appointment rows are shown (not the empty state)
4. Assert each row has a "Cancelled" status badge
**Expected Result**: Cancelled appointments appear in history, not filtered out.

### Edge Case 3: Back button navigates to client list
**Scenario**: The "Back" button on the client detail page returns to the clients list.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id}`
2. Click the "Back" button (secondary variant)
3. Assert URL is `/en/dashboard/clients`
**Expected Result**: Back button works correctly — not affected by the new history section.

---

## Data Requirements
- One client with 3+ appointments in mixed statuses (Scenarios 3, 4, 7, 8)
- One client with exactly zero appointments (Scenario 6)
- One client with 20+ appointments (Edge Case 1)
- One client with only cancelled appointments (Edge Case 2)
- Network simulation capability for Scenario 7 (Edge Case network block)

## Coverage Gaps
- Pagination beyond 50 appointments — API limit means this is not tested
- Real-time updates (if an appointment is created while on the client detail page)
- Mobile layout of the appointment history rows
