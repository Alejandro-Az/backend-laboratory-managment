# Base de Datos

## Objetivo

Este documento define el modelo de datos base del MVP de gestión de laboratorio.

El objetivo es mantener una base de datos:

- coherente con las pantallas analizadas
- proporcional al alcance del MVP
- limpia y fácil de mantener
- preparada para filtros, métricas y trazabilidad
- compatible con JWT + RBAC + panel administrativo React

---

## Principios de modelado

### 1. Jerarquía real del dominio
La jerarquía oficial del sistema es:

**Client -> Project -> Sample**

Reglas:

- un `client` tiene muchos `projects`
- un `project` tiene muchas `samples`
- una `sample` pertenece a un `project`
- el cliente de una muestra se obtiene por su proyecto

### 2. No duplicar `client_id` en `samples`
Aunque en la UI se vea el cliente dentro de la tabla de muestras, la relación real es:

- `samples.project_id -> projects.client_id`

Por lo tanto, `samples` **no debe** almacenar `client_id`.

### 3. `urgent` no es `status`
En la UI aparece un badge rojo “Urgent”, pero a nivel de dominio eso se modela como:

- `priority = urgent`

No como `status`.

Valores recomendados:

#### `status`
- `pending`
- `in_progress`
- `completed`
- `cancelled`

#### `priority`
- `standard`
- `urgent`

### 4. Métricas del dashboard son derivadas
Estas métricas no deben persistirse como columnas:

- total_samples
- urgent_samples
- pending_analysis
- completion_rate

Se calculan con queries sobre `samples`.

### 5. Actividad reciente proviene de bitácora
El bloque “Recent Activity” debe alimentarse desde una tabla persistida de eventos, no desde texto hardcodeado.

La tabla definida para esto es:

- `sample_events`

### 6. Auditoría mínima
Las entidades principales deben registrar:

- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at` cuando aplique

---

## Tablas base del MVP

Las tablas núcleo del MVP son:

- `users`
- tablas de Spatie Permission
- `clients`
- `projects`
- `samples`
- `sample_results`
- `sample_events`
- `user_preferences`

---

## Tabla `users`

Se utilizará la tabla estándar de Laravel como base de usuarios del sistema.

### Campos relevantes
- `id`
- `name`
- `email`
- `password`
- `is_active`
- `email_verified_at`
- `created_at`
- `updated_at`

### Notas
- los roles se gestionan con Spatie Permission
- no se define una columna `role` manual como fuente principal de verdad
- puede añadirse `is_active` para habilitar o deshabilitar acceso sin borrar usuarios

---

## Tabla `clients`

Representa organizaciones o clientes del laboratorio.

### Campos base
- `id`
- `name`
- `contact_email`
- `contact_phone`
- `location`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

### Notas
- `location` puede mantenerse como string simple en MVP
- `active_projects` y `total_samples` son métricas derivadas
- se recomienda soft delete

### Índices sugeridos
- índice por `name`

---

## Tabla `projects`

Representa proyectos asociados a un cliente.

### Campos base
- `id`
- `client_id`
- `name`
- `status`
- `started_at`
- `ended_at`
- `description`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

### Status recomendados
- `active`
- `completed`
- `on_hold`
- `archived`

### Notas
- cada proyecto pertenece a un cliente
- `total_samples` se calcula por relación
- se recomienda soft delete

### Índices sugeridos
- `client_id`
- `status`
- `started_at`
- índice compuesto `client_id + status`

---

## Tabla `samples`

Es la entidad central del sistema.

### Campos base
- `id`
- `project_id`
- `code`
- `status`
- `priority`
- `received_at`
- `analysis_started_at`
- `completed_at`
- `notes`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at`

### Status recomendados
- `pending`
- `in_progress`
- `completed`
- `cancelled`

### Priority recomendados
- `standard`
- `urgent`

### Notas
- no incluir `client_id`
- la tabla visible en frontend puede mostrar cliente con join a `projects` y `clients`
- se recomienda soft delete
- `code` debe ser único

### Índices sugeridos
- `project_id`
- `code` unique
- `status`
- `priority`
- `received_at`
- índice compuesto `project_id + status`
- índice compuesto `project_id + priority`

---

## Tabla `sample_results`

Almacena resultados asociados a una muestra.

### Campos base
- `id`
- `sample_id`
- `analyst_id`
- `result_summary`
- `result_data`
- `created_at`
- `updated_at`

### Notas
- permite almacenar resultado textual y/o estructura JSON
- para MVP puede manejarse como historial simple 1..N
- normalmente el frontend puede mostrar el más reciente

### Índices sugeridos
- `sample_id`
- `analyst_id`

---

## Tabla `sample_events`

Bitácora de eventos de muestra para trazabilidad y actividad reciente.

### Campos base
- `id`
- `sample_id`
- `user_id`
- `event_type`
- `description`
- `old_status`
- `new_status`
- `old_priority`
- `new_priority`
- `metadata`
- `created_at`

