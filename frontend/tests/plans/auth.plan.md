# Test Plan: Authentication & Password Reset

## Overview
Covers all authentication flows: email/password registration, email/password login,
Facebook OAuth, JWT refresh, profile update, forgot password, and reset password.
Every other feature depends on a working auth session, making this the highest-priority
module to verify. Actors: unauthenticated visitors (registration, login, forgot/reset
password) and authenticated shop owners (profile update, token refresh).

## Scope
- **In scope**: Register, login (email + Facebook), logout, token refresh, GET/PUT profile,
  forgot password request, reset password with token.
- **Out of scope**: Admin-level user management, Facebook OAuth end-to-end (requires live
  Facebook app token — mark as `[VERIFY]`), email delivery (only the request side is
  testable via UI).

## Prerequisites
- Application running at `BASE_URL` (e.g., `http://localhost:3000`)
- At least one seeded user with known email/password credentials
- Facebook OAuth app configured (`FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`) [VERIFY: confirm test FB token available before running OAuth scenarios]
- Mailer transport configured (or set to `null://` for dev — email content not directly assertable via UI)

## Test Scenarios

### Scenario 1: Successful email registration
**Actor**: Unauthenticated visitor
**Goal**: Register a new account and be redirected to the dashboard
**Priority**: Critical

**Steps**:
1. Navigate to `/register`
2. Assert page title or heading contains registration text
3. Fill "Email" with a unique test email (e.g., `test+{timestamp}@example.com`)
4. Fill "Password" with `TestPass123`
5. Fill "First name" with `Test`
6. Fill "Last name" with `User`
7. Click the register/submit button
8. Wait for navigation
9. Assert URL is `/dashboard` (or locale-prefixed equivalent)
10. Assert user name or avatar is visible in the top navigation

**Expected Result**: User is created, JWT issued, user lands on dashboard authenticated.

---

### Scenario 2: Registration with duplicate email
**Actor**: Unauthenticated visitor
**Goal**: See a clear error when registering with an already-used email
**Priority**: Critical

**Steps**:
1. Navigate to `/register`
2. Fill "Email" with an email that already exists in the system
3. Fill "Password" with `TestPass123`
4. Fill "First name" with `Dupe`
5. Fill "Last name" with `User`
6. Click the register/submit button
7. Wait for response

**Expected Result**: An error message is displayed indicating the email is already taken.
No navigation away from the register page. HTTP 409 `EMAIL_ALREADY_EXISTS` translated to UI error.

---

### Scenario 3: Registration with invalid data
**Actor**: Unauthenticated visitor
**Goal**: See field-level validation errors before the form submits
**Priority**: High

**Steps**:
1. Navigate to `/register`
2. Click the register/submit button without filling any fields
3. Assert at least one validation error message is visible for "Email"
4. Assert at least one validation error message is visible for "Password"
5. Fill "Email" with `not-an-email`
6. Assert "Email" field shows a format validation error
7. Fill "Password" with `short`
8. Assert "Password" field shows a minimum-length validation error

**Expected Result**: Inline validation errors appear for each invalid field. Form does not submit.

---

### Scenario 4: Successful email login
**Actor**: Unauthenticated visitor
**Goal**: Log in with valid credentials and reach the dashboard
**Priority**: Critical

**Steps**:
1. Navigate to `/login`
2. Fill "Email" with a known valid email
3. Fill "Password" with the correct password
4. Click the login/submit button
5. Wait for navigation
6. Assert URL is `/dashboard`
7. Assert authenticated user UI element is visible

**Expected Result**: JWT stored, user redirected to dashboard.

---

### Scenario 5: Login with wrong password
**Actor**: Unauthenticated visitor
**Goal**: See an error when credentials are incorrect
**Priority**: Critical

**Steps**:
1. Navigate to `/login`
2. Fill "Email" with a valid email
3. Fill "Password" with `WrongPassword999`
4. Click the login/submit button
5. Wait for response

**Expected Result**: Error message displayed (e.g., "Invalid credentials" or Vietnamese equivalent).
User remains on `/login`. No navigation.

---

### Scenario 6: Authenticated route redirect for guests
**Actor**: Unauthenticated visitor
**Goal**: Be redirected to login when accessing a protected page
**Priority**: Critical

**Steps**:
1. Ensure no auth cookies/tokens are set (use a fresh browser session)
2. Navigate to `/dashboard`
3. Wait for navigation

**Expected Result**: Redirected to `/login` (or locale-prefixed login page). Dashboard is not rendered.

---

### Scenario 7: Guest route redirect for authenticated users
**Actor**: Authenticated shop owner
**Goal**: Be redirected away from login/register when already logged in
**Priority**: High

**Steps**:
1. Log in successfully (see Scenario 4)
2. Navigate to `/login`
3. Wait for navigation

