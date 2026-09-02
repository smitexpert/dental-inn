# Dental Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Foundation sub-project for a single-clinic dental practice management system: auth, roles, clinic settings, app shell, and a Vue 3 + PrimeVue (unstyled) + Tailwind v4 component library that every later sub-project reuses.

**Architecture:** Laravel 13 + Inertia.js 2 on the backend (returning Vue pages, not Blade). Vue 3 (plain JavaScript) on the frontend with a wrapper layer (`resources/js/Components/ui/`) that re-exports PrimeVue 4 components pre-themed with Tailwind v4 design tokens via the `pt` pass-through. spatie/laravel-permission for roles, spatie/laravel-settings for typed clinic settings, spatie/laravel-medialibrary for avatars. Single-clinic tenancy; three roles: admin, doctor, receptionist.

**Tech Stack:** PHP 8.4, Laravel 13, MySQL, Inertia.js 2, Vue 3, PrimeVue 4, Tailwind CSS v4, Vite 8, Pest, Vitest, Pinia (later), Ziggy.

**Spec:** `docs/superpowers/specs/2026-08-13-dental-foundation-design.md`

---

## File Structure

### Backend (PHP)

```
app/
├── Domain/                                  # Empty in Foundation; sub-projects add folders here
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── PasswordResetLinkController.php
│   │   │   └── NewPasswordController.php
│   │   ├── ProfileController.php
│   │   ├── DashboardController.php
│   │   ├── Settings/
│   │   │   ├── ClinicSettingsController.php
│   │   │   └── WorkingHoursController.php
│   │   └── Admin/
│   │       └── UserController.php
│   ├── Middleware/
│   │   ├── EnsureUserIsActive.php
│   │   ├── RoleMiddleware.php
│   │   └── PermissionMiddleware.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── NewPasswordRequest.php
│   │   ├── Profile/
│   │   │   ├── UpdateProfileRequest.php
│   │   │   └── UpdatePasswordRequest.php
│   │   ├── Settings/
│   │   │   ├── UpdateClinicSettingsRequest.php
│   │   │   └── UpdateWorkingHoursRequest.php
│   │   └── Admin/
│   │       ├── StoreUserRequest.php
│   │       └── UpdateUserRequest.php
│   └── Resources/                           # Eloquent API Resources (DTOs for Inertia)
│       ├── UserResource.php
│       ├── RoleResource.php
│       └── SettingsResource.php
├── Models/
│   └── User.php                             # Modified
├── Repositories/
│   ├── Contracts/
│   │   └── UserRepository.php
│   └── Eloquent/
│       └── UserEloquentRepository.php
├── Settings/
│   └── ClinicSettings.php                   # spatie/laravel-settings class
├── Support/
│   ├── Money.php                            # Value object
│   └── Inertia/
│       └── ShareClinicSettings.php          # Inertia middleware
├── Policies/
│   └── UserPolicy.php
└── Providers/
    └── AppServiceProvider.php               # Modified (register spatie)

bootstrap/app.php                            # Modified: register middleware
config/
├── app.php                                  # Modified: middleware aliases
└── media-library.php                        # Published from medialibrary

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php    # Replaced
│   ├── 0001_01_01_000001_create_cache_table.php    # Kept
│   ├── 0001_01_01_000002_create_jobs_table.php     # Kept
│   ├── 2026_08_13_000001_add_extra_columns_to_users_table.php
│   ├── 2026_08_13_000002_create_permission_tables.php
│   ├── 2026_08_13_000003_create_settings_table.php
│   ├── 2026_08_13_000004_create_activity_log_table.php
│   ├── 2026_08_13_000005_create_media_table.php
│   └── 2026_08_13_000006_create_personal_access_tokens_table.php   # if using Sanctum later
├── seeders/
│   ├── DatabaseSeeder.php                   # Modified
│   ├── RoleSeeder.php
│   ├── UserSeeder.php
│   └── SettingsSeeder.php
└── factories/
    └── UserFactory.php                      # Modified

routes/
├── web.php                                  # Modified
├── auth.php                                 # New
├── settings.php                             # New
└── admin.php                                # New

tests/
├── Pest.php
├── TestCase.php
└── Feature/
    ├── Auth/
    │   ├── LoginTest.php
    │   ├── LogoutTest.php
    │   ├── PasswordResetTest.php
    │   └── DeactivatedUserCannotLoginTest.php
    ├── Profile/
    │   ├── UpdateProfileTest.php
    │   └── UpdatePasswordTest.php
    ├── Settings/
    │   ├── UpdateClinicSettingsTest.php
    │   └── UpdateWorkingHoursTest.php
    └── Admin/
        ├── ListUsersTest.php
        ├── CreateUserTest.php
        ├── UpdateUserTest.php
        ├── DeactivateUserTest.php
        └── DeleteUserTest.php
```

### Frontend (JS)

```
resources/
├── css/
│   ├── app.css                              # Tailwind + design tokens + primevue overrides
│   └── primevue-overrides.css               # PrimeVue token mapping
├── js/
│   ├── app.js                               # Inertia + Vue + PrimeVue bootstrap
│   ├── bootstrap.js                         # axios + CSRF
│   ├── Components/
│   │   ├── ui/
│   │   │   ├── index.js
│   │   │   ├── Button.vue
│   │   │   ├── Input.vue
│   │   │   ├── Textarea.vue
│   │   │   ├── Select.vue
│   │   │   ├── Checkbox.vue
│   │   │   ├── RadioGroup.vue
│   │   │   ├── Switch.vue
│   │   │   ├── DatePicker.vue
│   │   │   ├── TimePicker.vue
│   │   │   ├── Dialog.vue
│   │   │   ├── Drawer.vue
│   │   │   ├── Popover.vue
│   │   │   ├── Tooltip.vue
│   │   │   ├── DropdownMenu.vue
│   │   │   ├── Tabs.vue
│   │   │   ├── Accordion.vue
│   │   │   ├── Toast.vue
│   │   │   ├── Alert.vue
│   │   │   ├── Badge.vue
│   │   │   ├── Card.vue
│   │   │   ├── DataTable.vue
│   │   │   ├── Pagination.vue
│   │   │   ├── Breadcrumbs.vue
│   │   │   ├── Avatar.vue
│   │   │   ├── Spinner.vue
│   │   │   ├── Skeleton.vue
│   │   │   ├── EmptyState.vue
│   │   │   ├── Stat.vue
│   │   │   ├── Form/
│   │   │   │   ├── Form.vue
│   │   │   │   ├── Field.vue
│   │   │   │   ├── FormError.vue
│   │   │   │   └── FormActions.vue
│   │   │   └── ConfirmDialog.vue
│   │   └── layout/
│   │       ├── Sidebar.vue
│   │       ├── SidebarItem.vue
│   │       ├── TopBar.vue
│   │       ├── UserMenu.vue
│   │       └── ColorModeToggle.vue
│   ├── Composables/
│   │   ├── useNav.js
│   │   ├── useColorMode.js
│   │   ├── useToast.js
│   │   ├── useConfirm.js
│   │   ├── useFlashMessages.js
│   │   ├── useCurrentUser.js
│   │   └── useCurrency.js
│   ├── Layouts/
│   │   ├── Auth.vue
│   │   └── Authenticated.vue
│   ├── Pages/
│   │   ├── Auth/
│   │   │   ├── Login.vue
│   │   │   ├── ForgotPassword.vue
│   │   │   └── ResetPassword.vue
│   │   ├── Profile/Edit.vue
│   │   ├── Dashboard.vue
│   │   ├── Settings/
│   │   │   ├── Clinic.vue
│   │   │   └── WorkingHours.vue
│   │   ├── Admin/Users/
│   │   │   ├── Index.vue
│   │   │   ├── Create.vue
│   │   │   └── Edit.vue
│   │   └── Errors/
│   │       ├── 403.vue
│   │       ├── 404.vue
│   │       └── 500.vue
│   ├── types/
│   │   ├── index.js
│   │   ├── user.js
│   │   ├── role.js
│   │   ├── nav.js
│   │   └── settings.js
│   ├── lib/
│   │   ├── money.js
│   │   ├── inertia.js
│   │   └── timezone.js
│   ├── plugins/
│   │   └── primevue.js
│   └── test-setup.js
└── views/
    └── app.blade.php                        # Inertia root template (replaces welcome.blade.php)
```

### Tests (Frontend)

```
resources/js/
└── __tests__/
    ├── Components/ui/
    │   ├── Button.test.js
    │   ├── Input.test.js
    │   ├── Dialog.test.js
    │   └── Form/Field.test.js
    ├── Composables/
    │   ├── useNav.test.js
    │   ├── useColorMode.test.js
    │   └── useCurrency.test.js
    └── Lib/
        └── money.test.js
```

### Docs

```
docs/
├── superpowers/
│   ├── specs/2026-08-13-dental-foundation-design.md
│   └── plans/2026-08-13-dental-foundation-plan.md   # this file
└── architecture/
    └── decisions/
        ├── 0001-primevue-unstyled-tailwind.md
        ├── 0002-spatie-permissions.md
        └── 0003-decomposition-into-sub-projects.md

README.md                                    # Rewritten
```

---

## Phase 0 — Dependencies, configuration, and project skeleton

**Goal:** Lay the technical foundation. Install Composer + NPM packages, configure Vite for Vue, wire Inertia's Blade root, and verify the build works end-to-end before any feature work.

---

### Task 0.1: Install Composer packages

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add the Composer dependencies**

Run:
```bash
cd /mnt/c3c37cc3-52e9-4cf6-887a-7d8eab6bda76/Workspace/Molla/dental
docker exec dental_app composer require \
  inertiajs/inertia-laravel \
  spatie/laravel-permission \
  spatie/laravel-settings \
  spatie/laravel-activitylog \
  spatie/laravel-medialibrary \
  tightenco/ziggy
```

If `docker exec dental_app` fails because the container is not running, run locally with:
```bash
composer require \
  inertiajs/inertia-laravel \
  spatie/laravel-permission \
  spatie/laravel-settings \
  spatie/laravel-activitylog \
  spatie/laravel-medialibrary \
  tightenco/ziggy
```

Expected: all six packages install successfully; `composer.json` `require` block is updated.

- [ ] **Step 2: Verify packages installed**

Run: `docker exec dental_app composer show inertiajs/inertia-laravel spatie/laravel-permission | head -20`
Expected: shows package names and versions.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore(deps): add Inertia, spatie permissions/settings/activitylog/medialibrary, ziggy"
```

### Task 0.2: Install NPM packages

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Install runtime NPM dependencies**

Run:
```bash
cd /mnt/c3c37cc3-52e9-4cf6-887a-7d8eab6bda76/Workspace/Molla/dental
npm install --ignore-scripts \
  vue@^3.5 \
  @inertiajs/vue3@^2.0 \
  primevue@^4.2 \
  @primevue/themes \
  @primeuix/themes \
  ziggy-js \
  @vueuse/core \
  date-fns \
  date-fns-tz
```

Expected: all packages added to `dependencies` in `package.json`.

- [ ] **Step 2: Install dev NPM dependencies**

Run:
```bash
npm install --ignore-scripts -D \
  @vitejs/plugin-vue \
  vitest \
  @vue/test-utils \
  jsdom \
  eslint \
  eslint-plugin-vue \
  eslint-plugin-jsdoc \
  prettier
```

Expected: all packages added to `devDependencies` in `package.json`.

- [ ] **Step 3: Verify the build still works**

Run: `npx vite build`
Expected: builds successfully, output in `public/build/`.

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore(deps): add Vue 3, Inertia, PrimeVue 4, Vitest, ESLint"
```

### Task 0.3: Update `vite.config.js` for Vue + Vitest

**Files:**
- Modify: `vite.config.js`

- [ ] **Step 1: Replace the Vite config**

Overwrite `vite.config.js` with:

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

- [ ] **Step 2: Verify the build still works**

Run: `npx vite build`
Expected: build succeeds.

- [ ] **Step 3: Commit**

```bash
git add vite.config.js
git commit -m "chore(vite): add Vue plugin, @ alias, Vitest config"
```

### Task 0.4: Add the Inertia Blade root

**Files:**
- Replace: `resources/views/welcome.blade.php` → `resources/views/app.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the Inertia root template**

Create `resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
```

- [ ] **Step 2: Delete the old welcome view**

Run: `rm resources/views/welcome.blade.php`

- [ ] **Step 3: Update `routes/web.php` to a placeholder Inertia response**

Replace `routes/web.php` with:

```php
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/app.blade.php resources/views/welcome.blade.php routes/web.php
git commit -m "feat(inertia): add Inertia Blade root + placeholder Dashboard route"
```

### Task 0.5: Create the Vue app entry point

**Files:**
- Replace: `resources/js/app.js`

- [ ] **Step 1: Write the new `app.js`**

Overwrite `resources/js/app.js` with:

```js
import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

import installPrimeVue from './plugins/primevue.js'

const appName = import.meta.env.VITE_APP_NAME || 'Dental'

createInertiaApp({
  title: (title) => title ? `${title} · ${appName}` : appName,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(installPrimeVue)
      .mount(el)
  },
  progress: { color: '#0284c7' },
})
```

- [ ] **Step 2: Create a placeholder `resources/js/plugins/primevue.js`**

Create `resources/js/plugins/primevue.js`:

```js
import PrimeVue from 'primevue/config'

/**
 * @param {import('vue').App} app
 */
export default function installPrimeVue(app) {
  app.use(PrimeVue, { unstyled: true, ripple: true })
}
```

- [ ] **Step 3: Create a placeholder Dashboard page**

Create `resources/js/Pages/Dashboard.vue`:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Dashboard" />
  <div class="p-8">
    <h1 class="text-2xl font-semibold">Foundation smoke test</h1>
    <p class="text-muted-foreground mt-2">If you see this styled, Vue + Tailwind + Inertia are wired up.</p>
  </div>
</template>
```

- [ ] **Step 4: Replace `resources/css/app.css` with the Tailwind v4 design-token starter**

Overwrite `resources/css/app.css`:

```css
@import "tailwindcss";

@theme {
  --color-primary-50:  #f0f9ff;
  --color-primary-500: #0ea5e9;
  --color-primary-600: #0284c7;
  --color-primary-700: #0369a1;

  --color-surface:           #ffffff;
  --color-surface-muted:     #f8fafc;
  --color-border:            #e2e8f0;
  --color-foreground:        #0f172a;
  --color-muted-foreground:  #64748b;

  --color-success: #16a34a;
  --color-warning: #f59e0b;
  --color-danger:  #dc2626;

  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;

  --font-sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
}
```

- [ ] **Step 5: Verify the page renders**

Run:
```bash
docker exec dental_app php artisan view:clear
docker exec dental_app php artisan config:clear
```

Then open the app in the browser (e.g. `http://localhost` or the configured domain). The "Foundation smoke test" page should render with the dark-foreground heading and muted-foreground paragraph.

- [ ] **Step 6: Commit**

```bash
git add resources/js/app.js resources/js/plugins/primevue.js resources/js/Pages/Dashboard.vue resources/css/app.css
git commit -m "feat(frontend): wire Vue + Inertia + PrimeVue bootstrap with smoke-test Dashboard"
```

---

## Phase 1 — Data model, settings, and roles

**Goal:** Replace Laravel's default `users` table with our extended version, publish migrations for permissions / settings / activitylog / media, create the `ClinicSettings` typed class, set up the `User` model with spatie roles + medialibrary + activitylog, and seed the 3 roles + 1 admin user.

### Task 1.1: Replace the default `users` migration

**Files:**
- Replace: `database/migrations/0001_01_01_000000_create_users_table.php`

- [ ] **Step 1: Write the new users migration**

Overwrite `database/migrations/0001_01_01_000000_create_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 32)->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `docker exec dental_app php artisan migrate:fresh`
Expected: `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` tables created. No errors.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/0001_01_01_000000_create_users_table.php
git commit -m "feat(users): add phone, avatar_path, is_active, last_login_at columns"
```

### Task 1.2: Publish spatie package migrations

**Files:**
- Create: migrations from each spatie package

- [ ] **Step 1: Publish each package's migrations**

