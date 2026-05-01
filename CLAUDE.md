# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TriggerTime Site is a full-stack web application with a **CakePHP 5.3 REST API backend** and a **Vue 3 SPA frontend**. It manages devices, subscriptions (via Stripe), activation licenses, and remote configuration for mobile apps.

## Development Environment

- **Local dev:** DDEV (`triggertime.ddev.site`)
- **PHP:** >=8.2
- **Node:** See `client/package.json`

## Common Commands

### Backend (PHP/CakePHP) — run from project root

```bash
composer check          # Run tests + code sniffer
composer test           # Run PHPUnit tests only
composer cs-check       # Check coding standards (CakePHP standard via PHPCS)
composer cs-fix         # Auto-fix coding standards (PHPCBF)
phpstan                 # Static analysis (level 8, needs SECURITY_SALT env var)
```

Run a single test:
```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/DevicesControllerTest.php
vendor/bin/phpunit --filter testMethodName
```

### Frontend (Vue 3 + Vite) — run from `client/`

```bash
npm run dev             # Start Vite dev server (port 5173, HMR via WSS)
npm run build           # Production build → ../webroot/spa/
npm run preview         # Preview production build
```

### Database Migrations

```bash
bin/cake migrations migrate        # Run pending migrations
bin/cake migrations rollback       # Rollback last migration
```

## Architecture

### Backend (`src/`)

CakePHP 5.3 app structured as a REST API. All API routes return JSON.

**API route scopes** (`config/routes.php`):
- `/api/v1/` — Mobile app endpoints (API Key auth via `ApiKeyMiddleware`)
- `/api/v1/web/` — Web SPA endpoints (JWT auth via `JwtMiddleware`)
- `/api/v1/admin/` — Admin endpoints (JWT + `AdminRoleMiddleware`)
- `/api/v1/webhooks/stripe` — Stripe webhook (signature verification)
- All other routes → `PagesController::spa` (serves the Vue SPA)

**Key middleware stack** (in `src/Application.php`):
`ErrorHandler → ApiError → HostHeader → Asset → Routing → BodyParser → CSRF (skipped for /api/*)`

**Authentication:** Custom JWT (HS256) via `src/Service/JwtService.php`. Tokens contain `sub` (user ID), `email`, `role`. Secret is `Security.salt`.

**User roles:** `user`, `club_admin`, `admin`

**Coding standard:** CakePHP standard via PHPCS (`phpcs.xml`). Return type hint rule is relaxed for controllers.

**Tests:** PHPUnit with CakePHP fixtures. CI uses SQLite (`DATABASE_TEST_URL=sqlite://./testdb.sqlite`).

### Frontend (`client/src/`)

Vue 3 SPA using Composition API, built with Vite.

- **State management:** Pinia (`stores/auth.js`)
- **Routing:** Vue Router (`router/index.js`) — guards: `requiresAuth`, `requiresGuest`, `requiresAdminRole`, `requiresSuperAdmin`
- **HTTP client:** Axios with interceptor for Bearer token injection and 401 auto-logout (`api/index.js`)
- **API modules:** `api/auth.js`, `api/devices.js`, `api/subscriptions.js`, `api/admin.js`
- **i18n:** vue-i18n with 8 languages (es default). Locale files in `i18n/locales/`. Detection: localStorage → browser → English fallback.
- **Path alias:** `@` → `client/src/`
- **Views structure:** `views/landing/`, `views/public/` (auth pages), `views/dashboard/`, `views/admin/`
- **Reusable components:** `components/ui/` (AppButton, AppModal, AppInput, etc.)

**Auth token storage:** `localStorage` key `tt_token`

### Build Output

Vite builds to `webroot/spa/` which is served by the CakePHP backend. The SPA catch-all route in CakePHP serves `templates/Pages/spa.php` for all non-API routes.

## CI/CD

GitHub Actions (`.github/workflows/ci.yml`):
- **Test matrix:** PHP 8.2 (lowest deps) + PHP 8.5 (highest deps) with SQLite
- **Code quality:** PHPCS + PHPStan (level 8)
- Runs on push to `5.x`/`5.next`/`6.x` and all pull requests

## Design Context

### Users
Broad audience spanning competitive ISSF/national-level shooters, recreational range-goers, and club administrators managing teams and licenses. Users typically review their data after range sessions — often in the evening at home or between rounds at the range. The web dashboard is secondary to the mobile app; it's where users go for deeper analysis, data management, subscription handling, and admin tasks.

### Brand Personality
**Precise, focused, serious.** TriggerTime is an instrument, not a toy. Think of the confidence a shooter feels picking up a well-maintained firearm — reliable, purposeful, nothing wasted. The brand mark is a crosshair target in electric green on black, reinforcing the "locked on" feeling.

### Emotional Goal
**Excitement and progress.** When a shooter opens the dashboard, the feeling should be: "I can see how much I've improved." Data should feel motivating, not clinical. Celebrate milestones and trends without being noisy or gamified.

### Aesthetic Direction
- **Theme**: Dark. Shooters review data in low-light contexts (evenings, range rest areas). Dark feels native to the sport.
- **Palette**: Olive green primary (#7CB342 brand green) on near-black surfaces tinted toward the brand hue. Warm accent (#C1693C) for secondary emphasis. The green should feel like a sight reticle — natural and precise, not neon.
- **Typography**: Space Grotesk (headings, matching logo) + Figtree (body). No Inter, Outfit, or other AI-default fonts.
- **Surfaces**: OKLCH-based neutrals with subtle green tint (hue 145). No glassmorphism, no glow shadows, no `backdrop-filter: blur`.
- **Tone**: Technical confidence. Closer to a precision instrument's interface than a social fitness app. Dense where it matters (session data, scores), spacious where it guides (landing page, onboarding).
- **Anti-references**: Avoid looking like a generic SaaS dashboard, a gaming UI, or a military/tactical cliche. No camo, no skulls, no aggressive red/black schemes.

### Design Principles
1. **Precision over decoration** — Every element should earn its place. If it doesn't help the shooter understand their data or take action, remove it.
2. **Progress is the hero** — Design for the moment a shooter sees improvement. Charts, scores, and trends should be the most prominent elements, not chrome.
3. **Instrument-grade clarity** — Labels, numbers, and states must be instantly readable. Ambiguity is failure in a precision sport.
4. **Respect the breadth** — The interface serves first-time recreational shooters and seasoned ISSF competitors alike. Progressive disclosure: simple surface, depth on demand.
5. **International by default** — 8 languages, metric and imperial, ISSF and local formats. Never assume a single locale.
