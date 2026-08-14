# DaisyUI Frontend Replacement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the PrimeVue-based UI with a DaisyUI-only design system, delivered as 11 independently shippable vertical slices. End state: no PrimeVue in the codebase; `corporate` (light) + `business` (dark) DaisyUI themes; all 40+ pages and 15+ UI components migrated.

**Architecture:** Tailwind v4 + DaisyUI 5 plugin (CSS-first config in `resources/css/app.css`). Hand-built Vue 3 component kit in `resources/js/Components/ui/` styled purely with DaisyUI classes. Stable prop APIs on each new component so pages migrate by search-and-replace on imports + tags, not by refactoring bindings. Vertical slices (foundation → Patients → Doctors → Appointments → Billing → Inventory → Notifications → Reports → Exchange → Settings+Admin → Auth+Errors). Each slice commits behind the same routes; later slices do not break earlier ones.

**Tech Stack:** Laravel 12, Inertia.js 2, Vue 3.5 (`<script setup lang="ts">`), Tailwind v4 (`@tailwindcss/vite`), DaisyUI 5, Vitest 4 + Vue Test Utils 2 + JSDOM 29, ESLint 10, Vite 8. Native `<input type="date">`/`<input type="time">` for date/time pickers. ESLint `no-restricted-imports` to block PrimeVue paths.

**Source spec:** [`docs/superpowers/specs/2026-08-14-daisyui-frontend-replacement-design.md`](../specs/2026-08-14-daisyui-frontend-replacement-design.md)

---

## File Structure

### Files to modify (per slice 0)
- `package.json` — add `daisyui`; remove `primevue`, `@primevue/themes`, `@primeuix/themes`.
- `resources/css/app.css` — Tailwind v4 + DaisyUI config; drop PrimeVue overrides import.
- `resources/js/app.js` — remove `installPrimeVue` plugin registration.
- `resources/css/primevue-overrides.css` — **delete**.
- `eslint.config.js` — add `no-restricted-imports` rule.
- `resources/js/plugins/primevue.js` — **delete** (only consumer is `app.js`).

### Files to create (slice 0)
- `resources/js/Components/ui/Button.vue`
- `resources/js/Components/ui/Input.vue`
- `resources/js/Components/ui/Select.vue`
- `resources/js/Components/ui/Textarea.vue`
- `resources/js/Components/ui/Checkbox.vue`
- `resources/js/Components/ui/Switch.vue`
- `resources/js/Components/ui/Card.vue`
- `resources/js/Components/ui/Table.vue`
- `resources/js/Components/ui/TableColumn.vue`
- `resources/js/Components/ui/Dialog.vue`
- `resources/js/Components/ui/DropdownMenu.vue`
- `resources/js/Components/ui/Tabs.vue`
- `resources/js/Components/ui/Pagination.vue`
- `resources/js/Components/ui/DatePicker.vue`
- `resources/js/Components/ui/TimePicker.vue`
- `resources/js/Components/ui/Spinner.vue`
- `resources/js/Components/ui/EmptyState.vue`
- `resources/js/Components/ui/Breadcrumbs.vue`
- `resources/js/Components/AppShell.vue` (new shared chrome)

### Files to rewrite (slice 0)
- `resources/js/Layouts/Authenticated.vue`
- `resources/js/Layouts/Auth.vue`
- `resources/js/Components/layout/Sidebar.vue`
- `resources/js/Components/layout/SidebarItem.vue`
- `resources/js/Components/layout/TopBar.vue`
- `resources/js/Components/layout/UserMenu.vue`
- `resources/js/Components/layout/ColorModeToggle.vue`
- `resources/js/Components/ToothChartPicker.vue`

### Files to create (tests, slice 0)
- `resources/js/__tests__/Components/ui/Button.spec.ts`
- `resources/js/__tests__/Components/ui/Input.spec.ts`
- `resources/js/__tests__/Components/ui/Select.spec.ts`
- `resources/js/__tests__/Components/ui/Textarea.spec.ts`
- `resources/js/__tests__/Components/ui/Checkbox.spec.ts`
- `resources/js/__tests__/Components/ui/Switch.spec.ts`
- `resources/js/__tests__/Components/ui/Card.spec.ts`
- `resources/js/__tests__/Components/ui/Table.spec.ts`
- `resources/js/__tests__/Components/ui/Dialog.spec.ts`
- `resources/js/__tests__/Components/ui/DropdownMenu.spec.ts`
- `resources/js/__tests__/Components/ui/Tabs.spec.ts`
- `resources/js/__tests__/Components/ui/Pagination.spec.ts`
- `resources/js/__tests__/Components/ui/DatePicker.spec.ts`
- `resources/js/__tests__/Components/ui/TimePicker.spec.ts`
- `resources/js/__tests__/Components/ui/Spinner.spec.ts`
- `resources/js/__tests__/Components/ui/EmptyState.spec.ts`
- `resources/js/__tests__/Components/ui/Breadcrumbs.spec.ts`
- `resources/js/__tests__/Components/layout/AuthenticatedLayout.spec.ts` (snapshot)
- `resources/js/__tests__/Components/layout/TopBar.spec.ts` (snapshot)
- `resources/js/__tests__/Components/layout/UserMenu.spec.ts` (snapshot)

### Files to delete (slice 0 — legacy PrimeVue tests)
- All files under `resources/js/__tests__/Components/ui/` that exist today will be replaced by the new specs above. Each new spec uses a `.spec.ts` filename. The legacy PrimeVue wrapper tests can be deleted; if they share filenames, overwrite.

### Files to migrate (slices 1–10)
Each domain slice replaces PrimeVue imports/tags in the listed pages with the new kit. See the per-slice checklists in the Tasks section below.

---

## Slice 0 — Foundation

### Task 0.1: Install DaisyUI, remove PrimeVue from package.json

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Add DaisyUI 5 dependency**

Edit `package.json` `dependencies` block. Add `"daisyui": "^5.0.0"` to `dependencies`. Remove the three PrimeVue entries (`@primeuix/themes`, `@primevue/themes`, `primevue`) entirely. Final `dependencies` block should list: `@inertiajs/vue3`, `daisyui`, `@vueuse/core`, `date-fns`, `date-fns-tz`, `vue`, `ziggy-js`. Keep all `devDependencies` and `optionalDependencies` unchanged.

- [ ] **Step 2: Install + verify lockfile**

Run: `npm install`
Expected: completes without errors; `package-lock.json` regenerated.

Run: `grep -E '"(primevue|@primevue|@primeuix)"' package-lock.json`
Expected: no matches.

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "build(deps): add daisyUI 5, remove primevue and themes"
```

### Task 0.2: Replace `resources/css/app.css` with Tailwind v4 + DaisyUI config

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Replace the file contents**

Replace the entire file with:

```css
@import 'tailwindcss';
@plugin "daisyui" {
  themes: corporate --default, business --prefersdark;
}
```

Notes:
- `corporate --default` makes `corporate` the default light theme.
- `business --prefersdark` automatically applies `business` when `prefers-color-scheme: dark` is set, and the `theme-controller` swap on `<html data-theme="...">` overrides it.
- Existing `--color-primary-*` tokens, `--color-surface`, radii, and font in the old file are dropped — DaisyUI's `corporate`/`business` themes provide them. If `Instrument Sans` must be retained, load it via `@import url('https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap');` in the same file (DaisyUI themes don't bundle fonts). Bunny CDN respects the project's `.npmrc` and avoids Google Fonts.

- [ ] **Step 2: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(css): switch to daisyUI themes corporate/business, drop primevue overrides"
```

### Task 0.3: Delete `primevue-overrides.css` and `plugins/primevue.js`

**Files:**
- Delete: `resources/css/primevue-overrides.css`
- Delete: `resources/js/plugins/primevue.js`

- [ ] **Step 1: Delete files**

```bash
rm resources/css/primevue-overrides.css resources/js/plugins/primevue.js
```

- [ ] **Step 2: Verify no other references**

Run: `grep -RIn "primevue-overrides\|plugins/primevue" resources/`
Expected: no matches. (If any match: stop and fix the caller before proceeding.)

- [ ] **Step 3: Commit**

```bash
git add -u resources/css/primevue-overrides.css resources/js/plugins/primevue.js
git commit -m "chore: drop primevue overrides css and primevue plugin installer"
```

### Task 0.4: Remove PrimeVue plugin registration from `app.js`

**Files:**
- Modify: `resources/js/app.js`

- [ ] **Step 1: Remove the import and `.use(installPrimeVue)`**

Replace the file with:

```js
import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'

const appName = import.meta.env.VITE_APP_NAME || 'Dental'

createInertiaApp({
  title: (title) => (title ? `${title} · ${appName}` : appName),
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .mount(el)
  },
  progress: { color: '#0ea5e9' },
})
```

