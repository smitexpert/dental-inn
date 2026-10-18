# Admin UI Modernization — Design Spec

**Date:** 2026-09-03
**Status:** Approved (brainstorming)
**Owner:** Frontend / design system
**Stack:** Laravel 13 + Inertia 3 + Vue 3 (plain JS) + daisyUI 5 + Tailwind v4 (no library swaps)
**Scope:** Authenticated app shell — sidebar + page chrome + wrapper components + list-page skeleton. Print views and the auth layout are out of scope.

---

## 1. Goals

1. Refresh the entire authenticated app shell to a **soft & rounded** look — large radii, soft shadows, pastel mint/coral accents, generous whitespace — using the existing **daisyUI 5 + Tailwind v4** stack. No library swaps, no class renames in `daisyui/*`.
2. Replace ad-hoc CSS in pages with reusable wrapper components — add **5 new components** and **refresh 7 existing ones** so every list page shares one skeleton.
3. Add a **KPI strip** (variant B — number + sparkline/bar) above the table on every list page that has backing data.
4. Add a **sticky page header** with breadcrumb above every authenticated page, replacing today's `<PageHeader>` placement.
5. Add **dark mode parity** for the new design — `dental-dark` token refresh, every new component works in both themes.
6. Introduce a **responsive table** that gracefully degrades to a card list on `<md`.

## 2. Non-goals

- Replacing daisyUI or Tailwind.
- Rewriting print views (they have bespoke CSS that must not change).
- Touching the auth layout (`Auth.vue`).
- Adding new backend endpoints or migrations. Frontend-only redesign; existing Inertia contracts preserved (one new `kpis` prop per list page is the only contract addition).
- Multi-language UI strings.
- Adding new icons beyond what `Icon.vue` already exposes.

---

## 3. Architecture & token strategy

### 3.1 The single biggest lever: design tokens

Update `resources/css/app.css` to define new dental tokens inside the `@theme` block. Components that reference these tokens explicitly (via Tailwind utilities or daisyUI classes) automatically inherit the new values. Components that hard-code classes keep their current look until individually refreshed.

#### Token refresh table

| Token group | Today | New | Notes |
|---|---|---|---|
| `--radius-sm` | `4px` | `8px` | softer small radius |
| `--radius-md` | `8px` | `14px` | softer default |
| `--radius-lg` | `12px` | `20px` | cards, modals |
| `--radius-xl` | `16px` | `28px` | hero / KPI tiles |
| `--shadow-sm` | hard `0 1px 2px` | `--shadow-soft` (below) | layered soft |
| `--shadow-md` | hard `0 4px 8px` | `--shadow-pop` | layered soft |
| `--shadow-lg` | hard `0 10px 24px` | `--shadow-ring` | focus/hover halo |
| daisyUI theme `dental` | mid-saturation mint | pastel mint primary (`oklch(0.78 0.10 175)`), warm coral secondary (`oklch(0.78 0.13 35)`) | full OKLCH palette update |
| daisyUI theme `dental-dark` | generic dark | deep navy surfaces (slate-900 family), pastel mint accents, slate borders | parity with light theme |
| Spacing / typography | unchanged | unchanged | layouts must not shift |

New shadow tokens defined in `@theme`:

```css
--shadow-soft: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.06);
--shadow-pop:  0 4px 12px rgba(15, 23, 42, 0.08), 0 12px 32px rgba(15, 23, 42, 0.08);
--shadow-ring: 0 0 0 4px rgba(16, 185, 129, 0.18);  /* primary halo */
```

Dark-mode variants are declared under `:root[data-theme="dark"]` and `@media (prefers-color-scheme: dark) :root:not([data-theme="light"])`, matching the existing token infrastructure.

### 3.2 New wrapper components (5)

| Component | Purpose | Key props | Slots |
|---|---|---|---|
| `KpiTile.vue` | Single KPI card (variant B) | `label`, `value`, `format` (`'number'` \| `'currency'` \| `'percent'`), `series` (array of numbers → sparkline), `bar` (`{filled, total}` → progress bar) | `default` (overrides inline chart), `caption` |
| `KpiStrip.vue` | Grid of KpiTiles | `tiles` (array of KPI configs — shape passed straight to `KpiTile`) | none |
| `PageHeaderSticky.vue` | Sticky header (breadcrumb above H1, actions right) | `title`, `breadcrumb` (array of `{label, href?}`) | `actions`, `meta` |
| `ResponsiveTable.vue` | Table on `md+`, card list on `<md` | `columns`, `rows`, `cardView` (function for the small-screen fallback) | `card` (per-row content for mobile), `empty` |
| `SidebarItem.vue` (refreshed) | Sidebar row, pill style | `href`, `icon`, `badge`, `children` | none |

`Breadcrumbs.vue` already exists — it is **refreshed**, not new.

### 3.3 Refreshed existing wrappers (7)

