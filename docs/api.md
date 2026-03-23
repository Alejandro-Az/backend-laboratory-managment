# API (v1)

Este documento resume el contrato vigente de endpoints principales del MVP.

## Envelope obligatorio

### Éxito

```json
{
  "ok": true,
  "data": {},
  "message": "Success"
}
```

### Error

```json
{
  "ok": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {}
  }
}
```

## Dashboard

Todos los endpoints requieren JWT (`auth:api`) y permiso `dashboard.view`.

### GET /api/v1/dashboard/metrics

- Devuelve métricas derivadas en tiempo real.
- No persiste snapshots/estadísticas en tablas.

`data`:
- `total_samples`
- `urgent_samples`
- `pending_analysis` (`pending` + `in_progress`)
- `completion_rate` (entero, 0 si no hay muestras)
- `rejection_rate` (entero, % de muestras con `rejection_count > 0` sobre el total; 0 si no hay muestras)

### GET /api/v1/dashboard/recent-samples

- Fuente: tabla `samples` (no soft-deleted), con relaciones cargadas (`project.client`, `latestResult`).
- Exclusión de soft-deleted: explícita en query (`whereNull(samples.deleted_at)`) y además coherente con el global scope de `SoftDeletes` en `Sample`.
- Orden: `created_at desc`.
- Límite por defecto: 5 (query param `limit` opcional).

`data`:
- `items`: lista corta para dashboard
- `meta`: **solo** `{ "count": N }`

### GET /api/v1/dashboard/recent-activity

- Fuente: tabla `sample_events`.
- Orden: `created_at desc`.
- Límite por defecto: 10 (query param `limit` opcional).
- Se aplica `whereHas('sample')`: los eventos de muestras soft-deleted no se exponen en dashboard.

`data`:
- `items`: actividad reciente
- `meta`: **solo** `{ "count": N }`

## Settings

Todos los endpoints requieren JWT (`auth:api`). No requieren permisos de rol adicionales — cualquier usuario autenticado puede gestionar su propio perfil.

### GET /api/v1/settings/profile

Devuelve el perfil editable del usuario autenticado.

`data`: `id`, `name`, `email`, `roles[]`

### PATCH /api/v1/settings/profile

Actualiza `name` y/o `email` del usuario autenticado.

- `email` valida unicidad ignorando el propio usuario.
- Solo actualiza los campos `name` y `email`.

`data`: mismo shape que `GET /settings/profile`.

### GET /api/v1/settings/preferences

Devuelve las preferencias de notificación. Si no existía fila previa, se crea con todos los valores en `false` (`firstOrCreate`).

`data`:
- `notify_urgent_sample_alerts` (boolean)
- `notify_sample_completion` (boolean)
- `notify_daily_activity_digest` (boolean)
- `notify_project_updates` (boolean)

### PATCH /api/v1/settings/preferences

Actualiza las 4 preferencias de notificación. Requiere los 4 campos (todos boolean).

`data`: mismo shape que `GET /settings/preferences`.

### POST /api/v1/settings/change-password

Cambia la contraseña del usuario autenticado.

Body: `current_password`, `password`, `password_confirmation`.

- Valida que `current_password` coincida con la contraseña real del usuario.
- Si no coincide: error `INVALID_PASSWORD` (422).
- El token JWT actual sigue siendo válido tras el cambio de contraseña (sin invalidación automática en este MVP).

`data`: objeto vacío `{}`.

---

## Convenciones cerradas relevantes

- `urgent` pertenece a `priority`, no a `status`.
- `status` válidos de `samples`: `pending`, `in_progress`, `completed`, `cancelled`.
- `priority` válidos de `samples`: `standard`, `urgent`.
