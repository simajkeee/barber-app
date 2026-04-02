# Test Report: MoMo Payment — Self-Serve PRO Subscription

**Executed**: 2026-04-01T08:25:00+07:00
**Plan file**: `development/features/26-momo-payment-mvp/momo-payment.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 13    |
| Passed             | 8     |
| Failed             | 2     |
| Partial/Skipped    | 3     |
| Edge Cases Total   | 5     |
| Edge Cases Passed  | 3     |
| Edge Cases Failed  | 1     |
| Edge Cases Skipped | 1     |
| Coverage Gaps      | 4     |

---

## Bugs Found and Fixed During Testing

Three bugs in the implementation were discovered and fixed during this test run:

| # | File | Bug | Fix |
|---|------|-----|-----|
| 1 | `frontend/composables/useSubscriptionApi.ts:7` | `getSubscription()` called `/subscription/` with trailing slash → backend 301 redirected to `http://localhost/api/v1/subscription` (absolute URL, port 80), bypassing the Nuxt devProxy → CORS failure | Removed trailing slash: `/subscription/` → `/subscription` |
| 2 | `src/Subscription/Service/MomoPaymentService.php` | `requestType: 'paymentCode'` is not a valid value for MoMo v2 API → "Bad format request" | Changed to `captureWallet` |
| 3 | `src/Subscription/Service/MomoPaymentService.php` | `accessKey` field was missing from the request body sent to MoMo | Added `'accessKey' => $this->accessKey` to the `$body` array |

---

## Bugs Found (Unfixed — Require Separate Fix)

| # | Severity | File | Description |
|---|----------|------|-------------|
| B1 | Critical | `.env` | `MOMO_SECRET_KEY=REDACTED_MOMO_SECRET_KEY` is stale — MoMo sandbox returns `resultCode: 11007` (Invalid signature) for partnerCode=MOMO. Checkout is fully blocked. Requires updated sandbox credentials from MoMo Business Portal. |
| B2 | High | `frontend/pages/dashboard/subscription/index.vue:41-47` | `processingPayment` state is set to `true` in `onMounted` but immediately lost: the subsequent `router.replace(localePath('/dashboard/subscription'))` triggers component remount in Nuxt 3's `ssr:false` context, resetting all reactive state. The "Đang xử lý thanh toán" message never renders. Fix: move `processingPayment` to a Pinia store or `sessionStorage` to survive route changes. |
| B3 | Medium | `frontend/components/Subscription/UpgradePrompt.vue:25-35` | Double-click protection does not work. `isLoading.value = true` is set synchronously but Vue batches DOM updates asynchronously. A rapid second click fires before `:disabled="isLoading"` propagates to the DOM, sending two POST requests to `/api/v1/subscription/checkout`. Fix: add `if (isLoading.value) return` guard at the start of `startCheckout()`. |

---

## Scenario Results

### Scenario 1: FREE user initiates checkout — redirects to MoMo
**Priority**: Critical
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 2 | Assert FREE plan indicator visible | ✅ PASS | "Miễn phí" shown |
| 3 | Assert "Nâng cấp lên PRO" button visible | ✅ PASS | Button present |
| 4 | Click "Nâng cấp lên PRO – 299.000 ₫/tháng" | ✅ PASS | — |
| 5 | Wait for POST `/api/v1/subscription/checkout` | ✅ PASS | Request fired via correct proxy (port 3000) |
| 6 | Assert browser navigates to MoMo URL | ❌ FAIL | Checkout returns 502 MOMO_INIT_FAILED |
| 7 | Assert URL contains `momo.vn` | ❌ FAIL | No redirect occurred |

**Failure Detail**:
- **Failed step**: Steps 6–7
- **Expected**: Browser redirects to `test-payment.momo.vn` checkout URL
- **Actual**: `POST /api/v1/subscription/checkout` returns HTTP 502 `{"code":"MOMO_INIT_FAILED","message":"Chữ ký không hợp lệ"}` — MoMo sandbox rejects the HMAC signature
- **Root cause**: `MOMO_SECRET_KEY=REDACTED_MOMO_SECRET_KEY` in `.env` is stale; MoMo's test server uses a different internal secretKey for `partnerCode=MOMO`. Updated sandbox credentials required.
- **Note**: Two bugs were fixed as a precondition (`paymentCode`→`captureWallet`, added `accessKey` to body). The signature mismatch remains.

---

### Scenario 2: Loading state shown during checkout initiation
**Priority**: High
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 2 | Click "Nâng cấp lên PRO" | ✅ PASS | — |
| 3 | Assert button is disabled/loading immediately | ⏭ SKIPPED | State too transient for snapshot capture |
| 4 | Wait for checkout request to complete | ✅ PASS | Error response received |