| Component | Changes |
|---|---|
| `Button.vue` | Replace `rounded-md` → `rounded-xl`; `shadow-sm` → `shadow-soft`; add new `gradient` variant for primary actions (mint → teal gradient with `shadow-pop`) |
| `Card.vue` | Replace `rounded-lg` → `rounded-2xl`; `shadow-sm` → `shadow-soft`; ensure `border border-base-200/60` for definition |
| `Badge.vue` | Replace `rounded-md` → `rounded-lg`; soften color palette to pastels (`bg-primary/10 text-primary` style) |
| `Avatar.vue` | Same radius refresh; soft inner shadow; gradient fallback for unknown names (deterministic by hash) |
| `FilterBar.vue` | Replace `rounded-lg` → `rounded-2xl`; inset on the page rather than floating; pill-shaped inputs |
| `EmptyState.vue` | Larger icon container with `rounded-2xl bg-base-200/60`; softer copy weight |
| `SidebarItem.vue` | Pill style (`rounded-xl`), active = `bg-primary/10 text-primary`, hover = `bg-base-200/60` (see §5) |

`PageHeader.vue` is **kept for backwards compatibility** but deprecated. New code uses `PageHeaderSticky.vue`.

### 3.4 Page restructure

Eleven list pages adopt the same skeleton:

```
<PageHeaderSticky title="…" :breadcrumb="[…]">  <template #actions>…</template>  </PageHeaderSticky>
<KpiStrip :tiles="kpis" />
<FilterBar …>
<ResponsiveTable :columns="…" :rows="…">  <template #card="{row}">…</template>  </ResponsiveTable>
<Pagination … />
```

Pages:

- `Pages/Admin/Users/Index.vue`
- `Pages/Admin/Roles/Index.vue`
- `Pages/Doctors/Index.vue`
- `Pages/Patients/Index.vue`
- `Pages/Billing/Invoices/Index.vue`
- `Pages/Billing/Payments/Index.vue`
- `Pages/Billing/Expenses/Index.vue`
- `Pages/Bank/Accounts/Index.vue`
- `Pages/Bank/Transfers/Index.vue`
- `Pages/Inventory/Products/Index.vue`
- `Pages/Inventory/Suppliers/Index.vue`

Show / Edit / Create pages keep their existing structure and inherit the new tokens via the wrapper refresh. They are not actively restructured.

---

## 4. Data flow, errors, integration

### 4.1 New prop: `kpis`

Every redesigned list page receives a `kpis` prop from its controller. The shape is fixed and identical across pages; `<KpiStrip>` does not care about the underlying domain.

```js
kpis: [
  { id: 'issued_total', label: 'Outstanding', value: '142800', format: 'currency',
    bar: { filled: 12, total: 24 } },
  { id: 'paid_today',   label: 'Collected today', value: '84250', format: 'currency',
    series: [10, 22, 18, 30, 24, 32, 28] },
  { id: 'overdue_count',label: 'Overdue', value: '12', format: 'number' },
]
```

- `format` controls the right-aligned suffix/symbol. The wrapper respects `ClinicSettings.currency_symbol` and `currency_decimals`.
- `series` produces a sparkline `<svg>`; `bar` produces a progress `<div>`.
- Either is optional — a tile may have neither, in which case only the label/value/caption render.

### 4.2 Per-page KPIs

| Page | KPI 1 | KPI 2 | KPI 3 | KPI 4 |
|---|---|---|---|---|
| Users | Total | Active | Doctors | Inactive |
| Roles | Total roles | Total permissions | Users assigned | (3 KPIs) |
| Doctors | Total | Available | On schedule today | New this month |
| Patients | Total | New this month | Active | With allergies |
| Invoices | Outstanding (sum) | Collected today | Overdue count | Issued this month |
| Payments | Total today | Total this month | Avg ticket | Pending count |
| Expenses | Pending approval | Approved this month | Top category | Total spent |
| Bank/Accounts | Total balance | Active accounts | Reconciled today | Pending reconciles |
| Bank/Transfers | Transfers today | Total volume | Largest | (3 KPIs) |
| Inventory/Products | Total SKUs | Low stock | Out of stock | Inventory value |
| Inventory/Suppliers | Total suppliers | Active | Top supplier | (3 KPIs) |

Each controller's `index()` method gains a `kpis(): array` helper. No new DB roundtrips — reuse the `count()` / `sum()` patterns already in place.

### 4.3 Error handling (no change)

- Form validation errors continue to flow via Inertia's `errors` prop, rendered inline via `<Field :error="form.errors.x">`.
- The sticky header has `min-h-[64px]` so the layout doesn't shift on first paint.
- `Errors/403`, `Errors/404`, `Errors/500` pages inherit the new tokens automatically.

### 4.4 Integration points

