# Test Plan: Subscription & Billing

## Overview
Covers the subscription status dashboard for shop owners: viewing current plan (FREE or
PRO), usage (appointments this month vs limit), upgrade prompt visibility, and the
enforcement of limits on appointment creation. Admin-side activation/cancellation/downgrade
are backend-only actions and are not directly testable through the owner-facing UI.
Actors: authenticated shop owner (view only), unauthenticated public client (limit enforcement
side effect visible via public booking error).

## Scope
- **In scope**: View subscription status page, plan badge display (FREE/PRO), usage meter
  and progress bar, upgrade prompt visibility, expired/cancelled state rendering, appointment
  creation blocked when limit reached (owner side), public booking blocked when limit reached.
- **Out of scope**: Admin panel activation/cancellation (no admin UI in frontend MVP),
  bank transfer payment flow (manual — no UI), PRO multi-staff features (post-MVP).

## Prerequisites
- Application running at `BASE_URL`
- A logged-in shop owner on the FREE plan with known `monthlyAppointmentCount`
- A second account on the FREE plan with count at exactly 50 (limit reached) — or ability
  to seed this state via the API
- A third account on PRO plan (or seed via admin API call) [VERIFY: obtain via manual admin
  activation or test seeding]
- A fourth account with CANCELLED subscription [VERIFY: seed via admin API]

## Test Scenarios

### Scenario 1: View subscription page — FREE plan, active
**Actor**: Authenticated shop owner on FREE plan
**Goal**: See current plan, usage, and upgrade prompt
**Priority**: Critical

**Steps**:
1. Log in as a user on the FREE plan with some appointments used (e.g., 18/50)
2. Navigate to `/dashboard/subscription`
3. Assert "Miễn phí" (Free) plan badge is visible
4. Assert status shows "Hoạt động" (Active)
5. Assert usage shows `18 / 50 lượt` (or current count)
6. Assert a progress bar is displayed
7. Assert "Còn lại X lượt" (remaining count) is shown
8. Assert the upgrade prompt ("Nâng cấp lên Chuyên nghiệp") is visible

**Expected Result**: All subscription fields rendered from GET `/api/v1/subscription`.
Usage and limit displayed correctly.

---

### Scenario 2: View subscription page — FREE plan, limit reached (50/50)
**Actor**: Authenticated shop owner at monthly appointment limit
**Goal**: See the limit-reached warning and upgrade prompt
**Priority**: Critical

**Steps**:
1. Log in as a user whose FREE plan has `monthlyAppointmentCount = 50`
2. Navigate to `/dashboard/subscription`
3. Assert usage shows `50 / 50 lượt`
4. Assert the progress bar is full and styled in red/warning color
5. Assert a warning alert is visible (e.g., "Bạn đã hết lượt đặt lịch trong tháng")
6. Assert the upgrade prompt is visible

**Expected Result**: Limit-reached state rendered. Warning alert and red progress bar visible.

---

### Scenario 3: View subscription page — PRO plan, active
**Actor**: Authenticated shop owner on PRO plan
**Goal**: See PRO plan details including end date and days remaining
**Priority**: High

**Steps**:
1. Log in as a user on the PRO plan with a future expiry date
2. Navigate to `/dashboard/subscription`
3. Assert "Chuyên nghiệp" (Pro) plan badge is visible
4. Assert status shows "Hoạt động" (Active)
5. Assert "Ngày hết hạn" (expiry date) is displayed
6. Assert "Số ngày còn lại" (days remaining) is displayed
7. Assert usage shows appointment count with "Không giới hạn" (unlimited) label
8. Assert NO progress bar is shown (unlimited plan)
9. Assert the upgrade prompt is NOT visible

**Expected Result**: PRO plan UI with expiry info, no usage cap, and no upgrade prompt.

---

### Scenario 4: View subscription page — PRO plan expired (reverted to FREE)
**Actor**: Authenticated shop owner whose PRO has expired
**Goal**: See that the plan has reverted to FREE with limits enforced
**Priority**: High

**Steps**:
1. Log in as a user whose PRO plan has expired
2. Navigate to `/dashboard/subscription`
3. Assert plan badge shows "Miễn phí" (Free)
4. Assert status shows "Hết hạn" (Expired)
5. Assert the end date of the expired subscription is displayed
6. Assert usage shows `X / 50 lượt` (limit is now enforced)

**Expected Result**: Expired state rendered. Subscription reverted to FREE limits.
Plan badge is "FREE" but status is "EXPIRED" (past-PRO state).

---

### Scenario 5: View subscription page — cancelled subscription
**Actor**: Authenticated shop owner with a cancelled subscription
**Goal**: See the cancelled state displayed clearly
**Priority**: High

**Steps**:
1. Log in as a user with a CANCELLED subscription
2. Navigate to `/dashboard/subscription`
3. Assert status badge shows "Đã hủy" (Cancelled) in red
4. Assert no appointment booking actions are available [VERIFY: check if the UI hides booking controls or shows a warning]

