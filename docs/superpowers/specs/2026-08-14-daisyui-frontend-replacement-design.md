# DaisyUI Frontend Replacement — Design Spec

**Date:** 2026-08-14
**Status:** Draft (autonomous mode — user asked for no mid-design questioning)
**Approach:** A — vertical-slice, one domain at a time

## 1. Goal & Scope

### Goal
Replace this app's PrimeVue-based UI with a DaisyUI-only design system. Deliver as a sequence of independently shippable vertical slices (one domain at a time). End state: no PrimeVue in the codebase; consistent DaisyUI look across all pages; same user-facing functionality.

### Out of scope
- Backend changes (controllers, services, models, routes, jobs, requests, migrations, seeders).
- New features, UX changes, or behavior changes.
- Internationalization changes.
- Accessibility audits beyond what DaisyUI provides for free.
- Performance budgets / refactors unrelated to the swap.

### In scope
- Install DaisyUI as a Tailwind v4 plugin. Remove `primevue`, `@primevue/themes`, `@primeuix/themes`, `primevue-overrides.css`, PrimeVue plugin wiring.
- Build a new component kit in `resources/js/Components/ui/` styled purely with DaisyUI classes.
- Rewrite both layouts (`Authenticated.vue`, `Auth.vue`) using DaisyUI primitives.
- Replace `resources/css/app.css` with DaisyUI theme config. Delete `resources/css/primevue-overrides.css`.
- Migrate every page in `resources/js/Pages/` to use the new components.
- Replace the Vitest component test suite in `resources/js/__tests__/Components/ui/`.
- Each vertical slice: pick a domain → swap its pages → run that domain's tests + manual smoke → commit → next domain.

### Sequencing (vertical slices)
1. Slice 0 — Foundation: install DaisyUI, define theme, build the base component kit, build the two layouts. End of slice 0: app still loads; chrome is DaisyUI but pages are unconverted.
2. Slice 1 — Patients.
3. Slice 2 — Doctors.
4. Slice 3 — Appointments (hardest: Calendar.vue).
5. Slice 4 — Billing.
6. Slice 5 — Inventory.
7. Slice 6 — Notifications.
8. Slice 7 — Reports.
9. Slice 8 — Exchange.
10. Slice 9 — Settings + Admin.
11. Slice 10 — Auth + Errors.

### Acceptance gate at end of each slice
- No PrimeVue imports remain in the migrated domain.
- That domain's pages render and are usable end-to-end (manually smoke-tested).
- That domain's Vitest tests pass; new component tests cover the migrated kit.
- `npm run build` clean; `npm run lint` clean.
- `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` clean (sanity run; no PHP changes expected).

### Final acceptance (slice 10)
`grep -RInE 'primevue|@primevue|@primeuix|PrimeVue' resources/ resources/css/ resources/js/` returns no matches.

## 2. Architecture & Component Model

### Tooling layer
- **Tailwind v4** (already installed via `@tailwindcss/vite`) — keep using v4 syntax (`@theme`, `@plugin`, CSS-first config).
- **DaisyUI 5** added as a Tailwind v4 plugin: `@plugin "daisyui" { themes: corporate --default, business --prefersdark; }` in `resources/css/app.css`. `data-theme` on `<html>` swaps themes, matching the existing `ColorModeToggle` behavior.
- **PrimeVue removed**: uninstall `primevue`, `@primevue/themes`, `@primeuix/themes` from `package.json`. Delete `resources/css/primevue-overrides.css`. Remove the PrimeVue plugin registration in `resources/js/app.js`. Remove `PrimeVue` from `eslint.config.js` globals if present.