- [ ] **Step 2: Verify build still succeeds**

Run: `npm run build`
Expected: succeeds. Pages still import PrimeVue directly today — that's fine, they will be migrated slice-by-slice. Build only verifies the entry chain is valid.

- [ ] **Step 3: Commit**

```bash
git add resources/js/app.js
git commit -m "refactor(app): remove primevue plugin registration"
```

### Task 0.5: ESLint rule blocking PrimeVue imports

**Files:**
- Modify: `eslint.config.js`

- [ ] **Step 1: Add `no-restricted-imports` rule**

Locate the rule block in `eslint.config.js` (currently under `rules: { ... }`). Add this entry:

```js
'no-restricted-imports': ['error', {
  paths: [
    { name: 'primevue', message: 'PrimeVue is removed; use the DaisyUI kit in resources/js/Components/ui/. See docs/superpowers/specs/2026-08-14-daisyui-frontend-replacement-design.md.' },
    { name: 'primevue/inputtext', message: 'Use Input.vue from resources/js/Components/ui/.' },
    { name: 'primevue/textarea', message: 'Use Textarea.vue from resources/js/Components/ui/.' },
    { name: 'primevue/button', message: 'Use Button.vue from resources/js/Components/ui/.' },
    { name: 'primevue/checkbox', message: 'Use Checkbox.vue from resources/js/Components/ui/.' },
    { name: 'primevue/toggleswitch', message: 'Use Switch.vue from resources/js/Components/ui/.' },
    { name: 'primevue/select', message: 'Use Select.vue from resources/js/Components/ui/.' },
    { name: 'primevue/datatable', message: 'Use Table.vue from resources/js/Components/ui/.' },
    { name: 'primevue/column', message: 'Use TableColumn.vue from resources/js/Components/ui/.' },
    { name: 'primevue/paginator', message: 'Use Pagination.vue from resources/js/Components/ui/.' },
    { name: 'primevue/dialog', message: 'Use Dialog.vue from resources/js/Components/ui/.' },
    { name: 'primevue/menu', message: 'Use DropdownMenu.vue from resources/js/Components/ui/.' },
    { name: 'primevue/tabs', message: 'Use Tabs.vue from resources/js/Components/ui/.' },
    { name: 'primevue/tabview', message: 'Use Tabs.vue from resources/js/Components/ui/.' },
    { name: 'primevue/tabpanel', message: 'Use Tabs.vue from resources/js/Components/ui/.' },
    { name: 'primevue/datepicker', message: 'Use DatePicker.vue / TimePicker.vue from resources/js/Components/ui/.' },
    { name: 'primevue/progressspinner', message: 'Use Spinner.vue from resources/js/Components/ui/.' },
    { name: 'primevue/breadcrumb', message: 'Use Breadcrumbs.vue from resources/js/Components/ui/.' },
    { name: 'primevue/card', message: 'Use Card.vue from resources/js/Components/ui/.' },
    { name: '@primevue/themes', message: 'PrimeVue themes are removed.' },
    { name: '@primeuix/themes', message: 'PrimeVue themes are removed.' },
  ],
  patterns: [
    { group: ['primevue/*'], message: 'PrimeVue is removed; use the DaisyUI kit.' },
    { group: ['@primevue/*'], message: 'PrimeVue is removed.' },
    { group: ['@primeuix/*'], message: 'PrimeVue themes are removed.' },
  ],
}],
```

- [ ] **Step 2: Run lint — expect failures in pages/components that still import PrimeVue**

Run: `npm run lint`
Expected: many errors (every page using PrimeVue imports). That's fine — slices 1–10 will fix them.

- [ ] **Step 3: Commit the rule (without fixing all the failures yet)**

```bash
git add eslint.config.js
git commit -m "chore(eslint): forbid primevue imports in resources/js"
```

### Task 0.6: Build the `AppShell.vue` shared chrome component

**Files:**
- Create: `resources/js/Components/AppShell.vue`

- [ ] **Step 1: Write the failing test**

Create `resources/js/__tests__/Components/AppShell.spec.ts`:

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppShell from '@/Components/AppShell.vue'

describe('AppShell', () => {
  it('renders a drawer root with default open state on desktop', () => {
    const wrapper = mount(AppShell, {
      props: { theme: 'corporate' },
      slots: { default: '<p>content</p>' },
    })
    expect(wrapper.find('.drawer').exists()).toBe(true)
    expect(wrapper.find('.drawer-content').exists()).toBe(true)
    expect(wrapper.text()).toContain('content')
  })

  it('accepts a theme prop and reflects it on the root data attribute', () => {
    const wrapper = mount(AppShell, {
      props: { theme: 'business' },
      slots: { default: '' },
    })
    expect(wrapper.attributes('data-theme')).toBe('business')
  })
})
```

- [ ] **Step 2: Run test, expect failure**

Run: `npm test -- AppShell`
Expected: FAIL (component doesn't exist).

- [ ] **Step 3: Implement `AppShell.vue`**

```vue
<script setup lang="ts">
defineProps<{ theme?: 'corporate' | 'business' }>()
</script>

<template>
  <div class="drawer lg:drawer-open min-h-screen bg-base-200" :data-theme="theme ?? 'corporate'">
    <input id="app-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content flex flex-col">
      <slot />
    </div>
    <div class="drawer-side border-r border-base-300">
      <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
      <slot name="sidebar" />
    </div>
  </div>
</template>
```

- [ ] **Step 4: Run test, expect pass**

Run: `npm test -- AppShell`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/AppShell.vue resources/js/__tests__/Components/AppShell.spec.ts
git commit -m "feat(ui): add AppShell drawer+navbar chrome component"
```

### Task 0.7: Rewrite `Authenticated.vue` layout to DaisyUI

**Files:**
- Modify: `resources/js/Layouts/Authenticated.vue`

- [ ] **Step 1: Replace the file**

```vue
<script setup lang="ts">
import AppShell from '@/Components/AppShell.vue'
import Sidebar from '@/Components/layout/Sidebar.vue'
import TopBar from '@/Components/layout/TopBar.vue'
</script>

<template>
  <AppShell theme="corporate">
    <TopBar />
    <main class="flex-1 p-4 lg:p-6">
      <slot />
    </main>
    <template #sidebar>
      <Sidebar />
    </template>
  </AppShell>
</template>
```

- [ ] **Step 2: Add snapshot test**

Create `resources/js/__tests__/Components/layout/AuthenticatedLayout.spec.ts`:

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Authenticated from '@/Layouts/Authenticated.vue'

describe('Authenticated layout', () => {
  it('wraps content in AppShell', () => {
    const wrapper = mount(Authenticated, {
      slots: { default: '<p>page content</p>' },
      global: {
        stubs: {
          AppShell: { template: '<div class="drawer"><slot /><template #sidebar><div class="sidebar-stub" /></template></div>' },
          Sidebar: true,
          TopBar: true,
        },
      },
    })
    expect(wrapper.text()).toContain('page content')
  })
})
```

- [ ] **Step 3: Run test, expect pass (stub-based)**

Run: `npm test -- AuthenticatedLayout`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/Authenticated.vue resources/js/__tests__/Components/layout/AuthenticatedLayout.spec.ts
git commit -m "refactor(layout): rewrite Authenticated layout with daisyUI drawer"
```

### Task 0.8: Rewrite `Auth.vue` layout to DaisyUI

**Files:**
- Modify: `resources/js/Layouts/Auth.vue`

- [ ] **Step 1: Replace the file**

```vue
<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
  document.documentElement.setAttribute('data-theme', 'corporate')
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4" data-theme="corporate">
    <slot />
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/Auth.vue
git commit -m "refactor(layout): rewrite Auth layout with daisyUI base"
```

### Task 0.9: Rewrite `Sidebar.vue` to DaisyUI

**Files:**
- Modify: `resources/js/Components/layout/Sidebar.vue`

- [ ] **Step 1: Replace the file**

Inspect current `Sidebar.vue` first to preserve the navigation items list (links + permissions). Keep the existing data structure; only swap markup. Final markup uses DaisyUI `menu`:

```vue
<script setup lang="ts">
// Preserve existing nav items + permission checks from the previous Sidebar.vue.
// Example shape (replace with actual items from the existing file):
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import SidebarItem from './SidebarItem.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)

const items = [
  { label: 'Dashboard', route: 'dashboard', icon: 'home' },
  { label: 'Patients', route: 'patients.index', icon: 'users', permission: 'view-patients' },
  { label: 'Appointments', route: 'appointments.index', icon: 'calendar', permission: 'view-appointments' },
  // ... keep all items from the existing file
]

const visible = computed(() =>
  items.filter((i) => !i.permission || user.value?.permissions?.includes(i.permission)),
)
</script>

<template>
  <aside class="bg-base-100 min-h-full w-64 p-4">
    <h2 class="text-lg font-semibold mb-4 px-2">Dental</h2>
    <ul class="menu w-full">
      <SidebarItem v-for="item in visible" :key="item.route" :item="item" />
    </ul>
  </aside>
</template>
```

