# Integración frontend

Este documento describe los contratos y convenciones que el frontend React debe respetar al consumir la API.

---

## Base URL

```
/api/v1
```

---

## Autenticación

El flujo de autenticación usa JWT Bearer Token.

1. `POST /api/v1/auth/login` → recibe `{ token, token_type, expires_in, user }`
2. Almacenar el token en memoria o `localStorage` (evaluar según requisitos de seguridad)
3. Incluir en cada petición protegida: `Authorization: Bearer <token>`
4. Renovar con `POST /api/v1/auth/refresh` antes de que expire
5. `POST /api/v1/auth/logout` para invalidar el token en servidor

---

## Envelope

Todas las respuestas siguen este formato:

```json
// Éxito
{ "ok": true, "data": {}, "message": "..." }

// Error
{ "ok": false, "error": { "code": "...", "message": "...", "details": {} } }
```

El cliente HTTP debe leer siempre `ok` para determinar el resultado.

### Códigos de error frecuentes

| code | HTTP | Situación |
|------|------|-----------|
| `UNAUTHENTICATED` | 401 | Token ausente o inválido |
| `FORBIDDEN` | 403 | Sin permiso para la operación |
| `VALIDATION_ERROR` | 422 | Falló validación — `details` tiene los errores por campo |
| `NOT_FOUND` | 404 | Recurso no encontrado o soft-deleted |
| `INVALID_CREDENTIALS` | 401 | Login con credenciales incorrectas |
| `INVALID_PASSWORD` | 422 | `change-password` con contraseña actual incorrecta |

### Mensajes de validación por campo

Los errores de validación llegan en `error.details` como un objeto con arrays por campo. El frontend puede mapearlos directamente a los inputs del formulario.

**Mensajes relevantes para muestras (`/samples`):**

| Campo | Situación | Mensaje |
|-------|-----------|---------|
| `project_id` | No enviado | `"A project is required."` |
| `project_id` | No existe o está eliminado | `"The selected project does not exist or has been deleted."` |
| `code` | No enviado | `"The sample code is required."` |
| `code` | Ya en uso | `"This sample code is already in use."` |
| `code` | Supera 100 caracteres | `"The sample code must not exceed 100 characters."` |
| `priority` | No enviado | `"The priority field is required."` |
| `priority` | Valor inválido | `"Invalid priority. Valid values are: standard, urgent."` |
| `received_at` | No enviado | `"The received date is required."` |
| `received_at` | Formato inválido | `"The received date must be a valid date."` |

**Mensajes para cambio de status (`PATCH /samples/{id}/status`):**

| Campo | Situación | Mensaje |
|-------|-----------|---------|
| `status` | No enviado | `"The status field is required."` |
| `status` | Valor inválido | `"Invalid status. Valid values are: pending, in_progress, completed, cancelled."` |

**Mensajes para cambio de priority (`PATCH /samples/{id}/priority`):**

| Campo | Situación | Mensaje |
|-------|-----------|---------|
| `priority` | No enviado | `"The priority field is required."` |
| `priority` | Valor inválido | `"Invalid priority. Valid values are: standard, urgent."` |

---

## Paginación

Los listados paginados responden con:

```json
{
  "data": {
    "items": [...],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    }
  }
}
```

Los endpoints de dashboard (`recent-samples`, `recent-activity`) usan un shape reducido:

```json
{
  "data": {
    "items": [...],
    "meta": { "count": 5 }
  }
}
```

---

## Roles y restricciones visibles

Roles disponibles: `admin`, `analyst`.

| Acción | admin | analyst |
|--------|-------|---------|
| Crear/editar/eliminar clientes | ✓ | — |
| Crear/editar/eliminar proyectos | ✓ | — |
| Crear/eliminar/restaurar muestras | ✓ | — |
| Editar muestra (notas) | ✓ | — |
| Cambiar `status` de muestra | ✓ | ✓ |
| Cambiar `priority` de muestra | ✓ | — |
| Agregar resultado a muestra | ✓ | ✓ |
| Ver dashboard | ✓ | ✓ |
| Gestionar su propio perfil/preferencias | ✓ | ✓ |

El frontend debe leer `data.user.roles` del login (o de `GET /me`) y ajustar la visibilidad de acciones según esta tabla.

---

## Convenciones de dominio

- La jerarquía es **Cliente → Proyecto → Muestra**
- El cliente de una muestra se obtiene a través de su proyecto (`project.client`), nunca hay `client_id` directo en muestra
- `urgent` es `priority`, no `status`
- `status` válidos: `pending`, `in_progress`, `completed`, `cancelled`
- `priority` válidos: `standard`, `urgent`
- Las métricas del dashboard son siempre calculadas en backend; no cachear ni calcular en frontend
- `rejection_count` en `samples`: contador de ciclos de control de calidad. Se incrementa automáticamente cada vez que el `status` regresa de `in_progress` a `pending`. El frontend puede mostrarlo como indicador de retrabajo en la vista de detalle de muestra.
- `rejection_rate` en las métricas del dashboard: porcentaje de muestras con `rejection_count > 0` sobre el total. Entero 0–100. Corresponde a la "Tasa de Incidencias/Rechazos".

---

## Endpoints principales por módulo

### Auth
- `POST /auth/login`
- `GET /me`
- `POST /auth/logout`
- `POST /auth/refresh`

### Clientes
- `GET /clients` — soporta `?search=`, `?per_page=`
- `POST /clients`
- `GET /clients/{id}`
- `PUT /clients/{id}`
- `DELETE /clients/{id}`

### Proyectos
- `GET /projects` — soporta `?status=`, `?client_id=`, `?per_page=`
- `POST /projects`
- `GET /projects/{id}`
- `PUT /projects/{id}`
- `DELETE /projects/{id}`

### Muestras
- `GET /samples` — soporta `?status=`, `?priority=`, `?project_id=`, `?client_id=`, `?received_from=`, `?received_to=`, `?per_page=`
- `POST /samples`
- `GET /samples/{id}`
- `PUT /samples/{id}`
- `DELETE /samples/{id}`
- `POST /samples/{id}/restore`
- `PATCH /samples/{id}/status` — body: `{ "status": "..." }`
- `PATCH /samples/{id}/priority` — body: `{ "priority": "..." }`
- `POST /samples/{id}/results` — body: `{ "result_summary": "...", "result_data": {} }`
- `GET /samples/{id}/events`

### Dashboard
- `GET /dashboard/metrics`
- `GET /dashboard/recent-samples` — soporta `?limit=`
- `GET /dashboard/recent-activity` — soporta `?limit=`

### Settings
- `GET /settings/profile`
- `PATCH /settings/profile` — body: `{ "name": "...", "email": "..." }`
- `GET /settings/preferences`
- `PATCH /settings/preferences` — body: 4 campos boolean
- `POST /settings/change-password` — body: `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`