**Expected Result**: Cancelled state rendered with red badge.

---

### Scenario 6: Appointment creation blocked at FREE limit
**Actor**: Authenticated shop owner at 50/50 monthly limit
**Goal**: See an error when trying to create the 51st appointment
**Priority**: Critical

**Steps**:
1. Log in as a FREE-plan user with `monthlyAppointmentCount = 50`
2. Navigate to `/dashboard/appointments/create`
3. Fill in all required fields (client, service, future time slot)
4. Click create/save
5. Wait for response

**Expected Result**: HTTP 403 `APPOINTMENT_LIMIT_REACHED`. Error message displayed in the
form (e.g., "Bạn đã đạt giới hạn lịch hẹn trong tháng"). No appointment created.

---

### Scenario 7: Appointment creation NOT blocked for PRO plan
**Actor**: Authenticated shop owner on PRO plan with > 50 appointments this month
**Goal**: Confirm PRO plan bypasses the 50-appointment limit
**Priority**: High

**Steps**:
1. Log in as a PRO-plan user with `monthlyAppointmentCount > 50`
2. Navigate to `/dashboard/appointments/create`
3. Fill in all required fields
4. Click create/save
5. Wait for response

**Expected Result**: HTTP 201. Appointment created successfully. PRO plan ignores the
monthly counter limit.

---

### Scenario 8: Public booking blocked when shop limit reached
**Actor**: Unauthenticated client
**Goal**: See a user-friendly message when a FREE-plan shop has hit its booking limit
**Priority**: High

**Steps**:
1. Ensure the target shop is on FREE plan with `monthlyAppointmentCount = 50`
2. As an unauthenticated user, navigate to `/shop/{slug}`
3. Select a service, date, and slot
4. Fill in name and phone
5. Click submit
6. Wait for response

**Expected Result**: HTTP 403 `APPOINTMENT_LIMIT_REACHED`. User-friendly message shown
(e.g., "Tiệm hiện không nhận lịch online. Vui lòng liên hệ trực tiếp."). No appointment created.

---

### Scenario 9: All writes blocked when subscription is CANCELLED
**Actor**: Authenticated shop owner with CANCELLED subscription
**Goal**: Confirm that creating an appointment is blocked for a cancelled shop
**Priority**: High

**Steps**:
1. Log in as a user with a CANCELLED subscription
2. Navigate to `/dashboard/appointments/create`
3. Fill in all required fields
4. Click save
5. Wait for response

**Expected Result**: HTTP 403 `SUBSCRIPTION_CANCELLED`. Error displayed. No appointment created.
`SubscriptionGuard` blocks all writes on authenticated shop-scoped endpoints.

## Edge Cases & Negative Tests

### Edge Case 1: Usage meter near-limit state (≥ 80%)
**Scenario**: Usage at 42/50 — progress bar should show amber/yellow warning color
**Steps**:
1. Log in as a user with `monthlyAppointmentCount = 42`
2. Navigate to `/dashboard/subscription`
3. Assert the progress bar color is amber/yellow (not blue or red)
**Expected Result**: Near-limit state (≥ 80%) triggers a visual warning on the progress bar.

### Edge Case 2: Cancelling an appointment decrements the usage counter
**Scenario**: Cancelling an appointment frees a slot and decrements the monthly count
**Steps**:
1. Log in as a FREE-plan user with `monthlyAppointmentCount = 50` (at limit)
2. Cancel an existing `SCHEDULED` appointment
3. Navigate to `/dashboard/subscription`
4. Assert usage shows `49 / 50`
5. Navigate to `/dashboard/appointments/create` and attempt to create a new appointment
6. Assert the creation succeeds (limit no longer reached)
**Expected Result**: Counter decremented on cancellation. User can create one more appointment.

### Edge Case 3: Subscription dashboard accessible with no shop
**Scenario**: User without a shop navigates to the subscription page
**Steps**:
1. Log in as a user with no shop
2. Navigate to `/dashboard/subscription`
**Expected Result**: Either redirected to create-shop, or a meaningful error/empty state is shown.
No 500 error. [VERIFY: depends on frontend middleware — check `auth.ts` middleware behavior]

## Data Requirements
- 1 FREE-plan user with `monthlyAppointmentCount` between 1–49
- 1 FREE-plan user with `monthlyAppointmentCount = 50` (limit reached)
- 1 PRO-plan user (seed via admin API) [VERIFY: use `POST /api/v1/admin/subscriptions/{shopId}/activate`]
- 1 user with expired PRO (seed via admin API with past `endDate` + run expire command)
- 1 user with CANCELLED subscription (seed via admin API)
- A shop with a known slug at the FREE limit (for public booking test)

## Coverage Gaps
- Admin UI for activating/cancelling subscriptions — no admin frontend in MVP; test via API directly
- Monthly counter reset (`app:subscriptions:reset-counters` command) — testable via CLI, not UI
- PRO expiration command (`app:subscriptions:expire`) — testable via CLI, not UI
- Bank transfer / payment flow — manual process, no UI to test
