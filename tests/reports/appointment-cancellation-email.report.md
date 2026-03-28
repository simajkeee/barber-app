# Test Report: Appointment Cancellation Email

**Executed**: 2026-03-24T17:00:00Z
**Plan file**: `development/features/10-email-notifications/appointment-cancellation-email.plan.md`
**Result**: FAILED

---

## Summary

| Metric               | Value |
|----------------------|-------|
| Total Scenarios      | 7     |
| Passed               | 6     |
| Failed               | 1     |
| Skipped              | 0     |
| Edge Cases Total     | 4     |
| Edge Cases Passed    | 2     |
| Edge Cases Failed    | 1     |
| Edge Cases Skipped   | 1     |
| Coverage Gaps        | 2     |

---

## Test Accounts Used

| Account | Email | Password | State |
|---------|-------|----------|-------|
| A (barber) | test-account-a@test.com | Password1! | Shop: Test Shop A (phone 0901234567) |
| Email Client | cancel-test@example.com | — | Client with email address |
| NoEmail Client | (none) | — | Client with no email address |

**Appointments created for this run**:
| Label | Client | Time | Used in |
|-------|--------|------|---------|
| Appt A | Email Client | Mar 25, 10:00 | Sc1, Sc3, Sc4 |
| Appt B | NoEmail Client | Mar 25, 11:00 | Sc2 |
| Appt C | Email Client | Mar 25, 12:00 | Sc5 |
| Appt D | Email Client | Mar 25, 13:00 | Sc6 |
| Appt E (dashboard-created) | Email Client | Mar 25, 14:00 | Sc7 |

---

## Scenario Results

### Scenario 1: Cancellation email sent when barber cancels a scheduled appointment
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear Mailpit inbox | ✅ PASS | 0 messages confirmed |
| 2 | Log in as barber | ✅ PASS | test-account-a@test.com |
| 3 | Navigate to appointment A detail | ✅ PASS | `/en/dashboard/appointments/{id}` |
| 4 | Click "Cancel" button | ✅ PASS | Confirmation dialog appeared |
| 5 | Confirm cancellation | ✅ PASS | Status changed to "Cancelled" |
| 6 | Run Messenger worker | ✅ PASS | `php bin/console messenger:consume async --limit=10 --time-limit=8` |
| 7 | Navigate to MailHog | ✅ PASS | — |
| 8 | Assert email addressed to cancel-test@example.com | ✅ PASS | 1 message in inbox |
| 9 | Assert subject contains "Lịch hẹn đã bị hủy" | ✅ PASS | Subject: "Lịch hẹn đã bị hủy - Test Shop A" |
| 10 | Assert body contains client first name | ✅ PASS | "Xin chào Email," |
| 11 | Assert body contains service name | ✅ PASS | "Classic Cut" |
| 12 | Assert body contains shop name | ✅ PASS | "Test Shop A" |
| 13 | Assert body contains shop phone | ✅ PASS | "0901234567" |

---

### Scenario 2: No cancellation email when client has no email address
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear Mailpit inbox | ✅ PASS | — |
| 2 | Navigate to appointment B (NoEmail Client) | ✅ PASS | — |
| 3 | Click "Cancel" → confirm | ✅ PASS | Status: Cancelled |
| 4 | Run Messenger worker | ✅ PASS | — |
| 5 | Navigate to MailHog | ✅ PASS | — |
| 6 | Assert inbox is empty | ✅ PASS | 0 messages |
| 7 | Assert no error on dashboard | ✅ PASS | Dashboard rendered normally |

---

### Scenario 3: Cancellation email subject includes shop name
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Use cancellation email from Sc1 | ✅ PASS | Already in MailHog from Sc1 |
| 2 | Assert subject is "Lịch hẹn đã bị hủy - Test Shop A" | ✅ PASS | Exact match including shop name |

**Note**: Verified using the email from Scenario 1 (same inbox, no re-cancellation needed).

---

### Scenario 4: Cancellation email body contains correct appointment details
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Open cancellation email from Sc1 | ✅ PASS | — |
| 2 | Assert body contains client first name | ✅ PASS | "Xin chào Email," |
| 3 | Assert body contains service name | ✅ PASS | "Classic Cut" |
| 4 | Assert body contains start time in Asia/Ho_Chi_Minh (UTC+7) | ❌ FAIL | Shows "03:00" (UTC); expected "10:00" (UTC+7) |
| 5 | Assert body contains shop name | ✅ PASS | "Test Shop A" |
| 6 | Assert body contains shop phone | ✅ PASS | "0901234567" |

**Failure Detail**:
- **Failed step**: 4 — Start time timezone conversion
- **Expected**: Time displayed as Asia/Ho_Chi_Minh (UTC+7): `"10:00"` for an appointment stored at 03:00 UTC
- **Actual**: Time displayed as raw UTC: `"03:00, Wed, 25/03/2026"`
- **Bug**: The email template does not convert the appointment start time from UTC to Asia/Ho_Chi_Minh before rendering. Every cancellation email will show UTC times, which are 7 hours behind the local time.

---