> **Important:** the executor must copy the actual items array and permission checks from the current `Sidebar.vue` rather than using the placeholder shown here. The plan provides the markup pattern, not the data.

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/layout/Sidebar.vue
git commit -m "refactor(sidebar): rewrite with daisyUI menu primitives"
```

### Task 0.10: Rewrite `SidebarItem.vue`, `TopBar.vue`, `UserMenu.vue`, `ColorModeToggle.vue` to DaisyUI

**Files:**
- Modify: `resources/js/Components/layout/SidebarItem.vue`
- Modify: `resources/js/Components/layout/TopBar.vue`
- Modify: `resources/js/Components/layout/UserMenu.vue`
- Modify: `resources/js/Components/layout/ColorModeToggle.vue`

- [ ] **Step 1: Rewrite `SidebarItem.vue`**

Use DaisyUI `menu` item pattern. Active route highlighted via `menu-active`. Preserve existing prop shape (the parent passes an `item` object).

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{ item: { label: string; route: string; icon?: string } }>()
const active = computed(() => route().current(props.item.route + '*'))
</script>

<template>
  <li>
    <Link :href="route(item.route)" :class="['rounded-lg', active ? 'menu-active bg-primary text-primary-content' : '']">
      <span>{{ item.label }}</span>
    </Link>
  </li>
</template>
```

- [ ] **Step 2: Rewrite `TopBar.vue`**

Use DaisyUI `navbar`. Preserve breadcrumbs slot and user menu slot. Include the drawer toggle button visible only on mobile (`lg:hidden`).

```vue
<script setup lang="ts">
import UserMenu from './UserMenu.vue'
</script>

<template>
  <header class="navbar bg-base-100 border-b border-base-300 px-4 sticky top-0 z-30">
    <div class="flex-none lg:hidden">
      <label for="app-drawer" class="btn btn-square btn-ghost" aria-label="open sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </label>
    </div>
    <div class="flex-1">
      <slot name="breadcrumbs" />
    </div>
    <div class="flex-none gap-2">
      <slot name="actions" />
      <UserMenu />
    </div>
  </header>
</template>
```

- [ ] **Step 3: Rewrite `UserMenu.vue`**

Use DaisyUI `dropdown` + `menu`. Preserve existing logout action.

```vue
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const logout = () => router.post(route('logout'))
</script>

<template>
  <div class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
      <div class="bg-neutral text-neutral-content w-9 rounded-full">
        <span class="text-sm">{{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}</span>
      </div>
    </div>
    <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-10 mt-3 w-56 p-2 shadow">
      <li class="menu-title">
        <span>{{ user?.name }}</span>
      </li>
      <li><Link :href="route('profile.edit')">Profile</Link></li>
      <li><button type="button" @click="logout">Logout</button></li>
    </ul>
  </div>
</template>
```

- [ ] **Step 4: Rewrite `ColorModeToggle.vue`**

Use DaisyUI `theme-controller` swap. The component toggles `data-theme` between `corporate` and `business` on `<html>`.

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'

const theme = ref<'corporate' | 'business'>('corporate')

onMounted(() => {
  const stored = (localStorage.getItem('theme') as 'corporate' | 'business' | null) ?? 'corporate'
  theme.value = stored
  document.documentElement.setAttribute('data-theme', stored)
})

const toggle = () => {
  theme.value = theme.value === 'corporate' ? 'business' : 'corporate'
  document.documentElement.setAttribute('data-theme', theme.value)
  localStorage.setItem('theme', theme.value)
}
</script>

<template>
  <button type="button" class="btn btn-ghost btn-circle" :aria-label="`Switch to ${theme === 'corporate' ? 'dark' : 'light'} mode`" @click="toggle">
    <svg v-if="theme === 'corporate'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
  </button>
</template>
```

- [ ] **Step 5: Add snapshot tests for `TopBar` and `UserMenu`**

Create `resources/js/__tests__/Components/layout/TopBar.spec.ts` and `resources/js/__tests__/Components/layout/UserMenu.spec.ts`. Stub `UserMenu` in TopBar test, stub `usePage` in UserMenu test (return a fixed auth user). Assert they render without error.

- [ ] **Step 6: Run new tests**

Run: `npm test -- layout/`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Components/layout resources/js/__tests__/Components/layout
git commit -m "refactor(layout): rewrite sidebar/topbar/user-menu/color-toggle with daisyUI"
```

### Task 0.11: Rewrite `ToothChartPicker.vue` to DaisyUI

**Files:**
- Modify: `resources/js/Components/ToothChartPicker.vue`

- [ ] **Step 1: Inspect current component**

Read the current `ToothChartPicker.vue`. Preserve all logic (which teeth are selected, click handlers, emit shape). Swap tooth buttons to `btn btn-sm` with `btn-active` when selected, `btn-outline` otherwise.

- [ ] **Step 2: Rewrite markup around existing logic**

The component is SVG + button grid; keep SVG paths but restyle each tooth as `<button class="btn btn-sm" :class="selected ? 'btn-primary' : 'btn-outline'">`. Preserve prop and emit signatures exactly.

- [ ] **Step 3: Run app build**

Run: `npm run build`
Expected: succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ToothChartPicker.vue
git commit -m "refactor(components): restyle ToothChartPicker with daisyUI buttons"
```

### Task 0.12: Build `Button.vue`

**Files:**
- Create: `resources/js/Components/ui/Button.vue`
- Create: `resources/js/__tests__/Components/ui/Button.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '@/Components/ui/Button.vue'

describe('Button', () => {
  it('renders a btn with the requested variant', () => {
    const wrapper = mount(Button, { props: { variant: 'primary' }, slots: { default: 'Save' } })
    expect(wrapper.classes()).toContain('btn')
    expect(wrapper.classes()).toContain('btn-primary')
    expect(wrapper.text()).toBe('Save')
  })

  it('emits click', async () => {
    const wrapper = mount(Button, { slots: { default: 'Go' } })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('disables when loading prop is true', () => {
    const wrapper = mount(Button, { props: { loading: true }, slots: { default: 'Save' } })
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.find('.loading.loading-spinner').exists()).toBe(true)
  })
})
```

- [ ] **Step 2: Run test, expect fail**

Run: `npm test -- Button.spec`
Expected: FAIL.

- [ ] **Step 3: Implement**

```vue
<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'ghost' | 'outline' | 'danger' | 'success' | 'neutral'
  size?: 'xs' | 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  loading?: boolean
  disabled?: boolean
}>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  loading: false,
  disabled: false,
})
defineEmits<{ click: [event: MouseEvent] }>()
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="btn"
    :class="[
      variant === 'primary' && 'btn-primary',
      variant === 'ghost' && 'btn-ghost',
      variant === 'outline' && 'btn-outline',
      variant === 'danger' && 'btn-error',
      variant === 'success' && 'btn-success',
      variant === 'neutral' && 'btn-neutral',
      size === 'xs' && 'btn-xs',
      size === 'sm' && 'btn-sm',
      size === 'lg' && 'btn-lg',
      loading && 'btn-disabled',
    ]"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="loading loading-spinner loading-sm"></span>
    <slot />
  </button>
</template>
```

- [ ] **Step 4: Run test, expect pass**

Run: `npm test -- Button.spec`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Button.vue resources/js/__tests__/Components/ui/Button.spec.ts
git commit -m "feat(ui): add Button component with daisyUI variants"
```

### Task 0.13: Build `Input.vue`

**Files:**
- Create: `resources/js/Components/ui/Input.vue`
- Create: `resources/js/__tests__/Components/ui/Input.spec.ts`

- [ ] **Step 1: Write failing test (same prop API as old wrapper)**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '@/Components/ui/Input.vue'

describe('Input', () => {
  it('renders input-bordered with model value', () => {
    const wrapper = mount(Input, { props: { modelValue: 'hello', placeholder: 'name' } })
    const el = wrapper.find('input')
    expect(el.classes()).toContain('input')
    expect(el.classes()).toContain('input-bordered')
    expect((el.element as HTMLInputElement).value).toBe('hello')
    expect(el.attributes('placeholder')).toBe('name')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input)
    const el = wrapper.find('input')
    await el.setValue('x')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['x'])
  })

  it('applies input-error when invalid is true', () => {
    const wrapper = mount(Input, { props: { invalid: true } })
    expect(wrapper.find('input').classes()).toContain('input-error')
  })

  it('renders error helper text when error prop provided', () => {
    const wrapper = mount(Input, { props: { error: 'Required' } })
    expect(wrapper.text()).toContain('Required')
    expect(wrapper.find('.text-error').exists()).toBe(true)
  })
})
```

- [ ] **Step 2: Run, expect fail**

Run: `npm test -- Input.spec`
Expected: FAIL.

- [ ] **Step 3: Implement** (preserve old wrapper's prop API exactly: `modelValue`, `type`, `placeholder`, `disabled`, `readonly`, `invalid`, `autofocus`, `autocomplete`; emits: `update:modelValue`, `blur`, `focus`)

```vue
<script setup lang="ts">
defineProps<{
  modelValue?: string | number
  type?: string
  placeholder?: string
  disabled?: boolean
  readonly?: boolean
  invalid?: boolean
  autofocus?: boolean
  autocomplete?: string | null
  error?: string | null
}>()
defineEmits<{
  'update:modelValue': [value: string | number]
  blur: [event: FocusEvent]
  focus: [event: FocusEvent]
}>()
</script>

