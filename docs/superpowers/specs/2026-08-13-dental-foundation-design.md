# Dental Practice Management — Foundation (Sub-project 0)

**Date:** 2026-08-13
**Status:** Approved (pending)
**Author:** Brainstorming session
**Stack:** Laravel 13 + Inertia.js 2 + Vue 3 (JavaScript, **not** TypeScript) + PrimeVue 4 (unstyled) + Tailwind v4
**Scope:** First of 6-7 sub-projects. Everything that downstream sub-projects build on.

---

## 0. Decomposition context

The full dental practice management system contains 9+ independent subsystems (ledger, treatment records, doctors, payments, billing, expenses, money exchange, inventory, appointments, notifications). Building all of them in a single design spec produces a vague wishlist that grows into rework. We decompose into sub-projects, each with its own design → plan → build cycle.

| # | Sub-project | Depends on |
|---|---|---|
| **0** | **Foundation** *(this doc)* | — |
| 1 | Doctors & Appointments | Foundation |
| 2 | Patients & Treatment Records | Foundation |
| 3 | Inventory | Foundation |
| 4 | Billing, Payments & Ledger | Foundation, Patients |
| 5 | Money Exchange | Foundation, Billing |
| 6 | Notifications (SMS / WhatsApp) | Foundation, Appointments, Billing |

When sub-project 1 is designed, the IA already exists, the component library is in place, and the design tokens are decided. Sub-projects focus on their domain, not on scaffolding.

---

## 1. High-level architecture

### 1.1 Backend (Laravel 13)

- **Domain logic** in `app/Domain/<Area>/` (later sub-projects add `Doctors/`, `Patients/`, `Billing/`, etc.)
- **HTTP layer** in `app/Http/Controllers/<Area>/`, with `app/Http/Requests/` for FormRequests and `app/Http/Middleware/` for custom middleware
- **Persistence** — Eloquent models in `app/Models/`, repository pattern with interfaces in `app/Repositories/` and `Eloquent` implementations
- **Cross-cutting** code in `app/Support/` (helpers, value objects, `Money` value object)
- **Settings** — `spatie/laravel-settings` exposing a typed `ClinicSettings` class
- **Authorization** — `spatie/laravel-permission` with roles `admin`, `doctor`, `receptionist`

### 1.2 Frontend (Inertia + Vue 3)

- **Routes** — `routes/web.php` returns Inertia responses, not Blade views
- **Pages** — `resources/js/Pages/<Area>/<Page>.vue`
- **Layouts** — `resources/js/Layouts/{Authenticated,Auth}.vue`
- **UI components** — `resources/js/Components/ui/*` (wrapper layer that re-exports PrimeVue unstyled with our Tailwind theme applied)
- **Layout components** — `resources/js/Components/layout/*` (sidebar, top bar, etc.)
- **Composables** — `resources/js/Composables/*` (reusable client logic)
- **Types** — `resources/js/types/` (JSDoc `@typedef` definitions mirroring backend DTOs; no TypeScript)
- **Tailwind v4 design tokens** defined in `resources/css/app.css` via `@theme` block

### 1.3 Data flow

- All mutations: Inertia form requests → controller → FormRequest validation → repository → redirect with flash props
- All reads: Inertia responses with props whose shape is documented in `resources/js/types/*.js` (JSDoc `@typedef`s mirroring backend DTOs)
- Flash messages: success/error/warning toast via global `<Toast>` mounted in `Authenticated.vue`
- Validation errors: Inertia `errors` prop rendered inline using `<UiField>` wrapper

### 1.4 Out of scope for Foundation

Patients, appointments, treatments, billing, inventory, exchange, notifications. Shown as disabled "Coming soon" sidebar items and placeholder KPI cards on the dashboard.

---

## 2. Data model & migrations

### 2.1 Tables created by Foundation

