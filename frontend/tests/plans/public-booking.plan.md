# Test Plan: Public Booking Page

## Overview
Covers the client-facing public booking flow: viewing a shop's public profile page,
browsing available time slots, and submitting a booking without authentication. This
feature is the external-facing surface of the product — clients access it via a shareable
URL. Actors: unauthenticated members of the public (the client).

## Scope
- **In scope**: View shop public info, browse available slots by date and service, submit
  a booking (new and returning client), validation errors, rate limiting behavior.
- **Out of scope**: Owner-side appointment visibility (covered in `appointment-scheduling.plan.md`),
  subscription limit enforcement from the shop side (covered in `subscription.plan.md`).

## Prerequisites
- Application running at `BASE_URL`
- At least 1 shop with a known slug (e.g., `test-barber`)
- The shop has at least 1 active service and configured working hours
- The shop has at least 1 open day within the next 7 days
- A known phone number already linked to a client in the shop (for returning-client test)
- No authentication cookies set (or use a private/incognito browser context)

## Test Scenarios

### Scenario 1: View public shop page — valid slug
**Actor**: Unauthenticated client
**Goal**: Access a shop's booking page and see shop information
**Priority**: Critical

**Steps**:
1. Navigate to `/shop/{slug}` (using the known test shop slug)
2. Assert shop name is displayed
3. Assert shop address is displayed
4. Assert shop phone number is displayed
5. Assert the list of services is visible (at least 1 service with name, duration, price)
6. Assert a date/time selection section is visible

**Expected Result**: Shop info loaded from GET `/api/v1/public/shops/{slug}`. All shop
details rendered without authentication.

---

### Scenario 2: View public shop page — invalid slug
**Actor**: Unauthenticated client
**Goal**: See a 404 / not-found state for an unknown shop slug
**Priority**: High

**Steps**:
1. Navigate to `/shop/nonexistent-slug-xyz-123`
2. Assert a "not found" or error message is displayed
3. Assert no shop data is shown

**Expected Result**: HTTP 404 `SHOP_NOT_FOUND`. A user-friendly error page or message is displayed.

---

### Scenario 3: Browse available time slots
**Actor**: Unauthenticated client
**Goal**: Select a service and date, and see the available booking slots
**Priority**: Critical

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service from the service list (click or select)
3. Select a date within the next 7 days that the shop is open
4. Wait for the slots to load
5. Assert a list of time slots is displayed
6. Assert at least some slots are shown as available
7. Assert past time slots (if today is selected) are excluded or disabled

**Expected Result**: Slots loaded from GET `/api/v1/public/shops/{slug}/available-slots`.
30-minute interval slots shown. Unavailable or past slots are visually distinct or absent.

---

### Scenario 4: No available slots on a closed day
**Actor**: Unauthenticated client
**Goal**: See a "no slots" message when selecting a day the shop is closed
**Priority**: High

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service
3. Select a date that falls on the shop's closed day (e.g., Sunday)
4. Wait for the response

**Expected Result**: No slots displayed. A message indicates the shop is closed on this day.
GET `/api/v1/public/shops/{slug}/available-slots` returns empty or an appropriate status.

---

### Scenario 5: Submit a new booking (new client)
**Actor**: Unauthenticated client, first-time booker
**Goal**: Book an appointment as a new client
**Priority**: Critical

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service
3. Select an open future date
4. Select an available time slot
5. Fill "Your name" with `Walk In Client`
6. Fill "Phone number" with a new, previously unseen phone number (e.g., `0977000001`)
7. Click the "Book appointment" / submit button
8. Wait for response
9. Assert a booking confirmation message is displayed (e.g., "Đặt lịch thành công!")
10. Assert appointment details (date, time, service) are shown in the confirmation

**Expected Result**: POST `/api/v1/public/shops/{slug}/book` returns HTTP 201. New client
record created. Appointment created with status `SCHEDULED`. Confirmation shown.

---

### Scenario 6: Submit a booking — returning client (phone already exists)
**Actor**: Unauthenticated client, returning booker
**Goal**: Book as a returning client and have the booking linked to the existing client record
**Priority**: High

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service, date, and time slot
3. Fill "Phone number" with the known existing client's phone number
4. Fill "Your name" with the same or a slightly different name
5. Click submit
6. Assert confirmation is shown with HTTP 201
7. [Owner verification] Log in as the shop owner, navigate to `/dashboard/appointments`
8. Assert the new appointment is linked to the existing client

**Expected Result**: Booking linked to existing client. If name differs slightly, client
name is updated per business rule 5. Confirmation shown to the public user.

---

