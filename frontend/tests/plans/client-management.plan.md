# Test Plan: Client Management

## Overview
Covers the shop owner's ability to manage their client list: creating clients, viewing
client details (including visit history), editing client information, searching by name
or phone, and deleting clients. Client data is scoped to the authenticated owner's shop —
no cross-shop access. Actors: authenticated shop owner.

## Scope
- **In scope**: List clients, search clients, create client, view client detail, edit
  client, delete client, pagination behavior.
- **Out of scope**: Visit count/lastVisitAt updates triggered by appointment completion
  (tested in Appointment Scheduling plan), CSV import (post-MVP).

## Prerequisites
- Application running at `BASE_URL`
- Logged-in user with an existing shop
- At least 5 seeded clients in the shop (for list/search/pagination tests)
- At least 1 client with a known phone number (for duplicate phone test)

## Test Scenarios

### Scenario 1: View client list
**Actor**: Authenticated shop owner
**Goal**: See a list of clients belonging to the shop
**Priority**: Critical

**Steps**:
1. Log in and navigate to `/dashboard/clients`
2. Assert the clients list is visible
3. Assert at least one client row/card is displayed
4. Assert each client entry shows at minimum: name and phone

**Expected Result**: Client list rendered from GET `/api/v1/clients`. Clients are scoped
to the authenticated user's shop.

---

### Scenario 2: Search clients by name
**Actor**: Authenticated shop owner
**Goal**: Filter the client list by partial name match
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/clients`
2. Assert a search input is visible
3. Fill the search input with a known partial name (e.g., first 3 letters of a seeded client's name)
4. Wait for the list to update (debounce or submit)
5. Assert only clients whose name contains the search term are shown
6. Clear the search input
7. Assert the full client list is restored

**Expected Result**: GET `/api/v1/clients?search={term}` returns filtered results.
Partial, case-insensitive matching works.

---

### Scenario 3: Search clients by phone
**Actor**: Authenticated shop owner
**Goal**: Find a client using their phone number
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/clients`
2. Fill the search input with the last 4 digits of a known client's phone number
3. Wait for the list to update
4. Assert the matching client appears in the results

**Expected Result**: Phone-based partial search returns the correct client.

---

### Scenario 4: Create a new client
**Actor**: Authenticated shop owner
**Goal**: Add a new client to the shop
**Priority**: Critical

**Steps**:
1. Log in and navigate to `/dashboard/clients/create`
2. Assert the create-client form is visible
3. Fill "First name" with `New`
4. Fill "Last name" with `Client`
5. Fill "Phone" with `0912345678`
6. Click save
7. Wait for navigation or response
8. Assert the new client appears in the client list (navigate to `/dashboard/clients`)
9. Assert client "New Client" is visible

**Expected Result**: Client created via POST `/api/v1/clients`. Phone stored normalized.
Client visible in list.

---

### Scenario 5: Create client with optional fields
**Actor**: Authenticated shop owner
**Goal**: Create a client with email and notes filled in
**Priority**: Medium

**Steps**:
1. Navigate to `/dashboard/clients/create`
2. Fill "First name" with `Full`
3. Fill "Last name" with `Record`
4. Fill "Phone" with `0987654321`
5. Fill "Email" with `full.record@example.com`
6. Fill "Notes" with `VIP client, prefers morning slots`
7. Click save
8. Wait for navigation/response
9. Navigate to the client detail page
10. Assert email and notes are displayed correctly

**Expected Result**: All optional fields saved and shown in the detail view.

---

### Scenario 6: Create client with duplicate phone
**Actor**: Authenticated shop owner
**Goal**: See an error when a phone number already belongs to another client in this shop
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/clients/create`
2. Fill "First name" with `Dupe`
3. Fill "Last name" with `Phone`
4. Fill "Phone" with the phone number of an already-existing client
5. Click save
6. Wait for response

**Expected Result**: Error message displayed indicating the phone number is already in use
(HTTP 409 `PHONE_ALREADY_EXISTS`). Form is not cleared; user can correct and resubmit.

---

### Scenario 7: Create client with invalid phone format
**Actor**: Authenticated shop owner
**Goal**: See a validation error for a malformed phone number
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/clients/create`
2. Fill "First name" with `Bad`
3. Fill "Last name" with `Phone`
4. Fill "Phone" with `abc-not-a-phone`
5. Click save
6. Wait for response