Run:
```bash
docker exec dental_app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations"
docker exec dental_app php artisan vendor:publish --provider="Spatie\Settings\SettingsServiceProvider" --tag="settings-migrations"
docker exec dental_app php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
docker exec dental_app php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

Expected: 4 new migration files appear in `database/migrations/`.

- [ ] **Step 2: Rename the published migrations to use the project's date prefix**

The spatie migrations ship with their own timestamps. Rename them so they run in a predictable order after our Foundation migrations:

```bash
cd database/migrations
mv *create_permission_tables.php 2026_08_13_000002_create_permission_tables.php
mv *create_settings_table.php    2026_08_13_000003_create_settings_table.php
mv *create_activity_log_table.php 2026_08_13_000004_create_activity_log_table.php
mv *create_media_table.php        2026_08_13_000005_create_media_table.php
```

- [ ] **Step 3: Run migrations**

Run: `docker exec dental_app php artisan migrate:fresh`
Expected: all tables created, no errors.

- [ ] **Step 4: Commit**

```bash
git add database/migrations
git commit -m "feat(db): add spatie permissions, settings, activitylog, medialibrary tables"
```

### Task 1.3: Create the `ClinicSettings` typed class

**Files:**
- Create: `app/Settings/ClinicSettings.php`
- Create: `database/seeders/SettingsSeeder.php`

- [ ] **Step 1: Write the settings class**

Create `app/Settings/ClinicSettings.php`:

```php
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ClinicSettings extends Settings
{
    public string $clinic_name;
    public string $clinic_tagline;
    public ?string $logo_media_id;
    public string $address_line_1;
    public ?string $address_line_2;
    public string $city;
    public ?string $state;
    public string $postal_code;
    public string $country;
    public string $phone;
    public ?string $email;
    public string $currency_code;
    public string $currency_symbol;
    public int $currency_decimals;
    public string $currency_position;
    public string $timezone;
    public string $date_format;
    public string $time_format;
    public string $fiscal_year_start;
    public ?float $default_tax_rate;
    public string $invoice_prefix;
    public int $next_invoice_number;
    public string $appointment_slot_minutes;
    public string $working_hours_json;

    public static function group(): string
    {
        return 'clinic';
    }
}
```

- [ ] **Step 2: Write the seeder**

Create `database/seeders/SettingsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Settings\ClinicSettings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'clinic_name' => 'My Dental Clinic',
            'clinic_tagline' => 'Caring for your smile',
            'logo_media_id' => null,
            'address_line_1' => '',
            'address_line_2' => null,
            'city' => '',
            'state' => null,
            'postal_code' => '',
            'country' => 'BD',
            'phone' => '',
            'email' => null,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'currency_decimals' => 0,
            'currency_position' => 'before',
            'timezone' => 'Asia/Dhaka',
            'date_format' => 'Y-m-d',
            'time_format' => 'h:i A',
            'fiscal_year_start' => '01-01',
            'default_tax_rate' => null,
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 1,
            'appointment_slot_minutes' => '30',
            'working_hours_json' => json_encode([
                'mon' => [['start' => '09:00', 'end' => '18:00']],
                'tue' => [['start' => '09:00', 'end' => '18:00']],
                'wed' => [['start' => '09:00', 'end' => '18:00']],
                'thu' => [['start' => '09:00', 'end' => '18:00']],
                'fri' => [['start' => '09:00', 'end' => '18:00']],
                'sat' => [['start' => '09:00', 'end' => '14:00']],
                'sun' => [],
            ]),
        ];

        app(ClinicSettings::class)->fill($defaults)->save();
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Settings/ClinicSettings.php database/seeders/SettingsSeeder.php
git commit -m "feat(settings): add ClinicSettings class + default seeder"
```

### Task 1.4: Update the `User` model

**Files:**
- Replace: `app/Models/User.php`

- [ ] **Step 1: Write the updated User model**

Overwrite `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'phone'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/User.php
git commit -m "feat(user): add roles, media, activitylog, is_active + casts"
```

### Task 1.5: Seed roles and the admin user

**Files:**
- Create: `database/seeders/RoleSeeder.php`
- Create: `database/seeders/UserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Write the RoleSeeder**

Create `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['view-dashboard', 'manage-users', 'manage-settings'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctor->syncPermissions(['view-dashboard']);

        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions(['view-dashboard']);
    }
}
```

- [ ] **Step 2: Write the UserSeeder**

Create `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@clinic.test'],
            [
                'name' => 'Clinic Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        )->assignRole('admin');
    }
}
```

- [ ] **Step 3: Update DatabaseSeeder**

Replace `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingsSeeder::class,
            UserSeeder::class,
        ]);
    }
}
```

- [ ] **Step 4: Run seeders and verify**

Run: `docker exec dental_app php artisan migrate:fresh --seed`
Expected: 3 roles, 3 permissions, 1 admin user, 1 settings row.

- [ ] **Step 5: Commit**

```bash
git add database/seeders
git commit -m "feat(seed): add RoleSeeder, UserSeeder, SettingsSeeder, wire DatabaseSeeder"
```

---

## Phase 2 — Custom middleware, auth scaffolding, and route files

**Goal:** Wire up the custom middleware (`active`, `role`, `permission`), build the Inertia middleware that shares clinic settings, register the Inertia `share` callback so every page receives `auth.user` + `settings` + `flash`, and create the four route files.

### Task 2.1: Create the `EnsureUserIsActive` middleware

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsActive.php`

- [ ] **Step 1: Write the middleware**

Create `app/Http/Middleware/EnsureUserIsActive.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Middleware/EnsureUserIsActive.php
git commit -m "feat(middleware): add EnsureUserIsActive that signs out deactivated users"
```

### Task 2.2: Create the `Role` and `Permission` middleware

**Files:**
- Create: `app/Http/Middleware/RoleMiddleware.php`
- Create: `app/Http/Middleware/PermissionMiddleware.php`

- [ ] **Step 1: Write the role middleware**

Create `app/Http/Middleware/RoleMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Write the permission middleware**

Create `app/Http/Middleware/PermissionMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyPermission($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Middleware/RoleMiddleware.php app/Http/Middleware/PermissionMiddleware.php
git commit -m "feat(middleware): add role and permission middleware for spatie"
```

### Task 2.3: Create the Inertia `ShareClinicSettings` middleware

**Files:**
- Create: `app/Support/Inertia/ShareClinicSettings.php`

- [ ] **Step 1: Write the middleware**

Create `app/Support/Inertia/ShareClinicSettings.php`:

```php
<?php

namespace App\Support\Inertia;

use App\Http\Resources\SettingsResource;
use App\Settings\ClinicSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareClinicSettings
{
    public function __construct(private readonly ClinicSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('settings', fn () => SettingsResource::fromSettings($this->settings));

        return $next($request);
    }
}
```

- [ ] **Step 2: Create the SettingsResource DTO**

Create `app/Http/Resources/SettingsResource.php`:

```php
<?php

namespace App\Http\Resources;

use App\Settings\ClinicSettings;

class SettingsResource
{
    public static function fromSettings(ClinicSettings $s): array
    {
        return [
            'clinic_name'             => $s->clinic_name,
            'clinic_tagline'          => $s->clinic_tagline,
            'logo_media_id'           => $s->logo_media_id,
            'address_line_1'          => $s->address_line_1,
            'address_line_2'          => $s->address_line_2,
            'city'                    => $s->city,
            'state'                   => $s->state,
            'postal_code'             => $s->postal_code,
            'country'                 => $s->country,
            'phone'                   => $s->phone,
            'email'                   => $s->email,
            'currency_code'           => $s->currency_code,
            'currency_symbol'         => $s->currency_symbol,
            'currency_decimals'       => $s->currency_decimals,
            'currency_position'       => $s->currency_position,
            'timezone'                => $s->timezone,
            'date_format'             => $s->date_format,
            'time_format'             => $s->time_format,
            'fiscal_year_start'       => $s->fiscal_year_start,
            'default_tax_rate'        => $s->default_tax_rate,
            'invoice_prefix'          => $s->invoice_prefix,
            'next_invoice_number'     => $s->next_invoice_number,
            'appointment_slot_minutes' => $s->appointment_slot_minutes,
            'working_hours'           => json_decode($s->working_hours_json, true) ?? [],
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Support/Inertia/ShareClinicSettings.php app/Http/Resources/SettingsResource.php
git commit -m "feat(inertia): share typed settings on every Inertia response"
```

### Task 2.4: Register middleware aliases and Inertia share callback

**Files:**
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Read the current `bootstrap/app.php`**

Run: `cat bootstrap/app.php`

- [ ] **Step 2: Register the middleware aliases**

Edit `bootstrap/app.php`. The file uses the Laravel 11+ `withMiddleware` / `withExceptions` fluent API. Add the aliases block:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Support\Inertia\ShareClinicSettings::class,
    ]);

    $middleware->alias([
        'role'       => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'active'     => \App\Http\Middleware\EnsureUserIsActive::class,
    ]);
})
```

- [ ] **Step 3: Add the Inertia `share` callback for auth user and flash**

Add to `bootstrap/app.php`:

```php
->withInertia(function (Inertia $inertia) {
    $inertia->share([
        'auth' => fn () => [
            'user' => auth()->user() ? [
                'id'         => auth()->user()->id,
                'name'       => auth()->user()->name,
                'email'      => auth()->user()->email,
                'avatar_url' => auth()->user()->getFirstMediaUrl('avatar'),
                'roles'      => auth()->user()->getRoleNames(),
                'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
            ] : null,
        ],
        'flash' => fn () => [
            'success' => session('success'),
            'error'   => session('error'),
            'warning' => session('warning'),
        ],
    ]);
})
```

(Add `use Inertia\Inertia;` at the top of the file if not already present.)

- [ ] **Step 4: Clear caches and test**

Run:
```bash
docker exec dental_app php artisan config:clear
docker exec dental_app php artisan route:clear
docker exec dental_app php artisan view:clear
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add bootstrap/app.php
git commit -m "feat(bootstrap): register Inertia middleware aliases and share callback"
```

### Task 2.5: Create `routes/auth.php`

**Files:**
- Create: `routes/auth.php`

- [ ] **Step 1: Write the auth routes**

Create `routes/auth.php`:

```php
<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
```

- [ ] **Step 2: Commit**

```bash
git add routes/auth.php
git commit -m "feat(routes): add auth.php with login, forgot, reset, logout"
```

### Task 2.6: Create stub auth controllers

**Files:**
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Create: `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- Create: `app/Http/Controllers/Auth/NewPasswordController.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`

(Full implementations land in Phase 3 alongside the Login page. This task creates the controllers with stub `create` actions returning Inertia responses, so the routes resolve.)

- [ ] **Step 1: Write `LoginController`**

Create `app/Http/Controllers/Auth/LoginController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $request->user()->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

- [ ] **Step 2: Write `LoginRequest`**

Create `app/Http/Requests/Auth/LoginRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    public function throttleKey(): string
    {
        return $this->string('email')->lower() . '|' . $this->ip();
    }
}
```

- [ ] **Step 3: Write the password-reset controllers (stubs)**

Create `app/Http/Controllers/Auth/PasswordResetLinkController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
```

Create `app/Http/Controllers/Auth/NewPasswordController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    public function store(NewPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
```

Create `app/Http/Requests/Auth/NewPasswordRequest.php`:

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class NewPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
```

- [ ] **Step 4: Create stub Vue pages so the Inertia responses resolve**

Create `resources/js/Pages/Auth/Login.vue`:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Log in" />
  <div class="p-8">
    <h1 class="text-2xl font-semibold">Login (stub — Phase 3 fills in)</h1>
  </div>
</template>
```

Create `resources/js/Pages/Auth/ForgotPassword.vue`:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Forgot password" />
  <div class="p-8">
    <h1 class="text-2xl font-semibold">Forgot password (stub)</h1>
  </div>
</template>
```

Create `resources/js/Pages/Auth/ResetPassword.vue`:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
defineProps({ email: String, token: String })
</script>

<template>
  <Head title="Reset password" />
  <div class="p-8">
    <h1 class="text-2xl font-semibold">Reset password (stub)</h1>
  </div>
</template>
```

- [ ] **Step 5: Update `routes/web.php` to load the route files**

Replace `routes/web.php`:

```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

Create `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }
}
```

- [ ] **Step 6: Verify the routes load**

Run: `docker exec dental_app php artisan route:list --columns=method,uri,name`
Expected: shows `login`, `logout`, `forgot-password`, `reset-password`, `/`, `dashboard` routes. No errors.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth app/Http/Requests/Auth app/Http/Controllers/DashboardController.php resources/js/Pages/Auth routes/web.php
git commit -m "feat(auth): add auth controllers, requests, stub Vue pages, route loading"
```

---

## Phase 3 — JSDoc typedefs, types module, and the Auth layout + login page

**Goal:** Build the foundational JS types module, the `useInertiaForm` / `useNav` / `useCurrentUser` / `useColorMode` / `useToast` / `useFlashMessages` / `useConfirm` / `useCurrency` composables, the `Auth` layout, and the full `Login` page that uses our wrapper components.

### Task 3.1: Create JSDoc typedefs

**Files:**
- Create: `resources/js/types/index.js`, `user.js`, `role.js`, `nav.js`, `settings.js`

- [ ] **Step 1: Write `types/settings.js`**

Create `resources/js/types/settings.js`:

```js
/**
 * @typedef {Object} ClinicSettings
 * @property {string}  clinic_name
 * @property {string}  clinic_tagline
 * @property {string|null} logo_media_id
 * @property {string}  address_line_1
 * @property {string|null} address_line_2
 * @property {string}  city
 * @property {string|null} state
 * @property {string}  postal_code
 * @property {string}  country
 * @property {string}  phone
 * @property {string|null} email
 * @property {string}  currency_code
 * @property {string}  currency_symbol
 * @property {number}  currency_decimals
 * @property {'before'|'after'} currency_position
 * @property {string}  timezone
 * @property {string}  date_format
 * @property {string}  time_format
 * @property {string}  fiscal_year_start
 * @property {number|null} default_tax_rate
 * @property {string}  invoice_prefix
 * @property {number}  next_invoice_number
 * @property {'15'|'30'|'60'} appointment_slot_minutes
 * @property {Record<string, Array<{start: string, end: string}>>} working_hours
 */

export {}
```

- [ ] **Step 2: Write `types/user.js`**

Create `resources/js/types/user.js`:

```js
/**
 * @typedef {Object} User
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {string} avatar_url
 * @property {string[]} roles
 * @property {string[]} permissions
 * @property {boolean} is_active
 */

export {}
```

- [ ] **Step 3: Write `types/role.js`**

Create `resources/js/types/role.js`:

```js
/**
 * @typedef {Object} Role
 * @property {number} id
 * @property {string} name
 * @property {string} guard_name
 */

export {}
```

- [ ] **Step 4: Write `types/nav.js`**

Create `resources/js/types/nav.js`:

```js
/**
 * @typedef {Object} NavItem
 * @property {string} label
 * @property {string} href
 * @property {string|null} icon
 * @property {string|null} permission
 * @property {string|null} role
 * @property {boolean} [comingSoon]
 */

export {}
```

- [ ] **Step 5: Write `types/index.js`**

Create `resources/js/types/index.js`:

```js
import './settings.js'
import './user.js'
import './role.js'
import './nav.js'

/**
 * @typedef {Object} FlashMessages
 * @property {string|null} success
 * @property {string|null} error
 * @property {string|null} warning
 */

/**
 * @typedef {Object} PageProps
 * @property {{ user: (User|null) }} auth
 * @property {ClinicSettings} settings
 * @property {FlashMessages} flash
 * @property {Record<string, string>} [errors]
 */

export {}
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/types
git commit -m "feat(types): add JSDoc typedefs mirroring backend DTOs"
```

### Task 3.2: Create the `useInertiaForm` and `inertia.js` lib

**Files:**
- Create: `resources/js/lib/inertia.js`

- [ ] **Step 1: Write the lib**

Create `resources/js/lib/inertia.js`:

```js
import { useForm } from '@inertiajs/vue3'

/**
 * Thin wrapper around Inertia's useForm that:
 *  - exposes a `processing` ref bound to the form's transform callback
 *  - optionally resets specific fields on success
 *
 * @param {Record<string, any>} initial
 * @param {{ resetOnSuccess?: string[] }} [options]
 */
export function useInertiaForm(initial, options = {}) {
  const form = useForm(initial)

  form.submit = (method, url, extra = {}) => {
    const submitConfig = {
      onSuccess: () => {
        if (options.resetOnSuccess?.length) {
          form.reset(...options.resetOnSuccess)
        }
      },
      ...extra,
    }
    form.submit(method, url, submitConfig)
  }

  return form
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/lib/inertia.js
git commit -m "feat(lib): add useInertiaForm wrapper with resetOnSuccess"
```

### Task 3.3: Create the `useCurrentUser` and `useFlashMessages` composables

**Files:**
- Create: `resources/js/Composables/useCurrentUser.js`
- Create: `resources/js/Composables/useFlashMessages.js`

- [ ] **Step 1: Write `useCurrentUser`**

Create `resources/js/Composables/useCurrentUser.js`:

```js
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useCurrentUser() {
  const page = usePage()

  const user = computed(() => page.props.auth?.user ?? null)
  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') ?? false)
  const isDoctor = computed(() => user.value?.roles?.includes('doctor') ?? false)
  const isReceptionist = computed(() => user.value?.roles?.includes('receptionist') ?? false)
  const can = (permission) => user.value?.permissions?.includes(permission) ?? false

  return { user, isAuthenticated, isAdmin, isDoctor, isReceptionist, can }
}
```

- [ ] **Step 2: Write `useFlashMessages`**

Create `resources/js/Composables/useFlashMessages.js`:

```js
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from './useToast.js'

export function useFlashMessages() {
  const page = usePage()
  const toast = useToast()

  watch(
    () => ({ ...page.props.flash }),
    (flash) => {
      if (flash.success) toast.success(flash.success)
      if (flash.error)   toast.error(flash.error)
      if (flash.warning) toast.warn(flash.warning)
    },
    { deep: true, immediate: true }
  )
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Composables/useCurrentUser.js resources/js/Composables/useFlashMessages.js
git commit -m "feat(composables): add useCurrentUser and useFlashMessages"
```

### Task 3.4: Create `useToast`, `useConfirm`, `useColorMode`, `useCurrency`, `useNav`

**Files:**
- Create: 5 composable files

- [ ] **Step 1: Write `useToast.js`**

Create `resources/js/Composables/useToast.js`:

```js
import { useToast as usePrimeToast } from 'primevue/usetoast'

let _toast = null

export function provideToastInstance(instance) {
  _toast = instance
}

export function useToast() {
  const toast = _toast ?? usePrimeToast()

  return {
    success: (msg) => toast.add({ severity: 'success', summary: 'Success', detail: msg, life: 4000 }),
    error:   (msg) => toast.add({ severity: 'error',   summary: 'Error',   detail: msg, life: 5000 }),
    warn:    (msg) => toast.add({ severity: 'warn',    summary: 'Warning', detail: msg, life: 4000 }),
    info:    (msg) => toast.add({ severity: 'info',    summary: 'Info',    detail: msg, life: 4000 }),
  }
}
```

- [ ] **Step 2: Write `useConfirm.js`**

Create `resources/js/Composables/useConfirm.js`:

```js
import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'

let _confirm = null

export function provideConfirmInstance(instance) {
  _confirm = instance
}

export function useConfirm() {
  return _confirm ?? usePrimeConfirm()
}
```

- [ ] **Step 3: Write `useColorMode.js`**

Create `resources/js/Composables/useColorMode.js`:

```js
import { ref, watch, onMounted } from 'vue'

const STORAGE_KEY = 'dental-color-mode'

const mode = ref('light')
let initialized = false

function apply(mode) {
  if (typeof document === 'undefined') return
  if (mode === 'dark') document.documentElement.dataset.theme = 'dark'
  else if (mode === 'light') document.documentElement.dataset.theme = 'light'
  else document.documentElement.dataset.theme = ''
}

export function useColorMode() {
  onMounted(() => {
    if (initialized) return
    initialized = true
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === 'dark' || stored === 'light') {
      mode.value = stored
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      mode.value = 'dark'
    }
    apply(mode.value)
  })

  watch(mode, (m) => {
    localStorage.setItem(STORAGE_KEY, m)
    apply(m)
  })

  function toggle() {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
  }

  return { mode, toggle }
}
```

- [ ] **Step 4: Write `useCurrency.js`**

Create `resources/js/Composables/useCurrency.js`:

```js
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { formatMoney } from '@/lib/money.js'

export function useCurrency() {
  const page = usePage()
  const settings = computed(() => page.props.settings)
  return { settings, format: (amount) => formatMoney(amount, settings.value) }
}
```

- [ ] **Step 5: Write `useNav.js`**

Create `resources/js/Composables/useNav.js`:

```js
import { computed } from 'vue'
import { useCurrentUser } from './useCurrentUser.js'

/**
 * Returns the navigation items visible to the current user.
 * Each item declares an optional `permission` or `role`; if the user
 * doesn't satisfy it, the item is filtered out.
 *
 * @returns {import('vue').ComputedRef<import('@/types/nav.js').NavItem[]>}
 */
export function useNav() {
  const { can, isAdmin } = useCurrentUser()

  const all = [
    { label: 'Dashboard',    href: '/dashboard',  icon: 'home',     permission: 'view-dashboard' },
    { label: 'Appointments', href: '#',           icon: 'calendar', comingSoon: true, permission: 'view-dashboard' },
    { label: 'Patients',     href: '#',           icon: 'users',    comingSoon: true, permission: 'view-dashboard' },
    { label: 'Billing',      href: '#',           icon: 'receipt',  comingSoon: true, permission: 'view-dashboard' },
    { label: 'Inventory',    href: '#',           icon: 'package',  comingSoon: true, permission: 'view-dashboard' },
    { label: 'Exchange',     href: '#',           icon: 'arrows',   comingSoon: true, permission: 'view-dashboard' },
    { label: 'Settings',     href: '/settings',   icon: 'cog',      permission: 'manage-settings' },
    { label: 'Users',        href: '/admin/users', icon: 'shield',  permission: 'manage-users' },
  ]

  return computed(() => all.filter((item) => {
    if (item.permission && !can(item.permission)) return false
    if (item.role && !isAdmin.value) return false
    return true
  }))
}
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Composables
git commit -m "feat(composables): add useToast, useConfirm, useColorMode, useCurrency, useNav"
```

### Task 3.5: Create `lib/money.js` and `lib/timezone.js`

**Files:**
- Create: `resources/js/lib/money.js`
- Create: `resources/js/lib/timezone.js`

- [ ] **Step 1: Write `money.js`**

Create `resources/js/lib/money.js`:

```js
/**
 * Format a number as currency using the clinic's settings.
 *
 * @param {number|string} amount
 * @param {import('@/types/settings.js').ClinicSettings} settings
 * @returns {string}
 */
export function formatMoney(amount, settings) {
  const value = Number(amount ?? 0)
  const formatted = value.toFixed(settings.currency_decimals)
  const withSeparator = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return settings.currency_position === 'before'
    ? `${settings.currency_symbol}${withSeparator}`
    : `${withSeparator}${settings.currency_symbol}`
}
```

- [ ] **Step 2: Write `timezone.js`**

Create `resources/js/lib/timezone.js`:

```js
import { format } from 'date-fns'
import { toZonedTime, fromZonedTime, formatInTimeZone } from 'date-fns-tz'

/**
 * @param {Date|string} date
 * @param {string} tz IANA timezone
 * @param {string} [fmt] date-fns format
 */
export function formatInClinicTz(date, tz, fmt = 'yyyy-MM-dd HH:mm') {
  return formatInTimeZone(date, tz, fmt)
}

/**
 * Convert a UTC date into the clinic's local-time Date object (for display).
 * @param {Date|string} date
 * @param {string} tz
 */
export function toClinicLocal(date, tz) {
  return toZonedTime(date, tz)
}

/**
 * Convert a local-time Date (entered in the clinic's timezone) into UTC.
 * @param {Date|string} date
 * @param {string} tz
 */
export function fromClinicLocal(date, tz) {
  return fromZonedTime(date, tz)
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/lib
git commit -m "feat(lib): add money and timezone helpers"
```

---

### Task 3.6: Update the PrimeVue plugin to register Toast + Confirm services

**Files:**
- Modify: `resources/js/plugins/primevue.js`

- [ ] **Step 1: Replace the plugin**

Overwrite `resources/js/plugins/primevue.js`:

```js
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'

import { provideToastInstance } from '@/Composables/useToast.js'
import { provideConfirmInstance } from '@/Composables/useConfirm.js'

/**
 * @param {import('vue').App} app
 */
export default function installPrimeVue(app) {
  app.use(PrimeVue, { unstyled: true, ripple: true })
  app.use(ToastService)
  app.use(ConfirmationService)
  app.directive('tooltip', Tooltip)

  app.mixin({
    setup() {
      provideToastInstance(app.config.globalProperties.$toast)
      provideConfirmInstance(app.config.globalProperties.$confirm)
    },
  })
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/plugins/primevue.js
git commit -m "feat(primevue): register Toast + Confirm services and share with composables"
```

### Task 3.7: Create the test-setup and a sanity Vitest test

**Files:**
- Create: `resources/js/test-setup.js`
- Create: `resources/js/__tests__/Lib/money.test.js`
- Modify: `package.json` (add `test` script)

- [ ] **Step 1: Write the test setup**

Create `resources/js/test-setup.js`:

```js
import { vi } from 'vitest'

// Stub Inertia's usePage so composables can run in isolation.
vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({ props: { auth: { user: null }, settings: {}, flash: {} } }),
  useForm: (initial) => ({
    data: () => initial,
    errors: {},
    processing: false,
    post: vi.fn(),
    reset: vi.fn(),
  }),
}))

vi.mock('ziggy-js', () => ({
  ZiggyVue: { install: () => {} },
}))

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({ add: vi.fn() }),
}))

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({ require: vi.fn() }),
}))
```

- [ ] **Step 2: Write the money test**

Create `resources/js/__tests__/Lib/money.test.js`:

```js
import { describe, it, expect } from 'vitest'
import { formatMoney } from '@/lib/money.js'

