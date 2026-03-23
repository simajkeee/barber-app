# Test Plan: Shop Profile Management

## Overview
Covers the shop owner's ability to create their barbershop profile, view and edit shop
details (name, address, phone, description, slug), manage weekly working hours, and
manage the list of services offered. The shop is the tenant boundary for all other
features — every dashboard feature depends on a shop existing. Actors: authenticated
shop owner.

## Scope
- **In scope**: Create shop, view/edit shop details (name, address, phone, slug), update
  weekly working schedule, CRUD for shop services (create, edit, deactivate).
- **Out of scope**: Cover image upload (URL-only in MVP), multi-staff management
  (post-MVP), subscription-gated features.

## Prerequisites
- Application running at `BASE_URL`
- A logged-in user session with NO shop created yet (for shop creation scenarios)
- A second logged-in session WITH an existing shop (for edit/service scenarios)
- At least one active service in the existing shop (for service edit/delete scenarios)

## Test Scenarios

### Scenario 1: Create a new shop
**Actor**: Authenticated owner with no shop
**Goal**: Create a shop profile and be redirected to the shop dashboard
**Priority**: Critical

**Steps**:
1. Log in as a user who does not have a shop
2. Navigate to `/dashboard/shop/create`
3. Assert the create-shop form is visible
4. Fill "Shop name" with `Test Barber Shop`
5. Fill "Address" with `123 Test Street`
6. Fill "Phone" with `0901234567`
7. Click the create/submit button
8. Wait for navigation
9. Assert URL is `/dashboard/shop` or `/dashboard`
10. Assert shop name `Test Barber Shop` is visible on the page

**Expected Result**: Shop created via POST `/api/v1/shops`. Response includes auto-generated
slug. Default 7-day work schedule is created. User lands on shop detail/dashboard page.

---

### Scenario 2: Attempt to create a second shop
**Actor**: Authenticated owner who already has a shop
**Goal**: See an error preventing a second shop from being created
**Priority**: High

**Steps**:
1. Log in as a user who already has a shop
2. Navigate to `/dashboard/shop/create`
3. Assert either a redirect occurs or the form is not rendered (UI should prevent this)
4. If a form is shown, fill all required fields and submit
5. Assert an error message is shown (HTTP 409 `SHOP_ALREADY_EXISTS`)

**Expected Result**: User cannot create a second shop. Either the UI prevents navigation
to the create page, or the submission returns a visible error.

---

### Scenario 3: View shop profile
**Actor**: Authenticated owner with an existing shop
**Goal**: View the shop's current details and working schedule
**Priority**: Critical

**Steps**:
1. Log in as a user with an existing shop
2. Navigate to `/dashboard/shop`
3. Assert shop name is displayed
4. Assert address is displayed
5. Assert phone number is displayed
6. Assert the working schedule section is present

**Expected Result**: All shop fields rendered correctly from GET `/api/v1/shops/me`.

---

### Scenario 4: Edit shop basic details
**Actor**: Authenticated owner
**Goal**: Update shop name, address, and phone number
**Priority**: High

**Steps**:
1. Log in as a user with an existing shop
2. Navigate to `/dashboard/shop`
3. Click the edit button (or navigate to the edit form)
4. Clear the "Shop name" field and fill with `Updated Shop Name`
5. Clear the "Address" field and fill with `456 New Address`
6. Click save
7. Wait for response
8. Assert a success notification or toast is visible
9. Assert the updated name `Updated Shop Name` is displayed

**Expected Result**: Shop updated via PUT `/api/v1/shops/me`. Changes persisted and visible.

---

### Scenario 5: Edit shop slug — valid slug
**Actor**: Authenticated owner
**Goal**: Change the shop's URL slug
**Priority**: High

**Steps**:
1. Log in as a user with an existing shop
2. Navigate to `/dashboard/shop`
3. Open the slug edit field or section
4. Clear current slug and fill with `my-test-shop-{timestamp}` (unique value)
5. Click save
6. Wait for response
7. Assert no error is shown
8. Assert the new slug is reflected in the public booking URL or slug display

**Expected Result**: Slug updated. HTTP 200. New slug is shown.

---

### Scenario 6: Edit shop slug — duplicate slug
**Actor**: Authenticated owner
**Goal**: See an error when using a slug that is already taken by another shop
**Priority**: Medium

**Steps**:
1. Log in as a user with an existing shop
2. Navigate to `/dashboard/shop` edit form
3. Fill the slug field with a slug known to be in use by another shop [VERIFY: seed a second shop with a known slug before running]
4. Click save
5. Wait for response

**Expected Result**: Error message displayed indicating the slug is already taken.
HTTP 409 `SLUG_ALREADY_EXISTS`.

---

### Scenario 7: Update working schedule
**Actor**: Authenticated owner
**Goal**: Change open/close hours for a working day and mark Sunday as closed
**Priority**: High