### New component kit — design rules
1. **No PrimeVue imports, ever.** Lint rule (`no-restricted-imports`) blocks `primevue/*` and `@primevue/*`.
2. **DaisyUI classes only for styling.** No raw Tailwind color tokens for theming (so theme switch works). Use `bg-base-100`, `text-base-content`, `btn`, `card`, `badge`, `input`, `select`, `checkbox`, `tabs`, `modal`, `drawer`, `dropdown`, `menu`, `table`, `join`, `pagination`, `loading`, `alert`, `divider`, `breadcrumbs`.
3. **Vue 3 Composition API + `<script setup lang="ts">`** — match existing codebase convention.
4. **Stable prop APIs.** Existing pages bind props like `:items`, `:columns`, `:model-value`, `@update:modelValue`, `@change`, `@page`. New components expose the same shape so pages migrate by search-and-replace on imports + tags, not by refactoring bindings.
5. **Emits standard Vue 3 conventions**: `v-model`, `update:modelValue`, plus semantic events (`change`, `page`, `select`, `close`).
6. **Accessibility by default via DaisyUI's semantic HTML**: `<dialog>` for modals, `<button>` for clickable items, `aria-*` only where DaisyUI doesn't already add it.

### Component inventory

| New component | Replaces | DaisyUI primitives |
|---|---|---|
| `Button.vue` | `<Button>` | `btn`, `btn-primary`, `btn-ghost`, `btn-sm`/`btn-lg` |
| `Input.vue` | `<InputText>`, `<Textarea>` | `input`, `textarea`, `input-bordered`, `label`, `input-error` |
| `Select.vue` | `<Select>` | `select`, `select-bordered` (native `<select>`) |
| `Checkbox.vue` | `<Checkbox>` | `checkbox` |
| `Switch.vue` | `<ToggleSwitch>` | `toggle` |
| `Card.vue` | `<Card>` | `card`, `card-body`, `card-title`, `card-actions` |
| `Table.vue` | `<DataTable>` | `table`, `table-zebra`; sorting handled in page via computed props; pagination lives in `Pagination.vue` |
| `Dialog.vue` | `<Dialog>` | DaisyUI `<dialog>` + `showModal()`; controlled via `v-model:open`; backdrop via `<form method="dialog">` |
| `DropdownMenu.vue` | `<Menu>` | DaisyUI `dropdown` + `menu`; trigger button + `tabindex="0"` content |
| `Tabs.vue` | `<Tabs>` / `<TabView>` | `tabs` + `tab-content`; v-model for active index |
| `Pagination.vue` | `<Paginator>` | `join` + `btn`; first/prev/next/last via `btn-square` icons |
| `DatePicker.vue` | `<DatePicker>` date mode | Native `<input type="date">` + `input-bordered` + `Intl.DateTimeFormat` |
| `TimePicker.vue` | `<DatePicker>` time mode | Native `<input type="time">` + `input-bordered` |
| `Spinner.vue` | `<ProgressSpinner>` | `loading loading-spinner loading-md` |
| `EmptyState.vue` | custom | DaisyUI `hero` / `card` + `text-base-content/60` body |
| `Breadcrumbs.vue` | `<Breadcrumb>` | DaisyUI `breadcrumbs` |
| `ToothChartPicker.vue` | unchanged logic | restyle with DaisyUI `btn` / `btn-active` |
| `ColorModeToggle.vue` | unchanged logic | DaisyUI `theme-controller` swap |
| `Sidebar.vue`, `SidebarItem.vue`, `TopBar.vue`, `UserMenu.vue` | unchanged logic | DaisyUI `drawer`, `menu`, `navbar`, `dropdown` |
| `AppShell.vue` (new) | shared chrome | `drawer` + `navbar` composition; both layouts use it |

### Layouts
- `Authenticated.vue`: DaisyUI `drawer` (mobile-aware sidebar), `navbar` for top bar with breadcrumbs slot, `dropdown` for user menu.
- `Auth.vue`: centered card on `bg-base-200`, no sidebar.
- Both share `AppShell.vue` for outer chrome (`<html data-theme>`, color-mode bootstrap, flash messages region).

