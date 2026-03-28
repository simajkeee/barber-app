# Test Plan: Trial Expiry Email Notifications

## Overview
This module covers the automated lifecycle emails for barbers on a 30-day PRO free trial.
Two console commands drive the feature: `app:subscriptions:expire-trials` (updated) downgrades
overdue trials to FREE and dispatches a "trial ended" email; `app:subscriptions:send-trial-reminders`
(new) finds trials expiring within ~3 days and dispatches a "trial ending soon" reminder.
There is no new frontend UI — observable browser-side effects are the subscription page state after
command execution and emails captured by the test mailbox. Testing is primarily done via CLI
execution followed by API/UI assertions and mailbox inspection.

## Scope
- **In scope**: `app:subscriptions:expire-trials` command observable effects (subscription downgraded to FREE,
  "trial ended" email dispatched), `app:subscriptions:send-trial-reminders` command observable effects
  ("trial ending" reminder email dispatched, `trialReminderSentAt` set), email content in both `vi`
  and `en` locales, deduplication guards, idempotency of both commands.
- **Out of scope**: MoMo payment flow (Feature #26), PRO renewal UI, subscription admin override
  endpoints, SMS/Zalo notifications, PHPUnit unit tests for the service layer (separate test suite).

## Prerequisites
- Application running at `BASE_URL` (frontend) and `API_BASE_URL` (backend)
- Docker container running with PHP service accessible (`docker compose exec php`)
- At least one registered barber account with `locale = vi` (`TEST_BARBER_VI_EMAIL` / password)
- At least one registered barber account with `locale = en` (`TEST_BARBER_EN_EMAIL` / password)
- Test mailbox accessible (Mailpit at `http://localhost:8025` or equivalent) [VERIFY: mail catcher host in docker-compose]
- `MESSENGER_TRANSPORT_DSN=sync://` set in `.env.test` so emails dispatch synchronously during tests
- Admin account available for direct subscription manipulation between test runs
- `APP_URL=http://localhost:3000` set in `.env.test`

---

## Test Scenarios

### Scenario 1: `expire-trials` command downgrades an overdue trial to FREE
**Actor**: System / cron (observable by barber via subscription page)
**Goal**: Confirm a PRO trial that has passed `trialEndsAt` is moved to FREE plan
**Priority**: Critical

**Steps**:
1. Pre-condition: Set barber's subscription to PRO trial with `trialEndsAt = yesterday` via admin endpoint or direct DB fixture [VERIFY: admin endpoint path for trial setup]
2. Send `GET /api/v1/subscription` with barber JWT
3. Assert response contains `"plan": "pro"` (pre-condition confirmed)
4. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
5. Assert command output contains text "Expired 1 trial"
6. Send `GET /api/v1/subscription` with barber JWT
7. Assert response contains `"plan": "free"`
8. Assert response contains `"status": "active"`
9. Navigate to `BASE_URL/dashboard/subscription`
10. Assert page displays FREE plan indicator
11. Assert page does NOT display active PRO indicator

**Expected Result**: Subscription is downgraded to FREE; dashboard reflects the change.

---

### Scenario 2: `expire-trials` command dispatches "trial ended" email (vi locale)
**Actor**: System (barber receives email)
**Goal**: Barber with `locale = vi` receives the Vietnamese "trial ended" email
**Priority**: Critical

**Steps**:
1. Pre-condition: Set `TEST_BARBER_VI_EMAIL` account's subscription to PRO trial with `trialEndsAt = yesterday`
2. Clear the test mailbox (delete all messages in Mailpit)
3. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
4. Assert command output contains "Expired 1 trial"
5. Navigate to `http://localhost:8025` (Mailpit inbox) [VERIFY: mailbox URL]
6. Assert at least one email is visible in the inbox
7. Click the email addressed to `TEST_BARBER_VI_EMAIL`
8. Assert email subject contains text "Gói dùng thử của bạn đã kết thúc"
9. Assert email body contains text "đã kết thúc"
10. Assert email body contains text "FREE"
11. Assert email body contains a link to `BASE_URL/dashboard/subscription`
12. Assert email body contains text "299,000₫"
13. Assert email body contains text "Nâng cấp ngay"

**Expected Result**: One Vietnamese "trial ended" email received with correct subject, downgrade notice, price, and upgrade CTA.

---

### Scenario 3: `expire-trials` command dispatches "trial ended" email (en locale)
**Actor**: System (barber receives email)
**Goal**: Barber with `locale = en` receives the English "trial ended" email
**Priority**: High

**Steps**:
1. Pre-condition: Set `TEST_BARBER_EN_EMAIL` account's subscription to PRO trial with `trialEndsAt = yesterday`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
4. Navigate to Mailpit inbox
5. Click the email addressed to `TEST_BARBER_EN_EMAIL`
6. Assert email subject contains text "Your free trial has ended"
7. Assert email body contains text "has ended"
8. Assert email body contains text "FREE plan"
9. Assert email body contains text "299,000₫"
10. Assert email body contains text "Upgrade Now"
11. Assert email body contains a link to `BASE_URL/dashboard/subscription`

**Expected Result**: English "trial ended" email with correct subject and content.

---

### Scenario 4: `send-trial-reminders` command dispatches "trial ending" reminder email (vi locale)
**Actor**: System (barber receives reminder)
**Goal**: Barber with 3 days left on trial (within 2–4 day window) receives the Vietnamese reminder
**Priority**: Critical

**Steps**:
1. Pre-condition: Set `TEST_BARBER_VI_EMAIL` account's subscription to PRO trial with `trialEndsAt = today + 3 days` and `trialReminderSentAt = null`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
4. Assert command output contains "Sent 1 trial reminder"
5. Navigate to Mailpit inbox
6. Click the email addressed to `TEST_BARBER_VI_EMAIL`
7. Assert email subject contains text "Gói dùng thử của bạn sẽ kết thúc trong 3 ngày"
8. Assert email body contains the expiry date formatted as `dd/mm/yyyy`
9. Assert email body contains text "sẽ hết hạn"
10. Assert email body contains text "299,000₫"
11. Assert email body contains text "Nâng cấp ngay"
12. Assert email body contains a link to `BASE_URL/dashboard/subscription`

**Expected Result**: One Vietnamese reminder email with correct subject, expiry date, and upgrade CTA.

---

### Scenario 5: `send-trial-reminders` command dispatches "trial ending" reminder email (en locale)
**Actor**: System (barber receives reminder)
**Goal**: Barber with `locale = en` receives the English reminder
**Priority**: High

**Steps**:
1. Pre-condition: Set `TEST_BARBER_EN_EMAIL` account's subscription to PRO trial with `trialEndsAt = today + 3 days` and `trialReminderSentAt = null`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
4. Navigate to Mailpit inbox
5. Click the email addressed to `TEST_BARBER_EN_EMAIL`
6. Assert email subject contains text "Your free trial ends in 3 days"
7. Assert email body contains the expiry date formatted as month name + day + year (e.g. "April 2, 2026")
8. Assert email body contains text "expires on"
9. Assert email body contains text "Upgrade Now"

**Expected Result**: English reminder email with date formatted as `F j, Y`.

---

### Scenario 6: `send-trial-reminders` sets `trialReminderSentAt` on the subscription
**Actor**: System
**Goal**: Confirm the subscription is marked after the reminder is sent (deduplication guard)
**Priority**: Critical

**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = today + 3 days` and `trialReminderSentAt = null`
2. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
3. Assert command output contains "Sent 1 trial reminder"
4. Send `GET /api/v1/subscription` with barber JWT [VERIFY: whether `trialReminderSentAt` is exposed in the API response; if not, check via admin endpoint or DB query]
5. Assert `trialReminderSentAt` is no longer null (check via admin debug endpoint or direct DB query: `SELECT trial_reminder_sent_at FROM subscriptions WHERE ...`)

**Expected Result**: `trialReminderSentAt` is set to a non-null timestamp after the command runs.

---

### Scenario 7: `expire-trials` is idempotent — running twice downgrades only once
**Actor**: System
**Goal**: Running the command a second time when no new trials have expired produces no changes and no duplicate emails
**Priority**: High

**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = yesterday`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
4. Assert command output contains "Expired 1 trial"
5. Assert one email is in the mailbox
6. Run command again: `docker compose exec php bin/console app:subscriptions:expire-trials`
7. Assert command output contains "Expired 0 trial"
8. Assert mailbox still contains exactly one email (no duplicate sent)
9. Send `GET /api/v1/subscription` with barber JWT
10. Assert plan is still `"free"` (not double-processed)

**Expected Result**: Second run produces 0 expirations and no additional emails.

---

### Scenario 8: `send-trial-reminders` is idempotent — deduplication guard prevents second reminder
**Actor**: System
**Goal**: Running the reminder command twice sends only one email per trial
**Priority**: High

**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = today + 3 days` and `trialReminderSentAt = null`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
4. Assert command output contains "Sent 1 trial reminder"
5. Assert one email in the mailbox
6. Run command again: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
7. Assert command output contains "Sent 0 trial reminder"
8. Assert mailbox still contains exactly one email

**Expected Result**: Second run sends 0 reminders because `trialReminderSentAt` is already set.

---

### Scenario 9: Subscription page correctly shows FREE state after trial expiry
**Actor**: Barber (just had trial expired)
**Goal**: Confirm the dashboard UI reflects the FREE plan without requiring a page reload or manual state reset
**Priority**: High

**Steps**:
1. Pre-condition: Run `expire-trials` command with at least one expired trial (see Scenario 1)
2. Navigate to `BASE_URL/dashboard/subscription`
3. Wait for subscription data to load
4. Assert page contains FREE plan indicator [VERIFY: exact text or badge label for FREE plan in UI]
5. Assert appointment limit text references "50" or equivalent FREE plan cap [VERIFY: if this is shown on the subscription page]
6. Assert the upgrade/PRO CTA button is visible (barber can now upgrade via MoMo)

**Expected Result**: Subscription page shows FREE plan state with upgrade CTA after trial is expired by the command.

---

## Edge Cases & Negative Tests

### Edge Case 1: Trial expiring in 1 day — outside the 2–4 day window, no reminder sent
**Scenario**: A barber has 1 day left on their trial, which is below the reminder window
**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = today + 1 day` and `trialReminderSentAt = null`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
4. Assert command output contains "Sent 0 trial reminder"
5. Assert mailbox contains 0 emails

**Expected Result**: No reminder sent — subscription is outside the 2–4 day window.

---

### Edge Case 2: Trial expiring in 6 days — outside the 2–4 day window, no reminder sent
**Scenario**: A barber has 6 days left on their trial, which is above the reminder window
**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = today + 6 days` and `trialReminderSentAt = null`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
4. Assert command output contains "Sent 0 trial reminder"
5. Assert mailbox contains 0 emails

**Expected Result**: No reminder sent — too far from expiry.

---

### Edge Case 3: Already-downgraded trial — `expire-trials` does not re-process
**Scenario**: A subscription with `trialEndsAt` in the past but already on FREE plan is not touched
**Steps**:
1. Pre-condition: Subscription has `plan = free`, `trialEndsAt = yesterday`, `status = active` (already downgraded in a prior run)
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
4. Assert command output contains "Expired 0 trial"
5. Assert mailbox contains 0 emails

**Expected Result**: Command recognises the subscription is already on FREE and skips it entirely.

---

### Edge Case 4: `expire-trials` runs on a subscription with `trialReminderSentAt` already set
**Scenario**: Barber already received a 3-day reminder, now the trial has actually expired
**Steps**:
1. Pre-condition: Set subscription to PRO trial with `trialEndsAt = yesterday` and `trialReminderSentAt = 4 days ago`
2. Clear the test mailbox
3. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
4. Assert command output contains "Expired 1 trial"
5. Assert mailbox contains exactly 1 email (only the "trial ended" email — no second reminder)
6. Assert email subject contains "Gói dùng thử của bạn đã kết thúc" or "Your free trial has ended"

**Expected Result**: "Trial ended" email is sent once; no duplicate "trial ending" reminder email is sent.

---

### Edge Case 5: No trials expiring — commands output zero count
**Scenario**: All subscriptions are either on FREE or have active (non-expired) PRO trials
**Steps**:
1. Pre-condition: Ensure no subscription has `trialEndsAt` in the past or within the 2–4 day window
2. Run command: `docker compose exec php bin/console app:subscriptions:expire-trials`
3. Assert command output contains "Expired 0 trial"
4. Run command: `docker compose exec php bin/console app:subscriptions:send-trial-reminders`
5. Assert command output contains "Sent 0 trial reminder"

**Expected Result**: Both commands exit successfully with zero-count messages and no emails dispatched.

---

## Data Requirements

| Data | Source | Notes |
|------|--------|-------|
| Barber with `locale = vi`, PRO trial active | Register + admin setup | Reset `trialEndsAt` and `trialReminderSentAt` between test runs |
| Barber with `locale = en`, PRO trial active | Register + admin setup | Separate account to avoid cross-locale contamination |
| Subscription with `trialEndsAt = yesterday` (expired) | Admin endpoint or DB fixture | Required for Scenarios 1, 2, 3, 7 |
| Subscription with `trialEndsAt = today + 3 days` (window) | Admin endpoint or DB fixture | Required for Scenarios 4, 5, 6, 8 |
| Subscription with `trialEndsAt = today + 1 day` (outside window) | Admin endpoint or DB fixture | Required for Edge Case 1 |
| Subscription with `trialEndsAt = today + 6 days` (outside window) | Admin endpoint or DB fixture | Required for Edge Case 2 |
| Mailpit (or equivalent) test mailbox | Running in Docker | Required for all email content assertions |
| `MESSENGER_TRANSPORT_DSN=sync://` | `.env.test` | Ensures emails send synchronously during test runs |

---

## Coverage Gaps

| Gap | Reason |
|-----|--------|
| Email rendering in actual email clients | Mailpit previews HTML but does not test cross-client rendering (Outlook, Gmail, Apple Mail) |
| Cron scheduling correctness (01:00 / 02:00 ICT) | System cron is infrastructure-level; not testable via Playwright or CLI in the app container |
| Async Messenger worker email delivery | Sync transport used in tests; async delivery in production depends on worker process uptime |
| Fallback to `vi` locale when `User.locale` is null | Requires a legacy user fixture with no locale set; noted as open question in spec |
| Manually cancelled trial exclusion | Spec notes cancelled subscriptions should be excluded; requires a fixture with `status = CANCELLED` to verify the query guard works |
| Email spam/deliverability | Not testable in this environment |
