# Test Plan: UX Batch 6 — Book Again Shortcut

## Overview
The appointment detail page (`/dashboard/appointments/[id]`) now shows a "Book Again" button
when viewing a completed appointment. Clicking it navigates to the new appointment form
pre-filled with the same client and service, saving the user from having to select them
manually. The button only appears for `status: completed` appointments — not for cancelled,
no-show, or scheduled appointments.

## Scope
- **In scope**: "Book Again" button visibility rules (completed only), pre-fill of clientId and
  serviceId query params in the new appointment URL, navigation to create page.
- **Out of scope**: The appointment creation form itself (tested in appointment scheduling plans),
  the complete/no-show/cancel action buttons (existing functionality).

## Prerequisites
- Application running at `BASE_URL`
- Logged in as shop owner
- At least one completed appointment (for "Book Again" visibility test)
- At least one scheduled, cancelled, and no-show appointment (for negative visibility tests)
- The client and service referenced by the completed appointment still exist

---

## Test Scenarios

### Scenario 1: "Book Again" button is visible on a completed appointment
**Actor**: Authenticated shop owner
**Goal**: The "Book Again" button appears when viewing a completed appointment
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id}`
2. Wait for the page to load
3. Assert a "Book Again" button is visible
4. Assert the button is styled as secondary variant (not primary)

**Expected Result**: "Book Again" button is present and correctly styled.

---

### Scenario 2: "Book Again" navigates to create page with client and service pre-filled
**Actor**: Authenticated shop owner
**Goal**: Clicking "Book Again" opens the create appointment form with the same client and service pre-selected
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id}`
2. Note the client name and service name shown on the detail page
3. Click the "Book Again" button
4. Assert URL is `/en/dashboard/appointments/create?clientId={client-id}&serviceId={service-id}`
5. Assert the client dropdown/field is pre-selected with the correct client name
6. Assert the service dropdown is pre-selected with the correct service name

**Expected Result**: Create form opens with both client and service already selected.

---

### Scenario 3: "Book Again" is NOT shown on a scheduled appointment
**Actor**: Authenticated shop owner
**Goal**: The "Book Again" button does not appear for in-progress/scheduled appointments
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{scheduled-appointment-id}`
2. Wait for the page to load
3. Assert "Book Again" button is NOT visible
4. Assert "Mark Complete", "No Show", and "Cancel" action buttons ARE visible

**Expected Result**: Active appointment shows status-change actions, not "Book Again".

---

### Scenario 4: "Book Again" is NOT shown on a cancelled appointment
**Actor**: Authenticated shop owner
**Goal**: Cancelled appointments do not show "Book Again"
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{cancelled-appointment-id}`
2. Wait for the page to load
3. Assert "Book Again" button is NOT visible
4. Assert no status-change action buttons are visible (terminal status)

**Expected Result**: Cancelled appointment detail page shows neither action buttons nor "Book Again".

---

### Scenario 5: "Book Again" is NOT shown on a no-show appointment
**Actor**: Authenticated shop owner
**Goal**: No-show appointments do not show "Book Again"
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{no-show-appointment-id}`
2. Wait for the page to load
3. Assert "Book Again" button is NOT visible

**Expected Result**: No-show appointment has no "Book Again" button.

---

### Scenario 6: Pre-filled create form can be submitted successfully
**Actor**: Authenticated shop owner
**Goal**: A "Book Again" booking can be completed end-to-end — pick a date, pick a slot, save
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id}`
2. Click "Book Again"
3. Wait for the create form to load with client and service pre-selected
4. Fill in the date field with a future date (e.g., tomorrow)
5. Wait for time slots to load
6. Click the first available time slot
7. Click "Save"
8. Wait for success toast or navigation
9. Assert URL is `/en/dashboard/appointments/{new-id}` (or appointments list)
10. Assert toast contains "created" success message

**Expected Result**: The pre-filled "Book Again" form completes a full appointment creation.

---

## Edge Cases & Negative Tests

### Edge Case 1: Client deleted after the completed appointment
**Scenario**: The client associated with the completed appointment has since been deleted.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id-with-deleted-client}`
2. Click "Book Again"
3. Assert URL includes `clientId={deleted-client-id}` in query params
4. Assert the create form handles the missing client gracefully (client field is empty or shows error)
**Expected Result**: Create form degrades gracefully — no crash. Client field is blank.
**Notes**: `[VERIFY: confirm how the form handles an unknown clientId in query params]`

### Edge Case 2: Service deleted after the completed appointment
**Scenario**: The service used in the completed appointment has since been removed.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id-with-deleted-service}`
2. Click "Book Again"
3. Assert URL includes `serviceId={deleted-service-id}` in query params
4. Assert the create form handles the missing service gracefully (service dropdown is blank)
**Expected Result**: Service dropdown shows no pre-selection rather than crashing.
**Notes**: `[VERIFY: confirm form behavior with an unknown serviceId in query params]`

### Edge Case 3: "Back" button on create page returns to appointment detail
**Scenario**: User clicks "Book Again" then decides to go back.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{completed-appointment-id}`
2. Click "Book Again"
3. On the create form, click "Cancel" or browser back
4. Assert user is back on or near the appointment detail page
**Expected Result**: Cancel navigates away from create form — user can abandon the re-booking.

---

## Data Requirements
- One completed appointment with an existing client and service (Scenarios 1, 2, 6)
- One scheduled appointment (Scenario 3)
- One cancelled appointment (Scenario 4)
- One no-show appointment (Scenario 5)
- A future date with available time slots for the service used (Scenario 6)
- `[OPTIONAL]` One completed appointment whose client or service has since been deleted (Edge Cases 1–2)

## Coverage Gaps
- "Book Again" from the appointments list view (list rows do not have this button — only the detail page)
- Subscription limit reached when trying to re-book via "Book Again" (API error on create)
- Keyboard activation of the "Book Again" button (accessibility)
