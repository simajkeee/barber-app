# Test Plan: UX Batch 5 — Dashboard Home Stats

## Overview
The dashboard home page (`/dashboard`) now displays four real-time stat widgets driven by
existing API endpoints: today's appointment count, the next upcoming appointment (with a link
to its detail), monthly appointment usage with a visual progress bar, and the current
subscription plan with an upgrade prompt for free users. Quick-action buttons for New
Appointment and New Client are also present. The 2×2 grid layout is responsive (stacked on
mobile). No new backend endpoints were created.

## Scope
- **In scope**: All four stat widgets, their loading states, their empty/null states, quick-
  action buttons, responsive grid layout behavior, upgrade link for free plan users, next
  appointment card as a navigable link.
- **Out of scope**: Appointment creation and client creation flows (triggered by quick actions),
  subscription plan upgrade flow, daily schedule and appointment list pages.

## Prerequisites
- Application running at `BASE_URL`
- Logged in as shop owner with an active shop
- At least one appointment scheduled for today (for "today's count" widget)
- At least one future scheduled appointment (for "next appointment" widget)
- An active subscription (free or pro) with known usage numbers

---

## Test Scenarios

### Scenario 1: Dashboard renders all four stat widgets
**Actor**: Authenticated shop owner
**Goal**: All four stat cards are visible on the dashboard
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Wait for page to fully load (all loading spinners/skeletons gone)
3. Assert a widget containing today's appointment count is visible
4. Assert a widget containing the next upcoming appointment is visible
5. Assert a widget containing monthly usage data is visible
6. Assert a widget containing the subscription plan name is visible

**Expected Result**: All four stat cards render with real data.

---

### Scenario 2: Today's appointment count shows correct number
**Actor**: Authenticated shop owner
**Goal**: The "today" widget shows the correct count of today's appointments
**Priority**: Critical

**Steps**:
1. Note the expected number of appointments for today (from the appointments page)
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for the today's count widget to load
4. Assert the today widget contains the correct numeric count

**Expected Result**: Count matches the number of appointments on today's daily schedule.

---

### Scenario 3: Next appointment card shows client name, service, and time
**Actor**: Authenticated shop owner
**Goal**: The next upcoming appointment card shows the client name, service name, and formatted time
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Wait for the next appointment widget to load
3. Assert the next appointment card contains a client name (first + last)
4. Assert the next appointment card contains a service name
5. Assert the next appointment card contains a formatted date/time

**Expected Result**: All three data points are visible on the next appointment card.

---

### Scenario 4: Next appointment card navigates to appointment detail
**Actor**: Authenticated shop owner
**Goal**: Clicking the next appointment card goes to that appointment's detail page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Wait for the next appointment widget to load
3. Note the client name shown in the next appointment card
4. Click the next appointment card
5. Assert URL is `/en/dashboard/appointments/{id}`
6. Assert the appointment detail page shows the same client name

**Expected Result**: The card is a clickable link to the appointment detail page.

---

### Scenario 5: Monthly usage widget shows appointments used and limit
**Actor**: Authenticated shop owner (limited plan)
**Goal**: The usage widget shows "{used} / {limit}" and a progress bar
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Wait for the usage widget to load
3. Assert the usage widget contains numbers in the format "X / Y" (or similar)
4. Assert a progress bar element is visible within the usage widget
5. Assert the progress bar width is greater than 0% (assuming at least one appointment this month)

**Expected Result**: Usage is displayed with a visual progress bar.

---

### Scenario 6: Unlimited plan shows appointments used without a limit
**Actor**: Authenticated shop owner (unlimited/pro plan)
**Goal**: When there is no appointment limit, usage shows count without "/ limit" denominator
**Priority**: Medium

**Steps**:
1. Log in as a user with an unlimited-quota plan
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for the usage widget to load
4. Assert the usage widget shows the appointment count
5. Assert the usage widget contains "/ Unlimited" text (or equivalent)
6. Assert NO progress bar is shown (no bar when unlimited)

**Expected Result**: Unlimited plan shows count with "/ Unlimited" label and no progress bar.

---

### Scenario 7: Subscription plan widget shows plan name and days remaining
**Actor**: Authenticated shop owner
**Goal**: The plan widget shows the plan name and remaining subscription days
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Wait for the plan widget to load
3. Assert the plan name (e.g., "free" or "pro") is visible and capitalized
4. Assert "days remaining" or equivalent text is visible with a number

**Expected Result**: Plan name and remaining days are both shown.

---

### Scenario 8: Free plan shows upgrade link
**Actor**: Authenticated shop owner on free plan
**Goal**: Free plan users see an "Upgrade" link pointing to the subscription page
**Priority**: High

