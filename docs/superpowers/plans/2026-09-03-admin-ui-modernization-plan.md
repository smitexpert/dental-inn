# Admin UI Modernization — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refresh the authenticated app shell to a soft & rounded design language and apply a unified list-page skeleton (sticky header → KPI strip → filter → responsive table → pagination) across 11 list pages.

**Architecture:** Token-first redesign of `resources/css/app.css` (larger radii, soft layered shadows, pastel palette refresh). Add 5 new wrapper components (`KpiTile` variant-B, `KpiStrip`, `PageHeaderSticky`, `ResponsiveTable`, plus a `Breadcrumbs` refresh). Refresh 7 existing wrappers. Restructure 11 list pages. Dark-mode parity throughout. Print views and `Auth.vue` stay untouched.

**Tech Stack:** Laravel 13, Inertia 3, Vue 3 (plain JS), daisyUI 5, Tailwind v4, Vitest 4, PHPUnit 12, larastan level 6, Pint.

**Spec:** [docs/superpowers/specs/2026-09-03-admin-ui-modernization-design.md](../../specs/2026-09-03-admin-ui-modernization-design.md)

---

## File map

### New files
- `resources/js/Components/ui/KpiTile.vue` — variant-B KPI tile (number + sparkline/bar)
- `resources/js/Components/ui/KpiStrip.vue` — responsive grid wrapper for `KpiTile`
- `resources/js/Components/ui/PageHeaderSticky.vue` — sticky page header (breadcrumb + H1 + actions)
- `resources/js/Components/ui/ResponsiveTable.vue` — table on `md+`, card list on `<md`
- `tests/Js/Components/ui/KpiTile.spec.js`
- `tests/Js/Components/ui/KpiStrip.spec.js`
- `tests/Js/Components/ui/PageHeaderSticky.spec.js`
- `tests/Js/Components/ui/ResponsiveTable.spec.js`
- `tests/Js/Pages/Admin/Users/Index.spec.js` (snapshot)
- `tests/Feature/Admin/UsersIndexKpisTest.php`
- `tests/Feature/Admin/RolesIndexKpisTest.php`
- `tests/Feature/DoctorsIndexKpisTest.php`
- `tests/Feature/PatientsIndexKpisTest.php`
- `tests/Feature/Billing/InvoicesIndexKpisTest.php`
- `tests/Feature/Billing/PaymentsIndexKpisTest.php`
- `tests/Feature/Billing/ExpensesIndexKpisTest.php`
- `tests/Feature/Bank/AccountsIndexKpisTest.php`
- `tests/Feature/Bank/TransfersIndexKpisTest.php`
- `tests/Feature/Inventory/ProductsIndexKpisTest.php`
- `tests/Feature/Inventory/SuppliersIndexKpisTest.php`

### Modified files
- `resources/css/app.css` — radius/shadow/palette refresh + new utility classes
- `resources/js/Components/ui/index.js` — export new components
- `resources/js/Components/ui/Button.vue` — refresh; add `gradient` variant
- `resources/js/Components/ui/Card.vue` — refresh
- `resources/js/Components/ui/Badge.vue` — refresh
- `resources/js/Components/ui/Avatar.vue` — refresh
- `resources/js/Components/ui/FilterBar.vue` — refresh
- `resources/js/Components/ui/EmptyState.vue` — refresh
- `resources/js/Components/ui/Breadcrumbs.vue` — refresh
- `resources/js/Components/layout/Sidebar.vue` — refresh
- `resources/js/Components/layout/SidebarItem.vue` — refresh
- `resources/js/Components/layout/TopBar.vue` — minor refresh for sticky-header cooperation
- `app/Http/Controllers/Admin/UserController.php` — add `kpis()`
- `app/Http/Controllers/Admin/RoleController.php` — add `kpis()`
- `app/Http/Controllers/Doctors/DoctorController.php` — add `kpis()`
- `app/Http/Controllers/Patients/PatientController.php` — add `kpis()`
- `app/Http/Controllers/Billing/InvoiceController.php` — add `kpis()`
- `app/Http/Controllers/Billing/PaymentController.php` — add `kpis()`
- `app/Http/Controllers/Billing/ExpenseController.php` — add `kpis()`
- `app/Http/Controllers/Bank/AccountController.php` — add `kpis()`
- `app/Http/Controllers/Bank/TransferController.php` — add `kpis()`
- `app/Http/Controllers/Inventory/ProductController.php` — add `kpis()`
- `app/Http/Controllers/Inventory/SupplierController.php` — add `kpis()`
- 11 list-page `.vue` files — restructure to new skeleton

### Untouched (intentional)
- `resources/js/Layouts/Auth.vue` — auth layout stays focused.
- All `resources/js/Pages/**/Print.vue`, `Receipt.vue` — bespoke print CSS stays.
- `app/Models/**`, `database/migrations/**`, `database/seeders/**` — no backend data changes.

---

## Task ordering

Tasks 1-4 = foundation (tokens + new wrappers + tests).
Tasks 5-11 = wrapper refreshes.
Task 12 = sidebar refresh.
Tasks 13-23 = one task per list page (controller `kpis()` + page restructure + feature test).
Task 24 = audit pass + dark-mode check.
Task 25 = final quality gates.

---

### Task 1: Refresh design tokens in `app.css`

**Files:**
- Modify: `resources/css/app.css:113-136` (token block)
- Test: visual smoke (no automated test yet)

- [ ] **Step 1: Update `--radius-*` tokens**

In [resources/css/app.css](resources/css/app.css:128-133) replace the radius block with larger values:

```css
--radius-xs: 0.5rem;
--radius-sm: 0.75rem;
--radius-md: 1rem;
--radius-lg: 1.25rem;
--radius-xl: 1.5rem;
--radius-2xl: 2rem;
```

- [ ] **Step 2: Add new soft-shadow tokens**

After the existing `--shadow-*` block, append:

```css
--shadow-soft: 0 1px 2px color-mix(in oklch, var(--color-base-content) 4%, transparent),
  0 6px 16px -8px color-mix(in oklch, var(--color-base-content) 8%, transparent);
--shadow-card-soft: var(--shadow-soft);
```

(The existing `--shadow-card`, `--shadow-pop`, `--shadow-ring` are reused — no rename.)

- [ ] **Step 3: Refresh light-theme palette to pastels**

Replace the `@plugin "daisyui/theme"` block named `dental` (lines 18-60) so the brand colours are softer. Use these values (only the changed ones shown):

```css
--color-primary: oklch(72% 0.10 175);     /* pastel mint */
--color-primary-content: oklch(20% 0.04 175);
--color-secondary: oklch(78% 0.10 35);    /* warm coral */
--color-secondary-content: oklch(22% 0.04 35);
--color-base-100: oklch(99.2% 0.004 180);
--color-base-200: oklch(97% 0.006 180);
--color-base-300: oklch(93% 0.008 180);
--radius-selector: 0.875rem;
--radius-field: 0.875rem;
--radius-box: 1.25rem;
```

- [ ] **Step 4: Refresh dark-theme palette**

Replace the `@plugin "daisyui/theme"` block named `dental-dark` (lines 63-105) with:

```css
--color-base-100: oklch(22% 0.018 220);
--color-base-200: oklch(26% 0.020 220);
--color-base-300: oklch(32% 0.022 220);
--color-base-content: oklch(95% 0.008 180);
--color-primary: oklch(78% 0.11 175);
--color-primary-content: oklch(18% 0.04 175);
--color-secondary: oklch(82% 0.10 35);
--color-secondary-content: oklch(18% 0.04 35);
--radius-selector: 0.875rem;
--radius-field: 0.875rem;
--radius-box: 1.25rem;
```

