# Feature: Free Trial + Phone Number

## Status
Draft

## Overview
Every new shop automatically receives a 30-day PRO trial on first subscription access. After
30 days the subscription silently downgrades to FREE (50 appointments/month, status stays
`active`). Phone number is added as a required, unique field at registration to prevent one
barber from creating multiple accounts to reset the trial indefinitely.

## Dependencies

- Hard: Feature #07 (Subscription) — `Subscription` entity, `SubscriptionService`,
  `SubscriptionPlan`, `SubscriptionStatus`, `GetSubscriptionController`
- Hard: Feature #01 (Auth) — `User` entity, `AuthService::register()`, `RegisterRequest`,
  `UpdateProfileRequest`, `AuthService::serializeUser()`
- External: None (no SMS provider; no OTP at this stage)

---

## Domain Model

### Modified Entity: `Subscription`

**File**: `src/Entity/Subscription.php`

Add one new column:

| Field | PHP type | Column | Nullable | Default |
|-------|----------|--------|----------|---------|
| `trialEndsAt` | `?\DateTimeImmutable` | `trial_ends_at` | yes | `NULL` |

`NULL` means no trial was ever granted (pre-existing accounts). A non-null value means a trial
was started; if `trialEndsAt` is in the past the trial has expired.

**New computed method** (no DB column):

```php
public function isInTrial(): bool
{
    return $this->trialEndsAt !== null
        && $this->trialEndsAt > new \DateTimeImmutable();
}
```

**New index** on `trial_ends_at` to support the expiry command query:

```
idx_subscriptions_trial_ends_at — column: trial_ends_at (partial: WHERE trial_ends_at IS NOT NULL)
```

PostgreSQL supports partial indexes; Doctrine attribute mapping uses `options: ['where' => ...]`.

**Full new mapping addition** to the entity class:

```php
#[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
private ?\DateTimeImmutable $trialEndsAt = null;
```

Add getter and setter:

```php
public function getTrialEndsAt(): ?\DateTimeImmutable
{
    return $this->trialEndsAt;
}

public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): void
{
    $this->trialEndsAt = $trialEndsAt;
}

public function isInTrial(): bool
{
    return $this->trialEndsAt !== null
        && $this->trialEndsAt > new \DateTimeImmutable();
}
```

---

### Modified Entity: `User`

**File**: `src/Entity/User.php`

Add one new column:

| Field | PHP type | Column | Nullable | Unique | Length |
|-------|----------|--------|----------|--------|--------|
| `phoneNumber` | `?string` | `phone_number` | yes | yes | 20 |

`NULL` for users registered before this feature (email/password and Facebook). Required only
for new email/password registrations going forward. Facebook-registered users remain without
a phone number.

**New class-level attribute** (add alongside existing `UniqueConstraint` attributes):

```php
#[ORM\UniqueConstraint(name: 'uniq_user_phone_number', columns: ['phone_number'])]
```

**New property**:

```php
#[ORM\Column(length: 20, nullable: true, unique: true)]
private ?string $phoneNumber = null;
```

Add getter and setter:

```php
public function getPhoneNumber(): ?string
{
    return $this->phoneNumber;
}

public function setPhoneNumber(?string $phoneNumber): void
{
    $this->phoneNumber = $phoneNumber;
}
```

---

## Business Rules

1. Every new `Shop`'s first subscription access triggers trial creation: `plan = PRO`,
   `status = active`, `trialEndsAt = now + 30 days`, `endDate = null`.
2. While `isInTrial()` is `true`, `canCreateAppointment()` returns `true` (unlimited, same as PRO).
3. The trial can only be granted once per subscription (per shop). If `trialEndsAt` is already
   set, `createTrialForShop()` must not overwrite it.
4. When the trial expires (`trialEndsAt < now`): `plan = FREE`, `status = active`,
   `endDate = null`. `trialEndsAt` is preserved as a historical marker (not reset to null).
5. A shop on an expired trial is indistinguishable from a regular FREE shop for feature access.
6. If the owner manually upgrades to paid PRO during the trial: trial fields are preserved but
   `plan = PRO` and `endDate` are set as normal for a paid subscription. `isInTrial()` returning
   `true` alongside an active paid PRO is benign — both grant unlimited access.
