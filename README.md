# Dental Practice Management

A single-clinic dental practice management system.

## Sub-projects

The system is decomposed into 7 phases. Each one ships with its own design doc + implementation plan + tests.

| # | Phase | Status |
|---|---|---|
| 0 | **Foundation** (auth, roles, settings, app shell, UI library) | ← you are here |
| 1 | Doctors & Appointments | planned |
| 2 | Patients & Treatment Records | planned |
| 3 | Inventory | planned |
| 4 | Billing, Payments & Ledger | planned |
| 5 | Money Exchange | planned |
| 6 | Notifications (SMS / WhatsApp) | planned |

## Stack

- Laravel 13 (PHP 8.4), MySQL
- Inertia.js 3 + Vue 3 (plain JavaScript, no TypeScript)
- daisyUI 5 themed via Tailwind v4 design tokens (`caremint` + `caremint-dark`)
- PHPUnit (backend), Vitest (frontend)

## Local development

```bash
# Start the docker stack
docker compose up -d

# Backend deps
docker exec dental_app composer install

# Frontend deps
npm install --ignore-scripts

# Migrate + seed
docker exec dental_app php artisan migrate:fresh --seed

# Dev server (run in two terminals)
docker exec dental_app php artisan serve        # :8000
npm run dev                                    # :5173, Vite HMR
```

Default admin: `admin@clinic.test` / `password`

## Quality gates

```bash
# All backend checks
docker exec dental_app composer quality
#  - pint --test
#  - phpstan analyse
#  - artisan test (phpunit)

# Frontend
npm run lint
npm test
npm run build
```

## Project layout

```
app/                 # Laravel backend
  Http/Controllers/  # web controllers
  Http/Requests/     # FormRequests
  Http/Resources/    # DTOs
  Http/Middleware/   # custom middleware
  Models/            # Eloquent
  Repositories/      # Contracts + Eloquent implementations
  Settings/          # ClinicSettings
  Support/           # cross-cutting helpers

resources/js/
  Pages/             # Inertia pages
  Layouts/           # Auth + Authenticated
  Components/ui/     # the wrapper design system
  Components/layout/ # Sidebar, TopBar, etc.
  Composables/       # useNav, useToast, useCurrentUser, ...
  types/             # JSDoc typedefs
  lib/               # money, timezone, inertia
  plugins/           # primevue.js

docs/
  superpowers/specs/ # design docs
  superpowers/plans/ # implementation plans
  architecture/decisions/  # ADRs
```

## ADRs

- [0001 — PrimeVue unstyled + Tailwind v4 tokens](docs/architecture/decisions/0001-primevue-unstyled-tailwind.md)
- [0002 — spatie/laravel-permission for roles](docs/architecture/decisions/0002-spatie-permissions.md)
- [0003 — Decomposition into sub-projects](docs/architecture/decisions/0003-decomposition-into-sub-projects.md)