**Steps**:
1. Log in as a user on the free plan
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for the plan widget to load
4. Assert an "Upgrade" link or button is visible within the plan widget
5. Click the "Upgrade" link
6. Assert URL is `/en/dashboard/subscription`

**Expected Result**: Free plan users see and can click an upgrade prompt.

---

### Scenario 9: Quick action "New Appointment" button navigates to create page
**Actor**: Authenticated shop owner
**Goal**: The quick-action primary button navigates to the appointment creation page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Assert a "New Appointment" button is visible (primary variant)
3. Click "New Appointment"
4. Assert URL is `/en/dashboard/appointments/create`

**Expected Result**: User is taken to the appointment creation form.

---

### Scenario 10: Quick action "New Client" button navigates to create page
**Actor**: Authenticated shop owner
**Goal**: The secondary quick-action button navigates to the client creation page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Assert a "New Client" button is visible (secondary variant)
3. Click "New Client"
4. Assert URL is `/en/dashboard/clients/create`

**Expected Result**: User is taken to the client creation form.

---

### Scenario 11: Loading skeletons are shown while stats load
**Actor**: Authenticated shop owner
**Goal**: Each widget shows a skeleton/placeholder before data arrives
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Immediately assert `.animate-pulse` skeleton elements are visible in the stat grid
3. Wait for all data to load
4. Assert no skeleton elements remain visible

**Expected Result**: Loading skeletons are shown during fetch, replaced by real data.
**Notes**: May require network throttling to observe the loading state.

---

### Scenario 12: No shop configured — dashboard shows empty state
**Actor**: Authenticated user without a shop
**Goal**: When no shop exists, the dashboard shows the "create a shop" empty state instead of stats
**Priority**: High

**Steps**:
1. Log in as a user with no shop configured
2. Navigate to `BASE_URL/en/dashboard`
3. Assert the four stat widgets are NOT visible
4. Assert text "You don't have a shop yet" (or equivalent) is visible
5. Assert a "Create Shop" button or link is visible

**Expected Result**: Empty state renders for users without a shop.

---

## Edge Cases & Negative Tests

### Edge Case 1: Today has zero appointments
**Scenario**: No appointments scheduled for today — the count widget shows 0.
**Steps**:
1. Ensure no appointments are scheduled for today
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for today's count to load
4. Assert the count widget shows "0"
**Expected Result**: 0 is displayed — no error, no blank.

### Edge Case 2: No future appointments — "Next Appointment" shows empty state
**Scenario**: There are no upcoming scheduled appointments.
**Steps**:
1. Ensure no future scheduled appointments exist
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for next appointment widget to load
4. Assert the widget shows "No upcoming appointments" (or equivalent) — not an error
**Expected Result**: Empty/none state message is shown for the next appointment slot.

### Edge Case 3: Progress bar turns amber at 80%+ usage
**Scenario**: When usage is 80% or more of the limit, the progress bar changes to amber/warning color.
**Steps**:
1. Log in as a user with at least 80% of their monthly appointment limit used
2. Navigate to `BASE_URL/en/dashboard`
3. Wait for usage widget to load
4. Assert the progress bar has the amber/warning class (not the primary blue)
**Expected Result**: Visual warning is shown for high usage. `[VERIFY: amber color class is bg-amber-500]`

### Edge Case 4: Stats load independently (partial failure)
**Scenario**: One API call fails (e.g., subscription) while others succeed.
**Steps**:
1. `[SIMULATE: block only the /api/v1/subscription request]`
2. Navigate to `BASE_URL/en/dashboard`
3. Assert today's count widget loads successfully
4. Assert next appointment widget loads successfully
5. Assert plan widget does not crash the page (may show empty or error sub-state)
**Expected Result**: Failure of one stat card does not crash other cards (`Promise.allSettled` behavior).

---

## Data Requirements
- One shop owner with at least 1 appointment today (Scenarios 2, 11)
- One future scheduled appointment (Scenarios 3, 4)
- One user on a limited plan with known usage numbers (Scenarios 5, 7, 8)
- One user on an unlimited/pro plan (Scenario 6)
- One user on the free plan (Scenario 8)
- One user with no shop (Scenario 12)
- One user with 0 appointments today (Edge Case 1)
- One user with no future appointments (Edge Case 2)
- One user at 80%+ usage (Edge Case 3)

## Coverage Gaps
- Real-time updates (stat cards do not auto-refresh — only updates on navigate)
- Mobile 1-column stacked layout — requires viewport resize
- Timezone edge cases for "today's" appointments (server vs. client timezone)
