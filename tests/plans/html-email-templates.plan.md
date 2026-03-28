# Test Plan: HTML Email Templates

## Overview
This module covers the replacement of all plain-text outgoing emails with branded HTML templates rendered via Symfony `TemplatedEmail` and Twig. Four email types are affected: booking confirmation (sent to the client), new booking notification (sent to the shop owner), appointment cancellation (sent to the client), and password reset (sent to the account holder). All templates share a common base layout, support `vi` and `en` locales, inline their CSS for Gmail compatibility, and include a plain-text fallback. Testing this module is critical because email is a primary trust signal for clients and shop owners — broken, missing, or malformed email content directly damages user confidence and usability.

Since email sending is asynchronous (via Symfony Messenger), tests trigger the relevant UI or API action and then inspect the delivered email via **Mailpit** (local SMTP catch-all). There is no email preview route — all scenarios go through the real dispatch pipeline.

## Scope
- **In scope**: HTML structure and content of all 4 email templates, locale-aware subjects and body copy, correct template variables rendered (name, service, date/time, shop contact), null-field handling (`shopAddress`, `shopPhone`), plain-text fallback presence, CSS inlining (no external stylesheet links in the email source), password reset link and expiry, both `vi` and `en` locales.
- **Out of scope**: Email delivery to real inboxes (Mailpit only), dark mode variants, AMP format, open/click tracking, unsubscribe links, SMS templates, Messenger queue internals, SMTP authentication.

## Prerequisites
- Application running at `BASE_URL` (default `http://localhost`)
- **Mailpit running on the host at `localhost:1025` (SMTP) and `localhost:8025` (web UI)** — the Docker Compose MAILER_DSN points to `host.docker.internal:1025`. Start Mailpit separately before running these tests: `mailpit` or `docker run -p 1025:1025 -p 8025:8025 axllent/mailpit`
- Mailpit inbox cleared before each scenario (click "Delete all" in the Mailpit UI at `http://localhost:8025`)
- At least one active shop with: a known `slug`, at least one bookable service, at least one available future slot, a shop owner account with a confirmed email address
- At least one future appointment in a bookable state for the cancellation scenarios
- A registered user account with a known email address for the password reset scenario
- `[NOT YET IMPLEMENTED — validate before running]` — the unified template architecture (shared layout, single-file templates, CSS inlining) described in this spec must be deployed before executing this plan. The current codebase uses locale-suffixed template files (`booking_confirmation.vi.html.twig`) and has no password reset template.

## Test Scenarios

### Scenario 1: Booking confirmation email sent to client — Vietnamese locale
**Actor**: Anonymous client completing a public booking
**Goal**: Confirm the client receives a properly rendered HTML booking confirmation email with correct Vietnamese content
**Priority**: Critical

**Steps**:
1. Navigate to `http://localhost:8025` and click "Delete all" to clear the Mailpit inbox
2. Navigate to `BASE_URL/shop/test-shop`
3. Select a service
4. Select a future date and available time slot
5. Fill `clientName` with `Nguyễn Văn A`
6. Fill `clientPhone` with `0901234567`
7. Wait for the Turnstile CAPTCHA widget to resolve (or complete CAPTCHA if using real keys)
8. Click the Next button to advance to the confirmation step
9. Click the Confirm / Book button
10. Assert a success message is visible on the page
11. Navigate to `http://localhost:8025`
12. Wait for a new message to appear in the Mailpit inbox (refresh if needed, wait up to 10 seconds)
13. Click on the most recent email in the inbox
14. Assert the email subject contains `Xác nhận lịch hẹn` [VERIFY: confirm exact Vietnamese subject string from handler]
15. Assert the email recipient matches the client's email address [VERIFY: confirm public booking captures client email — current implementation may only send if client email exists in DB]
16. Click the "HTML" tab in Mailpit to view the rendered HTML
17. Assert the email body contains `Nguyễn Văn A`
18. Assert the email body contains the service name selected in step 3
19. Assert the email body contains the date and time selected in steps 4–5
20. Assert the email body contains the shop name `test-shop` [VERIFY: actual shop display name]
21. Assert the email body does NOT contain the text `null`
22. Assert the email body does NOT contain `{{ ` (unrendered Twig variables)

**Expected Result**: A well-formed HTML email arrives in Mailpit with the client's name, service, formatted date/time, and shop details — all in Vietnamese. No raw template variables or "null" strings visible.
**Notes**: The public booking flow may only send a confirmation email if the client has an email address on record. [VERIFY: confirm whether the public booking form captures an email field — if not, this scenario may require a registered-client booking instead]

---