const bdt = { currency_symbol: '৳', currency_decimals: 0, currency_position: 'before' }
const usd = { currency_symbol: '$', currency_decimals: 2, currency_position: 'before' }
const inr = { currency_symbol: '₹', currency_decimals: 2, currency_position: 'after' }

describe('formatMoney', () => {
  it('formats BDT without decimals, symbol before', () => {
    expect(formatMoney(1234567, bdt)).toBe('৳1,234,567')
  })
  it('formats USD with 2 decimals, symbol before', () => {
    expect(formatMoney(1234.5, usd)).toBe('$1,234.50')
  })
  it('formats INR with 2 decimals, symbol after', () => {
    expect(formatMoney(99, inr)).toBe('99.00₹')
  })
  it('handles null and undefined', () => {
    expect(formatMoney(null, usd)).toBe('$0.00')
    expect(formatMoney(undefined, bdt)).toBe('৳0')
  })
})
```

- [ ] **Step 3: Add the test script to package.json**

In `package.json`, under `"scripts"`, add:

```json
"test": "vitest run"
```

- [ ] **Step 4: Run the test**

Run: `npm test`
Expected: 4 passing tests.

- [ ] **Step 5: Commit**

```bash
git add resources/js/test-setup.js resources/js/__tests__ package.json
git commit -m "test: add Vitest setup and money.formatMoney test"
```

### Task 3.8: Create the `Auth` layout and the full `Login` page

**Files:**
- Replace: `resources/js/Layouts/Auth.vue`
- Replace: `resources/js/Pages/Auth/Login.vue`
- Create: `resources/js/Pages/Auth/ForgotPassword.vue`
- Create: `resources/js/Pages/Auth/ResetPassword.vue`

(Phase 3.8 ships a minimal auth layout using stock Tailwind. PrimeVue component wrappers land in Phase 4 — for now the Login page uses plain HTML inputs styled with Tailwind utility classes. After Phase 4 we revisit and swap them for `<UiInput>`/`<UiButton>`.)

- [ ] **Step 1: Write the Auth layout**

Overwrite `resources/js/Layouts/Auth.vue`:

```vue
<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

useColorMode()

const settings = computed(() => usePage().props.settings)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-surface-muted p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-6">
        <Link href="/" class="inline-block text-2xl font-semibold text-foreground">
          {{ settings.clinic_name }}
        </Link>
        <p v-if="settings.clinic_tagline" class="text-sm text-muted-foreground mt-1">
          {{ settings.clinic_tagline }}
        </p>
      </div>

      <div class="bg-surface border border-border rounded-lg shadow-sm p-6">
        <slot />
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Write the full Login page (plain HTML inputs — Phase 4 will swap for wrappers)**

Overwrite `resources/js/Pages/Auth/Login.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'

defineProps({
  status: { type: String, default: null },
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login', { onFinish: () => form.reset('password') })
}
</script>

<template>
  <Head title="Log in" />
  <AuthLayout>
    <div v-if="status" class="mb-4 rounded-md bg-success/10 text-success px-3 py-2 text-sm">
      {{ status }}
    </div>

    <h1 class="text-xl font-semibold text-foreground mb-1">Sign in</h1>
    <p class="text-sm text-muted-foreground mb-6">Use your clinic account to continue.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-foreground mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autofocus
          autocomplete="email"
          class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="form.errors.email && 'border-danger'"
        />
        <p v-if="form.errors.email" class="mt-1 text-sm text-danger">{{ form.errors.email }}</p>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="password" class="block text-sm font-medium text-foreground">Password</label>
          <Link href="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700">Forgot?</Link>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
          :class="form.errors.password && 'border-danger'"
        />
        <p v-if="form.errors.password" class="mt-1 text-sm text-danger">{{ form.errors.password }}</p>
      </div>

      <label class="flex items-center gap-2 text-sm text-foreground">
        <input v-model="form.remember" type="checkbox" class="rounded border-border" />
        Remember me
      </label>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full h-10 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50"
      >
        {{ form.processing ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>
  </AuthLayout>
</template>
```

- [ ] **Step 3: Write the Forgot Password page**

Overwrite `resources/js/Pages/Auth/ForgotPassword.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'

const form = useForm({ email: '' })

function submit() {
  form.post('/forgot-password')
}
</script>

<template>
  <Head title="Forgot password" />
  <AuthLayout>
    <h1 class="text-xl font-semibold text-foreground mb-1">Forgot password</h1>
    <p class="text-sm text-muted-foreground mb-6">Enter your email and we'll send a reset link.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-foreground mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
        <p v-if="form.errors.email" class="mt-1 text-sm text-danger">{{ form.errors.email }}</p>
      </div>
      <button type="submit" :disabled="form.processing" class="w-full h-10 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50">
        {{ form.processing ? 'Sending…' : 'Email reset link' }}
      </button>
      <p class="text-sm text-muted-foreground text-center">
        <Link href="/login" class="text-primary-600 hover:text-primary-700">Back to sign in</Link>
      </p>
    </form>
  </AuthLayout>
</template>
```

- [ ] **Step 4: Write the Reset Password page**

Overwrite `resources/js/Pages/Auth/ResetPassword.vue`:

```vue
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/Auth.vue'

const props = defineProps({
  email: { type: String, default: '' },
  token: { type: String, required: true },
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/reset-password', { onSuccess: () => form.reset('password', 'password_confirmation') })
}
</script>

<template>
  <Head title="Reset password" />
  <AuthLayout>
    <h1 class="text-xl font-semibold text-foreground mb-1">Set a new password</h1>
    <p class="text-sm text-muted-foreground mb-6">Enter a new password for your account.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-foreground mb-1">Email</label>
        <input id="email" v-model="form.email" type="email" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
        <p v-if="form.errors.email" class="mt-1 text-sm text-danger">{{ form.errors.email }}</p>
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-foreground mb-1">New password</label>
        <input id="password" v-model="form.password" type="password" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
        <p v-if="form.errors.password" class="mt-1 text-sm text-danger">{{ form.errors.password }}</p>
      </div>
      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1">Confirm password</label>
        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required class="w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500" />
      </div>
      <button type="submit" :disabled="form.processing" class="w-full h-10 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50">
        {{ form.processing ? 'Saving…' : 'Reset password' }}
      </button>
      <p class="text-sm text-muted-foreground text-center">
        <Link href="/login" class="text-primary-600 hover:text-primary-700">Back to sign in</Link>
      </p>
    </form>
  </AuthLayout>
</template>
```

- [ ] **Step 5: Verify the login flow**

In your browser, visit `/login`, log in as `admin@clinic.test` / `password`, and confirm you land on `/dashboard` showing the existing smoke-test page. Try the wrong password — you should see an error. Try logging out and the forgot-password flow.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Layouts/Auth.vue resources/js/Pages/Auth
git commit -m "feat(auth): full Auth layout, Login/Forgot/Reset pages with plain-Tailwind inputs"
```

### Task 3.9: Add Login feature tests

**Files:**
- Create: `tests/Feature/Auth/LoginTest.php`
- Create: `tests/Feature/Auth/LogoutTest.php`
- Create: `tests/Feature/Auth/DeactivatedUserCannotLoginTest.php`

- [ ] **Step 1: Write LoginTest**

Create `tests/Feature/Auth/LoginTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('logs in a valid user and redirects to dashboard', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'is_active' => true,
    ])->assignRole('admin');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'x@y.test', 'is_active' => true]);

    $this->post('/login', [
        'email' => 'x@y.test',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rate-limits after 5 failed attempts', function () {
    User::factory()->create(['email' => 'rl@y.test', 'is_active' => true]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'rl@y.test', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'rl@y.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});
```

- [ ] **Step 2: Write LogoutTest**

Create `tests/Feature/Auth/LogoutTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('logs out the authenticated user', function () {
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});
```

- [ ] **Step 3: Write DeactivatedUserCannotLoginTest**

Create `tests/Feature/Auth/DeactivatedUserCannotLoginTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('rejects login for a deactivated user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'is_active' => false,
    ])->assignRole('admin');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/login')->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('signs out a deactivated user who is currently logged in', function () {
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $user->update(['is_active' => false]);

    $this->get('/dashboard')->assertRedirect('/login');
    $this->assertGuest();
});
```

- [ ] **Step 4: Run the new tests**

Run: `docker exec dental_app php artisan test --filter=Auth`
Expected: 5 tests pass.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Auth
git commit -m "test: add login, logout, deactivated-user feature tests"
```

---

## Phase 4 — UI wrapper component library (PrimeVue unstyled + Tailwind tokens)

**Goal:** Build the wrapper layer at `resources/js/Components/ui/` — 30 components wrapping PrimeVue unstyled with our Tailwind design tokens. Every later sub-project consumes from `@/Components/ui`, never from `primevue/*`.

### Task 4.1: Create the PrimeVue token override CSS

**Files:**
- Create: `resources/css/primevue-overrides.css`
- Modify: `resources/css/app.css` (import it)

- [ ] **Step 1: Write the override CSS**

Create `resources/css/primevue-overrides.css`:

```css
:root {
  --p-primary-color:        var(--color-primary-600);
  --p-primary-hover-color:  var(--color-primary-700);
  --p-primary-active-color: var(--color-primary-700);
  --p-primary-contrast-color: #ffffff;

  --p-content-background:    var(--color-surface);
  --p-content-hover-background: var(--color-surface-muted);
  --p-content-border-color:  var(--color-border);
  --p-content-color:         var(--color-foreground);
  --p-content-hover-color:   var(--color-foreground);

  --p-text-color:        var(--color-foreground);
  --p-text-hover-color:  var(--color-foreground);
  --p-text-muted-color:  var(--color-muted-foreground);

  --p-content-border-radius: var(--radius-md);
  --p-overlay-modal-border-radius: var(--radius-lg);
}
```

- [ ] **Step 2: Import the override CSS from `app.css`**

Edit `resources/css/app.css` — add the import at the top, after the `@import "tailwindcss";` line:

```css
@import "./primevue-overrides.css";
```

- [ ] **Step 3: Commit**

```bash
git add resources/css/primevue-overrides.css resources/css/app.css
git commit -m "feat(css): add primevue-overrides.css mapping PrimeVue tokens to our @theme tokens"
```

### Task 4.2: Build the `Button` wrapper

**Files:**
- Create: `resources/js/Components/ui/Button.vue`
- Create: `resources/js/__tests__/Components/ui/Button.test.js`

- [ ] **Step 1: Write the failing test**

Create `resources/js/__tests__/Components/ui/Button.test.js`:

```js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '@/Components/ui/Button.vue'

const factory = (props = {}, slots = { default: 'Click me' }) =>
  mount(Button, { props, slots })

describe('UiButton', () => {
  it('renders default primary variant classes', () => {
    const wrapper = factory()
    const html = wrapper.html()
    expect(html).toContain('bg-primary-600')
    expect(html).toContain('text-white')
  })

  it('renders secondary variant', () => {
    const wrapper = factory({ variant: 'secondary' })
    expect(wrapper.html()).toContain('bg-surface-muted')
  })

  it('renders danger variant', () => {
    const wrapper = factory({ variant: 'danger' })
    expect(wrapper.html()).toContain('bg-danger')
  })

  it('renders size sm', () => {
    const wrapper = factory({ size: 'sm' })
    expect(wrapper.html()).toContain('h-8')
  })

  it('disables when loading', () => {
    const wrapper = factory({ loading: true })
    expect(wrapper.html()).toMatch(/disabled/i)
  })
})
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `npm test -- Button`
Expected: FAIL — module not found.

- [ ] **Step 3: Write the Button component**

Create `resources/js/Components/ui/Button.vue`:

```vue
<script setup>
import { computed } from 'vue'
import PrimeButton from 'primevue/button'

