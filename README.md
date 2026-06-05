# BarberPro

Barber shop appointment booking app. PHP/Symfony 7.2 REST API backend + Nuxt 3 (Vue 3) frontend.

## Stack

- **Backend:** PHP 8.3, Symfony 7.2, Doctrine ORM, PostgreSQL 16, JWT auth, Symfony Messenger
- **Frontend:** Nuxt 3, Vue 3, Pinia, vee-validate, Zod, Tailwind CSS
- **Infrastructure:** Docker (PHP-FPM, PostgreSQL, Caddy)

## Getting started

### Prerequisites

- Docker & Docker Compose
- Node.js 20+

### Setup

```bash
# Clone and start infrastructure
docker-compose up -d

# Install backend dependencies
docker compose exec php composer install

# Generate JWT keys
docker compose exec php symfony console lexik:jwt:generate-keypair

# Run migrations
docker compose exec php symfony console doctrine:migrations:migrate

# Install and start frontend
cd frontend
cp .env.example .env
npm install
npm run dev
```

### Environment

```bash
cp .env.example .env          # Backend
cp frontend/.env.example frontend/.env   # Frontend
```

Fill in your values — see comments in each file.

## Development

### Backend

```bash
docker compose exec php composer test    # PHPUnit tests
docker compose exec php composer lint    # PHP-CS-Fixer
docker compose exec php composer stan    # PHPStan (level 6)
```

### Frontend

```bash
cd frontend
npm run dev        # Dev server at http://localhost:3000
npm run test       # Vitest
npm run lint       # ESLint
npm run build      # Production build
```

## Architecture

### Backend (`src/`)

Domain modules: `Auth`, `Appointment`, `Shop`, `Client`, `Subscription`, `Reminder`, `Notification`, `PublicBooking`.

Each module follows the same layout: `Controller/` → `Service/` → `Dto/` → `Repository/`. Controllers are single-action classes with `#[Route]` attributes. Validation happens on readonly DTOs via Symfony Validator.

Auth: JWT access tokens + refresh token rotation (30-day TTL, stored in DB).

### Frontend (`frontend/`)

- `composables/useApi.ts` — `$fetch` wrapper with JWT injection and 401 auto-retry
- `composables/useAuth.ts` — login, register, Facebook OAuth, logout, profile update
- `stores/auth.ts` — Pinia user state
- `middleware/` — `auth.ts` and `guest.ts` route guards
- `schemas/` — Zod schemas used with vee-validate

i18n: Vietnamese (`vi`) default, English (`en`) fallback. Translation files in `i18n/locales/`.