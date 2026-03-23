# Test Plan: UX Batch 3 — Landing Page

## Overview
A new static landing page at `/` (index route) replaces a blank or redirect-only entry point.
The page presents the product hero, two primary CTAs (Get Started Free / Log In), a 3-feature
summary section, and a language switcher in the footer. No API calls are made — the page is
fully static. This is the first impression for unauthenticated visitors and directly impacts
conversion.

## Scope
- **In scope**: Hero content, CTA button labels and destinations, 3-feature summary, language
  switcher in footer, page accessibility at a basic level.
- **Out of scope**: Registration and login flows (tested in auth plans), subscription pricing,
  authenticated user redirect behavior from `/`.

## Prerequisites
- Application running at `BASE_URL`
- Unauthenticated session (no logged-in user)
- Both `/en` and `/vi` locale prefixes reachable

---

## Test Scenarios

### Scenario 1: Landing page renders hero section
**Actor**: Unauthenticated visitor
**Goal**: The landing page shows a descriptive headline and sub-headline
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert page title or main heading contains "Manage your barbershop" (or equivalent)
3. Assert a sub-headline / description text is visible below the main heading

**Expected Result**: Hero section with product description is rendered.

---

### Scenario 2: "Get Started Free" CTA navigates to /register
**Actor**: Unauthenticated visitor
**Goal**: Primary CTA button takes the user to the registration page
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert a button or link with text "Get Started Free" is visible
3. Click "Get Started Free"
4. Assert URL is `/en/register`

**Expected Result**: User lands on the registration page.

---

### Scenario 3: "Log In" CTA navigates to /login
**Actor**: Unauthenticated visitor
**Goal**: Secondary CTA button takes the user to the login page
**Priority**: Critical

**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert a button or link with text "Log In" is visible
3. Click "Log In"
4. Assert URL is `/en/login`

**Expected Result**: User lands on the login page.

---

### Scenario 4: Three feature summary cards are visible
**Actor**: Unauthenticated visitor
**Goal**: The page communicates the product's three core capabilities
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert a section containing "Online Bookings" text is visible
3. Assert a section containing "Client Management" text is visible
4. Assert a section containing "Appointment Reminders" text is visible

**Expected Result**: All three feature cards/sections are present on the page.

---

### Scenario 5: Language switcher is present in the footer
**Actor**: Unauthenticated visitor
**Goal**: Users can switch language from the landing page footer
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en`
2. Scroll to the bottom of the page
3. Assert a language switcher element is visible in the footer area

**Expected Result**: Language switcher is accessible without leaving the page.

---

### Scenario 6: Landing page renders correctly in Vietnamese
**Actor**: Vietnamese-locale visitor
**Goal**: The `/vi` version of the landing page displays translated content
**Priority**: High

**Steps**:
1. Navigate to `BASE_URL/vi`
2. Assert the page renders (no 404 or error)
3. Assert at least the hero heading text is different from the English version (or is Vietnamese)
4. Assert "Get Started Free" equivalent CTA is visible (in Vietnamese)

**Expected Result**: Vietnamese landing page renders with translated content.

---

### Scenario 7: No API calls are made on the landing page
**Actor**: Unauthenticated visitor
**Goal**: Confirm the landing page is fully static — no fetch requests to `/api/`
**Priority**: Medium

**Steps**:
1. Navigate to `BASE_URL/en`
2. Open browser network tab (via `browser_network_requests`)
3. Assert no requests to `/api/` are present in the network log

**Expected Result**: Zero API requests — page is static.

---

### Scenario 8: Authenticated user visiting / is redirected to dashboard
**Actor**: Already logged-in user
**Goal**: Authenticated users are not shown the landing page — they go straight to the dashboard
**Priority**: High

**Steps**:
1. Log in as a shop owner (navigate to `/en/login`, fill credentials, submit)
2. Navigate to `BASE_URL/en` (root)
3. Assert URL is `/en/dashboard` (redirect has occurred)

**Expected Result**: Authenticated users bypass the landing page and land on the dashboard.
**Notes**: `[VERIFY: confirm whether a guest middleware redirects logged-in users away from /]`

---

## Edge Cases & Negative Tests

### Edge Case 1: Page is accessible without JavaScript (SSR check)
**Scenario**: The landing page content is server-rendered and visible before JS hydrates.
**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert page contains heading text before any JS-triggered rendering
**Expected Result**: Content is in the initial HTML response (SSR).
**Notes**: Hard to test with Playwright alone — check page source if needed.

### Edge Case 2: CTA buttons respond to keyboard navigation
**Scenario**: A keyboard-only user can Tab to both CTA buttons and activate them.
**Steps**:
1. Navigate to `BASE_URL/en`
2. Press Tab key repeatedly until "Get Started Free" button is focused
3. Assert focus indicator is visible on the button
4. Press Enter
5. Assert URL is `/en/register`
**Expected Result**: CTA is keyboard-accessible.

### Edge Case 3: Feature cards have no broken images
**Scenario**: If feature cards include icons or images, they load correctly.
**Steps**:
1. Navigate to `BASE_URL/en`
2. Assert no broken image (`<img>` with natural width 0) is present in the features section
**Expected Result**: All visual elements load correctly.

---

## Data Requirements
- No test data required — page is fully static
- One valid authenticated session for Scenario 8 (any registered shop owner)

## Coverage Gaps
- SEO meta tags (`<title>`, `<meta description>`, Open Graph) — not tested here
- Performance / LCP measurement — not in scope for functional testing
- Mobile responsiveness — requires viewport resize; not covered in this plan
