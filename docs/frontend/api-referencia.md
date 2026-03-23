# Referencia de API — Shapes exactos

Base URL: `http://localhost:8000/api/v1`

Todas las respuestas siguen el envelope:
```json
// Éxito
{ "ok": true, "data": { ... }, "message": "..." }

// Error
{ "ok": false, "error": { "code": "...", "message": "...", "details": { ... } } }
```

---

## Auth

### POST /auth/login

**Body:**
```json
{ "email": "string", "password": "string" }
```

**`data` en éxito:**
```json
{
  "token": "string",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "string",
    "email": "string",
    "roles": ["admin"],
    "permissions": ["clients.view", "..."]
  }
}
```

### GET /me *(auth)*

**`data`:**
```json
{
  "id": 1,
  "name": "string",
  "email": "string",
  "roles": ["admin"],
  "permissions": ["clients.view", "..."]
}
```

### POST /auth/logout *(auth)*

**`data`:** `{}`

### POST /auth/refresh *(auth)*

**`data`:**
```json
{ "token": "string", "token_type": "bearer", "expires_in": 3600 }
```

---

## Clientes

Requieren `auth`. Admin tiene acceso completo. Analyst solo puede listar y ver.

### GET /clients *(auth)*

**Query params opcionales:** `?search=texto&per_page=15`

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "name": "string",
      "contact_email": "string|null",
      "contact_phone": "string|null",
      "location": "string|null",
      "created_by": 1,
      "updated_by": 1,
      "created_at": "2026-03-17T...",
      "updated_at": "2026-03-17T...",
      "deleted_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### GET /clients/{id} *(auth)*

**`data`:** un objeto con el mismo shape que un ítem del listado.

### POST /clients *(auth — solo admin)*

**Body:**
```json
{
  "name": "string (requerido, único)",
  "contact_email": "string|null (email válido)",
  "contact_phone": "string|null (max 50)",
  "location": "string|null (max 255)"
}
```
**Respuesta: 201** con el cliente creado en `data`.

### PUT /clients/{id} *(auth — solo admin)*

**Body:** mismo shape que POST. **Respuesta: 200** con el cliente actualizado.

### DELETE /clients/{id} *(auth — solo admin)*

**`data`:** `{}`. Soft delete — el cliente no desaparece de la base de datos.

---

## Proyectos

Requieren `auth`. Admin tiene acceso completo. Analyst solo puede listar y ver.

### GET /projects *(auth)*

**Query params opcionales:** `?status=active&client_id=1&per_page=15`

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "client_id": 1,
      "name": "string",
      "status": "active",
      "started_at": "2026-01-01T...|null",
      "ended_at": "2026-06-01T...|null",
      "description": "string|null",
      "created_by": 1,
      "updated_by": 1,
      "created_at": "...",
      "updated_at": "...",
      "deleted_at": null
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 5 }
}
```

**Valores de `status`:** `active` | `completed` | `on_hold` | `archived`

### GET /projects/{id} *(auth)*

**`data`:** un objeto con el mismo shape del listado.

### POST /projects *(auth — solo admin)*

**Body:**
```json
{
  "client_id": 1,
  "name": "string (requerido)",
  "status": "active (requerido, enum)",
  "started_at": "2026-01-01 (nullable, fecha)",
  "ended_at": "2026-06-01 (nullable, fecha)",
  "description": "string (nullable)"
}
```

### PUT /projects/{id} *(auth — solo admin)*

**Body:** mismo shape que POST.

### DELETE /projects/{id} *(auth — solo admin)*

**`data`:** `{}`

---

## Muestras

### GET /samples *(auth)*

**Query params opcionales:**
- `status` — `pending` | `in_progress` | `completed` | `cancelled`
- `priority` — `standard` | `urgent`
- `client_id` — integer
- `project_id` — integer
- `received_from` — fecha `Y-m-d`
- `received_to` — fecha `Y-m-d`
- `per_page` — integer (default 15, max 100)

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "code": "SAMPLE-001",
      "status": "pending",
      "priority": "standard",
      "project_id": 1,
      "project_name": "Vaccine Development",
      "client_id": 1,
      "client_name": "BioTech Corp",
      "received_at": "2026-03-17",
      "latest_result_summary": {
        "result_summary": "string",
        "analyzed_at": "2026-03-18T...",
        "analyst_name": "string"
      },
      "latest_result_at": "2026-03-18T...|null",
      "results_count": 2,
      "rejection_count": 0,
      "created_by_name": "Admin User",
      "updated_at": "2026-03-18T..."
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 10 }
}
```