### Eventos recomendados
- `created`
- `updated`
- `analysis_started`
- `priority_changed`
- `status_changed`
- `result_added`
- `completed`
- `deleted`
- `restored`

### Notas
- alimenta “Recent Activity”
- sirve como trazabilidad mínima del sistema
- `metadata` puede usarse para datos adicionales sin inflar estructura inicial

### Índices sugeridos
- `sample_id`
- `user_id`
- `event_type`
- `created_at`
- índice compuesto `sample_id + created_at`

---

## Tabla `user_preferences`

Almacena preferencias del usuario vistas en Settings.

### Campos base
- `id`
- `user_id`
- `notify_urgent_sample_alerts`
- `notify_sample_completion`
- `notify_daily_activity_digest`
- `notify_project_updates`
- `created_at`
- `updated_at`

### Notas
- relación 1:1 con `users`
- `user_id` debe ser único
- esta tabla cubre el bloque de notificaciones del panel

---

## Relaciones principales

### Client -> Project
- un cliente tiene muchos proyectos
- un proyecto pertenece a un cliente

### Project -> Sample
- un proyecto tiene muchas muestras
- una muestra pertenece a un proyecto

### Sample -> SampleResult
- una muestra puede tener muchos resultados
- un resultado pertenece a una muestra

### Sample -> SampleEvent
- una muestra puede tener muchos eventos
- un evento pertenece a una muestra

### User -> SampleResult
- un usuario analista puede registrar resultados

### User -> UserPreference
- un usuario tiene una preferencia de usuario

### Auditoría por usuario
Las entidades principales pueden referenciar usuarios mediante:
- `created_by`
- `updated_by`

---

## Métricas derivadas esperadas

### Dashboard

#### Total Samples
`count(*)` sobre muestras activas

#### Urgent Samples
`count(*) where priority = urgent`

#### Pending Analysis
Definición recomendada:
- muestras con `status in (pending, in_progress)`

#### Completion Rate
`completed / total * 100`

### Clients

#### Active Projects
conteo de proyectos activos por cliente

#### Total Samples
conteo de muestras vía proyectos del cliente

### Projects

#### Total Samples
conteo de muestras asociadas al proyecto

---

## Política de soft deletes

Se recomienda usar soft deletes en:

- `clients`
- `projects`
- `samples`

### Razones
- preservar trazabilidad
- evitar pérdidas accidentales
- permitir restauración futura
- mantener compatibilidad con acciones de UI tipo “delete” sin borrar duro

No es necesario usar soft delete inicialmente en:
- `sample_results`
- `sample_events`
- `user_preferences`

---

## Foreign keys recomendadas

### `projects.client_id`
- referencia a `clients.id`
- `on delete restrict`

### `samples.project_id`
- referencia a `projects.id`
- `on delete restrict`

### `sample_results.sample_id`
- referencia a `samples.id`
- `on delete cascade`

### `sample_results.analyst_id`
- referencia a `users.id`
- `on delete restrict`

### `sample_events.sample_id`
- referencia a `samples.id`
- `on delete cascade`

### `sample_events.user_id`
- referencia a `users.id`
- `on delete set null`

### `user_preferences.user_id`
- referencia a `users.id`
- `on delete cascade`

### `created_by` y `updated_by`
Recomendación:
- referencia a `users.id`
- `on delete set null`

---

## Convención recomendada para estados y prioridad

Aunque en el diagrama conceptual se usen enums, en Laravel se recomienda implementar inicialmente como `string` controlado por:

- enums PHP nativos, o
- constantes centralizadas

### Razones
- mayor flexibilidad en migraciones futuras
- menor fricción si cambia el catálogo de estados
- mejor compatibilidad con validaciones y casting del framework

---

## Orden recomendado de migraciones

1. tablas base ya existentes de Laravel
2. migraciones de Spatie Permission
3. `clients`
4. `projects`
5. `samples`
6. `sample_results`
7. `sample_events`
8. `user_preferences`

---

## Decisiones cerradas de base de datos

Estas decisiones se consideran fuente de verdad:

- `samples` no tendrá `client_id`
- `urgent` es `priority`, no `status`
- métricas del dashboard son derivadas
- `sample_events` alimenta actividad reciente
- entidades principales registran auditoría mínima
- `clients`, `projects` y `samples` usan soft delete
- roles y permisos se resuelven con Spatie

---

## Futuras extensiones posibles

Estas piezas pueden agregarse después si el proyecto lo requiere, pero no forman parte del núcleo actual:

- historial de login
- intentos de acceso fallidos
- 2FA real
- sistema de notificaciones persistidas
- export jobs / import jobs
- adjuntos por muestra
- comentarios internos
- catálogos normalizados de ubicación
- contactos múltiples por cliente

No deben incorporarse al MVP si no existe una necesidad concreta y validada.