**Steps**:
1. Log in as a user with an existing shop
2. Navigate to `/dashboard/shop/schedule`
3. Assert the 7-day schedule grid is visible
4. Change Monday's open time to `08:00`
5. Change Monday's close time to `21:00`
6. Toggle Sunday to "closed" (if it isn't already)
7. Click save
8. Wait for response
9. Assert success feedback is displayed
10. Reload or re-navigate to `/dashboard/shop/schedule`
11. Assert Monday shows `08:00`–`21:00`
12. Assert Sunday is marked as closed

**Expected Result**: Schedule saved via PUT `/api/v1/shops/me/schedule`. Persisted and
correctly shown on reload.

---

### Scenario 8: Working schedule — close time before open time
**Actor**: Authenticated owner
**Goal**: See a validation error when close time is not after open time
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/shop/schedule`
2. Set Monday's open time to `18:00`
3. Set Monday's close time to `09:00`
4. Click save
5. Wait for response

**Expected Result**: Error displayed indicating closing time must be after opening time.
HTTP 400 `VALIDATION_ERROR`. Form is not saved.

---

### Scenario 9: Create a new service
**Actor**: Authenticated owner
**Goal**: Add a new service to the shop's service list
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/shop/services`
2. Click "Add service" or equivalent button
3. Fill "Service name" with `Haircut`
4. Fill "Duration" with `30` (minutes)
5. Fill "Price" with `100000` (VND)
6. Click save
7. Wait for response
8. Assert `Haircut` appears in the services list

**Expected Result**: Service created via POST `/api/v1/shops/me/services`. Service visible
in the list with correct name, duration, and price.

---

### Scenario 10: Edit an existing service
**Actor**: Authenticated owner
**Goal**: Update a service's price
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/shop/services`
2. Click "Edit" on an existing service
3. Change the "Price" field to `150000`
4. Click save
5. Wait for response
6. Assert updated price `150,000` (or formatted equivalent) is visible in the list

**Expected Result**: Service updated via PUT `/api/v1/shops/me/services/{id}`.

---

### Scenario 11: Deactivate (soft delete) a service
**Actor**: Authenticated owner
**Goal**: Remove a service from the active list
**Priority**: High

**Steps**:
1. Log in and navigate to `/dashboard/shop/services`
2. Note the current count of visible services
3. Click "Delete" or "Deactivate" on one service
4. Confirm the deletion if a confirmation dialog appears
5. Wait for response
6. Assert the service is no longer visible in the default (active) list
7. If an "include inactive" toggle exists, enable it and assert the service appears as inactive

**Expected Result**: Service soft-deleted (isActive = false) via DELETE `/api/v1/shops/me/services/{id}`.
Not removed from history, just hidden from the active list.

---

### Scenario 12: Create service with invalid data
**Actor**: Authenticated owner
**Goal**: See validation errors for missing or invalid service fields
**Priority**: Medium

**Steps**:
1. Log in and navigate to `/dashboard/shop/services`
2. Click "Add service"
3. Leave "Service name" empty
4. Fill "Duration" with `0` (below minimum of 5)
5. Fill "Price" with `500` (below minimum of 1000)
6. Click save

**Expected Result**: Validation errors displayed for each invalid field. No service created.

## Edge Cases & Negative Tests

### Edge Case 1: Create shop with Vietnamese shop name (slug auto-generation)
**Scenario**: Shop name uses Vietnamese diacritics — verify the slug is valid ASCII
**Steps**:
1. Navigate to `/dashboard/shop/create`
2. Fill "Shop name" with `Tiệm Cắt Tóc Quận 1`
3. Submit the form
**Expected Result**: Shop created. Slug is auto-generated as something like `tiem-cat-toc-quan-1`. No diacritics in slug.

### Edge Case 2: Schedule with `isOpen = false` — times ignored
**Scenario**: Setting a day to closed should not require or save open/close times
**Steps**:
1. Navigate to `/dashboard/shop/schedule`
2. Toggle a currently-open day to "closed"
3. Verify no time fields are required for that day
4. Save
**Expected Result**: Saved successfully. The day shows as closed with no times displayed.

### Edge Case 3: Service with maximum field lengths
**Scenario**: Service name at 255 characters, duration at 480 minutes
**Steps**:
1. Create a service with a 255-character name and duration `480`
2. Submit
**Expected Result**: Service created without error.

## Data Requirements
- A seeded or newly registered user with NO shop (for creation tests)
- A seeded user WITH a shop and at least 2 active services (for edit/delete tests)
- A second shop with a known slug (for slug collision test) [VERIFY: seed manually or via API]

## Coverage Gaps
- Cover image upload (URL only accepted in MVP — file upload UI not implemented)
- Concurrent slug creation race condition (requires load testing beyond Playwright)
- Schedule update with exactly `openTime == closeTime` boundary (UI may prevent this)
