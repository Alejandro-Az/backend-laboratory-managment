# Pantallas del panel

---

## Layout general

Todas las pantallas autenticadas comparten el mismo layout:

```
┌─────────────────────────────────────┐
│  Topbar (nombre usuario, logout)    │
├──────────┬──────────────────────────┤
│          │                          │
│ Sidebar  │   Área de contenido      │
│          │                          │
│ Dashboard│                          │
│ Samples  │                          │
│ Projects │                          │
│ Clients  │                          │
│ Settings │                          │
│          │                          │
└──────────┴──────────────────────────┘
```

El sidebar muestra los mismos ítems para ambos roles. Las restricciones se aplican dentro de cada pantalla (ocultando botones de acción que el analyst no puede usar).

**Cerrar sesión:** el botón de logout está en el **Topbar**, al hacer clic sobre el nombre o avatar del usuario autenticado. Llama a `POST /auth/logout` e invalida el token en el servidor, luego limpia localStorage y redirige a `/login`.

---

## Rutas sugeridas

```
/login                  → pantalla de login
/                       → redirige a /dashboard
/dashboard              → dashboard
/samples                → lista de muestras
/samples/:id            → detalle de muestra
/projects               → lista de proyectos
/clients                → lista de clientes
/settings               → configuración de usuario
```

---

## Login

**Ruta:** `/login`
**Autenticación:** no requerida (redirigir a `/dashboard` si ya hay token válido)

**Endpoint:** `POST /auth/login`

**Elementos:**
- Campo email
- Campo password
- Botón "Iniciar sesión" (disabled durante submit)
- Mensaje de error si `error.code === 'INVALID_CREDENTIALS'`

**Tras login exitoso:**
1. Guardar `data.token` y `data.user` en localStorage
2. Redirigir a `/dashboard`

---

## Dashboard

**Ruta:** `/dashboard`
**Roles:** admin, analyst
**Endpoints:**
- `GET /dashboard/metrics`
- `GET /dashboard/recent-samples`
- `GET /dashboard/recent-activity`

**Secciones:**

### Tarjetas de métricas (4 tarjetas)
Llamar `GET /dashboard/metrics` al montar.

| Tarjeta | Campo | Descripción |
|---------|-------|-------------|
| Total Samples | `total_samples` | Total de muestras |
| Urgent Samples | `urgent_samples` | Muestras con priority = urgent |
| Pending Analysis | `pending_analysis` | Muestras pending + in_progress |
| Completion Rate | `completion_rate` | Porcentaje completadas (0–100) |

### Recent Samples (tabla corta)
Llamar `GET /dashboard/recent-samples?limit=5`.

Columnas: Code, Client, Project, Received, Status, Priority

### Recent Activity (lista)
Llamar `GET /dashboard/recent-activity?limit=10`.

Mostrar: `user_name` hizo algo (`description`) en muestra `sample_code` hace X tiempo.

---

## Samples (lista)

**Ruta:** `/samples`
**Roles:** admin, analyst
**Endpoint principal:** `GET /samples`

**Filtros** (todos opcionales, enviados como query params):
- `status` — selector: Todos / Pending / In Progress / Completed / Cancelled
- `priority` — selector: Todos / Standard / Urgent
- `client_id` — selector (cargar lista de clientes)
- `project_id` — selector (filtrar por proyecto, opcionalmente dependiente del cliente seleccionado)
- `received_from` / `received_to` — rango de fechas

**Tabla:**

| Columna | Campo | Notas |
|---------|-------|-------|
| Code | `code` | |
| Client | `client_name` | |
| Project | `project_name` | |
| Received | `received_at` | Formato legible |
| Status | `status` | Badge de color |
| Priority | `priority` | Badge `urgent` en color llamativo |
| Actions | — | Ver / Editar / Eliminar |

**Colores de status sugeridos:**
- `pending` → gris / amarillo
- `in_progress` → azul
- `completed` → verde
- `cancelled` → rojo

**Botón "New Sample"** — visible solo para admin.

**Acciones por fila:**
- Ver → navegar a `/samples/:id`
- Editar → modal o inline (solo admin)
- Eliminar → confirmación → `DELETE /samples/:id` (solo admin)

**Paginación:** usar `meta.current_page`, `meta.last_page`, `meta.total`.

---

## Sample — Formulario de creación (modal)

**Endpoint:** `POST /samples`
**Rol requerido:** admin

**Campos:**
1. **Client** — selector de cliente (`GET /clients` para poblar). Al seleccionar cliente, filtrar proyectos.
2. **Project** — selector de proyecto (`GET /projects?client_id=X`). Requerido.
3. **Sample Code** — texto. Debe ser único (el backend lo valida).
4. **Priority** — radio o selector: Standard / Urgent
5. **Received Date** — date picker

**Al enviar:**
- Mostrar errores de `error.details` si el backend responde 422
- Refrescar la lista tras éxito

---

## Sample — Detalle

**Ruta:** `/samples/:id`
**Endpoint:** `GET /samples/:id`

**Secciones:**