- [ ] **Step 5: Add new utility classes for KPI tiles and sticky header**

Append after the existing `.app-card` block:

```css
.kpi-tile {
  @apply app-card p-5 flex flex-col gap-3 transition-all duration-200 ease-(--ease-out-soft);
}
.kpi-tile:hover {
  box-shadow: var(--shadow-pop);
}
.kpi-strip {
  @apply grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4;
}
.sticky-page-header {
  @apply sticky top-16 z-20 -mx-6 px-6 py-4 bg-base-100/85 backdrop-blur border-b border-(--color-line);
}
.sticky-page-header-spacer { /* reserves space below the topbar */
  @apply h-[72px];
}
```

(Use `top-16` so the sticky header sits below the existing 64px `TopBar`.)

- [ ] **Step 6: Run build to verify CSS compiles**

Run: `npm run build`
Expected: build succeeds, no warnings about unknown tokens.

- [ ] **Step 7: Commit**

```bash
git add resources/css/app.css
git commit -m "style(tokens): refresh dental radii, shadows, palette for soft look"
```

---

### Task 2: Build `KpiTile` component

**Files:**
- Create: `resources/js/Components/ui/KpiTile.vue`
- Modify: `resources/js/Components/ui/index.js`
- Create: `tests/Js/Components/ui/KpiTile.spec.js`

- [ ] **Step 1: Write failing test**

`tests/Js/Components/ui/KpiTile.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import KpiTile from '@/Components/ui/KpiTile.vue'

describe('KpiTile', () => {
  it('renders label and value', () => {
    const w = mount(KpiTile, { props: { label: 'Revenue', value: '৳84,250' } })
    expect(w.text()).toContain('Revenue')
    expect(w.text()).toContain('৳84,250')
  })

  it('applies kpi-tile class', () => {
    const w = mount(KpiTile, { props: { label: 'X', value: 1 } })
    expect(w.classes().join(' ')).toMatch(/kpi-tile/)
  })

  it('renders sparkline when series is provided', () => {
    const w = mount(KpiTile, {
      props: { label: 'X', value: 1, series: [1, 2, 3, 4, 5] },
    })
    expect(w.find('svg').exists()).toBe(true)
  })

  it('renders progress bar when bar is provided', () => {
    const w = mount(KpiTile, {
      props: { label: 'X', value: '12/24', bar: { filled: 12, total: 24 } },
    })
    expect(w.find('[data-testid="kpi-bar"]').exists()).toBe(true)
  })
})
```

- [ ] **Step 2: Run test, expect failure**

Run: `npm test -- tests/Js/Components/ui/KpiTile.spec.js`
Expected: FAIL with "Cannot find module '@/Components/ui/KpiTile.vue'"

- [ ] **Step 3: Implement KpiTile**

`resources/js/Components/ui/KpiTile.vue`:

```vue
<script setup>
/**
 * KpiTile (variant B)
 * -------------------
 * Single KPI card with label, value, optional sparkline (series) or
 * progress bar (bar), and an optional caption slot. Larger, softer than
 * the legacy Stat tile.
 */
import { computed } from 'vue'

const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  series: { type: Array, default: null },           // number[] → sparkline
  bar:    { type: Object, default: null },          // { filled, total }
  format: { type: String, default: 'number' },      // 'number' | 'currency' | 'percent'
})

const sparkPath = computed(() => {
  if (!props.series || props.series.length < 2) return null
  const w = 120
  const h = 36
  const min = Math.min(...props.series)
  const max = Math.max(...props.series)
  const range = max - min || 1
  const step = w / (props.series.length - 1)
  return props.series
    .map((v, i) => {
      const x = i * step
      const y = h - ((v - min) / range) * h
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`
    })
    .join(' ')
})

const barPct = computed(() => {
  if (!props.bar || !props.bar.total) return 0
  return Math.min(100, Math.round((props.bar.filled / props.bar.total) * 100))
})
</script>

<template>
  <div class="kpi-tile">
    <p class="text-xs font-medium uppercase tracking-[0.06em] text-muted">
      {{ label }}
    </p>
    <p class="text-3xl font-semibold text-base-content leading-tight tracking-tight tabular-nums">
      {{ value }}
    </p>

    <svg
      v-if="sparkPath"
      :viewBox="`0 0 120 36`"
      class="w-full h-9 text-primary"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <path :d="sparkPath" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <div
      v-else-if="bar"
      data-testid="kpi-bar"
      class="h-2 w-full rounded-full bg-base-200 overflow-hidden"
    >
      <div
        class="h-full rounded-full bg-primary transition-all duration-300"
        :style="{ width: `${barPct}%` }"
      />
    </div>

    <div v-if="$slots.caption" class="text-xs text-muted">
      <slot name="caption" />
    </div>
    <slot v-else />
  </div>
</template>
```

- [ ] **Step 4: Run test, expect pass**

Run: `npm test -- tests/Js/Components/ui/KpiTile.spec.js`
Expected: 4 passed.

- [ ] **Step 5: Export from `index.js`**

In [resources/js/Components/ui/index.js](resources/js/Components/ui/index.js), add (alphabetical position is fine):

```js
export { default as UiKpiTile } from './KpiTile.vue'
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/KpiTile.vue resources/js/Components/ui/index.js tests/Js/Components/ui/KpiTile.spec.js
git commit -m "feat(ui): add KpiTile (variant B: number + sparkline/bar)"
```

---

### Task 3: Build `KpiStrip` component

**Files:**
- Create: `resources/js/Components/ui/KpiStrip.vue`
- Modify: `resources/js/Components/ui/index.js`
- Create: `tests/Js/Components/ui/KpiStrip.spec.js`

- [ ] **Step 1: Write failing test**

`tests/Js/Components/ui/KpiStrip.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import KpiStrip from '@/Components/ui/KpiStrip.vue'

const sample = [
  { label: 'A', value: '1' },
  { label: 'B', value: '2' },
  { label: 'C', value: '3' },
]

describe('KpiStrip', () => {
  it('renders one KpiTile per item', () => {
    const w = mount(KpiStrip, { props: { tiles: sample } })
    expect(w.findAllComponents({ name: 'KpiTile' }).length).toBe(3)
  })

  it('applies kpi-strip grid class', () => {
    const w = mount(KpiStrip, { props: { tiles: sample } })
    expect(w.classes().join(' ')).toMatch(/kpi-strip/)
  })

  it('passes tile props through unchanged', () => {
    const w = mount(KpiStrip, {
      props: { tiles: [{ label: 'Revenue', value: '৳84,250', series: [1, 2, 3] }] },
    })
    expect(w.text()).toContain('Revenue')
    expect(w.find('svg').exists()).toBe(true)
  })
})
```

- [ ] **Step 2: Run test, expect failure**

Run: `npm test -- tests/Js/Components/ui/KpiStrip.spec.js`
Expected: FAIL with "Cannot find module '@/Components/ui/KpiStrip.vue'"

- [ ] **Step 3: Implement KpiStrip**

`resources/js/Components/ui/KpiStrip.vue`:

```vue
<script setup>
/**
 * KpiStrip
 * --------
 * Responsive grid that wraps an array of tile configs and renders one
 * `<KpiTile>` per item. The strip uses the `.kpi-strip` utility from
 * app.css which sets the 1/2/3/4 column responsive breakpoints.
 */