7. `phoneNumber` is required at email/password registration. Facebook registration does not
   require a phone number.
8. `phoneNumber` must be stored in E.164 format (e.g. `+84901234567`). Normalization happens
   in `AuthService::register()` before any uniqueness check.
9. If a normalized phone number already exists in the `users` table: return
   `PHONE_ALREADY_IN_USE` (HTTP 422).
10. `phoneNumber` is excluded from `UpdateProfileRequest` — it cannot be changed after
    registration. Rationale: changing phone allows an abuser to discard a used phone number
    and grab another trial.
11. `phoneNumber` is included in the `GET /api/v1/auth/me` response.
12. Trial expiry runs via a Symfony console command `app:subscriptions:expire-trials`, expected
    to run daily via cron at 08:00 ICT. The command logs how many trials it expired.

---

## API Endpoints

### GET /api/v1/subscription (modified)

- **Purpose**: Returns the authenticated user's shop subscription including trial status.
- **Auth**: `ROLE_USER` (JWT)
- **Rate limit**: existing (60/min general API)
- **Request**: no body, no query params

**Response 200 — updated shape** (new fields in bold):

```json
{
  "id": "01951234-...",
  "plan": "pro",
  "status": "active",
  "startDate": "2026-03-25T08:00:00+07:00",
  "endDate": null,
  "trial": {
    "isInTrial": true,
    "trialEndsAt": "2026-04-24T08:00:00+07:00",
    "trialDaysRemaining": 29
  },
  "usage": {
    "appointmentsThisMonth": 3,
    "appointmentLimit": null,
    "limitReached": false
  }
}
```

When not in trial (trial expired or never started):

```json
{
  "trial": {
    "isInTrial": false,
    "trialEndsAt": null,
    "trialDaysRemaining": null
  }
}
```

**Field rules**:
- `trial.trialEndsAt`: ISO 8601 with timezone, or `null` if `Subscription.trialEndsAt` is null.
- `trial.isInTrial`: `Subscription::isInTrial()`.
- `trial.trialDaysRemaining`: `null` when `!isInTrial`. When `isInTrial`: `max(0, ceil(diff days))`.
  Use `(int) ceil($subscription->getTrialEndsAt()->diff($now)->days)` with appropriate sign check.
  Returns `0` on the last day.
- Remove the existing top-level `daysRemaining` field that was only present for paid PRO with
  an `endDate`. Replace entirely with the `trial` object above.
- `endDate`: remains `null` for trial subscriptions. Only set for paid PRO.
- `plan` during trial: `"pro"` (the shop has PRO-level access during the trial).

**Note on the existing `daysRemaining` field**: the controller currently only emits `daysRemaining`
when `plan = PRO && endDate !== null`. With the trial model, `endDate` remains `null` during
the trial. Remove the legacy `daysRemaining` field and replace it with the `trial` object for
all responses. This is a breaking change — document it in the frontend spec.

---

### POST /api/v1/auth/register (modified)

- **Purpose**: Register a new account, now requiring a phone number.
- **Auth**: none
- **Rate limit**: existing (5/1h)

**Request body — new field**:

```json
{
  "email": "barber@example.com",
  "password": "securepass",
  "firstName": "Nguyen",
  "lastName": "Van A",
  "locale": "vi",
  "phoneNumber": "0901234567"
}
```

`phoneNumber` accepts:
- `0901234567` — Vietnamese mobile (leading zero)
- `+84901234567` — E.164 already
- `84901234567` — missing `+`
- Spaces and dashes are stripped before validation: `090-123 4567` → `0901234567`

**Validation errors (400)**:

```json
{
  "code": "VALIDATION_ERROR",
  "message": "Validation failed.",
  "details": [
    { "field": "phoneNumber", "message": "Phone number is required." },
    { "field": "phoneNumber", "message": "Phone number format is invalid." }
  ]
}
```

**Business rule violation (422)**:

```json
{
  "code": "PHONE_ALREADY_IN_USE",
  "message": "This phone number is already registered."
}
```