### Info principal
- Code, Status, Priority, Client, Project
- Received at, Analysis started at, Completed at (si aplica)
- Notes

### Cambiar status
Endpoint: `PATCH /samples/:id/status`
Roles: admin y analyst

Botones o selector de status. Mostrar solo transiciones lógicas (o mostrar todas — el backend acepta cualquier combinación).

### Cambiar priority
Endpoint: `PATCH /samples/:id/priority`
Rol: solo admin (ocultar para analyst)

### Agregar resultado
Endpoint: `POST /samples/:id/results`
Roles: admin y analyst

Formulario con:
- `result_summary` (textarea)
- `result_data` (opcional, JSON o campos específicos según necesidad)

### Historial de resultados
Sección con todos los `results` del detalle (ordenados `analyzed_at desc`):
- Analyst name
- Fecha
- Result summary
- Result data (expandible)

### Bitácora de eventos
Endpoint: `GET /samples/:id/events`

Lista paginada de eventos. Columnas: Fecha, Tipo, Descripción, Usuario.

Mostrar `event_type` con etiquetas legibles:

| event_type | Texto |
|------------|-------|
| `created` | Muestra creada |
| `updated` | Notas actualizadas |
| `status_changed` | Estado cambiado |
| `priority_changed` | Prioridad cambiada |
| `analysis_started` | Análisis iniciado |
| `result_added` | Resultado agregado |
| `completed` | Completada |
| `deleted` | Eliminada |
| `restored` | Restaurada |

---

## Projects (lista)

**Ruta:** `/projects`
**Roles:** admin (CRUD), analyst (solo lectura)
**Endpoint:** `GET /projects`

**Filtros:** `?status=active&client_id=1`

**Vista de tarjetas o tabla:**
- Name
- Client (necesita join — el endpoint devuelve `client_id`, hacer un fetch de clientes o mostrar el ID. Considerar cargar clientes al montar.)
- Status (badge)
- Started at

**Botón "New Project"** — visible solo para admin.

**Acciones:** Ver detalle / Editar / Eliminar (solo admin).

---

## Projects — Formulario (modal)

**Endpoints:** `POST /projects` o `PUT /projects/:id`
**Rol:** admin

**Campos:**
- Client — selector (`GET /clients`)
- Name — texto
- Status — selector: Active / Completed / On Hold / Archived
- Started at — fecha (opcional)
- Ended at — fecha (opcional)
- Description — textarea (opcional)

---

## Clients (lista)

**Ruta:** `/clients`
**Roles:** admin (CRUD), analyst (solo lectura)
**Endpoint:** `GET /clients`

**Filtros:** `?search=texto`

**Tabla:**
- Name
- Contact email
- Contact phone
- Location
- Acciones: Editar / Eliminar (solo admin)

**Botón "New Client"** — visible solo para admin.

---

## Clients — Formulario (modal)

**Endpoints:** `POST /clients` o `PUT /clients/:id`
**Rol:** admin

**Campos:**
- Name — texto (requerido, único)
- Contact email — email (opcional)
- Contact phone — texto (opcional)
- Location — texto (opcional)

---

## Settings

**Ruta:** `/settings`
**Roles:** admin y analyst (cada uno gestiona sus propios datos)

### Perfil de usuario
Endpoints: `GET /settings/profile` y `PATCH /settings/profile`

Formulario con:
- Name
- Email
- Role (solo lectura — viene del perfil)

Botón "Guardar cambios".

### Preferencias de notificación
Endpoints: `GET /settings/preferences` y `PATCH /settings/preferences`

4 toggles o checkboxes:
- Urgent Sample Alerts
- Sample Completion Notifications
- Daily Activity Digest
- Project Updates

Guardar al hacer clic en "Guardar preferencias".

### Cambiar contraseña
Endpoint: `POST /settings/change-password`

Formulario con:
- Current password
- New password
- Confirm new password

Errores posibles:
- `VALIDATION_ERROR` con `details.password` → mostrar error de validación
- `INVALID_PASSWORD` → mostrar "La contraseña actual es incorrecta"

---

## Restricciones por rol — resumen

| Acción | admin | analyst |
|--------|:-----:|:-------:|
| Ver lista de clientes | ✓ | ✓ |
| Crear / editar / eliminar clientes | ✓ | — |
| Ver lista de proyectos | ✓ | ✓ |
| Crear / editar / eliminar proyectos | ✓ | — |
| Ver lista de muestras | ✓ | ✓ |
| Crear muestra | ✓ | — |
| Editar notas de muestra | ✓ | — |
| Eliminar / restaurar muestra | ✓ | — |
| Cambiar status de muestra | ✓ | ✓ |
| Cambiar priority de muestra | ✓ | — |
| Agregar resultado a muestra | ✓ | ✓ |
| Ver eventos de muestra | ✓ | ✓ |
| Ver dashboard | ✓ | ✓ |
| Gestionar perfil propio | ✓ | ✓ |

**Implementación:** ocultar (o deshabilitar) los botones de acción que el rol no puede usar. El backend igualmente devolverá 403 si el frontend los llama — pero la UX debe impedirlo antes.
