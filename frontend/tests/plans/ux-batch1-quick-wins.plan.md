# Test Plan: UX Batch 1 — Quick Wins

## Overview
Six small, high-value UX improvements that reduce friction across the dashboard: a clickable
logo, a cancel button on shop creation, a toast before redirect on the appointment edit guard,
clickable appointment list rows, a semantic heading fix on the shop profile page, and visual
polish to the language switcher. All changes are frontend-only with no API impact.

## Scope
- **In scope**: Logo navigation link, shop create cancel button, appointment edit terminal-status
  redirect toast, appointment list row clickability, shop page heading hierarchy, language
  switcher appearance.
- **Out of scope**: Business logic of any page, data mutations, subscription flows.

## Prerequisites
- Application running at `BASE_URL`
- At least one active shop configured for the test user
- At least one appointment in a terminal status (completed, cancelled, or no-show)
- At least one appointment in a non-terminal status (scheduled)
- Logged in as the shop owner

---

## Test Scenarios

### Scenario 1: Logo navigates to /dashboard when authenticated
**Actor**: Authenticated shop owner
**Goal**: Click the BarberPro logo and land on the dashboard
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments`
2. Assert element with the BarberPro logo/wordmark is visible
3. Click the BarberPro logo/wordmark
4. Assert URL is `/en/dashboard` (or `/vi/dashboard`)

**Expected Result**: User is redirected to the dashboard home page.

---

### Scenario 2: Logo navigates to /login when unauthenticated
**Actor**: Guest user
**Goal**: Click the BarberPro logo on a public page and land on /login
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/login`
2. Assert element with the BarberPro logo/wordmark is visible
3. Click the BarberPro logo/wordmark
4. Assert URL is `/en/login` or `/en` (root/login)

**Expected Result**: User stays on login or is navigated to the home/login page — not to the dashboard.
**Notes**: `[VERIFY: check whether the logo appears on auth pages (login/register) and where it links]`

---

### Scenario 3: Shop create page has a working Cancel button
**Actor**: Authenticated shop owner without a shop
**Goal**: Cancel shop creation and return to the dashboard
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/shop/create`
2. Assert a "Cancel" button (variant: secondary) is visible
3. Click the "Cancel" button
4. Assert URL is `/en/dashboard`

**Expected Result**: User is returned to the dashboard without creating a shop.

---

### Scenario 4: Editing a terminal-status appointment shows toast then redirects
**Actor**: Authenticated shop owner
**Goal**: Navigating to the edit page for a completed appointment shows an informational toast before redirecting
**Priority**: High

**Steps**:
1. Note the ID of a completed (or cancelled/no-show) appointment
2. Navigate to `BASE_URL/en/dashboard/appointments/{id}/edit`
3. Assert a toast notification is visible with a message indicating the appointment cannot be edited
4. Assert URL changes to `/en/dashboard/appointments/{id}` (the detail page)

**Expected Result**: Toast appears briefly, then the user is redirected to the appointment detail page — not silently dropped there.
**Notes**: The toast message key is `[VERIFY: confirm exact i18n key from appointments.edit.terminalRedirect or similar]`

---

### Scenario 5: Clicking an appointment row navigates to appointment detail
**Actor**: Authenticated shop owner
**Goal**: The entire appointment list row is a clickable link to the appointment detail page
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments`
2. Click the "All Appointments" tab
3. Wait for appointment list to load
4. Assert at least one appointment row is visible
5. Click the first appointment row (not a specific button — click the row itself)
6. Assert URL is `/en/dashboard/appointments/{id}` (any valid appointment ID)

**Expected Result**: Clicking anywhere on the row navigates to that appointment's detail page.

---

### Scenario 6: Shop profile page Working Hours uses correct heading level
**Actor**: Authenticated shop owner
**Goal**: The "Working Hours" section uses an `<h2>` heading, not `<h3>`
**Priority**: Low

**Steps**:
1. Navigate to `BASE_URL/en/dashboard/shop`
2. Assert the page contains a heading element with text "Working Hours"
3. Assert the "Working Hours" heading is an `<h2>` element (not `<h3>`)

**Expected Result**: The heading hierarchy is semantically correct — "Working Hours" is `<h2>`.
**Notes**: Requires DOM inspection. Use `browser_evaluate` to check `document.querySelector('h2')?.textContent` includes "Working Hours".

---

### Scenario 7: Language switcher has visible border/pill styling and globe icon
**Actor**: Any user
**Goal**: The language switcher has distinct visual styling — globe icon, border, and heavier font weight
**Priority**: Low

**Steps**:
1. Navigate to `BASE_URL/en/login`
2. Assert the language switcher element is visible
3. Assert the language switcher contains a globe icon (SVG or img element)
4. Assert the language switcher has a visible border or pill-shaped container

**Expected Result**: The language switcher is visually distinct and includes a globe icon.
**Notes**: Visual test — use screenshot comparison if border detection is unreliable. `[VERIFY: confirm globe icon selector]`

---

### Scenario 8: Language switcher switches language from English to Vietnamese
**Actor**: Guest user
**Goal**: Clicking the language switcher changes the UI language
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en/login`
2. Assert current language is "EN" (English label visible in switcher)
3. Click the language switcher
4. Select "Vietnamese" / "VI" from the language options
5. Assert URL changes to `/vi/login` (or equivalent Vietnamese-locale path)
6. Assert page content is now in Vietnamese

**Expected Result**: UI language switches to Vietnamese and URL reflects the new locale.

---

## Edge Cases & Negative Tests

### Edge Case 1: Logo on shop/[slug] public page (unauthenticated context)
**Scenario**: The public booking page may also show the logo — verify it does not link to the dashboard.
**Steps**:
1. Navigate to `BASE_URL/en/shop/test-shop` (or any valid shop slug)
2. If logo is visible, click it
3. Assert URL is NOT `/en/dashboard`
**Expected Result**: Logo links to home (`/`) or login — not to the authenticated dashboard.
**Notes**: `[VERIFY: confirm whether AppLogo appears on the public booking page]`

### Edge Case 2: Cancel on shop create with partial form data
**Scenario**: User partially fills the shop create form, then clicks Cancel.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/shop/create`
2. Fill "Shop name" with "Test Shop"
3. Click "Cancel"
4. Assert URL is `/en/dashboard`
**Expected Result**: Form is abandoned with no save, no error, no confirmation dialog required.

### Edge Case 3: Appointment row click on Daily Schedule tab
**Scenario**: Clicking an appointment card in the Daily Schedule view also navigates to detail.
**Steps**:
1. Navigate to `BASE_URL/en/dashboard/appointments`
2. Stay on "Daily Schedule" tab
3. Wait for appointments to load (ensure today has at least one)
4. Click the first appointment card
5. Assert URL is `/en/dashboard/appointments/{id}`
**Expected Result**: Daily schedule cards are also navigable. `[VERIFY: confirm DailyList items are wrapped in NuxtLink]`

---

## Data Requirements
- One shop configured and active for the test user
- At least one appointment with `status: completed` (for Scenario 4)
- At least one appointment with `status: scheduled` visible on the appointments list (for Scenario 5)
- Today must have at least one scheduled appointment for Edge Case 3 (or skip/mark as conditional)

## Coverage Gaps
- Actual pixel-level visual regression for language switcher styling — requires screenshot diffing tool
- Logo link behavior on the register and forgot-password pages — not specified in the spec
- Keyboard navigation to/through the language switcher dropdown — accessibility not tested here
