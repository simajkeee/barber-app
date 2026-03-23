# Test Plan: UX Batch 7 — Button Hierarchy Standardization

## Overview
Button variants have been standardized across several pages to follow a consistent hierarchy:
primary for main CTAs, secondary for back/cancel actions, danger for destructive actions (with
confirm dialog), and ghost/sm for row-level edit/view actions. Affected pages are:
`pages/dashboard/shop/schedule.vue`, `pages/dashboard/clients/[id]/index.vue`,
`pages/dashboard/appointments/[id]/index.vue`. The change is cosmetic/UX — no behavioral logic
was modified.

## Scope
- **In scope**: Button variant correctness on affected pages (Back buttons are secondary,
  destructive actions are danger-variant, row actions are ghost), confirm dialog presence for
  destructive actions.
- **Out of scope**: Button click behavior and downstream flows (tested elsewhere), pages not
  listed in the spec.

## Prerequisites
- Application running at `BASE_URL`
- Logged in as shop owner
- At least one client, one scheduled appointment, one completed appointment
- A shop with working hours configured

---

## Test Scenarios

### Scenario 1: Appointment detail — Back button is secondary variant
**Actor**: Authenticated shop owner
**Goal**: The "Back" button on the appointment detail page uses the secondary button variant
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{appointment-id}`
2. Wait for page to load
3. Assert a "Back" button is visible
4. Assert the "Back" button does NOT have the primary button class
5. Assert the "Back" button has the secondary button class/style (outlined or gray)

**Expected Result**: Back button is styled as secondary, visually subordinate to the primary action.
**Notes**: Inspect computed classes with `browser_evaluate` if needed. `[VERIFY: secondary class name in UiButton component]`

---

### Scenario 2: Appointment detail — Mark Complete is primary variant
**Actor**: Authenticated shop owner
**Goal**: "Mark Complete" is the primary action button on a scheduled appointment
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{scheduled-appointment-id}`
2. Assert a "Mark Complete" button is visible
3. Assert the "Mark Complete" button has the primary button class/style (filled, prominent)

**Expected Result**: "Mark Complete" is visually the dominant action.

---

### Scenario 3: Appointment detail — Cancel button is danger variant with confirm dialog
**Actor**: Authenticated shop owner
**Goal**: "Cancel Appointment" is styled danger and triggers a confirm dialog before acting
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{scheduled-appointment-id}`
2. Assert a "Cancel" button is visible with danger styling (red)
3. Click the "Cancel" button
4. Assert a confirmation dialog is visible
5. Assert the dialog contains text "Cancel Appointment?" (or equivalent)
6. Click the "Cancel" option in the dialog (to dismiss without acting)
7. Assert the appointment is still in "scheduled" status (no state change)

**Expected Result**: Cancel button shows danger styling and requires confirmation.

---

### Scenario 4: Appointment detail — No Show button is secondary variant
**Actor**: Authenticated shop owner
**Goal**: "No Show" is styled as secondary (not primary, not danger)
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{scheduled-appointment-id}`
2. Assert a "No Show" button is visible
3. Assert the "No Show" button has the secondary button class/style (not red, not primary fill)

**Expected Result**: "No Show" is secondary — present but visually subordinate to "Mark Complete".

---

### Scenario 5: Client detail — Back button is secondary variant
**Actor**: Authenticated shop owner
**Goal**: The "Back" button on the client detail page is secondary
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id}`
2. Wait for page to load
3. Assert a "Back" button is visible
4. Assert the "Back" button has the secondary button class/style (not primary fill)

**Expected Result**: Back button is secondary variant.

---

### Scenario 6: Client detail — Delete button is danger variant
**Actor**: Authenticated shop owner
**Goal**: The "Delete" client button uses the danger variant
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/clients/{client-id}`
2. Assert a "Delete" button is visible
3. Assert the "Delete" button has danger styling (red color class)
4. Click the "Delete" button
5. Assert a confirmation dialog appears before the delete executes

**Expected Result**: Delete is danger-styled and guarded by a confirm dialog.

---

### Scenario 7: Shop schedule page — Back button is secondary variant
**Actor**: Authenticated shop owner
**Goal**: The "Back" button on the shop schedule page is secondary
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/shop/schedule`
2. Wait for page to load
3. Assert a "Back" button is visible
4. Assert the "Back" button has the secondary button class/style

**Expected Result**: Back button is secondary on the schedule page.

---

### Scenario 8: Confirm dialog — confirming a destructive action completes it
**Actor**: Authenticated shop owner
**Goal**: Clicking "Confirm" in a danger action dialog actually performs the action
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments/{scheduled-appointment-id}`
2. Click the "Cancel" button (danger variant)
3. Assert confirmation dialog is visible
4. Click the confirm/proceed button in the dialog
5. Wait for the action to complete
6. Assert the appointment status has changed to "cancelled"
7. Assert a success toast is visible

**Expected Result**: Confirming in the dialog completes the cancellation.

---

## Edge Cases & Negative Tests

### Edge Case 1: Dismissing confirm dialog does not change state
**Scenario**: User clicks Cancel in the confirm dialog — appointment status must not change.
**Steps**:
1. Navigate to a scheduled appointment detail page
2. Click "Cancel" (danger button)
3. When dialog appears, click the "Cancel" / dismiss button in the dialog
4. Assert dialog is closed
5. Assert appointment still shows "scheduled" status
**Expected Result**: No action taken, appointment unchanged.

### Edge Case 2: Multiple destructive actions on same page — each has its own confirm
**Scenario**: Both "Cancel" and "No Show" are destructive-ish; verify each triggers its own appropriate dialog.
**Steps**:
1. Navigate to a scheduled appointment detail page
2. Click "No Show"
3. Assert dialog title contains "No Show" text (not "Cancel" text)
4. Dismiss dialog
5. Click "Cancel"
6. Assert dialog title contains "Cancel Appointment" text
**Expected Result**: Each button shows a context-appropriate confirm dialog.

### Edge Case 3: Ghost/sm row actions on appointment list are visually compact
**Scenario**: Edit/View buttons within list rows use ghost variant at small size.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments` → All Appointments tab
2. Wait for list to load
3. Assert Edit or View button in a row is present
4. Assert the row action button is smaller/lighter than a page-level button
**Expected Result**: Row-level actions use `ghost` variant at `sm` size — visually compact.

---

## Data Requirements
- One scheduled appointment (Scenarios 2, 3, 4, 8, Edge Cases 1, 2)
- One completed appointment (for Scenario 1 — no action buttons, but back button present)
- One client with an existing record (Scenarios 5, 6)
- A shop with working hours configured (Scenario 7)

## Coverage Gaps
- Full visual regression comparison (pixel-level color verification) — not possible with functional tests alone
- Button styling on the services card (`ServiceCard.vue`) — spec mentions it but exact changes not tested here (flag for visual review)
- Mobile viewport button sizing and spacing