### Pages migration pattern
For each page in a vertical slice:
1. Replace `import { ... } from 'primevue/...'` with imports from `@/Components/ui/...`.
2. Replace `<template>` PrimeVue tags with new equivalents (most 1:1 by name).
3. Keep all `<script setup>` logic untouched (props, composables, Inertia `useForm`, etc.).
4. Keep route bindings and Inertia `Link` usage untouched.

No controller, composable, or test-fixture changes. The migration is template-and-styling only.

### State for interactive components
`Dialog`, `DropdownMenu`, `Tabs`, `DatePicker`, `TimePicker` need local open/active state. Use Vue 3 `defineModel` where two-way binding matters (Dialog `open`, Tabs `active`, Input `modelValue`); emit events for the rest. Keep parity with existing wrappers so call sites don't change.

### Date/Time picker caveat
DaisyUI doesn't ship a calendar widget. **Decision (autonomous):** use native `<input type="date">` / `<input type="time">` styled with DaisyUI `input` classes. Functionally complete, accessible, zero JS. Trade-off: the calendar popup looks OS-native. Flagged as a follow-up if it looks jarring in the Appointments slice.

## 3. Data Flow, Testing, Error Handling

### Data flow
Nothing changes at the data layer:
- Inertia `Link` for navigation, `useForm` for forms, `router` for visits — unchanged.
- Composables in `resources/js/Composables/` — unchanged.
- Server-side controllers, services, requests — unchanged.
- Only the rendering layer changes; the contract between page and backend is preserved.

### Testing strategy
1. **Component tests** in `resources/js/__tests__/Components/ui/` — Vitest + Vue Test Utils + JSDOM. For each new component, test:
   - Renders expected DaisyUI class (`btn`, `input`, `card`, etc.) given default props.
   - Emits `update:modelValue` when user changes value (Input, Select, Checkbox, Switch, Tabs, DatePicker).
   - Emits `update:open` when Dialog opens/closes.
   - Emits `page` with right page number when Pagination is clicked.
   - Applies error state (`input-error`, `text-error`) when an error prop is passed.
   - Spinner/EmptyState/Breadcrumbs render right content from props.
   - Snapshot tests for `Sidebar`, `TopBar`, `UserMenu`, `Card`.
2. **Page-level smoke tests** — minimal Vitest specs that mount each page with stubbed Inertia props and assert: no PrimeVue in the rendered DOM, no console errors, key elements present. Catches accidental PrimeVue imports creeping back.
3. **Manual smoke checklist** per slice (kept in the plan, not automated): login → open domain → create → edit → list → delete → search/filter → pagination → dialog open/close → dark mode toggle → mobile viewport.

### Error handling
- Form validation errors come from Laravel via `useForm`'s `errors` object. New `Input.vue` accepts an `error` prop and renders `input-error` + `<p class="text-error text-xs">{{ error }}</p>` below the field. Same UX as today.
- `Dialog` uses `<dialog>` + `showModal()` (built-in ESC + focus trapping). Add `close` listener + `form method="dialog"` for the close button so the native dialog lifecycle handles backdrop clicks.
- `DropdownMenu` closes on outside click via `mousedown` listener (capture phase); closes on `Escape` via keydown listener. Both registered in `onMounted`, removed in `onUnmounted`.
- Spinner shown when `loading` prop is true; pages already pass `loading` from `useForm().processing`.

### Linting & build gates
- ESLint rule added: `no-restricted-imports` blocking `primevue`, `primevue/*`, `@primevue/*`, `@primeuix/*`.
- `npm run build` must succeed at end of each slice.
- `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` run at end of each slice.
- Final grep at end of slice 10: `grep -RInE 'primevue|@primevue|@primeuix|PrimeVue' resources/ resources/css/ resources/js/` returns no matches.

## 4. Slice-level deliverables

