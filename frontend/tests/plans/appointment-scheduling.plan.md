# Test Plan: Appointment Scheduling

## Overview
The core booking engine. Covers creating appointments (with overlap/working-hours
validation), viewing the daily schedule, editing and rescheduling, status lifecycle
(scheduled → completed / cancelled / no_show), the available-slots endpoint, and
revenue reporting. This is the highest-complexity module and the primary daily workflow
for shop owners. Actors: authenticated shop owner.

## Scope
- **In scope**: Create appointment, view daily schedule, list appointments with filters,
  view appointment detail, edit/reschedule appointment, change appointment status (complete,
  cancel, no-show), soft delete via DELETE, get available slots, revenue summary.
- **Out of scope**: Public booking (covered in `public-booking.plan.md`), subscription
  limit enforcement (covered in `subscription.plan.md`), client visit-stat side effects
  (covered in `client-management.plan.md`).

## Prerequisites
- Application running at `BASE_URL`
- Logged-in user with an existing shop
- Shop has at least 1 active service with a known duration
- Shop has at least 1 client
- Shop working hours are configured for the test date's day of the week
- At least 1 existing `SCHEDULED` appointment (for edit/status change tests)

## Test Scenarios

### Scenario 1: View daily schedule (empty day)
**Actor**: Authenticated shop owner
**Goal**: View the schedule for a day with no appointments
**Priority**: Critical

**Steps**:
1. Log in and navigate to `/dashboard/appointments`
2. Select or navigate to a date with no appointments
3. Assert the daily schedule view is rendered
4. Assert working hours are displayed (open and close times)
5. Assert an empty state message or empty time grid is shown

**Expected Result**: GET `/api/v1/appointments/daily?date={date}` returns an empty
`appointments` array with correct `workingHours`.

---

### Scenario 2: Create a new appointment
**Actor**: Authenticated shop owner
**Goal**: Book an appointment for a client at an available time slot
**Priority**: Critical

**Steps**:
1. Log in and navigate to `/dashboard/appointments/create`
2. Assert the create-appointment form is visible
3. Select a client from the client dropdown/search
4. Select an active service
5. Select a future date that the shop is open
6. Select an available time slot (e.g., from the available-slots list)
7. Click save/create
8. Wait for navigation or response
9. Assert success feedback is shown
10. Assert the new appointment appears on the daily schedule for the selected date

**Expected Result**: Appointment created via POST `/api/v1/appointments`. `endTime`
calculated from service duration. Appointment appears on daily schedule.

---

### Scenario 3: Create appointment with time conflict
**Actor**: Authenticated shop owner
**Goal**: See an error when booking a slot that overlaps an existing appointment
**Priority**: Critical

**Steps**:
1. Note the start time of an existing `SCHEDULED` appointment
2. Navigate to `/dashboard/appointments/create`
3. Select the same client (or any client)
4. Select the same or a longer service
5. Select the same date and start time as the existing appointment
6. Click save
7. Wait for response

**Expected Result**: Error message displayed indicating a time conflict (HTTP 409
`APPOINTMENT_OVERLAP`). No appointment created.

---

### Scenario 4: Create appointment outside working hours
**Actor**: Authenticated shop owner
**Goal**: See an error when booking outside the shop's configured hours
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/appointments/create`
2. Select a client and service
3. Select a date the shop is open
4. Manually enter or select a time before the shop's open time (e.g., 07:00 when opens at 08:00)
5. Click save
6. Wait for response

**Expected Result**: Error displayed (HTTP 422 `OUTSIDE_WORKING_HOURS`). No appointment created.

---

### Scenario 5: Create appointment on a closed day
**Actor**: Authenticated shop owner
**Goal**: See an error when booking on a day the shop is marked as closed
**Priority**: High

**Steps**:
1. Identify a day of the week marked as closed in the shop's schedule (e.g., Sunday)
2. Navigate to `/dashboard/appointments/create`
3. Select the closed day's next occurrence as the date
4. Select any client, service, and time
5. Click save
6. Wait for response

**Expected Result**: Error displayed (HTTP 422 `SHOP_CLOSED`). No appointment created.

---

### Scenario 6: View appointment detail
**Actor**: Authenticated shop owner
**Goal**: See full details of a single appointment
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/appointments`
2. Click on an existing appointment
3. Assert URL changes to `/dashboard/appointments/{id}`
4. Assert client name is displayed
5. Assert service name and duration are displayed
6. Assert start and end times are displayed
7. Assert current status is displayed

**Expected Result**: Appointment detail rendered from GET `/api/v1/appointments/{id}`.

---

