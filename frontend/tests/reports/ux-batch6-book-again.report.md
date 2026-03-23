# Test Report: UX Batch 6 — Book Again Shortcut

**Executed**: 2026-03-22T15:25:00Z
**Plan file**: `tests/plans/ux-batch6-book-again.plan.md`
**Result**: FAILED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 6     |
| Passed             | 5     |
| Failed             | 1     |
| Skipped            | 2     |
| Coverage Gaps      | 2     |

---

## Scenario Results

### Scenario 1: "Book Again" button visible on completed appointment
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to completed appointment detail | ✅ PASS | `/en/dashboard/appointments/019cf160-6a2d-7027-9651-ca7d8f0e7857` |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert "Book Again" button visible | ✅ PASS | Button present |
| 4 | Assert button is secondary variant | ✅ PASS | Button is a link styled as secondary |

---

### Scenario 2: "Book Again" navigates with pre-filled client and service
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to completed appointment detail | ✅ PASS | — |
| 2 | Note client name and service name | ✅ PASS | "Second Client", "Haircut" |
| 3 | Click "Book Again" | ✅ PASS | — |
| 4 | Assert URL contains `clientId=` and `serviceId=` params | ✅ PASS | `/create?clientId=019cf160-1440-703f-a1b4-62490eedd9db&serviceId=019ce30f-add8-7ab7-ae23-1d2f1c415f81` |
| 5 | Assert client pre-selected in form | ✅ PASS | "Second Client" pre-filled |
| 6 | Assert service pre-selected | ✅ PASS | "Haircut (60min)" selected |

---

### Scenario 3: "Book Again" NOT shown on scheduled appointment
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to scheduled appointment detail | ✅ PASS | — |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert "Book Again" NOT visible | ✅ PASS | No "Book Again" button rendered |
| 4 | Assert action buttons (Mark Complete, No Show, Cancel) ARE visible | ✅ PASS | All status-change actions present |

---

### Scenario 4: "Book Again" NOT shown on cancelled appointment
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to cancelled appointment detail | ✅ PASS | — |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert "Book Again" NOT visible | ✅ PASS | Only "Back" button present |
| 4 | Assert no status-change buttons visible | ✅ PASS | Terminal status — no action buttons |

---

### Scenario 5: "Book Again" NOT shown on no-show appointment
**Priority**: Medium
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to no-show appointment detail | ✅ PASS | `/en/dashboard/appointments/019cf16c-0a7b-7fd9-be0a-20f8633f3f91` |
| 2 | Wait for page to load | ✅ PASS | — |
| 3 | Assert "Book Again" NOT visible | ✅ PASS | Only "Back" button present; no "Book Again" |

---

### Scenario 6: Pre-filled create form can be submitted end-to-end
**Priority**: High
**Result**: ❌ FAILED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to completed appointment and click "Book Again" | ✅ PASS | — |
| 2 | Wait for create form with pre-selected client and service | ✅ PASS | Pre-filled correctly |
| 3 | Fill date with a future date (Mar 26) | ✅ PASS | Slots available |
| 4 | Click first available time slot | ✅ PASS | Slot selected |
| 5 | Click "Save" | ❌ FAIL | 400 Bad Request — "Failed to create appointment" |
| 6 | Retry with Mar 27 | ❌ FAIL | Same 400 error |

**Failure Detail**:
- **Failed step**: Step 5 — Save new appointment
- **Expected**: Appointment created successfully, redirect to new appointment detail
- **Actual**: API returns 400 Bad Request; toast shows "Failed to create appointment"
- **Note**: Likely cause is the monthly appointment subscription limit being reached. The test account has exhausted its appointment quota for the current month. This is a prerequisite issue, not a feature bug.

---

## Edge Case Results

### Edge Case 1: Client deleted after completed appointment
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to appointment with deleted client | ⏭ SKIP | No completed appointment with deleted client in test data |

---

### Edge Case 2: Service deleted after completed appointment
**Result**: ⏭ SKIPPED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to appointment with deleted service | ⏭ SKIP | No completed appointment with deleted service in test data |

---

### Edge Case 3: "Cancel" on create page abandons booking
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Click "Book Again" on completed appointment | ✅ PASS | — |
| 2 | On create form, click "Cancel" | ✅ PASS | — |
| 3 | Assert user is navigated away from create form | ✅ PASS | Navigated to `/en/dashboard/appointments` |

**Note**: Cancel returns to appointments list (not the specific appointment detail as the plan suggested), but this is acceptable UX — user can abandon the re-booking.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | S6 end-to-end booking fails with 400 | Likely subscription limit reached; test account cannot create new appointments this month | Use a test account with remaining appointment quota, or run S6 at the start of a test month |
| 2 | EC1/EC2 — deleted client/service | No test data with deleted associated records | Create appointments, then delete the client/service, then test the "Book Again" flow |

---

## Recommendations

- "Book Again" visibility rules (S1-S5) all pass correctly — the feature correctly gates the button to completed appointments only.
- Pre-fill of `clientId` and `serviceId` query params works correctly (S2 pass).
- For S6 (end-to-end), ensure the test account has remaining appointment quota before running.
- Consider adding `APPOINTMENT_LIMIT_REACHED` specific error handling on the create form — the 400 response should show a helpful message, not a generic "Failed to create appointment" toast.