**Success 200**: unchanged (returns `user`, `token`, `refreshToken`), plus `user.phoneNumber` is
now present in the serialized user object.

---

### GET /api/v1/auth/me (modified)

**Response — added field**:

```json
{
  "id": "...",
  "email": "barber@example.com",
  "firstName": "Nguyen",
  "lastName": "Van A",
  "locale": "vi",
  "avatarUrl": null,
  "phoneNumber": "+84901234567"
}
```

`phoneNumber` is `null` for users without a phone (Facebook-registered or pre-migration accounts).

---

### PUT /api/v1/auth/me (no change)

`phoneNumber` is intentionally not accepted. If the client sends `phoneNumber` in the request
body, it is ignored (not mapped by `UpdateProfileRequest`). No error is returned — extra fields
are silently discarded by `#[MapRequestPayload]`.

---

## Service Layer

### Modified: `SubscriptionService`

**File**: `src/Subscription/Service/SubscriptionService.php`

**Rename** `createFreeForShop()` → **`createTrialForShop()`**. This is the only call site for
new subscription creation. The method now creates a PRO trial instead of a FREE subscription.

```php
public function createTrialForShop(Shop $shop): Subscription
{
    $tz = new \DateTimeZone(self::TZ_NAME);
    $now = new \DateTimeImmutable('now', $tz);
    $trialEndsAt = $now->modify('+' . self::TRIAL_DURATION_DAYS . ' days');

    $subscription = new Subscription();
    $subscription->setShop($shop);
    $subscription->setPlan(SubscriptionPlan::PRO);
    $subscription->setStatus(SubscriptionStatus::ACTIVE);
    $subscription->setStartDate($now);
    $subscription->setEndDate(null);
    $subscription->setTrialEndsAt($trialEndsAt);
    $subscription->setMonthlyAppointmentCount(0);
    $subscription->setCountResetAt($now->modify('first day of this month')->setTime(0, 0));

    $this->em->persist($subscription);
    $this->em->flush();

    return $subscription;
}
```

Add constant:
```php
public const int TRIAL_DURATION_DAYS = 30;
```

**Update `getByShop()`**: call `createTrialForShop()` instead of `createFreeForShop()`:

```php
public function getByShop(Shop $shop): Subscription
{
    $subscription = $this->subscriptionRepository->findByShop($shop);
    if ($subscription === null) {
        return $this->createTrialForShop($shop);
    }
    return $subscription;
}
```

**Update `canCreateAppointment()`**: no change needed. During trial, `plan = PRO`, so the
existing `if ($subscription->getPlan() === SubscriptionPlan::PRO) { return true; }` already
handles trial access correctly.

**Add new method `expireOverdueTrials()`**:

```php
public function expireOverdueTrials(): int
{
    $tz = new \DateTimeZone(self::TZ_NAME);
    $now = new \DateTimeImmutable('now', $tz);

    $overdue = $this->subscriptionRepository->findOverdueTrials($now);
    foreach ($overdue as $subscription) {
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setEndDate(null);
        $subscription->setMonthlyAppointmentCount(0);
        $subscription->setCountResetAt($now->modify('first day of this month')->setTime(0, 0));
        // trialEndsAt is intentionally preserved as a historical marker
    }

    $this->em->flush();

    return count($overdue);
}
```

**Note on `expireOverdueSubscriptions()`**: this existing method handles paid PRO subscriptions
(`endDate < now`). Trial subscriptions have `endDate = null`, so they are NOT picked up by
`expireOverdueSubscriptions()`. The two expiry paths are entirely separate.

---

### Modified: `AuthService`

**File**: `src/Auth/Service/AuthService.php`

**Add dependency**: `UserRepository` already injected. No new dependencies needed.

**Update `register()`**:

```php
public function register(RegisterRequest $dto): array
{
    if ($this->userRepository->findByEmail($dto->email) !== null) {
        throw new ApiException('EMAIL_ALREADY_EXISTS', 'A user with this email already exists.', 409);
    }

    $normalizedPhone = $this->normalizePhoneNumber($dto->phoneNumber);

    if ($this->userRepository->findByPhoneNumber($normalizedPhone) !== null) {
        throw new ApiException('PHONE_ALREADY_IN_USE', 'This phone number is already registered.', 422);
    }

    $user = new User();
    $user->setEmail($dto->email);
    $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
    $user->setFirstName($dto->firstName);
    $user->setLastName($dto->lastName);
    $user->setLocale($dto->locale);
    $user->setPhoneNumber($normalizedPhone);

    $this->em->persist($user);

    try {
        $this->em->flush();
    } catch (UniqueConstraintViolationException) {
        // Race condition: email or phone taken between check and flush
        throw new ApiException('EMAIL_ALREADY_EXISTS', 'A user with this email already exists.', 409);
    }

    return $this->buildAuthResponse($user);
}
```

**Add private method `normalizePhoneNumber()`**:

```php
private function normalizePhoneNumber(string $phone): string
{
    // Strip spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);

    // Already E.164
    if (str_starts_with($phone, '+')) {
        return $phone;
    }

    // 84XXXXXXXXX → +84XXXXXXXXX
    if (str_starts_with($phone, '84') && strlen($phone) === 11) {
        return '+' . $phone;
    }

    // 0XXXXXXXXX → +840XXXXXXXXX (wrong) — must strip leading zero
    // Vietnamese format: 0XXXXXXXXX (10 digits) → +84XXXXXXXXX (remove leading 0)
    if (str_starts_with($phone, '0')) {
        return '+84' . substr($phone, 1);
    }

    // Return as-is if pattern unrecognized (validator will reject it anyway)
    return $phone;
}
```

**Update `serializeUser()`**:

```php
public static function serializeUser(User $user): array
{
    return [
        'id'          => (string) $user->getId(),
        'email'       => $user->getEmail(),
        'firstName'   => $user->getFirstName(),
        'lastName'    => $user->getLastName(),
        'locale'      => $user->getLocale()->value,
        'avatarUrl'   => $user->getAvatarUrl(),
        'phoneNumber' => $user->getPhoneNumber(),
    ];
}
```

---

## Console Commands

### New: `app:subscriptions:expire-trials`

**File**: `src/Subscription/Command/ExpireTrialsCommand.php`

```php
#[AsCommand(
    name: 'app:subscriptions:expire-trials',
    description: 'Downgrade subscriptions whose free trial period has ended',
)]
final class ExpireTrialsCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->subscriptionService->expireOverdueTrials();
        $io->success(sprintf('Expired %d trial(s). Downgraded to FREE plan.', $count));
        return Command::SUCCESS;
    }
}
```

**Cron schedule**: `0 1 * * *` UTC (= 08:00 ICT, UTC+7).

**Expected output**:
```
[OK] Expired 3 trial(s). Downgraded to FREE plan.
```

**Idempotency**: Safe to run multiple times — `findOverdueTrials()` only returns subscriptions
where `plan = PRO AND trialEndsAt < now`, so already-expired trials (now on FREE) are
not touched again.

---

## Repository Changes

### Modified: `SubscriptionRepository`

**File**: `src/Repository/SubscriptionRepository.php`

**Add method `findOverdueTrials()`**:

```php
/**
 * @return Subscription[]
 */
public function findOverdueTrials(\DateTimeImmutable $now): array
{
    return $this->createQueryBuilder('s')
        ->where('s.trialEndsAt IS NOT NULL')
        ->andWhere('s.trialEndsAt < :now')
        ->andWhere('s.plan = :plan')
        ->andWhere('s.status = :status')
        ->setParameter('plan', SubscriptionPlan::PRO)
        ->setParameter('status', SubscriptionStatus::ACTIVE)
        ->setParameter('now', $now)
        ->getQuery()
        ->getResult();
}
```

The `plan = PRO AND status = ACTIVE` guards ensure already-expired trials (now FREE) and
cancelled subscriptions are excluded.

---

### Modified: `UserRepository`

**File**: `src/Repository/UserRepository.php`

**Add method `findByPhoneNumber()`**:

```php
public function findByPhoneNumber(string $phoneNumber): ?User
{
    return $this->findOneBy(['phoneNumber' => $phoneNumber]);
}
```

---

## DTO Changes

### Modified: `RegisterRequest`

**File**: `src/Auth/Dto/RegisterRequest.php`

Add `phoneNumber` field:

```php
#[Assert\NotBlank(message: 'Phone number is required.')]
#[Assert\Regex(
    pattern: '/^\+?[0-9]{9,15}$/',
    message: 'Phone number format is invalid.',
)]
#[Assert\Length(max: 20)]
public string $phoneNumber = '',
```

**Validation order**: Symfony Validator runs `NotBlank` first. If blank, subsequent constraints
are skipped. The `Regex` constraint validates the raw input value after spaces/dashes are
stripped in `AuthService::normalizePhoneNumber()`. Note: the DTO validation runs on the raw
input; normalization runs after validation passes. This means the regex must accept both
`0901234567` and `+84901234567` formats.

Regex `/^\+?[0-9]{9,15}$/` (after stripping spaces/dashes in service):
- `+84901234567` → matches (12 digits after +)
- `0901234567` → matches (10 digits)
- `84901234567` → matches (11 digits)
- `090 123 4567` → stripped → `0901234567` → but stripping happens in service, not DTO

**Important**: the stripping happens in `AuthService::normalizePhoneNumber()` AFTER DTO validation.
To validate pre-stripped input, update the regex to allow spaces and dashes in the DTO:

```php
#[Assert\Regex(
    pattern: '/^\+?[\d\s\-]{9,20}$/',
    message: 'Phone number format is invalid.',
)]
```

And then `normalizePhoneNumber()` strips spaces/dashes and validates digit count internally.
If digit count is wrong after stripping, `normalizePhoneNumber()` returns the cleaned but
invalid string, and the DB unique constraint will not fail — but the normalized number will be
unusual. Consider adding a digit-count validation in `AuthService` after normalization:

```php
$digitsOnly = preg_replace('/\D/', '', $normalizedPhone);
if (strlen($digitsOnly) < 9 || strlen($digitsOnly) > 15) {
    throw new ApiException('VALIDATION_ERROR', 'Phone number format is invalid.', 400);
}
```

---

### Not Modified: `UpdateProfileRequest`

**File**: `src/Auth/Dto/UpdateProfileRequest.php`

No changes. `phoneNumber` is intentionally absent. This is the enforcement mechanism — since
`#[MapRequestPayload]` only maps declared properties, any `phoneNumber` in the request body
is silently ignored.

Add a comment in the file explaining why:

```php
// phoneNumber is intentionally excluded: it cannot be changed after registration
// to prevent trial reset abuse (registering a new account with the same phone).
```

---

## GetSubscriptionController Changes

**File**: `src/Subscription/Controller/GetSubscriptionController.php`

Replace the existing `daysRemaining` conditional with the new `trial` object:

```php
$tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
$now = new \DateTimeImmutable('now', $tz);
$subscription = $this->subscriptionService->getByShop($shop);

$trialEndsAt = $subscription->getTrialEndsAt();
$isInTrial = $subscription->isInTrial();

$trialDaysRemaining = null;
if ($isInTrial && $trialEndsAt !== null) {
    $diff = $now->diff($trialEndsAt);
    $trialDaysRemaining = $diff->invert === 0 ? (int) $diff->days : 0;
}

$data = [
    'id'        => (string) $subscription->getId(),
    'plan'      => $subscription->getPlan()->value,
    'status'    => $subscription->getStatus()->value,
    'startDate' => $subscription->getStartDate()->setTimezone($tz)->format(\DateTimeInterface::ATOM),
    'endDate'   => $subscription->getEndDate()?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
    'trial'     => [
        'isInTrial'          => $isInTrial,
        'trialEndsAt'        => $trialEndsAt?->setTimezone($tz)->format(\DateTimeInterface::ATOM),
        'trialDaysRemaining' => $trialDaysRemaining,
    ],
    'usage' => [
        'appointmentsThisMonth' => $subscription->getMonthlyAppointmentCount(),
        'appointmentLimit'      => $subscription->getPlan() === SubscriptionPlan::PRO ? null : SubscriptionService::FREE_APPOINTMENT_LIMIT,
        'limitReached'          => $subscription->getPlan() === SubscriptionPlan::FREE
            && $subscription->getMonthlyAppointmentCount() >= SubscriptionService::FREE_APPOINTMENT_LIMIT,
    ],
];

return new JsonResponse($data);
```