- `useNav.js` — unchanged. The sidebar is rebuilt below.
- `Authenticated.vue` layout — refreshed to use the soft white panel sidebar.
- `Icon.vue` — unchanged; KPI sparklines use inline `<svg>`.
- Backend — unchanged except for the small `kpis()` helpers on the eleven `index()` methods.

---

## 5. Sidebar (soft white panel)

- Width: **260px** on `lg+`, **80px icon-only** on `md`, hidden on `<md` (drawer, same as today).
- Background: `bg-base-100` with `border-r border-base-200/60` and a soft right-edge shadow for depth.
- Top: clinic name + small circular logo placeholder (existing `ClinicSettings` props already shared).
- Items: `rounded-xl`, `px-3`, `py-2.5`, `gap-3`. Icon (20px) + label.
- Active item: `bg-primary/10 text-primary` (no left bar — the tinted background does the work).
- Hover (non-active): `bg-base-200/60`.
- Badge (e.g. low stock count): pill on the right, `bg-primary text-primary-content text-xs`.
- Nested groups (Inventory, Billing, Bank & Cash, Reports, Notifications):
  - Inactive parent: same as flat items but a chevron rotates `90deg` on open.
  - Active parent OR open: `bg-base-200/40`.
  - Children indent `pl-9`, `py-2`, smaller text (`text-sm`).
  - Active child: `text-primary font-medium` (no background tint).
  - Divider between groups: `border-t border-base-200/60`.
- Bottom (sticky): user avatar + name + role chip, color-mode toggle, sign-out.
- Icon-only (`md`): labels hidden, tooltips via `title` attribute. Nested groups open in a popover.

Dark mode parity: `bg-base-100` → slate-900; `border-base-200/60` → slate-800/60; active `bg-primary/15 text-primary` (slightly higher opacity).

---

## 6. Testing & quality gates

### 6.1 New Vitest tests

- `tests/Js/Components/ui/KpiTile.spec.js` — renders label + value; sparkline when `series`; progress bar when `bar`; `rounded-2xl` + `shadow-soft` classes; dark-mode classes apply.
- `tests/Js/Components/ui/KpiStrip.spec.js` — N tiles; 1/2/3/4 col grid; wraps at `md`.
- `tests/Js/Components/ui/PageHeaderSticky.spec.js` — breadcrumb + H1 + actions; `position: sticky` + `backdrop-blur` classes.
- `tests/Js/Components/ui/ResponsiveTable.spec.js` — table on `md+`, card list on `<md` (mocked matchMedia).
- `tests/Js/Components/ui/Breadcrumbs.spec.js` (refreshed) — last item is not a link; pill items.
- `tests/Js/Pages/Admin/Users/Index.spec.js` (snapshot) — page renders with new skeleton; filter debounce triggers Inertia.

### 6.2 New PHPUnit tests (one lean test per page)

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

Each: seed minimal fixtures, GET the index, assert `kpis` count and first KPI's label/value/format match.

### 6.3 Quality gates

- `composer quality` → Pint + PHPStan level 6 + PHPUnit green.
- `npm run lint` → 0 errors.
- `npm test` → Vitest passes.
- `npm run build` → production bundle succeeds.

### 6.4 Visual regression risk

Largest risk is accidental style breakage on Show / Edit / Create pages we don't actively touch. Mitigation: after the wrapper + token changes, do a **read-only audit pass** across all `Pages/**/*.vue`, fix anything visually broken. No automated visual regression tool — no Storybook in this project.

---

## 7. Out of scope

- Multi-clinic tenancy.
- Per-user UI customization / theming.
- Mobile app shell.
- New permissions or roles.
- Any backend table / migration changes.

---

## 8. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Token change causes regression in a page we didn't audit | Audit pass at the end; KPI strip / sticky header are gated behind explicit adoption so only the 11 redesigned pages change structurally |
| KPI queries slow the index page | KPIs reuse existing queries — no new roundtrips; one feature test per page catches N+1 regressions |
| `ResponsiveTable` breaks server-rendered table semantics | `<table>` markup stays on `md+`; only the `<md` fallback swaps to `<ul>` cards; both pass smoke tests |
| Dark-mode contrast regression | Every new component asserts dark classes in its spec; visual audit includes dark mode for one page per category |
| Sidebar icon-only mode hides critical labels | Tooltips on hover via `title` attribute (already used elsewhere); popover expansion for nested groups |

---

## 9. Definition of done

A reviewer can clone the repo, run `docker compose up -d`, visit `http://localhost`, and:

1. Sign in and land on the redesigned dashboard with the new KPI strip + sticky header.
2. Visit Users, Roles, Doctors, Patients, Invoices, Payments, Expenses, Bank Accounts, Bank Transfers, Products, Suppliers — each shows the new skeleton.
3. Resize the window to `<md` and see every table degrade gracefully to a card list.
4. Toggle dark mode — every new component renders correctly.
5. Run `composer quality`, `npm run lint`, `npm test`, `npm run build` — all green.