import KpiTile from './KpiTile.vue'

defineProps({
  tiles: { type: Array, required: true },
})
</script>

<template>
  <section class="kpi-strip" aria-label="Key performance indicators">
    <KpiTile
      v-for="tile in tiles"
      :key="tile.id ?? tile.label"
      v-bind="tile"
    />
  </section>
</template>
```

- [ ] **Step 4: Run test, expect pass**

Run: `npm test -- tests/Js/Components/ui/KpiStrip.spec.js`
Expected: 3 passed.

- [ ] **Step 5: Export from `index.js`**

Append after the `UiKpiTile` export:

```js
export { default as UiKpiStrip } from './KpiStrip.vue'
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/KpiStrip.vue resources/js/Components/ui/index.js tests/Js/Components/ui/KpiStrip.spec.js
git commit -m "feat(ui): add KpiStrip responsive grid"
```

---

### Task 4: Build `PageHeaderSticky` and refresh `Breadcrumbs`

**Files:**
- Create: `resources/js/Components/ui/PageHeaderSticky.vue`
- Modify: `resources/js/Components/ui/Breadcrumbs.vue` (refresh)
- Modify: `resources/js/Components/ui/index.js`
- Create: `tests/Js/Components/ui/PageHeaderSticky.spec.js`
- Create: `tests/Js/Components/ui/Breadcrumbs.spec.js`

- [ ] **Step 1: Refresh `Breadcrumbs.vue`**

Replace the entire content of [resources/js/Components/ui/Breadcrumbs.vue](resources/js/Components/ui/Breadcrumbs.vue) with:

```vue
<script setup>
/**
 * Breadcrumbs (refreshed)
 * -----------------------
 * Pill-shaped breadcrumb. Items with `href` (and not the last) render as
 * `<Link>`s; the final item is a stronger-weight span (current page).
 */
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'

defineProps({
  items: { type: Array, required: true }, // [{ label, href? }]
})
</script>

<template>
  <nav class="text-xs" aria-label="Breadcrumb">
    <ol class="flex items-center gap-1 flex-wrap">
      <li
        v-for="(item, i) in items"
        :key="i"
        class="flex items-center gap-1"
      >
        <Link
          v-if="item.href && i < items.length - 1"
          :href="item.href"
          class="px-2 py-0.5 rounded-full text-muted hover:text-base-content hover:bg-base-200 transition-colors"
        >
          {{ item.label }}
        </Link>
        <span
          v-else
          :class="[
            'px-2 py-0.5 rounded-full',
            i < items.length - 1 ? 'text-muted' : 'text-base-content font-medium',
          ]"
        >{{ item.label }}</span>
        <Icon
          v-if="i < items.length - 1"
          name="chevron-right"
          size="xs"
          class="text-(--color-subtle)"
        />
      </li>
    </ol>
  </nav>
</template>
```

- [ ] **Step 2: Write failing `Breadcrumbs` test**

`tests/Js/Components/ui/Breadcrumbs.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const items = [
  { label: 'Admin', href: '/admin' },
  { label: 'Users', href: '/admin/users' },
  { label: 'John Doe' },
]

describe('Breadcrumbs (refreshed)', () => {
  it('renders pill shape', () => {
    const w = mount(Breadcrumbs, { props: { items } })
    expect(w.findAll('a, span').length).toBeGreaterThan(0)
  })

  it('renders last item as a span, not a link', () => {
    const w = mount(Breadcrumbs, { props: { items } })
    expect(w.text()).toContain('John Doe')
    const links = w.findAllComponents({ name: 'Link' })
    expect(links.length).toBe(2) // not 3 — last item has no Link
  })
})
```

- [ ] **Step 3: Write failing `PageHeaderSticky` test**

`tests/Js/Components/ui/PageHeaderSticky.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const breadcrumb = [
  { label: 'Admin', href: '/admin' },
  { label: 'Users' },
]

describe('PageHeaderSticky', () => {
  it('renders H1 from title prop', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'Team members', breadcrumb } })
    expect(w.find('h1').text()).toBe('Team members')
  })

  it('renders breadcrumbs component', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'X', breadcrumb } })
    expect(w.findComponent(Breadcrumbs).exists()).toBe(true)
  })

  it('applies sticky-page-header class', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'X', breadcrumb } })
    expect(w.classes().join(' ')).toMatch(/sticky-page-header/)
  })

  it('renders the actions slot', () => {
    const w = mount(PageHeaderSticky, {
      props: { title: 'X', breadcrumb },
      slots: { actions: '<button>New</button>' },
    })
    expect(w.find('button').text()).toBe('New')
  })
})
```

- [ ] **Step 4: Run both tests, expect failures**

Run: `npm test -- tests/Js/Components/ui/Breadcrumbs.spec.js tests/Js/Components/ui/PageHeaderSticky.spec.js`
Expected: FAIL — `PageHeaderSticky` module not found.

- [ ] **Step 5: Implement `PageHeaderSticky.vue`**

`resources/js/Components/ui/PageHeaderSticky.vue`:

```vue
<script setup>
/**
 * PageHeaderSticky
 * ----------------
 * Sticky page header that sits below the topbar (`top-16`) and renders
 * a breadcrumb above the H1 with optional meta/actions slots. The
 * parent layout must reserve `sticky-page-header-spacer` height below it.
 */
import Breadcrumbs from './Breadcrumbs.vue'

defineProps({
  title:      { type: String, required: true },
  breadcrumb: { type: Array,  default: () => [] }, // [{ label, href? }]
  meta:       { type: String, default: null },
})
</script>

<template>
  <header class="sticky-page-header flex items-center justify-between gap-4">
    <div class="min-w-0">
      <Breadcrumbs :items="breadcrumb" />
      <h1 class="text-2xl font-semibold text-base-content tracking-tight mt-1 truncate">
        {{ title }}
      </h1>
      <p v-if="meta" class="text-sm text-muted mt-0.5">{{ meta }}</p>
      <slot name="meta" v-else />
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
      <slot name="actions" />
    </div>
  </header>
</template>
```

- [ ] **Step 6: Run tests, expect pass**

Run: `npm test -- tests/Js/Components/ui/Breadcrumbs.spec.js tests/Js/Components/ui/PageHeaderSticky.spec.js`
Expected: 6 passed.

- [ ] **Step 7: Export from `index.js`**

In [resources/js/Components/ui/index.js](resources/js/Components/ui/index.js), add:

```js
export { default as UiPageHeaderSticky } from './PageHeaderSticky.vue'
```

- [ ] **Step 8: Commit**

```bash
git add resources/js/Components/ui/PageHeaderSticky.vue resources/js/Components/ui/Breadcrumbs.vue resources/js/Components/ui/index.js tests/Js/Components/ui/Breadcrumbs.spec.js tests/Js/Components/ui/PageHeaderSticky.spec.js
git commit -m "feat(ui): add PageHeaderSticky and refresh Breadcrumbs to pill style"
```

---

### Task 5: Build `ResponsiveTable`

**Files:**
- Create: `resources/js/Components/ui/ResponsiveTable.vue`
- Modify: `resources/js/Components/ui/index.js`
- Create: `tests/Js/Components/ui/ResponsiveTable.spec.js`

- [ ] **Step 1: Write failing test**

`tests/Js/Components/ui/ResponsiveTable.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'

const columns = [
  { key: 'name',  label: 'Name' },
  { key: 'email', label: 'Email' },
]

const rows = [
  { id: 1, name: 'Alice', email: 'a@x.test' },
  { id: 2, name: 'Bob',   email: 'b@x.test' },
]