Note: the existing `daysRemaining` top-level field is removed. If the frontend currently reads
`daysRemaining`, it must be updated to read `trial.trialDaysRemaining`.

---

## Security and Authorization

No new roles or voters. All modified endpoints use existing `ROLE_USER` JWT authentication.

**Phone number is PII**: store only the normalized E.164 value. Do not log phone numbers.
If `ApiExceptionListener` logs request bodies, ensure `phoneNumber` is masked in logs.

---

## Error Handling

| Scenario | Exception | HTTP code | Error code |
|----------|-----------|-----------|------------|
| Phone number missing at registration | Symfony validator | 400 | `VALIDATION_ERROR` |
| Phone number format invalid | Symfony validator | 400 | `VALIDATION_ERROR` |
| Phone number already registered | `ApiException` | 422 | `PHONE_ALREADY_IN_USE` |
| Race: phone uniqueness race condition | `UniqueConstraintViolationException` catch | 409 | `EMAIL_ALREADY_EXISTS` |
| Trial expiry command DB error | Exception propagates | — | Command exits with error |

**Race condition note**: The `UniqueConstraintViolationException` catch in `register()` currently
throws `EMAIL_ALREADY_EXISTS`. With two unique fields (email + phone), the catch block cannot
distinguish which field caused the violation without parsing the DB error message. Simplest
approach: keep throwing `EMAIL_ALREADY_EXISTS` from the catch block. The explicit pre-checks
handle the common case correctly; the race condition is rare.

---

## Database

### Migration: `Version20260325000001.php`

```php
public function getDescription(): string
{
    return 'Add trial_ends_at to subscriptions and phone_number to users';
}

public function up(Schema $schema): void
{
    // Subscription: add trial_ends_at
    $this->addSql('ALTER TABLE subscriptions ADD COLUMN trial_ends_at TIMESTAMPTZ DEFAULT NULL');
    $this->addSql(
        'CREATE INDEX idx_subscriptions_trial_ends_at ON subscriptions (trial_ends_at) WHERE trial_ends_at IS NOT NULL'
    );

    // User: add phone_number
    $this->addSql('ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL');
    $this->addSql(
        'CREATE UNIQUE INDEX uniq_user_phone_number ON users (phone_number) WHERE phone_number IS NOT NULL'
    );
}

public function down(Schema $schema): void
{
    $this->addSql('DROP INDEX idx_subscriptions_trial_ends_at');
    $this->addSql('ALTER TABLE subscriptions DROP COLUMN trial_ends_at');

    $this->addSql('DROP INDEX uniq_user_phone_number');
    $this->addSql('ALTER TABLE users DROP COLUMN phone_number');
}
```

**Migration strategy for existing rows**:
- `trial_ends_at`: `NULL` on existing subscriptions — no backfill needed. Existing subscriptions
  are not in trial; `isInTrial()` returns `false` for null.
- `phone_number`: `NULL` on existing users — correct, they predate the feature. The unique index
  uses `WHERE phone_number IS NOT NULL` so multiple `NULL` values are allowed.

**Do NOT backfill** existing subscriptions with a trial. Only new registrations receive trials.

---

## Frontend Changes (High-Level — Detailed Spec Separate)

### Registration Page

- Add `phoneNumber` field to the registration form between `lastName` and `locale`.
- Zod schema: `z.string().min(1).regex(/^\+?[\d\s\-]{9,20}$/)`.
- Display field error for `PHONE_ALREADY_IN_USE` response as a non-field alert (same pattern
  as `EMAIL_ALREADY_EXISTS`).
- i18n key: `auth.register.phoneNumber`, `auth.errors.phoneAlreadyInUse`.

### Subscription Page / Dashboard