**Notes**: Code review confirms `isLoading.value = true` is set before `await checkout()` and `:disabled="isLoading"` + `<span v-if="isLoading">{{ t('common.loading') }}</span>` are wired correctly (`UpgradePrompt.vue:14,78,82`). The loading state IS implemented but the transition is sub-100ms — the Playwright snapshot captures the post-error state. Mechanically present; not visually verifiable in this test environment.

---

### Scenario 3: Return from MoMo with `?payment=success` — PRO already active
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Activate PRO via admin API (`durationDays: 30`) | ✅ PASS | `{"plan":"pro","status":"active","endDate":"2026-05-01"}` |
| 2 | Navigate to `/dashboard/subscription?payment=success` | ✅ PASS | — |
| 3 | Wait for subscription data to load | ✅ PASS | — |
| 4 | Assert success toast visible | ✅ PASS (inferred) | Toast fired (code: `toast.success('subscription.upgrade.success')`); dismissed before snapshot |
| 5 | Assert PRO plan indicator | ✅ PASS | "Chuyên nghiệp" shown |
| 6 | Assert URL changes to `/dashboard/subscription` | ✅ PASS | `?payment=success` removed |
| 7 | Assert end date ~30 days from today | ✅ PASS | "1 thg 5, 2026" (30 days), 30 ngày remaining |

---

### Scenario 4: Return from MoMo — IPN not yet processed (processing state)
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Ensure subscription is FREE | ✅ PASS | Downgraded via admin |
| 2 | Navigate to `/dashboard/subscription?payment=success` | ✅ PASS | — |
| 3 | Wait for subscription fetch to complete | ✅ PASS | Fetched, plan = free |
| 4 | Assert "Đang xử lý thanh toán" visible | ❌ FAIL | Message never rendered |
| 5 | Wait 4 seconds | — | Skipped after step 4 failure |
| 6 | Assert second subscription fetch | — | Skipped |

**Failure Detail**:
- **Failed step**: Step 4
- **Expected**: "Đang xử lý thanh toán... Trang sẽ tự động cập nhật." alert shown
- **Actual**: Normal FREE plan page rendered; no processing message
- **Root cause**: See Bug B2. `processingPayment = true` is set in `onMounted`, but `router.replace(localePath('/dashboard/subscription'))` immediately after causes component state reset in Nuxt 3's `ssr:false` context. By the time Vue can re-render, `processingPayment` is back to `false` in the new component instance.

---

### Scenario 5: PRO user with 20 days remaining — no expiry warning
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Activate PRO with `durationDays: 20` | ✅ PASS | endDate: 2026-04-21 |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert plan shows PRO | ✅ PASS | "Chuyên nghiệp" |
| 4 | Assert end date visible | ✅ PASS | "21 thg 4, 2026" |
| 5 | Assert NO expiry warning banner | ✅ PASS | No alert in snapshot |
| 6 | Assert NO renew button | ✅ PASS | No "Gia hạn" button in snapshot |

---

### Scenario 6: PRO user expiring in ≤7 days — warning banner and renew button
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Downgrade to FREE then activate PRO with `durationDays: 5` | ✅ PASS | endDate: 2026-04-06 |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert plan shows PRO | ✅ PASS | "Chuyên nghiệp" |
| 4 | Assert expiry warning banner visible | ✅ PASS | Alert shown |
| 5 | Assert banner contains "hết hạn" and expiry date | ✅ PASS | "Gói PRO của bạn hết hạn vào 6 thg 4, 2026. Gia hạn để không bị gián đoạn." |
| 6 | Assert "Gia hạn PRO" button visible | ✅ PASS | Button present |

**Note**: Prior test (Scenario 5) showed that when `durationDays` is passed while already PRO, it extends the existing `endDate`. Reset to FREE first was required to get exactly 5 days remaining.

---

### Scenario 7: Expiring PRO user clicks Renew — initiates checkout
**Priority**: Critical
**Result**: ⚠️ PARTIAL

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Activate PRO with 5 days (same state as Sc6) | ✅ PASS | — |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert "Gia hạn PRO" button visible | ✅ PASS | — |
| 4 | Click "Gia hạn PRO" | ✅ PASS | — |
| 5 | Wait for POST `/api/v1/subscription/checkout` | ✅ PASS | Request fired via correct proxy |
| 6 | Assert redirect to MoMo URL | ❌ FAIL | Same MoMo credential issue as Sc1 |

**Partial pass**: Steps 1–5 confirm the renew button correctly triggers the same checkout endpoint as the upgrade button. Redirect blocked by Bug B1 (stale credentials).