**Expected Result**: Redirected to `/dashboard`. Login page is not shown to an already-authenticated user.

---

### Scenario 8: Forgot password — request reset email
**Actor**: Unauthenticated visitor
**Goal**: Submit the forgot password form and receive a confirmation message
**Priority**: High

**Steps**:
1. Navigate to `/forgot-password`
2. Assert the forgot-password form is visible
3. Fill "Email" with a registered user's email
4. Click the submit button
5. Wait for response

**Expected Result**: A success/confirmation message is displayed (e.g., "If an account with
this email exists, a password reset link has been sent."). The message is shown regardless
of whether the email exists (anti-enumeration).

---

### Scenario 9: Forgot password — unknown email still shows success
**Actor**: Unauthenticated visitor
**Goal**: Confirm the anti-enumeration behavior (same message for unknown emails)
**Priority**: High

**Steps**:
1. Navigate to `/forgot-password`
2. Fill "Email" with `nonexistent-{timestamp}@example.com`
3. Click the submit button
4. Wait for response

**Expected Result**: The same success/confirmation message as Scenario 8. No error indicating
the email does not exist.

---

### Scenario 10: Reset password with valid token
**Actor**: Unauthenticated visitor with a valid reset token
**Goal**: Set a new password and be able to log in with it
**Priority**: High

**Steps**:
1. Navigate to `/reset-password?token={valid_raw_token}` [VERIFY: obtain token via API call or test DB seed before running]
2. Assert the reset-password form is visible with a "New password" field
3. Fill "New password" with `NewPassword456`
4. Click the submit button
5. Wait for response
6. Assert a success message is shown or redirect to login occurs
7. Navigate to `/login`
8. Fill "Email" with the email whose token was used
9. Fill "Password" with `NewPassword456`
10. Click login
11. Assert URL is `/dashboard`

**Expected Result**: Password updated, user can log in with new credentials.

---

### Scenario 11: Reset password with invalid/expired token
**Actor**: Unauthenticated visitor
**Goal**: See a clear error when token is invalid or expired
**Priority**: High

**Steps**:
1. Navigate to `/reset-password?token=invalidtoken0000000000000000000000000000000000000000000000000000`
2. Fill "New password" with `AnyPassword123`
3. Click the submit button
4. Wait for response

**Expected Result**: Error message displayed indicating the link is invalid or has expired.
HTTP 401 `INVALID_RESET_TOKEN` translated to UI error.

---

### Scenario 12: Update user profile
**Actor**: Authenticated shop owner
**Goal**: Update first name, last name, or locale
**Priority**: Medium

**Steps**:
1. Log in successfully
2. Navigate to the profile or account settings page [VERIFY: confirm exact route — may be `/dashboard` with a profile section or a dedicated `/account` page]
3. Assert current user name fields are pre-filled
4. Fill "First name" with `UpdatedFirst`
5. Click save
6. Wait for response
7. Assert success feedback is displayed
8. Assert the updated name appears in the navigation

**Expected Result**: Profile updated via PUT `/api/v1/auth/me`, UI reflects changes.

## Edge Cases & Negative Tests

### Edge Case 1: Register with Facebook-linked email via email/password form
**Scenario**: A user previously registered via Facebook now tries email/password login
**Steps**:
1. Navigate to `/login`
2. Fill "Email" with an email that only has a Facebook account (no password set)
3. Fill "Password" with any password
4. Click login
**Expected Result**: Error message shown (invalid credentials or account linked to social login).

### Edge Case 2: Reset password rate limiting
**Scenario**: Submit forgot-password form 4+ times in quick succession with the same email
**Steps**:
1. Navigate to `/forgot-password`
2. Fill "Email" with a valid email
3. Submit the form 4 times rapidly
4. Assert the 4th attempt returns a rate-limit message or 429 response
**Expected Result**: After 3 requests per hour, a rate-limit error is displayed.

### Edge Case 3: Password too short on reset
**Scenario**: Provide a password shorter than 8 characters on the reset form
**Steps**:
1. Navigate to `/reset-password?token={valid_token}`
2. Fill "New password" with `short`
3. Click submit
**Expected Result**: Validation error displayed indicating minimum password length.

## Data Requirements
- At least 1 existing user with known email/password (seeded or created in a prior test run)
- A valid, unused password reset token for reset-password tests (obtain via API or seed script)
- A "Facebook-only" user (null password) for OAuth edge case [VERIFY: may require manual setup]

## Coverage Gaps
- Facebook OAuth full flow — requires a live Facebook access token; not automatable without a test Facebook account or mock
- Email delivery verification — the actual email contents cannot be asserted via the UI; token must be injected directly
- Token expiry boundary test (exactly at 1 hour) — requires time manipulation
- Concurrent registration race condition — requires load-testing tooling beyond Playwright