### Slice 0 — Foundation (before any domain)
**Deliverables**
- `package.json`: add `daisyui` (v5). Remove `primevue`, `@primevue/themes`, `@primeuix/themes`. Run `npm install`.
- `resources/css/app.css`: replace contents with Tailwind v4 + DaisyUI config. Drop `@import './primevue-overrides.css';`. Theme block: `@plugin "daisyui" { themes: corporate --default, business --prefersdark; }`. Keep font (`Instrument Sans`) if desired.
- Delete `resources/css/primevue-overrides.css`.
- `resources/js/app.js`: remove PrimeVue plugin registration.
- `eslint.config.js`: add `no-restricted-imports` rule blocking PrimeVue paths.
- Build new components: `Button`, `Input`, `Select`, `Textarea`, `Checkbox`, `Switch`, `Card`, `Table`, `Dialog`, `DropdownMenu`, `Tabs`, `Pagination`, `DatePicker`, `TimePicker`, `Spinner`, `EmptyState`, `Breadcrumbs`. Each with a Vitest spec.
- Build `AppShell.vue` + rewrite `Authenticated.vue` and `Auth.vue`.
- Rewrite `Sidebar`, `SidebarItem`, `TopBar`, `UserMenu`, `ColorModeToggle` to use DaisyUI classes (logic unchanged).
- Rewrite `ToothChartPicker` to use DaisyUI `btn` for tooth selection.

**Gate**
- `npm run build` clean.
- `npm test` clean.
- `npm run lint` clean.
- App loads on `/login` and `/dashboard` (legacy pages still using PrimeVue — that's fine).

### Slice 1 — Patients
Migrate `resources/js/Pages/Patients/`: `Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue`. Plus `resources/js/Components/PatientQuickAddDialog.vue`.

**Gate:** No PrimeVue imports in `resources/js/Pages/Patients/**` or `PatientQuickAddDialog.vue`. Manually: list → search → paginate → open patient → edit → save → quick-add → delete. Vitest spec for `PatientQuickAddDialog`.

### Slice 2 — Doctors
`resources/js/Pages/Doctors/`: `Index`, `Create`, `Edit`, `Show`.

### Slice 3 — Appointments
`resources/js/Pages/Appointments/`: `Index`, `Create`, `Edit`, `Show`, `Calendar`. Calendar is the hardest — full-day grid with appointment slots. Build a small DaisyUI `table`-based grid; date/time pickers already exist from slice 0.

### Slice 4 — Billing
`resources/js/Pages/Billing/`: Invoices, Payments, Accounts, Expenses. Heavy Table + Pagination + Tabs.

### Slice 5 — Inventory
`resources/js/Pages/Inventory/`: Suppliers, Locations, Products.

### Slice 6 — Notifications
`resources/js/Pages/Notifications/`: Templates, Send, Logs.

### Slice 7 — Reports
`resources/js/Pages/Reports/`.

### Slice 8 — Exchange
`resources/js/Pages/Exchange/`: Dashboard, Reconcile.

### Slice 9 — Settings + Admin
`resources/js/Pages/Settings/` + `resources/js/Pages/Admin/`.

### Slice 10 — Auth + Errors
`resources/js/Pages/Auth/` + `resources/js/Pages/Errors/`. `Auth.vue` layout was already redone in slice 0, so this is mostly form migration.

## 5. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Native `<input type="date">` looks off-brand | Acceptable trade-off; revisit with a custom calendar widget as a follow-up if needed |
| `Calendar.vue` is complex | Isolated to slice 3 with its own design notes in the plan |
| Tests churn heavily in slice 0 | Slice 0 is large on purpose — pays down debt so later slices are mechanical |
| User-visible visual change from PrimeVue → DaisyUI | Built-in `corporate`/`business` themes are professional/clinical |
| DaisyUI 5 + Tailwind v4 plugin syntax is newer | Verified: `@plugin` is the v4-correct way; documented in slice 0 |
| ESLint rule may break third-party deps | Scope `no-restricted-imports` to `resources/js/**` only |
