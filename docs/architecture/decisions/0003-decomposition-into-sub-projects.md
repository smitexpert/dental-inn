# 0003 — Decomposition into sub-projects

**Date:** 2026-08-13
**Status:** Accepted

## Context

The dental practice management system contains 9+ independent subsystems (ledger, treatment records, doctors, payments, billing, expenses, money exchange, inventory, appointments, notifications). Building them all in a single design → plan → build cycle would produce a vague wishlist with many unexamined assumptions.

## Decision

We decompose into 7 phases, each with its own design spec and implementation plan:

0. **Foundation** — auth, roles, clinic settings, app shell, UI library
1. Doctors & Appointments
2. Patients & Treatment Records
3. Inventory
4. Billing, Payments & Ledger
5. Money Exchange
6. Notifications (SMS / WhatsApp)

Phases 1–6 each depend only on Foundation. Phases 4 and 5 additionally depend on the data they need from earlier phases (e.g. billing needs patients, money exchange needs billing).

## Consequences

- Each phase is small enough to design + plan + build in days, not months.
- The IA, design tokens, and component library are decided once in Foundation and reused.
- The cost is some duplication of design context — every phase spec re-states the stack and conventions.
