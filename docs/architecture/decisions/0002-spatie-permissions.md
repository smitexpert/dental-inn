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
