# Test Report: Client Management

**Executed**: 2026-03-20T01:00:00Z
**Plan file**: `tests/plans/client-management.plan.md`
**Result**: PASSED

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 11    |
| Passed             | 9     |
| Failed             | 0     |
| Skipped            | 2 (S5 Medium, S11 Medium — out of scope for this run) |
| Coverage Gaps      | 1     |

---

## Scenario Results

### Scenario 1: View client list
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to /dashboard/clients | ✅ PASS | — |
| 2–4 | Assert list visible, at least 1 client with name and phone | ✅ PASS | "Nguyen Van Test" (0901234568) shown |

---

### Scenario 2: Search clients by name
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Assert search input visible | ✅ PASS | — |
| 3–4 | Type "Ngu" partial search | ✅ PASS | — |
| 5 | Assert only matching clients shown | ✅ PASS | "Nguyen Van Test" returned |
| 6–7 | Clear search, assert full list restored | ✅ PASS | — |

---

### Scenario 3: Search clients by phone
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Type last 4 digits "4568" | ✅ PASS | — |
| 3–4 | Assert matching client shown | ✅ PASS | "Nguyen Van Test" (0901234568) returned |

---

### Scenario 4: Create a new client
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to /dashboard/clients/create, assert form visible | ✅ PASS | — |
| 3–5 | Fill first name "New", last name "Client", phone "0912345678" | ✅ PASS | — |
| 6 | Click save | ✅ PASS | — |
| 7–9 | Assert redirect to client detail, "New Client" visible | ✅ PASS | "Đã thêm khách hàng" toast |

---

### Scenario 5: Create client with optional fields
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded from this run.

---

### Scenario 6: Create client with duplicate phone
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–4 | Fill form with existing phone 0912345678 | ✅ PASS | — |
| 5 | Assert error for duplicate phone | ✅ PASS | "Số điện thoại này đã được sử dụng cho khách hàng khác." (HTTP 409) |

---

### Scenario 7: Create client with invalid phone format
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–4 | Fill phone with "abc-not-a-phone" | ✅ PASS | — |
| 5 | Assert phone validation error | ✅ PASS | "Phone number format is invalid." (HTTP 400) |

---

### Scenario 8: View client detail
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Click on "Nguyen Van Test" in client list | ✅ PASS | — |
| 3 | Assert URL changes to /dashboard/clients/{id} | ✅ PASS | — |
| 4–7 | Assert name, phone, visit count, last visit visible | ✅ PASS | All fields rendered; no recent appointments (client seeded without appointments) |

**Coverage gap**: No "recent appointments" section visible — client has no appointments yet. Visit history section display could not be verified.

---

### Scenario 9: Edit client information
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–2 | Navigate to edit page, assert fields pre-filled | ✅ PASS | — |
| 3–4 | Update first name to "Edited", notes to "Updated notes text" | ✅ PASS | — |
| 5–7 | Save, assert success toast | ✅ PASS | "Đã cập nhật khách hàng" |
| 8–9 | Assert name shows "Edited" and notes show updated text | ✅ PASS | — |

---

### Scenario 10: Delete a client
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1–3 | Click "Xóa khách hàng" on "New Client" | ✅ PASS | Confirmation dialog shown |
| 4 | Confirm deletion | ✅ PASS | — |
| 5–7 | Assert redirect to /dashboard/clients, client no longer in list | ✅ PASS | Only "Edited Van Test" remains |

---

### Scenario 11: Pagination — load more clients
**Priority**: Medium
**Result**: ⏭ SKIPPED
**Reason**: Medium priority — excluded. Also requires 21+ seeded clients; only 2 exist in the test shop.

---

## Edge Case Results

### Edge Cases 1–3
**Result**: ⏭ SKIPPED
**Reason**: Edge cases excluded from Critical + High priority run.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | S8 "recent appointments" section not verifiable | Test shop has no completed appointments linked to "Nguyen Van Test" | Seed at least 1 completed appointment for the client before running S8, or use the playwright shop after creating and completing an appointment in the appointment-scheduling module. |

---

## Recommendations

- **Note (S7)**: Validation error message is in English ("Phone number format is invalid.") while the rest of the UI is in Vietnamese. Consider localizing this error message.
- **S11 prerequisite**: Pagination needs 21+ clients. Consider adding a seeding script to the test setup.