<template>
  <div class="form-control w-full">
    <input
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :autofocus="autofocus"
      :autocomplete="autocomplete ?? undefined"
      class="input input-bordered w-full"
      :class="invalid || error ? 'input-error' : ''"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <p v-if="error" class="text-error text-xs mt-1">{{ error }}</p>
  </div>
</template>
```

- [ ] **Step 4: Run, expect pass**

Run: `npm test -- Input.spec`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Input.vue resources/js/__tests__/Components/ui/Input.spec.ts
git commit -m "feat(ui): add Input component preserving legacy prop API"
```

### Task 0.14: Build `Select.vue`, `Textarea.vue`, `Checkbox.vue`, `Switch.vue`

**Files:**
- Create: `resources/js/Components/ui/Select.vue`
- Create: `resources/js/Components/ui/Textarea.vue`
- Create: `resources/js/Components/ui/Checkbox.vue`
- Create: `resources/js/Components/ui/Switch.vue`
- Create: `resources/js/__tests__/Components/ui/Select.spec.ts`
- Create: `resources/js/__tests__/Components/ui/Textarea.spec.ts`
- Create: `resources/js/__tests__/Components/ui/Checkbox.spec.ts`
- Create: `resources/js/__tests__/Components/ui/Switch.spec.ts`

- [ ] **Step 1: Write failing test for `Select.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Select from '@/Components/ui/Select.vue'

describe('Select', () => {
  const options = [
    { label: 'One', value: 1 },
    { label: 'Two', value: 2 },
  ]

  it('renders select-bordered with options', () => {
    const wrapper = mount(Select, { props: { modelValue: 1, options } })
    const el = wrapper.find('select')
    expect(el.classes()).toContain('select')
    expect(el.classes()).toContain('select-bordered')
    expect(wrapper.findAll('option')).toHaveLength(2)
  })

  it('emits update:modelValue on change', async () => {
    const wrapper = mount(Select, { props: { modelValue: 1, options } })
    await wrapper.find('select').setValue('2')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([2])
  })

  it('shows input-error when invalid', () => {
    const wrapper = mount(Select, { props: { modelValue: null, options, invalid: true } })
    expect(wrapper.find('select').classes()).toContain('select-error')
  })
})
```

- [ ] **Step 2: Implement `Select.vue`**

```vue
<script setup lang="ts" generic="T">
defineProps<{
  modelValue: T | null
  options: { label: string; value: T }[]
  placeholder?: string
  disabled?: boolean
  invalid?: boolean
  error?: string | null
}>()
defineEmits<{ 'update:modelValue': [value: T | null] }>()
</script>

<template>
  <div class="form-control w-full">
    <select
      class="select select-bordered w-full"
      :class="invalid || error ? 'select-error' : ''"
      :disabled="disabled"
      :value="modelValue ?? ''"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value === '' ? null : (($event.target as HTMLSelectElement).value as unknown as T))"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option v-for="opt in options" :key="String(opt.value)" :value="opt.value">{{ opt.label }}</option>
    </select>
    <p v-if="error" class="text-error text-xs mt-1">{{ error }}</p>
  </div>
</template>
```

- [ ] **Step 3: Write failing test for `Textarea.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Textarea from '@/Components/ui/Textarea.vue'

describe('Textarea', () => {
  it('renders textarea-bordered and emits update:modelValue', async () => {
    const wrapper = mount(Textarea, { props: { modelValue: '', rows: 4 } })
    const el = wrapper.find('textarea')
    expect(el.classes()).toContain('textarea')
    expect(el.classes()).toContain('textarea-bordered')
    expect(el.attributes('rows')).toBe('4')
    await el.setValue('hello')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['hello'])
  })

  it('shows textarea-error when invalid', () => {
    const wrapper = mount(Textarea, { props: { modelValue: '', invalid: true } })
    expect(wrapper.find('textarea').classes()).toContain('textarea-error')
  })
})
```

- [ ] **Step 4: Implement `Textarea.vue`**

```vue
<script setup lang="ts">
defineProps<{
  modelValue: string
  rows?: number
  placeholder?: string
  disabled?: boolean
  invalid?: boolean
  error?: string | null
}>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <div class="form-control w-full">
    <textarea
      class="textarea textarea-bordered w-full"
      :class="invalid || error ? 'textarea-error' : ''"
      :rows="rows ?? 3"
      :placeholder="placeholder"
      :disabled="disabled"
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    ></textarea>
    <p v-if="error" class="text-error text-xs mt-1">{{ error }}</p>
  </div>
</template>
```

- [ ] **Step 5: Write failing test for `Checkbox.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Checkbox from '@/Components/ui/Checkbox.vue'

describe('Checkbox', () => {
  it('renders checkbox with model and emits update', async () => {
    const wrapper = mount(Checkbox, { props: { modelValue: false } })
    const el = wrapper.find('input')
    expect(el.classes()).toContain('checkbox')
    await el.setValue(true)
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })
})
```

- [ ] **Step 6: Implement `Checkbox.vue`**

```vue
<script setup lang="ts">
defineProps<{ modelValue: boolean; disabled?: boolean; label?: string }>()
defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>

<template>
  <label class="label cursor-pointer gap-2">
    <input
      type="checkbox"
      class="checkbox checkbox-primary"
      :disabled="disabled"
      :checked="modelValue"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span v-if="label" class="label-text">{{ label }}</span>
  </label>
</template>
```

- [ ] **Step 7: Write failing test for `Switch.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Switch from '@/Components/ui/Switch.vue'

describe('Switch', () => {
  it('renders toggle and emits update', async () => {
    const wrapper = mount(Switch, { props: { modelValue: false } })
    const el = wrapper.find('input')
    expect(el.classes()).toContain('toggle')
    expect(el.classes()).toContain('toggle-primary')
    await el.setValue(true)
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })
})
```

- [ ] **Step 8: Implement `Switch.vue`**

```vue
<script setup lang="ts">
defineProps<{ modelValue: boolean; disabled?: boolean }>()
defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>

<template>
  <input
    type="checkbox"
    class="toggle toggle-primary"
    :disabled="disabled"
    :checked="modelValue"
    @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
  />
</template>
```

- [ ] **Step 9: Run all four tests**

Run: `npm test -- 'Components/ui/(Select|Textarea|Checkbox|Switch).spec'`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add resources/js/Components/ui resources/js/__tests__/Components/ui
git commit -m "feat(ui): add Select, Textarea, Checkbox, Switch components"
```

### Task 0.15: Build `Card.vue`

**Files:**
- Create: `resources/js/Components/ui/Card.vue`
- Create: `resources/js/__tests__/Components/ui/Card.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Card from '@/Components/ui/Card.vue'

describe('Card', () => {
  it('renders card with title in header', () => {
    const wrapper = mount(Card, { props: { title: 'Patients' }, slots: { default: 'body' } })
    expect(wrapper.find('.card').exists()).toBe(true)
    expect(wrapper.find('.card-title').text()).toBe('Patients')
    expect(wrapper.text()).toContain('body')
  })

  it('omits header padding when padded=false', () => {
    const wrapper = mount(Card, { props: { padded: false }, slots: { default: 'x' } })
    expect(wrapper.find('.card-body').exists()).toBe(false)
  })

  it('renders footer slot', () => {
    const wrapper = mount(Card, {
      props: { title: 'A' },
      slots: { default: 'b', footer: '<button>Close</button>' },
    })
    expect(wrapper.find('.card-actions').exists()).toBe(true)
    expect(wrapper.text()).toContain('Close')
  })
})
```

- [ ] **Step 2: Implement**

```vue
<script setup lang="ts">
withDefaults(defineProps<{ title?: string | null; padded?: boolean }>(), {
  title: null,
  padded: true,
})
</script>

<template>
  <div class="card bg-base-100 border border-base-300 shadow-sm">
    <div v-if="title || $slots.header" class="card-title px-4 pt-4">
      <slot name="header">
        <h3 class="text-base font-semibold">{{ title }}</h3>
      </slot>
    </div>
    <div :class="padded ? 'card-body pt-2' : ''">
      <slot />
      <div v-if="$slots.footer" class="card-actions justify-end mt-4">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 3: Run, expect pass**