- Read `subscription.trial.isInTrial` and `subscription.trial.trialDaysRemaining`.
- Show a banner when `isInTrial: true`:
  - `> 7 days`: info banner — "Bạn đang dùng thử PRO. Còn X ngày."
  - `<= 7 days`: warning banner — "Thử nghiệm PRO sắp kết thúc. Còn X ngày."
- When `isInTrial: false` and `plan = free`: no trial banner.
- Remove any existing logic that reads the old top-level `daysRemaining` field.

---

## Testing Strategy

### Unit Tests — `SubscriptionService`

| Test | What to assert |
|------|---------------|
| `createTrialForShop()` sets `plan = PRO` | plan is `PRO` on created subscription |
| `createTrialForShop()` sets `trialEndsAt` to now + 30 days | trialEndsAt ≈ now + 30 days (within 1s tolerance) |
| `createTrialForShop()` sets `endDate = null` | endDate is null |
| `canCreateAppointment()` returns true during trial | plan = PRO → existing logic passes |
| `expireOverdueTrials()` with no overdue trials | returns 0, no flush |
| `expireOverdueTrials()` downgrades to FREE + ACTIVE | plan = FREE, status = ACTIVE, trialEndsAt unchanged |
| `expireOverdueTrials()` does not touch already-expired (plan = FREE) | only PRO subscriptions are fetched |
| `expireOverdueSubscriptions()` does not touch trial subscriptions (endDate = null) | trial sub not in result set |

Mock: `SubscriptionRepository`, `EntityManagerInterface`.

### Unit Tests — `AuthService`

| Test | What to assert |
|------|---------------|
| `register()` with valid phone | `User.phoneNumber` = normalized E.164 |
| `register()` normalizes `0901234567` → `+84901234567` | normalized value stored |
| `register()` normalizes `+84901234567` → `+84901234567` | unchanged |
| `register()` when phone already exists | throws `PHONE_ALREADY_IN_USE` (422) |
| `register()` when email already exists | throws `EMAIL_ALREADY_EXISTS` (409) |
| `serializeUser()` includes `phoneNumber` | key present in returned array |
| `serializeUser()` with null phone | `phoneNumber: null` |

Mock: `UserRepository`, `EntityManagerInterface`, `UserPasswordHasherInterface`, `JWTTokenManagerInterface`.

### Unit Tests — `Subscription` entity

| Test | What to assert |
|------|---------------|
| `isInTrial()` when `trialEndsAt` is null | returns false |
| `isInTrial()` when `trialEndsAt` is in the future | returns true |
| `isInTrial()` when `trialEndsAt` is in the past | returns false |

No mocks needed — pure entity logic.

### Unit Tests — `RegisterRequest` DTO

| Test | What to assert |
|------|---------------|
| `phoneNumber = ''` | validation fails with `VALIDATION_ERROR` |
| `phoneNumber = 'abc'` | validation fails |
| `phoneNumber = '0901234567'` | validation passes |
| `phoneNumber = '+84901234567'` | validation passes |
| `phoneNumber = '090 123 4567'` | validation passes (regex allows spaces) |

Use Symfony Validator component directly — no mocks needed.

### Unit Tests — `ExpireTrialsCommand`

| Test | What to assert |
|------|---------------|
| Command calls `expireOverdueTrials()` | method called exactly once |
| Command displays success message | output contains count |
| Command returns `Command::SUCCESS` | exit code = 0 |

Mock: `SubscriptionService`.

### Edge Cases Worth Testing

1. Two concurrent registrations with the same phone number — one should get `PHONE_ALREADY_IN_USE`.
   Test the catch-block path by mocking `UniqueConstraintViolationException` from `em->flush()`.
2. `expireOverdueTrials()` called when a shop has paid PRO active (endDate set, trialEndsAt in past)
   — should NOT downgrade. The query filters `plan = PRO AND status = ACTIVE AND trialEndsAt < now`,
   but paid PRO with trialEndsAt in the past would be fetched. Add `AND endDate IS NULL` to the
   query to guard against this (trial subs have `endDate = null`; paid PRO has `endDate` set).
