# 0001 — Caremint design system: daisyUI + Tailwind v4 tokens

**Date:** 2026-08-13 (original PrimeVue decision), revised 2026-09-02
**Status:** Accepted (supersedes the original PrimeVue unstyled pick)
**Related:** See [0001-primevue-unstyled-tailwind.md](0001-primevue-unstyled-tailwind.md) for the original decision that was later reversed in code.

## Context

We need a Vue 3 component layer for an admin-heavy dental practice management app that gives us accessibility, keyboard handling, and a complete component surface (DataTable, Calendar, Dialog, etc.) without forcing an opinionated look that clashes with our Tailwind utility classes. The original ADR picked PrimeVue 4 in `unstyled: true` mode wrapped by a thin layer that mapped PrimeVue tokens to Tailwind v4 `@theme` tokens.

In practice, maintaining that wrapper layer cost more than it saved — pass-through drift across PrimeVue patch releases, a separate token mapping to keep in sync, and a heavier install than the project actually needed. We decided to switch the wrapper layer to **daisyUI 5** (Tailwind v4 plugin), which gives us the same "one source of truth for the look-and-feel" without the upstream token-mapping debt.

## Decision

We chose **daisyUI 5** as a Tailwind v4 plugin, configured in `resources/css/app.css` with two custom themes:

- `caremint` — default light theme (soft clinical teal-mint primary, warm coral secondary, OKLCH palette).
- `caremint-dark` — `prefersdark` automatic dark variant of the same palette.

Spacing, radius, shadow, and ease are defined as CSS custom properties inside the same `@theme` block, so every component reads from one source. The wrapper layer in `resources/js/Components/ui/` is now built directly on daisyUI classes (`.btn`, `.input`, `.modal`, `.table`, …) plus the semantic tokens (`--color-line`, `--color-muted`, `--shadow-card/pop/ring`, `--ease-out-soft`). Icons are inline SVG copied from icones.js.org (Lucide collection), removing the icon-library dependency.

Every app page imports from `@/Components/ui`, never from `daisyui/*` directly — that wrapper layer owns the visual contract.

## Consequences

- One source of truth for the look-and-feel (the CSS file).
- Two themes ship in the codebase, both defined inline.
- No more PrimeVue / unstyled / pass-through / token-mapping drift.
- A wrapper layer must still be maintained; when daisyUI upgrades we may need to update class names or component APIs.
- Tests on wrapper components assert the expected daisyUI classes are present.
- If we ever want to swap daisyUI for something else, we replace the wrappers; app code is unaffected.