### Scenario 7: Edit an appointment — reschedule
**Actor**: Authenticated shop owner
**Goal**: Move an appointment to a different time slot
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/appointments/{id}/edit` for a `SCHEDULED` appointment
2. Assert fields are pre-filled with current values
3. Change the start time to a different available slot on the same day
4. Click save
5. Wait for response
6. Assert success feedback
7. Navigate back to the daily schedule
8. Assert the appointment appears at the new time

**Expected Result**: Appointment rescheduled via PUT `/api/v1/appointments/{id}`.
`endTime` recalculated. No false overlap triggered (excludes itself).

---

### Scenario 8: Attempt to edit a completed appointment
**Actor**: Authenticated shop owner
**Goal**: Confirm that terminal-status appointments cannot be edited
**Priority**: High

**Steps**:
1. Navigate to the detail or edit page of a `COMPLETED` appointment
2. Assert either: the edit form is hidden/disabled, or an error is shown on submit

**Expected Result**: Edit is blocked. HTTP 403 `APPOINTMENT_NOT_MODIFIABLE` if submitted.
UI preferably shows a read-only state or hides the edit controls.

---

### Scenario 9: Mark appointment as completed
**Actor**: Authenticated shop owner
**Goal**: Change an appointment's status from SCHEDULED to COMPLETED
**Priority**: Critical

**Steps**:
1. Navigate to `/dashboard/appointments/{id}` for a `SCHEDULED` appointment
2. Find the status change control (dropdown, button, or action menu)
3. Select "Completed" / "Hoàn thành"
4. Confirm the action if a confirmation dialog appears
5. Wait for response
6. Assert the appointment status shows "Completed" / "Hoàn thành"
7. Navigate to the client's detail page
8. Assert the client's visit count has incremented by 1

**Expected Result**: Status updated via PATCH `/api/v1/appointments/{id}/status`.
`AppointmentCompleted` event updates `client.visitCount` and `client.lastVisitAt`.

---

### Scenario 10: Cancel an appointment
**Actor**: Authenticated shop owner
**Goal**: Cancel a scheduled appointment, freeing the time slot
**Priority**: Critical

**Steps**:
1. Navigate to `/dashboard/appointments/{id}` for a `SCHEDULED` appointment
2. Click "Cancel" or select "Cancelled" from the status control
3. Confirm if prompted
4. Wait for response
5. Assert status shows "Cancelled"
6. Navigate to the daily schedule for that appointment's date
7. Assert the previously occupied time slot is now free (try booking it)

**Expected Result**: Status set to CANCELLED via PATCH or DELETE. Slot freed for new bookings.

---

### Scenario 11: Mark appointment as no-show
**Actor**: Authenticated shop owner
**Goal**: Record that a client did not show up for their appointment
**Priority**: Medium

**Steps**:
1. Navigate to `/dashboard/appointments/{id}` for a `SCHEDULED` appointment
2. Select "No show" from the status control
3. Confirm if prompted
4. Assert status shows "No show" / "Không đến"

**Expected Result**: Status updated to `no_show`. No visit stats updated (only `COMPLETED` triggers visit update).

---

### Scenario 12: Invalid status transition (COMPLETED → CANCELLED)
**Actor**: Authenticated shop owner
**Goal**: Confirm that already-completed appointments cannot be cancelled
**Priority**: High

**Steps**:
1. Navigate to a `COMPLETED` appointment's detail page
2. Assert that no status-change controls are available, or
3. If a status dropdown exists and shows "Cancelled" as an option, attempt to select it and save
4. Wait for response

**Expected Result**: Either the UI prevents the action entirely, or HTTP 403
`INVALID_STATUS_TRANSITION` is returned and an error is displayed.

---

### Scenario 13: View available slots
**Actor**: Authenticated shop owner
**Goal**: See the list of available booking slots for a specific date and service
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/appointments/create`
2. Select a service
3. Select a date the shop is open
4. Assert a list of time slots is rendered
5. Assert that times occupied by existing appointments are marked as unavailable or absent
6. Assert that past times (for today's date) are excluded

**Expected Result**: Available slots loaded from GET `/api/v1/appointments/available-slots`.
Slots reflect current bookings for the shop.

---

### Scenario 14: Revenue summary
**Actor**: Authenticated shop owner
**Goal**: View the total revenue for the current month
**Priority**: Medium

**Steps**:
1. Log in and navigate to `/dashboard` or revenue section [VERIFY: confirm exact route — may be part of the dashboard home or a dedicated `/dashboard/appointments` view with revenue widget]
2. Assert a revenue total is displayed for the current month
3. Assert the revenue figure only counts completed appointments
4. Assert a daily breakdown or chart is visible

**Expected Result**: Revenue data loaded from GET `/api/v1/appointments/revenue`. Only
`COMPLETED` appointment prices are summed.

## Edge Cases & Negative Tests

### Edge Case 1: Create appointment with unaligned start time (e.g., 09:15)
**Scenario**: User manually enters a time not on a 30-minute boundary
**Steps**:
1. Navigate to create appointment form
2. Manually enter `09:15` as start time (if input allows free text)
3. Submit
**Expected Result**: HTTP 400 `INVALID_SLOT_ALIGNMENT`. Error displayed.

### Edge Case 2: Reschedule to a slot that the appointment itself occupies
**Scenario**: Moving an appointment to an overlapping but not conflicting time
**Steps**:
1. Edit a `SCHEDULED` appointment
2. Change only the notes field, leave start time unchanged
3. Submit
**Expected Result**: HTTP 200. No false overlap triggered (overlap check excludes self).

### Edge Case 3: Appointment spanning past shop close time
**Scenario**: Service duration would push the end time past closing hours
**Steps**:
1. Select a service with 60-minute duration
2. Select a start time that is 30 minutes before close (e.g., 19:30 if shop closes at 20:00)
3. Submit
**Expected Result**: HTTP 422 `OUTSIDE_WORKING_HOURS`. End time 20:30 > close time 20:00.

### Edge Case 4: Revenue query with date range > 90 days
**Scenario**: Request revenue for a range exceeding the 90-day maximum
**Steps**:
1. Navigate to the revenue view
2. Set date range to more than 90 days
3. Submit or trigger the request
**Expected Result**: HTTP 400. Error displayed indicating the date range is too wide.

## Data Requirements
- Shop with working hours configured (e.g., Mon–Sat 08:00–20:00, Sun closed)
- At least 1 active service with known duration
- At least 1 client
- At least 1 `SCHEDULED` appointment on a future date
- At least 1 `COMPLETED` appointment (for revenue and status transition tests)
- A "closed" day configured in the shop schedule (for closed-day test)

## Coverage Gaps
- Concurrent double-booking (requires parallel requests beyond Playwright single-session)
- Revenue daily breakdown chart rendering (visual assertion — use screenshot comparison)
- Subscription limit enforcement on appointment creation — covered in `subscription.plan.md`