const props = defineProps({
  variant:  { type: String, default: 'primary', validator: (v) => ['primary','secondary','ghost','danger'].includes(v) },
  size:     { type: String, default: 'md',      validator: (v) => ['sm','md','lg'].includes(v) },
  loading:  { type: Boolean, default: false },
  type:     { type: String, default: 'button',  validator: (v) => ['button','submit','reset'].includes(v) },
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
  <PrimeButton
    :type="type"
    :disabled="disabled || loading"
    :pt="{ root: { class: classes } }"
    :loading="loading"
  >
    <slot />
  </PrimeButton>
</template>
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `npm test -- Button`
Expected: 5 tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Button.vue resources/js/__tests__/Components/ui/Button.test.js
git commit -m "feat(ui): add Button wrapper with variant/size/loading props"
```

### Task 4.3: Build the `Input` wrapper

**Files:**
- Create: `resources/js/Components/ui/Input.vue`
- Create: `resources/js/__tests__/Components/ui/Input.test.js`

- [ ] **Step 1: Write the test**

Create `resources/js/__tests__/Components/ui/Input.test.js`:

```js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '@/Components/ui/Input.vue'

describe('UiInput', () => {
  it('renders base input classes', () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    expect(wrapper.html()).toContain('border')
    expect(wrapper.html()).toContain('rounded-md')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input, { props: { modelValue: '' } })
    await wrapper.find('input').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['hello'])
  })

  it('applies error border when invalid', () => {
    const wrapper = mount(Input, { props: { modelValue: '', invalid: true } })
    expect(wrapper.html()).toContain('border-danger')
  })
})
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `npm test -- Input`
Expected: FAIL.

- [ ] **Step 3: Write the Input component**

Create `resources/js/Components/ui/Input.vue`:

```vue
<script setup>
import { computed } from 'vue'
import PrimeInputText from 'primevue/inputtext'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
  autocomplete: { type: String, default: null },
})

defineEmits(['update:modelValue', 'blur', 'focus'])

const classes = computed(() => [
  'w-full h-10 px-3 rounded-md bg-surface text-foreground placeholder:text-muted-foreground',
  'focus:outline-none focus:ring-2 focus:ring-primary-500',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  props.invalid ? 'border border-danger' : 'border border-border',
].join(' '))
</script>

<template>
  <PrimeInputText
    :type="type"
    :model-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :pt="{ root: { class: classes } }"
    @update:model-value="$emit('update:modelValue', $event)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `npm test -- Input`
Expected: 3 tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Input.vue resources/js/__tests__/Components/ui/Input.test.js
git commit -m "feat(ui): add Input wrapper with invalid state and PrimeVue pass-through"
```

### Task 4.4: Build `Textarea`, `Select`, `Checkbox`, `Switch`, `RadioGroup`

**Files:** 5 new components, 5 test files

(Each follows the same TDD pattern as 4.2/4.3. To keep the plan readable, this task shows the full code for each — no placeholders.)

- [ ] **Step 1: Textarea**

Create `resources/js/Components/ui/Textarea.vue`:

```vue
<script setup>
import { computed } from 'vue'
import PrimeTextarea from 'primevue/textarea'

const props = defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 4 },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const classes = computed(() => [
  'w-full px-3 py-2 rounded-md bg-surface text-foreground placeholder:text-muted-foreground',
  'focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50',
  props.invalid ? 'border border-danger' : 'border border-border',
].join(' '))
</script>

<template>
  <PrimeTextarea
    :rows="rows"
    :model-value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :pt="{ root: { class: classes } }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
```

- [ ] **Step 2: Select**

Create `resources/js/Components/ui/Select.vue`:

```vue
<script setup>
import PrimeSelect from 'primevue/select'

defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  optionLabel: { type: [String, Function], default: 'label' },
  optionValue: { type: [String, Function], default: 'value' },
  placeholder: { type: String, default: 'Select…' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'change'])
</script>

<template>
  <PrimeSelect
    :model-value="modelValue"
    :options="options"
    :option-label="optionLabel"
    :option-value="optionValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :pt="{
      root: { class: 'h-10 w-full rounded-md border bg-surface' + (invalid ? ' border-danger' : ' border-border') },
      label: { class: 'px-3 text-foreground' },
      panel: { class: 'rounded-md border border-border bg-surface' },
      option: { class: 'px-3 py-2 hover:bg-surface-muted cursor-pointer' },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
    @change="$emit('change', $event)"
  />
</template>
```

- [ ] **Step 3: Checkbox**

Create `resources/js/Components/ui/Checkbox.vue`:

```vue
<script setup>
import PrimeCheckbox from 'primevue/checkbox'

defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: null },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <label class="inline-flex items-center gap-2 cursor-pointer">
    <PrimeCheckbox
      :model-value="modelValue"
      :disabled="disabled"
      :pt="{ root: { class: 'w-4 h-4 rounded border border-border text-primary-600 focus:ring-2 focus:ring-primary-500' }, box: { class: 'rounded' } }"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <span v-if="label" class="text-sm text-foreground">{{ label }}</span>
  </label>
</template>
```

- [ ] **Step 4: Switch**

Create `resources/js/Components/ui/Switch.vue`:

```vue
<script setup>
import InputSwitch from 'primevue/inputswitch'

defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <InputSwitch
    :model-value="modelValue"
    :disabled="disabled"
    :pt="{
      root: { class: 'relative inline-block w-10 h-6 rounded-full transition-colors cursor-pointer' + (modelValue ? ' bg-primary-600' : ' bg-surface-muted border border-border') },
      slider: { class: 'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform shadow' + (modelValue ? ' translate-x-4' : '') },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
```

- [ ] **Step 5: RadioGroup**

Create `resources/js/Components/ui/RadioGroup.vue`:

```vue
<script setup>
import PrimeRadioButton from 'primevue/radiobutton'

defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  name: { type: String, required: true },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <div class="space-y-2">
    <label
      v-for="opt in options"
      :key="opt[optionValue]"
      class="flex items-center gap-2 cursor-pointer text-sm text-foreground"
    >
      <PrimeRadioButton
        :model-value="modelValue"
        :value="opt[optionValue]"
        :name="name"
        :disabled="disabled"
        :pt="{ root: { class: 'w-4 h-4 text-primary-600 focus:ring-2 focus:ring-primary-500' } }"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      {{ opt[optionLabel] }}
    </label>
  </div>
</template>
```

- [ ] **Step 6: Add minimal tests for each**

Create `resources/js/__tests__/Components/ui/FormControls.test.js`:

```js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Textarea from '@/Components/ui/Textarea.vue'
import Select from '@/Components/ui/Select.vue'
import Checkbox from '@/Components/ui/Checkbox.vue'
import Switch from '@/Components/ui/Switch.vue'
import RadioGroup from '@/Components/ui/RadioGroup.vue'

describe('UiTextarea', () => {
  it('renders', () => {
    const wrapper = mount(Textarea, { props: { modelValue: '' } })
    expect(wrapper.find('textarea').exists()).toBe(true)
  })
})

describe('UiSelect', () => {
  it('renders the placeholder', () => {
    const wrapper = mount(Select, { props: { modelValue: null, options: [] } })
    expect(wrapper.html()).toContain('Select…')
  })
})

describe('UiCheckbox', () => {
  it('renders a label when given', () => {
    const wrapper = mount(Checkbox, { props: { modelValue: false, label: 'Remember me' } })
    expect(wrapper.html()).toContain('Remember me')
  })
})

describe('UiSwitch', () => {
  it('renders on and off classes based on value', () => {
    const on = mount(Switch, { props: { modelValue: true } })
    const off = mount(Switch, { props: { modelValue: false } })
    expect(on.html()).toContain('bg-primary-600')
    expect(off.html()).toContain('bg-surface-muted')
  })
})

describe('UiRadioGroup', () => {
  it('renders one option per item', () => {
    const wrapper = mount(RadioGroup, {
      props: { modelValue: 'a', name: 'r', options: [{ label: 'A', value: 'a' }, { label: 'B', value: 'b' }] },
    })
    expect(wrapper.findAll('label')).toHaveLength(2)
  })
})
```

- [ ] **Step 7: Run all the tests**

Run: `npm test`
Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add resources/js/Components/ui/Textarea.vue resources/js/Components/ui/Select.vue resources/js/Components/ui/Checkbox.vue resources/js/Components/ui/Switch.vue resources/js/Components/ui/RadioGroup.vue resources/js/__tests__/Components/ui/FormControls.test.js
git commit -m "feat(ui): add Textarea, Select, Checkbox, Switch, RadioGroup wrappers"
```

### Task 4.5: Build `DatePicker` and `TimePicker`

**Files:** 2 components

- [ ] **Step 1: DatePicker**

Create `resources/js/Components/ui/DatePicker.vue`:

```vue
<script setup>
import DatePicker from 'primevue/datepicker'

defineProps({
  modelValue: { type: [Date, String, null], default: null },
  placeholder: { type: String, default: 'Pick a date' },
  showTime: { type: Boolean, default: false },
  dateFormat: { type: String, default: 'yy-mm-dd' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <DatePicker
    :model-value="modelValue"
    :placeholder="placeholder"
    :show-time="showTime"
    :date-format="dateFormat"
    :disabled="disabled"
    :pt="{
      root: { class: 'w-full' },
      input: { class: 'w-full h-10 px-3 rounded-md bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500' + (invalid ? ' border border-danger' : ' border border-border') },
      panel: { class: 'rounded-md border border-border bg-surface shadow-md mt-1' },
    }"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
```

- [ ] **Step 2: TimePicker**

Create `resources/js/Components/ui/TimePicker.vue`:

```vue
<script setup>
import DatePicker from 'primevue/datepicker'

defineProps({
  modelValue: { type: [Date, String, null], default: null },
  placeholder: { type: String, default: 'Pick a time' },
  timeOnly: { type: Boolean, default: true },
  hourFormat: { type: String, default: '24' },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <DatePicker
    :model-value="modelValue"
    :placeholder="placeholder"
    :time-only="timeOnly"
    :hour-format="hourFormat"
    :disabled="disabled"
    :pt="{
      root: { class: 'w-full' },
      input: { class: 'w-full h-10 px-3 rounded-md border border-border bg-surface text-foreground focus:outline-none focus:ring-2 focus:ring-primary-500' },
    }"
    @update:model-value="$emit('modelValue', $event)"
  />
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/ui/DatePicker.vue resources/js/Components/ui/TimePicker.vue
git commit -m "feat(ui): add DatePicker and TimePicker wrappers"
```

---

### Task 4.6: Build `Dialog`, `Drawer`, `Popover`, `Tooltip`, `DropdownMenu`

**Files:** 5 components

- [ ] **Step 1: Dialog**

Create `resources/js/Components/ui/Dialog.vue`:

```vue
<script setup>
import Dialog from 'primevue/dialog'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: null },
  modal: { type: Boolean, default: true },
  closable: { type: Boolean, default: true },
  size: { type: String, default: 'md', validator: (v) => ['sm','md','lg','xl'].includes(v) },
})
defineEmits(['update:modelValue', 'close'])

const sizeClass = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-2xl' }
</script>

<template>
  <Dialog
    :visible="modelValue"
    :modal="modal"
    :closable="closable"
    :pt="{
      root: { class: 'fixed inset-0 z-50 flex items-center justify-center p-4' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'relative bg-surface border border-border rounded-lg shadow-xl w-full ' + sizeClass[size] },
      header: { class: 'flex items-center justify-between px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 text-foreground' },
      footer: { class: 'px-4 py-3 border-t border-border flex justify-end gap-2' },
    }"
    @update:visible="$emit('update:modelValue', $event)"
    @close="$emit('close')"
  >
    <template #header>
      <slot name="header">
        <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
      </slot>
    </template>
    <slot />
    <template #footer>
      <slot name="footer" />
    </template>
  </Dialog>
</template>
```

- [ ] **Step 2: Drawer**

Create `resources/js/Components/ui/Drawer.vue`:

```vue
<script setup>
import Drawer from 'primevue/drawer'

defineProps({
  modelValue: { type: Boolean, default: false },
  position: { type: String, default: 'right', validator: (v) => ['left','right','top','bottom'].includes(v) },
  title: { type: String, default: null },
})
defineEmits(['update:modelValue', 'close'])
</script>

<template>
  <Drawer
    :visible="modelValue"
    :position="position"
    :pt="{
      root: { class: 'fixed inset-0 z-50' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'absolute bg-surface border-border shadow-xl flex flex-col ' + (
        position === 'right' ? 'right-0 top-0 h-full w-80 border-l' :
        position === 'left'  ? 'left-0 top-0 h-full w-80 border-r' :
        position === 'top'   ? 'top-0 left-0 w-full h-80 border-b' :
                                'bottom-0 left-0 w-full h-80 border-t'
      ) },
      header: { class: 'px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 flex-1 overflow-y-auto' },
    }"
    @update:visible="$emit('update:modelValue', $event)"
    @close="$emit('close')"
  >
    <template #header>
      <slot name="header">
        <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
      </slot>
    </template>
    <slot />
  </Drawer>
</template>
```

- [ ] **Step 3: Popover**

Create `resources/js/Components/ui/Popover.vue`:

```vue
<script setup>
import Popover from 'primevue/popover'

defineProps({})
defineEmits([])

const pt = {
  root: { class: 'absolute z-40' },
  content: { class: 'bg-surface border border-border rounded-md shadow-md p-3' },
}
</script>

<template>
  <Popover :pt="pt">
    <slot />
  </Popover>
</template>
```

- [ ] **Step 4: Tooltip component (the directive is registered globally in primevue.js)**

Create `resources/js/Components/ui/Tooltip.vue`:

```vue
<script setup>
import { ref } from 'vue'

const visible = ref(false)
function show() { visible.value = true }
function hide() { visible.value = false }
</script>

<template>
  <span class="relative inline-block" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
    <slot />
    <Transition
      enter-active-class="transition-opacity"
      leave-active-class="transition-opacity"
      enter-from-class="opacity-0" leave-to-class="opacity-0"
    >
      <span
        v-if="visible"
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs text-white bg-foreground rounded shadow whitespace-nowrap z-50"
        role="tooltip"
      >
        <slot name="content" />
      </span>
    </Transition>
  </span>
</template>
```

- [ ] **Step 5: DropdownMenu**

Create `resources/js/Components/ui/DropdownMenu.vue`:

```vue
<script setup>
import Menu from 'primevue/menu'

defineProps({
  modelValue: { type: Array, required: true },
  trigger: { type: Object, default: null },
})
const emit = defineEmits(['select'])

const pt = {
  root: { class: 'min-w-[12rem] bg-surface border border-border rounded-md shadow-md py-1' },
  menu: { class: 'flex flex-col gap-0 list-none m-0 p-0' },
  item: { class: 'px-3 py-2 text-sm text-foreground hover:bg-surface-muted cursor-pointer flex items-center gap-2' },
  separator: { class: 'my-1 border-t border-border' },
}
</script>

<template>
  <Menu
    :model="modelValue"
    :popup="true"
    :pt="pt"
    @item-select="(e) => emit('select', e.item)"
  >
    <template #itemicon="{ item }">
      <span v-if="item.icon" :class="item.icon" />
    </template>
  </Menu>
</template>
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/Dialog.vue resources/js/Components/ui/Drawer.vue resources/js/Components/ui/Popover.vue resources/js/Components/ui/Tooltip.vue resources/js/Components/ui/DropdownMenu.vue
git commit -m "feat(ui): add Dialog, Drawer, Popover, Tooltip, DropdownMenu wrappers"
```

### Task 4.7: Build `Tabs`, `Accordion`, `Toast`, `Alert`, `Badge`

**Files:** 5 components

- [ ] **Step 1: Tabs**

Create `resources/js/Components/ui/Tabs.vue`:

```vue
<script setup>
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'

defineProps({ modelValue: { type: String, default: null } })
defineEmits(['update:modelValue'])

const pt = {
  tabList: { class: 'flex border-b border-border gap-1' },
  tab: ({ context }) => [
    'px-4 py-2 text-sm font-medium cursor-pointer transition-colors border-b-2 -mb-px',
    context.active ? 'border-primary-600 text-primary-700' : 'border-transparent text-muted-foreground hover:text-foreground',
  ],
  tabPanels: { class: 'pt-4' },
}
</script>

<template>
  <Tabs :value="modelValue" @update:value="$emit('update:modelValue', $event)">
    <TabList :pt="pt.tabList">
      <Tab v-for="t in $slots.default ? [] : []" :key="t" :value="t" />
    </TabList>
    <slot />
  </Tabs>
</template>
```

(Use this wrapper as a passthrough — most callers use the raw PrimeVue Tabs with our tailwind class overrides via `pt` on their pages. The convenience component is intentionally minimal.)

- [ ] **Step 2: Accordion**

Create `resources/js/Components/ui/Accordion.vue`:

```vue
<script setup>
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'

