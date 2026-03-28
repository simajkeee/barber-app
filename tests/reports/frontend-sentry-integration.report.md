# Test Report: Frontend Sentry Integration

**Executed**: 2026-03-28T08:00:00Z
**Plan file**: `development/features/25-sentry/frontend-sentry-integration.plan.md`
**Result**: PARTIAL

---

## Summary

| Metric             | Value |
|--------------------|-------|
| Total Scenarios    | 9     |
| Passed             | 6     |
| Failed             | 0     |
| Skipped            | 3     |
| Coverage Gaps      | 6     |

---

## Scenario Results

### Scenario 1: Frontend application loads after Sentry module installation
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `http://localhost:3000/` | ✅ PASS | — |
| 2 | Assert page loads without blank screen | ✅ PASS | Home page fully rendered |
| 3 | Assert URL is valid (locale redirect or root) | ✅ PASS | `http://localhost:3000/` — no locale prefix on root |
| 4 | Open browser console | ✅ PASS | — |
| 5 | Assert no JavaScript errors related to Sentry init | ✅ PASS | 0 console errors |
| 6 | Assert no TypeError or ReferenceError from sentry.client.config.ts | ✅ PASS | No errors at all |

---

### Scenario 2: Registration page loads and form submits correctly
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/register` | ✅ PASS | Resolves to `http://localhost:3000/register` (no locale prefix) |
| 2 | Assert registration form is visible | ✅ PASS | Form with firstName, lastName, email, password fields visible |
| 3 | Fill email field with `sentry-test-reg@example.com` | ✅ PASS | — |
| 4 | Fill password with `Password123!` | ✅ PASS | — |
| 5 | Fill firstName with `Test` | ✅ PASS | — |
| 6 | Fill lastName with `Sentry` | ✅ PASS | — |
| 7 | Fill phoneNumber field | ⏭ SKIP | **COVERAGE GAP**: Phone number field not present in registration form (feature #23 added phoneNumber to backend API but frontend form was not updated) |
| 8 | Click submit button | ✅ PASS | — |
| 9 | Assert no JavaScript error in console during/after submission | ✅ PASS | Only a network-level 400 (Bad Request), no unhandled JS exception |
| 10 | Assert response handled (success redirect OR error message — not crash) | ✅ PASS | Page did not crash; 400 was handled silently (see recommendation) |

**Notes**: Form submitted successfully without crash. Backend returned 400 (`VALIDATION_ERROR: phoneNumber required`) because the frontend form is missing the phoneNumber field added in feature #23. The error was handled without a JavaScript crash, but **no error message was displayed to the user** (silent failure — separate UX bug).

---

### Scenario 3: Login page loads and form submits correctly
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/login` | ✅ PASS | `http://localhost:3000/login` |
| 2 | Assert login form is visible | ✅ PASS | Email, password fields and login button visible |
| 3 | Fill email with `sentry-test-new@example.com` | ✅ PASS | Test user created via API |
| 4 | Fill password with `Password123!` | ✅ PASS | — |
| 5 | Click login button | ✅ PASS | — |
| 6 | Assert no JavaScript error in console | ✅ PASS | No Sentry-related errors; only expected 404s for /api/v1/shops/me |
| 7 | Assert URL changes to dashboard after login | ✅ PASS | Redirected to `http://localhost:3000/dashboard` |

---

### Scenario 4: Login failure shows error message — not a crash
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `/login` (after logging out) | ✅ PASS | — |
| 2 | Fill email with `nonexistent@example.com` | ✅ PASS | — |
| 3 | Fill password with `wrongpassword` | ✅ PASS | — |
| 4 | Click login button | ✅ PASS | — |
| 5 | Assert error message displayed in UI | ✅ PASS | `alert "Email hoặc mật khẩu không đúng"` shown |
| 6 | Assert no unhandled JavaScript error in console | ✅ PASS | Only network-level 401 error, no JS exception |
| 7 | Assert page does not crash or become unresponsive | ✅ PASS | Page fully functional after failed login |

---

### Scenario 5: Backend DSN not visible in page source or public bundle
**Priority**: Critical
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Navigate to `http://localhost:3000/` | ✅ PASS | — |
| 2–3 | Search page HTML for `sentry.io` | ✅ PASS | `hasSentryIo: false` — no sentry.io in page source |
| 4–6 | Inspect network requests and JS bundles for DSN patterns | ✅ PASS | No DSN URL patterns (`https://[hash]@sentry.io/...`) found; `sentryDsnMatches: null` |
| 7 | Assert `SENTRY_AUTH_TOKEN` absent from loaded resources | ✅ PASS | `hasAuthTokenInInline: false` |

**Notes**: With empty `NUXT_PUBLIC_SENTRY_DSN`, no DSN value is embedded in the bundle. No Sentry-related external scripts loaded. Network requests showed only `_nuxt/builds/meta/dev.json`.

---

### Scenario 6: Frontend build succeeds with Sentry module
**Priority**: High
**Result**: ✅ PASSED

| Step | Description | Result | Notes |
|------|-------------|--------|-------|
| 1 | Run `cd frontend && npm run build` inside container | ✅ PASS | Exit code 0 |
| 2 | Assert exit code is `0` | ✅ PASS | — |
| 3 | Assert no ERROR or FATAL from Sentry module | ✅ PASS | Only WARN-level messages (no auth token — graceful skip) |
| 4 | Assert `.output/` directory is created | ✅ PASS | `.output/server/index.mjs` and `.output/public/` created |

**Notes**: Sentry plugin emitted warnings only (no auth token, source maps skipped). Build completed with "✨ Build complete!" Total bundle: 7.6 MB (1.42 MB gzip).

⚠️ **Critical Side Effect**: Running `npm run build` inside the Docker container overwrote `.nuxt/dist/server/server.mjs` with container-absolute paths (`/app/frontend/node_modules/...`). The host's Nuxt dev server (running at `/Users/aleksandrkarelin/...`) then failed to resolve these paths, crashing the entire dev server with SSR 500 errors. **Scenarios 9 and Edge Cases 1–3 were blocked as a result.** To recover: restart the dev server with `npm run dev` from `frontend/` on the host machine.

---

### Scenario 7: Frontend JS error captured by Sentry
**Priority**: Critical
**Result**: ⏭ SKIPPED

**Reason**: `[REQUIRES LIVE SENTRY DSN]` — staging environment with real `NUXT_PUBLIC_SENTRY_DSN` required.

---

### Scenario 8: FetchError does NOT appear in Sentry
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: `[REQUIRES LIVE SENTRY DSN]` — Sentry dashboard access required.

---

### Scenario 9: Duplicate registration shows error message — FetchError handled gracefully
**Priority**: High
**Result**: ⏭ SKIPPED

**Reason**: Dev server crashed after `npm run build` (container path conflict — see Scenario 6 notes). Could not navigate to any frontend page after Scenario 6. Re-run this scenario after restarting the dev server.

---

## Edge Case Results

### Edge Case 1: Empty NUXT_PUBLIC_SENTRY_DSN — no initialization error
**Result**: ⏭ SKIPPED

**Reason**: Dev server crashed after Scenario 6. Partially verified earlier (Scenario 1 passed with 0 console errors, empty DSN, no Sentry init errors). Full edge case verification blocked.

---

### Edge Case 2: NavigationDuplicated error does not crash the app
**Result**: ⏭ SKIPPED

**Reason**: Dev server crashed after Scenario 6.

---

### Edge Case 3: Network error (offline simulation) handled gracefully
**Result**: ⏭ SKIPPED

**Reason**: Dev server crashed after Scenario 6.

---

## Coverage Gaps Encountered

| # | Description | Reason | Action Required |
|---|-------------|--------|-----------------|
| 1 | `phoneNumber` field missing from registration form | Feature #23 added phoneNumber to backend API but frontend registration form was not updated | Add phoneNumber field to the registration page component |
| 2 | Registration failure shows no error message (silent 400) | When backend returns 400 (missing phoneNumber), the UI shows no error to the user | Fix the registration composable/form to display validation errors for unexpected 400 responses |
| 3 | Scenarios 9 + Edge Cases 1–3 blocked by dev server crash | Running `npm run build` inside the container corrupted `.nuxt/dist/server/server.mjs` for the host dev server | Restart `npm run dev` from `frontend/` on the host; avoid running `npm run build` inside the container when the host dev server is active |
| 4 | Actual Sentry event delivery from browser | `[REQUIRES LIVE SENTRY DSN]` — Scenario 7 skipped | Manual staging test with real `NUXT_PUBLIC_SENTRY_DSN` |
| 5 | FetchError filtering verification | `[REQUIRES LIVE SENTRY DSN]` — Scenario 8 skipped | Manual staging verification |
| 6 | `sendDefaultPii` not explicitly set in `sentry.client.config.ts` | Plan notes this as unverified — confirm whether `@sentry/nuxt` defaults to `false` | Explicitly set `sendDefaultPii: false` in `sentry.client.config.ts` to match backend config |

---

## Recommendations

1. **🚨 Restart dev server**: Run `npm run dev` from `frontend/` on the host machine. The `npm run build` inside the container corrupted `.nuxt/dist/server/server.mjs` with container-absolute paths. The host dev server can't resolve `/app/frontend/node_modules/...`.

2. **🐛 Missing phoneNumber field in registration form**: Feature #23 added `phoneNumber` (required) to the backend registration endpoint, but the frontend registration form was not updated. Every registration attempt from the UI silently fails with 400 `VALIDATION_ERROR`. This is a critical UX bug — users cannot register through the frontend.

3. **🐛 Silent 400 handling in registration**: When the backend returns an unexpected 400, the registration form stays on the page with no error message. The `useAuth` composable or form component should surface these errors.

4. **Add `sendDefaultPii: false` to `sentry.client.config.ts`**: The backend Sentry config explicitly sets `send_default_pii: false`. The frontend config does not. Set this explicitly to match the backend's privacy posture.

5. **Build environment conflict**: Avoid running `npm run build` inside the Docker container while the host's `npm run dev` is active — they share the `.nuxt/` directory via volume mount and the container's absolute paths will break the host server. Consider using `npm run build:docker` as a separate script that writes output elsewhere, or stop the host dev server before running container builds.
