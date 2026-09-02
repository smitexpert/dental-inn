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