defineProps({
  modelValue: { type: [String, Array], default: null },
  multiple: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const pt = {
  root: { class: 'border border-border rounded-md overflow-hidden divide-y divide-border' },
  header: { class: 'bg-surface hover:bg-surface-muted' },
  headerContent: { class: 'px-4 py-3 font-medium text-foreground' },
  content: { class: 'px-4 py-3 text-foreground bg-surface' },
}
</script>

<template>
  <Accordion
    :value="modelValue"
    :multiple="multiple"
    :pt="pt"
    @update:value="$emit('update:modelValue', $event)"
  >
    <slot />
  </Accordion>
</template>
```

- [ ] **Step 3: Toast (mounted once in the App root)**

Create `resources/js/Components/ui/Toast.vue`:

```vue
<script setup>
// PrimeVue's toast service is wired to the useToast() composable globally
// by the PrimeVue plugin (resources/js/plugins/primevue.js). This component
// just renders the global Toast container.
import Toast from 'primevue/toast'
</script>

<template>
  <Toast
    position="top-right"
    :pt="{
      root: { class: 'fixed top-4 right-4 z-[100] flex flex-col gap-2' },
      message: { class: 'bg-surface border border-border rounded-md shadow-md px-4 py-3 text-sm text-foreground flex items-start gap-2' },
      messageContent: { class: 'flex flex-col gap-0.5' },
      summary: { class: 'font-semibold' },
      detail: { class: 'text-muted-foreground' },
    }"
  />
</template>
```

- [ ] **Step 4: Alert**

Create `resources/js/Components/ui/Alert.vue`:

```vue
<script setup>
defineProps({
  variant: { type: String, default: 'info', validator: (v) => ['info','success','warning','danger'].includes(v) },
  title: { type: String, default: null },
})

const classes = {
  info:    'bg-primary-50 text-primary-700 border-primary-200',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger:  'bg-danger/10 text-danger border-danger/30',
}
</script>

<template>
  <div :class="['rounded-md border px-3 py-2 text-sm', classes[variant]]" role="alert">
    <p v-if="title" class="font-semibold mb-0.5">{{ title }}</p>
    <p><slot /></p>
  </div>
</template>
```

- [ ] **Step 5: Badge**

Create `resources/js/Components/ui/Badge.vue`:

```vue
<script setup>
defineProps({
  variant: { type: String, default: 'neutral', validator: (v) => ['neutral','primary','success','warning','danger'].includes(v) },
})

const classes = {
  neutral: 'bg-surface-muted text-muted-foreground border-border',
  primary: 'bg-primary-50 text-primary-700 border-primary-200',
  success: 'bg-success/10 text-success border-success/30',
  warning: 'bg-warning/10 text-warning border-warning/30',
  danger:  'bg-danger/10 text-danger border-danger/30',
}
</script>

<template>
  <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border', classes[variant]]">
    <slot />
  </span>
</template>
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/Tabs.vue resources/js/Components/ui/Accordion.vue resources/js/Components/ui/Toast.vue resources/js/Components/ui/Alert.vue resources/js/Components/ui/Badge.vue
git commit -m "feat(ui): add Tabs, Accordion, Toast, Alert, Badge wrappers"
```

### Task 4.8: Build `Card`, `DataTable`, `Pagination`, `Breadcrumbs`, `Avatar`, `Spinner`, `Skeleton`, `EmptyState`, `Stat`, `ConfirmDialog`

**Files:** 10 components

- [ ] **Step 1: Card**

Create `resources/js/Components/ui/Card.vue`:

```vue
<script setup>
defineProps({
  title: { type: String, default: null },
  padded: { type: Boolean, default: true },
})
</script>

<template>
  <div class="bg-surface border border-border rounded-lg shadow-sm">
    <div v-if="title || $slots.header" class="px-4 py-3 border-b border-border flex items-center justify-between">
      <slot name="header">
        <h3 class="text-base font-semibold text-foreground">{{ title }}</h3>
      </slot>
    </div>
    <div :class="padded ? 'p-4' : ''">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-4 py-3 border-t border-border bg-surface-muted">
      <slot name="footer" />
    </div>
  </div>
</template>
```

- [ ] **Step 2: DataTable**

Create `resources/js/Components/ui/DataTable.vue`:

```vue
<script setup>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

defineProps({
  value: { type: Array, required: true },
  paginator: { type: Boolean, default: true },
  rows: { type: Number, default: 10 },
  loading: { type: Boolean, default: false },
  striped: { type: Boolean, default: true },
  selectionMode: { type: String, default: null },
  vModel: { type: [Array, Object], default: undefined },
})
defineEmits(['update:selection', 'row-click', 'sort', 'page'])

const pt = {
  root: { class: 'bg-surface border border-border rounded-lg overflow-hidden' },
  table: { class: 'w-full text-sm' },
  thead: { class: 'bg-surface-muted text-muted-foreground' },
  headerCell: { class: 'px-4 py-2 text-left font-medium border-b border-border' },
  bodyRow: ({ context }) => [
    'border-b border-border last:border-b-0',
    context.selected ? 'bg-primary-50' : 'hover:bg-surface-muted',
    context.striped && context.even ? 'bg-surface-muted/50' : '',
  ],
  bodyCell: { class: 'px-4 py-2 text-foreground' },
  paginator: { class: 'flex items-center justify-between px-4 py-2 border-t border-border bg-surface' },
  pageButton: ({ context }) => [
    'h-8 px-2 rounded text-sm',
    context.active ? 'bg-primary-600 text-white' : 'text-foreground hover:bg-surface-muted',
  ],
}
</script>

<template>
  <DataTable
    :value="value"
    :paginator="paginator"
    :rows="rows"
    :loading="loading"
    :selection-mode="selectionMode"
    :pt="pt"
    @row-click="(e) => $emit('row-click', e)"
    @page="(e) => $emit('page', e)"
    @sort="(e) => $emit('sort', e)"
  >
    <slot />
  </DataTable>
</template>
```

- [ ] **Step 3: Pagination (used standalone, not inside DataTable)**

Create `resources/js/Components/ui/Pagination.vue`:

```vue
<script setup>
import Paginator from 'primevue/paginator'

const props = defineProps({
  rows: { type: Number, default: 10 },
  totalRecords: { type: Number, default: 0 },
  first: { type: Number, default: 0 },
  rowsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
})
defineEmits(['page', 'update:first'])

const pt = {
  root: { class: 'flex items-center justify-between gap-2 px-4 py-2 bg-surface border border-border rounded-md' },
  pageButton: ({ context }) => [
    'h-8 min-w-[2rem] px-2 rounded text-sm',
    context.active ? 'bg-primary-600 text-white' : 'text-foreground hover:bg-surface-muted',
  ],
  rowsPerPageDropdown: { class: 'h-8' },
}
</script>

<template>
  <Paginator
    :rows="rows"
    :total-records="totalRecords"
    :first="first"
    :rows-per-page-options="rowsPerPageOptions"
    :pt="pt"
    @page="$emit('page', $event)"
  />
</template>
```

- [ ] **Step 4: Breadcrumbs**

Create `resources/js/Components/ui/Breadcrumbs.vue`:

```vue
<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  items: { type: Array, required: true },
})
</script>

<template>
  <nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 flex-wrap">
      <li v-for="(item, i) in items" :key="i" class="flex items-center gap-1.5">
        <Link v-if="item.href && i < items.length - 1" :href="item.href" class="text-muted-foreground hover:text-foreground">
          {{ item.label }}
        </Link>
        <span v-else class="text-foreground font-medium">{{ item.label }}</span>
        <span v-if="i < items.length - 1" class="text-muted-foreground">/</span>
      </li>
    </ol>
  </nav>
</template>
```

- [ ] **Step 5: Avatar**

Create `resources/js/Components/ui/Avatar.vue`:

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  name: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (v) => ['sm','md','lg','xl'].includes(v) },
})

const sizes = { sm: 'h-6 w-6 text-xs', md: 'h-8 w-8 text-sm', lg: 'h-10 w-10 text-base', xl: 'h-14 w-14 text-lg' }

const initials = computed(() => {
  if (!props.name) return '?'
  return props.name
    .split(' ')
    .map((p) => p[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
})
</script>

<template>
  <div :class="['inline-flex items-center justify-center rounded-full bg-primary-100 text-primary-700 font-medium overflow-hidden', sizes[size]]" :title="name">
    <img v-if="src" :src="src" :alt="name" class="h-full w-full object-cover" />
    <span v-else>{{ initials }}</span>
  </div>
</template>
```

- [ ] **Step 6: Spinner**

Create `resources/js/Components/ui/Spinner.vue`:

```vue
<script setup>
defineProps({
  size: { type: String, default: 'md', validator: (v) => ['sm','md','lg'].includes(v) },
  label: { type: String, default: 'Loading…' },
})

const sizes = { sm: 'h-4 w-4 border-2', md: 'h-6 w-6 border-2', lg: 'h-10 w-10 border-4' }
</script>

<template>
  <span :class="['inline-block rounded-full border-primary-600 border-t-transparent animate-spin', sizes[size]]" role="status" :aria-label="label" />
</template>
```

- [ ] **Step 7: Skeleton**

Create `resources/js/Components/ui/Skeleton.vue`:

```vue
<script setup>
defineProps({
  width: { type: String, default: 'w-full' },
  height: { type: String, default: 'h-4' },
  rounded: { type: String, default: 'rounded' },
})
</script>

<template>
  <div :class="['animate-pulse bg-surface-muted', width, height, rounded]" />
</template>
```

- [ ] **Step 8: EmptyState**

Create `resources/js/Components/ui/EmptyState.vue`:

```vue
<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  icon: { type: String, default: null },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center p-8">
    <div v-if="icon" :class="icon" class="text-4xl text-muted-foreground mb-2" />
    <h3 class="text-base font-semibold text-foreground">{{ title }}</h3>
    <p v-if="description" class="text-sm text-muted-foreground mt-1 max-w-sm">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
```

- [ ] **Step 9: Stat (KPI card)**

Create `resources/js/Components/ui/Stat.vue`:

```vue
<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: null },
  trend: { type: String, default: null, validator: (v) => v === null || ['up','down','flat'].includes(v) },
  trendValue: { type: String, default: null },
})
</script>

<template>
  <div class="bg-surface border border-border rounded-lg p-4">
    <p class="text-xs uppercase tracking-wide text-muted-foreground font-medium">{{ label }}</p>
    <p class="text-2xl font-semibold text-foreground mt-2">{{ value }}</p>
    <p v-if="hint" class="text-xs text-muted-foreground mt-1">{{ hint }}</p>
    <p v-if="trend" :class="[
      'text-xs mt-2 font-medium inline-flex items-center gap-1',
      trend === 'up'   ? 'text-success' :
      trend === 'down' ? 'text-danger'  : 'text-muted-foreground'
    ]">
      <span v-if="trend === 'up'">↑</span>
      <span v-else-if="trend === 'down'">↓</span>
      <span v-else>→</span>
      {{ trendValue }}
    </p>
  </div>
</template>
```

- [ ] **Step 10: ConfirmDialog (mounted once, opened by useConfirm())**

Create `resources/js/Components/ui/ConfirmDialog.vue`:

```vue
<script setup>
import ConfirmDialog from 'primevue/confirmdialog'
</script>

<template>
  <ConfirmDialog
    :pt="{
      root: { class: 'fixed inset-0 z-50 flex items-center justify-center p-4' },
      mask: { class: 'fixed inset-0 bg-black/50' },
      container: { class: 'bg-surface border border-border rounded-lg shadow-xl w-full max-w-md' },
      header: { class: 'px-4 py-3 border-b border-border' },
      title: { class: 'text-lg font-semibold text-foreground' },
      content: { class: 'p-4 text-foreground' },
      footer: { class: 'px-4 py-3 border-t border-border flex justify-end gap-2' },
      acceptButton: { class: 'h-10 px-4 rounded-md bg-primary-600 text-white font-medium hover:bg-primary-700' },
      rejectButton: { class: 'h-10 px-4 rounded-md bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70' },
    }"
  />
</template>
```

- [ ] **Step 11: Commit**

```bash
git add resources/js/Components/ui/Card.vue resources/js/Components/ui/DataTable.vue resources/js/Components/ui/Pagination.vue resources/js/Components/ui/Breadcrumbs.vue resources/js/Components/ui/Avatar.vue resources/js/Components/ui/Spinner.vue resources/js/Components/ui/Skeleton.vue resources/js/Components/ui/EmptyState.vue resources/js/Components/ui/Stat.vue resources/js/Components/ui/ConfirmDialog.vue
git commit -m "feat(ui): add Card, DataTable, Pagination, Breadcrumbs, Avatar, Spinner, Skeleton, EmptyState, Stat, ConfirmDialog"
```

### Task 4.9: Build `Form`, `Field`, `FormError`, `FormActions`

**Files:** 4 components, 1 barrel index

- [ ] **Step 1: Form**

Create `resources/js/Components/ui/Form/Form.vue`:

```vue
<script setup>
defineProps({
  method: { type: String, default: 'post' },
  action: { type: String, default: null },
})
</script>

<template>
  <form
    :method="method"
    :action="action"
    @submit.prevent="$emit('submit', $event)"
    class="space-y-4"
  >
    <slot />
  </form>
</template>
```

- [ ] **Step 2: Field**

Create `resources/js/Components/ui/Form/Field.vue`:

```vue
<script setup>
defineProps({
  label: { type: String, default: null },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
  hint: { type: String, default: null },
  htmlFor: { type: String, default: null },
})
</script>

<template>
  <div>
    <label v-if="label" :for="htmlFor" class="block text-sm font-medium text-foreground mb-1">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    <slot />
    <p v-if="hint && !error" class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-sm text-danger">{{ error }}</p>
  </div>
</template>
```

- [ ] **Step 3: FormError (summary at top of form)**

Create `resources/js/Components/ui/Form/FormError.vue`:

```vue
<script setup>
defineProps({
  errors: { type: Object, default: () => ({}) },
})
</script>

<template>
  <div v-if="Object.keys(errors).length" class="rounded-md border border-danger/30 bg-danger/10 p-3 text-sm text-danger">
    <p class="font-semibold mb-1">Please correct the following:</p>
    <ul class="list-disc list-inside space-y-0.5">
      <li v-for="(msg, field) in errors" :key="field">{{ msg }}</li>
    </ul>
  </div>
</template>
```

- [ ] **Step 4: FormActions**

Create `resources/js/Components/ui/Form/FormActions.vue`:

```vue
<script setup>
import Button from '@/Components/ui/Button.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  processing: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  cancelHref: { type: String, default: null },
  showCancel: { type: Boolean, default: false },
})
defineEmits(['submit', 'cancel'])
</script>

<template>
  <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
    <Link v-if="showCancel && cancelHref" :href="cancelHref" class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border hover:bg-surface-muted/70 text-sm">
      Cancel
    </Link>
    <button
      type="submit"
      :disabled="processing"
      class="h-10 px-4 rounded-md bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
    >
      {{ processing ? 'Saving…' : submitLabel }}
    </button>
  </div>
</template>
```

- [ ] **Step 5: Barrel index**

Create `resources/js/Components/ui/index.js`:

```js
export { default as UiButton }         from './Button.vue'
export { default as UiInput }          from './Input.vue'
export { default as UiTextarea }       from './Textarea.vue'
export { default as UiSelect }         from './Select.vue'
export { default as UiCheckbox }       from './Checkbox.vue'
export { default as UiRadioGroup }     from './RadioGroup.vue'
export { default as UiSwitch }         from './Switch.vue'
export { default as UiDatePicker }     from './DatePicker.vue'
export { default as UiTimePicker }     from './TimePicker.vue'
export { default as UiDialog }         from './Dialog.vue'
export { default as UiDrawer }         from './Drawer.vue'
export { default as UiPopover }        from './Popover.vue'
export { default as UiTooltip }        from './Tooltip.vue'
export { default as UiDropdownMenu }   from './DropdownMenu.vue'
export { default as UiTabs }           from './Tabs.vue'
export { default as UiAccordion }      from './Accordion.vue'
export { default as UiToast }          from './Toast.vue'
export { default as UiAlert }          from './Alert.vue'
export { default as UiBadge }          from './Badge.vue'
export { default as UiCard }           from './Card.vue'
export { default as UiDataTable }      from './DataTable.vue'
export { default as UiPagination }     from './Pagination.vue'
export { default as UiBreadcrumbs }    from './Breadcrumbs.vue'
export { default as UiAvatar }         from './Avatar.vue'
export { default as UiSpinner }        from './Spinner.vue'
export { default as UiSkeleton }       from './Skeleton.vue'
export { default as UiEmptyState }     from './EmptyState.vue'
export { default as UiStat }           from './Stat.vue'
export { default as UiConfirmDialog }  from './ConfirmDialog.vue'
export { default as UiForm }           from './Form/Form.vue'
export { default as UiField }          from './Form/Field.vue'
export { default as UiFormError }      from './Form/FormError.vue'
export { default as UiFormActions }    from './Form/FormActions.vue'
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/Form resources/js/Components/ui/index.js
git commit -m "feat(ui): add Form/Field/FormError/FormActions + barrel index"
```

### Task 4.10: Build the layout components (Sidebar, TopBar, UserMenu, ColorModeToggle)

**Files:** 4 layout components

- [ ] **Step 1: SidebarItem**

Create `resources/js/Components/layout/SidebarItem.vue`:

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Badge from '@/Components/ui/Badge.vue'

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: null },
  active: { type: Boolean, default: false },
  comingSoon: { type: Boolean, default: false },
})

const classes = computed(() => [
  'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
  props.comingSoon
    ? 'text-muted-foreground cursor-not-allowed'
    : props.active
      ? 'bg-primary-50 text-primary-700 font-medium'
      : 'text-foreground hover:bg-surface-muted',
])
</script>

<template>
  <Link v-if="!comingSoon" :href="href" :class="classes">
    <span v-if="icon" :class="icon" class="w-4 h-4 shrink-0" />
    <span class="flex-1 truncate">{{ label }}</span>
  </Link>
  <span v-else :class="classes">
    <span v-if="icon" :class="icon" class="w-4 h-4 shrink-0" />
    <span class="flex-1 truncate">{{ label }}</span>
    <Badge variant="neutral">Soon</Badge>
  </span>
</template>
```

- [ ] **Step 2: Sidebar**

Create `resources/js/Components/layout/Sidebar.vue`:

```vue
<script setup>
import SidebarItem from './SidebarItem.vue'
import { useNav } from '@/Composables/useNav.js'
import { usePage } from '@inertiajs/vue3'

const items = useNav()
const page = usePage()
const isActive = (href) => page.url === href || (href !== '/dashboard' && page.url.startsWith(href))
</script>