### Scenario 2: Booking confirmation email — English locale
**Actor**: Anonymous client (English locale)
**Goal**: Confirm the booking confirmation email renders correctly in English
**Priority**: High

**Steps**:
1. Navigate to `http://localhost:8025` and click "Delete all"
2. Navigate to `BASE_URL/en/shop/test-shop`
3. Select a service, date, and time slot
4. Fill `clientName` with `John Smith`
5. Fill `clientPhone` with `0901234567`
6. Complete CAPTCHA and confirm the booking
7. Assert success message is visible
8. Navigate to `http://localhost:8025` and wait for a new email
9. Click the most recent email
10. Assert the email subject is in English (contains `Appointment Confirmation` or equivalent) [VERIFY: confirm exact English subject string]
11. Click the "HTML" tab
12. Assert the email body contains `John Smith`
13. Assert the email body contains the formatted date in English format (e.g., `Wednesday, 25 Mar 2026`)
14. Assert the email body does NOT contain Vietnamese text for labels (e.g., `Thứ` should not appear for the day name if the locale is `en`)

**Expected Result**: Subject and body are in English. Date is formatted in English locale style.

---

### Scenario 3: New booking notification sent to shop owner
**Actor**: Shop owner (receives notification when a client books)
**Goal**: Confirm the shop owner receives an HTML email with the client's details after a booking is made
**Priority**: Critical

**Steps**:
1. Navigate to `http://localhost:8025` and click "Delete all"
2. Complete a public booking as in Scenario 1 (any client name and phone)
3. Assert success message is visible on the booking page
4. Navigate to `http://localhost:8025` and wait for new emails (there may be 2: one to the client, one to the owner)
5. Find the email addressed to the shop owner's email address
6. Click on that email
7. Assert the subject contains a notification phrase [VERIFY: confirm exact subject — e.g., `Lịch hẹn mới` for vi]
8. Click the "HTML" tab
9. Assert the email body contains the client's name filled in step 2
10. Assert the email body contains the client's phone number `0901234567`
11. Assert the email body contains the service name
12. Assert the email body contains the appointment date and time
13. Assert the email body does NOT contain `null`
14. Assert the email body does NOT contain `{{ `

**Expected Result**: Shop owner receives a properly formatted HTML notification with all client booking details.

---

### Scenario 4: Appointment cancellation email sent to client
**Actor**: Shop owner cancelling an appointment via the admin UI (or API)
**Goal**: Confirm the client receives an HTML cancellation email with service and time details
**Priority**: Critical

**Steps**:
1. Navigate to `http://localhost:8025` and click "Delete all"
2. Navigate to `BASE_URL` and log in as the shop owner
3. Navigate to the appointments list [VERIFY: exact admin route for appointment management]
4. Find a future appointment for a client who has an email on record
5. Click the Cancel / Delete button on that appointment
6. Confirm the cancellation in any confirmation dialog
7. Assert the appointment is removed from the list or marked as cancelled
8. Navigate to `http://localhost:8025` and wait for a new email
9. Click the most recent email addressed to the client
10. Assert the subject contains a cancellation phrase [VERIFY: confirm exact subject — e.g., `Lịch hẹn đã bị hủy`]
11. Click the "HTML" tab
12. Assert the email body contains the client's first name
13. Assert the email body contains the service name
14. Assert the email body contains the appointment date and time
15. Assert the email body contains the shop phone number (if the shop has one)
16. Assert the email body does NOT contain `null`
17. Assert the locale is Vietnamese (`vi`) — cancellation emails always send in Vietnamese per spec

**Expected Result**: Client receives an HTML cancellation email in Vietnamese with service name, date/time, and shop contact info.
**Notes**: Per spec BR: `appointment_cancelled` always uses `vi` locale regardless of any client locale preference.

---

### Scenario 5: Password reset email — HTML template with reset link
**Actor**: Registered user requesting a password reset
**Goal**: Confirm the password reset email is now an HTML email with a working reset link and expiry notice
**Priority**: Critical