| Table | Purpose | Source |
|---|---|---|
| `users` | Staff accounts (replaces Laravel default with extra fields) | New migration |
| `password_reset_tokens` | Password reset tokens | Laravel default (kept) |
| `sessions` | HTTP sessions | Laravel default (kept) |
| `cache` / `cache_locks` | Application cache (stored in MySQL per `.env` `CACHE_STORE=database`) | Laravel default (kept) |
| `jobs` / `job_batches` / `failed_jobs` | Queue | Laravel default (kept) |
| `permissions` | spatie/laravel-permission | Package |
| `roles` | spatie/laravel-permission, seeded with `admin`, `doctor`, `receptionist` | Package |
| `model_has_permissions`, `model_has_roles`, `role_has_permissions` | spatie/laravel-permission pivots | Package |
| `activity_log` | spatie/laravel-activitylog — logs user logins, settings changes, and (later) every domain mutation | Package |
| `settings` | spatie/laravel-settings — `ClinicSettings` payload | Package |
| `media` | spatie/laravel-medialibrary — avatars + future attachments | Package |

### 2.2 `users` table additions

Beyond the Laravel default `users` columns:

- `phone` (nullable string)
- `avatar_path` (nullable string) — synced with medialibrary's `avatar` collection
- `is_active` (boolean, default `true`)
- `last_login_at` (nullable timestamp)

### 2.3 `ClinicSettings` shape

A typed settings class registered with `spatie/laravel-settings`:

```php
class ClinicSettings extends Settings
{
    public string $clinic_name;
    public string $clinic_tagline;
    public ?string $logo_media_id;     // FK to media table
    public string $address_line_1;
    public ?string $address_line_2;
    public string $city;
    public ?string $state;
    public string $postal_code;
    public string $country;            // ISO 3166-1 alpha-2
    public string $phone;
    public ?string $email;
    public string $currency_code;      // ISO 4217, e.g. "BDT", "INR", "USD"
    public string $currency_symbol;    // e.g. "৳", "₹", "$"
    public int $currency_decimals;     // 0 for BDT, 2 for USD
    public string $currency_position;  // "before" | "after"
    public string $timezone;           // IANA, e.g. "Asia/Dhaka"
    public string $date_format;        // "Y-m-d" | "d/m/Y" | "m/d/Y"
    public string $time_format;        // "H:i" | "h:i A"
    public string $fiscal_year_start;  // "01-01" by default
    public ?float $default_tax_rate;   // percent, nullable
    public string $invoice_prefix;     // e.g. "INV-"
    public int $next_invoice_number;   // 1
    public string $appointment_slot_minutes; // "15" | "30" | "60"
    public string $working_hours_json; // JSON: 7-key object (mon..sun). Each day is either an empty array (closed) or an array of one or more {start: "HH:mm", end: "HH:mm"} slots, in clinic-local time. Example: {"mon":[{"start":"09:00","end":"13:00"},{"start":"15:00","end":"20:00"}],"tue":[{"start":"09:00","end":"18:00"}], ... ,"sun":[]}

    public static function group(): string { return 'clinic'; }
}
```

### 2.4 User model

- Implements `HasRoles` (spatie) and `LogsActivity` (spatie activitylog)
- Implements `HasMedia` for the `avatar` collection (single file)
- Casts: `is_active` (bool), `last_login_at` (datetime), `password` (hashed)
- Relationships: `media()` (HasMany polymorphic), `roles()` (BelongsToMany)

### 2.5 Seeders

- `RoleSeeder` — creates the 3 roles. Foundation seeds only the permissions that exist post-Foundation (`view-dashboard`, `manage-users`, `manage-settings`). Later sub-projects extend each role's permission set as their own domains land:
  - `admin` — receives `*` (all permissions) via a gate
  - `doctor` — `view-dashboard` *(extended in sub-projects 1 & 2 with `view-patients`, `manage-own-appointments`, `manage-own-treatments`)*
  - `receptionist` — `view-dashboard` *(extended in later sub-projects with `manage-patients`, `manage-appointments`, `manage-billing`, `manage-inventory`)*
- `UserSeeder` — creates one `admin@clinic.test` user with password `password`.

### 2.6 Conventions every later sub-project must follow

- Migration filename: `YYYY_MM_DD_HHMMSS_create_<table>_table.php` (Laravel convention)
- Every table gets `id`, `created_at`, `updated_at`; `deleted_at` only if the model is `SoftDeletes`
- Foreign keys named `<singular>_id`, with `constrained()` and `cascadeOnDelete()` only when the child has no independent meaning
- Money columns: `decimal(18, 4)` for ledger lines, `decimal(18, 2)` for display (computed at the boundary)
- All timestamps in UTC at the DB; convert to clinic timezone in the UI
- New domain tables get a corresponding `activity_log` subject via `LogsActivity` trait on the model

