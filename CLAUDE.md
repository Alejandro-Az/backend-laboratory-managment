# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 API backend for a laboratory management admin panel. MVP scope: clients, projects, samples, dashboard metrics, and user settings. The React frontend is a separate project that consumes this API.

**Before proposing changes, read `CONTEXTO.md` and `AGENTS.md`.** `CONTEXTO.md` is the source of truth for stack, scope, closed decisions, domain rules, and API contracts.

## Common Commands

```bash
# First-time setup
composer setup

# Start all dev services (server + queue + logs + vite)
composer run dev

# Run tests
php artisan test
# or
./vendor/bin/phpunit

# Fresh database with seeds
php artisan migrate:fresh --seed

# Generate JWT secret (first setup only)
php artisan jwt:secret
```

## Architecture

**Laravel 12 · JWT auth (`tymon/jwt-auth`) · RBAC (`spatie/laravel-permission`) · SQLite (dev)**

All routes are under `/api/v1`. Two roles: `admin` and `analyst`.

### Request/Response Flow

```
Route → Middleware (auth:api) → Policy → FormRequest (validation) → Controller → Resource → ApiResponse envelope
```

### Key Directories

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Api/V1/` | Thin controllers — no business logic |
| `app/Http/Requests/Api/V1/` | All input validation via Form Requests |
| `app/Http/Resources/` | API response serialization |
| `app/Models/` | Eloquent models with relationships, scopes, enum casts |
| `app/Policies/` | Per-resource authorization |
| `app/Enums/` | `SampleStatus`, `SamplePriority`, `ProjectStatus`, `SampleEventType` |
| `app/Support/ApiResponse.php` | Centralized envelope builder |
| `database/migrations/` | Schema migrations |
| `docs/` | Technical documentation (in Spanish) |

### Domain Model

```
Client → Project → Sample → SampleResult
                          → SampleEvent (audit trail)
User → UserPreference
```

- `client_id` is **never** stored on `samples` — always traverse via `project`
- `urgent` is a `priority` value, not a `status` value
- Dashboard metrics are **derived from queries**, never persisted columns
- Recent activity comes from `sample_events`, not computed at runtime

### API Envelope (mandatory for all endpoints)

```json
// Success
{ "ok": true, "data": {}, "message": "Success" }

// Error
{ "ok": false, "error": { "code": "VALIDATION_ERROR", "message": "...", "details": {} } }
```

Use `app/Support/ApiResponse.php` — never return raw arrays or arbitrary structures.

### Sample Status & Priority Enums

- `status`: `pending` | `in_progress` | `completed` | `cancelled`
- `priority`: `standard` | `urgent`
- `project.status`: `active` | `completed` | `on_hold` | `archived`

### Main Entities with Soft Deletes

`clients`, `projects`, `samples` all use `SoftDeletes`. `sample_events` does not. All main entities track `created_by` and `updated_by`.

## Closed Architectural Decisions

Do not reopen these without documenting the reason:

1. No Filament — API-only backend
2. Auth via JWT Bearer Token only (no session mixing)
3. RBAC via Spatie Permission
4. Routes versioned under `/api/v1`
5. React frontend (separate repo, consumes this API)
6. Code in English, documentation in Spanish

## Implementation Rules

- Controllers must stay thin — logic belongs in models, form requests, and policies
- Use Form Requests for all validation
- Use Policies for all resource-level authorization
- Do not introduce new layers (services, repositories) unless strictly necessary for the current task
- Do not persist derived metrics (counts, rates) — always compute via queries
- Create small, atomic migrations; never modify existing migrations that have been run
- No ghost code: every file, method, and import must serve a real purpose

## Testing

Feature tests live in `tests/Feature/`. Tests must reflect real system behavior — no experimental or debugging tests. Minimum coverage expected:

- Authentication (login, protected routes, JWT refresh)
- CRUD operations with authorization checks per role
- Sample status and priority updates
- Validation error responses
- Soft delete and restore