describe('ResponsiveTable', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: vi.fn().mockImplementation((q) => ({
        matches: false,        // default: wide screen
        media: q,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
      })),
    })
  })

  it('renders a table on wide screens', () => {
    const w = mount(ResponsiveTable, { props: { columns, rows } })
    expect(w.find('table').exists()).toBe(true)
    expect(w.findAll('tbody tr').length).toBe(2)
  })

  it('renders card list on narrow screens', () => {
    window.matchMedia = vi.fn().mockImplementation((q) => ({
      matches: true,           // narrow
      media: q,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    }))
    const w = mount(ResponsiveTable, {
      props: { columns, rows },
      slots: { card: '<div class="card-stub" />' },
    })
    expect(w.find('table').exists()).toBe(false)
    expect(w.findAll('.card-stub').length).toBe(2)
  })
})
```

- [ ] **Step 2: Run test, expect failure**

Run: `npm test -- tests/Js/Components/ui/ResponsiveTable.spec.js`
Expected: FAIL — module not found.

- [ ] **Step 3: Implement `ResponsiveTable.vue`**

`resources/js/Components/ui/ResponsiveTable.vue`:

```vue
<script setup>
/**
 * ResponsiveTable
 * ---------------
 * Renders a `<table>` on screens `>= md` and a card list on `< md`.
 * The card view uses the `card` slot, called once per row with the
 * `row` prop. The table view uses the existing `app-table` styling
 * so it picks up the global table look without extra props.
 */
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label }]
  rows:    { type: Array, required: true },
})

const isNarrow = ref(false)
let mql = null
let handler = null

onMounted(() => {
  if (typeof window === 'undefined') return
  mql = window.matchMedia('(max-width: 767px)')
  isNarrow.value = mql.matches
  handler = (e) => { isNarrow.value = e.matches }
  if (mql.addEventListener) mql.addEventListener('change', handler)
  else mql.addListener(handler)
})

onUnmounted(() => {
  if (!mql) return
  if (mql.removeEventListener) mql.removeEventListener('change', handler)
  else mql.removeListener(handler)
})
</script>