---

## 3. Auth flow, routes, and the app shell

### 3.1 Routes

#### `routes/auth.php` (no auth required)

| Method | URI | Controller | Inertia page |
|---|---|---|---|
| GET | `/login` | `Auth\LoginController@create` | `Auth/Login.vue` |
| POST | `/login` | `Auth\LoginController@store` | — (redirects) |
| GET | `/forgot-password` | `Auth\PasswordResetLinkController@create` | `Auth/ForgotPassword.vue` |
| POST | `/forgot-password` | `Auth\PasswordResetLinkController@store` | — |
| GET | `/reset-password/{token}` | `Auth\NewPasswordController@create` | `Auth/ResetPassword.vue` |
| POST | `/reset-password` | `Auth\NewPasswordController@store` | — |
| POST | `/logout` | `Auth\LoginController@destroy` | — (auth required) |

#### `routes/web.php` (auth required)

| Method | URI | Controller | Inertia page | Notes |
|---|---|---|---|---|
| GET | `/` | `DashboardController@index` | (redirects to `/dashboard`) | All roles |
| GET | `/dashboard` | `DashboardController@index` | `Dashboard.vue` | All roles, no `verified` required |
| GET | `/profile` | `ProfileController@edit` | `Profile/Edit.vue` | All roles |
| PATCH | `/profile` | `ProfileController@update` | — | All roles |
| PATCH | `/profile/password` | `ProfileController@updatePassword` | — | All roles |
| DELETE | `/profile` | `ProfileController@destroy` | — | All roles (deactivates own account; sets `is_active = false`; does not delete the row) |

#### `routes/settings.php` (auth + `manage-settings` permission)

| Method | URI | Controller | Inertia page |
|---|---|---|---|
| GET | `/settings` | `Settings\ClinicSettingsController@edit` | `Settings/Clinic.vue` |
| PATCH | `/settings` | `Settings\ClinicSettingsController@update` | — |
| GET | `/settings/working-hours` | `Settings\WorkingHoursController@edit` | `Settings/WorkingHours.vue` |
| PATCH | `/settings/working-hours` | `Settings\WorkingHoursController@update` | — |

#### `routes/admin.php` (auth + `manage-users` permission)

| Method | URI | Controller | Inertia page |
|---|---|---|---|
| GET | `/admin/users` | `Admin\UserController@index` | `Admin/Users/Index.vue` |
| GET | `/admin/users/create` | `Admin\UserController@create` | `Admin/Users/Create.vue` |
| POST | `/admin/users` | `Admin\UserController@store` | — |
| GET | `/admin/users/{user}/edit` | `Admin\UserController@edit` | `Admin/Users/Edit.vue` |
| PATCH | `/admin/users/{user}` | `Admin\UserController@update` | — |
| DELETE | `/admin/users/{user}` | `Admin\UserController@destroy` | — |
| POST | `/admin/users/{user}/toggle-active` | `Admin\UserController@toggleActive` | — |

Auth routes live inside `Route::middleware('guest')->group(...)`; everything else inside `Route::middleware(['auth'])->group(...)` (with the `verified` middleware added per route as needed).

### 3.2 Middleware stack

- `auth` — Laravel default
- `verified` — Laravel default (added per route, not the whole group, so dev login works without email verification)
- `role:<slug>` — custom middleware using spatie permissions, e.g. `Route::middleware('role:admin')->group(...)`
- `permission:<slug>` — custom middleware using spatie, e.g. `Route::middleware('permission:manage-users')`
- `active` — custom middleware: rejects users with `is_active = false` and signs them out
- `ShareClinicSettings` — Inertia middleware that shares `settings` (typed shape, camelCase) with every Inertia response

### 3.3 App shell (Vue side)

#### `resources/js/Layouts/Auth.vue`
Used by `/login`, `/forgot-password`, `/reset-password`. Centered card on a soft-gradient background. No sidebar.

#### `resources/js/Layouts/Authenticated.vue`
Used by everything else. Structure:

```
┌──────────────────────────────────────────────────────────────┐
│  TopBar:  [☰]  Clinic name           [🔔 0]  [🌓]  [avatar ▾] │
├──────────┬───────────────────────────────────────────────────┤
│ Sidebar  │  Breadcrumbs: Dashboard > Patients > John Doe     │
│ • Dash   │  ┌─────────────────────────────────────────────┐  │
│ • Appts  │  │                                             │  │
│ • Pat.   │  │           <slot /> (page content)           │  │
│ • Bill.  │  │                                             │  │
│ • Inv.   │  │                                             │  │
│ • Exch.  │  │                                             │  │
│ • Sett.  │  └─────────────────────────────────────────────┘  │
│ • Users  │                                                   │
└──────────┴───────────────────────────────────────────────────┘
```

- Sidebar collapses to icons-only on `lg` breakpoint, fully hidden on mobile with a slide-out drawer triggered by the topbar's `☰`
- Sidebar items are filtered by user permissions (unauthorized items don't render)
- Topbar shows a notifications bell (placeholder count of 0 in Foundation)
- Avatar dropdown: "Profile", "Sign out"
- Dark/light mode toggle uses `useColorMode`; preference persists in `localStorage` keyed by user id

#### Sidebar items

The sidebar receives a `nav` array from `useNav()`. Foundation seeds:
- Dashboard (all roles)
- Settings (admin only)
- Users (admin only)

Placeholder slots for "Appointments", "Patients", "Billing", "Inventory", "Exchange" render as **disabled grayed-out items with a "Coming soon" badge** so the IA is visible from day one.

### 3.4 Error handling

- 403 → Inertia page `Errors/403.vue` (friendly message + "Go to dashboard")
- 404 → Inertia page `Errors/404.vue`
- 500 → Inertia page `Errors/500.vue` (never leaks stack traces; logs everything)
- Form validation errors come back via Inertia's `errors` prop, rendered inline using `<UiField>`
- Global `<Toast>` listens to `flash` props for `success` / `error` / `warning` messages

### 3.5 Testing

Foundation ships with:
- **Pest** feature tests for: login (valid, invalid, deactivated user, rate-limit), logout, password reset request, password reset complete, profile update, settings update, user CRUD (create, edit, deactivate, delete)
- **Vitest** unit tests for: `useNav()`, `useColorMode()`, `useCurrency()`
- Playwright smoke test: **not** in scope for Foundation

---

## 4. Component layer, theming, and code organization

### 4.1 Folder structure

```
resources/js/
├── app.js                          # Inertia + PrimeVue bootstrap
├── bootstrap.js                    # axios setup, CSRF, error interceptors
├── Components/
│   ├── ui/                         # The wrapper layer (our design system)
│   │   ├── index.ts
│   │   ├── Button.vue
│   │   ├── Input.vue
│   │   ├── Textarea.vue
│   │   ├── Select.vue
│   │   ├── Checkbox.vue
│   │   ├── RadioGroup.vue
│   │   ├── Switch.vue
│   │   ├── DatePicker.vue
│   │   ├── TimePicker.vue
│   │   ├── Dialog.vue
│   │   ├── Drawer.vue
│   │   ├── Popover.vue
│   │   ├── Tooltip.vue              # directive + component
│   │   ├── DropdownMenu.vue
│   │   ├── Tabs.vue
│   │   ├── Accordion.vue
│   │   ├── Toast.vue                # + useToast() composable
│   │   ├── Alert.vue
│   │   ├── Badge.vue
│   │   ├── Card.vue
│   │   ├── DataTable.vue
│   │   ├── Pagination.vue
│   │   ├── Breadcrumbs.vue
│   │   ├── Avatar.vue
│   │   ├── Spinner.vue
│   │   ├── Skeleton.vue
│   │   ├── EmptyState.vue
│   │   ├── Stat.vue                 # KPI card for dashboard
│   │   ├── Form/
│   │   │   ├── Form.vue
│   │   │   ├── Field.vue
│   │   │   ├── FormError.vue
│   │   │   └── FormActions.vue
│   │   └── ConfirmDialog.vue
│   ├── layout/
│   │   ├── Sidebar.vue
│   │   ├── SidebarItem.vue
│   │   ├── TopBar.vue
│   │   ├── UserMenu.vue
│   │   └── ColorModeToggle.vue
│   └── domain/                      # Empty for Foundation; later sub-projects add here
├── Composables/
│   ├── useNav.ts
│   ├── useColorMode.ts
│   ├── useToast.ts
│   ├── useConfirm.ts
│   ├── useFlashMessages.ts
│   ├── useCurrentUser.ts
│   └── useCurrency.ts
├── Layouts/
│   ├── Auth.vue
│   └── Authenticated.vue
├── Pages/
│   ├── Auth/
│   │   ├── Login.vue
│   │   ├── ForgotPassword.vue
│   │   └── ResetPassword.vue
│   ├── Profile/
│   │   └── Edit.vue
│   ├── Dashboard.vue
│   ├── Settings/
│   │   ├── Clinic.vue
│   │   └── WorkingHours.vue
│   ├── Admin/
│   │   └── Users/
│   │       ├── Index.vue
│   │       ├── Create.vue
│   │       └── Edit.vue
│   └── Errors/
│       ├── 403.vue
│       ├── 404.vue
│       └── 500.vue
├── types/
│   ├── settings.js
│   ├── user.js
│   ├── role.js
│   ├── nav.js
│   └── index.js
├── lib/
│   ├── money.js
│   ├── inertia.js
│   └── timezone.js
├── plugins/
│   └── primevue.js
└── test-setup.js                    # Vitest global setup
```

### 4.2 The wrapper pattern

Every file in `Components/ui/` wraps a PrimeVue component with the same approach: PrimeVue's `pt` (pass-through) prop injects our Tailwind utility classes onto PrimeVue's root element. No CSS specificity wars. App code imports from `@/Components/ui`, never from `primevue/*`.

Example — `Button.vue`:

```vue
<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'

const props = defineProps({
  variant:  { type: String, default: 'primary', validator: v => ['primary','secondary','ghost','danger'].includes(v) },
  size:     { type: String, default: 'md',      validator: v => ['sm','md','lg'].includes(v) },
  loading:  { type: Boolean, default: false },
  type:     { type: String, default: 'button',  validator: v => ['button','submit','reset'].includes(v) },
  disabled: { type: Boolean, default: false },
})

const classes = computed(() => {
  const base = 'inline-flex items-center justify-center gap-2 font-medium rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed'
  const sizes = { sm: 'h-8 px-3 text-sm', md: 'h-10 px-4 text-sm', lg: 'h-12 px-6 text-base' }
  const variants = {
    primary:   'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-700',
    secondary: 'bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70',
    ghost:     'text-foreground hover:bg-surface-muted',
    danger:    'bg-danger text-white hover:bg-danger/90',
  }
  return [base, sizes[props.size], variants[props.variant]].join(' ')
})
</script>

<template>
  <Button
    :type="type"
    :disabled="disabled || loading"
    :pt="{ root: { class: classes } }"
    :loading="loading"
  >
    <slot />
  </Button>
</template>
```

This pattern repeats for every wrapper component. PrimeVue gives us a11y, keyboard handling, ripple, and the loading spinner; our wrapper owns the visual contract.

### 4.3 Tailwind v4 design tokens

`resources/css/app.css`:

```css
@import "tailwindcss";

@theme {
  /* Brand */
  --color-primary-50:  #f0f9ff;
  --color-primary-500: #0ea5e9;
  --color-primary-600: #0284c7;
  --color-primary-700: #0369a1;

  /* Surfaces & text */
  --color-surface:           #ffffff;
  --color-surface-muted:     #f8fafc;
  --color-border:            #e2e8f0;
  --color-foreground:        #0f172a;
  --color-muted-foreground:  #64748b;

  /* Status */
  --color-success: #16a34a;
  --color-warning: #f59e0b;
  --color-danger:  #dc2626;

  /* Radius */
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;

  /* Typography */
  --font-sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --color-surface:          #0b1220;
    --color-surface-muted:    #111a2e;
    --color-border:           #1f2a44;
    --color-foreground:       #e2e8f0;
    --color-muted-foreground: #94a3b8;
  }
}

:root[data-theme="dark"] {
  --color-surface:          #0b1220;
  --color-surface-muted:    #111a2e;
  --color-border:           #1f2a44;
  --color-foreground:       #e2e8f0;
  --color-muted-foreground: #94a3b8;
}
```

`@theme` automatically generates Tailwind utilities like `bg-primary-600`, `text-muted-foreground`, `rounded-md`.

### 4.4 PrimeVue unstyled setup

`resources/js/plugins/primevue.js`:

```js
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'

/**
 * @param {import('vue').App} app
 */
export default function installPrimeVue(app) {
  app.use(PrimeVue, { unstyled: true, ripple: true })
  app.use(ToastService)
  app.use(ConfirmationService)
  app.directive('tooltip', Tooltip)
}
```

`resources/css/primevue-overrides.css` (imported by `app.css`):

```css
:root {
  --p-primary-color:        var(--color-primary-600);
  --p-primary-hover-color:  var(--color-primary-700);
  --p-primary-contrast-color: #ffffff;
  --p-content-border-radius: var(--radius-md);
  --p-text-color:           var(--color-foreground);
  --p-text-muted-color:     var(--color-muted-foreground);
  --p-content-border-color: var(--color-border);
  --p-content-background:   var(--color-surface);
}
```

### 4.5 Forms

`useInertiaForm()` wraps Inertia's `useForm()` and adds typed errors, a `processing` ref, and a `resetOnSuccess` option. Combined with `<Form>`, `<Field>`, `<Input>`, and `<FormActions>`, every form page looks the same:

```vue
<script setup>
import { Form, Field, Input, FormActions } from '@/Components/ui'
import { useInertiaForm } from '@/lib/inertia'

const form = useInertiaForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login', { onSuccess: () => form.reset('password') })
}
</script>

<template>
  <Form @submit.prevent="submit">
    <Field label="Email" :error="form.errors.email">
      <Input v-model="form.email" type="email" required />
    </Field>
    <Field label="Password" :error="form.errors.password">
      <Input v-model="form.password" type="password" required />
    </Field>
    <FormActions :processing="form.processing" submit-label="Sign in" />
  </Form>
</template>
```

### 4.6 Notifications

`useToast()` exposes `toast.success(msg)`, `toast.error(msg)`, `toast.warn(msg)`. The global `<Toast>` is mounted once per layout and reads from PrimeVue's `ToastService`. `useFlashMessages()` auto-broadcasts Laravel's flash messages to the toast on every Inertia response.

### 4.7 Type sharing (JSDoc-based)

We **don't** generate TS types from PHP. We hand-write JSDoc `@typedef` definitions in `resources/js/types/*.js` that mirror backend DTOs. VS Code's Volar extension reads these to give autocomplete in `<script setup>` blocks. Foundation ships:
- `User`, `Role`, `Permission` typedefs in `types/user.js` and `types/role.js`
- `ClinicSettings` typedef in `types/settings.js`
- `NavItem` typedef in `types/nav.js`
- `PageProps` typedef in `types/index.js` — Inertia's standard props envelope with `auth.user`, `settings`, `flash`, `errors` baked in

Controllers pass data through a `Resource` class producing the exact JSON shape the JSDoc typedef expects. CI runs `phpstan` and `eslint` (with the `jsdoc` rule) to prevent drift. We do **not** run a separate type-checker step.

### 4.8 Testing the component layer

- Each wrapper component gets a Vitest test asserting it renders the right Tailwind class for each variant/size combination
- Storybook is **not** in scope for Foundation
- Playwright smoke test is **not** in scope for Foundation

---

## 5. Build, dev environment, and quality gates

### 5.1 Docker

No changes to `docker-compose.yml` are required for Foundation. We verify the `dental-app` container can reach the `mysqldb` service on the `mollanet` external network during implementation.

### 5.2 Local dev commands

| Command | Purpose |
|---|---|
| `composer install` | Install PHP deps |
| `npm install` | Install JS deps |
| `php artisan migrate --seed` | Bootstrap DB |
| `php artisan serve` | Laravel dev server on `:8000` |
| `npm run dev` | Vite HMR on `:5173`, proxied by Inertia |
| `npm run build` | Production Vite bundle |
| `vendor/bin/pest` | Backend tests |
| `npm run test` | Vitest |
| `vendor/bin/pint` | PHP code style |
| `npm run lint` | ESLint |
| `vendor/bin/phpstan analyse` | PHP static analysis (level 6) |
| `composer quality` | Runs `pint --test && phpstan && pest && npm run lint && vitest run && npm run build` |

### 5.3 New dependencies

#### Composer
```
inertiajs/inertia-laravel        ^2.0
spatie/laravel-permission         ^6.10
spatie/laravel-settings           ^3.4
spatie/laravel-activitylog        ^4.8
spatie/laravel-medialibrary      ^11.10
tightenco/ziggy                   ^2.4
```

*(Versions confirmed at implementation time. The `^` allows patch/minor updates within the major.)*

#### NPM
```
dependencies:
  vue                  ^3.5
  @inertiajs/vue3      ^2.0
  primevue             ^4.2
  @primevue/themes     ^4.2
  @primeuix/themes     // Tailwind preset
  ziggy-js             ^2.4
  @vueuse/core         ^11.3
  date-fns             ^4.1
  date-fns-tz          ^3.2

devDependencies:
  @vitejs/plugin-vue                ^5.2
  vitest                           ^2.1
  @vue/test-utils                  ^2.4
  jsdom                            ^25.0
  eslint                           ^9.16
  eslint-plugin-vue                ^9.32
  eslint-plugin-jsdoc              ^50.6  // enforces JSDoc on shared typedefs
  prettier                         ^3.4
```

### 5.4 `vite.config.js` changes

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import path from 'node:path'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      ignored: ['**/storage/framework/views/**', '**/vendor/**'],
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setup: ['./resources/js/test-setup.js'],
  },
})
```

### 5.5 CI

Not in scope for Foundation. The `composer quality` script makes CI integration trivial later.

### 5.6 Documentation

- `README.md` — rewritten to describe the dental project, sub-projects, and how to run locally
- `docs/superpowers/specs/2026-08-13-dental-foundation-design.md` — this document
- `docs/superpowers/plans/2026-08-13-dental-foundation-plan.md` — implementation plan (written by `writing-plans` skill)
- `docs/architecture/decisions/0001-primevue-unstyled-tailwind.md` — ADR for Approach A
- `docs/architecture/decisions/0002-spatie-permissions.md` — ADR for spatie permissions choice
- `docs/architecture/decisions/0003-decomposition-into-sub-projects.md` — ADR for sub-project decomposition

### 5.7 Definition of done

A reviewer can clone the repo, run `docker compose up -d`, visit `http://localhost`, and:

1. See the login page styled with the clinic's design tokens
2. Log in as `admin@clinic.test` / `password`
3. Land on a dashboard with 4-6 placeholder KPI cards and disabled "Coming soon" sidebar items
4. Open Settings → Clinic, change the clinic name, see it reflected in the topbar instantly
5. Open Users, create a `receptionist` user, log out, log in as them, see the "Users" and "Settings" sidebar items disappear
6. Run `composer quality` and see green
7. Run `vendor/bin/pest` and see all backend tests pass
8. Run `npm run test` and see all frontend tests pass

---

## 6. Risks & open questions

| Risk | Mitigation |
|---|---|
| Tailwind v4 + PrimeVue unstyled + custom design tokens is a less-trodden path | Approach A is documented, but we'll smoke-test the full design system on day 1 (login page + button variants + dark mode) before building the rest |
| Version drift between PrimeVue and `@primevue/themes` | Pin to minor versions in `package.json` |
| `inertiajs/inertia-laravel` v2 is recent — Laravel 13 compat must be confirmed | Confirmed at implementation time; if v2 isn't compatible, fall back to v1.x |
| Existing `users` migration in fresh Laravel skeleton needs to be replaced | Drop the default `0001_01_01_000000_create_users_table.php` and replace with our own; rerun migrations cleanly |
| `npm install --ignore-scripts` is the default in `composer.json` setup | Already accounts for puppeteer post-install not running in this environment |

---

## 7. Out of scope (deferred to later sub-projects)

- Patient, doctor, appointment, treatment, billing, payment, expense, inventory, exchange, notification models and routes
- Any kind of public/patient-facing surface
- SMS / WhatsApp integration
- Reporting / export
- Multi-clinic tenancy
- Mobile app
- Backup / disaster recovery
- Audit report views (the activity log table exists; UI for browsing it lands in a later sub-project)