**Steps**:
1. Navigate to `http://localhost:8025` and click "Delete all"
2. Navigate to `BASE_URL/forgot-password` [VERIFY: confirm exact frontend route for password reset]
3. Fill the email field with a registered user's email address
4. Click the Submit / Send Reset Link button
5. Assert a confirmation message is shown (e.g., `Kiểm tra email của bạn`)
6. Navigate to `http://localhost:8025` and wait for a new email
7. Click the most recent email
8. Assert the subject contains a password reset phrase [VERIFY: exact subject — e.g., `Đặt lại mật khẩu`]
9. Click the "HTML" tab
10. Assert the email body contains the user's first name
11. Assert the email body contains a reset link (an `<a href="...">` containing the frontend reset URL with a token)
12. Assert the reset link URL starts with the configured `frontendUrl` [VERIFY: confirm `FRONTEND_URL` env value]
13. Assert the email body contains the expiry notice — `60` minutes [VERIFY: confirm exact text — e.g., `60 phút` in vi]
14. Assert the email body does NOT contain `null`
15. Assert the email body does NOT contain `{{ `
16. Click the "Source" tab in Mailpit
17. Assert the source does NOT contain `<link rel="stylesheet"` (no external CSS links — CSS must be inlined)
18. Assert the source contains `style="` attributes on HTML elements (evidence of CSS inlining)

**Expected Result**: An HTML email arrives with the user's name, a clickable reset URL with token, and a 60-minute expiry notice. CSS is inlined (no external stylesheet links).
**Notes**: `[NOT YET IMPLEMENTED]` — the current codebase sends a plain-text password reset email. This scenario verifies the migration to `TemplatedEmail` with `password_reset.html.twig`.

---

### Scenario 6: Password reset email — English locale
**Actor**: Registered user with locale set to `en`
**Goal**: Confirm the password reset email uses the user's locale
**Priority**: High

**Steps**:
1. Log in as a user whose `locale` is set to `en` (or update via API: `PATCH /api/v1/profile` with `{"locale":"en"}`)
2. Log out
3. Navigate to `http://localhost:8025` and click "Delete all"
4. Navigate to `BASE_URL/en/forgot-password` [VERIFY: locale-prefixed route]
5. Fill the email field with the user's email and submit
6. Navigate to `http://localhost:8025` and open the new email
7. Assert the subject is in English [VERIFY: English subject string]
8. Click the "HTML" tab
9. Assert the body contains English text for the expiry notice (e.g., `60 minutes`, not `60 phút`)
10. Assert the body does NOT contain Vietnamese labels

**Expected Result**: Subject and body are in English for a user with `locale=en`.

---

### Scenario 7: CSS is inlined — no external stylesheets in email source
**Actor**: Email client / deliverability check
**Goal**: Confirm the `symfony/css-inliner-extra` inlining is applied and no `<link>` stylesheet tags survive in the email HTML
**Priority**: High

**Steps**:
1. Trigger a booking confirmation email (follow steps 1–10 of Scenario 1)
2. Navigate to `http://localhost:8025` and open the email
3. Click the "Source" tab (raw MIME source)
4. Assert the source does NOT contain `<link rel="stylesheet"`
5. Assert the source does NOT contain `<style>` tags containing non-trivial CSS (a reset style block is acceptable; the main layout CSS must be inlined)
6. Assert the source contains multiple `style="..."` inline attributes on `<td>`, `<table>`, `<p>`, and similar elements

**Expected Result**: All CSS is inlined on HTML elements. No external stylesheet references survive.
**Notes**: The `{% apply inline_css %}` Twig block in the base layout handles this. If `symfony/css-inliner-extra` is not installed, emails will have unresolved `<style>` blocks or missing inline styles.

---

### Scenario 8: Plain-text fallback is present in email
**Actor**: Email client that does not render HTML
**Goal**: Confirm every email has a `text/plain` MIME part as a fallback
**Priority**: High

**Steps**:
1. Trigger a booking confirmation email (follow Scenario 1 steps 1–10)
2. Navigate to `http://localhost:8025` and open the email
3. Click the "Plain Text" tab in Mailpit
4. Assert the plain-text tab contains readable content (client name, service name, date/time)
5. Assert the plain-text content is NOT empty
6. Assert the plain-text content does NOT contain raw HTML tags (`<td>`, `<br>`, etc.)

**Expected Result**: Email has a `text/plain` MIME part with the same core information as the HTML version, without HTML markup.

---

### Scenario 9: Shared layout renders — header and footer present in all email types
**Actor**: Any email recipient
**Goal**: Confirm the base layout wrapper (header with app/shop name, footer with contact info) is present in all 4 email types
**Priority**: Medium

**Steps**:
1. Trigger a booking confirmation email and open the HTML view in Mailpit
2. Assert the email header contains the shop name or app name (from the layout `{% block header %}`)
3. Assert the email footer is present (contains shop contact info or generic app footer)
4. Trigger a new booking notification email (complete a booking as shop owner)
5. Open the HTML view in Mailpit for the notification email
6. Assert the header and footer are present in this email too
7. Trigger a cancellation email (cancel an appointment)
8. Open the HTML view and assert header and footer are present
9. Trigger a password reset email (submit forgot-password form)
10. Open the HTML view and assert header and footer are present