<template>
  <aside class="w-64 bg-surface border-r border-border flex flex-col h-full">
    <div class="px-4 py-4 border-b border-border">
      <h2 class="text-sm font-semibold text-foreground">Navigation</h2>
    </div>
    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
      <SidebarItem
        v-for="item in items"
        :key="item.href + item.label"
        :href="item.href"
        :label="item.label"
        :icon="item.icon ? `i-lucide-${item.icon}` : null"
        :active="isActive(item.href)"
        :coming-soon="!!item.comingSoon"
      />
    </nav>
  </aside>
</template>
```

- [ ] **Step 3: UserMenu**

Create `resources/js/Components/layout/UserMenu.vue`:

```vue
<script setup>
import { ref } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Avatar from '@/Components/ui/Avatar.vue'

const open = ref(false)
const user = usePage().props.auth.user

function toggle() { open.value = !open.value }
function close() { open.value = false }

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="relative">
    <button type="button" class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500" @click="toggle">
      <Avatar :src="user?.avatar_url" :name="user?.name" size="md" />
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-56 bg-surface border border-border rounded-md shadow-md py-1 z-40"
      @click="close"
    >
      <div class="px-3 py-2 border-b border-border">
        <p class="text-sm font-medium text-foreground">{{ user?.name }}</p>
        <p class="text-xs text-muted-foreground truncate">{{ user?.email }}</p>
      </div>
      <Link href="/profile" class="block px-3 py-2 text-sm text-foreground hover:bg-surface-muted">Profile</Link>
      <button type="button" @click="logout" class="w-full text-left px-3 py-2 text-sm text-foreground hover:bg-surface-muted">
        Sign out
      </button>
    </div>
  </div>
</template>
```

- [ ] **Step 4: ColorModeToggle**

Create `resources/js/Components/layout/ColorModeToggle.vue`:

```vue
<script setup>
import { useColorMode } from '@/Composables/useColorMode.js'
const { mode, toggle } = useColorMode()
</script>

<template>
  <button
    type="button"
    @click="toggle"
    class="h-9 w-9 inline-flex items-center justify-center rounded-md text-foreground hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-primary-500"
    :aria-label="mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <span v-if="mode === 'dark'" class="i-lucide-sun w-4 h-4" />
    <span v-else class="i-lucide-moon w-4 h-4" />
  </button>
</template>
```

- [ ] **Step 5: TopBar**

Create `resources/js/Components/layout/TopBar.vue`:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import UserMenu from './UserMenu.vue'
import ColorModeToggle from './ColorModeToggle.vue'

const settings = computed(() => usePage().props.settings)
defineEmits(['toggle-sidebar'])
</script>

<template>
  <header class="h-14 px-4 border-b border-border bg-surface flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="lg:hidden h-9 w-9 inline-flex items-center justify-center rounded-md text-foreground hover:bg-surface-muted"
        @click="$emit('toggle-sidebar')"
        aria-label="Toggle sidebar"
      >
        <span class="i-lucide-menu w-5 h-5" />
      </button>
      <h1 class="text-base font-semibold text-foreground">{{ settings.clinic_name }}</h1>
    </div>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="relative h-9 w-9 inline-flex items-center justify-center rounded-md text-foreground hover:bg-surface-muted"
        aria-label="Notifications"
      >
        <span class="i-lucide-bell w-4 h-4" />
        <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-primary-600" />
      </button>
      <ColorModeToggle />
      <UserMenu />
    </div>
  </header>
</template>
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/layout
git commit -m "feat(layout): add Sidebar, SidebarItem, TopBar, UserMenu, ColorModeToggle"
```

---

## Phase 5 — Settings pages, profile page, user management, error pages

**Goal:** Wire up the remaining authenticated routes — Profile, Settings (Clinic + Working Hours), Admin Users CRUD — and the three error pages (403/404/500). Every form uses the wrapper components built in Phase 4.

### Task 5.1: Create the `Authenticated` layout

**Files:**
- Create: `resources/js/Layouts/Authenticated.vue`

- [ ] **Step 1: Write the layout**

Create `resources/js/Layouts/Authenticated.vue`:

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from '@/Components/layout/Sidebar.vue'
import TopBar from '@/Components/layout/TopBar.vue'
import Toast from '@/Components/ui/Toast.vue'
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue'
import { useColorMode } from '@/Composables/useColorMode.js'
import { useFlashMessages } from '@/Composables/useFlashMessages.js'

useColorMode()
useFlashMessages()

const sidebarOpen = ref(false)
const isLg = ref(true)

onMounted(() => {
  isLg.value = window.innerWidth >= 1024
  window.addEventListener('resize', () => {
    isLg.value = window.innerWidth >= 1024
  })
})
</script>

<template>
  <div class="min-h-screen flex bg-surface-muted text-foreground">
    <!-- Sidebar: always visible on lg+, drawer on smaller -->
    <div v-if="isLg" class="shrink-0">
      <Sidebar />
    </div>
    <Transition
      v-else
      enter-active-class="transition-opacity"
      leave-active-class="transition-opacity"
      enter-from-class="opacity-0" leave-to-class="opacity-0"
    >
      <div v-if="sidebarOpen" class="fixed inset-0 z-40 flex" @click.self="sidebarOpen = false">
        <div class="relative z-50">
          <Sidebar />
        </div>
        <div class="flex-1 bg-black/50" />
      </div>
    </Transition>

    <div class="flex-1 flex flex-col min-w-0">
      <TopBar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>

    <Toast />
    <ConfirmDialog />
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/Authenticated.vue
git commit -m "feat(layout): add Authenticated layout with sidebar drawer + global Toast/ConfirmDialog"
```

### Task 5.2: Create the Profile page + controller + tests

**Files:**
- Create: `app/Http/Controllers/ProfileController.php`
- Create: `app/Http/Requests/Profile/UpdateProfileRequest.php`
- Create: `app/Http/Requests/Profile/UpdatePasswordRequest.php`
- Create: `resources/js/Pages/Profile/Edit.vue`
- Create: `routes/web.php` additions
- Create: `tests/Feature/Profile/UpdateProfileTest.php`
- Create: `tests/Feature/Profile/UpdatePasswordTest.php`

- [ ] **Step 1: Write `ProfileController`**

Create `app/Http/Controllers/ProfileController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user()->only(['id', 'name', 'email', 'phone', 'avatar_path']),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());
        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);
        return back()->with('success', 'Password changed.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->update(['is_active' => false]);
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Account deactivated.');
    }
}
```

- [ ] **Step 2: Write the two FormRequests**

Create `app/Http/Requests/Profile/UpdateProfileRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
```

Create `app/Http/Requests/Profile/UpdatePasswordRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            if (! Hash::check($this->input('current_password'), $this->user()->password)) {
                $v->errors()->add('current_password', 'Current password is incorrect.');
            }
        });
    }
}
```

- [ ] **Step 3: Write the Profile page (using wrapper components)**

Create `resources/js/Pages/Profile/Edit.vue`:

```vue
<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiButton from '@/Components/ui/Button.vue'

const props = defineProps({ user: { type: Object, required: true } })

const profile = useForm({
  name:  props.user.name,
  email: props.user.email,
  phone: props.user.phone,
})
function saveProfile() { profile.patch('/profile') }

const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})
function savePassword() {
  password.patch('/profile/password', { onSuccess: () => password.reset() })
}

function deactivate() {
  if (! confirm('This will deactivate your account. Continue?')) return
  router.delete('/profile')
}
</script>

<template>
  <Head title="Profile" />
  <AuthenticatedLayout>
    <div class="max-w-2xl space-y-6">
      <UiCard title="Profile">
        <UiForm @submit.prevent="saveProfile">
          <div v-if="profile.errors._all" class="text-sm text-danger">{{ profile.errors._all }}</div>
          <UiField label="Name" :error="profile.errors.name">
            <UiInput v-model="profile.name" type="text" required />
          </UiField>
          <UiField label="Email" :error="profile.errors.email">
            <UiInput v-model="profile.email" type="email" required />
          </UiField>
          <UiField label="Phone" :error="profile.errors.phone">
            <UiInput v-model="profile.phone" type="tel" />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="profile.processing">Save profile</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Change password">
        <UiForm @submit.prevent="savePassword">
          <UiField label="Current password" :error="password.errors.current_password">
            <UiInput v-model="password.current_password" type="password" required />
          </UiField>
          <UiField label="New password" :error="password.errors.password">
            <UiInput v-model="password.password" type="password" required />
          </UiField>
          <UiField label="Confirm new password" :error="password.errors.password_confirmation">
            <UiInput v-model="password.password_confirmation" type="password" required />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="password.processing">Update password</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Danger zone">
        <p class="text-sm text-muted-foreground mb-3">Deactivating your account will sign you out and prevent future logins.</p>
        <UiButton variant="danger" @click="deactivate">Deactivate my account</UiButton>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Add the profile routes to `routes/web.php`**

Edit `routes/web.php`, inside the `auth` group, add:

```php
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
```

- [ ] **Step 5: Write the feature tests**

Create `tests/Feature/Profile/UpdateProfileTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(fn () => Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

it('updates the profile', function () {
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/profile', [
        'name'  => 'New Name',
        'email' => 'new@example.com',
        'phone' => '+8801700000000',
    ])->assertRedirect();

    expect($user->fresh())->toMatchArray([
        'name'  => 'New Name',
        'email' => 'new@example.com',
        'phone' => '+8801700000000',
    ]);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com', 'is_active' => true]);
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/profile', [
        'name'  => 'Me',
        'email' => 'taken@example.com',
    ])->assertSessionHasErrors('email');
});
```

Create `tests/Feature/Profile/UpdatePasswordTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(fn () => Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

it('changes the password when the current one is correct', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password'), 'is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/profile/password', [
        'current_password'      => 'old-password',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an incorrect current password', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password'), 'is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/profile/password', [
        'current_password'      => 'WRONG',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('current_password');
});
```

- [ ] **Step 6: Run profile tests**

Run: `docker exec dental_app php artisan test --filter=Profile`
Expected: 3 tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/ProfileController.php app/Http/Requests/Profile resources/js/Pages/Profile routes/web.php tests/Feature/Profile
git commit -m "feat(profile): profile edit page, controllers, FormRequests, tests"
```

### Task 5.3: Settings — Clinic page + Working Hours page

**Files:**
- Create: `app/Http/Controllers/Settings/ClinicSettingsController.php`
- Create: `app/Http/Controllers/Settings/WorkingHoursController.php`
- Create: `app/Http/Requests/Settings/UpdateClinicSettingsRequest.php`
- Create: `app/Http/Requests/Settings/UpdateWorkingHoursRequest.php`
- Create: `resources/js/Pages/Settings/Clinic.vue`
- Create: `resources/js/Pages/Settings/WorkingHours.vue`
- Create: `routes/settings.php`
- Create: `tests/Feature/Settings/UpdateClinicSettingsTest.php`
- Create: `tests/Feature/Settings/UpdateWorkingHoursTest.php`

- [ ] **Step 1: Write the controller**

Create `app/Http/Controllers/Settings/ClinicSettingsController.php`:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateClinicSettingsRequest;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClinicSettingsController extends Controller
{
    public function edit(ClinicSettings $settings): Response
    {
        return Inertia::render('Settings/Clinic', [
            'settings' => $settings->toArray(),
        ]);
    }

    public function update(UpdateClinicSettingsRequest $request, ClinicSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();
        return back()->with('success', 'Clinic settings updated.');
    }
}
```

- [ ] **Step 2: Write the FormRequest**

Create `app/Http/Requests/Settings/UpdateClinicSettingsRequest.php`:

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-settings'); }

    public function rules(): array
    {
        return [
            'clinic_name'             => ['required', 'string', 'max:255'],
            'clinic_tagline'          => ['nullable', 'string', 'max:255'],
            'address_line_1'          => ['nullable', 'string', 'max:255'],
            'address_line_2'          => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:128'],
            'state'                   => ['nullable', 'string', 'max:128'],
            'postal_code'             => ['nullable', 'string', 'max:32'],
            'country'                 => ['required', 'string', 'size:2'],
            'phone'                   => ['nullable', 'string', 'max:32'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'currency_code'           => ['required', 'string', 'size:3'],
            'currency_symbol'         => ['required', 'string', 'max:8'],
            'currency_decimals'       => ['required', 'integer', 'min:0', 'max:4'],
            'currency_position'       => ['required', 'in:before,after'],
            'timezone'                => ['required', 'string', 'timezone'],
            'date_format'             => ['required', 'string'],
            'time_format'             => ['required', 'string'],
            'fiscal_year_start'       => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'default_tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix'          => ['required', 'string', 'max:16'],
            'next_invoice_number'     => ['required', 'integer', 'min:1'],
            'appointment_slot_minutes' => ['required', 'in:15,30,60'],
        ];
    }
}
```

- [ ] **Step 3: Write the Working Hours controller + request**

Create `app/Http/Controllers/Settings/WorkingHoursController.php`:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWorkingHoursRequest;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkingHoursController extends Controller
{
    public function edit(ClinicSettings $settings): Response
    {
        return Inertia::render('Settings/WorkingHours', [
            'working_hours' => json_decode($settings->working_hours_json, true) ?: [],
        ]);
    }

    public function update(UpdateWorkingHoursRequest $request, ClinicSettings $settings): RedirectResponse
    {
        $settings->working_hours_json = json_encode($request->validated('working_hours'));
        $settings->save();
        return back()->with('success', 'Working hours updated.');
    }
}
```

Create `app/Http/Requests/Settings/UpdateWorkingHoursRequest.php`:

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-settings'); }

    public function rules(): array
    {
        return [
            'working_hours'               => ['required', 'array'],
            'working_hours.mon'           => ['array'],
            'working_hours.tue'           => ['array'],
            'working_hours.wed'           => ['array'],
            'working_hours.thu'           => ['array'],
            'working_hours.fri'           => ['array'],
            'working_hours.sat'           => ['array'],
            'working_hours.sun'           => ['array'],
            'working_hours.*.*.start'     => ['required_with:working_hours.*.*.end', 'date_format:H:i'],
            'working_hours.*.*.end'       => ['required_with:working_hours.*.*.start', 'date_format:H:i', 'after:working_hours.*.*.start'],
        ];
    }
}
```

- [ ] **Step 4: Write the Clinic settings page**

Create `resources/js/Pages/Settings/Clinic.vue`:

```vue
<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'

const initial = usePage().props.settings
const form = useForm({
  clinic_name: initial.clinic_name,
  clinic_tagline: initial.clinic_tagline,
  address_line_1: initial.address_line_1,
  address_line_2: initial.address_line_2,
  city: initial.city,
  state: initial.state,
  postal_code: initial.postal_code,
  country: initial.country,
  phone: initial.phone,
  email: initial.email,
  currency_code: initial.currency_code,
  currency_symbol: initial.currency_symbol,
  currency_decimals: initial.currency_decimals,
  currency_position: initial.currency_position,
  timezone: initial.timezone,
  date_format: initial.date_format,
  time_format: initial.time_format,
  fiscal_year_start: initial.fiscal_year_start,
  default_tax_rate: initial.default_tax_rate,
  invoice_prefix: initial.invoice_prefix,
  next_invoice_number: initial.next_invoice_number,
  appointment_slot_minutes: initial.appointment_slot_minutes,
})

function submit() { form.patch('/settings') }
</script>

<template>
  <Head title="Clinic settings" />
  <AuthenticatedLayout>
    <div class="max-w-3xl space-y-6">
      <UiCard title="Clinic information">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-4">
            <UiField label="Clinic name" :error="form.errors.clinic_name" required>
              <UiInput v-model="form.clinic_name" required />
            </UiField>
            <UiField label="Tagline" :error="form.errors.clinic_tagline">
              <UiInput v-model="form.clinic_tagline" />
            </UiField>
            <UiField label="Phone" :error="form.errors.phone">
              <UiInput v-model="form.phone" type="tel" />
            </UiField>
            <UiField label="Email" :error="form.errors.email">
              <UiInput v-model="form.email" type="email" />
            </UiField>
          </div>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Address">
        <UiForm @submit.prevent="submit">
          <UiField label="Address line 1"><UiInput v-model="form.address_line_1" /></UiField>
          <UiField label="Address line 2"><UiInput v-model="form.address_line_2" /></UiField>
          <div class="grid grid-cols-3 gap-4">
            <UiField label="City"><UiInput v-model="form.city" /></UiField>
            <UiField label="State"><UiInput v-model="form.state" /></UiField>
            <UiField label="Postal code"><UiInput v-model="form.postal_code" /></UiField>
          </div>
          <UiField label="Country (ISO 3166-1 alpha-2)" required>
            <UiInput v-model="form.country" maxlength="2" required />
          </UiField>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>

      <UiCard title="Currency">
        <UiForm @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-4">
            <UiField label="Code" required><UiInput v-model="form.currency_code" maxlength="3" required /></UiField>
            <UiField label="Symbol" required><UiInput v-model="form.currency_symbol" required /></UiField>
            <UiField label="Decimals" required>
              <UiSelect v-model="form.currency_decimals" :options="[
                { label: '0', value: 0 }, { label: '2', value: 2 }, { label: '3', value: 3 }, { label: '4', value: 4 },
              ]" />
            </UiField>
            <UiField label="Position" required>
              <UiSelect v-model="form.currency_position" :options="[
                { label: 'Before (৳100)', value: 'before' },
                { label: 'After (100৳)', value: 'after' },
              ]" />
            </UiField>
            <UiField label="Default tax rate (%)" :error="form.errors.default_tax_rate">
              <UiInput v-model="form.default_tax_rate" type="number" step="0.01" min="0" max="100" />
            </UiField>
          </div>
          <div class="flex justify-end">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 5: Write the Working Hours page**

Create `resources/js/Pages/Settings/WorkingHours.vue`:

```vue
<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSwitch from '@/Components/ui/Switch.vue'
import UiInput from '@/Components/ui/Input.vue'

const initial = usePage().props.working_hours
const days = ['mon','tue','wed','thu','fri','sat','sun']
const labels = { mon:'Monday', tue:'Tuesday', wed:'Wednesday', thu:'Thursday', fri:'Friday', sat:'Saturday', sun:'Sunday' }

const form = useForm({
  working_hours: Object.fromEntries(
    days.map((d) => [d, initial[d] ?? []])
  ),
})

function addSlot(day) {
  form.working_hours[day] = [...(form.working_hours[day] || []), { start: '09:00', end: '17:00' }]
}
function removeSlot(day, idx) {
  form.working_hours[day] = form.working_hours[day].filter((_, i) => i !== idx)
}

function submit() { form.patch('/settings/working-hours') }
</script>

<template>
  <Head title="Working hours" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="Working hours">
        <form @submit.prevent="submit" class="space-y-4">
          <div v-for="day in days" :key="day" class="border-b border-border pb-3 last:border-b-0 last:pb-0">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium text-foreground">{{ labels[day] }}</span>
              <UiButton size="sm" variant="secondary" type="button" @click="addSlot(day)">+ Add slot</UiButton>
            </div>
            <div v-if="form.working_hours[day].length === 0" class="text-sm text-muted-foreground">Closed</div>
            <div v-for="(slot, idx) in form.working_hours[day]" :key="idx" class="flex items-center gap-2 mb-2">
              <UiInput v-model="form.working_hours[day][idx].start" type="time" />
              <span class="text-muted-foreground">to</span>
              <UiInput v-model="form.working_hours[day][idx].end" type="time" />
              <UiButton size="sm" variant="ghost" type="button" @click="removeSlot(day, idx)">Remove</UiButton>
            </div>
            <p v-if="form.errors[`working_hours.${day}`]" class="text-sm text-danger mt-1">
              {{ form.errors[`working_hours.${day}`] }}
            </p>
          </div>
          <div class="flex justify-end pt-2">
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </form>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 6: Write `routes/settings.php`**

Create `routes/settings.php`:

```php
<?php

use App\Http\Controllers\Settings\ClinicSettingsController;
use App\Http\Controllers\Settings\WorkingHoursController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:manage-settings'])
    ->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [ClinicSettingsController::class, 'edit'])->name('edit');
        Route::patch('/', [ClinicSettingsController::class, 'update'])->name('update');
        Route::get('/working-hours', [WorkingHoursController::class, 'edit'])->name('hours.edit');
        Route::patch('/working-hours', [WorkingHoursController::class, 'update'])->name('hours.update');
    });
```

- [ ] **Step 7: Wire `routes/settings.php` into `routes/web.php`**

Edit `routes/web.php`, add at the top of the file inside the `require` block:

```php
require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
```

- [ ] **Step 8: Write the feature tests**

Create `tests/Feature/Settings/UpdateClinicSettingsTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
});

it('lets an admin update the clinic settings', function () {
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/settings', [
        'clinic_name' => 'New Clinic',
        'currency_code' => 'USD',
        'currency_symbol' => '$',
        'currency_decimals' => 2,
        'currency_position' => 'before',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'fiscal_year_start' => '01-01',
        'invoice_prefix' => 'INV-',
        'next_invoice_number' => 1,
        'appointment_slot_minutes' => '30',
    ])->assertRedirect();

    expect(settings()->clinic_name)->toBe('New Clinic');
    expect(settings()->currency_code)->toBe('USD');
});

it('forbids a non-admin from updating settings', function () {
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    $user = User::factory()->create(['is_active' => true])->assignRole('doctor');
    $this->actingAs($user);

    $this->patch('/settings', [
        'clinic_name' => 'Hax',
        'currency_code' => 'USD',
        'currency_symbol' => '$',
        'currency_decimals' => 2,
        'currency_position' => 'before',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'fiscal_year_start' => '01-01',
        'invoice_prefix' => 'INV-',
        'next_invoice_number' => 1,
        'appointment_slot_minutes' => '30',
    ])->assertStatus(403);
});
```

Create `tests/Feature/Settings/UpdateWorkingHoursTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
});

it('updates working hours', function () {
    $user = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($user);

    $this->patch('/settings/working-hours', [
        'working_hours' => [
            'mon' => [['start' => '10:00', 'end' => '16:00']],
            'tue' => [],
            'wed' => [],
            'thu' => [],
            'fri' => [],
            'sat' => [],
            'sun' => [],
        ],
    ])->assertRedirect();

    $stored = json_decode(settings()->working_hours_json, true);
    expect($stored['mon'][0]['start'])->toBe('10:00');
    expect($stored['tue'])->toBe([]);
});
```

- [ ] **Step 9: Run settings tests**

Run: `docker exec dental_app php artisan test --filter=Settings`
Expected: 3 tests pass.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/Settings app/Http/Requests/Settings resources/js/Pages/Settings routes/settings.php routes/web.php tests/Feature/Settings
git commit -m "feat(settings): clinic + working hours pages, controllers, FormRequests, tests"
```

---

### Task 5.4: User management — controller, request, repository, pages, tests

**Files:**
- Create: `app/Repositories/Contracts/UserRepository.php`
- Create: `app/Repositories/Eloquent/UserEloquentRepository.php`
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `app/Http/Requests/Admin/StoreUserRequest.php`
- Create: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Create: `app/Http/Resources/UserResource.php`
- Create: `app/Policies/UserPolicy.php`
- Create: `resources/js/Pages/Admin/Users/Index.vue`
- Create: `resources/js/Pages/Admin/Users/Create.vue`
- Create: `resources/js/Pages/Admin/Users/Edit.vue`
- Create: `routes/admin.php`
- Create: 5 test files

- [ ] **Step 1: Write the repository interface**

Create `app/Repositories/Contracts/UserRepository.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): void;
    public function toggleActive(User $user): User;
}
```

- [ ] **Step 2: Write the Eloquent implementation**

Create `app/Repositories/Eloquent/UserEloquentRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserEloquentRepository implements UserRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = User::query()->with('roles');

        if (! empty($filters['q'])) {
            $q->where(function ($w) use ($filters) {
                $w->where('name', 'like', "%{$filters['q']}%")
                  ->orWhere('email', 'like', "%{$filters['q']}%");
            });
        }

        if (! empty($filters['role'])) {
            $q->whereHas('roles', fn ($w) => $w->where('name', $filters['role']));
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        }
        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);
        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }
        return $user->fresh('roles');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);
        return $user;
    }
}
```

- [ ] **Step 3: Bind the interface in `AppServiceProvider`**

Edit `app/Providers/AppServiceProvider.php`, in the `register` method:

```php
$this->app->bind(
    \App\Repositories\Contracts\UserRepository::class,
    \App\Repositories\Eloquent\UserEloquentRepository::class,
);
```

- [ ] **Step 4: Write the UserResource**

Create `app/Http/Resources/UserResource.php`:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'is_active'  => (bool) $this->is_active,
            'roles'      => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Write the controller**

Create `app/Http/Controllers/Admin/UserController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => $this->users->paginate(request()->integer('per_page', 15), request()->only(['q','role'])),
            'roles' => Role::pluck('name'),
            'filters' => request()->only(['q','role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $user->load('roles');
        return Inertia::render('Admin/Users/Edit', [
            'user'  => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'is_active' => $user->is_active,
                'role'      => $user->roles->first()?->name,
            ],
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());
        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete yourself.');
        $this->users->delete($user);
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot deactivate yourself.');
        $this->users->toggleActive($user);
        return back()->with('success', 'User status changed.');
    }
}
```

- [ ] **Step 6: Write the two FormRequests**

Create `app/Http/Requests/Admin/StoreUserRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-users'); }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:32'],
            'password'  => ['required', 'confirmed', Password::defaults()],
            'role'      => ['required', Rule::in(['admin','doctor','receptionist'])],
            'is_active' => ['boolean'],
        ];
    }
}
```

Create `app/Http/Requests/Admin/UpdateUserRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-users'); }

    public function rules(): array
    {
        $userId = $this->route('user')->id;
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone'     => ['nullable', 'string', 'max:32'],
            'password'  => ['nullable', 'confirmed', Password::defaults()],
            'role'      => ['required', Rule::in(['admin','doctor','receptionist'])],
            'is_active' => ['boolean'],
        ];
    }
}
```

- [ ] **Step 7: Write `routes/admin.php`**

Create `routes/admin.php`:

```php
<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:manage-users'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle');
    });
```

Edit `routes/web.php`, add:

```php
require __DIR__ . '/admin.php';
```

- [ ] **Step 8: Write the Vue pages**

Create `resources/js/Pages/Admin/Users/Index.vue`:

```vue
<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import { ref, watch } from 'vue'

const props = defineProps({ users: Object, roles: Array, filters: Object })

const q = ref(props.filters.q ?? '')
const role = ref(props.filters.role ?? null)

let t
function applyFilters() {
  clearTimeout(t)
  t = setTimeout(() => {
    router.get('/admin/users', { q: q.value, role: role.value }, { preserveState: true, replace: true })
  }, 300)
}
watch([q, role], applyFilters)

function toggleActive(user) {
  if (confirm(`Toggle active status for ${user.name}?`)) {
    router.post(`/admin/users/${user.id}/toggle-active`)
  }
}
function destroyUser(user) {
  if (confirm(`Permanently delete ${user.name}?`)) {
    router.delete(`/admin/users/${user.id}`)
  }
}
</script>

<template>
  <Head title="Users" />
  <AuthenticatedLayout>
    <UiCard title="Users">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-foreground">Users</h3>
          <Link href="/admin/users/create"><UiButton>New user</UiButton></Link>
        </div>
      </template>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <UiInput v-model="q" placeholder="Search by name or email" />
        <UiSelect v-model="role" :options="[{ label: 'All roles', value: null }, ...roles.map(r => ({ label: r, value: r }))]" />
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Role</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users.data" :key="u.id" class="border-b border-border last:border-b-0">
              <td class="px-4 py-2 text-foreground">{{ u.name }}</td>
              <td class="px-4 py-2 text-muted-foreground">{{ u.email }}</td>
              <td class="px-4 py-2">
                <UiBadge v-for="r in u.roles" :key="r" variant="primary" class="mr-1">{{ r }}</UiBadge>
              </td>
              <td class="px-4 py-2">
                <UiBadge :variant="u.is_active ? 'success' : 'danger'">{{ u.is_active ? 'Active' : 'Inactive' }}</UiBadge>
              </td>
              <td class="px-4 py-2 text-right">
                <Link :href="`/admin/users/${u.id}/edit`" class="text-primary-600 hover:text-primary-700 mr-3 text-sm">Edit</Link>
                <button v-if="u.id !== $page.props.auth.user.id" @click="toggleActive(u)" class="text-muted-foreground hover:text-foreground mr-3 text-sm">Toggle</button>
                <button v-if="u.id !== $page.props.auth.user.id" @click="destroyUser(u)" class="text-danger hover:text-danger/80 text-sm">Delete</button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="5" class="text-center text-muted-foreground py-6">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="users.last_page > 1" class="mt-3 flex items-center justify-between text-sm text-muted-foreground">
        <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
        <div class="flex gap-1">
          <template v-for="link in users.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-2 py-1 rounded hover:bg-surface-muted" />
          </template>
        </div>
      </div>
    </UiCard>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Admin/Users/Create.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSwitch from '@/Components/ui/Switch.vue'

const props = defineProps({ roles: Array })

const form = useForm({
  name: '', email: '', phone: '',
  password: '', password_confirmation: '',
  role: 'receptionist', is_active: true,
})

function submit() { form.post('/admin/users') }
</script>

<template>
  <Head title="New user" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard title="New user">
        <UiForm @submit.prevent="submit">
          <UiField label="Name" :error="form.errors.name" required><UiInput v-model="form.name" required /></UiField>
          <UiField label="Email" :error="form.errors.email" required><UiInput v-model="form.email" type="email" required /></UiField>
          <UiField label="Phone"><UiInput v-model="form.phone" type="tel" /></UiField>
          <UiField label="Password" :error="form.errors.password" required><UiInput v-model="form.password" type="password" required /></UiField>
          <UiField label="Confirm password" :error="form.errors.password_confirmation" required><UiInput v-model="form.password_confirmation" type="password" required /></UiField>
          <UiField label="Role" :error="form.errors.role" required>
            <UiSelect v-model="form.role" :options="roles.map(r => ({ label: r, value: r }))" />
          </UiField>
          <UiField label="Active">
            <UiSwitch v-model="form.is_active" />
          </UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link href="/admin/users" class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm">Cancel</Link>
            <UiButton type="submit" :loading="form.processing">Create</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Admin/Users/Edit.vue`:

```vue
<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiForm from '@/Components/ui/Form/Form.vue'
import UiField from '@/Components/ui/Form/Field.vue'
import UiInput from '@/Components/ui/Input.vue'
import UiSelect from '@/Components/ui/Select.vue'
import UiButton from '@/Components/ui/Button.vue'
import UiSwitch from '@/Components/ui/Switch.vue'

const props = defineProps({ user: Object, roles: Array })

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone,
  password: '',
  password_confirmation: '',
  role: props.user.role ?? 'receptionist',
  is_active: props.user.is_active,
})

function submit() { form.patch(`/admin/users/${props.user.id}`) }
</script>

<template>
  <Head title="Edit user" />
  <AuthenticatedLayout>
    <div class="max-w-2xl">
      <UiCard :title="`Edit ${user.name}`">
        <UiForm @submit.prevent="submit">
          <UiField label="Name" :error="form.errors.name" required><UiInput v-model="form.name" required /></UiField>
          <UiField label="Email" :error="form.errors.email" required><UiInput v-model="form.email" type="email" required /></UiField>
          <UiField label="Phone"><UiInput v-model="form.phone" type="tel" /></UiField>
          <UiField label="New password" :error="form.errors.password" hint="Leave blank to keep current password">
            <UiInput v-model="form.password" type="password" />
          </UiField>
          <UiField label="Confirm new password" :error="form.errors.password_confirmation">
            <UiInput v-model="form.password_confirmation" type="password" />
          </UiField>
          <UiField label="Role" required>
            <UiSelect v-model="form.role" :options="roles.map(r => ({ label: r, value: r }))" />
          </UiField>
          <UiField label="Active"><UiSwitch v-model="form.is_active" /></UiField>
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <Link href="/admin/users" class="h-10 px-4 inline-flex items-center rounded-md bg-surface-muted text-foreground border border-border text-sm">Cancel</Link>
            <UiButton type="submit" :loading="form.processing">Save</UiButton>
          </div>
        </UiForm>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 9: Write the user-management feature tests**

Create `tests/Feature/Admin/ListUsersTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
});

it('lists users for an admin', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    User::factory()->count(3)->create(['is_active' => true]);
    $this->actingAs($admin);

    $this->get('/admin/users')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));
});

it('forbids a non-admin from listing users', function () {
    $doctor = User::factory()->create(['is_active' => true])->assignRole('doctor');
    $this->actingAs($doctor);
    $this->get('/admin/users')->assertStatus(403);
});
```

Create `tests/Feature/Admin/CreateUserTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('creates a new user with role', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($admin);

    $this->post('/admin/users', [
        'name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        'role' => 'doctor', 'is_active' => true,
    ])->assertRedirect('/admin/users');

    $jane = User::where('email', 'jane@example.com')->first();
    expect($jane)->not->toBeNull();
    expect($jane->hasRole('doctor'))->toBeTrue();
});
```

Create `tests/Feature/Admin/UpdateUserTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
});

it('updates a user', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $target = User::factory()->create(['is_active' => true])->assignRole('receptionist');
    $this->actingAs($admin);

    $this->patch("/admin/users/{$target->id}", [
        'name' => 'Renamed', 'email' => $target->email, 'role' => 'doctor', 'is_active' => true,
    ])->assertRedirect();

    expect($target->fresh()->name)->toBe('Renamed');
    expect($target->fresh()->hasRole('doctor'))->toBeTrue();
});
```

Create `tests/Feature/Admin/DeactivateUserTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('toggles a user active status', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $target = User::factory()->create(['is_active' => true]);
    $this->actingAs($admin);

    $this->post("/admin/users/{$target->id}/toggle-active")->assertRedirect();
    expect($target->fresh()->is_active)->toBeFalse();

    $this->post("/admin/users/{$target->id}/toggle-active")->assertRedirect();
    expect($target->fresh()->is_active)->toBeTrue();
});

it('prevents an admin from deactivating themselves', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($admin);

    $this->post("/admin/users/{$admin->id}/toggle-active")->assertStatus(403);
});
```

Create `tests/Feature/Admin/DeleteUserTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('deletes a user', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $target = User::factory()->create(['is_active' => true]);
    $this->actingAs($admin);

    $this->delete("/admin/users/{$target->id}")->assertRedirect('/admin/users');
    expect(User::find($target->id))->toBeNull();
});

it('prevents self-deletion', function () {
    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $this->actingAs($admin);
    $this->delete("/admin/users/{$admin->id}")->assertStatus(403);
});
```

- [ ] **Step 10: Run the user-management tests**

Run: `docker exec dental_app php artisan test --filter=Admin`
Expected: 7 tests pass.

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/Admin app/Http/Requests/Admin app/Http/Resources/UserResource.php app/Repositories app/Policies resources/js/Pages/Admin routes/admin.php routes/web.php app/Providers tests/Feature/Admin
git commit -m "feat(admin): user management pages, repository, controller, FormRequests, 7 tests"
```

### Task 5.5: Error pages (403, 404, 500)

**Files:**
- Create: `resources/js/Pages/Errors/403.vue`
- Create: `resources/js/Pages/Errors/404.vue`
- Create: `resources/js/Pages/Errors/500.vue`

- [ ] **Step 1: Write all three error pages**

Create `resources/js/Pages/Errors/403.vue`:

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiButton from '@/Components/ui/Button.vue'
</script>