### Scenario 5: Completing an appointment does NOT trigger a cancellation email
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear Mailpit inbox | ✅ PASS | — |
| 2 | Navigate to appointment C | ✅ PASS | — |
| 3 | Click "Mark Complete" → confirm | ✅ PASS | Status: Completed |
| 4 | Run Messenger worker | ✅ PASS | — |
| 5 | Assert MailHog inbox is empty | ✅ PASS | 0 messages — no email sent |

---

### Scenario 6: Marking appointment as no-show does NOT trigger a cancellation email
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear Mailpit inbox | ✅ PASS | — |
| 2 | Navigate to appointment D | ✅ PASS | — |
| 3 | Click "No Show" → confirm | ✅ PASS | Status: No Show |
| 4 | Run Messenger worker | ✅ PASS | — |
| 5 | Assert MailHog inbox is empty | ✅ PASS | 0 messages — no email sent |

---

### Scenario 7: Dashboard booking created by barber is also cancellable with email
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Clear Mailpit inbox | ✅ PASS | 0 messages |
| 2 | Navigate to `/en/dashboard/appointments/create` | ✅ PASS | Form renders correctly |
| 3 | Select client "Email Client", service "Classic Cut (30min)", date Mar 25, time 14:00 | ✅ PASS | All fields filled |
| 4 | Click "Save" | ✅ PASS | Appointment created; redirected to detail page |
| 5 | Click "Cancel" → confirm | ✅ PASS | Status: Cancelled |
| 6 | Run Messenger worker | ✅ PASS | — |
| 7 | Navigate to MailHog | ✅ PASS | — |
| 8 | Assert email present addressed to cancel-test@example.com | ✅ PASS | Subject: "Lịch hẹn đã bị hủy - Test Shop A" |

**Note**: Body fields (client name, service, shop) verified present. Timezone bug same as Sc4 — email shows "07:00" (UTC) instead of "14:00" (UTC+7).

---

## Edge Case Results

### Edge Case 1: Cancelling an already-cancelled appointment does not re-send email
**Result**: ✅ PASSED (UI-level verification only)

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to already-cancelled appointment (from Sc7) | ✅ PASS | — |
| 2 | Inspect available action buttons | ✅ PASS | Only "Back" button present — no Cancel, Mark Complete, or No Show |
| 3 | Assert inbox is empty (cleared before check) | ✅ PASS | 0 messages |

**Note**: The UI removes action buttons for terminal-state appointments (Cancelled), preventing a second cancellation via the dashboard. Backend API-level protection (attempting a direct PATCH with a cancelled ID) was not tested — this would require raw HTTP requests with a JWT token.

---

### Edge Case 2: Cancellation email From address matches MAILER_SENDER
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Open cancellation email from Sc7 | ✅ PASS | — |
| 2 | Assert From header is `noreply@barberpro.com` | ✅ PASS | Exact match with `MAILER_SENDER` env value |

---

### Edge Case 3: Cancellation does not fail when Messenger worker is stopped
**Result**: ⏭ SKIPPED

**Reason**: Cannot stop/restart the Symfony Messenger worker in this environment — Docker socket is not accessible, and stopping the background process via `php bin/console messenger:stop-workers` would affect other running tests. Manual verification of queue persistence would require Docker access.

---

### Edge Case 4: Start time in cancellation email is in Asia/Ho_Chi_Minh, not UTC
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Check displayed time in Sc1 email (10:00 appointment) | ✅ PASS | — |
| 2 | Assert time is "10:00" (UTC+7) | ❌ FAIL | Displays "03:00" (UTC) |
| 3 | Check displayed time in Sc7 email (14:00 appointment) | ✅ PASS | — |
| 4 | Assert time is "14:00" (UTC+7) | ❌ FAIL | Displays "07:00" (UTC) |

**Failure Detail**: Both verified appointments show UTC times. The offset is consistently -7h (UTC stored value displayed raw). This confirms the template/service does not apply `Asia/Ho_Chi_Minh` timezone conversion.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | Backend API-level re-cancel protection (EC1) | Requires raw HTTP request with JWT — not possible via Playwright MCP without extracting token | Test via PHPUnit functional test or manual `curl` with a valid token; verify API returns 4xx on second cancel attempt |
| 2 | Worker-stopped queue persistence (EC3) | Docker socket not accessible; cannot start/stop worker in isolation | Test manually by stopping the worker, cancelling an appointment, asserting UI success, then restarting and verifying email arrival |

---

## Recommendations

- **Bug — UTC timezone in cancellation email**: The appointment start time is rendered as UTC in the email body. Fix: apply `Asia/Ho_Chi_Minh` timezone conversion in the email template or the message handler before passing the time to the Twig template. Affects all cancellation emails. Confirmed on two separate appointments (10:00 → shows 03:00; 14:00 → shows 07:00).
- **EC3 (worker down)**: Verify manually that cancelling an appointment while the worker is stopped still returns a success response in the UI. The async queue should absorb the failure gracefully.
- **All other flows confirmed clean**: Email dispatched only for CANCEL status (not COMPLETED/NO_SHOW); client without email receives no email and no error; dashboard-created appointments behave identically to public bookings for cancellation emails.