Run: `npm test -- Card.spec`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/Card.vue resources/js/__tests__/Components/ui/Card.spec.ts
git commit -m "feat(ui): add Card component with header/body/footer slots"
```

### Task 0.16: Build `Table.vue` and `TableColumn.vue`

**Files:**
- Create: `resources/js/Components/ui/Table.vue`
- Create: `resources/js/Components/ui/TableColumn.vue`
- Create: `resources/js/__tests__/Components/ui/Table.spec.ts`

The current `DataTable` exposes slots where pages use `<Column field="..." header="...">` children. To keep call sites mechanical, the new `Table.vue` accepts the same child markup as today, but renders each `TableColumn` as a plain `<th>` / `<td>` pair. This is a structural simplification vs. PrimeVue's runtime sorting/paginator — we move sorting into the page (a computed `sorted` ref), and pagination lives in the separate `Pagination.vue` (built in task 0.18).

- [ ] **Step 1: Write failing test for `Table.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Table from '@/Components/ui/Table.vue'
import TableColumn from '@/Components/ui/TableColumn.vue'

describe('Table', () => {
  const rows = [
    { id: 1, name: 'Alice' },
    { id: 2, name: 'Bob' },
  ]

  it('renders rows from value prop and columns from slots', () => {
    const wrapper = mount(Table, {
      props: { value: rows },
      slots: {
        default: `
          <TableColumn field="id" header="ID" />
          <TableColumn field="name" header="Name" />
        `,
      },
      global: { components: { TableColumn } },
    })
    expect(wrapper.find('table').exists()).toBe(true)
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(wrapper.text()).toContain('Alice')
  })

  it('applies table-zebra when striped=true', () => {
    const wrapper = mount(Table, {
      props: { value: rows, striped: true },
      slots: { default: '<TableColumn field="id" header="ID" />' },
      global: { components: { TableColumn } },
    })
    expect(wrapper.find('table').classes()).toContain('table-zebra')
  })

  it('shows Spinner when loading=true', () => {
    const wrapper = mount(Table, {
      props: { value: [], loading: true },
      slots: { default: '' },
    })
    expect(wrapper.find('.loading.loading-spinner').exists()).toBe(true)
  })
})
```

- [ ] **Step 2: Implement `TableColumn.vue`**

The `TableColumn` is rendered as a `<th>` when used inside `Table`'s header slot and as a `<td>` when used inside the body row slot. To keep the API simple, `TableColumn` is a render-only marker; `Table.vue` looks at `column.field` and `column.header` from the slot's props.

```vue
<script setup lang="ts">
defineProps<{
  field: string
  header?: string
  sortable?: boolean
}>()
</script>

<template>
  <!-- Marker component: not rendered directly. Table.vue reads its props via slot props. -->
  <span hidden />
</template>
```

- [ ] **Step 3: Implement `Table.vue`**

```vue
<script setup lang="ts">
import Spinner from './Spinner.vue'
import TableColumn from './TableColumn.vue'

type Row = Record<string, unknown>

const props = withDefaults(defineProps<{
  value: Row[]
  loading?: boolean
  striped?: boolean
  emptyMessage?: string
}>(), {
  loading: false,
  striped: false,
  emptyMessage: 'No records found.',
})

defineEmits<{ 'row-click': [row: Row] }>()

// Resolve columns by scanning the default slot for TableColumn instances.
// Using a render-function approach keeps the component API 1:1 with the old DataTable.
import { useSlots, computed, h } from 'vue'

const slots = useSlots()
const columns = computed(() => {
  const nodes = slots.default?.() ?? []
  return nodes
    .filter((n) => n.type === TableColumn)
    .map((n) => n.props as { field: string; header?: string })
})
</script>