<template>
  <Head title="Forbidden" />
  <AuthenticatedLayout>
    <div class="max-w-md mx-auto text-center py-12">
      <h1 class="text-3xl font-semibold text-foreground mb-2">403 — Forbidden</h1>
      <p class="text-muted-foreground mb-6">You don't have permission to view this page.</p>
      <Link href="/dashboard"><UiButton>Go to dashboard</UiButton></Link>
    </div>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Errors/404.vue`:

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiButton from '@/Components/ui/Button.vue'
</script>

<template>
  <Head title="Not found" />
  <AuthenticatedLayout>
    <div class="max-w-md mx-auto text-center py-12">
      <h1 class="text-3xl font-semibold text-foreground mb-2">404 — Not found</h1>
      <p class="text-muted-foreground mb-6">The page you're looking for doesn't exist.</p>
      <Link href="/dashboard"><UiButton>Go to dashboard</UiButton></Link>
    </div>
  </AuthenticatedLayout>
</template>
```

Create `resources/js/Pages/Errors/500.vue`:

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiButton from '@/Components/ui/Button.vue'
</script>

<template>
  <Head title="Server error" />
  <AuthenticatedLayout>
    <div class="max-w-md mx-auto text-center py-12">
      <h1 class="text-3xl font-semibold text-foreground mb-2">500 — Something went wrong</h1>
      <p class="text-muted-foreground mb-6">An unexpected error occurred. Please try again later.</p>
      <Link href="/dashboard"><UiButton>Go to dashboard</UiButton></Link>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Map 404/500 to Inertia error pages in `bootstrap/app.php`**

In `bootstrap/app.php`, inside `withExceptions`, add:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->respond(function ($response, $exception, $request) {
        if (! $request->expectsJson() && in_array($response->getStatusCode(), [403, 404, 500], true)) {
            return inertia("Errors/{$response->getStatusCode()}")
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        }
        return $response;
    });
})
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Errors bootstrap/app.php
git commit -m "feat(errors): add 403/404/500 Inertia error pages"
```

---

## Phase 6 — Dashboard page with placeholder KPIs

**Goal:** Replace the smoke-test Dashboard with a real page that uses the Authenticated layout, renders 6 placeholder KPI cards, and a welcome panel. Real KPI values come in later sub-projects; for now they read from the page props we pass.

### Task 6.1: Update `DashboardController` to pass placeholder data

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`

- [ ] **Step 1: Replace the controller**

Overwrite `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'todays_appointments' => ['value' => 0, 'label' => 'Today\'s appointments', 'hint' => 'Resets daily'],
                'pending_payments'   => ['value' => 0, 'label' => 'Pending payments', 'hint' => 'Awaiting settlement'],
                'low_stock_items'    => ['value' => 0, 'label' => 'Low stock items', 'hint' => 'Below reorder threshold'],
                'patients_today'     => ['value' => 0, 'label' => 'New patients', 'hint' => 'This month'],
                'month_revenue'      => ['value' => 0, 'label' => 'Revenue (this month)', 'hint' => 'Net of refunds'],
                'outstanding_balance'=> ['value' => 0, 'label' => 'Outstanding balance', 'hint' => 'Across all patients'],
            ],
        ]);
    }
}
```

### Task 6.2: Replace the Dashboard page

**Files:**
- Replace: `resources/js/Pages/Dashboard.vue`

- [ ] **Step 1: Write the Dashboard page**

Overwrite `resources/js/Pages/Dashboard.vue`:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UiStat from '@/Components/ui/Stat.vue'
import UiCard from '@/Components/ui/Card.vue'
import UiBadge from '@/Components/ui/Badge.vue'
import { useCurrentUser } from '@/Composables/useCurrentUser.js'

const props = defineProps({ stats: { type: Object, required: true } })
const { user } = useCurrentUser()
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <div class="max-w-7xl space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Welcome back, {{ user?.name?.split(' ')[0] }}</h1>
          <p class="text-sm text-muted-foreground mt-1">Foundation phase — KPI cards below are placeholders. Real numbers arrive in the upcoming sub-projects.</p>
        </div>
        <UiBadge variant="primary">Foundation</UiBadge>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <UiStat
          v-for="(stat, key) in stats"
          :key="key"
          :label="stat.label"
          :value="stat.value"
          :hint="stat.hint"
        />
      </div>

      <UiCard title="What ships next">
        <ol class="list-decimal list-inside space-y-1 text-sm text-foreground">
          <li><strong>Doctors &amp; Appointments</strong> — doctor module, working hours, patient booking</li>
          <li><strong>Patients &amp; Treatment Records</strong> — patient CRUD and visit history</li>
          <li><strong>Inventory</strong> — products, stock in/out, low-stock alerts</li>
          <li><strong>Billing, Payments &amp; Ledger</strong> — invoices, payments, partial payments, ledger book, expenses</li>
          <li><strong>Money Exchange</strong> — multi-currency cash drawer</li>
          <li><strong>Notifications</strong> — SMS / WhatsApp confirmations and reminders</li>
        </ol>
      </UiCard>
    </div>
  </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Verify the dashboard**

Log in as `admin@clinic.test` / `password`, visit `/dashboard`. Confirm:
- Sidebar shows Dashboard / Appointments / Patients / Billing / Inventory / Exchange (last five with "Soon" badges) / Settings / Users
- TopBar shows clinic name, color-mode toggle, user avatar
- Dashboard shows 6 placeholder stat cards + the "What ships next" card
- Dark-mode toggle works and persists across reloads
- Clicking "Soon" sidebar items is a no-op
- Visit `/settings` and `/admin/users` — both work
- Visit `/profile` and edit name — flash toast appears
- Log out

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/DashboardController.php resources/js/Pages/Dashboard.vue
git commit -m "feat(dashboard): real dashboard with 6 placeholder KPIs and roadmap card"
```

---

## Phase 7 — Quality gates, documentation, ADRs

**Goal:** Wire up Pint, PHPStan, ESLint, Prettier, the Vitest suite, and a `composer quality` script that runs everything. Write the README and the three ADRs.

### Task 7.1: Configure Laravel Pint

**Files:**
- Create: `pint.json`

- [ ] **Step 1: Write the Pint config**

Create `pint.json`:

```json
{
  "preset": "laravel",
  "rules": {
    "declare_strict_types": true,
    "ordered_imports": {
      "sort_algorithm": "alpha"
    },
    "no_unused_imports": true
  }
}
```

- [ ] **Step 2: Run Pint on the codebase**

Run: `docker exec dental_app vendor/bin/pint`
Expected: files formatted; either "No files need formatting" or a list of fixed files.

- [ ] **Step 3: Commit**

```bash
git add pint.json
git commit -m "chore: configure Pint with laravel preset + strict types"
```

### Task 7.2: Configure PHPStan

**Files:**
- Create: `phpstan.neon`

- [ ] **Step 1: Add phpstan to dev dependencies**

Run: `docker exec dental_app composer require --dev larastan/larastan`

- [ ] **Step 2: Write the config**

Create `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 6
    ignoreErrors:
        # spatie packages use dynamic methods we can't easily type
        - '#Call to an undefined method Spatie\\Permission\\Traits\\HasRoles::#'
```

- [ ] **Step 3: Run PHPStan**

Run: `docker exec dental_app vendor/bin/phpstan analyse --memory-limit=512M`
Expected: 0 errors at level 6.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock phpstan.neon
git commit -m "chore: configure PHPStan level 6 with Larastan"
```

### Task 7.3: Configure ESLint + Prettier

**Files:**
- Create: `eslint.config.js`
- Create: `.prettierrc.json`
- Create: `.prettierignore`

- [ ] **Step 1: Write the ESLint config**

Create `eslint.config.js`:

```js
import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import jsdoc from 'eslint-plugin-jsdoc'

export default [
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    files: ['**/*.{js,vue}'],
    languageOptions: {
      ecmaVersion: 2024,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        localStorage: 'readonly',
        navigator: 'readonly',
        confirm: 'readonly',
        alert: 'readonly',
        FormData: 'readonly',
        URL: 'readonly',
        import: 'readonly',
        export: 'readonly',
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },
  {
    files: ['resources/js/types/**/*.js'],
    plugins: { jsdoc },
    rules: {
      'jsdoc/no-undefined-types': 'off',
      'jsdoc/require-jsdoc': 'off',
    },
  },
  {
    ignores: ['public/build/**', 'node_modules/**', 'vendor/**', 'resources/js/test-setup.js'],
  },
]
```

- [ ] **Step 2: Write the Prettier config**

Create `.prettierrc.json`:

```json
{
  "semi": false,
  "singleQuote": true,
  "trailingComma": "all",
  "printWidth": 110,
  "tabWidth": 2
}
```

Create `.prettierignore`:

```
public/build
node_modules
vendor
storage
```

- [ ] **Step 3: Add the lint script to package.json**

In `package.json` under `"scripts"`, ensure:

```json
"lint": "eslint . --ext .js,.vue",
"format": "prettier --write \"resources/**/*.{js,vue,css}\""
```

- [ ] **Step 4: Run ESLint and Prettier**

Run: `npm run lint && npm run format`
Expected: ESLint reports no errors; Prettier rewrites files (commit the rewrites).

- [ ] **Step 5: Commit**

```bash
git add eslint.config.js .prettierrc.json .prettierignore package.json
git commit -m "chore: configure ESLint (flat) + Prettier"
```

### Task 7.4: Add the `composer quality` script

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add the script**

In `composer.json`, under `"scripts"`, add:

```json
"quality": [
  "pint --test",
  "phpstan analyse --memory-limit=512M",
  "pest"
]
```

- [ ] **Step 2: Run the quality script**

Run: `docker exec dental_app composer quality`
Expected: all three steps pass. (Pest runs only the backend tests; JS tests run separately via `npm test`.)

- [ ] **Step 3: Document the combined quality command in the README**

(Updated in Task 7.5.)

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "chore: add composer quality script (pint + phpstan + pest)"
```

### Task 7.5: Rewrite the README

**Files:**
- Replace: `README.md`

- [ ] **Step 1: Write the new README**

Overwrite `README.md`:

```markdown
# Dental Practice Management

A single-clinic dental practice management system.

## Sub-projects

The system is decomposed into 6 sub-projects. Each one ships with its own design doc + implementation plan + tests.

| # | Sub-project | Status |
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
- Inertia.js 2 + Vue 3 (plain JavaScript, no TypeScript)
- PrimeVue 4 (unstyled) themed via Tailwind v4 design tokens
- Pest (backend), Vitest (frontend)

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
#  - pest

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
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: rewrite README with stack, sub-projects, quality gates"
```

### Task 7.6: Write the three ADRs

**Files:**
- Create: `docs/architecture/decisions/0001-primevue-unstyled-tailwind.md`
- Create: `docs/architecture/decisions/0002-spatie-permissions.md`
- Create: `docs/architecture/decisions/0003-decomposition-into-sub-projects.md`

- [ ] **Step 1: ADR 0001**

Create `docs/architecture/decisions/0001-primevue-unstyled-tailwind.md`:

```markdown
# 0001 — PrimeVue unstyled + Tailwind v4 design tokens

**Date:** 2026-08-13
**Status:** Accepted

## Context

We need a Vue 3 component library for an admin-heavy dental practice management app. Three options:

A. PrimeVue 4 with stock themes (Aura / Lara) — fastest to working UI, but its look is opinionated and clashes with our Tailwind utility classes.
B. Reka UI + hand-rolled components — total control, but a 2-week cost before the first sub-project ships.
C. PrimeVue 4 in `unstyled: true` mode with PrimeVue's `--p-*` tokens mapped to our Tailwind v4 `@theme` tokens, and a thin wrapper layer that re-exports each component with Tailwind utility classes injected via the `pt` pass-through.

## Decision

We chose **C**. PrimeVue gives us accessibility, keyboard handling, ripple, and a large component surface (DataTable, Calendar, Dialog, etc.) for free. Our wrapper layer in `resources/js/Components/ui/` owns the visual contract. Every app page imports from `@/Components/ui`, never from `primevue/*`.

## Consequences

- One source of truth for the look-and-feel.
- A wrapper layer must be maintained — when PrimeVue upgrades, we may need to update pass-throughs.
- Tests on wrapper components assert Tailwind classes are present.
- If we ever want to swap PrimeVue for something else, we replace the wrappers; app code is unaffected.
```

- [ ] **Step 2: ADR 0002**

Create `docs/architecture/decisions/0002-spatie-permissions.md`:

```markdown
# 0002 — spatie/laravel-permission for roles & permissions

**Date:** 2026-08-13
**Status:** Accepted

## Context

We need role-based access control for three roles: admin, doctor, receptionist. Each role gets a set of permissions; route middleware enforces them.

Options:
A. Hand-rolled `role` column on `users` + a `User::isAdmin()` style helper.
B. `spatie/laravel-permission` — a battle-tested package with `Role` / `Permission` models, pivot tables, middleware, and `hasRole` / `can` integration with Laravel's authorization.

## Decision

We chose **B**. The package is well-known, has thorough docs, integrates with Laravel's `Gate` and `authorize()` methods out of the box, and saves us from writing our own pivot management.

## Consequences

- One extra dependency.
- Three migrations: `permissions`, `roles`, plus the model_has_* / role_has_* pivots.
- The `manage-settings` and `manage-users` permissions are seeded by the `RoleSeeder`; future sub-projects will extend each role's permission set as their domains land.
- The `active` middleware (sign out deactivated users) is our own; spatie does not handle that.
```

- [ ] **Step 3: ADR 0003**

Create `docs/architecture/decisions/0003-decomposition-into-sub-projects.md`:

```markdown
# 0003 — Decomposition into sub-projects

**Date:** 2026-08-13
**Status:** Accepted

## Context

The dental practice management system contains 9+ independent subsystems (ledger, treatment records, doctors, payments, billing, expenses, money exchange, inventory, appointments, notifications). Building them all in a single design → plan → build cycle would produce a vague wishlist with many unexamined assumptions.

## Decision

We decompose into 6 sub-projects, each with its own design spec and implementation plan:

0. **Foundation** — auth, roles, clinic settings, app shell, UI library
1. Doctors & Appointments
2. Patients & Treatment Records
3. Inventory
4. Billing, Payments & Ledger
5. Money Exchange
6. Notifications (SMS / WhatsApp)

Sub-projects 1–6 each depend only on Foundation. Sub-projects 4 and 5 additionally depend on the data they need from earlier sub-projects (e.g. billing needs patients, money exchange needs billing).

## Consequences

- Each sub-project is small enough to design + plan + build in days, not months.
- The IA, design tokens, and component library are decided once in Foundation and reused.
- The cost is some duplication of design context — every sub-project spec re-states the stack and conventions.
```

- [ ] **Step 4: Commit**

```bash
git add docs/architecture/decisions
git commit -m "docs: add ADRs for stack choices and sub-project decomposition"
```

### Task 7.7: Run the full quality gate end-to-end

**Goal:** Verify that everything works together. This is the "definition of done" smoke test.

- [ ] **Step 1: Run backend quality**

Run: `docker exec dental_app composer quality`
Expected: all three steps (pint, phpstan, pest) pass.

- [ ] **Step 2: Run frontend tests**

Run: `npm test`
Expected: all Vitest tests pass.

- [ ] **Step 3: Run the production build**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Run frontend lint**

Run: `npm run lint`
Expected: 0 errors.

- [ ] **Step 5: Run the full E2E walkthrough**

In a browser, sign in as `admin@clinic.test` / `password`. Verify:
1. Dashboard loads with 6 placeholder stat cards.
2. Sidebar shows Dashboard (active) + 5 "Soon" items + Settings + Users.
3. TopBar shows clinic name, bell, color-mode toggle, avatar dropdown.
4. Visit `/settings` — Clinic page renders, change the clinic name, see it reflected in the topbar instantly.
5. Visit `/settings/working-hours` — Working Hours page renders, add a slot, save, see success toast.
6. Visit `/admin/users` — Users list renders, create a `receptionist` user, log out, log in as them. Confirm "Users" and "Settings" disappear from the sidebar.
7. Visit `/profile`, change your name, see success toast.
8. Click the bell — placeholder count shown.
9. Toggle dark mode — colors flip; refresh — preference persists.
10. Try to visit `/admin/users` as the receptionist — 403 page renders.

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "chore: final Foundation polish" --allow-empty
```

---

## Appendix A — Out-of-scope reminders for later sub-projects

These intentionally land in later sub-projects. **Do not** build them in Foundation:

- Patient, doctor, appointment, treatment, billing, payment, expense, inventory, exchange, notification models and routes
- Any kind of public/patient-facing surface
- SMS / WhatsApp integration
- Reporting / export
- Multi-clinic tenancy
- Mobile app
- Backup / disaster recovery
- Audit report views (the activity log table exists; UI for browsing it lands in a later sub-project)
- Storybook for the component library (a future "design system" sub-project)
- Playwright E2E tests (a future "e2e" sub-project)

## Appendix B — Conventions every later sub-project must follow

- Migration filename: `YYYY_MM_DD_HHMMSS_create_<table>_table.php` (Laravel convention)
- Every table gets `id`, `created_at`, `updated_at`; `deleted_at` only if the model is `SoftDeletes`
- Foreign keys named `<singular>_id`, with `constrained()` and `cascadeOnDelete()` only when the child has no independent meaning
- Money columns: `decimal(18, 4)` for ledger lines, `decimal(18, 2)` for display
- All timestamps in UTC at the DB; convert to clinic timezone in the UI via `useCurrency()` / `formatInClinicTz()`
- New domain tables get a corresponding `activity_log` subject via `LogsActivity` trait on the model
- Vue components import from `@/Components/ui`, never from `primevue/*`
- Forms use `<UiForm>` / `<UiField>` / `<UiInput>` / `<UiButton>` — not raw `<input>`/`<button>`
- Money is formatted with `useCurrency().format(...)` — never with `toFixed` in templates
- Permissions live in `RoleSeeder`; new permissions extend existing role grants, never replace