> `latest_result_summary` es `null` si la muestra no tiene resultados todavía.
> `rejection_count`: número de veces que la muestra regresó de `in_progress` a `pending`. Útil para mostrar un badge de retrabajo en la tabla.

### GET /samples/{id} *(auth)*

Devuelve el detalle completo incluyendo historial de resultados.

**`data`:**
```json
{
  "id": 1,
  "code": "SAMPLE-001",
  "status": "in_progress",
  "priority": "urgent",
  "project_id": 1,
  "project_name": "Vaccine Development",
  "client_id": 1,
  "client_name": "BioTech Corp",
  "received_at": "2026-03-17",
  "notes": "string|null",
  "analysis_started_at": "2026-03-18T...|null",
  "completed_at": null,
  "rejection_count": 2,
  "latest_result_summary": { "result_summary": "...", "analyzed_at": "...", "analyst_name": "..." },
  "latest_result_at": "...|null",
  "results_count": 1,
  "created_by_name": "Admin User",
  "updated_at": "...",
  "results": [
    {
      "id": 1,
      "result_summary": "string",
      "result_data": { "key": "value" },
      "analyzed_at": "2026-03-18T...",
      "analyst_name": "Analyst User"
    }
  ],
  "latest_result": {
    "id": 1,
    "result_summary": "string",
    "result_data": {},
    "analyzed_at": "...",
    "analyst_name": "..."
  }
}
```

### POST /samples *(auth — solo admin)*

**Body:**
```json
{
  "project_id": 1,
  "code": "SAMPLE-001 (requerido, único incluyendo soft-deleted)",
  "priority": "standard|urgent (requerido)",
  "received_at": "2026-03-17 (requerido, fecha Y-m-d)",
  "notes": "string (opcional)"
}
```

**Respuesta: 201** con `SampleDetailResource` en `data`. El `status` se crea siempre como `pending`.

### PUT /samples/{id} *(auth — solo admin)*

Solo actualiza `notes`.

**Body:**
```json
{ "notes": "string (opcional)" }
```

**`data`:** `SampleDetailResource`

### DELETE /samples/{id} *(auth — solo admin)*

Soft delete. **`data`:** `{}`

### POST /samples/{id}/restore *(auth — solo admin)*

Restaura una muestra soft-deleted.

**`data`:** `SampleDetailResource` con la muestra restaurada.

### PATCH /samples/{id}/status *(auth — admin y analyst)*

**Body:**
```json
{ "status": "pending|in_progress|completed|cancelled" }
```

**`data`:** `SampleDetailResource`

Comportamiento automático del backend:
- Si `pending → in_progress`: se registra `analysis_started_at` y evento `analysis_started`
- Si `* → completed`: se registra `completed_at` y evento `completed`
- En cualquier caso: se registra evento `status_changed`

### PATCH /samples/{id}/priority *(auth — solo admin)*

**Body:**
```json
{ "priority": "standard|urgent" }
```

**`data`:** `SampleDetailResource`

### POST /samples/{id}/results *(auth — admin y analyst)*

Agrega un resultado de análisis a la muestra.

**Body:**
```json
{
  "result_summary": "string (requerido)",
  "result_data": { "cualquier": "objeto JSON" }
}
```

**`data`:** `SampleDetailResource` actualizado con el nuevo resultado.

### GET /samples/{id}/events *(auth)*

Historial de eventos de la muestra, paginado, orden `created_at desc`.