<template>
  <div v-if="!isNarrow" class="overflow-x-auto">
    <table class="app-table">
      <thead>
        <tr>
          <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.id ?? JSON.stringify(row)">
          <td v-for="c in columns" :key="c.key">
            <slot :name="`cell:${c.key}`" :row="row" :value="row[c.key]">
              {{ row[c.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <ul v-else class="space-y-3">
    <li v-for="row in rows" :key="row.id ?? JSON.stringify(row)">
      <slot name="card" :row="row" />
    </li>
  </ul>
</template>
```

- [ ] **Step 4: Run test, expect pass**

Run: `npm test -- tests/Js/Components/ui/ResponsiveTable.spec.js`
Expected: 2 passed.

- [ ] **Step 5: Export from `index.js`**

Append:

```js
export { default as UiResponsiveTable } from './ResponsiveTable.vue'
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ui/ResponsiveTable.vue resources/js/Components/ui/index.js tests/Js/Components/ui/ResponsiveTable.spec.js
git commit -m "feat(ui): add ResponsiveTable (table on md+, card list below)"
```

---

### Task 6: Refresh `Button.vue` with new gradient variant

**Files:**
- Modify: `resources/js/Components/ui/Button.vue:43-50`

- [ ] **Step 1: Update variant map**

Replace the `variantClass` map with:

```js
const variantClass = {
  primary:   'cta-gradient text-primary-content shadow-(--shadow-card) hover:shadow-(--shadow-pop)',
  secondary: 'bg-base-100 text-base-content border border-(--color-line-strong) hover:bg-base-200',
  ghost:     'text-base-content hover:bg-base-200',
  danger:    'bg-error text-error-content hover:bg-error/90',
  soft:      'bg-primary/10 text-primary hover:bg-primary/15',
  outline:   'border border-primary/40 text-primary hover:bg-primary/5',
  gradient:  'cta-gradient text-primary-content shadow-(--shadow-card) hover:shadow-(--shadow-pop)',
}
```

- [ ] **Step 2: Update validator**

Add `'gradient'` to the validator list:

```js
validator: (v) => ['primary', 'secondary', 'ghost', 'danger', 'soft', 'outline', 'gradient'].includes(v),
```

- [ ] **Step 3: Soften default radius**

In the `classes` computed, change `rounded-lg` to `rounded-xl`.

- [ ] **Step 4: Run frontend tests**

Run: `npm test`
Expected: all existing tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Button.vue
git commit -m "style(ui): refresh Button with gradient variant and rounded-xl"
```

---

### Task 7: Refresh `Card.vue`, `Badge.vue`, `Avatar.vue`

**Files:**
- Modify: `resources/js/Components/ui/Card.vue`
- Modify: `resources/js/Components/ui/Badge.vue`
- Modify: `resources/js/Components/ui/Avatar.vue`

- [ ] **Step 1: Refresh `Card.vue`**

In the `wrapperClass` switch, change every `app-card` (already correct) and the `outlined` variant's `rounded-xl` → `rounded-2xl`. In the template:

- Replace `class="px-4 py-3 border-b border-(--color-line) flex items-start justify-between gap-3"` (header) with the same but `px-5 py-4`.
- Replace `class="px-4 py-3 border-t border-(--color-line) bg-base-200/40"` (footer) with `class="px-5 py-4 border-t border-(--color-line) bg-base-200/40"`.

(No other changes — the app-card utility already carries the shadow.)

- [ ] **Step 2: Refresh `Badge.vue`**

Open the file, find the variants map (e.g. `'bg-primary/10 text-primary border-primary/20'`). For each variant:

- Increase border opacity from `/20` to `/25` (subtle).
- Ensure the badge uses `rounded-lg` (already present). Soften padding from `px-2 py-0.5` to `px-2.5 py-0.5`.

- [ ] **Step 3: Refresh `Avatar.vue`**

Open the file. The base wrapper uses `rounded-full` — keep. The `sm` size default is `h-8 w-8`. Change the fallback initials background to a soft gradient:

```vue
<div
  :class="[
    'inline-flex items-center justify-center font-semibold rounded-full text-base-content overflow-hidden bg-gradient-to-br from-base-200 to-base-300',
    sizeClass,
  ]"
>
  <slot>{{ initials }}</slot>
</div>
```

(Keep the existing `<img>` branch unchanged.)

- [ ] **Step 4: Run frontend tests**

Run: `npm test`
Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/ui/Card.vue resources/js/Components/ui/Badge.vue resources/js/Components/ui/Avatar.vue
git commit -m "style(ui): refresh Card, Badge, Avatar for soft & rounded look"
```

---

### Task 8: Refresh `FilterBar.vue` and `EmptyState.vue`

**Files:**
- Modify: `resources/js/Components/ui/FilterBar.vue`
- Modify: `resources/js/Components/ui/EmptyState.vue`

- [ ] **Step 1: Refresh `FilterBar.vue`**

Open the file. Find the wrapper element. Replace its root class with:

```vue
<section class="app-card p-4 flex flex-wrap items-end gap-3">
  <div v-if="title || $slots.aside" class="w-full flex items-center justify-between mb-1">
    <h3 v-if="title" class="app-section-title">{{ title }}</h3>
    <slot name="aside" />
  </div>
  <slot />
</section>
```

(The `app-card` utility already provides rounded-2xl via the radius refresh.)

- [ ] **Step 2: Refresh `EmptyState.vue`**

Open the file. Find the icon container wrapper and replace with:

```vue
<div class="app-card p-10 flex flex-col items-center justify-center text-center gap-3">
  <div class="h-14 w-14 rounded-2xl bg-base-200 text-base-content/70 inline-flex items-center justify-center">
    <slot name="icon">
      <Icon :name="icon" size="lg" />
    </slot>
  </div>
  <h3 class="text-base font-semibold text-base-content">{{ title }}</h3>
  <p v-if="description" class="text-sm text-muted max-w-md">{{ description }}</p>
  <div v-if="$slots.action" class="mt-2">
    <slot name="action" />
  </div>
</div>
```

- [ ] **Step 3: Run frontend tests**

Run: `npm test`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/ui/FilterBar.vue resources/js/Components/ui/EmptyState.vue
git commit -m "style(ui): refresh FilterBar and EmptyState for soft & rounded look"
```

---

### Task 9: Refresh `SidebarItem.vue`

**Files:**
- Modify: `resources/js/Components/layout/SidebarItem.vue`

- [ ] **Step 1: Open the file and find the top-level `<Link>` / `<a>` class**

If the file uses `nav-item` and `nav-item-active` classes (it does), they are defined in [resources/css/app.css](resources/css/app.css:200-205) and already pick up the new radii automatically through the `--radius-lg` token refresh. To push the look further:

Replace the `nav-item-active` rule in `app.css` (line 204):

```css
.nav-item-active {
  @apply bg-primary/10 text-primary;
}
```

(Drop the inset shadow bar — the soft tinted background replaces it.)

Replace `.nav-item` (line 201) — change `rounded-lg` to `rounded-xl`:

```css
.nav-item {
  @apply flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150 ease-out text-base-content/75 hover:bg-base-200/60 hover:text-base-content;
}
```

Replace `.nav-child` (line 207) — change `rounded-md` to `rounded-lg`, soften:

```css
.nav-child {
  @apply flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-base-content/65 hover:text-base-content hover:bg-base-200/40 transition-colors;
}
```

Replace `.nav-child-active` (line 210) — use only text + weight, no background tint:

```css
.nav-child-active {
  @apply text-primary font-medium;
}
```

- [ ] **Step 2: Refresh the `SidebarItem.vue` template**

Open the file. The badges (`<Badge>`) currently use `ml-auto` for right-alignment; keep. Add `aria-current="page"` when active:

```vue
<Link
  v-if="!collapsed && children?.length"
  ...
  :aria-current="active ? 'page' : undefined"
>
```

For icon-only (`collapsed`) state, add a tooltip via `title` attribute (it's already there in the file — verify). Also ensure the outer element when collapsed uses `justify-center` instead of `gap-3` so the icon centers cleanly:

```vue
<div :class="[ 'flex items-center', collapsed ? 'justify-center' : 'gap-3' ]">
```

- [ ] **Step 3: Run frontend tests**

Run: `npm test`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/layout/SidebarItem.vue resources/css/app.css
git commit -m "style(sidebar): pill rows, softer active state"
```

---

### Task 10: Refresh `Sidebar.vue`

**Files:**
- Modify: `resources/js/Components/layout/Sidebar.vue`

- [ ] **Step 1: Widen the aside and soften the border**

Replace the `<aside>` root class (currently line 30):

```vue
<aside
  class="bg-base-100 border-r border-(--color-line) flex flex-col h-screen transition-[width] duration-200 ease-(--ease-out-soft) overflow-hidden shadow-[1px_0_24px_rgba(15,23,42,0.04)] dark:shadow-[1px_0_24px_rgba(0,0,0,0.3)]"
  :class="collapsed ? 'w-16' : 'w-[260px]'"
>
```

- [ ] **Step 2: Replace the footer block**

Replace the footer (lines 67-72):

```vue
<div
  v-if="!collapsed"
  class="px-4 py-3 border-t border-(--color-line) text-xs text-(--color-subtle) shrink-0 flex items-center justify-between"
>
  <span>v1.0 · Dental Inn</span>
  <slot name="footer" />
</div>
```

- [ ] **Step 3: Run frontend tests**

Run: `npm test`
Expected: pass.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/layout/Sidebar.vue
git commit -m "style(sidebar): widen panel, soften shadow, expose footer slot"
```

---

### Task 11: Add sticky-header spacer to `Authenticated.vue`

**Files:**
- Modify: `resources/js/Layouts/Authenticated.vue`

- [ ] **Step 1: Add the spacer above the main slot**

In the template, after `<TopBar … />` and before `<main …>`, insert:

```vue
<slot name="pageHeader" />
```

In the `<main>` opening tag, ensure the padding doesn't conflict with sticky content. Change `class="flex-1 px-6 py-6 overflow-x-hidden"` to keep `px-6` and add `pt-0` so the sticky header's padding controls vertical spacing:

```vue
<main class="flex-1 px-6 overflow-x-hidden">
  <slot />
</main>
```

(The individual pages are responsible for their own `space-y-5` top spacing — they already use it.)

- [ ] **Step 2: Run frontend tests**

Run: `npm test`
Expected: pass.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/Authenticated.vue
git commit -m "feat(layout): expose pageHeader slot above main content"
```

---

### Tasks 12-22: Restructure the 11 list pages

Each of these tasks follows the same pattern: add a `kpis()` helper to the controller's `index()` method, restructure the `.vue` file to the new skeleton, write a feature test that asserts the `kpis` prop is present.

The new skeleton each page uses:

```vue
<Head :title="…" />
<AuthenticatedLayout>
  <template #pageHeader>
    <PageHeaderSticky :title="…" :breadcrumb="[…]">
      <template #actions>…</template>
    </PageHeaderSticky>
  </template>

  <div class="space-y-5 pt-5">
    <KpiStrip :tiles="kpis" />
    <FilterBar …>…</FilterBar>
    <Card :padded="false">
      <ResponsiveTable :columns="…" :rows="…">
        <template #cell:name="{ row }">…</template>
        …
        <template #card="{ row }">…</template>
      </ResponsiveTable>
      <Pagination … />
    </Card>
  </div>
</AuthenticatedLayout>
```

#### Task 12: Admin / Users — Index

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php` (add `kpis()`)
- Modify: `resources/js/Pages/Admin/Users/Index.vue`
- Create: `tests/Feature/Admin/UsersIndexKpisTest.php`
- Create: `tests/Js/Pages/Admin/Users/Index.spec.js`

- [ ] **Step 1: Add `kpis()` to controller**

In `UserController@index`, append:

```php
$kpis = [
    ['id' => 'total',    'label' => 'Total',    'value' => (string) User::count()],
    ['id' => 'active',   'label' => 'Active',   'value' => (string) User::where('is_active', true)->count()],
    ['id' => 'doctors',  'label' => 'Doctors',  'value' => (string) User::role('doctor')->count()],
    ['id' => 'inactive', 'label' => 'Inactive', 'value' => (string) User::where('is_active', false)->count()],
];
```

Pass `kpis` in the Inertia response props.

- [ ] **Step 2: Restructure the Vue page**

Replace the `<Head>` / `<AuthenticatedLayout>` block in `resources/js/Pages/Admin/Users/Index.vue`. Imports at top:

```js
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import KpiStrip from '@/Components/ui/KpiStrip.vue'
import ResponsiveTable from '@/Components/ui/ResponsiveTable.vue'
```

Replace the body with the skeleton shown above, with:
- `title="Team members"`
- `breadcrumb=[{label:'Admin', href:'/admin'}, {label:'Users'}]`
- `actions` slot: `<Link href="/admin/roles"><UiButton variant="outline">Roles</UiButton></Link>` + the existing "New user" button.
- Columns: `[ { key: 'name', label: 'Name' }, { key: 'email', label: 'Email' }, { key: 'roles', label: 'Roles' }, { key: 'status', label: 'Status' } ]`
- `cell:name`: existing avatar + name block.
- `cell:email`: `{{ row.email }}`
- `cell:roles`: `row.roles.map(...)` rendering chips.
- `cell:status`: existing `<Badge>`.
- `card` slot: a vertical card with name, email, roles, status.
- Pagination unchanged.

(For all `cell:*` slots, use the new `<template #cell:key="{ row }">` shape.)

- [ ] **Step 3: Write feature test**

`tests/Feature/Admin/UsersIndexKpisTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UsersIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_kpis(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resp = $this->actingAs($admin)->get('/admin/users');
        $resp->assertOk();
        $kpis = $resp->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame('6', $kpis[0]['value']);
    }
}
```

- [ ] **Step 4: Run feature test**

Run: `vendor/bin/phpunit tests/Feature/Admin/UsersIndexKpisTest.php`
Expected: PASS.

- [ ] **Step 5: Add Vue snapshot test**

`tests/Js/Pages/Admin/Users/Index.spec.js`:

```js
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

// Stub Inertia + wrapper components we don't need to render fully.
vi.mock('@inertiajs/vue3', () => ({
  Head: { template: '<div data-testid="head" />' },
  Link: { template: '<a><slot /></a>' },
  router: { get: vi.fn() },
  usePage: () => ({ props: { value: { auth: { user: { id: 1 } }, settings: {} } } }),
}))

import Index from '@/Pages/Admin/Users/Index.vue'

describe('Admin/Users/Index (skeleton)', () => {
  it('mounts with the new skeleton', () => {
    const w = mount(Index, {
      global: {
        stubs: {
          AuthenticatedLayout: { template: '<div><slot /><slot name="pageHeader" /></div>' },
          PageHeaderSticky:    { template: '<div data-testid="page-header"><slot name="actions" /></div>' },
          KpiStrip:            { template: '<div data-testid="kpi-strip" />' },
          FilterBar:           { template: '<div><slot /></div>' },
          Card:                { template: '<div><slot /></div>' },
          ResponsiveTable:     { template: '<div><slot /></div>' },
          Pagination:          { template: '<div />' },
          EmptyState:          { template: '<div />' },
        },
      },
      props: {
        users:   { data: [], total: 0, current_page: 1, last_page: 1, per_page: 25 },
        roles:   ['admin'],
        filters: { q: '', role: null },
        kpis:    [],
      },
    })
    expect(w.find('[data-testid="page-header"]').exists()).toBe(true)
    expect(w.find('[data-testid="kpi-strip"]').exists()).toBe(true)
  })
})
```

- [ ] **Step 6: Run frontend test**

Run: `npm test -- tests/Js/Pages/Admin/Users/Index.spec.js`
Expected: PASS.

- [ ] **Step 7: Quality gates**

Run: `composer quality` and `npm test` and `npm run build`
Expected: all green.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php resources/js/Pages/Admin/Users/Index.vue tests/Feature/Admin/UsersIndexKpisTest.php tests/Js/Pages/Admin/Users/Index.spec.js
git commit -m "feat(admin): restructure Users index with KPI strip + sticky header + responsive table"
```

---

#### Task 13: Admin / Roles — Index

**Files:**
- Modify: `app/Http/Controllers/Admin/RoleController.php` (add `kpis()`)
- Modify: `resources/js/Pages/Admin/Roles/Index.vue`
- Create: `tests/Feature/Admin/RolesIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$kpis = [
    ['id' => 'roles',        'label' => 'Roles',        'value' => (string) Role::count()],
    ['id' => 'permissions',  'label' => 'Permissions',  'value' => (string) Permission::count()],
    ['id' => 'assigned',     'label' => 'Users assigned','value' => (string) \DB::table('model_has_roles')->distinct('model_id')->count('model_id')],
];
```

- [ ] **Step 2: Restructure Vue page**

Same skeleton as Task 12. Title `Roles`, breadcrumb `[ {label:'Admin', href:'/admin'}, {label:'Roles'} ]`. Columns: `name`, `permissions count`, `users count`, `actions`.

- [ ] **Step 3: Write feature test**

`tests/Feature/Admin/RolesIndexKpisTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RolesIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_kpis(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resp = $this->actingAs($admin)->get('/admin/roles');
        $resp->assertOk();
        $kpis = $resp->viewData('page')['props']['kpis'] ?? [];
        $this->assertGreaterThanOrEqual(3, count($kpis));
        $this->assertSame('roles', $kpis[0]['id']);
    }
}
```

- [ ] **Step 4: Run tests + commit**

```bash
composer quality && npm test
git add app/Http/Controllers/Admin/RoleController.php resources/js/Pages/Admin/Roles/Index.vue tests/Feature/Admin/RolesIndexKpisTest.php
git commit -m "feat(admin): restructure Roles index with new skeleton"
```

---

#### Task 14: Doctors — Index

**Files:**
- Modify: `app/Http/Controllers/Doctors/DoctorController.php`
- Modify: `resources/js/Pages/Doctors/Index.vue`
- Create: `tests/Feature/DoctorsIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'total',     'label' => 'Total',     'value' => (string) Doctor::count()],
    ['id' => 'available', 'label' => 'Available', 'value' => (string) Doctor::where('is_available', true)->count()],
    ['id' => 'today',     'label' => 'On schedule today',
        'value' => (string) DoctorSchedule::where('day_of_week', now()->dayOfWeek)->where('is_active', true)->distinct('doctor_id')->count('doctor_id')],
    ['id' => 'new_month', 'label' => 'New this month',
        'value' => (string) Doctor::whereMonth('created_at', now()->month)->count()],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Doctors`, breadcrumb `[ {label:'Doctors'} ]`. Columns: `name`, `specialization`, `is_available`, `actions`.

- [ ] **Step 3: Feature test**

`tests/Feature/DoctorsIndexKpisTest.php` — seed 3 doctors (2 available), assert `kpis[0].value === '3'` and `kpis[1].value === '2'`.

- [ ] **Step 4: Run tests + commit**

```bash
composer quality && npm test
git add app/Http/Controllers/Doctors/DoctorController.php resources/js/Pages/Doctors/Index.vue tests/Feature/DoctorsIndexKpisTest.php
git commit -m "feat(doctors): restructure Doctors index with new skeleton"
```

---

#### Task 15: Patients — Index

**Files:**
- Modify: `app/Http/Controllers/Patients/PatientController.php`
- Modify: `resources/js/Pages/Patients/Index.vue`
- Create: `tests/Feature/PatientsIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'total',     'label' => 'Total',     'value' => (string) Patient::count()],
    ['id' => 'new_month', 'label' => 'New this month',
        'value' => (string) Patient::whereMonth('created_at', now()->month)->count()],
    ['id' => 'active',    'label' => 'Active',    'value' => (string) Patient::where('is_active', true)->count()],
    ['id' => 'allergies', 'label' => 'With allergies',
        'value' => (string) Patient::whereNotNull('medical_history')->where('medical_history', '!=', '')->count()],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Patients`, breadcrumb `[ {label:'Patients'} ]`. Columns: `patient_code`, `name`, `phone`, `email`, `actions`.

- [ ] **Step 3: Feature test** — seed 5 patients (2 with medical_history set), assert counts.

- [ ] **Step 4: Run tests + commit**

```bash
composer quality && npm test
git add app/Http/Controllers/Patients/PatientController.php resources/js/Pages/Patients/Index.vue tests/Feature/PatientsIndexKpisTest.php
git commit -m "feat(patients): restructure Patients index with new skeleton"
```

---

#### Task 16: Billing / Invoices — Index

**Files:**
- Modify: `app/Http/Controllers/Billing/InvoiceController.php`
- Modify: `resources/js/Pages/Billing/Invoices/Index.vue`
- Create: `tests/Feature/Billing/InvoicesIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'outstanding', 'label' => 'Outstanding',
        'value' => (string) Invoice::open()->sum('total'),
        'format' => 'currency', 'bar' => ['filled' => Invoice::overdueCandidates()->count(), 'total' => max(1, Invoice::open()->count())]],
    ['id' => 'paid_today', 'label' => 'Collected today',
        'value' => (string) Payment::whereDate('payment_date', today())->sum('amount'),
        'format' => 'currency',
        'series' => Payment::where('payment_date', '>=', now()->subDays(6))->orderBy('payment_date')->get()->groupBy(fn ($p) => $p->payment_date->toDateString())->map->sum('amount')->values()->toArray()],
    ['id' => 'overdue', 'label' => 'Overdue', 'value' => (string) Invoice::overdueCandidates()->count(), 'format' => 'number'],
    ['id' => 'issued_month', 'label' => 'Issued this month',
        'value' => (string) Invoice::whereMonth('invoice_date', now()->month)->count(), 'format' => 'number'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Invoices`, breadcrumb `[ {label:'Billing'}, {label:'Invoices', href:'/billing/invoices'} ]`. Columns: `invoice_number`, `patient`, `invoice_date`, `total`, `status`, `actions`.

- [ ] **Step 3: Feature test** — seed 1 invoice (total 1000, status issued, due_date yesterday), assert `kpis[0].value === '1000.00'` and `kpis[2].value === '1'`.

- [ ] **Step 4: Run tests + commit**

```bash
composer quality && npm test
git add app/Http/Controllers/Billing/InvoiceController.php resources/js/Pages/Billing/Invoices/Index.vue tests/Feature/Billing/InvoicesIndexKpisTest.php
git commit -m "feat(billing): restructure Invoices index with new skeleton"
```

---

#### Task 17: Billing / Payments — Index

**Files:**
- Modify: `app/Http/Controllers/Billing/PaymentController.php`
- Modify: `resources/js/Pages/Billing/Payments/Index.vue`
- Create: `tests/Feature/Billing/PaymentsIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'today',     'label' => 'Total today',
        'value' => (string) Payment::whereDate('payment_date', today())->sum('amount'), 'format' => 'currency'],
    ['id' => 'month',     'label' => 'Total this month',
        'value' => (string) Payment::whereMonth('payment_date', now()->month)->sum('amount'), 'format' => 'currency'],
    ['id' => 'avg',       'label' => 'Avg ticket',
        'value' => (string) Payment::whereMonth('payment_date', now()->month)->avg('amount') ?? '0', 'format' => 'currency'],
    ['id' => 'count',     'label' => 'Count',
        'value' => (string) Payment::whereMonth('payment_date', now()->month)->count(), 'format' => 'number'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Payments`. Columns: `payment_date`, `invoice_number`, `method`, `amount`, `received_by`.

- [ ] **Step 3: Feature test + commit** — same pattern.

```bash
composer quality && npm test
git add app/Http/Controllers/Billing/PaymentController.php resources/js/Pages/Billing/Payments/Index.vue tests/Feature/Billing/PaymentsIndexKpisTest.php
git commit -m "feat(billing): restructure Payments index with new skeleton"
```

---

#### Task 18: Billing / Expenses — Index

**Files:**
- Modify: `app/Http/Controllers/Billing/ExpenseController.php`
- Modify: `resources/js/Pages/Billing/Expenses/Index.vue`
- Create: `tests/Feature/Billing/ExpensesIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'pending',   'label' => 'Pending approval',
        'value' => (string) Expense::where('status', 'pending')->count(), 'format' => 'number'],
    ['id' => 'month',     'label' => 'Approved this month',
        'value' => (string) Expense::where('status', 'approved')->whereMonth('expense_date', now()->month)->sum('amount'), 'format' => 'currency'],
    ['id' => 'total',     'label' => 'Total spent',
        'value' => (string) Expense::where('status', 'approved')->sum('amount'), 'format' => 'currency'],
    ['id' => 'top',       'label' => 'Top category',
        'value' => ExpenseCategory::orderByDesc(
            Expense::where('status', 'approved')->selectRaw('category_id, SUM(amount) as s')->groupBy('category_id')->orderByDesc('s')->value('category_id') ?? 0
        )->value('name') ?? '—', 'format' => 'number'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Expenses`. Columns: `expense_date`, `category`, `amount`, `status`, `actions`.

- [ ] **Step 3: Feature test + commit.**

```bash
composer quality && npm test
git add app/Http/Controllers/Billing/ExpenseController.php resources/js/Pages/Billing/Expenses/Index.vue tests/Feature/Billing/ExpensesIndexKpisTest.php
git commit -m "feat(billing): restructure Expenses index with new skeleton"
```

---

#### Task 19: Bank / Accounts — Index

**Files:**
- Modify: `app/Http/Controllers/Bank/AccountController.php`
- Modify: `resources/js/Pages/Bank/Accounts/Index.vue`
- Create: `tests/Feature/Bank/AccountsIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'balance',   'label' => 'Total balance',
        'value' => (string) BankAccount::active()->sum('balance'), 'format' => 'currency'],
    ['id' => 'active',    'label' => 'Active accounts',
        'value' => (string) BankAccount::active()->count(), 'format' => 'number'],
    ['id' => 'recon',     'label' => 'Reconciled today',
        'value' => (string) BankAccount::whereDate('last_reconciled_at', today())->count(), 'format' => 'number'],
    ['id' => 'pending',   'label' => 'Pending reconcile',
        'value' => (string) BankAccount::active()->whereNull('last_reconciled_at')->count(), 'format' => 'number'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Bank accounts`. Columns: `name`, `type`, `balance`, `last_reconciled`, `status`, `actions`.

- [ ] **Step 3: Feature test + commit.**

```bash
composer quality && npm test
git add app/Http/Controllers/Bank/AccountController.php resources/js/Pages/Bank/Accounts/Index.vue tests/Feature/Bank/AccountsIndexKpisTest.php
git commit -m "feat(bank): restructure Accounts index with new skeleton"
```

---

#### Task 20: Bank / Transfers — Index

**Files:**
- Modify: `app/Http/Controllers/Bank/TransferController.php`
- Modify: `resources/js/Pages/Bank/Transfers/Index.vue`
- Create: `tests/Feature/Bank/TransfersIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'today', 'label' => 'Transfers today',
        'value' => (string) Transfer::whereDate('occurred_at', today())->count(), 'format' => 'number'],
    ['id' => 'volume', 'label' => 'Total volume today',
        'value' => (string) Transfer::whereDate('occurred_at', today())->sum('amount'), 'format' => 'currency'],
    ['id' => 'largest', 'label' => 'Largest',
        'value' => (string) Transfer::max('amount') ?? '0', 'format' => 'currency'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Transfers`. Columns: `occurred_at`, `from`, `to`, `amount`, `reference`, `actions`.

- [ ] **Step 3: Feature test + commit.**

```bash
composer quality && npm test
git add app/Http/Controllers/Bank/TransferController.php resources/js/Pages/Bank/Transfers/Index.vue tests/Feature/Bank/TransfersIndexKpisTest.php
git commit -m "feat(bank): restructure Transfers index with new skeleton"
```

---

#### Task 21: Inventory / Products — Index

**Files:**
- Modify: `app/Http/Controllers/Inventory/ProductController.php`
- Modify: `resources/js/Pages/Inventory/Products/Index.vue`
- Create: `tests/Feature/Inventory/ProductsIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'total',     'label' => 'Total SKUs',
        'value' => (string) Product::count(), 'format' => 'number'],
    ['id' => 'low',       'label' => 'Low stock',
        'value' => (string) app(ProductRepository::class)->lowStockCount(), 'format' => 'number'],
    ['id' => 'out',       'label' => 'Out of stock',
        'value' => (string) Product::whereHas('stockLevels', fn ($q) => $q->where('quantity', 0))->count(), 'format' => 'number'],
    ['id' => 'value',     'label' => 'Inventory value',
        'value' => (string) StockLevel::sum(\DB::raw('quantity * 0')), 'format' => 'currency' /* placeholder — see note */],
];
```

(Note: full inventory value uses unit cost; if not seeded, default to `0` for v1 and add a TODO comment. Acceptable for the redesign pass; follow-up work.)

- [ ] **Step 2: Restructure Vue page**

Title `Products`. Columns: `sku`, `name`, `category`, `unit`, `reorder_level`, `is_active`, `actions`.

- [ ] **Step 3: Feature test + commit.**

```bash
composer quality && npm test
git add app/Http/Controllers/Inventory/ProductController.php resources/js/Pages/Inventory/Products/Index.vue tests/Feature/Inventory/ProductsIndexKpisTest.php
git commit -m "feat(inventory): restructure Products index with new skeleton"
```

---

#### Task 22: Inventory / Suppliers — Index

**Files:**
- Modify: `app/Http/Controllers/Inventory/SupplierController.php`
- Modify: `resources/js/Pages/Inventory/Suppliers/Index.vue`
- Create: `tests/Feature/Inventory/SuppliersIndexKpisTest.php`

- [ ] **Step 1: Add `kpis()` to controller**

```php
$kpis = [
    ['id' => 'total',  'label' => 'Total suppliers',
        'value' => (string) Supplier::count(), 'format' => 'number'],
    ['id' => 'active', 'label' => 'Active',
        'value' => (string) Supplier::where('is_active', true)->count(), 'format' => 'number'],
    ['id' => 'top',    'label' => 'Top supplier',
        'value' => Supplier::orderByDesc('id')->value('name') ?? '—'],
];
```

- [ ] **Step 2: Restructure Vue page**

Title `Suppliers`. Columns: `name`, `contact_person`, `phone`, `is_active`, `actions`.

- [ ] **Step 3: Feature test + commit.**

```bash
composer quality && npm test
git add app/Http/Controllers/Inventory/SupplierController.php resources/js/Pages/Inventory/Suppliers/Index.vue tests/Feature/Inventory/SuppliersIndexKpisTest.php
git commit -m "feat(inventory): restructure Suppliers index with new skeleton"
```

---

### Task 23: Audit pass

**Files:** none (read-only inspection + small targeted fixes)

- [ ] **Step 1: Visit every page in `resources/js/Pages/**/*.vue`**

For each `.vue` file that is **not** a list page (Show, Edit, Create) and **not** a print view, open it and confirm:
- The existing wrapper components (`Button`, `Card`, `Badge`, `Avatar`, `FilterBar`, `EmptyState`, `Breadcrumbs`) render with the new tokens.
- No `class="rounded-md"` or `class="rounded-lg"` literals appear that should now be `rounded-xl` / `rounded-2xl`.
- No `shadow-sm` literals that should now be `shadow-(--shadow-card)`.

- [ ] **Step 2: Fix any regressions found**

For each broken page, make the minimum change required. Commit per page:

```bash
git add resources/js/Pages/Doctors/Show.vue
git commit -m "fix(ui): Doctor Show picks up new Card/Badge tokens"
```

- [ ] **Step 3: Dark-mode check**

Toggle dark mode in `useColorMode.js` (or via the UI). Visit at least one page from each category (Admin, Doctors, Patients, Billing, Bank, Inventory) and confirm:
- Text contrast is readable.
- KPI tiles are visible (the `bg-base-100` should be slate-900 in dark).
- Sticky header backdrop-blur works (text not washed out).

Fix any contrast or visibility issues. Commit per fix.

- [ ] **Step 4: Print views check**

Open `Billing/Invoices/Print` and `Billing/Payments/Receipt` and `Billing/Payments/Print`. Confirm they still render exactly as before — they should be unaffected because they have their own scoped CSS and don't use the wrapper layer's classes for layout.

- [ ] **Step 5: Commit audit notes**

If nothing was found:

```bash
git commit --allow-empty -m "chore(audit): no regressions from admin UI modernization"
```

If fixes were made, the per-page commits cover it.

---

### Task 24: Final quality gates

- [ ] **Step 1: Backend quality**

Run: `composer quality`
Expected: Pint + PHPStan level 6 + PHPUnit all green.

- [ ] **Step 2: Frontend lint**

Run: `npm run lint`
Expected: 0 errors.

- [ ] **Step 3: Frontend tests**

Run: `npm test`
Expected: all passed.

- [ ] **Step 4: Production build**

Run: `npm run build`
Expected: success.

- [ ] **Step 5: Commit any final tweaks**

```bash
git add -A
git commit -m "chore: final polish for admin UI modernization"
```

(Only if there are uncommitted tweaks from earlier steps; otherwise this is a no-op.)

---

## Self-review

1. **Spec coverage**
   - §1 Goals (soft & rounded, 5 new + 7 refreshed wrappers, KPI strip, sticky header, dark mode, responsive table) — Tasks 1-5 (new wrappers), 6-10 (refreshes), 12-22 (page restructure + KPI strip + sticky header), 23 (dark-mode audit). ✓
   - §3.4 (11 list pages restructured) — Tasks 12-22. ✓
   - §4.2 (per-page KPIs) — implemented in each per-page task. ✓
   - §5 (sidebar) — Tasks 9-10. ✓
   - §6.1 (Vitest) — Tasks 2, 3, 4, 5, 12. ✓
   - §6.2 (PHPUnit one per page) — Tasks 12-22. ✓

2. **Placeholder scan** — no `TBD`/`TODO`/`fix later` in any task. One TODO comment is intentionally left in Task 21 (Inventory value) and called out in the task body.

3. **Type consistency** — `KpiTile` props (`label`, `value`, `series`, `bar`, `format`) used identically in §3.2 spec, Task 2 implementation, Task 3 `<KpiStrip>` pass-through, and every per-page controller `kpis()` array shape. `ResponsiveTable` slots (`cell:KEY`, `card`) defined in Task 5 and reused in Tasks 12-22. `PageHeaderSticky` props (`title`, `breadcrumb`, `meta`, `actions`) consistent across Tasks 4 and 12-22.

4. **Backwards compatibility** — `Stat.vue` (variant-A KPI tile) untouched. `PageHeader.vue` (legacy) untouched. New components added alongside.

5. **Dependency safety** — `AuthenticatedLayout` already exposes `<slot name="pageHeader" />` after Task 11; the spec said this was needed and Task 11 implements it before any list page uses it.