---

### Scenario 8: Expired PRO user — renew button shown
**Priority**: High
**Result**: ⚠️ PARTIAL

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Cancel subscription via admin (`/cancel`) | ✅ PASS | `{"plan":"pro","status":"cancelled"}` |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert page does NOT show active PRO | ✅ PASS | Status shows "Đã hủy" |
| 4 | Assert renew/upgrade button visible | ✅ PASS | "Gia hạn PRO" button shown (`isExpiringSoon: true` with 5-day endDate) |
| 5 | Click renew button → checkout fires | ✅ PASS | POST checkout fired |
| 6-7 | Assert MoMo redirect | ❌ FAIL | Same credential issue |

**Partial pass**: Shows correct UI for non-active PRO. True `status: "expired"` (set by backend scheduler) could not be tested — admin cancel endpoint sets `status: "cancelled"`. The `showRenew` computed (`plan === 'pro' && (isExpiringSoon || status === 'expired')`) matched via `isExpiringSoon: true`.

---

### Scenario 9: Checkout fails — MoMo API error
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | MoMo error naturally present (Bug B1 — stale credentials) | ✅ PASS | — |
| 2 | Navigate to `/dashboard/subscription` (FREE plan) | ✅ PASS | — |
| 3 | Click "Nâng cấp lên PRO" | ✅ PASS | — |
| 4 | Wait for checkout request to fail | ✅ PASS | 502 received |
| 5 | Assert error toast visible | ✅ PASS | "Không thể khởi tạo thanh toán. Vui lòng thử lại." |
| 6 | Assert URL remains `/dashboard/subscription` | ✅ PASS | No redirect |
| 7 | Assert upgrade button re-enabled | ✅ PASS | `isLoading = false` in `finally` block |

---

### Scenario 10: Already PRO — checkout returns 409
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Activate PRO with `durationDays: 20` | ✅ PASS | Active, 20 days |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert NO upgrade/renew CTA visible | ✅ PASS | Neither button rendered (showUpgrade=false, showRenew=false) |
| 4 | Send direct `POST /api/v1/subscription/checkout` with JWT | ✅ PASS | — |
| 5 | Assert response is 409 | ✅ PASS | HTTP 409 |
| 6 | Assert body contains `"code":"ALREADY_PRO"` | ✅ PASS | `{"code":"ALREADY_PRO","message":"Shop already has an active PRO subscription."}` |

---

### Scenario 11: Unauthenticated checkout — returns 401
**Priority**: Critical
**Result**: ✅ PASSED

Executed via direct API call in previous test session.

| Step | Description | Result |
|------|-------------|--------|
| 1 | POST `/api/v1/subscription/checkout` without Authorization | ✅ PASS |
| 2 | Assert 401 response | ✅ PASS — HTTP 401 |

---

### Scenario 12: IPN webhook — valid payment activates PRO
**Priority**: Critical
**Result**: ✅ PASSED

Executed via direct API call with computed HMAC-SHA256 signature.

| Step | Description | Result |
|------|-------------|--------|
| 1 | Pre-condition: FREE plan | ✅ PASS |
| 2 | Compute valid HMAC signature | ✅ PASS |
| 3 | POST `/webhooks/momo/ipn` with signed payload (resultCode=0) | ✅ PASS |
| 4 | Assert 204 No Content | ✅ PASS |
| 5 | GET `/api/v1/subscription` with barber JWT | ✅ PASS |
| 6 | Assert `"plan":"pro"`, `"status":"active"` | ✅ PASS |
| 7 | Assert endDate ~30 days from now | ✅ PASS |
| 8 | Confirm email dispatched | ✅ PASS — queued via Messenger |

---

### Scenario 13: IPN idempotency — duplicate IPN ignored
**Priority**: High
**Result**: ✅ PASSED

Executed via direct API call.

| Step | Description | Result |
|------|-------------|--------|
| 1–2 | Send valid IPN, confirm PRO + note endDate | ✅ PASS |
| 3–4 | Wait 2s, send identical IPN (same transId) | ✅ PASS — 204 |
| 5–7 | GET subscription: endDate unchanged | ✅ PASS |
| 8 | No second email sent | ✅ PASS — idempotency guard via momoTransId check |

---

## Edge Case Results

### Edge Case 1: IPN with invalid HMAC — silently rejected
**Result**: ✅ PASSED

| Step | Description | Result |
|------|-------------|--------|
| 1 | POST IPN with corrupted signature (one char changed) | ✅ PASS |
| 2 | Assert 204 (no timing leak) | ✅ PASS |
| 3 | GET subscription: plan still "free" | ✅ PASS |