**Query params:** `?per_page=20`

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "event_type": "status_changed",
      "description": "Status changed from pending to in_progress",
      "old_status": "pending",
      "new_status": "in_progress",
      "old_priority": null,
      "new_priority": null,
      "metadata": null,
      "user_name": "Analyst User",
      "created_at": "2026-03-18T..."
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 4 }
}
```

**Valores de `event_type`:** `created` | `updated` | `analysis_started` | `priority_changed` | `status_changed` | `result_added` | `completed` | `deleted` | `restored`

---

## Dashboard

Requieren `auth`. Accesibles para admin y analyst (permiso `dashboard.view`).

### GET /dashboard/metrics

**`data`:**
```json
{
  "total_samples": 120,
  "urgent_samples": 8,
  "pending_analysis": 34,
  "completion_rate": 65,
  "rejection_rate": 12
}
```

- `completion_rate`: entero 0–100 (porcentaje de muestras completadas)
- `pending_analysis`: muestras con status `pending` + `in_progress`
- `rejection_rate`: entero 0–100 (% de muestras con `rejection_count > 0` — "Tasa de Incidencias/Rechazos")

### GET /dashboard/recent-samples

**Query params opcionales:** `?limit=5` (default 5, max 50)

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "code": "SAMPLE-001",
      "status": "pending",
      "priority": "standard",
      "received_at": "2026-03-17",
      "project_id": 1,
      "project_name": "Vaccine Development",
      "client_id": 1,
      "client_name": "BioTech Corp",
      "latest_result_summary": "string|null",
      "latest_result_at": "...|null",
      "updated_at": "..."
    }
  ],
  "meta": { "count": 5 }
}
```

> En dashboard, `meta` solo tiene `count`. No hay `total`, `limit` ni `has_more`.

### GET /dashboard/recent-activity

**Query params opcionales:** `?limit=10` (default 10, max 100)

**`data`:**
```json
{
  "items": [
    {
      "id": 1,
      "event_type": "status_changed",
      "description": "Status changed from pending to in_progress",
      "sample_id": 5,
      "sample_code": "SAMPLE-001",
      "user_id": 2,
      "user_name": "Analyst User",
      "created_at": "2026-03-18T...",
      "metadata": {}
    }
  ],
  "meta": { "count": 10 }
}
```

> Igual que `recent-samples`: `meta` solo tiene `count`.

---

## Settings

Requieren `auth`. Cualquier usuario autenticado puede gestionar sus propios datos.

### GET /settings/profile

**`data`:**
```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@laboratory.local",
  "roles": ["admin"]
}
```

### PATCH /settings/profile

**Body:**
```json
{
  "name": "string (requerido, max 255)",
  "email": "string (requerido, email válido, único — ignorando el propio usuario)"
}
```

**`data`:** mismo shape que `GET /settings/profile`

### GET /settings/preferences

Si el usuario nunca guardó preferencias, el backend las crea con todos los valores en `false`.

**`data`:**
```json
{
  "notify_urgent_sample_alerts": false,
  "notify_sample_completion": false,
  "notify_daily_activity_digest": false,
  "notify_project_updates": false
}
```

### PATCH /settings/preferences

**Body** (todos requeridos):
```json
{
  "notify_urgent_sample_alerts": true,
  "notify_sample_completion": false,
  "notify_daily_activity_digest": true,
  "notify_project_updates": false
}
```

**`data`:** mismo shape que `GET /settings/preferences`

### POST /settings/change-password

**Body:**
```json
{
  "current_password": "string (requerido)",
  "password": "string (requerido, min 8, debe coincidir con password_confirmation)",
  "password_confirmation": "string (requerido)"
}
```

**Éxito (200):** `data: {}`

**Error contraseña incorrecta (422):**
```json
{
  "ok": false,
  "error": {
    "code": "INVALID_PASSWORD",
    "message": "The current password is incorrect.",
    "details": {}
  }
}
```

> El token JWT actual sigue siendo válido después de cambiar la contraseña.

---

## Códigos de error del sistema

| code | HTTP | Descripción |
|------|------|-------------|
| `UNAUTHENTICATED` | 401 | Token ausente, inválido o expirado |
| `INVALID_CREDENTIALS` | 401 | Email o contraseña incorrectos |
| `FORBIDDEN` | 403 | El usuario no tiene permiso para esa acción |
| `NOT_FOUND` | 404 | Recurso no existe o está soft-deleted |
| `VALIDATION_ERROR` | 422 | Error de validación — ver `error.details` para errores por campo |
| `INVALID_PASSWORD` | 422 | Contraseña actual incorrecta en change-password |