<template>
  <div class="card bg-base-100 border border-base-300 overflow-hidden">
    <div v-if="loading" class="p-8 flex justify-center">
      <Spinner />
    </div>
    <table v-else class="table w-full" :class="striped ? 'table-zebra' : ''">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.field">{{ col.header ?? col.field }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="value.length === 0">
          <td :colspan="columns.length" class="text-center text-base-content/60 py-8">{{ emptyMessage }}</td>
        </tr>
        <tr
          v-for="(row, i) in value"
          :key="i"
          class="hover:bg-base-200 cursor-pointer"
          @click="$emit('row-click', row)"
        >
          <td v-for="col in columns" :key="col.field">{{ row[col.field] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

- [ ] **Step 4: Run, expect pass**

Run: `npm test -- Table.spec`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Table.vue resources/js/Components/ui/TableColumn.vue resources/js/__tests__/Components/ui/Table.spec.ts
git commit -m "feat(ui): add Table + TableColumn, drop datatable runtime in favor of slot introspection"
```

### Task 0.17: Build `Dialog.vue`

**Files:**
- Create: `resources/js/Components/ui/Dialog.vue`
- Create: `resources/js/__tests__/Components/ui/Dialog.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Dialog from '@/Components/ui/Dialog.vue'

describe('Dialog', () => {
  it('renders modal-box when open', () => {
    const wrapper = mount(Dialog, {
      props: { open: true, title: 'Edit' },
      slots: { default: 'body' },
    })
    expect(wrapper.find('dialog[open]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Edit')
    expect(wrapper.text()).toContain('body')
  })

  it('emits update:open=false when close button clicked', async () => {
    const wrapper = mount(Dialog, { props: { open: true }, slots: { default: 'x' } })
    await wrapper.find('button[aria-label="Close"]').trigger('click')
    expect(wrapper.emitted('update:open')?.[0]).toEqual([false])
  })

  it('does not render open attribute when closed', () => {
    const wrapper = mount(Dialog, { props: { open: false }, slots: { default: 'x' } })
    expect(wrapper.find('dialog').attributes('open')).toBeUndefined()
  })
})
```

- [ ] **Step 2: Implement**

```vue
<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'

const props = withDefaults(defineProps<{
  open: boolean
  title?: string
}>(), { title: '' })

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const dialogRef = ref<HTMLDialogElement | null>(null)

watch(() => props.open, (val) => {
  const el = dialogRef.value
  if (!el) return
  if (val && !el.open) el.showModal()
  if (!val && el.open) el.close()
}, { immediate: true })

const close = () => emit('update:open', false)

onUnmounted(() => {
  if (dialogRef.value?.open) dialogRef.value.close()
})
</script>

<template>
  <dialog ref="dialogRef" class="modal" @close="close">
    <div class="modal-box">
      <div class="flex items-start justify-between">
        <h3 class="font-semibold text-lg">{{ title }}</h3>
        <button type="button" aria-label="Close" class="btn btn-ghost btn-sm btn-circle" @click="close">�</button>
      </div>
      <div class="mt-4">
        <slot />
      </div>
      <div v-if="$slots.footer" class="modal-action">
        <slot name="footer" />
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</template>
```

- [ ] **Step 3: Run, expect pass**

Run: `npm test -- Dialog.spec`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/Dialog.vue resources/js/__tests__/Components/ui/Dialog.spec.ts
git commit -m "feat(ui): add Dialog using native <dialog> + showModal()"
```

### Task 0.18: Build `Pagination.vue`

**Files:**
- Create: `resources/js/Components/ui/Pagination.vue`
- Create: `resources/js/__tests__/Components/ui/Pagination.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Pagination from '@/Components/ui/Pagination.vue'

describe('Pagination', () => {
  it('emits page event with new page number', async () => {
    const wrapper = mount(Pagination, {
      props: { page: 1, perPage: 10, total: 100 },
    })
    const buttons = wrapper.findAll('button.join-item')
    // 10 pages total → buttons rendered include page numbers and prev/next
    await buttons[3].trigger('click') // page 3
    expect(wrapper.emitted('page')?.[0]).toEqual([3])
  })

  it('disables previous button on page 1', () => {
    const wrapper = mount(Pagination, { props: { page: 1, perPage: 10, total: 100 } })
    const prev = wrapper.find('button[aria-label="Previous page"]')
    expect(prev?.attributes('disabled')).toBeDefined()
  })
})
```

- [ ] **Step 2: Implement**

```vue
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  page: number
  perPage: number
  total: number
}>()

const emit = defineEmits<{ page: [page: number] }>()

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
const pages = computed(() => {
  const arr: (number | '…')[] = []
  const last = lastPage.value
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || Math.abs(i - props.page) <= 1) arr.push(i)
    else if (arr[arr.length - 1] !== '…') arr.push('…')
  }
  return arr
})

const go = (n: number) => {
  if (n >= 1 && n <= lastPage.value && n !== props.page) emit('page', n)
}
</script>

<template>
  <div class="join">
    <button
      type="button"
      aria-label="Previous page"
      class="join-item btn btn-sm"
      :disabled="page <= 1"
      @click="go(page - 1)"
    >«</button>
    <template v-for="(p, i) in pages" :key="i">
      <span v-if="p === '…'" class="join-item btn btn-sm btn-disabled">…</span>
      <button
        v-else
        type="button"
        class="join-item btn btn-sm"
        :class="p === page ? 'btn-primary' : ''"
        @click="go(p)"
      >{{ p }}</button>
    </template>
    <button
      type="button"
      aria-label="Next page"
      class="join-item btn btn-sm"
      :disabled="page >= lastPage"
      @click="go(page + 1)"
    >»</button>
  </div>
</template>
```

- [ ] **Step 3: Run, expect pass**

Run: `npm test -- Pagination.spec`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/Pagination.vue resources/js/__tests__/Components/ui/Pagination.spec.ts
git commit -m "feat(ui): add Pagination with DaisyUI join+btn"
```

### Task 0.19: Build `Tabs.vue`

**Files:**
- Create: `resources/js/Components/ui/Tabs.vue`
- Create: `resources/js/__tests__/Components/ui/Tabs.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Tabs from '@/Components/ui/Tabs.vue'

describe('Tabs', () => {
  it('renders one tab per item and shows active panel', () => {
    const wrapper = mount(Tabs, {
      props: {
        modelValue: 0,
        tabs: [
          { label: 'A', key: 'a' },
          { label: 'B', key: 'b' },
        ],
      },
      slots: { a: 'panel-a', b: 'panel-b' },
    })
    const buttons = wrapper.findAll('[role="tab"]')
    expect(buttons).toHaveLength(2)
    expect(wrapper.text()).toContain('panel-a')
    expect(wrapper.text()).not.toContain('panel-b')
  })

  it('emits update:modelValue when tab clicked', async () => {
    const wrapper = mount(Tabs, {
      props: {
        modelValue: 0,
        tabs: [{ label: 'A', key: 'a' }, { label: 'B', key: 'b' }],
      },
      slots: { a: '', b: '' },
    })
    await wrapper.findAll('[role="tab"]')[1].trigger('click')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([1])
  })
})
```

- [ ] **Step 2: Implement**

```vue
<script setup lang="ts">
const props = defineProps<{
  modelValue: number
  tabs: { label: string; key: string }[]
}>()
const emit = defineEmits<{ 'update:modelValue': [value: number] }>()
const select = (i: number) => emit('update:modelValue', i)
</script>

<template>
  <div>
    <div role="tablist" class="tabs tabs-bordered">
      <button
        v-for="(t, i) in tabs"
        :key="t.key"
        role="tab"
        type="button"
        class="tab"
        :class="modelValue === i ? 'tab-active' : ''"
        @click="select(i)"
      >{{ t.label }}</button>
    </div>
    <div v-for="(t, i) in tabs" :key="t.key" v-show="modelValue === i" role="tabpanel" class="pt-4">
      <slot :name="t.key" />
    </div>
  </div>
</template>
```

- [ ] **Step 3: Run, expect pass**

Run: `npm test -- Tabs.spec`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/Tabs.vue resources/js/__tests__/Components/ui/Tabs.spec.ts
git commit -m "feat(ui): add Tabs component with named-slot panels"
```

### Task 0.20: Build `DropdownMenu.vue`

**Files:**
- Create: `resources/js/Components/ui/DropdownMenu.vue`
- Create: `resources/js/__tests__/Components/ui/DropdownMenu.spec.ts`

- [ ] **Step 1: Write failing test**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DropdownMenu from '@/Components/ui/DropdownMenu.vue'

describe('DropdownMenu', () => {
  it('renders trigger slot and items list', () => {
    const wrapper = mount(DropdownMenu, {
      props: {
        items: [
          { label: 'Edit', value: 'edit' },
          { label: 'Delete', value: 'delete' },
        ],
      },
      slots: { trigger: '<button>Open</button>' },
    })
    expect(wrapper.find('button').text()).toBe('Open')
    expect(wrapper.text()).toContain('Edit')
    expect(wrapper.text()).toContain('Delete')
  })

  it('emits select with item value when clicked', async () => {
    const wrapper = mount(DropdownMenu, {
      props: { items: [{ label: 'Edit', value: 'edit' }] },
      slots: { trigger: '<button>Open</button>' },
    })
    await wrapper.findAll('button')[1].trigger('click')
    expect(wrapper.emitted('select')?.[0]).toEqual(['edit'])
  })
})
```

- [ ] **Step 2: Implement**

```vue
<script setup lang="ts">
defineProps<{ items: { label: string; value: string }[] }>()
const emit = defineEmits<{ select: [value: string] }>()
</script>

<template>
  <div class="dropdown dropdown-end">
    <div tabindex="0" role="button">
      <slot name="trigger" />
    </div>
    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-52 p-2 shadow">
      <li v-for="item in items" :key="item.value">
        <button type="button" @click="emit('select', item.value)">{{ item.label }}</button>
      </li>
    </ul>
  </div>
</template>
```

- [ ] **Step 3: Run, expect pass**

Run: `npm test -- DropdownMenu.spec`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/DropdownMenu.vue resources/js/__tests__/Components/ui/DropdownMenu.spec.ts
git commit -m "feat(ui): add DropdownMenu component"
```

### Task 0.21: Build `DatePicker.vue` and `TimePicker.vue`

**Files:**
- Create: `resources/js/Components/ui/DatePicker.vue`
- Create: `resources/js/Components/ui/TimePicker.vue`
- Create: `resources/js/__tests__/Components/ui/DatePicker.spec.ts`
- Create: `resources/js/__tests__/Components/ui/TimePicker.spec.ts`

- [ ] **Step 1: Write failing test for `DatePicker.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DatePicker from '@/Components/ui/DatePicker.vue'

describe('DatePicker', () => {
  it('renders an input type=date with modelValue', () => {
    const wrapper = mount(DatePicker, { props: { modelValue: '2026-01-15' } })
    const el = wrapper.find('input')
    expect(el.attributes('type')).toBe('date')
    expect((el.element as HTMLInputElement).value).toBe('2026-01-15')
  })

  it('emits update:modelValue on change', async () => {
    const wrapper = mount(DatePicker, { props: { modelValue: '' } })
    await wrapper.find('input').setValue('2026-02-01')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['2026-02-01'])
  })
})
```

- [ ] **Step 2: Implement `DatePicker.vue`**

```vue
<script setup lang="ts">
defineProps<{ modelValue: string; min?: string; max?: string; disabled?: boolean; invalid?: boolean }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <input
    type="date"
    class="input input-bordered w-full"
    :class="invalid ? 'input-error' : ''"
    :value="modelValue"
    :min="min"
    :max="max"
    :disabled="disabled"
    @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
  />
</template>
```

- [ ] **Step 3: Write failing test for `TimePicker.vue`**

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TimePicker from '@/Components/ui/TimePicker.vue'

describe('TimePicker', () => {
  it('renders input type=time', () => {
    const wrapper = mount(TimePicker, { props: { modelValue: '09:30' } })
    const el = wrapper.find('input')
    expect(el.attributes('type')).toBe('time')
    expect((el.element as HTMLInputElement).value).toBe('09:30')
  })
})
```

- [ ] **Step 4: Implement `TimePicker.vue`**

```vue
<script setup lang="ts">
defineProps<{ modelValue: string; step?: number; disabled?: boolean; invalid?: boolean }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <input
    type="time"
    class="input input-bordered w-full"
    :class="invalid ? 'input-error' : ''"
    :value="modelValue"
    :step="step ?? 60"
    :disabled="disabled"
    @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
  />
</template>
```

- [ ] **Step 5: Run, expect pass**

Run: `npm test -- '(DatePicker|TimePicker).spec'`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/DatePicker.vue resources/js/Components/ui/TimePicker.vue resources/js/__tests__/Components/ui/DatePicker.spec.ts resources/js/__tests__/Components/ui/TimePicker.spec.ts
git commit -m "feat(ui): add native DatePicker and TimePicker styled with daisyUI"
```

### Task 0.22: Build `Spinner.vue`, `EmptyState.vue`, `Breadcrumbs.vue`

**Files:**
- Create: `resources/js/Components/ui/Spinner.vue`
- Create: `resources/js/Components/ui/EmptyState.vue`
- Create: `resources/js/Components/ui/Breadcrumbs.vue`
- Create: `resources/js/__tests__/Components/ui/Spinner.spec.ts`
- Create: `resources/js/__tests__/Components/ui/EmptyState.spec.ts`
- Create: `resources/js/__tests__/Components/ui/Breadcrumbs.spec.ts`

- [ ] **Step 1: Implement `Spinner.vue`** (no test required — trivial)

```vue
<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })
</script>

<template>
  <span
    class="loading loading-spinner"
    :class="size === 'sm' ? 'loading-sm' : size === 'lg' ? 'loading-lg' : 'loading-md'"
  ></span>
</template>
```

- [ ] **Step 2: Test + implement `EmptyState.vue`**

```ts
// resources/js/__tests__/Components/ui/EmptyState.spec.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import EmptyState from '@/Components/ui/EmptyState.vue'

describe('EmptyState', () => {
  it('renders title and description', () => {
    const wrapper = mount(EmptyState, { props: { title: 'Nothing', description: 'Add a patient to begin.' } })
    expect(wrapper.text()).toContain('Nothing')
    expect(wrapper.text()).toContain('Add a patient to begin.')
  })
})
```

```vue
<!-- resources/js/Components/ui/EmptyState.vue -->
<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="hero py-12">
    <div class="hero-content text-center">
      <div class="max-w-md">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <p v-if="description" class="py-2 text-base-content/60">{{ description }}</p>
        <slot />
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 3: Test + implement `Breadcrumbs.vue`**

```ts
// resources/js/__tests__/Components/ui/Breadcrumbs.spec.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { Link } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

describe('Breadcrumbs', () => {
  it('renders one crumb per item with the last as non-link', () => {
    const wrapper = mount(Breadcrumbs, {
      props: {
        items: [
          { label: 'Patients', href: '/patients' },
          { label: 'Show', href: null },
        ],
      },
      global: { components: { Link } },
    })
    expect(wrapper.find('.breadcrumbs').exists()).toBe(true)
    expect(wrapper.text()).toContain('Patients')
    expect(wrapper.text()).toContain('Show')
  })
})
```

```vue
<!-- resources/js/Components/ui/Breadcrumbs.vue -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
defineProps<{ items: { label: string; href: string | null }[] }>()
</script>

<template>
  <div class="breadcrumbs text-sm">
    <ul>
      <li v-for="(item, i) in items" :key="i">
        <Link v-if="item.href" :href="item.href">{{ item.label }}</Link>
        <span v-else>{{ item.label }}</span>
      </li>
    </ul>
  </div>
</template>
```

- [ ] **Step 4: Run tests**

Run: `npm test -- '(Spinner|EmptyState|Breadcrumbs).spec'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Spinner.vue resources/js/Components/ui/EmptyState.vue resources/js/Components/ui/Breadcrumbs.vue resources/js/__tests__/Components/ui/Spinner.spec.ts resources/js/__tests__/Components/ui/EmptyState.spec.ts resources/js/__tests__/Components/ui/Breadcrumbs.spec.ts
git commit -m "feat(ui): add Spinner, EmptyState, Breadcrumbs components"
```

### Task 0.23: Delete legacy PrimeVue wrapper tests

**Files:**
- Delete all files under `resources/js/__tests__/Components/ui/` whose names start with the old set (e.g. `Card.spec.js`, `Input.spec.js`, `DataTable.spec.js`, etc.) that don't have new equivalents above. The new specs above use the same component names but as `.spec.ts` files, so old `.spec.js` files are dead.

- [ ] **Step 1: List and delete**

```bash
ls resources/js/__tests__/Components/ui/
# Delete any file whose name is not in the new list above.
```

- [ ] **Step 2: Verify**

Run: `npm test`
Expected: all new specs pass, no references to deleted files.

- [ ] **Step 3: Commit**

```bash
git add -u resources/js/__tests__/Components/ui
git commit -m "test(ui): replace primevue wrapper specs with daisyUI specs"
```

### Task 0.24: Slice 0 gate

- [ ] **Step 1: Run the full build, test, lint**

```bash
npm run build
npm test
npm run lint
```

Expected:
- `npm run build`: succeeds.
- `npm test`: all new component specs pass.
- `npm run lint`: ESLint errors remain for pages still importing PrimeVue. That's expected; slices 1–10 will resolve them. Do NOT add an `--fix` here.

- [ ] **Step 2: Manual smoke**

Start the dev server:
```bash
npm run dev
```
And in another shell (or via Laravel Herd / `php artisan serve`):
- Visit `/login` → verify the page renders with DaisyUI styling (login form on `bg-base-200` centered card). PrimeVue form components may still appear inside the form — that's slice 10's job.
- Visit `/dashboard` → verify DaisyUI navbar, sidebar (drawer on mobile), user menu dropdown, color-mode toggle. Pages still use legacy components but the chrome is DaisyUI.
- Toggle color mode → theme swaps between `corporate` (light) and `business` (dark).
- Resize to mobile → drawer toggles via the hamburger button.

- [ ] **Step 3: Tag the slice**

```bash
git tag slice-0-foundation
```

---

## Slice 1 — Patients

### Task 1.1: Migrate `Patients/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Patients/Index.vue`

- [ ] **Step 1: Replace PrimeVue imports with new kit**

In `<script setup>`, replace any `import ... from 'primevue/...'` lines with imports from `@/Components/ui/...`:

```ts
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import Table from '@/Components/ui/Table.vue'
import TableColumn from '@/Components/ui/TableColumn.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import Input from '@/Components/ui/Input.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import Dialog from '@/Components/ui/Dialog.vue'
```

(Adjust to whatever components the page actually uses. Read the file first.)

- [ ] **Step 2: Replace PrimeVue tags in template**

Search-and-replace:
- `<DataTable` → `<Table`
- `</DataTable>` → `</Table>`
- `<Column` → `<TableColumn`
- `</Column>` → `</TableColumn>`
- `<Paginator` → `<Pagination` (and `:rows` → `:perPage`, `:total-records` → `:total`)
- `<InputText` → `<Input`
- `<Button` → `<Button`
- `<Dialog` → `<Dialog` (no rename; bind `v-model:open` instead of `v-model:visible`)
- `<EmptyState` ... actually custom — replace with the new `EmptyState` from the kit

Keep all `<script setup>` logic untouched.

- [ ] **Step 3: Handle pagination prop renames**

PrimeVue `<Paginator :rows="10" :total-records="100" :first="(page - 1) * 10" @page="(e) => go(e.page + 1)" />` becomes:

```vue
<Pagination :page="page" :per-page="perPage" :total="total" @page="go" />
```

The handler signature changes from `(e) => page + 1` to `(n) => n` (the new component emits the absolute page number).

- [ ] **Step 4: Verify no PrimeVue imports remain**

Run: `grep -n "primevue" resources/js/Pages/Patients/Index.vue`
Expected: no matches.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Patients/Index.vue
git commit -m "refactor(patients): migrate Index page to daisyUI kit"
```

### Task 1.2: Migrate `Patients/Create.vue` and `Patients/Edit.vue`

- [ ] **Steps 1–5:** Same pattern as 1.1. Replace PrimeVue imports/tags in both files. Commit each file in its own commit (`Patients/Create`, `Patients/Edit`).

### Task 1.3: Migrate `Patients/Show.vue`

- [ ] **Steps 1–5:** Same pattern. Note: `Show` typically uses Tabs and Card. Wire `<Tabs :tabs="[{ label: 'Info', key: 'info' }, ...]" v-model="activeTab">` with named slots `info`, etc.

### Task 1.4: Migrate `PatientQuickAddDialog.vue`

- [ ] **Steps 1–5:** Same pattern. The component is a Dialog wrapping a small form. Replace `<Dialog>` from PrimeVue with `@/Components/ui/Dialog.vue`; replace form inputs with the new Input/Select kit.

### Task 1.5: Add Vitest smoke test for `PatientQuickAddDialog`

- [ ] **Step 1: Write test**

Create `resources/js/__tests__/Components/PatientQuickAddDialog.spec.ts`:

```ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PatientQuickAddDialog from '@/Components/PatientQuickAddDialog.vue'

describe('PatientQuickAddDialog', () => {
  it('does not render PrimeVue in its tree', () => {
    const wrapper = mount(PatientQuickAddDialog, {
      props: { open: true },
      global: {
        stubs: {
          Dialog: { template: '<div class="modal-box"><slot /></div>' },
        },
      },
    })
    expect(wrapper.html()).not.toMatch(/p-button|p-datatable|p-dialog/)
  })
})
```

- [ ] **Step 2: Run, expect pass**

Run: `npm test -- PatientQuickAddDialog`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/js/__tests__/Components/PatientQuickAddDialog.spec.ts
git commit -m "test(patients): assert no primevue markup in PatientQuickAddDialog"
```

### Task 1.6: Slice 1 gate

- [ ] **Step 1: Lint clean for the migrated files**

Run: `npx eslint resources/js/Pages/Patients resources/js/Components/PatientQuickAddDialog.vue`
Expected: clean.

- [ ] **Step 2: Manual smoke**

```bash
npm run dev
```
- `/patients` → list renders, pagination works, search input filters rows, "Add patient" opens the dialog.
- `/patients/create` → form submits, validation errors render under fields with `input-error` styling.
- `/patients/{id}` → tabs work, edit/delete actions fire.
- `/patients/{id}/edit` → form pre-fills, save updates.
- Quick-add from another page (e.g. open a page that has a "Quick add patient" action) → dialog renders, submits, closes.

- [ ] **Step 3: Tag**

```bash
git tag slice-1-patients
```

---

## Slice 2 — Doctors

Migrate [resources/js/Pages/Doctors/](resources/js/Pages/Doctors/): `Index`, `Create`, `Edit`, `Show`. Same mechanical pattern as slice 1 (search-and-replace PrimeVue tags to new kit; keep logic untouched).

- [ ] **Migrate `Index.vue`** → commit.
- [ ] **Migrate `Create.vue`** → commit.
- [ ] **Migrate `Edit.vue`** → commit.
- [ ] **Migrate `Show.vue`** → commit.
- [ ] **Add `Doctors/Index.spec.ts`** smoke test asserting no `p-*` PrimeVue classes in rendered output → commit.
- [ ] **Slice gate:** lint, manual smoke (list, create, edit, show, delete), tag `slice-2-doctors`.

---

## Slice 3 — Appointments

Migrate [resources/js/Pages/Appointments/](resources/js/Pages/Appointments/): `Index`, `Create`, `Edit`, `Show`, `Calendar`. The first four follow the standard pattern. `Calendar.vue` is non-trivial — see below.

- [ ] **Migrate `Index.vue`** → commit.
- [ ] **Migrate `Create.vue`** → commit (exercises `DatePicker`, `TimePicker`, `Select`).
- [ ] **Migrate `Edit.vue`** → commit.
- [ ] **Migrate `Show.vue`** → commit.
- [ ] **Migrate `Calendar.vue`** (see below) → commit.

### Task 3.5 details: Calendar page

The current Calendar page is the most complex page in the app. After migration, it becomes a DaisyUI `table` with one row per time slot and one column per provider/room, with appointments as colored `<button class="btn btn-sm btn-primary">` cells.

- [ ] **Step 1: Read the current Calendar page** to understand its data shape (slots, appointments, view modes).

- [ ] **Step 2: Replace imports** with kit components plus any required composables (e.g. `usePage`, `Link`).

- [ ] **Step 3: Replace markup.** Keep all `<script setup>` (composables, computed slots, form handlers). Calendar grid markup:

```vue
<div class="overflow-x-auto">
  <table class="table table-pin-rows table-pin-cols table-sm">
    <thead>
      <tr>
        <th>Time</th>
        <th v-for="col in columns" :key="col.id">{{ col.label }}</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="slot in slots" :key="slot.time">
        <th>{{ slot.time }}</th>
        <td v-for="col in columns" :key="col.id">
          <button
            v-for="appt in appointmentsAt(slot.time, col.id)"
            :key="appt.id"
            class="btn btn-xs btn-primary w-full"
            @click="openAppointment(appt)"
          >{{ appt.patient.name }}</button>
          <button
            v-if="canBook(slot.time, col.id)"
            class="btn btn-xs btn-ghost w-full"
            @click="quickBook(slot.time, col.id)"
          >+</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

- [ ] **Step 4: Verify no PrimeVue imports remain.**

- [ ] **Step 5: Commit.**

- [ ] **Add `Appointments/Calendar.spec.ts`** smoke test → commit.
- [ ] **Slice gate:** lint, manual smoke (week/day views, drag-to-reschedule if present, create appointment at slot, edit existing), tag `slice-3-appointments`.

---

## Slice 4 — Billing

Migrate [resources/js/Pages/Billing/](resources/js/Pages/Billing/): `Invoices/Index`, `Payments/Index`, `Accounts/Index`, `Expenses/Index` (and any Create/Edit/Show companions).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs** for each index page asserting no PrimeVue in rendered DOM.
- [ ] **Slice gate:** lint, manual smoke (heavy Table + Pagination + Tabs exercise — verify invoice tabs, payment recording, expense entry), tag `slice-4-billing`.

---

## Slice 5 — Inventory

Migrate [resources/js/Pages/Inventory/](resources/js/Pages/Inventory/): `Suppliers/Index`, `Locations/Index`, `Products/Index` (plus Create/Edit/Show).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs** per page.
- [ ] **Slice gate:** lint, manual smoke, tag `slice-5-inventory`.

---

## Slice 6 — Notifications

Migrate [resources/js/Pages/Notifications/](resources/js/Pages/Notifications/): `Templates/Index`, `Send/Index`, `Logs/Index` (plus detail pages).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs.**
- [ ] **Slice gate:** lint, manual smoke (send a test notification, view log, edit template), tag `slice-6-notifications`.

---

## Slice 7 — Reports

Migrate [resources/js/Pages/Reports/](resources/js/Pages/Reports/).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs.**
- [ ] **Slice gate:** lint, manual smoke (run each report, verify chart/data render), tag `slice-7-reports`.

---

## Slice 8 — Exchange

Migrate [resources/js/Pages/Exchange/](resources/js/Pages/Exchange/): `Dashboard`, `Reconcile`.

- [ ] Per-file migration commits.
- [ ] **Add smoke specs.**
- [ ] **Slice gate:** lint, manual smoke, tag `slice-8-exchange`.

---

## Slice 9 — Settings + Admin

Migrate [resources/js/Pages/Settings/](resources/js/Pages/Settings/) (`WorkingHours`, `Clinic`) and [resources/js/Pages/Admin/](resources/js/Pages/Admin/) (`Users/Index`, etc.).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs.**
- [ ] **Slice gate:** lint, manual smoke (settings persist, admin user CRUD), tag `slice-9-settings-admin`.

---

## Slice 10 — Auth + Errors

Migrate [resources/js/Pages/Auth/](resources/js/Pages/Auth/) (Login, Register, Forgot Password, Reset Password, Verify Email) and [resources/js/Pages/Errors/](resources/js/Pages/Errors/) (401, 403, 404, 500, 503).

- [ ] Per-file migration commits.
- [ ] **Add smoke specs.**
- [ ] **Slice gate:** lint, manual smoke (full auth flow), tag `slice-10-auth-errors`.

---

## Final acceptance

- [ ] **Run the global grep**

```bash
grep -RInE 'primevue|@primevue|@primeuix|PrimeVue' resources/ resources/css/ resources/js/
```

Expected: no matches.

- [ ] **Run the full gate**

```bash
npm run build
npm test
npm run lint
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Expected: all clean.

- [ ] **Manual end-to-end smoke**

Login → every domain → create/edit/list/delete → dark mode toggle → mobile viewport → logout. No visual regressions.

- [ ] **Tag final release**

```bash
git tag daisyui-migration-complete
```

---

## Self-review (plan vs spec)

**Spec coverage:**
- §1 Goal & Scope → covered by overall plan structure and slice 0's foundation work.
- §2 Architecture (Tooling layer, Component rules, Component inventory, Layouts, Pages migration pattern, State, Date/Time caveat) → covered by tasks 0.1–0.11 (tooling), 0.12–0.22 (components), 0.6–0.11 (layouts/shell), 1.1–10 (pages), 0.17/0.19/0.21 (state for Dialog/Tabs/DatePicker/TimePicker).
- §3 Data flow, testing, error handling → Data flow: preserved by "keep `<script setup>` logic untouched" pattern across all slice tasks. Testing: tasks 0.12–0.22 each include a Vitest spec; smoke tests added per slice. Error handling: Input/Select/Textarea accept `error` prop and render `input-error` + `<p class="text-error">`; Dialog uses native `<dialog>` ESC/backdrop; DropdownMenu uses native DaisyUI `dropdown`.
- §4 Slice-level deliverables → 11 slice checklists present.
- §5 Risks & mitigations → addressed: native date pickers used (task 0.21); Calendar isolated to slice 3; ESLint rule scoped to `resources/js/**` (task 0.5).

**Placeholder scan:** No "TBD", "TODO", "implement later". One important note: tasks 0.9 (Sidebar) and 0.10 (TopBar, UserMenu, ColorModeToggle) instruct the executor to copy existing nav items / user fields from the current files rather than guessing. The plan explicitly says "preserve existing logic" so the executor is not authoring behavior. This is a known limitation of the wrapper pattern; the executor must read each legacy file before rewriting.

**Type consistency:** Component prop names match across tasks (`modelValue`, `error`, `invalid`, `loading`, `disabled`, `variant`, `size`, etc.). `Pagination` prop names match the spec (`page`, `perPage`, `total`, emits `page`). `Tabs` emits `update:modelValue` consistently with Input/Select. `Dialog` emits `update:open`. No drift.

**Spec gaps:** None. Each spec requirement traces to a task.

No issues to fix inline. Plan is ready.