**Expected Result**: All 4 email types share the same layout structure (header + content block + footer). No email renders without the layout wrapper.

---

## Edge Cases & Negative Tests

### Edge Case 1: Shop has no address or phone — no "null" displayed
**Scenario**: A shop with `shopAddress = null` and `shopPhone = null` sends a booking confirmation — the template must omit those lines entirely
**Steps**:
1. Ensure the test shop has no address and no phone set [VERIFY: update via admin UI or API: `PATCH /api/v1/shop/profile` with `{"address":null,"phone":null}`]
2. Navigate to `http://localhost:8025` and click "Delete all"
3. Complete a public booking on the shop
4. Open the booking confirmation email in Mailpit
5. Click the "HTML" tab
6. Assert the email body does NOT contain the text `null`
7. Assert the email body does NOT contain a blank address line (an empty `<td>` or `<p>` where address would appear)
8. Assert the email body does NOT contain `{{ shopAddress }}` or `{{ shopPhone }}`

**Expected Result**: Address and phone lines are completely absent from the email when those fields are null. No "null" text, no empty placeholder rows.

---

### Edge Case 2: Very long shop name / service name does not break layout
**Scenario**: Template variable contains an unusually long string
**Steps**:
1. Set the test shop's name to a 60-character string [VERIFY: update via admin UI]
2. Trigger a booking confirmation email
3. Open the HTML view in Mailpit
4. Assert the email renders without broken table columns or overflowing text
5. Assert the shop name is visible and not truncated to empty

**Expected Result**: Long strings wrap gracefully inside the table-based layout. No layout breaking.

---

### Edge Case 3: Unrendered Twig variables indicate a missing template context key
**Scenario**: A handler passes incomplete context to `TemplatedEmail`, leaving a Twig variable unresolved
**Steps**:
1. (This is a catch-all regression check — run after every scenario above)
2. For each email opened in Mailpit, click the "HTML" tab
3. Assert the body does NOT contain `{{ ` (double curly brace — unrendered Twig output)
4. Assert the body does NOT contain `{%` (unrendered Twig block tag)

**Expected Result**: All Twig variables are resolved. No raw template syntax visible in any email.

---

### Edge Case 4: Email dispatch failure does not fail the booking
**Scenario**: Mailpit is stopped mid-test, simulating a mailer transport failure
**Steps**:
1. Stop Mailpit (`Ctrl+C` or `docker stop mailpit`)
2. Navigate to `BASE_URL/shop/test-shop` and complete a booking
3. Assert the success message is still displayed on the booking page (booking was created)
4. Assert no error toast or error page is shown to the client
5. Restart Mailpit

**Expected Result**: The booking succeeds and the client sees the success screen even though email delivery failed. Email sending is async via Messenger — transport failures must not bubble up to the HTTP response.
**Notes**: Per spec BR-1: "A delivery failure must never cause the triggering operation to fail." This tests that the async queue absorbs the failure silently.

---

## Data Requirements

| Requirement | Source |
|---|---|
| Mailpit running on host (SMTP port 1025, web UI port 8025) | Start manually: `mailpit` binary or `docker run -p 1025:1025 -p 8025:8025 axllent/mailpit` |
| Test shop `test-shop` with at least one service and available slot | Pre-seeded fixture |
| Shop owner account with a confirmed email address | Pre-seeded fixture |
| A registered user account with known email and `locale=en` | Pre-seeded or created via registration flow |
| A future bookable appointment for cancellation scenarios | Create via public booking in Scenario 1 before running Scenario 4 |
| Test shop with `shopAddress = null` and `shopPhone = null` | Update via admin UI or API before Edge Case 1 |

## Coverage Gaps

| Gap | Reason |
|---|---|
| Actual email rendering in Gmail / Apple Mail / Outlook | Requires real email delivery to those inboxes. Table-based HTML + inline CSS is the mitigation; visual regression testing (e.g., Litmus) is out of scope. |
| Password reset link token actually works (redirect + form) | Covered by the existing auth/password-reset test plan, not this plan. This plan only verifies the email HTML content. |
| Messenger queue retry on email failure | Requires simulating transport failure at the SMTP level and inspecting Messenger queue state — out of scope for Playwright testing. |
| Email sending when no client email exists (public booking without email) | The public booking form may not capture a client email. If the confirmation email is gated on email existence, Scenario 1 requires a pre-existing client record with an email. [VERIFY: confirm public booking email flow] |
| Reminder emails (Feature #14) reusing this layout | Out of scope — covered by Feature #14's own test plan. |