### Scenario 7: Slot unavailable — already booked
**Actor**: Unauthenticated client
**Goal**: See an error when trying to book a slot that was just taken
**Priority**: Critical

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service and date
3. Select a time slot that already has an existing `SCHEDULED` appointment [VERIFY: seed this state first]
4. Fill in name and phone
5. Click submit
6. Wait for response

**Expected Result**: HTTP 409 `SLOT_UNAVAILABLE`. Error message displayed (e.g., "This
time slot is no longer available. Please choose another."). No appointment created.

---

### Scenario 8: Booking too close to the current time (< 1 hour)
**Actor**: Unauthenticated client
**Goal**: See an error when trying to book within the minimum advance booking window
**Priority**: High

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service and today's date
3. Select a time slot less than 60 minutes from now (if visible)
4. Fill name and phone
5. Click submit
6. Wait for response

**Expected Result**: Error message displayed indicating the minimum advance booking time
has not been met. HTTP 400 `TOO_SHORT_NOTICE`. [VERIFY: slot may already be hidden in UI —
confirm if the slot is shown or filtered client-side]

---

### Scenario 9: Booking validation errors
**Actor**: Unauthenticated client
**Goal**: See field-level errors when submitting an incomplete or invalid form
**Priority**: High

**Steps**:
1. Navigate to `/shop/{slug}`
2. Select a service, date, and time slot
3. Leave "Your name" empty
4. Leave "Phone number" empty
5. Click submit
6. Assert validation errors appear for name and phone

**Steps (phone format)**:
7. Fill "Phone number" with `abc-invalid`
8. Click submit
9. Assert phone validation error is shown

**Expected Result**: Frontend and/or backend validation errors shown inline. HTTP 400
`VALIDATION_ERROR`. No appointment created.

---

### Scenario 10: Booking on a date too far in the future (> 30 days)
**Actor**: Unauthenticated client
**Goal**: Confirm the 30-day booking window is enforced
**Priority**: Medium

**Steps**:
1. Navigate to `/shop/{slug}`
2. Attempt to select a date more than 30 days from today (if the date picker allows it)
3. Assert the date is disabled or an error appears after selection

**Expected Result**: Date picker restricts selection to ≤ 30 days ahead, OR HTTP 400
`DATE_TOO_FAR_AHEAD` is returned on submit.

---

### Scenario 11: Booking with a date in the past
**Actor**: Unauthenticated client
**Goal**: Confirm past dates cannot be booked
**Priority**: Medium

**Steps**:
1. Navigate to `/shop/{slug}`
2. Attempt to select yesterday's date in the date picker
3. Assert the date is disabled or an error is shown

**Expected Result**: Past dates are not selectable in the UI, or HTTP 400 `DATE_IN_PAST`
is returned if submitted.

## Edge Cases & Negative Tests

### Edge Case 1: Phone number format variations
**Scenario**: Same phone entered as `0901234567` vs `+84901234567`
**Steps**:
1. Book once with `0901234567` as a new client
2. Book again (different slot) with `+84901234567`
**Expected Result**: Both bookings are linked to the same client record (phone normalization
maps both to the same canonical form).

### Edge Case 2: Service duration extends past close time
**Scenario**: A 60-minute service starting at 19:30 when the shop closes at 20:00
**Steps**:
1. Select a service with 60-minute duration
2. Navigate the date/slot picker to show 19:30 slot [VERIFY: confirm whether this slot appears]
3. If shown, attempt to book it
**Expected Result**: Either the slot is not shown (filtered server-side), or HTTP 422 is
returned on booking attempt.

### Edge Case 3: Rate limit — 5 bookings same phone same day
**Scenario**: Same phone number submits more than 5 bookings in a single day
**Steps**:
1. Submit 5 bookings for the same phone on the same day (different slots)
2. Attempt a 6th booking
**Expected Result**: HTTP 429 `BOOKING_RATE_LIMIT_EXCEEDED`. Error message shown.
[NOTE: Requires multiple available slots seeded — may be difficult to automate fully]

## Data Requirements
- 1 shop with a known slug, active services, and open working hours
- At least 3–6 future available slots across 2 different dates
- 1 existing client with a known phone number (for returning-client test)
- 1 existing `SCHEDULED` appointment to create an "unavailable slot" scenario

## Coverage Gaps
- Live rate limit test (5+ bookings) — requires many available slots and multiple Playwright requests
- Concurrent double-booking race condition — requires parallel browser sessions
- Subscription-cancelled shop blocking public access — covered in `subscription.plan.md`
- Zalo deep-link copy after booking — no automated Zalo integration in MVP