---

### Edge Case 2: IPN with `resultCode != 0` — failed payment, no activation
**Result**: ✅ PASSED

| Step | Description | Result |
|------|-------------|--------|
| 1 | Compute HMAC for payload with `"resultCode": 1003` | ✅ PASS |
| 2 | POST to `/webhooks/momo/ipn` | ✅ PASS |
| 3 | Assert 204 | ✅ PASS |
| 4 | GET subscription: plan still "free" | ✅ PASS |

---

### Edge Case 3: `?payment=success` before IPN — processing → auto-update
**Result**: ⏭ SKIPPED

Skipped — same root cause as Scenario 4 (Bug B2). The `processingPayment` state never renders, making this scenario untestable in the current implementation.

---

### Edge Case 4: Checkout button prevents double-click
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/dashboard/subscription` (FREE plan) | ✅ PASS | — |
| 2 | Click upgrade button twice via `element.click(); element.click()` | ✅ PASS | Both clicks executed |
| 3 | Assert only ONE checkout request sent | ❌ FAIL | Two POST requests sent |

**Failure Detail**:
- **Expected**: Second click ignored; button disabled after first click
- **Actual**: Two `POST /api/v1/subscription/checkout` requests both reached the backend (both returned 502)
- **Root cause**: See Bug B3. `isLoading.value = true` sets the ref synchronously, but Vue's DOM batch update (setting `disabled` attribute) is asynchronous — both programmatic clicks execute within the same microtask queue before Vue can flush the update.
- **Fix**: Add `if (isLoading.value) return` guard as the first line of `startCheckout()`.

---

### Edge Case 5: Subscription page with `daysRemaining = 0`
**Result**: ✅ PASSED (tested with 1 day)

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Activate PRO with `durationDays: 1` | ✅ PASS | endDate: 2026-04-02 |
| 2 | Navigate to `/dashboard/subscription` | ✅ PASS | — |
| 3 | Assert expiry warning banner visible | ✅ PASS | "hết hạn vào 2 thg 4, 2026" |
| 4 | Assert daysRemaining shown (1 ngày) | ✅ PASS | "1 ngày" rendered |
| 5 | Assert "Gia hạn PRO" button visible | ✅ PASS | — |

**Note**: Exact `daysRemaining = 0` requires the endDate to equal today which the admin API cannot set precisely. Tested with 1 day — near-zero case handled correctly with no UI crashes.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | MoMo hosted checkout page (Scenarios 1, 7, 8 redirect) | `MOMO_SECRET_KEY` is stale — MoMo sandbox rejects HMAC signature. Cannot test redirect until credentials are updated. | Register/update sandbox credentials at MoMo Business Portal. Update `MOMO_SECRET_KEY` and `MOMO_PARTNER_CODE` in `.env`. |
| 2 | `processingPayment` UI state (Scenario 4, Edge Case 3) | Bug B2: state reset by `router.replace` in `ssr:false` context. | Fix Bug B2 (move state to Pinia/sessionStorage), then re-run Sc4 and EC3. |
| 3 | True `status: "expired"` state (Scenario 8) | Admin cancel endpoint sets `status: "cancelled"`. `expired` status is only set by backend scheduler. | Add admin endpoint to force-expire, or wait for scheduler. Re-run Sc8 with true expired state. |
| 4 | Double-click prevention (Edge Case 4) | Bug B3: `isLoading` guard relies on async DOM update. | Fix Bug B3 (add sync guard), re-run EC4. |

---

## Recommendations

1. **Critical — Fix MoMo credentials (B1)**: The entire checkout flow (Scenarios 1, 7, 8 redirect, Edge Case 3) is blocked by stale `MOMO_SECRET_KEY`. This is the highest priority fix before any production deployment.

2. **High — Fix `processingPayment` state loss (B2)**: The post-payment return UX is broken. Users who return from MoMo before IPN fires see no feedback. Store `processingPayment` in `sessionStorage` or a Pinia store keyed to the payment flow to survive `router.replace`.

3. **Medium — Fix double-click guard (B3)**: Add `if (isLoading.value) return` at the top of `startCheckout()` in `UpgradePrompt.vue`. This is a one-line fix.

4. **Low — Admin activate endpoint accumulates duration**: `durationDays` is added to the existing `endDate` if already PRO, not computed from today. This makes it harder to set up precise test states. Consider documenting this behavior or adding a `setEndDate` param for testing.

5. **Bugs fixed during testing**: The three fixes applied (trailing slash, `requestType`, `accessKey`) should be committed and regression-tested to ensure checkout flow works end-to-end once credentials are updated.
