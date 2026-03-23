# Test Plan: UX Batch 2 — Error States

## Overview
Two error-state hardening changes: the public shop booking page (`/shop/[slug]`) now shows a
styled "shop unavailable" message instead of a blank screen on 403/fetch failure, and the
dashboard subscription page has a proper error state with a retry button and support contact
link. These changes ensure users are never left on a broken page with no guidance.

## Scope
- **In scope**: Public shop page error and unavailable states; subscription page error state
  including retry button, support link, and the special `SUBSCRIPTION_NOT_FOUND` case.
- **Out of scope**: Happy-path booking flow, subscription plan card content, payment flows.

## Prerequisites
- Application running at `BASE_URL`
- A valid shop slug for happy-path control test
- An invalid/nonexistent shop slug for 404 test
- A shop slug whose subscription is expired or not yet set up (for 403 test) — `[VERIFY: confirm backend returns 403 when subscription is inactive]`
- Logged in as shop owner for subscription page tests

---

## Test Scenarios

### Scenario 1: Public shop — valid shop slug renders booking flow
**Actor**: Public visitor (unauthenticated)
**Goal**: A valid shop slug loads the booking UI correctly
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/shop/{valid-slug}`
2. Wait for page to load
3. Assert the service selection step is visible
4. Assert no error or "unavailable" message is shown

**Expected Result**: Booking flow renders at step 1 (service selection).

---

### Scenario 2: Public shop — invalid slug shows "Shop not found"
**Actor**: Public visitor (unauthenticated)
**Goal**: An invalid shop slug shows a clear not-found message, not a blank page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/shop/this-shop-does-not-exist-xyz`
2. Wait for page to load
3. Assert page contains text "Shop not found" (or equivalent i18n text)
4. Assert the booking stepper is NOT visible

**Expected Result**: "Shop not found" message is displayed.

---

### Scenario 3: Public shop — unavailable/inactive shop shows styled error state
**Actor**: Public visitor (unauthenticated)
**Goal**: A shop with an inactive or unset subscription shows "This shop is temporarily unavailable" with explanatory text and a link home
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/shop/{slug-of-inactive-shop}`
2. Wait for page to load
3. Assert page contains text "This shop is temporarily unavailable"
4. Assert page contains text "The shop may be setting up their account" (or similar)
5. Assert a link to the home page (`/`) is visible

**Expected Result**: Styled unavailable state is shown — not a blank page or raw error.
**Notes**: `[VERIFY: confirm the exact slug to use for an inactive shop in the test environment]`

---

### Scenario 4: Public shop — 403 response surfaces subscription gate clearly
**Actor**: Public visitor (unauthenticated)
**Goal**: When the backend returns 403 (subscription expired), the UI communicates this to visitors
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/shop/{slug-of-403-shop}`
2. Wait for page to load
3. Assert an error state is visible (not a blank page)
4. Assert the message does NOT expose raw technical error details

**Expected Result**: User sees a friendly unavailability message. The 403 reason is communicated at a user-appropriate level.
**Notes**: `[VERIFY: confirm whether 403 and 404 are surfaced differently, or both show "unavailable"]`

---

### Scenario 5: Subscription page — loads successfully
**Actor**: Authenticated shop owner with active subscription
**Goal**: The subscription page renders plan and usage data correctly
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/subscription`
2. Wait for loading spinner to disappear
3. Assert plan name is visible (e.g., "free" or "pro")
4. Assert usage card is visible (shows appointments used / limit)

**Expected Result**: Subscription page renders without error.

---

### Scenario 6: Subscription page — error state shows retry button and support link
**Actor**: Authenticated shop owner when subscription API fails
**Goal**: On API error, the user sees a clear error state with a retry option and support contact
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/subscription`
2. `[SIMULATE: subscription API returning a 500 error — may require mocking or network throttle]`
3. Wait for error state to appear
4. Assert an error alert/message is visible
5. Assert a "Try Again" button is visible
6. Assert a "Contact Support" link is visible
7. Click "Try Again"
8. Assert the page attempts to reload the subscription data (spinner appears)

**Expected Result**: Error state shows actionable options — the user is not stuck with no path forward.
**Notes**: Network error simulation may require disabling the API endpoint or using browser devtools offline mode. `[VERIFY: confirm "Contact Support" links to the correct support URL]`

---

### Scenario 7: Subscription page — SUBSCRIPTION_NOT_FOUND shows setup message
**Actor**: Authenticated shop owner whose subscription is still being provisioned
**Goal**: The `SUBSCRIPTION_NOT_FOUND` error code surfaces a user-friendly "being set up" message instead of a generic error
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/subscription`
2. `[SIMULATE: subscription API returning code: 'SUBSCRIPTION_NOT_FOUND']`
3. Wait for response to process
4. Assert page contains text "Your subscription is being set up"
5. Assert page contains text "Please refresh the page in a moment" (or similar)
6. Assert NO generic error/alert is displayed

**Expected Result**: The setup-in-progress message is shown, distinguishing it from a true error.

---

## Edge Cases & Negative Tests

### Edge Case 1: Public shop page on slow network
**Scenario**: The shop API takes >3 seconds — spinner is visible, then shop loads.
**Steps**:
1. `[SIMULATE: add 3s network delay via browser devtools]`
2. Navigate to `BASE_URL/en/shop/{valid-slug}`
3. Assert a loading spinner or skeleton is visible immediately
4. Wait for page to load
5. Assert booking flow renders correctly after load
**Expected Result**: Loading state is visible during fetch; no blank flash.

### Edge Case 2: Subscription page retry succeeds after initial failure
**Scenario**: User retries after error and the retry succeeds.
**Steps**:
1. Trigger error state on subscription page (see Scenario 6)
2. Restore API connectivity
3. Click "Try Again"
4. Wait for data to load
5. Assert error state is gone and plan card is visible
**Expected Result**: Retry clears the error and renders data.

### Edge Case 3: Subscription sidebar nav item visibility
**Scenario**: `[VERIFY: spec notes "conditionally hide sidebar nav item if subscription endpoint not yet live"]` — confirm sidebar always shows Subscription link now that endpoint is live.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard`
2. Assert "Subscription" link is visible in the sidebar navigation
**Expected Result**: Subscription nav item is always visible for authenticated users.

---

## Data Requirements
- A valid, active shop slug for control test (Scenario 1)
- A non-existent slug (any random string works for Scenario 2)
- A shop slug whose backend returns 403 or whose subscription is inactive (Scenarios 3–4) — `[VERIFY: set up test data]`
- Network simulation capability for Scenarios 6–7 (browser devtools or proxy)

## Coverage Gaps
- Actual 403 simulation requires a test shop with expired subscription — may need dedicated test fixture
- `SUBSCRIPTION_NOT_FOUND` state is hard to trigger without mocking the API response
- Concurrent refresh behavior on the subscription page is not tested