**Expected Result**: Validation error displayed for the phone field. HTTP 400 `VALIDATION_ERROR`.

---

### Scenario 8: View client detail
**Actor**: Authenticated shop owner
**Goal**: See a client's full information including visit history
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/clients`
2. Click on a client who has at least one completed appointment
3. Assert URL changes to `/dashboard/clients/{id}`
4. Assert client name is displayed as a heading
5. Assert phone number is shown
6. Assert visit count is displayed
7. Assert "Recent appointments" section is visible with at least one entry

**Expected Result**: Full client record rendered from GET `/api/v1/clients/{id}`.
`recentAppointments` array visible.

---

### Scenario 9: Edit client information
**Actor**: Authenticated shop owner
**Goal**: Update a client's name and notes
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/clients/{id}/edit` for an existing client
2. Assert fields are pre-filled with current values
3. Clear the "First name" field and fill with `Edited`
4. Clear the "Notes" field and fill with `Updated notes text`
5. Click save
6. Wait for response
7. Assert success feedback is shown
8. Navigate back to the client detail page
9. Assert name shows `Edited` and notes show `Updated notes text`

**Expected Result**: Client updated via PUT `/api/v1/clients/{id}`. Changes persisted.

---

### Scenario 10: Delete a client
**Actor**: Authenticated shop owner
**Goal**: Remove a client from the system
**Priority**: High

**Steps**:
1. Navigate to `/dashboard/clients`
2. Click on a client to open the detail page (choose one with no critical data)
3. Click the "Delete" button
4. If a confirmation dialog appears, confirm deletion
5. Wait for navigation or response
6. Assert URL returns to `/dashboard/clients`
7. Assert the deleted client is no longer visible in the list

**Expected Result**: Client hard-deleted via DELETE `/api/v1/clients/{id}`. HTTP 204.
Client no longer appears in the list.

---

### Scenario 11: Pagination — load more clients
**Actor**: Authenticated shop owner
**Goal**: Navigate through multiple pages of clients
**Priority**: Medium

**Steps**:
1. Ensure at least 21 clients exist in the shop (to trigger pagination)
2. Navigate to `/dashboard/clients`
3. Assert only the first page of clients is shown (default 20)
4. Scroll to the bottom or click "Load more" / pagination control
5. Assert additional clients are loaded
6. Assert the total visible count is greater than 20

**Expected Result**: Cursor-based pagination works. `hasMore: true` triggers more items to load.

## Edge Cases & Negative Tests

### Edge Case 1: Search with Vietnamese diacritics
**Scenario**: Search input contains Vietnamese characters (e.g., "Nguyễn")
**Steps**:
1. Navigate to `/dashboard/clients`
2. Fill the search input with `Nguyễn`
3. Assert matching clients appear (case-insensitive, diacritic-aware)
**Expected Result**: Backend ILIKE search handles Vietnamese correctly.

### Edge Case 2: Edit phone to one belonging to another client
**Scenario**: Change a client's phone to a number already used by a different client in the same shop
**Steps**:
1. Navigate to `/dashboard/clients/{id}/edit`
2. Change phone to an existing client's phone
3. Submit
**Expected Result**: HTTP 409 `PHONE_ALREADY_EXISTS` error displayed. No update applied.

### Edge Case 3: Empty search returns full list
**Scenario**: Clearing the search input restores the unfiltered list
**Steps**:
1. Search for a term with few results
2. Clear the search input completely
3. Assert the full client list is shown again
**Expected Result**: Clearing search triggers GET `/api/v1/clients` without `search` param.

## Data Requirements
- At least 5–21 seeded clients with varied names and phone numbers
- At least 1 client with a completed appointment (for visit history test)
- One client with a known phone number available for duplicate-phone tests

## Coverage Gaps
- Visit count auto-update on appointment completion — covered in `appointment-scheduling.plan.md`
- CSV bulk import — post-MVP, not implemented
- Cross-shop client isolation test — requires two separate user sessions simultaneously