3. `getByShop()` called twice on a shop with no subscription — second call hits the existing
   subscription (not created twice). Standard lazy-creation pattern, already tested via existing suite.
4. Facebook-registered user has `phoneNumber = null` — `serializeUser()` must not fail.

---

## Implementation Notes

**Suggested implementation order**:
1. Migration — run `doctrine:migrations:diff` after entity changes and review generated SQL.
2. `Subscription` entity — add `trialEndsAt` field + getter/setter + `isInTrial()`.
3. `User` entity — add `phoneNumber` field + getter/setter.
4. `SubscriptionService` — rename `createFreeForShop()` → `createTrialForShop()`, add
   `expireOverdueTrials()`. Update `getByShop()`.
5. `SubscriptionRepository` — add `findOverdueTrials()`. Guard `AND endDate IS NULL`.
6. `UserRepository` — add `findByPhoneNumber()`.
7. `RegisterRequest` — add `phoneNumber` field.
8. `AuthService` — add normalization, uniqueness check, phone assignment, update `serializeUser()`.
9. `GetSubscriptionController` — replace `daysRemaining` with `trial` object.
10. `ExpireTrialsCommand` — new file.
11. Unit tests — write after each class is complete.

**`createFreeForShop()` rename side effects**: grep for all usages:
- `SubscriptionService::getByShop()` — update call site
- `BackfillMissingSubscriptionsCommand` (if it calls `createFreeForShop()`) — check and update
  to pass `false` for trial (backfill should NOT grant trials to existing shops)

For the backfill command, add an explicit `createFreeForShop()` private method that creates
a plain FREE subscription (no trial), and keep `createTrialForShop()` for new registrations.
This avoids accidentally granting trials to old accounts on the next backfill run.

**Timezone note**: `isInTrial()` uses `new \DateTimeImmutable()` which defaults to PHP's
configured timezone. The rest of the codebase uses `Asia/Ho_Chi_Minh` explicitly. For
consistency, pass `$now` as a parameter to `isInTrial()` or always construct it with the TZ:

```php
public function isInTrial(): bool
{
    if ($this->trialEndsAt === null) {
        return false;
    }
    $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
    return $this->trialEndsAt > $now;
}
```

**Facebook registration and trials**: Facebook users also get the trial when they first access
their subscription (via `createTrialForShop()` called lazily from `getByShop()`). They do not
provide a phone number. This is an accepted trade-off — Facebook account creation is a higher
barrier than email account creation. Document this in the implementation if questioned.

---

## Open Questions

1. **Trial expiry notification**: Should the barber receive an email when their trial expires
   or is about to expire? This is a separate feature (#26 in the pre-launch plan) — out of scope
   here. The `expireOverdueTrials()` method should be designed to return the list of expired
   subscriptions so that #26 can dispatch emails from the same command without a second DB query.
   Consider returning `Subscription[]` instead of `int` from `expireOverdueTrials()`.

2. **Backfill for existing shops**: Existing shops have no trial. The spec explicitly says do not
   backfill. Confirm this is the intended behavior — the first barbers you recruit manually will
   not get a trial unless they register fresh accounts.

3. **Phone normalization for non-Vietnamese numbers**: The current normalizer assumes Vietnamese
   format (`0XXXXXXXXX`). For future international expansion, a proper phone library
   (`giggsey/libphonenumber-for-php`) would be more robust. Flag for post-launch upgrade.

---

## Codebase Alignment Notes

- This spec follows the single-action controller pattern (`#[AsController]`, `__invoke`).
- Console commands follow the existing pattern: `#[AsCommand]`, `extends Command`, `SymfonyStyle`
  for output. Reference: `ExpireProSubscriptionsCommand`.
- DTOs are `final readonly` with constructor property promotion and `#[Assert\*]` attributes.
  Reference: `RegisterRequest`.
- Repositories use `createQueryBuilder()` for filtered queries. Reference: `findOverdueProSubscriptions()`.
- Migrations use raw SQL via `$this->addSql()`. Reference: `Version20260322000000`.
- **New dependency introduced**: none. All patterns and dependencies already exist in the project.
